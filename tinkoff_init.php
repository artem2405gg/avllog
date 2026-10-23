<?php
/**
 * tinkoff_init.php — создание платежа для подписки (в корне public_html)
 * URL: /tinkoff_init.php
 * 
 * POST (JSON): { plan: 'basic' | 'pro' | 'basic_yearly' | 'pro_yearly' }
 * Возвращает: { success: true, data: { payment_url, ... } }
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$userId = (int)$_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$plan = $input['plan'] ?? '';

$config = require $root . '/app/config/payments.php';

// Разрешённые планы (месячные + годовые)
$allowedPlans = ['basic', 'pro', 'basic_yearly', 'pro_yearly'];

if (!in_array($plan, $allowedPlans)) {
    Response::error('Неверный тариф', 400);
}

$subConfig = $config['subscriptions'][$plan];
$amount    = $subConfig['amount'];

// Пользователь
$stmt = $pdo->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    Response::error('Пользователь не найден', 404);
}

if ($user['role'] !== 'carrier') {
    Response::error('Подписка доступна только перевозчикам', 403);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO invoices (user_id, plan, amount, status, payment_method, payment_details)
        VALUES (?, ?, ?, 'pending', 'card', ?)
    ");
    $stmt->execute([
        $userId,
        $plan,
        $amount,
        "Подписка {$subConfig['name']} на {$subConfig['duration']} дней",
    ]);
    $invoiceId = (int)$pdo->lastInsertId();

    $client = new TinkoffClient();

    $description = "Подписка {$subConfig['name']} — AVL Logistic (агентское вознаграждение по оферте)";

    $result = $client->init($invoiceId, $amount, [
        'description' => $description,
        'email'       => $user['email'],
        'phone'       => $user['phone'] ?? null,
        'success_url' => 'https://avllogist.ru/payment/success?invoice=' . $invoiceId,
        'fail_url'    => 'https://avllogist.ru/payment/fail?invoice=' . $invoiceId,
    ]);

    if (empty($result['Success']) || empty($result['PaymentId'])) {
        $pdo->rollBack();
        Response::error(
            'Tinkoff отклонил: ' . ($result['Message'] ?? $result['Details'] ?? 'unknown'),
            502,
            ['raw' => $result]
        );
    }

    $paymentId  = $result['PaymentId'];
    $paymentUrl = $result['PaymentURL'];

    $stmt = $pdo->prepare("UPDATE invoices SET payment_id = ?, payment_url = ? WHERE id = ?");
    $stmt->execute([$paymentId, $paymentUrl, $invoiceId]);

    $stmt = $pdo->prepare("
        INSERT INTO transactions
            (invoice_id, user_id, payment_id, order_id_tinkoff, amount,
             commission, payout, status, payment_type, raw_response)
        VALUES (?, ?, ?, ?, ?, 0, ?, 'new', 'subscription', ?)
    ");
    $stmt->execute([
        $invoiceId,
        $userId,
        $paymentId,
        (string)$invoiceId,
        $amount,
        $amount,
        json_encode($result, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Response::error('Ошибка: ' . $e->getMessage(), 500);
}

Response::success([
    'invoice_id'  => $invoiceId,
    'payment_id'  => $paymentId,
    'payment_url' => $paymentUrl,
    'amount'      => $amount,
    'plan'        => $plan,
    'duration'    => $subConfig['duration'],
]);