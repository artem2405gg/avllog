<?php
/**
 * tinkoff_status.php — проверка статуса платежа + активация
 * URL: /tinkoff_status.php?invoice_id=X
 * 
 * Для plan='order'/'commission': фиксируем оплату (агентская схема).
 * Для plan IN ('basic','pro','basic_yearly','pro_yearly'): активируем подписку
 * на нужное количество дней (30 для месяца, 365 для года).
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$root = dirname(__DIR__);

require_once $root . '/app/config.php';
require_once $root . '/app/database.php';
require_once $root . '/app/lib/Response.php';
require_once $root . '/app/lib/TinkoffClient.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    Response::error('Не авторизован', 401);
}

$userId    = (int)$_SESSION['user_id'];
$invoiceId = (int)($_GET['invoice_id'] ?? 0);

if (!$invoiceId) {
    Response::error('Не передан invoice_id', 400);
}

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoiceId, $userId]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    Response::error('Счёт не найден', 404);
}

if ($invoice['status'] === 'paid') {
    returnState($pdo, $userId, $invoice, 'ALREADY_PAID');
}

if (empty($invoice['payment_id'])) {
    Response::error('У счёта нет payment_id', 400);
}

try {
    $client = new TinkoffClient();
    $state = $client->getState($invoice['payment_id']);
} catch (Throwable $e) {
    Response::error('Ошибка Tinkoff: ' . $e->getMessage(), 502);
}

$tinkoffStatus = $state['Status'] ?? 'UNKNOWN';

if ($tinkoffStatus === 'CONFIRMED' && $invoice['status'] !== 'paid') {
    $config = require $root . '/app/config/payments.php';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE invoices
            SET status = 'paid', paid_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$invoiceId]);

        $stmt = $pdo->prepare("
            UPDATE transactions
            SET status = 'confirmed', updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$invoice['payment_id']]);

        $plan = $invoice['plan'];

        if ($plan === 'commission' || $plan === 'order') {
            // === ОПЛАТА ЗАКАЗА (агентская схема) ===
            $stmt = $pdo->prepare("
                SELECT id, user_id, carrier_id, commission_amount, carrier_payout, price
                FROM orders
                WHERE commission_payment_id = ?
                LIMIT 1
            ");
            $stmt->execute([$invoice['payment_id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET commission_paid = 1,
                        commission_paid_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$order['id']]);

                $totalPaid = (float)$order['carrier_payout'] + (float)$order['commission_amount'];
                $formatted = number_format($totalPaid, 0, ',', ' ');

                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link)
                    VALUES (?, 'payment', 'Оплата получена', ?, ?)
                ");
                $stmt->execute([
                    (int)$order['user_id'],
                    "Оплата {$formatted} ₽ по заказу #{$order['id']} получена. Деньги заморожены на счёте платформы и будут переведены перевозчику после подтверждения выгрузки.",
                    "/orders/view/{$order['id']}",
                ]);

                if (!empty($order['carrier_id'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link)
                        VALUES (?, 'payment', 'Заказ оплачен', ?, ?)
                    ");
                    $stmt->execute([
                        (int)$order['carrier_id'],
                        "По заказу #{$order['id']} поступила оплата от заказчика. Деньги заморожены, будут переведены вам после подтверждения выгрузки.",
                        "/orders/view/{$order['id']}",
                    ]);
                }
            }

        } else {
            // === ПОДПИСКА (месячная или годовая) ===
            if (isset($config['subscriptions'][$plan])) {
                $duration = (int)($config['subscriptions'][$plan]['duration'] ?? 30);
                $planName = $config['subscriptions'][$plan]['name'] ?? ucfirst($plan);

                // Какой это тариф — базовый или pro
                $basePlan = (strpos($plan, 'pro') !== false) ? 'pro' : 'basic';

                // Деактивируем старые
                $stmt = $pdo->prepare("
                    UPDATE subscriptions
                    SET status = 'expired'
                    WHERE user_id = ? AND status = 'active'
                ");
                $stmt->execute([$userId]);

                // Создаём новую с нужной длительностью
                $stmt = $pdo->prepare("
                    INSERT INTO subscriptions (user_id, plan, status, expires_at)
                    VALUES (?, ?, 'active', DATE_ADD(NOW(), INTERVAL ? DAY))
                ");
                $stmt->execute([$userId, $basePlan, $duration]);

                // Уведомление
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link)
                    VALUES (?, 'subscription', 'Подписка активирована', ?, '/pricing')
                ");
                $stmt->execute([
                    $userId,
                    "Тариф «{$planName}» активирован на {$duration} дней.",
                ]);
            }
        }

        $pdo->commit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();

        $logDir = $root . '/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        file_put_contents(
            $logDir . '/tinkoff_status_errors.log',
            sprintf("[%s] invoice=%d error=%s\n", date('Y-m-d H:i:s'), $invoiceId, $e->getMessage()),
            FILE_APPEND
        );

        Response::error('Ошибка активации: ' . $e->getMessage(), 500);
    }
}

returnState($pdo, $userId, $invoice, $tinkoffStatus);


function returnState(PDO $pdo, int $userId, array $invoice, string $tinkoffStatus): void
{
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice['id']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT plan, expires_at
        FROM subscriptions
        WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
        ORDER BY expires_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    $order = null;
    if (!empty($invoice['payment_id'])) {
        $stmt = $pdo->prepare("
            SELECT id, user_id, carrier_id, commission_paid, commission_paid_at,
                   owner_paid_carrier, carrier_confirmed_payment, payment_status,
                   payout_to_carrier_at, agent_report_number
            FROM orders
            WHERE commission_payment_id = ?
            LIMIT 1
        ");
        $stmt->execute([$invoice['payment_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    Response::success([
        'invoice'        => $invoice,
        'subscription'   => $subscription ?: null,
        'order'          => $order,
        'tinkoff_status' => $tinkoffStatus,
    ]);
}