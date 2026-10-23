<?php
/**
 * Commission — расчёт комиссии платформы
 * 
 * Логика (агентская схема):
 *   Free  → 3% с суммы заказа
 *   Basic → 0% (подписка)
 *   Pro   → 0% (подписка)
 *   Нет подписки / просрочена → Free (3%)
 * 
 * Также учитывается users.commission_rate_override (VIP-клиенты) —
 * если задан, используется вместо планового.
 */

class Commission
{
    /** Ставка для Free-тарифа */
    const RATE_FREE = 0.03;

    /** Ставка для платных тарифов */
    const RATE_PAID = 0.00;

    /**
     * Получить ставку комиссии для перевозчика
     * 
     * @param int $carrierId
     * @return float 0.03 или 0.00
     */
    public static function getRateForCarrier(int $carrierId): float
    {
        global $pdo;

        if (!$pdo) {
            // fallback: если $pdo недоступен — считаем Free
            return self::RATE_FREE;
        }

        // 1. Проверяем индивидуальную ставку (VIP-override)
        try {
            $stmt = $pdo->prepare("SELECT commission_rate_override FROM users WHERE id = ?");
            $stmt->execute([$carrierId]);
            $override = $stmt->fetchColumn();

            if ($override !== null && $override !== false && $override !== '') {
                return (float)$override;
            }
        } catch (Throwable $e) {
            error_log('[Commission::getRateForCarrier] override check: ' . $e->getMessage());
        }

        // 2. Проверяем активную подписку
        try {
            $stmt = $pdo->prepare("
                SELECT plan 
                FROM subscriptions 
                WHERE user_id = ? 
                  AND status = 'active' 
                  AND expires_at > NOW()
                ORDER BY expires_at DESC
                LIMIT 1
            ");
            $stmt->execute([$carrierId]);
            $plan = $stmt->fetchColumn();

            // Basic или Pro → 0%
            if ($plan === 'basic' || $plan === 'pro') {
                return self::RATE_PAID;
            }

        } catch (Throwable $e) {
            error_log('[Commission::getRateForCarrier] subscription check: ' . $e->getMessage());
        }

        // 3. По умолчанию — Free (3%)
        return self::RATE_FREE;
    }

    /**
     * Полный расчёт комиссии по заказу
     * 
     * @param float $price        Сумма заказа (что предложил carrier)
     * @param int   $carrierId    ID перевозчика
     * @return array [
     *     'commission_rate'   => 0.03,
     *     'commission_amount' => 300.00,
     *     'carrier_payout'    => 9700.00,
     *     'price'             => 10000.00,
     * ]
     */
    public static function calculate(float $price, int $carrierId): array
    {
        $rate = self::getRateForCarrier($carrierId);

        // Сумма комиссии = price × rate
        $commissionAmount = round($price * $rate, 2);

        // Что получит carrier = price − комиссия
        $carrierPayout = round($price - $commissionAmount, 2);

        return [
            'commission_rate'   => $rate,
            'commission_amount' => $commissionAmount,
            'carrier_payout'    => $carrierPayout,
            'price'             => $price,
        ];
    }

    /**
     * Форматированная ставка для отображения: «3%» / «0%»
     */
    public static function formatRate(float $rate): string
    {
        if ($rate <= 0) return '0%';
        return number_format($rate * 100, 1, '.', '') . '%';
    }

    /**
     * Текстовое описание тарифа по ID carrier'а (для UI)
     */
    public static function getPlanNameForCarrier(int $carrierId): string
    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("
                SELECT plan 
                FROM subscriptions 
                WHERE user_id = ? 
                  AND status = 'active' 
                  AND expires_at > NOW()
                ORDER BY expires_at DESC
                LIMIT 1
            ");
            $stmt->execute([$carrierId]);
            $plan = $stmt->fetchColumn();

            if ($plan === 'basic') return 'Базовый';
            if ($plan === 'pro')   return 'Pro';
        } catch (Throwable $e) {
            error_log('[Commission::getPlanNameForCarrier] ' . $e->getMessage());
        }

        return 'Старт (Free)';
    }
}