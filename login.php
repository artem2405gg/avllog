<?php
// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Заголовки
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Получаем данные
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Проверяем, что данные получены
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email и пароль обязательны',
        'received' => [
            'email' => $email,
            'password_length' => strlen($password)
        ]
    ]);
    exit;
}

// Подключаем конфигурацию
$configPaths = [
    __DIR__ . '/../app/config.php',
    dirname(__DIR__) . '/app/config.php',
    dirname(__DIR__) . '/config.php',
    __DIR__ . '/../config.php'
];

$configLoaded = false;
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $configLoaded = true;
        error_log("Config loaded from: " . $path);
        break;
    }
}

if (!$configLoaded) {
    echo json_encode([
        'success' => false,
        'message' => 'Файл конфигурации не найден',
        'searched_paths' => $configPaths,
        'current_dir' => __DIR__
    ]);
    exit;
}

// Подключаем базу данных
$databasePaths = [
    __DIR__ . '/../app/database.php',
    dirname(__DIR__) . '/app/database.php',
    dirname(__DIR__) . '/database.php',
    __DIR__ . '/../database.php'
];

$databaseLoaded = false;
foreach ($databasePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $databaseLoaded = true;
        error_log("Database loaded from: " . $path);
        break;
    }
}

if (!$databaseLoaded) {
    echo json_encode([
        'success' => false,
        'message' => 'Файл базы данных не найден',
        'searched_paths' => $databasePaths,
        'current_dir' => __DIR__
    ]);
    exit;
}

// Проверяем подключение к БД
if (!isset($pdo) || !$pdo instanceof PDO) {
    echo json_encode([
        'success' => false,
        'message' => 'Нет подключения к базе данных',
        'pdo_status' => isset($pdo) ? get_class($pdo) : 'not set'
    ]);
    exit;
}

// Ищем пользователя
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Создаем сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'] ?? 'owner';
        session_write_close();
        
        // Формируем данные пользователя
        $userData = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'phone' => $user['phone'] ?? null,
            'company' => $user['company_name'] ?? null,
            'role' => $user['role'] ?? 'owner',
            'avatar' => $user['avatar'] ?? null,
            'is_verified' => (bool)($user['is_verified'] ?? false),
            'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Вход выполнен успешно',
            'user' => $userData,
            'session_id' => session_id()
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Неверный email или пароль',
            'user_found' => (bool)$user,
            'password_verified' => $user ? password_verify($password, $user['password']) : false
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>