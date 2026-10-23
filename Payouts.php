<?php
/**
 * Payouts — управление реестром выплат перевозчикам
 * 
 * После того как owner подтвердил выгрузку и отчёт агента принят,
 * заказ попадает в реестр выплат. Админ переводит деньги и отмечает в системе.
 */

class Payouts
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Создать запись о выплате при завершении заказа
     */
    public function createForOrder(int $orderId): int|false
    {
        // Проверяем, нет ли уже выплаты
        $stmt = $this->pdo->prepare("SELECT id FROM payouts WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        if ($stmt->fetch()) {
            return false;
        }

        // Загружаем заказ
        $stmt = $this->pdo->prepare("
            SELECT o.id, o.carrier_id, o.carrier_payout, o.commission_amount
            FROM orders o
            WHERE o.id = ? AND o.carrier_id IS NOT NULL
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO payouts (order_id, carrier_id, amount, commission, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $orderId,
            (int)$order['carrier_id'],
            (float)$order['carrier_payout'],
            (float)$order['commission_amount'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Пометить выплату как исполненную
     */
    public function markPaid(int $payoutId, int $adminId, string $document = ''): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE payouts
            SET status = 'paid',
                paid_at = NOW(),
                paid_by = ?,
                payment_document = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$adminId, $document, $payoutId]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        // Обновляем заказ
        $stmt = $this->pdo->prepare("
            UPDATE orders o
            JOIN payouts p ON p.order_id = o.id
            SET o.payout_to_carrier_at = NOW(),
                o.payout_method = 'manual',
                o.payout_document = ?
            WHERE p.id = ?
        ");
        $stmt->execute([$document, $payoutId]);

        // Уведомление carrier'у
        $stmt = $this->pdo->prepare("
            SELECT p.order_id, p.amount, p.carrier_id
            FROM payouts p WHERE p.id = ?
        ");
        $stmt->execute([$payoutId]);
        $payout = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payout) {
            $amount = number_format((float)$payout['amount'], 0, ',', ' ');
            $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, link)
                VALUES (?, 'payment', 'Деньги переведены', ?, ?)
            ")->execute([
                (int)$payout['carrier_id'],
                "Платформа перевела вам {$amount} ₽ по заказу #{$payout['order_id']}. Проверьте счёт.",
                "/orders/view/{$payout['order_id']}",
            ]);
        }

        return true;
    }

    /**
     * Список выплат (для админки)
     */
    public function list(string $status = 'pending', int $limit = 100): array
    {
        $status = in_array($status, ['pending', 'paid', 'failed', 'all']) ? $status : 'pending';
        $limit = (int)$limit;

        $where = '';
        if ($status !== 'all') {
            $where = "WHERE p.status = " . $this->pdo->quote($status);
        }

        $sql = "
            SELECT p.*, 
                   o.title as order_title,
                   o.created_at as order_created,
                   u.name as carrier_name, 
                   u.company_name as carrier_company,
                   u.inn as carrier_inn,
                   u.bank_details as carrier_bank
            FROM payouts p
            JOIN orders o ON o.id = p.order_id
            JOIN users u ON u.id = p.carrier_id
            $where
            ORDER BY p.created_at DESC
            LIMIT $limit
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Сумма ожидающих выплат (для дашборда)
     */
    public function pendingTotal(): float
    {
        return (float)$this->pdo->query("
            SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE status = 'pending'
        ")->fetchColumn();
    }
}