<?php
/**
 * cron_bounces.php — обработка bounce-писем
 * 
 * Запуск через браузер: https://avllogist.ru/cron_bounces.php?key=AVL_BOUNCE_2026_SECRET
 */

// === ЗАЩИТА ОТ ПУБЛИЧНОГО ДОСТУПА ===
$isCli = (php_sapi_name() === 'cli');
$secretKey = 'AVL_BOUNCE_2026_SECRET';

if (!$isCli) {
    if (($_GET['key'] ?? '') !== $secretKey) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============ НАСТРОЙКИ IMAP ============
$imapHost = 'mail.avllogist.ru';
$imapPort = 993;
$imapUser = 'info@avllogist.ru';
$imapPass = 'Artem240503@';

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

if (!function_exists('imap_open')) {
    die("ОШИБКА: PHP IMAP расширение не установлено.\n");
}

// ⚠️ /novalidate-cert — отключает проверку сертификата (Sprinthost: from.sh)
$mailbox = "{{$imapHost}:{$imapPort}/imap/ssl/novalidate-cert}INBOX";
$mbox = @imap_open($mailbox, $imapUser, $imapPass);

if (!$mbox) {
    die("ОШИБКА IMAP: " . imap_last_error() . "\n");
}

echo "✓ Подключено к {$mailbox}\n";

// ============ ПОИСК BOUNCE-ПИСЕМ ============
$emails = imap_search($mbox, 'FROM "MAILER-DAEMON"');
if (!$emails) $emails = imap_search($mbox, 'SUBJECT "Delivery Status Notification"');
if (!$emails) $emails = imap_search($mbox, 'SUBJECT "Undelivered"');
if (!$emails) $emails = imap_search($mbox, 'SUBJECT "Mail delivery failed"');

if (!$emails) {
    echo "Письма-bounce не найдены.\n";
    imap_close($mbox);
    exit;
}

echo "Найдено писем-bounce: " . count($emails) . "\n\n";

// ============ ОБРАБОТКА ============
$processed = 0;
$notFound = 0;
$parsed = 0;
$invalidEmails = [];

foreach ($emails as $num) {
    $body = imap_fetchbody($mbox, $num, 1);
    $fullBody = imap_fetchbody($mbox, $num, '');

    if (strpos($fullBody, 'Content-Transfer-Encoding: base64') !== false) {
        if (preg_match('/Content-Transfer-Encoding: base64\s*\n\s*([A-Za-z0-9+\/=\s]+)/s', $fullBody, $m)) {
            $decoded = base64_decode($m[1]);
            if ($decoded !== false) $body = $decoded;
        }
    }

    $email = null;

    if (preg_match('/Final-Recipient:\s*rfc822;\s*([^\s\r\n]+)/i', $body, $m)) {
        $email = trim($m[1]);
    }
    if (!$email && preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}):\s*user not found/i', $body, $m)) {
        $email = trim($m[1]);
    }
    if (!$email && preg_match('/X-Failed-Recipients:\s*([^\s\r\n]+)/i', $body, $m)) {
        $email = trim($m[1]);
    }
    if (!$email && preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $body, $m)) {
        $candidate = trim($m[1]);
        if (strpos($candidate, 'avllogist.ru') === false) $email = $candidate;
    }

    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $parsed++;
        $invalidEmails[] = $email;

        try {
            $stmt = $pdo->prepare("UPDATE users SET email_invalid = 1 WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $processed++;
                echo "✓ Помечен: {$email}\n";
            } else {
                $notFound++;
                echo "· Нет в БД: {$email}\n";
            }
        } catch (Throwable $e) {
            error_log("[cron_bounces] SQL error for {$email}: " . $e->getMessage());
        }
    }

    imap_setflag_full($mbox, $num, "\\Seen");
}

imap_close($mbox);

echo "\n=== ИТОГО ===\n";
echo "Всего писем-bounce: " . count($emails) . "\n";
echo "Успешно распарсено: {$parsed}\n";
echo "Уникальных: " . count(array_unique($invalidEmails)) . "\n";
echo "Помечено в БД: {$processed}\n";
echo "Не найдено в БД: {$notFound}\n";

$logFile = __DIR__ . '/../logs/bounces_parsed_' . date('Y-m-d_H-i') . '.txt';
@file_put_contents($logFile, implode("\n", array_unique($invalidEmails)));
echo "Список сохранён: {$logFile}\n";