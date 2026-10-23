<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

// Считаем активные заказы
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'new'");
$ordersCount = $stmt->fetch()['cnt'];

if ($ordersCount == 0) {
    echo "Нет активных заказов\n";
    exit;
}

// Правильное склонение
function getWordForm($count) {
    $mod10 = $count % 10;
    $mod100 = $count % 100;
    if ($mod10 == 1 && $mod100 != 11) return 'заказ';
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) return 'заказа';
    return 'заказов';
}

$word = getWordForm($ordersCount);

// Получаем перевозчиков, которым ещё не отправляли в этот час
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email 
    FROM users u 
    WHERE u.role = 'carrier' 
    AND u.email_verified = 1
    AND NOT EXISTS (
        SELECT 1 FROM notifications n 
        WHERE n.user_id = u.id 
        AND n.type = 'hourly_orders' 
        AND n.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    )
");
$carriers = $stmt->fetchAll();

$sent = 0;
foreach ($carriers as $carrier) {
    // Уведомление на сайте
    $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'hourly_orders', 'Заказы ждут вас!', ?, '/orders')")
        ->execute([$carrier['id'], "На платформе {$ordersCount} {$word} ждут перевозчика. Откликнитесь!"]);
    
    // Email (отключай если не нужно)
    if (!empty($carrier['email'])) {
        $subject = "Заказы ждут вас — AVL Logistic";
        $message = "Здравствуйте, {$carrier['name']}!\n\n"
            . "На платформе {$ordersCount} {$word} ждут перевозчика.\n"
            . "Откликнитесь: " . SITE_URL . "/orders\n\n"
            . "С уважением,\nAVL Logistic";
        
        $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        mail($carrier['email'], $subject, $message, $headers, "-f " . MAIL_FROM);
    }
    
    $sent++;
}

echo "Отправлено уведомлений: $sent (заказов: $ordersCount $word)";