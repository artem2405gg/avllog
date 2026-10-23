<?php
/**
 * app/config.php — общие настройки проекта
 * 
 * ВАЖНО: session_start() в самом начале — чтобы сессия была доступна
 * во всех standalone-файлах (order_pay.php, tinkoff_init.php, cron_email.php и т.д.)
 */

// ========== СЕССИЯ ==========
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 2592000);      // 30 дней
    ini_set('session.cookie_lifetime', 2592000);     // 30 дней
    session_set_cookie_params(2592000);
    session_start();
}

// ========== БД ==========
define('DB_HOST', 'localhost');
define('DB_NAME', 'a1069720_avllogist');
define('DB_USER', 'a1069720_avllogist');
define('DB_PASS', 'Ground23058@');
define('SITE_URL', 'https://avllogist.ru');

// ========== ЯНДЕКС.КАРТЫ ==========
define('YANDEX_MAPS_API_KEY', '99623d6f-c48b-44c8-aa6b-a12ef4419781');
define('YANDEX_GEOCODER_KEY', '99623d6f-c48b-44c8-aa6b-a12ef4419781');

// ========== РЕКВИЗИТЫ ДЛЯ СЧЕТОВ ==========
define('PAYMENT_DETAILS', "Виноградов Андрей Юрьевич\nИНН: 4401116480\nР/с: 40817810250189673327\nБанк: ФИЛИАЛ «ЦЕНТРАЛЬНЫЙ» ПАО «СОВКОМБАНК»\nБИК: 045004763\nК/с: 30101810150040000763");

// ========== ПОЧТА ==========
define('MAIL_FROM', 'info@avllogist.ru');
define('MAIL_FROM_NAME', 'AVL Logistic');

// ========== ПУБЛИЧНАЯ ОФЕРТА (версия и дата) ==========
// Меняется при каждой правке оферты. Старые акцепты сохраняют свою версию.
define('OFFER_VERSION', '1.0');
define('OFFER_DATE', '2026-09-01');         // дата, когда редакция вступила в силу
define('OFFER_URL', SITE_URL . '/page/terms');