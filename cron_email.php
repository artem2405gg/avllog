<?php
/**
 * cron_email.php — отправка email из очереди
 * Запускать раз в минуту через cron
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

// Проверяем, что запуск из CLI (не из браузера)
if (php_sapi_name() !== 'cli' && empty($_GET['secret_key'])) {
    die('Forbidden');
}

// Защита через секретный ключ (для запуска через URL)
$secret = 'cron_avl_' . md5('email_secret_2026');
if (php_sapi_name() !== 'cli' && ($_GET['secret_key'] ?? '') !== $secret) {
    die('Forbidden');
}

$sent = 0;
$failed = 0;

// Берём 20 писем
$stmt = $pdo->query("
    SELECT * FROM email_queue 
    WHERE status = 'pending' AND attempts < 3 
    ORDER BY id ASC 
    LIMIT 20
");
$emails = $stmt->fetchAll();

foreach ($emails as $e) {
    $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    
    $ok = @mail($e['to_email'], $e['subject'], $e['body'], $headers, "-f " . MAIL_FROM);
    
    if ($ok) {
        $pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")
            ->execute([$e['id']]);
        $sent++;
    } else {
        $newStatus = ($e['attempts'] + 1 >= 3) ? 'failed' : 'pending';
        $pdo->prepare("UPDATE email_queue SET status = ?, attempts = attempts + 1, error = 'mail() failed' WHERE id = ?")
            ->execute([$newStatus, $e['id']]);
        $failed++;
    }
}

echo "Sent: $sent, Failed: $failed, Total: " . count($emails) . "\n";