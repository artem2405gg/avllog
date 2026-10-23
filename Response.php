<?php
/**
 * Response — единый JSON-ответ для API
 * Использование:
 *   Response::success(['order_id' => 5]);
 *   Response::error('Не авторизован', 401);
 */

class Response
{
    /**
     * Успешный ответ
     */
    public static function success($data = [], int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Ошибка
     */
    public static function error(string $message, int $code = 400, array $extra = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => false,
            'error'   => $message,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Проверка авторизации сессии (для API)
     */
    public static function requireAuth(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            self::error('Не авторизован', 401);
        }
        return (int)$_SESSION['user_id'];
    }

    /**
     * Проверка роли (owner / carrier / admin)
     */
    public static function requireRole(string $role): void
    {
        if (($_SESSION['user_role'] ?? '') !== $role) {
            self::error('Нет доступа', 403);
        }
    }
}