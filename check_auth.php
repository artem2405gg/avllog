<?php
// api/check_auth.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => (int)$user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'phone' => $user['phone'] ?? null,
                'company' => $user['company_name'] ?? null,
                'role' => $user['role'] ?? 'owner',
                'avatar' => $user['avatar'] ?? null,
                'is_verified' => (bool)($user['is_verified'] ?? false),
                'created_at' => $user['created_at'] ?? null
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь не найден'
        ]);
    }
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Не авторизован'
    ]);
}
?>