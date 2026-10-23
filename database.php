<?php
/**
 * app/database.php — подключение к MySQL
 * 
 * Особенности:
 *   - host = 127.0.0.1 (не localhost — сокет не работает на shared-хостинге)
 *   - charset = utf8mb4 (поддержка эмодзи и 4-байтовых символов)
 *   - MYSQL_ATTR_INIT_COMMAND — принудительно фиксируем кодировку на уровне соединения
 *   - ERRMODE_EXCEPTION — ошибки PDO бросают исключения
 */

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}