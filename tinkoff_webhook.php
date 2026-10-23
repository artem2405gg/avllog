<?php
/**
 * tinkoff_webhook.php — приём уведомлений от Tinkoff
 * 
 * Всегда возвращаем 200 OK (даже при ошибке), 
 * иначе банк будет ретраить запрос.
 * 
 * Настройка в ЛК Tinkoff: URL = https://avllogist.ru/api/tinkoff_webhook.php
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/lib/TinkoffClient.php';

header('Content-Type: application/json; charset=utf-8');

// Читаем webhook
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Логируем всё входящее
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
file_put_contents(
    $logDir . '/webhook_' . date('Y-m-d') . '.log',
    sprintf("[%s] IN: %s\n", date('Y-m-d H:i:s'), $raw),
    FILE_APPEND
);

if (!is_array($data)) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

// Проверка подписи
try {
    $client = new TinkoffClient();
    if (!$client->verifyWebhook($data)) {
        http_response_code(200);  // всё равно 200, но логируем
        file_put_contents(
            $logDir . '/webhook_' . date('Y-m-d') . '.log',
            "[BAD_SIGNATURE] {$raw}\n",
            FILE_APPEND
        );
        echo json_encode(['ok' => false, 'error' => 'bad_signature']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'verify_error']);
    exit;
}

$paymentId = $data['PaymentId'] ?? '';
$status    = $data['Status']    ?? '';
$orderId   = $data['OrderId']   ?? '';

if (!$paymentId || !$status) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'missing_fields']);
    exit;
}

// Находим транзакцию в БД
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE payment_id = ?");
$stmt->execute([$paymentId]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    file_put_contents(
        $logDir . '/webhook_' . date('Y-m-d') . '.log',
        "[TX_NOT_FOUND] payment_id={$paymentId}\n",
        FILE_APPEND
    );
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'tx_not_found']);
    exit;
}

// Обновляем статус транзакции
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE transactions
        SET status = ?, raw_response = JSON_MERGE_PATCH(COALESCE(raw_response, '{}'), ?)
        WHERE payment_id = ?
    ");
    $stmt->execute([
        $status,
        json_encode(['webhook' => $data], JSON_UNESCAPED_UNICODE),
        $paymentId,
    ]);

    // Если платёж подтверждён — активируем подписку
    if ($status === 'CONFIRMED') {
        // Помечаем invoice как оплаченный
        if (!empty($tx['invoice_id'])) {
            $stmt = $pdo->prepare("
                UPDATE invoices
                SET status = 'paid', paid_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$tx['invoice_id']]);

            // Обновляем или создаём подписку
            if ($tx['payment_type'] === 'subscription') {
                // Получаем plan из invoice
                $stmt = $pdo->prepare("SELECT plan, amount FROM invoices WHERE id = ?");
                $stmt->execute([$tx['invoice_id']]);
                $inv = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($inv && $inv['plan']) {
                    $plan     = $inv['plan'];
                    $duration = $config['subscriptions'][$plan]['duration'] ?? 30;

                    // Деактивируем старые подписки
                    $stmt = $pdo->prepare("
                        UPDATE subscriptions
                        SET status = 'expired'
                        WHERE user_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$tx['user_id']]);

                    // Создаём новую
                    $stmt = $pdo->prepare("
                        INSERT INTO subscriptions (user_id, plan, status, expires_at)
                        VALUES (?, ?, 'active', DATE_ADD(NOW(), INTERVAL ? DAY))
                    ");
                    $stmt->execute([$tx['user_id'], $plan, $duration]);

                    // Уведомление пользователю
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link)
                        VALUES (?, 'subscription', 'Подписка активирована', ?, '/pricing')
                    ");
                    $stmt->execute([
                        $tx['user_id'],
                        "Тариф «{$config['subscriptions'][$plan]['name']}» активирован на {$duration} дней.",
                    ]);
                }
            }
        }
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    file_put_contents(
        $logDir . '/webhook_' . date('Y-m-d') . '.log',
        "[ERROR] " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'db_error']);
    exit;
}

// Всегда отвечаем 200 OK
http_response_code(200);
echo json_encode(['ok' => true]);