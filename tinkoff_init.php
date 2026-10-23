<?php
/**
 * tinkoff_init.php — создание платежа для подписки
 * 
 * POST (JSON):
 *   { plan: 'basic' | 'pro' }
 * 
 * Возвращает:
 *   { success: true, data: { payment_url, payment_id, invoice_id } }
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/lib/Response.php';
require_once __DIR__ . '/../app/lib/TinkoffClient.php';

// Проверка авторизации
$userId = Response::requireAuth();

// Читаем запрос
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$plan = $input['plan'] ?? '';

// Валидация тарифа
$config = require __DIR__ . '/../app/config/payments.php';
if (!in_array($plan, ['basic', 'pro'])) {
    Response::error('Неверный тариф', 400);
}

$subConfig = $config['subscriptions'][$plan];
$amount    = $subConfig['amount'];

// Загружаем пользователя
$stmt = $pdo->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    Response::error('Пользователь не найден', 404);
}

if ($user['role'] !== 'carrier') {
    Response::error('Подписка доступна только перевозчикам', 403);
}

// 1. Создаём invoice (запись в БД)
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
        "Подписка {$subConfig['name']} на {$subConfig['duration']} дней. Оплата через Tinkoff Kassa.",
    ]);
    $invoiceId = (int)$pdo->lastInsertId();

    // 2. Инициализируем платёж в Tinkoff
    $client = new TinkoffClient();
    $result = $client->init($invoiceId, $amount, [
        'description'  => "Подписка {$subConfig['name']} — AVL Logistic",
        'email'        => $user['email'],
        'phone'        => $user['phone'] ?? null,
        'customer_key' => 'user_' . $userId,
        'success_url'  => 'https://avllogist.ru/payment/success?invoice=' . $invoiceId,
        'fail_url'     => 'https://avllogist.ru/payment/fail?invoice=' . $invoiceId,
    ]);

    if (empty($result['Success']) || empty($result['PaymentId'])) {
        $pdo->rollBack();
        Response::error(
            'Tinkoff отклонил запрос: ' . ($result['Message'] ?? $result['Details'] ?? 'unknown'),
            502,
            ['raw' => $result]
        );
    }

    $paymentId  = $result['PaymentId'];
    $paymentUrl = $result['PaymentURL'];

    // 3. Обновляем invoice
    $stmt = $pdo->prepare("
        UPDATE invoices
        SET payment_id = ?, payment_url = ?
        WHERE id = ?
    ");
    $stmt->execute([$paymentId, $paymentUrl, $invoiceId]);

    // 4. Пишем в transactions
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
        $amount,   // для подписки payout = вся сумма платформе
        json_encode($result, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error('Ошибка создания платежа: ' . $e->getMessage(), 500);
}

Response::success([
    'invoice_id'  => $invoiceId,
    'payment_id'  => $paymentId,
    'payment_url' => $paymentUrl,
    'amount'      => $amount,
    'plan'        => $plan,
]);