<?php
/**
 * order_pay.php — оплата заказа через Tinkoff Kassa
 * 
 * URL: /order_pay.php?order_id=X
 * 
 * АГЕНТСКАЯ СХЕМА:
 * Owner платит 100% суммы заказа (carrier_payout + commission) одним платежом.
 * Деньги идут на счёт платформы. После подтверждения выгрузки — платформа
 * переводит carrier'у.
 * 
 * Назначение платежа: "Агентское вознаграждение AVL Logistic + оплата перевозки по заказу #X"
 */

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
    header('Location: /login');
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if (!$orderId) {
    header('Location: /orders/my');
    exit;
}

// Загружаем заказ + данные carrier'а
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
           u.company_name as owner_company,
           u_carrier.name as carrier_name, u_carrier.company_name as carrier_company,
           u_carrier.inn as carrier_inn, u_carrier.phone as carrier_phone
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN bids b ON b.order_id = o.id AND b.status = 'accepted'
    LEFT JOIN users u_carrier ON u_carrier.id = b.carrier_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Заказ не найден';
    header('Location: /orders/my');
    exit;
}

// Проверки
if ($order['status'] === 'new') {
    $_SESSION['error'] = 'Нельзя оплатить заказ без выбранного перевозчика';
    header('Location: /orders/view/' . $orderId);
    exit;
}

if (!empty($order['commission_paid']) && $order['payment_method'] === 'card') {
    $_SESSION['error'] = 'Комиссия уже оплачена';
    header('Location: /orders/view/' . $orderId);
    exit;
}

if (empty($order['carrier_id'])) {
    $_SESSION['error'] = 'Перевозчик ещё не выбран';
    header('Location: /orders/view/' . $orderId);
    exit;
}

$carrierPayout    = (float)$order['carrier_payout'];
$commissionAmount = (float)$order['commission_amount'];

// === АГЕНТСКАЯ СХЕМА: owner платит 100% (carrier_payout + commission) ===
$totalAmount = $carrierPayout + $commissionAmount;

if ($totalAmount <= 0) {
    $_SESSION['error'] = 'Сумма к оплате должна быть больше нуля';
    header('Location: /orders/view/' . $orderId);
    exit;
}

// Проверяем, нет ли уже pending-счёта по этому заказу
$stmt = $pdo->prepare("
    SELECT * FROM invoices 
    WHERE user_id = ? AND plan = 'order' AND status = 'pending'
      AND payment_details LIKE ?
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$userId, '%заказу #' . $orderId . '%']);
$existingInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingInvoice && !empty($existingInvoice['payment_url'])) {
    // Уже есть счёт — редиректим на оплату
    header('Location: ' . $existingInvoice['payment_url']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Формируем детали платежа
    $details = "Агентское вознаграждение AVL Logistic + оплата перевозки по заказу #{$orderId} "
             . "на основании Публичной оферты. "
             . "Комиссия платформы: " . number_format($commissionAmount, 2, ',', ' ') . " ₽. "
             . "Оплата перевозки: " . number_format($carrierPayout, 2, ',', ' ') . " ₽.";

    // Создаём invoice
    $stmt = $pdo->prepare("
        INSERT INTO invoices (user_id, plan, amount, status, payment_method, payment_details)
        VALUES (?, 'order', ?, 'pending', 'card', ?)
    ");
    $stmt->execute([$userId, $totalAmount, $details]);
    $invoiceId = (int)$pdo->lastInsertId();

    // Инициализируем платёж в Tinkoff
    $client = new TinkoffClient();

    $description = "Агентское вознаграждение AVL Logistic + оплата перевозки по заказу #{$orderId} "
                 . "на основании Публичной оферты";

    $result = $client->init($invoiceId, $totalAmount, [
        'description' => $description,
        'email'       => $order['owner_email'],
        'phone'       => $order['owner_phone'] ?? null,
        'success_url' => 'https://avllogist.ru/payment/success?invoice=' . $invoiceId . '&order_id=' . $orderId,
        'fail_url'    => 'https://avllogist.ru/payment/fail?invoice=' . $invoiceId . '&order_id=' . $orderId,
    ]);

    if (empty($result['Success']) || empty($result['PaymentId'])) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Ошибка Tinkoff: ' . ($result['Message'] ?? $result['Details'] ?? 'unknown');
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    $paymentId  = $result['PaymentId'];
    $paymentUrl = $result['PaymentURL'];

    // Сохраняем payment_id и url
    $stmt = $pdo->prepare("UPDATE invoices SET payment_id = ?, payment_url = ? WHERE id = ?");
    $stmt->execute([$paymentId, $paymentUrl, $invoiceId]);

    // Привязываем invoice к заказу через commission_payment_id
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET commission_payment_id = ?,
            payment_method = 'card'
        WHERE id = ?
    ");
    $stmt->execute([$paymentId, $orderId]);

    // Запись в transactions
    $stmt = $pdo->prepare("
        INSERT INTO transactions
            (invoice_id, order_id, user_id, carrier_id, payment_id, order_id_tinkoff,
             amount, commission, payout, status, payment_type, raw_response)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', 'order', ?)
    ");
    $stmt->execute([
        $invoiceId,
        $orderId,
        $userId,
        (int)$order['carrier_id'],
        $paymentId,
        (string)$orderId,
        $totalAmount,
        $commissionAmount,
        $carrierPayout,
        json_encode($result, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[order_pay.php] ' . $e->getMessage());
    $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
    header('Location: /orders/view/' . $orderId);
    exit;
}

// Редирект на оплату в Tinkoff
header('Location: ' . $paymentUrl);
exit;