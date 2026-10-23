<?php
/**
 * cron_return_expire.php — протухание поисков обратных рейсов
 * Запускать раз в час через cron
 */

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/lib/ReturnSearch.php';

// Проверка: только CLI или секретный ключ
if (php_sapi_name() !== 'cli') {
    $secret = 'cron_avl_' . md5('return_secret_2026');
    if (($_GET['secret_key'] ?? '') !== $secret) {
        die('Forbidden');
    }
}

$rs = new ReturnSearch($pdo);
$count = $rs->expireOld();

echo "Expired searches: {$count}\n";