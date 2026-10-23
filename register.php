<?php
// api/register.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'owner';
$company = trim($input['company'] ?? $input['company_name'] ?? '');
$inn = trim($input['inn'] ?? '');

// Валидация
if (empty($name) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Имя, email и пароль обязательны'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Неверный формат email'
    ]);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Пароль должен быть не менее 6 символов'
    ]);
    exit;
}

if (!in_array($role, ['owner', 'carrier'])) {
    $role = 'owner';
}

try {
    // Проверка на существование email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь с таким email уже существует'
        ]);
        exit;
    }
    
    // Хешируем пароль
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Создаём пользователя
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, password, role, company_name, inn, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $email, $phone, $passwordHash, $role, $company, $inn]);
    
    $userId = $pdo->lastInsertId();
    
    // Даём бесплатный пробный период перевозчику
    if ($role === 'carrier') {
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (user_id, plan, status, expires_at) 
            VALUES (?, 'free', 'active', DATE_ADD(NOW(), INTERVAL 14 DAY))
        ");
        $stmt->execute([$userId]);
    }
    
    // Создаем сессию
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
    session_write_close();
    
    $userData = [
        'id' => $userId,
        'email' => $email,
        'name' => $name,
        'phone' => $phone ?: null,
        'company' => $company ?: null,
        'role' => $role,
        'avatar' => null,
        'is_verified' => false,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Регистрация успешна',
        'user' => $userData,
        'session_id' => session_id()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}
?>