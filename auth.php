<?php
function register() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role']; // 'owner' или 'carrier'
        $company = trim($_POST['company_name'] ?? '');
        $inn = trim($_POST['inn'] ?? '');
        
        // Проверка на существование email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Пользователь с таким email уже существует";
            require_once __DIR__ . '/../templates/register.php';
            return;
        }
        
        // Создаём пользователя
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, company_name, inn) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $password, $role, $company, $inn]);
        
        // Автоматически даём бесплатную подписку перевозчику
        if ($role === 'carrier') {
            $userId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan, status, expires_at) VALUES (?, 'free', 'active', DATE_ADD(NOW(), INTERVAL 14 DAY))");
            $stmt->execute([$userId]);
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        header('Location: /dashboard');
        exit;
    }
    
    require_once __DIR__ . '/../templates/register.php';
}

function login() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
           $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: /dashboard');
            exit;
        } else {
            $error = "Неверный email или пароль";
        }
    }
    
    require_once __DIR__ . '/../templates/login.php';
}

function logout() {
    session_destroy();
    header('Location: /');
    exit;
}

// Проверка авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}