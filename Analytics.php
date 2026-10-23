<?php
/**
 * Analytics — расчёт метрик для owner'ов и carrier'ов
 * 
 * Использование:
 *   require_once BASE_PATH . '/app/lib/Analytics.php';
 *   $analytics = new Analytics($pdo);
 *   $data = $analytics->getOwnerStats($userId, 30);
 */

class Analytics
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * ============================================================
     * ОБЩИЕ МЕТОДЫ
     * ============================================================
     */
    
    /**
     * Расстояние между двумя городами (км) по формуле гаверсинуса
     */
    public function getDistance(string $fromCity, string $toCity): ?float
    {
        $from = $this->findCity($fromCity);
        $to = $this->findCity($toCity);
        
        if (!$from || !$to) return null;
        
        $lat1 = deg2rad((float)$from['lat']);
        $lng1 = deg2rad((float)$from['lng']);
        $lat2 = deg2rad((float)$to['lat']);
        $lng2 = deg2rad((float)$to['lng']);
        
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlng / 2) ** 2;
        $c = 2 * asin(sqrt($a));
        $earthRadius = 6371;
        
        return round($earthRadius * $c);
    }
    
    /**
     * Найти город в справочнике (по названию)
     */
    private function findCity(string $name): ?array
    {
        $name = mb_strtolower(trim($name));
        
        // Извлекаем только первое слово (город)
        $parts = preg_split('/[,\s]+/u', $name);
        $search = $parts[0] ?? $name;
        
        $stmt = $this->pdo->prepare("SELECT lat, lng FROM cities_coords WHERE name_lower = ? LIMIT 1");
        $stmt->execute([$search]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    /**
     * ============================================================
     * ОБЩАЯ СВОДКА (30/90/365 дней)
     * ============================================================
     */
    
    public function getDateRange(int $days): array
    {
        $now = time();
        return [
            'from' => date('Y-m-d H:i:s', $now - $days * 86400),
            'prev_from' => date('Y-m-d H:i:s', $now - 2 * $days * 86400),
            'prev_to' => date('Y-m-d H:i:s', $now - $days * 86400),
        ];
    }
    
    /**
     * ============================================================
     * АНАЛИТИКА ДЛЯ OWNER
     * ============================================================
     */
    
    public function getOwnerStats(int $userId, int $days = 30): array
    {
        $range = $this->getDateRange($days);
        
        // 1. Сводные метрики за период
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN status IN ('accepted','pickup','loaded','in_transit','arrived','unloaded') THEN 1 ELSE 0 END) as active_orders,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as total_spent
            FROM orders 
            WHERE user_id = ? AND created_at >= ?
        ");
        $stmt->execute([$userId, $range['from']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. Те же метрики за предыдущий период (для расчёта тренда)
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as total_spent
            FROM orders 
            WHERE user_id = ? AND created_at >= ? AND created_at < ?
        ");
        $stmt->execute([$userId, $range['prev_from'], $range['prev_to']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Отклики на заказы owner'а
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as bids_count
            FROM bids b
            JOIN orders o ON b.order_id = o.id
            WHERE o.user_id = ? AND b.created_at >= ?
        ");
        $stmt->execute([$userId, $range['from']]);
        $bidsCount = (int)$stmt->fetch(PDO::FETCH_COLUMN);
        
        // 4. Среднее время до первого отклика (в часах)
        $stmt = $this->pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, b.first_bid)) as avg_hours
            FROM orders o
            JOIN (
                SELECT order_id, MIN(created_at) as first_bid
                FROM bids GROUP BY order_id
            ) b ON b.order_id = o.id
            WHERE o.user_id = ? AND o.created_at >= ?
        ");
        $stmt->execute([$userId, $range['from']]);
        $avgHours = $stmt->fetch(PDO::FETCH_COLUMN);
        
        // 5. Экономия vs диспетчер (15% от оборота)
        $savings = round((float)$current['total_spent'] * 0.15);
        
        return [
            'period_days' => $days,
            'orders' => [
                'total' => (int)$current['total_orders'],
                'completed' => (int)$current['completed_orders'],
                'cancelled' => (int)$current['cancelled_orders'],
                'active' => (int)$current['active_orders'],
                'trend' => $this->calcTrend($current['total_orders'], $prev['total_orders']),
            ],
            'spent' => [
                'total' => (float)$current['total_spent'],
                'trend' => $this->calcTrend($current['total_spent'], $prev['total_spent']),
            ],
            'savings' => $savings,
            'bids_count' => $bidsCount,
            'avg_response_hours' => $avgHours ? round((float)$avgHours, 1) : null,
        ];
    }
    
    /**
     * Динамика заказов по месяцам (для графика)
     */
    public function getOwnerMonthlyChart(int $userId, int $months = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as orders,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as spent
            FROM orders
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$userId, $months]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Заполняем пропущенные месяцы
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $found = null;
            foreach ($rows as $r) {
                if ($r['month'] === $month) { $found = $r; break; }
            }
            $result[] = [
                'month' => $month,
                'label' => $this->monthLabel($month),
                'orders' => $found ? (int)$found['orders'] : 0,
                'spent' => $found ? (float)$found['spent'] : 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Топ перевозчиков, с которыми работал owner
     */
    public function getOwnerTopCarriers(int $userId, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.company_name,
                u.rating,
                COUNT(*) as deals_count,
                SUM(o.price) as total_amount
            FROM orders o
            JOIN bids b ON b.order_id = o.id AND b.status = 'accepted'
            JOIN users u ON u.id = b.carrier_id
            WHERE o.user_id = ? AND o.status = 'completed'
            GROUP BY u.id, u.name, u.company_name, u.rating
            ORDER BY deals_count DESC, total_amount DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Топ маршрутов owner'а
     */
    public function getOwnerTopRoutes(int $userId, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt = $this->pdo->prepare("
            SELECT 
                pickup_address,
                delivery_address,
                COUNT(*) as trips,
                AVG(price) as avg_price,
                SUM(price) as total_price
            FROM orders
            WHERE user_id = ? AND status IN ('completed', 'unloaded', 'in_transit', 'arrived')
            GROUP BY pickup_address, delivery_address
            ORDER BY trips DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Извлекаем города и считаем расстояния
        foreach ($rows as &$row) {
            $fromCity = explode(',', $row['pickup_address'])[0] ?? '';
            $toCity = explode(',', $row['delivery_address'])[0] ?? '';
            $distance = $this->getDistance($fromCity, $toCity);
            
            $row['from_city'] = trim($fromCity);
            $row['to_city'] = trim($toCity);
            $row['distance'] = $distance;
            $row['price_per_km'] = ($distance && $row['avg_price']) 
                ? round((float)$row['avg_price'] / $distance, 1) 
                : null;
        }
        
        return $rows;
    }
    
    /**
     * ============================================================
     * АНАЛИТИКА ДЛЯ CARRIER
     * ============================================================
     */
    
    public function getCarrierStats(int $userId, int $days = 30): array
    {
        $range = $this->getDateRange($days);
        
        // 1. Сводка откликов
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_bids,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_bids,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bids
            FROM bids 
            WHERE carrier_id = ? AND created_at >= ?
        ");
        $stmt->execute([$userId, $range['from']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. То же за предыдущий период
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_bids,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_bids
            FROM bids 
            WHERE carrier_id = ? AND created_at >= ? AND created_at < ?
        ");
        $stmt->execute([$userId, $range['prev_from'], $range['prev_to']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Заработано (по принятым сделкам)
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(SUM(b.price), 0) as total_earned,
                COUNT(*) as deals_count
            FROM bids b
            JOIN orders o ON b.order_id = o.id
            WHERE b.carrier_id = ? AND b.status = 'accepted' 
              AND o.status = 'completed' AND o.created_at >= ?
        ");
        $stmt->execute([$userId, $range['from']]);
        $earnings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 4. Заработано за предыдущий период
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(b.price), 0) as total_earned
            FROM bids b
            JOIN orders o ON b.order_id = o.id
            WHERE b.carrier_id = ? AND b.status = 'accepted' 
              AND o.status = 'completed' 
              AND o.created_at >= ? AND o.created_at < ?
        ");
        $stmt->execute([$userId, $range['prev_from'], $range['prev_to']]);
        $prevEarned = (float)$stmt->fetch(PDO::FETCH_COLUMN);
        
        // 5. Win rate
        $winRate = $current['total_bids'] > 0 
            ? round($current['accepted_bids'] / $current['total_bids'] * 100, 1) 
            : 0;
        
        // 6. Средний чек
        $avgDeal = $earnings['deals_count'] > 0 
            ? round($earnings['total_earned'] / $earnings['deals_count']) 
            : 0;
        
        // 7. Средний win rate по платформе
        $stmt = $this->pdo->prepare("
            SELECT 
                ROUND(
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) * 100,
                    1
                ) as platform_win_rate
            FROM bids 
            WHERE created_at >= ?
        ");
        $stmt->execute([$range['from']]);
        $platformWinRate = (float)$stmt->fetch(PDO::FETCH_COLUMN);
        
        return [
            'period_days' => $days,
            'bids' => [
                'total' => (int)$current['total_bids'],
                'accepted' => (int)$current['accepted_bids'],
                'rejected' => (int)$current['rejected_bids'],
                'trend' => $this->calcTrend($current['total_bids'], $prev['total_bids']),
            ],
            'earnings' => [
                'total' => (float)$earnings['total_earned'],
                'deals_count' => (int)$earnings['deals_count'],
                'avg_deal' => $avgDeal,
                'trend' => $this->calcTrend($earnings['total_earned'], $prevEarned),
            ],
            'win_rate' => $winRate,
            'platform_win_rate' => $platformWinRate,
            'is_top' => $winRate > $platformWinRate,
        ];
    }
    
    /**
     * Динамика заработка по месяцам
     */
    public function getCarrierMonthlyChart(int $userId, int $months = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(o.created_at, '%Y-%m') as month,
                COUNT(DISTINCT b.id) as deals,
                COALESCE(SUM(b.price), 0) as earned
            FROM bids b
            JOIN orders o ON b.order_id = o.id
            WHERE b.carrier_id = ? AND b.status = 'accepted' 
              AND o.status = 'completed'
              AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$userId, $months]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Заполняем пропущенные месяцы
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $found = null;
            foreach ($rows as $r) {
                if ($r['month'] === $month) { $found = $r; break; }
            }
            $result[] = [
                'month' => $month,
                'label' => $this->monthLabel($month),
                'deals' => $found ? (int)$found['deals'] : 0,
                'earned' => $found ? (float)$found['earned'] : 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Топ маршрутов carrier'а
     */
    public function getCarrierTopRoutes(int $userId, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt = $this->pdo->prepare("
            SELECT 
                o.pickup_address,
                o.delivery_address,
                COUNT(*) as trips,
                AVG(b.price) as avg_price,
                SUM(b.price) as total_price
            FROM bids b
            JOIN orders o ON b.order_id = o.id
            WHERE b.carrier_id = ? AND b.status = 'accepted'
              AND o.status IN ('completed', 'unloaded')
            GROUP BY o.pickup_address, o.delivery_address
            ORDER BY trips DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as &$row) {
            $fromCity = explode(',', $row['pickup_address'])[0] ?? '';
            $toCity = explode(',', $row['delivery_address'])[0] ?? '';
            $distance = $this->getDistance($fromCity, $toCity);
            
            $row['from_city'] = trim($fromCity);
            $row['to_city'] = trim($toCity);
            $row['distance'] = $distance;
            $row['price_per_km'] = ($distance && $row['avg_price']) 
                ? round((float)$row['avg_price'] / $distance, 1) 
                : null;
        }
        
        return $rows;
    }
    
    /**
     * Топ заказчиков carrier'а
     */
    public function getCarrierTopClients(int $userId, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.company_name,
                u.rating,
                COUNT(*) as deals_count,
                SUM(b.price) as total_earned
            FROM bids b
            JOIN orders o ON b.order_id = o.id
            JOIN users u ON u.id = o.user_id
            WHERE b.carrier_id = ? AND b.status = 'accepted' 
              AND o.status = 'completed'
            GROUP BY u.id, u.name, u.company_name, u.rating
            ORDER BY deals_count DESC, total_earned DESC
            LIMIT $limit
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ============================================================
     * УТИЛИТЫ
     * ============================================================
     */
    
    private function calcTrend($current, $prev): float
    {
        $current = (float)$current;
        $prev = (float)$prev;
        
        if ($prev == 0) return $current > 0 ? 100.0 : 0.0;
        return round(($current - $prev) / $prev * 100, 1);
    }
    
    private function monthLabel(string $yearMonth): string
    {
        $months = [
            '01' => 'Янв', '02' => 'Фев', '03' => 'Мар', '04' => 'Апр',
            '05' => 'Май', '06' => 'Июн', '07' => 'Июл', '08' => 'Авг',
            '09' => 'Сен', '10' => 'Окт', '11' => 'Ноя', '12' => 'Дек',
        ];
        [$y, $m] = explode('-', $yearMonth);
        return ($months[$m] ?? $m) . ' ' . substr($y, 2);
    }
}