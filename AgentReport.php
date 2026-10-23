<?php
/**
 * AgentReport — формирование отчётов агента (ст. 1008 ГК РФ)
 * 
 * Платформа = агент Owner'а.
 * По каждой сделке формируется отчёт о том, как агент исполнил поручение.
 */

class AgentReport
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Создать отчёт агента по заказу
     * 
     * @param int $orderId ID заказа
     * @return array|false Данные отчёта или false
     */
    public function create(int $orderId): array|false
    {
        // Загружаем заказ + данные сторон
        $stmt = $this->pdo->prepare("
            SELECT 
                o.id, o.title, o.price, o.carrier_payout, o.commission_amount,
                o.commission_rate, o.created_at, o.user_id as owner_id,
                b.carrier_id,
                u_owner.name as owner_name, u_owner.company_name as owner_company,
                u_owner.inn as owner_inn, u_owner.email as owner_email,
                u_carrier.name as carrier_name, u_carrier.company_name as carrier_company,
                u_carrier.inn as carrier_inn, u_carrier.phone as carrier_phone,
                u_carrier.bank_details as carrier_bank
            FROM orders o
            JOIN bids b ON b.order_id = o.id AND b.status = 'accepted'
            JOIN users u_owner ON u_owner.id = o.user_id
            JOIN users u_carrier ON u_carrier.id = b.carrier_id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return false;
        }

        // Проверяем, не создан ли уже отчёт
        $stmt = $this->pdo->prepare("SELECT * FROM agent_reports WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return $existing;
        }

        // Генерируем номер отчёта: АО-2026-000123
        $number = 'АО-' . date('Y') . '-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);

        $total = (float)$order['price'];
        $fee = (float)$order['commission_amount'];
        $payout = (float)$order['carrier_payout'];

        // Формируем HTML-снапшот
        $html = $this->renderHtml($number, $order);

        // Сохраняем отчёт
        $stmt = $this->pdo->prepare("
            INSERT INTO agent_reports
                (order_id, report_number, principal_id, agent_id,
                 total_amount, agent_fee, carrier_payment,
                 status, sent_at, content)
            VALUES (?, ?, ?, 1, ?, ?, ?, 'sent', NOW(), ?)
        ");
        $stmt->execute([
            $orderId,
            $number,
            (int)$order['owner_id'],
            $total,
            $fee,
            $payout,
            $html,
        ]);

        $reportId = (int)$this->pdo->lastInsertId();

        // Обновляем заказ
        $stmt = $this->pdo->prepare("
            UPDATE orders
            SET agent_report_number = ?,
                agent_report_sent_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$number, $orderId]);

        // Уведомление owner'у
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, link)
            VALUES (?, 'payment', 'Отчёт агента готов', ?, ?)
        ");
        $stmt->execute([
            (int)$order['owner_id'],
            "По заказу #{$orderId} сформирован Отчёт агента №{$number}. Сумма к переводу перевозчику: " . number_format($payout, 0, ',', ' ') . " ₽.",
            "/agent_report/{$orderId}",
        ]);

        return [
            'id' => $reportId,
            'report_number' => $number,
            'total_amount' => $total,
            'agent_fee' => $fee,
            'carrier_payment' => $payout,
        ];
    }

    /**
     * HTML-снапшот отчёта (для печати/PDF)
     */
    private function renderHtml(string $number, array $order): string
    {
        $date = date('d.m.Y');
        $total = number_format((float)$order['price'], 2, ',', ' ');
        $fee = number_format((float)$order['commission_amount'], 2, ',', ' ');
        $payout = number_format((float)$order['carrier_payout'], 2, ',', ' ');
        $owner = htmlspecialchars($order['owner_company'] ?: $order['owner_name']);
        $ownerInn = htmlspecialchars($order['owner_inn'] ?? '—');
        $carrier = htmlspecialchars($order['carrier_company'] ?: $order['carrier_name']);
        $carrierInn = htmlspecialchars($order['carrier_inn'] ?? '—');
        $title = htmlspecialchars($order['title']);

        return <<<HTML
<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<title>Отчёт агента №{$number}</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px 20px; color: #222; }
    h1 { font-size: 20px; text-align: center; margin-bottom: 8px; }
    .subtitle { text-align: center; color: #666; font-size: 13px; margin-bottom: 30px; }
    h2 { font-size: 14px; margin-top: 24px; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid #ddd; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
    td { padding: 8px; border-bottom: 1px solid #eee; }
    td:first-child { width: 40%; color: #666; }
    td:last-child { font-weight: 600; }
    .total { background: #fef3c7; padding: 14px; margin: 16px 0; border-left: 4px solid #f59e0b; }
    .total strong { font-size: 18px; }
    .signatures { margin-top: 60px; }
    .signatures td { border: none; padding: 30px 10px; vertical-align: bottom; }
</style>
</head><body>

<h1>ОТЧЁТ АГЕНТА № {$number}</h1>
<div class="subtitle">к агентскому договору от {$date} · в соответствии со ст. 1008 ГК РФ</div>

<h2>1. Стороны</h2>
<table>
    <tr><td>Принципал (Грузовладелец):</td><td>{$owner}, ИНН {$ownerInn}</td></tr>
    <tr><td>Агент (Платформа):</td><td>AVL Logistic (avllogist.ru)</td></tr>
    <tr><td>Перевозчик:</td><td>{$carrier}, ИНН {$carrierInn}</td></tr>
</table>

<h2>2. Поручение и его исполнение</h2>
<table>
    <tr><td>Заказ №:</td><td>{$order['id']}</td></tr>
    <tr><td>Описание:</td><td>{$title}</td></tr>
    <tr><td>Дата поручения:</td><td>{$order['created_at']}</td></tr>
    <tr><td>Перевозчик найден:</td><td>Да</td></tr>
</table>

<h2>3. Финансовые расчёты</h2>
<table>
    <tr><td>Сумма, полученная от Принципала:</td><td>{$total} ₽</td></tr>
    <tr><td>Агентское вознаграждение:</td><td>{$fee} ₽</td></tr>
    <tr><td>К перечислению Перевозчику:</td><td>{$payout} ₽</td></tr>
</table>

<div class="total">
    <strong>Итого к выплате Перевозчику: {$payout} ₽</strong><br>
    <small>Агент перечислит указанную сумму на реквизиты Перевозчика после подтверждения выгрузки.</small>
</div>

<h2>4. Реквизиты Перевозчика</h2>
<table>
    <tr><td>Банковские реквизиты:</td><td><pre style="margin:0; font-family: monospace; font-size: 12px; white-space: pre-wrap;">{$order['carrier_bank']}</pre></td></tr>
</table>

<p style="margin-top: 30px; font-size: 12px; color: #666;">
    Отчёт считается принятым Принципалом, если в течение 3 рабочих дней 
    с момента направления не поступило мотивированных возражений 
    (в соответствии с п. X Публичной оферты).
</p>

<table class="signatures">
    <tr>
        <td>Агент: _______________<br>AVL Logistic<br>М.П.</td>
        <td>Принципал: _______________<br>{$owner}<br>М.П.</td>
    </tr>
</table>

</body></html>
HTML;
    }

    /**
     * Получить отчёт по заказу
     */
    public function getByOrder(int $orderId): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM agent_reports WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /**
     * Автоматически принять отчёт (если owner не возразил за 3 дня)
     */
    public function autoAcceptOld(): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE agent_reports
            SET status = 'accepted', accepted_at = NOW()
            WHERE status = 'sent'
              AND sent_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
        ");
        $stmt->execute();
        $count = $stmt->rowCount();

        if ($count > 0) {
            // Обновляем orders
            $this->pdo->query("
                UPDATE orders o
                JOIN agent_reports ar ON ar.order_id = o.id
                SET o.agent_report_accepted = 1,
                    o.agent_report_accepted_at = ar.accepted_at
                WHERE ar.status = 'accepted' AND o.agent_report_accepted = 0
            ");
        }

        return $count;
    }
}