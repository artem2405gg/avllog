<?php
/**
 * ReturnSearch — подбор обратных рейсов для перевозчиков
 * 
 * Логика:
 * 1. Перевозчик создаёт поиск: «Я в Казани, готов с 23.09 по 25.09, 20т тент»
 * 2. Система ищет заказы, где pickup_address содержит Казань
 * 3. pickup_date попадает в диапазон
 * 4. Вес/объём/тип ТС подходит
 * 5. Сортирует по цене за км (выгоднее = выше)
 * 6. Возвращает топ-50
 */

class ReturnSearch
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Создать поиск обратного рейса
     */
    public function create(int $carrierId, array $data): int|false
    {
        // Проверяем, нет ли активного поиска
        $stmt = $this->pdo->prepare("
            SELECT id FROM return_searches 
            WHERE carrier_id = ? AND status = 'active' AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$carrierId]);
        if ($stmt->fetch()) {
            return false;
        }
        
        $city = trim($data['city'] ?? '');
        $cityLower = mb_strtolower($city);
        
        $dateFrom = $data['date_from'] ?? date('Y-m-d');
        $dateTo = $data['date_to'] ?? date('Y-m-d', strtotime('+3 days'));
        
        $expiresAt = date('Y-m-d H:i:s', strtotime($dateTo . ' +1 day'));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO return_searches 
                (carrier_id, city, city_lower, radius_km, date_from, date_to,
                 vehicle_type, capacity_tons, capacity_volume,
                 preferred_destination, contact_phone, comment, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $carrierId,
            $city,
            $cityLower,
            (int)($data['radius_km'] ?? 300),
            $dateFrom,
            $dateTo,
            $data['vehicle_type'] ?? null,
            !empty($data['capacity_tons']) ? (float)$data['capacity_tons'] : null,
            !empty($data['capacity_volume']) ? (float)$data['capacity_volume'] : null,
            $data['preferred_destination'] ?? null,
            $data['contact_phone'] ?? null,
            $data['comment'] ?? null,
            $expiresAt,
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }
    
    /**
     * Найти обратные заказы для поиска
     */
    public function findOrders(int $searchId): array
    {
        // Загружаем поиск
        $stmt = $this->pdo->prepare("SELECT * FROM return_searches WHERE id = ? AND status = 'active'");
        $stmt->execute([$searchId]);
        $search = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$search) return [];
        
        // Увеличиваем счётчик просмотров
        $this->pdo->prepare("UPDATE return_searches SET views_count = views_count + 1 WHERE id = ?")
            ->execute([$searchId]);
        
        // Базовые условия поиска
        $sql = "
            SELECT 
                o.*,
                u.name AS owner_name,
                u.company_name AS owner_company,
                u.rating AS owner_rating,
                (SELECT COUNT(*) FROM bids WHERE order_id = o.id) AS bids_count
            FROM orders o
            JOIN users u ON u.id = o.user_id
            WHERE o.status = 'new'
              AND o.accepts_return = 1
              AND o.pickup_address LIKE ?
              AND o.pickup_date >= ?
              AND o.pickup_date <= ?
        ";
        
        $params = [
            '%' . $search['city'] . '%',
            $search['date_from'],
            $search['date_to'],
        ];
        
        // Фильтр по грузоподъёмности
        if (!empty($search['capacity_tons'])) {
            $sql .= " AND (o.weight IS NULL OR o.weight <= ?)";
            $params[] = $search['capacity_tons'];
        }
        
        // Фильтр по объёму
        if (!empty($search['capacity_volume'])) {
            $sql .= " AND (o.volume IS NULL OR o.volume <= ?)";
            $params[] = $search['capacity_volume'];
        }
        
        // Фильтр по типу ТС (если у перевозчика указан и у заказа указан)
        if (!empty($search['vehicle_type'])) {
            $sql .= " AND (o.vehicle_type IS NULL OR o.vehicle_type = '' OR o.vehicle_type = ?)";
            $params[] = $search['vehicle_type'];
        }
        
        $sql .= " ORDER BY o.pickup_date ASC, o.price DESC LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Считаем расстояние и цену за км
        require_once __DIR__ . '/Analytics.php';
        $analytics = new Analytics($this->pdo);
        
        foreach ($orders as &$order) {
            $fromCity = explode(',', $order['pickup_address'])[0] ?? '';
            $toCity = explode(',', $order['delivery_address'])[0] ?? '';
            
            $distance = $analytics->getDistance(trim($fromCity), trim($toCity));
            $order['distance_km'] = $distance;
            $order['price_per_km'] = ($distance && $order['price']) 
                ? round((float)$order['price'] / $distance, 2) 
                : null;
        }
        unset($order);
        
        // Сортируем по цене за км (выгоднее — выше)
        usort($orders, function($a, $b) {
            $aPk = $a['price_per_km'] ?? 0;
            $bPk = $b['price_per_km'] ?? 0;
            if ($bPk != $aPk) return $bPk <=> $aPk;
            return strtotime($a['pickup_date']) - strtotime($b['pickup_date']);
        });
        
        return $orders;
    }
    
    /**
     * Получить активный поиск перевозчика
     */
    public function getActive(int $carrierId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM return_searches 
            WHERE carrier_id = ? AND status = 'active' AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$carrierId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }
    
    /**
     * Отменить поиск
     */
    public function cancel(int $searchId, int $carrierId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE return_searches 
            SET status = 'cancelled'
            WHERE id = ? AND carrier_id = ? AND status = 'active'
        ");
        $stmt->execute([$searchId, $carrierId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Пометить как matched
     */
    public function markMatched(int $searchId, int $orderId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE return_searches 
            SET status = 'matched', matched_order_id = ?
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$orderId, $searchId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Cron: протухшие поиски
     */
    public function expireOld(): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE return_searches 
            SET status = 'expired'
            WHERE status = 'active' AND expires_at < NOW()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * Статистика
     */
    public function stats(): array
    {
        return [
            'active' => (int)$this->pdo->query("SELECT COUNT(*) FROM return_searches WHERE status = 'active'")->fetchColumn(),
            'matched' => (int)$this->pdo->query("SELECT COUNT(*) FROM return_searches WHERE status = 'matched'")->fetchColumn(),
            'expired' => (int)$this->pdo->query("SELECT COUNT(*) FROM return_searches WHERE status = 'expired'")->fetchColumn(),
            'total_views' => (int)$this->pdo->query("SELECT COALESCE(SUM(views_count), 0) FROM return_searches")->fetchColumn(),
        ];
    }
}