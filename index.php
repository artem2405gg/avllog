<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);
session_set_cookie_params(2592000);
session_start();

function __($key) { return $key; }

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/config.php';
require_once BASE_PATH . '/app/database.php';

function sendEmail($to, $subject, $message) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT email_invalid FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$to]);
        if ($stmt->fetchColumn()) {
            error_log("[sendEmail] Пропущено для {$to} — email_invalid=1");
            return false;
        }
    } catch (Throwable $e) {}
    
    try {
        $stmt = $pdo->prepare("INSERT INTO email_queue (to_email, subject, body) VALUES (?, ?, ?)");
        $stmt->execute([$to, $subject, $message]);
        return true;
    } catch (Throwable $e) {
        error_log("sendEmail queue error: " . $e->getMessage());
        return false;
    }
}

// ========== УВЕДОМЛЕНИЕ ПЕРЕВОЗЧИКОВ О НОВОМ ЗАКАЗЕ ==========
function notifyMatchingCarriers($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return false;

    $timeLabels = [
        'morning' => 'Утро (8:00–12:00)',
        'day'     => 'День (12:00–17:00)',
        'evening' => 'Вечер (17:00–21:00)',
        'exact'   => 'Точное время',
    ];
    
    $pickupWindow = '';
    if (!empty($order['pickup_time_window'])) {
        $pickupWindow = $timeLabels[$order['pickup_time_window']] ?? '';
        if ($order['pickup_time_window'] === 'exact' && !empty($order['pickup_time_exact'])) {
            $pickupWindow = 'В ' . substr($order['pickup_time_exact'], 0, 5);
        }
    }
    
    $deliveryWindow = '';
    if (!empty($order['delivery_time_window'])) {
        $deliveryWindow = $timeLabels[$order['delivery_time_window']] ?? '';
        if ($order['delivery_time_window'] === 'exact' && !empty($order['delivery_time_exact'])) {
            $deliveryWindow = 'В ' . substr($order['delivery_time_exact'], 0, 5);
        }
    }
    
    $conditions = [];
    if (!empty($order['requires_pass']))     $conditions[] = '🚪 Требуется пропуск';
    if (!empty($order['unload_on_floor']))   $conditions[] = '🏢 Разгрузка на этаже';
    if (!empty($order['needs_manipulator'])) $conditions[] = '🏗️ Нужен манипулятор';
    if (!empty($order['is_fragile']))        $conditions[] = '⚠️ Хрупкий груз';
    
    $vehicleLine = !empty($order['vehicle_type']) ? ", ТС: {$order['vehicle_type']}" : '';
    
    $orderBlock  = "Название: {$order['title']}\n";
    $orderBlock .= "Груз: {$order['cargo_type']}{$vehicleLine}, {$order['weight']} т, {$order['volume']} м³\n";
    $orderBlock .= "Маршрут: {$order['pickup_address']} → {$order['delivery_address']}\n";
    $orderBlock .= "Погрузка: {$order['pickup_date']}";
    if ($pickupWindow) $orderBlock .= " ({$pickupWindow})";
    $orderBlock .= "\n";
    $orderBlock .= "Выгрузка: {$order['delivery_date']}";
    if ($deliveryWindow) $orderBlock .= " ({$deliveryWindow})";
    $orderBlock .= "\n";
    $orderBlock .= "Цена: {$order['price']} ₽\n";
    
    if (!empty($conditions)) {
        $orderBlock .= "\nОсобые условия:\n" . implode("\n", $conditions) . "\n";
    }
    if (!empty($order['special_comment'])) {
        $orderBlock .= "\n💬 Комментарий: {$order['special_comment']}\n";
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.email, u.name
        FROM users u
        LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active' AND s.expires_at > NOW()
        WHERE u.role = 'carrier'
        AND u.email_verified = 1
        AND u.email_invalid = 0
    ");
    $stmt->execute();
    $carriers = $stmt->fetchAll();

    foreach ($carriers as $carrier) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, is_read) 
                VALUES (?, 'new_order', 'Новый заказ!', ?, ?, 0)
            ");
            $stmt->execute([
                $carrier['id'],
                "Новый заказ: {$order['title']} ({$order['cargo_type']}, {$order['weight']} т)",
                "/orders/view/{$orderId}"
            ]);
        } catch (Exception $e) {}

        try {
            sendEmail(
                $carrier['email'],
                "Новый заказ на AVL Logistic: {$order['title']}",
                "Здравствуйте, {$carrier['name']}!\n\n" .
                "Появился новый заказ:\n\n" .
                $orderBlock .
                "\nОткликнуться: " . SITE_URL . "/orders/view/{$orderId}\n\n" .
                "С уважением,\nAVL Logistic"
            );
        } catch (Exception $e) {}
    }
    return true;
}

// ========== АВТОРИЗАЦИЯ ==========
function checkAuth() {
    if (!isset($_SESSION['user_id'])) { header('Location: /login'); exit; }
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

function register() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']); $email = trim($_POST['email']);
        $phone = trim($_POST['phone']); $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? $_GET['role'] ?? 'owner';
        if (!in_array($role, ['owner', 'carrier'])) $role = 'owner';
        $company = trim($_POST['company_name'] ?? ''); $inn = trim($_POST['inn'] ?? '');
        $manualRefCode = trim($_POST['ref_code'] ?? '');
        if (!empty($manualRefCode) && empty($_SESSION['ref_by'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE ref_code = ?");
            $stmt->execute([$manualRefCode]); $referrer = $stmt->fetch();
            if ($referrer) { $_SESSION['ref_by'] = $referrer['id']; $_SESSION['ref_code'] = $manualRefCode; }
        }

        // ========== АКЦЕПТ ПУБЛИЧНОЙ ОФЕРТЫ ==========
        // Галочка обязательна. Без неё — ошибка и возврат к форме.
        if (empty($_POST['accept_offer'])) {
            $error = "❌ Необходимо принять условия Публичной оферты";
            require_once BASE_PATH . '/templates/register.php';
            return;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?"); $stmt->execute([$email]);
        if ($stmt->fetch()) { $error = "❌ Пользователь с таким email уже существует"; require_once BASE_PATH . '/templates/register.php'; return; }
        if (!empty($phone)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND phone != ''"); $stmt->execute([$phone]);
            if ($stmt->fetch()) { $error = "❌ Пользователь с таким телефоном уже зарегистрирован"; require_once BASE_PATH . '/templates/register.php'; return; }
        }
        if (!empty($inn)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE inn = ? AND inn != ''"); $stmt->execute([$inn]);
            if ($stmt->fetch()) { $error = "❌ Пользователь с таким ИНН уже зарегистрирован"; require_once BASE_PATH . '/templates/register.php'; return; }
        }

        $code = md5($email . time() . rand(1000, 9999));
        $refCode = strtoupper(substr(md5($email . time()), 0, 8));
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, company_name, inn, verification_code, ref_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $password, $role, $company, $inn, $code, $refCode]);
        $userId = $pdo->lastInsertId();

        // ========== ФИКСАЦИЯ АКЦЕПТА ОФЕРТЫ ==========
        // Пишем в acceptances: user_id, тип документа, версия оферты, IP, user-agent.
        // Ошибку логируем, но регистрацию не откатываем.
        try {
            $pdo->prepare("
                INSERT INTO acceptances (user_id, document_type, offer_version, ip_address, user_agent)
                VALUES (?, 'offer', ?, ?, ?)
            ")->execute([
                $userId,
                OFFER_VERSION,
                $_SERVER['REMOTE_ADDR'] ?? null,
                mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Throwable $e) {
            error_log('[register acceptance] ' . $e->getMessage());
        }

        if ($role === 'carrier') {
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan, status, expires_at) VALUES (?, 'free', 'active', DATE_ADD(NOW(), INTERVAL 30 DAY))");
            $stmt->execute([$userId]);
        }

        if ($role === 'owner') { $_SESSION['metrika_goal'] = 'owner_register'; }
        elseif ($role === 'carrier') { $_SESSION['metrika_goal'] = 'carrier_register'; }

        if (!empty($_SESSION['ref_by'])) {
            $stmt = $pdo->prepare("UPDATE users SET ref_by = ? WHERE id = ?"); $stmt->execute([$_SESSION['ref_by'], $userId]);
            $stmt = $pdo->prepare("UPDATE users SET bonus_days = bonus_days + 14 WHERE id = ?"); $stmt->execute([$_SESSION['ref_by']]);
            $stmt = $pdo->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(expires_at, INTERVAL 14 DAY) WHERE user_id = ? AND status = 'active' ORDER BY expires_at DESC LIMIT 1"); $stmt->execute([$_SESSION['ref_by']]);
            $stmt = $pdo->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(expires_at, INTERVAL 5 DAY) WHERE user_id = ? AND status = 'active' ORDER BY expires_at DESC LIMIT 1"); $stmt->execute([$userId]);
            unset($_SESSION['ref_by'], $_SESSION['ref_code']);
        }
        sendEmail($email, "Подтверждение регистрации AVL Logistic", "Здравствуйте, {$name}!\n\nДля подтверждения email перейдите по ссылке:\n" . SITE_URL . "/verify?code={$code}\n\nС уважением,\nAVL Logistic");
        $_SESSION['user_id'] = $userId; $_SESSION['user_name'] = $name; $_SESSION['user_email'] = $email; $_SESSION['user_role'] = $role; $_SESSION['email_verified'] = false;
        header('Location: /dashboard?verify_email=1'); exit;
    }
    require_once BASE_PATH . '/templates/register.php';
}

function login() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']); $password = $_POST['password'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?"); $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; $_SESSION['user_name'] = $user['name']; $_SESSION['user_email'] = $user['email']; $_SESSION['user_role'] = $user['role']; $_SESSION['email_verified'] = (bool)$user['email_verified'];
            header('Location: /dashboard'); exit;
        } else { $error = "Неверный email или пароль"; }
    }
    require_once BASE_PATH . '/templates/login.php';
}

function logout() { session_destroy(); header('Location: /'); exit; }

function verifyEmail() {
    global $pdo;
    $code = $_GET['code'] ?? '';
    if (empty($code)) { header('Location: /'); exit; }
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE verification_code = ? AND email_verified = 0");
    $stmt->execute([$code]);
    $user = $stmt->fetch();
    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        $stmt = $pdo->prepare("UPDATE orders SET status = 'new' WHERE user_id = ? AND status = 'hidden'");
        $stmt->execute([$user['id']]);
        $_SESSION['email_verified'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        require_once BASE_PATH . '/templates/verify_success.php';
    } else {
        echo "<div style='text-align:center;padding:40px;'><h2>Ссылка недействительна</h2><a href='/'>На главную</a></div>";
    }
}

function forgotPassword() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]); $user = $stmt->fetch();
        if ($user) {
            $code = md5($email . time());
            $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $stmt->execute([$code, $user['id']]);
            sendEmail($email, "Восстановление пароля", "Ссылка: " . SITE_URL . "/reset?code={$code}");
        }
        $success = "Если email существует, письмо отправлено.";
    }
    require_once BASE_PATH . '/templates/forgot_password.php';
}

function resetPassword() {
    global $pdo; $code = $_GET['code'] ?? '';
    if (empty($code)) { header('Location: /login'); exit; }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_code = ? AND reset_expires > NOW()");
    $stmt->execute([$code]); $user = $stmt->fetch();
    if (!$user) { $error = "Ссылка недействительна."; require_once BASE_PATH . '/templates/reset_password.php'; return; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        if (strlen($password) < 6) { $error = "Минимум 6 символов"; require_once BASE_PATH . '/templates/reset_password.php'; return; }
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        $success = "Пароль изменён! <a href='/login'>Войти</a>";
        require_once BASE_PATH . '/templates/reset_password.php'; return;
    }
    require_once BASE_PATH . '/templates/reset_password.php';
}

function refPage() {
    checkAuth(); global $pdo;
    $stmt = $pdo->prepare("SELECT ref_code, bonus_days FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]); $user = $stmt->fetch();
    if (empty($user['ref_code'])) {
        $code = strtoupper(substr(md5($_SESSION['user_id'] . time()), 0, 8));
        $stmt = $pdo->prepare("UPDATE users SET ref_code = ? WHERE id = ?");
        $stmt->execute([$code, $_SESSION['user_id']]); $user['ref_code'] = $code;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE ref_by = ?");
    $stmt->execute([$_SESSION['user_id']]); $refCount = $stmt->fetch()['cnt'];
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE ref_by = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]); $refUsers = $stmt->fetchAll();
    $refLink = SITE_URL . "/r/{$user['ref_code']}";
    $bonusDays = $user['bonus_days'];
    require_once BASE_PATH . '/templates/ref_page.php';
}

function refRegister($code) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE ref_code = ?");
    $stmt->execute([$code]); $referrer = $stmt->fetch();
    if ($referrer) { $_SESSION['ref_by'] = $referrer['id']; $_SESSION['ref_code'] = $code; }
    header('Location: /register'); exit;
}

function createOrder() {
    checkAuth();
    global $pdo;

    if ($_SESSION['user_role'] !== 'owner') {
        header('Location: /orders');
        exit;
    }

    $stmt = $pdo->prepare("SELECT email_verified, inn FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (empty($user['inn'])) {
        $_SESSION['error'] = "Для создания заказа необходимо указать ИНН.";
        header('Location: /profile/edit?required=inn');
        exit;
    }

    $emailNotVerified = !$user['email_verified'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $status = $emailNotVerified ? 'hidden' : 'new';

        $allowedWindows = ['morning', 'day', 'evening', 'exact'];
        $pickupWindow   = in_array($_POST['pickup_time_window'] ?? '', $allowedWindows) ? $_POST['pickup_time_window'] : null;
        $deliveryWindow = in_array($_POST['delivery_time_window'] ?? '', $allowedWindows) ? $_POST['delivery_time_window'] : null;

        $pickupTimeExact   = ($pickupWindow === 'exact' && !empty($_POST['pickup_time_exact']))     ? $_POST['pickup_time_exact'] : null;
        $deliveryTimeExact = ($deliveryWindow === 'exact' && !empty($_POST['delivery_time_exact'])) ? $_POST['delivery_time_exact'] : null;

        $pickupContactName    = trim($_POST['pickup_contact_name'] ?? '') ?: null;
        $pickupContactPhone   = trim($_POST['pickup_contact_phone'] ?? '') ?: null;
        $deliveryContactName  = trim($_POST['delivery_contact_name'] ?? '') ?: null;
        $deliveryContactPhone = trim($_POST['delivery_contact_phone'] ?? '') ?: null;

        $requiresPass     = !empty($_POST['requires_pass']) ? 1 : 0;
        $unloadOnFloor    = !empty($_POST['unload_on_floor']) ? 1 : 0;
        $needsManipulator = !empty($_POST['needs_manipulator']) ? 1 : 0;
        $isFragile        = !empty($_POST['is_fragile']) ? 1 : 0;
        $specialComment   = trim($_POST['special_comment'] ?? '') ?: null;

        $vehicleType = trim($_POST['vehicle_type'] ?? '') ?: null;

        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, title, cargo_type, vehicle_type, weight, volume, 
                pickup_address, pickup_contact_name, pickup_contact_phone,
                delivery_address, delivery_contact_name, delivery_contact_phone,
                pickup_date, pickup_time_window, pickup_time_exact,
                delivery_date, delivery_time_window, delivery_time_exact,
                price, status,
                requires_pass, unload_on_floor, needs_manipulator, is_fragile, special_comment
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, ?, 
                ?, ?, 
                ?, ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['cargo_type'],
            $vehicleType,
            $_POST['weight'],
            $_POST['volume'],
            $_POST['pickup_address'],
            $pickupContactName,
            $pickupContactPhone,
            $_POST['delivery_address'],
            $deliveryContactName,
            $deliveryContactPhone,
            $_POST['pickup_date'],
            $pickupWindow,
            $pickupTimeExact,
            $_POST['delivery_date'],
            $deliveryWindow,
            $deliveryTimeExact,
            $_POST['price'],
            $status,
            $requiresPass,
            $unloadOnFloor,
            $needsManipulator,
            $isFragile,
            $specialComment,
        ]);

        $orderId = $pdo->lastInsertId();
        $_SESSION['metrika_goal'] = 'order_created';

        if ($emailNotVerified) {
            $_SESSION['success'] = "✅ Заказ создан! Но он будет виден перевозчикам только после подтверждения email.";
        } else {
            notifyMatchingCarriers($orderId);
            $_SESSION['success'] = "✅ Заказ успешно создан!";
        }

        header('Location: /orders/my');
        exit;
    }

    require_once BASE_PATH . '/templates/order_create.php';
}

/**
 * Редактирование заказа owner'ом
 */
function editOrder($orderId) {
    checkAuth();
    global $pdo;

    if ($_SESSION['user_role'] !== 'owner') {
        header('Location: /orders');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error'] = 'Заказ не найден';
        header('Location: /orders/my');
        exit;
    }

    if (!in_array($order['status'], ['new', 'hidden'])) {
        $_SESSION['error'] = 'Нельзя редактировать заказ после выбора перевозчика';
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $allowedWindows = ['morning', 'day', 'evening', 'exact'];
        $pickupWindow   = in_array($_POST['pickup_time_window'] ?? '', $allowedWindows) ? $_POST['pickup_time_window'] : null;
        $deliveryWindow = in_array($_POST['delivery_time_window'] ?? '', $allowedWindows) ? $_POST['delivery_time_window'] : null;

        $pickupTimeExact   = ($pickupWindow === 'exact' && !empty($_POST['pickup_time_exact']))     ? $_POST['pickup_time_exact'] : null;
        $deliveryTimeExact = ($deliveryWindow === 'exact' && !empty($_POST['delivery_time_exact'])) ? $_POST['delivery_time_exact'] : null;

        $vehicleType = trim($_POST['vehicle_type'] ?? '') ?: null;
        
        $stmt = $pdo->prepare("
            UPDATE orders SET
                title = ?, cargo_type = ?, vehicle_type = ?, weight = ?, volume = ?,
                pickup_address = ?, pickup_contact_name = ?, pickup_contact_phone = ?,
                delivery_address = ?, delivery_contact_name = ?, delivery_contact_phone = ?,
                pickup_date = ?, pickup_time_window = ?, pickup_time_exact = ?,
                delivery_date = ?, delivery_time_window = ?, delivery_time_exact = ?,
                price = ?,
                requires_pass = ?, unload_on_floor = ?, needs_manipulator = ?, is_fragile = ?,
                special_comment = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $_POST['title'],
            $_POST['cargo_type'],
            $vehicleType,
            $_POST['weight'],
            $_POST['volume'],
            $_POST['pickup_address'],
            trim($_POST['pickup_contact_name'] ?? '') ?: null,
            trim($_POST['pickup_contact_phone'] ?? '') ?: null,
            $_POST['delivery_address'],
            trim($_POST['delivery_contact_name'] ?? '') ?: null,
            trim($_POST['delivery_contact_phone'] ?? '') ?: null,
            $_POST['pickup_date'],
            $pickupWindow,
            $pickupTimeExact,
            $_POST['delivery_date'],
            $deliveryWindow,
            $deliveryTimeExact,
            $_POST['price'],
            !empty($_POST['requires_pass']) ? 1 : 0,
            !empty($_POST['unload_on_floor']) ? 1 : 0,
            !empty($_POST['needs_manipulator']) ? 1 : 0,
            !empty($_POST['is_fragile']) ? 1 : 0,
            trim($_POST['special_comment'] ?? '') ?: null,
            $orderId,
            $_SESSION['user_id'],
        ]);

        $_SESSION['success'] = '✅ Заказ обновлён';
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    $stmt = $pdo->prepare("SELECT DISTINCT pickup_address FROM orders WHERE user_id = ? AND pickup_address IS NOT NULL AND pickup_address != '' ORDER BY id DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $pickupHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT DISTINCT delivery_address FROM orders WHERE user_id = ? AND delivery_address IS NOT NULL AND delivery_address != '' ORDER BY id DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $deliveryHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT DISTINCT title FROM orders WHERE user_id = ? AND title IS NOT NULL AND title != '' ORDER BY id DESC LIMIT 15");
    $stmt->execute([$_SESSION['user_id']]);
    $titleHistory = $stmt->fetchAll(PDO::FETCH_COLUMN);

    require_once BASE_PATH . '/templates/order_edit.php';
}

// ========== ОБРАТНЫЕ РЕЙСЫ ==========

/**
 * Страница поиска обратного рейса
 */
function returnSearchPage() {
    checkAuth();
    global $pdo;

    if ($_SESSION['user_role'] !== 'carrier') {
        header('Location: /orders');
        exit;
    }

    require_once BASE_PATH . '/app/lib/ReturnSearch.php';
    $rs = new ReturnSearch($pdo);

    $activeSearch = $rs->getActive($_SESSION['user_id']);

    // Для автозаполнения телефона
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profilePhone = $stmt->fetchColumn() ?: '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $searchId = $rs->create($_SESSION['user_id'], [
            'city'                  => $_POST['city'] ?? '',
            'radius_km'             => $_POST['radius_km'] ?? 300,
            'date_from'             => $_POST['date_from'] ?? date('Y-m-d'),
            'date_to'               => $_POST['date_to'] ?? date('Y-m-d', strtotime('+3 days')),
            'vehicle_type'          => $_POST['vehicle_type'] ?? null,
            'capacity_tons'         => $_POST['capacity_tons'] ?? null,
            'capacity_volume'       => $_POST['capacity_volume'] ?? null,
            'preferred_destination' => $_POST['preferred_destination'] ?? null,
            'contact_phone'         => $_POST['contact_phone'] ?? null,
            'comment'               => $_POST['comment'] ?? null,
        ]);

        if (!$searchId) {
            $_SESSION['error'] = 'У вас уже есть активный поиск. Отмените его, чтобы создать новый.';
            header('Location: /orders/return');
            exit;
        }

        $_SESSION['success'] = '✅ Поиск создан! Показываем подходящие заказы.';
        header('Location: /orders/return/' . $searchId);
        exit;
    }

    require_once BASE_PATH . '/templates/return_search.php';
}

/**
 * Показать результаты поиска
 */
function returnSearchResults($searchId) {
    checkAuth();
    global $pdo;

    if ($_SESSION['user_role'] !== 'carrier') {
        header('Location: /orders');
        exit;
    }

    require_once BASE_PATH . '/app/lib/ReturnSearch.php';
    $rs = new ReturnSearch($pdo);

    $stmt = $pdo->prepare("SELECT * FROM return_searches WHERE id = ? AND carrier_id = ?");
    $stmt->execute([$searchId, $_SESSION['user_id']]);
    $search = $stmt->fetch();

    if (!$search) {
        $_SESSION['error'] = 'Поиск не найден';
        header('Location: /orders/return');
        exit;
    }

    $orders = $rs->findOrders($searchId);

    require_once BASE_PATH . '/templates/return_orders.php';
}

/**
 * Отменить поиск
 */
function returnSearchCancel($searchId) {
    checkAuth();
    global $pdo;

    if ($_SESSION['user_role'] !== 'carrier') {
        header('Location: /orders');
        exit;
    }

    require_once BASE_PATH . '/app/lib/ReturnSearch.php';
    $rs = new ReturnSearch($pdo);

    $ok = $rs->cancel($searchId, $_SESSION['user_id']);

    if ($ok) {
        $_SESSION['success'] = '✅ Поиск отменён';
    }

    header('Location: /orders/return');
    exit;
}

function listOrders() {
    checkAuth(); global $pdo;
    $where = "WHERE o.status = 'new'"; $params = [];
    if (!empty($_GET['city_from'])) { $where .= " AND o.pickup_address LIKE ?"; $params[] = '%' . $_GET['city_from'] . '%'; }
    if (!empty($_GET['city_to'])) { $where .= " AND o.delivery_address LIKE ?"; $params[] = '%' . $_GET['city_to'] . '%'; }
    $stmt = $pdo->prepare("SELECT o.*, u.company_name, u.rating FROM orders o JOIN users u ON o.user_id = u.id $where ORDER BY o.id DESC LIMIT 20");
    $stmt->execute($params); $orders = $stmt->fetchAll();
    require_once BASE_PATH . '/templates/orders_list.php';
}

function viewOrder($id) {
    checkAuth(); global $pdo;
    $stmt = $pdo->prepare("SELECT o.*, u.name, u.company_name, u.rating, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$id]); $order = $stmt->fetch();
    if (!$order) { header('Location: /orders'); exit; }

    if ($order['status'] == 'unloaded') {
        $stmt = $pdo->prepare("SELECT created_at FROM order_status_history WHERE order_id = ? AND status = 'unloaded' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$id]); $unloadedTime = $stmt->fetch();
        if ($unloadedTime && (time() - strtotime($unloadedTime['created_at'])) / 3600 >= 24) {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $stmt->execute([$id]); $order['status'] = 'completed';
        }
    }

    $stmt = $pdo->prepare("SELECT b.*, u.company_name, u.rating FROM bids b JOIN users u ON b.carrier_id = u.id WHERE b.order_id = ? ORDER BY b.id ASC");
    $stmt->execute([$id]); $bids = $stmt->fetchAll();

    $myBid = null;
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'carrier') {
        $stmt = $pdo->prepare("SELECT * FROM bids WHERE order_id = ? AND carrier_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]); $myBid = $stmt->fetch();
    }

    require_once BASE_PATH . '/templates/order_view.php';
}

function myOrders() {
    checkAuth(); global $pdo;
    if ($_SESSION['user_role'] === 'owner') {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    } else {
        $stmt = $pdo->prepare("SELECT o.*, b.status as bid_status FROM orders o JOIN bids b ON o.id = b.order_id WHERE b.carrier_id = ? ORDER BY o.id DESC");
    }
    $stmt->execute([$_SESSION['user_id']]); $orders = $stmt->fetchAll();
    require_once BASE_PATH . '/templates/my_orders.php';
}

function placeBid($orderId) {
    checkAuth(); global $pdo;
    if ($_SESSION['user_role'] !== 'carrier') { header('Location: /orders'); exit; }
    
    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?"); 
    $stmt->execute([$_SESSION['user_id']]); 
    $user = $stmt->fetch();
    if (!$user['email_verified']) { header('Location: /dashboard?verify_email=1'); exit; }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $price = $_POST['price'];
        $stmt = $pdo->prepare("INSERT INTO bids (order_id, carrier_id, price) VALUES (?, ?, ?)"); 
        $stmt->execute([$orderId, $_SESSION['user_id'], $price]);
        $_SESSION['metrika_goal'] = 'bid_placed';
        
        // Помечаем активный поиск обратного рейса как matched
        try {
            require_once BASE_PATH . '/app/lib/ReturnSearch.php';
            $rs = new ReturnSearch($pdo);
            $activeSearch = $rs->getActive($_SESSION['user_id']);
            if ($activeSearch) {
                $rs->markMatched((int)$activeSearch['id'], (int)$orderId);
            }
        } catch (Throwable $e) {
            error_log('[placeBid markMatched] ' . $e->getMessage());
        }
        
        $stmt = $pdo->prepare("SELECT o.*, u.name as owner_name, u.email as owner_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?"); 
        $stmt->execute([$orderId]); 
        $orderInfo = $stmt->fetch();
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'bid', 'Новый отклик', ?, ?)"); 
        $stmt->execute([$orderInfo['user_id'], "На заказ «{$orderInfo['title']}» отклик на {$price} ₽ от {$_SESSION['user_name']}.", "/orders/view/{$orderId}"]);
        
        sendEmail($orderInfo['owner_email'], "Новый отклик на заказ «{$orderInfo['title']}»", "Здравствуйте, {$orderInfo['owner_name']}!\n\nНа ваш заказ «{$orderInfo['title']}» новый отклик.\n\nПеревозчик: {$_SESSION['user_name']}\nЦена: {$price} ₽\n\n" . SITE_URL . "/orders/view/{$orderId}\n\nС уважением,\nAVL Logistic");
        
        header("Location: /orders/view/$orderId"); exit;
    }
}

function acceptBid() {
    checkAuth();
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $bidId = $_POST['bid_id'];

        $stmt = $pdo->prepare("SELECT * FROM bids WHERE id = ?");
        $stmt->execute([$bidId]);
        $bid = $stmt->fetch();
        if (!$bid) die("Отклик не найден");

        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$bid['order_id']]);
        $orderRow = $stmt->fetch();
        if ($orderRow['user_id'] != $_SESSION['user_id']) die("Нет доступа");

        require_once BASE_PATH . '/app/lib/Commission.php';
        $bidPrice = (float)$bid['price'];
        $calc = Commission::calculate($bidPrice, (int)$bid['carrier_id']);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE bids SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$bidId]);

        $stmt = $pdo->prepare("UPDATE bids SET status = 'rejected' WHERE order_id = ? AND id != ?");
        $stmt->execute([$bid['order_id'], $bidId]);

        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'accepted',
                carrier_id = ?,
                price = ?,
                commission_rate = ?,
                commission_amount = ?,
                carrier_payout = ?,
                payment_method = 'none'
            WHERE id = ?
        ");
        $stmt->execute([
            (int)$bid['carrier_id'],
            $bidPrice,
            $calc['commission_rate'],
            $calc['commission_amount'],
            $calc['carrier_payout'],
            $bid['order_id'],
        ]);

        $pdo->commit();

        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'accepted', ?)");
        $stmt->execute([$bid['order_id'], $_SESSION['user_id']]);

        $stmt = $pdo->prepare("SELECT o.*, u.name as carrier_name, u.email as carrier_email FROM orders o JOIN users u ON u.id = ? WHERE o.id = ?");
        $stmt->execute([$bid['carrier_id'], $bid['order_id']]);
        $info = $stmt->fetch();

        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'bid_accepted', 'Вас выбрали!', ?, ?)");
        $stmt->execute([$bid['carrier_id'], "Вы выбраны исполнителем заказа «{$info['title']}».", "/orders/view/{$bid['order_id']}"]);

        sendEmail(
            $info['carrier_email'],
            "Вас выбрали исполнителем!",
            "Здравствуйте, {$info['carrier_name']}!\n\nВы выбраны исполнителем заказа «{$info['title']}».\n\nМаршрут: {$info['pickup_address']} → {$info['delivery_address']}\nЦена: {$bid['price']} ₽\n\n" . SITE_URL . "/orders/view/{$bid['order_id']}\n\nС уважением,\nAVL Logistic"
        );

        header("Location: /orders/view/" . $bid['order_id']);
        exit;
    }
}

function updateOrderStatus($orderId) {
    checkAuth(); global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /orders/view/' . $orderId); exit; }
    $newStatus = $_POST['status'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]); $order = $stmt->fetch();
    if (!$order) { header('Location: /orders/my'); exit; }
    $isOwner = ($order['user_id'] == $_SESSION['user_id']);
    $stmt = $pdo->prepare("SELECT carrier_id FROM bids WHERE order_id = ? AND status = 'accepted'");
    $stmt->execute([$orderId]); $bid = $stmt->fetch();
    $isCarrier = ($bid && $bid['carrier_id'] == $_SESSION['user_id']);
    if (!$isOwner && !$isCarrier) die("Нет доступа");

    $allowed = [
        'new' => ['accepted', 'cancelled'],
        'accepted' => ['pickup', 'cancelled'],
        'pickup' => ['loaded', 'cancelled'],
        'loaded' => ['in_transit'],
        'in_transit' => ['arrived'],
        'arrived' => ['unloaded'],
        'unloaded' => ['completed']
    ];
    if (!isset($allowed[$order['status']]) || !in_array($newStatus, $allowed[$order['status']])) die("Нельзя");
    if ($isOwner && !in_array($newStatus, ['accepted', 'completed', 'cancelled'])) die("Нельзя");
    if ($isCarrier && !in_array($newStatus, ['pickup', 'loaded', 'in_transit', 'arrived', 'unloaded', 'cancelled'])) die("Нельзя");

    if ($isCarrier && $newStatus === 'pickup') {
        $commissionPaid = !empty($order['commission_paid']) || (float)$order['commission_amount'] <= 0;
        if (!$commissionPaid) {
            $_SESSION['error'] = "Нельзя начать перевозку — ожидаем оплату заказа от заказчика";
            header('Location: /orders/view/' . $orderId);
            exit;
        }
    }

    if ($isOwner && $newStatus === 'completed') {
        $_SESSION['error'] = "Завершение сделки происходит через кнопку «Подтвердить выгрузку» в карточке заказа";
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, ?, ?)");
    $stmt->execute([$orderId, $newStatus, $_SESSION['user_id']]);

    if ($isCarrier && $bid) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'status', 'Статус заказа изменён', ?, ?)");
        $stmt->execute([
            (int)$order['user_id'],
            "Перевозчик обновил статус заказа #{$orderId}: «{$newStatus}»",
            "/orders/view/{$orderId}",
        ]);
    } elseif ($isOwner && $bid) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'status', 'Статус заказа изменён', ?, ?)");
        $stmt->execute([
            (int)$bid['carrier_id'],
            "Заказчик обновил статус заказа #{$orderId}: «{$newStatus}»",
            "/orders/view/{$orderId}",
        ]);
    }

    header('Location: /orders/view/' . $orderId . '?status_updated=1'); exit;
}

function uploadScan($orderId) {
    checkAuth(); global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?"); $stmt->execute([$orderId]); $order = $stmt->fetch();
    if (!$order) die("Не найден");
    $stmt = $pdo->prepare("SELECT carrier_id FROM bids WHERE order_id = ? AND status = 'accepted'"); $stmt->execute([$orderId]); $bid = $stmt->fetch();
    if (!$bid || $bid['carrier_id'] != $_SESSION['user_id']) die("Нет доступа");
    if (!isset($_FILES['scan']) || $_FILES['scan']['error'] !== UPLOAD_ERR_OK) { header('Location: /orders/view/' . $orderId . '?upload_error=1'); exit; }
    $file = $_FILES['scan'];
    if (!in_array($file['type'], ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])) die("PDF, JPG, PNG");
    if ($file['size'] > 10 * 1024 * 1024) die("Макс 10 МБ");
    $uploadDir = BASE_PATH . '/public_html/uploads/scans/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'scan_order_' . $orderId . '_' . time() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        $stmt = $pdo->prepare("INSERT INTO documents (order_id, type, file_path) VALUES (?, 'scan', ?)");
        $stmt->execute([$orderId, 'uploads/scans/' . $fileName]);
        header('Location: /orders/view/' . $orderId . '?scan_uploaded=1'); exit;
    } else { die("Не удалось сохранить"); }
}

/**
 * Возвращает последний акцепт оферты пользователя.
 * Используется для вывода блока акцепта в договоре.
 */
function getLastAcceptance(PDO $pdo, int $userId): ?array {
    try {
        $stmt = $pdo->prepare("
            SELECT created_at, ip_address, user_agent, offer_version
            FROM acceptances
            WHERE user_id = ? AND document_type = 'offer'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('[getLastAcceptance] ' . $e->getMessage());
        return null;
    }
}

function viewDocument($orderId, $type) {
    checkAuth(); global $pdo;
    $stmt = $pdo->prepare("
        SELECT o.*, 
               u1.name as o_name, u1.company_name as o_comp, u1.inn as o_inn,
               u1.ogrnip as o_ogrnip, u1.address as o_address, u1.phone as o_phone,
               u2.name as c_name, u2.company_name as c_comp, u2.inn as c_inn,
               u2.ogrnip as c_ogrnip, u2.address as c_address, u2.phone as c_phone,
               u2.nds_rate,
               b.price as bid_price,
               b.carrier_id
        FROM orders o 
        JOIN users u1 ON o.user_id = u1.id 
        JOIN bids b ON o.id = b.order_id AND b.status = 'accepted' 
        JOIN users u2 ON b.carrier_id = u2.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]); $data = $stmt->fetch();
    if (!$data) die("Документ не найден");
    
    $data['price'] = $data['bid_price'] ?? $data['price'];

    // ========== АКЦЕПТЫ ОФЕРТЫ ==========
    // Последний акцепт оферты для owner'а и carrier'а.
    // Используется в шаблоне contract.php (блок «Договор акцептован в электронной форме»).
    $data['owner_acceptance']   = getLastAcceptance($pdo, (int)$data['user_id']);
    $data['carrier_acceptance'] = getLastAcceptance($pdo, (int)$data['carrier_id']);
    
    $docDir = BASE_PATH . '/templates/documents/';
    
    if ($type === 'contract') {
        require $docDir . 'contract.php';
    } elseif ($type === 'waybill') {
        require $docDir . 'waybill.php';
    } elseif ($type === 'act') {
        require $docDir . 'act.php';
    } elseif ($type === 'agent_report') {
        require $docDir . 'agent_report.php';
    } else {
        die("Неизвестный тип документа");
    }
}

function chatView($orderId) { checkAuth(); global $pdo; $stmt = $pdo->prepare("SELECT o.*, u.name as o_name, u.id as o_id FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?"); $stmt->execute([$orderId]); $order = $stmt->fetch(); if (!$order) { header('Location: /orders/my'); exit; } if ($_SESSION['user_id'] == $order['o_id']) { $stmt = $pdo->prepare("SELECT u.id, u.name, u.company_name FROM users u JOIN bids b ON u.id = b.carrier_id WHERE b.order_id = ? AND b.status = 'accepted'"); $stmt->execute([$orderId]); $companion = $stmt->fetch(); } else { $companion = ['id' => $order['o_id'], 'name' => $order['o_name'], 'company_name' => '']; } if (!$companion) die("Собеседник не найден"); $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ? AND is_read = 0"); $stmt->execute([$orderId, $_SESSION['user_id']]); require_once BASE_PATH . '/templates/chat.php'; }

function sendMessage($orderId) { checkAuth(); global $pdo; if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit; $msg = trim($_POST['message'] ?? ''); $receiver = (int)$_POST['receiver_id']; $filePath = null; $fileType = null; if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) { $file = $_FILES['file']; $allowedTypes = ['image/jpeg' => 'image', 'image/png' => 'image', 'image/jpg' => 'image', 'image/gif' => 'image', 'image/webp' => 'image', 'application/pdf' => 'document']; if (isset($allowedTypes[$file['type']]) && $file['size'] <= 20 * 1024 * 1024) { $uploadDir = BASE_PATH . '/public_html/uploads/chat/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); $ext = pathinfo($file['name'], PATHINFO_EXTENSION); $fileName = 'chat_' . $orderId . '_' . time() . '_' . rand(100,999) . '.' . $ext; if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) { $filePath = 'uploads/chat/' . $fileName; $fileType = $allowedTypes[$file['type']]; } } } if (empty($msg) && empty($filePath)) exit; $stmt = $pdo->prepare("INSERT INTO messages (order_id, sender_id, receiver_id, message, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?)"); $stmt->execute([$orderId, $_SESSION['user_id'], $receiver, $msg, $filePath, $fileType]); $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM messages WHERE order_id = ? AND receiver_id = ? AND is_read = 0"); $stmt->execute([$orderId, $receiver]); $unread = $stmt->fetch()['cnt']; if ($unread == 1) { $stmt = $pdo->prepare("SELECT o.title, u.name, u.email FROM orders o JOIN users u ON u.id = ? WHERE o.id = ?"); $stmt->execute([$receiver, $orderId]); $info = $stmt->fetch(); if ($info && !empty($info['email'])) { sendEmail($info['email'], "Новое сообщение на AVL Logistic", "Здравствуйте, {$info['name']}!\n\nУ вас новое сообщение в чате по заказу «{$info['title']}».\n\n" . SITE_URL . "/chat/view/{$orderId}\n\nС уважением,\nAVL Logistic"); } } echo json_encode(['status' => 'ok']); exit; }

function loadMessages($orderId) { checkAuth(); global $pdo; $stmt = $pdo->prepare("SELECT m.*, u.name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.order_id = ? ORDER BY m.id ASC"); $stmt->execute([$orderId]); echo json_encode($stmt->fetchAll()); exit; }

function messagesList() {
    checkAuth();
    global $pdo;
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT o.id, o.title, u.name as companion_name, u.id as companion_id,
               (SELECT COUNT(*) FROM messages m WHERE m.order_id = o.id AND m.receiver_id = ? AND m.is_read = 0) as unread,
               (SELECT message FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_message,
               (SELECT sender_id FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_sender_id,
               (SELECT created_at FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_time
        FROM orders o 
        JOIN bids b ON o.id = b.order_id AND b.status = 'accepted' 
        JOIN users u ON b.carrier_id = u.id 
        WHERE o.user_id = ?
        UNION ALL
        SELECT o.id, o.title, u.name as companion_name, u.id as companion_id,
               (SELECT COUNT(*) FROM messages m WHERE m.order_id = o.id AND m.receiver_id = ? AND m.is_read = 0) as unread,
               (SELECT message FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_message,
               (SELECT sender_id FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_sender_id,
               (SELECT created_at FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_time
        FROM orders o 
        JOIN bids b ON o.id = b.order_id AND b.status = 'accepted' 
        JOIN users u ON o.user_id = u.id 
        WHERE b.carrier_id = ?
        ORDER BY last_time DESC, id DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $allChats = $stmt->fetchAll();
    $chats = [];
    foreach ($allChats as $chat) { if (!isset($chats[$chat['id']])) $chats[$chat['id']] = $chat; }
    $chats = array_values($chats);
    usort($chats, function($a, $b) {
        if ($a['unread'] > 0 && $b['unread'] == 0) return -1;
        if ($b['unread'] > 0 && $a['unread'] == 0) return 1;
        return strtotime($b['last_time'] ?? '2000-01-01') - strtotime($a['last_time'] ?? '2000-01-01');
    });
    require_once BASE_PATH . '/templates/messages_list.php';
}

function createReview($orderId) { checkAuth(); global $pdo; $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'completed'"); $stmt->execute([$orderId]); $order = $stmt->fetch(); if (!$order) die("Заказ не найден"); $isOwner = ($order['user_id'] == $_SESSION['user_id']); $reviewedId = null; if ($isOwner) { $stmt = $pdo->prepare("SELECT carrier_id FROM bids WHERE order_id = ? AND status = 'accepted'"); $stmt->execute([$orderId]); $bid = $stmt->fetch(); $reviewedId = $bid['carrier_id'] ?? null; } else { $reviewedId = $order['user_id']; } if (!$reviewedId) die("Не найден"); $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ? AND reviewer_id = ?"); $stmt->execute([$orderId, $_SESSION['user_id']]); if ($stmt->fetch()) { header('Location: /orders/view/' . $orderId . '?already_reviewed=1'); exit; } if ($_SERVER['REQUEST_METHOD'] === 'POST') { $rating = (int)$_POST['rating']; $comment = trim($_POST['comment'] ?? ''); if ($rating < 1 || $rating > 5) { $error = "Оценка от 1 до 5"; require_once BASE_PATH . '/templates/review_create.php'; return; } $stmt = $pdo->prepare("INSERT INTO reviews (order_id, reviewer_id, reviewed_id, rating, comment) VALUES (?, ?, ?, ?, ?)"); $stmt->execute([$orderId, $_SESSION['user_id'], $reviewedId, $rating, $comment]); $stmt = $pdo->prepare("SELECT AVG(rating) as avg FROM reviews WHERE reviewed_id = ?"); $stmt->execute([$reviewedId]); $stmt = $pdo->prepare("UPDATE users SET rating = ? WHERE id = ?"); $stmt->execute([round($stmt->fetch()['avg'], 1), $reviewedId]); header('Location: /orders/view/' . $orderId . '?reviewed=1'); exit; } require_once BASE_PATH . '/templates/review_create.php'; }

function activateSubscription() { checkAuth(); global $pdo; if ($_SESSION['user_role'] !== 'carrier') { header('Location: /pricing'); exit; } $plan = $_POST['plan'] ?? ''; if (!in_array($plan, ['free', 'basic', 'pro'])) { header('Location: /pricing'); exit; } if ($plan === 'free') { $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active'")->execute([$_SESSION['user_id']]); $pdo->prepare("INSERT INTO subscriptions (user_id, plan, status, expires_at) VALUES (?, 'free', 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH))")->execute([$_SESSION['user_id']]); $_SESSION['success'] = "Бесплатный тариф!"; header('Location: /pricing?activated=1'); exit; } $stmt = $pdo->prepare("SELECT id FROM invoices WHERE user_id = ? AND status = 'pending'"); $stmt->execute([$_SESSION['user_id']]); if ($stmt->fetch()) { $_SESSION['error'] = "Уже есть счёт."; header('Location: /pricing?error=1'); exit; } $prices = ['basic' => 1990, 'pro' => 4990]; $amount = $prices[$plan] ?? 0; $details = PAYMENT_DETAILS . "\n\nТариф «{$plan}» AVL Logistic"; $stmt = $pdo->prepare("INSERT INTO invoices (user_id, plan, amount, status, payment_details) VALUES (?, ?, ?, 'pending', ?)"); $stmt->execute([$_SESSION['user_id'], $plan, $amount, $details]); $_SESSION['success'] = "Счёт на {$amount} ₽!"; header('Location: /pricing?invoiced=1'); exit; }

function userInvoices() { checkAuth(); global $pdo; $invoices = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY id DESC"); $invoices->execute([$_SESSION['user_id']]); $pdo->prepare("UPDATE invoices SET viewed = 1 WHERE user_id = ? AND viewed = 0")->execute([$_SESSION['user_id']]); require_once BASE_PATH . '/templates/invoices.php'; }

function notificationsPage() { checkAuth(); global $pdo; if (!empty($_GET['mark_all'])) { $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$_SESSION['user_id']]); header('Location: /notifications'); exit; } $notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 30"); $notifications->execute([$_SESSION['user_id']]); require_once BASE_PATH . '/templates/notifications.php'; }

function viewProfile() { checkAuth(); global $pdo; $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$_SESSION['user_id']]); $profile = $stmt->fetch(); if ($_SESSION['user_role'] === 'owner') { $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ?"); $stmt->execute([$_SESSION['user_id']]); $totalOrders = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'completed'"); $stmt->execute([$_SESSION['user_id']]); $completedOrders = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE user_id = ? AND status = 'completed'"); $stmt->execute([$_SESSION['user_id']]); $totalSpent = $stmt->fetch()['total']; } else { $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ? AND status = 'accepted'"); $stmt->execute([$_SESSION['user_id']]); $totalDeals = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'"); $stmt->execute([$_SESSION['user_id']]); $completedDeals = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COALESCE(SUM(b.price), 0) as total FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'"); $stmt->execute([$_SESSION['user_id']]); $totalEarned = $stmt->fetch()['total']; } require_once BASE_PATH . '/templates/profile.php'; }

function editProfile() {
    checkAuth();
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $company = trim($_POST['company_name'] ?? '');
        $inn = trim($_POST['inn'] ?? '');
        $ogrnip = trim($_POST['ogrnip'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $kpp = trim($_POST['kpp'] ?? '');
        $bank = trim($_POST['bank_details'] ?? '');
        $ndsRate = trim($_POST['nds_rate'] ?? '20');
        $newPass = $_POST['new_password'] ?? '';

        if (empty($name)) {
            $error = "Имя обязательно";
        } else {
            if (!empty($phone)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ? AND phone != ''");
                $stmt->execute([$phone, $_SESSION['user_id']]);
                if ($stmt->fetch()) { $error = "❌ Этот телефон уже используется"; }
            }
            if (!isset($error) && !empty($inn)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE inn = ? AND id != ? AND inn != ''");
                $stmt->execute([$inn, $_SESSION['user_id']]);
                if ($stmt->fetch()) { $error = "❌ Этот ИНН уже используется"; }
            }
            if (!isset($error)) {
                if (!empty($newPass)) {
                    if (strlen($newPass) < 6) {
                        $error = "Пароль минимум 6 символов";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, company_name=?, inn=?, ogrnip=?, address=?, kpp=?, nds_rate=?, bank_details=?, password=? WHERE id=?");
                        $stmt->execute([$name, $phone, $company, $inn, $ogrnip, $address, $kpp, $ndsRate, $bank, password_hash($newPass, PASSWORD_DEFAULT), $_SESSION['user_id']]);
                        $_SESSION['user_name'] = $name;
                        header('Location: /profile?updated=1'); exit;
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, company_name=?, inn=?, ogrnip=?, address=?, kpp=?, nds_rate=?, bank_details=? WHERE id=?");
                    $stmt->execute([$name, $phone, $company, $inn, $ogrnip, $address, $kpp, $ndsRate, $bank, $_SESSION['user_id']]);
                    $_SESSION['user_name'] = $name;
                    header('Location: /profile?updated=1'); exit;
                }
            }
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    require_once BASE_PATH . '/templates/profile_edit.php';
}

// ============================================================
// АГЕНТСКАЯ СХЕМА
// ============================================================

function confirmUnloadAndCreatePayout($orderId) {
    checkAuth();
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) { header('Location: /orders/my'); exit; }

    if ($order['status'] !== 'unloaded') {
        $_SESSION['error'] = "Подтвердить можно только после статуса «Выгружен»";
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    if (!empty($order['payout_to_carrier_at'])) {
        header('Location: /orders/view/' . $orderId . '?already_paid=1');
        exit;
    }

    if (empty($order['commission_paid']) && (float)$order['commission_amount'] > 0) {
        $_SESSION['error'] = "Сначала оплатите заказ";
        header('Location: /orders/view/' . $orderId);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$orderId]);

        $pdo->prepare("
            INSERT INTO order_status_history (order_id, status, changed_by, comment)
            VALUES (?, 'completed', ?, 'Owner подтвердил выгрузку')
        ")->execute([$orderId, $_SESSION['user_id']]);

        require_once BASE_PATH . '/app/lib/AgentReport.php';
        $ar = new AgentReport($pdo);
        $report = $ar->create($orderId);

        require_once BASE_PATH . '/app/lib/Payouts.php';
        $po = new Payouts($pdo);
        $po->createForOrder($orderId);

        $pdo->commit();

        $_SESSION['success'] = "✅ Выгрузка подтверждена. Отчёт агента " . ($report['report_number'] ?? '') . " сформирован.";
        header('Location: /orders/view/' . $orderId . '?payout_created=1');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[confirmUnloadAndCreatePayout] ' . $e->getMessage());
        $_SESSION['error'] = "Ошибка: " . $e->getMessage();
        header('Location: /orders/view/' . $orderId);
        exit;
    }
}

function agentReportDownload($orderId) {
    checkAuth();
    global $pdo;

    require_once BASE_PATH . '/app/lib/AgentReport.php';
    $ar = new AgentReport($pdo);
    $report = $ar->getByOrder((int)$orderId);

    if (!$report) { header('Location: /orders/view/' . $orderId); exit; }

    $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $ownerId = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $isAdmin = (bool)$stmt->fetchColumn();

    if ($ownerId !== (int)$_SESSION['user_id'] && !$isAdmin) {
        header('Location: /dashboard');
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="agent_report_' . $orderId . '.html"');
    echo $report['content'];
    exit;
}

function adminMarkPayoutPaid() {
    checkAdmin();
    global $pdo;

    header('Content-Type: application/json');

    $payoutId = (int)($_POST['payout_id'] ?? 0);
    $document = trim($_POST['document'] ?? '');

    if (!$payoutId || !$document) {
        echo json_encode(['success' => false, 'error' => 'Не переданы payout_id или document']);
        exit;
    }

    require_once BASE_PATH . '/app/lib/Payouts.php';
    $po = new Payouts($pdo);
    $ok = $po->markPaid($payoutId, (int)$_SESSION['user_id'], $document);

    echo json_encode(['success' => $ok]);
    exit;
}

function adminPayoutsExport() {
    checkAdmin();
    global $pdo;

    $status = $_GET['status'] ?? 'pending';
    require_once BASE_PATH . '/app/lib/Payouts.php';
    $po = new Payouts($pdo);
    $items = $po->list($status, 1000);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="payouts_' . $status . '_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'ID выплаты', 'Заказ', 'Дата заказа', 'Перевозчик', 'ИНН',
        'Сумма к переводу', 'Комиссия', 'Банковские реквизиты', 'Статус'
    ], ';');

    foreach ($items as $p) {
        fputcsv($out, [
            $p['id'],
            '#' . $p['order_id'],
            $p['order_created'],
            $p['carrier_company'] ?: $p['carrier_name'],
            $p['carrier_inn'] ?? '',
            number_format((float)$p['amount'], 2, '.', ''),
            number_format((float)$p['commission'], 2, '.', ''),
            str_replace(["\r\n", "\n"], ' ', $p['carrier_bank'] ?? ''),
            $p['status'],
        ], ';');
    }

    fclose($out);
    exit;
}

// ============================================================
// ТЕХПОДДЕРЖКА
// ============================================================

function listTickets() { checkAuth(); global $pdo; $tickets = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC"); $tickets->execute([$_SESSION['user_id']]); require_once BASE_PATH . '/templates/tickets.php'; }
function createTicket() { checkAuth(); global $pdo; if ($_SERVER['REQUEST_METHOD'] === 'POST') { $subject = trim($_POST['subject']); $message = trim($_POST['message']); $category = trim($_POST['category'] ?? 'Другое'); if (empty($subject) || empty($message)) { $error = "Заполните все поля"; } else { $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, message) VALUES (?, ?, ?)"); $stmt->execute([$_SESSION['user_id'], $subject, $message]); $_SESSION['success'] = "Тикет создан!"; header('Location: /support'); exit; } } require_once BASE_PATH . '/templates/ticket_create.php'; }
function viewTicket($id) { checkAuth(); global $pdo; $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?"); $stmt->execute([$id, $_SESSION['user_id']]); $ticket = $stmt->fetch(); if (!$ticket) { header('Location: /support'); exit; } require_once BASE_PATH . '/templates/ticket_view.php'; }
function replyTicket($id) { checkAuth(); global $pdo; if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /support/view/' . $id); exit; } $message = trim($_POST['message'] ?? ''); if (empty($message)) { header('Location: /support/view/' . $id); exit; } $stmt = $pdo->prepare("SELECT id, subject FROM tickets WHERE id = ? AND user_id = ? AND status = 'open'"); $stmt->execute([$id, $_SESSION['user_id']]); $ticket = $stmt->fetch(); if (!$ticket) { header('Location: /support'); exit; } $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, ?, 0, ?)"); $stmt->execute([$id, $_SESSION['user_id'], $message]); $stmt = $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?"); $stmt->execute([$id]); header('Location: /support/view/' . $id); exit; }
function closeTicket($id) { checkAuth(); global $pdo; $stmt = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ? AND user_id = ? AND status = 'open'"); $stmt->execute([$id, $_SESSION['user_id']]); header('Location: /support/view/' . $id); exit; }

// ========== АДМИН ==========
function checkAdmin() { checkAuth(); global $pdo; $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?"); $stmt->execute([$_SESSION['user_id']]); if (!$stmt->fetch()['is_admin']) { header('Location: /dashboard'); exit; } }
function adminDashboard() { global $pdo; $stats = []; $stats['users'] = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c']; $stats['owners'] = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='owner'")->fetch()['c']; $stats['carriers'] = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='carrier'")->fetch()['c']; $stats['orders'] = $pdo->query("SELECT COUNT(*) as c FROM orders")->fetch()['c']; $stats['completed'] = $pdo->query("SELECT COUNT(*) as c FROM orders WHERE status='completed'")->fetch()['c']; $stats['active'] = $pdo->query("SELECT COUNT(*) as c FROM orders WHERE status NOT IN ('completed','cancelled')")->fetch()['c']; $stats['messages'] = $pdo->query("SELECT COUNT(*) as c FROM messages")->fetch()['c']; $stats['pendingInvoices'] = $pdo->query("SELECT COUNT(*) as c FROM invoices WHERE status='pending'")->fetch()['c']; $latestUsers = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll(); $latestOrders = $pdo->query("SELECT o.*, u.name as owner_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 5")->fetchAll(); require_once BASE_PATH . '/templates/admin/dashboard.php'; }
function adminUsers() { global $pdo; $s = $_GET['search'] ?? ''; $w = "WHERE 1=1"; $p = []; if ($s) { $w .= " AND (name LIKE ? OR email LIKE ?)"; $p[] = "%$s%"; $p[] = "%$s%"; } $users = $pdo->prepare("SELECT * FROM users $w ORDER BY id DESC LIMIT 50"); $users->execute($p); require_once BASE_PATH . '/templates/admin/users.php'; }
function adminViewUser($id) { checkAdmin(); global $pdo; $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$id]); $user = $stmt->fetch(); if (!$user) { header('Location: /admin/users'); exit; } if ($user['role'] == 'owner') { $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 20"); $stmt->execute([$id]); $orders = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ?"); $stmt->execute([$id]); $totalOrders = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'completed'"); $stmt->execute([$id]); $completedOrders = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE user_id = ?"); $stmt->execute([$id]); $totalAmount = $stmt->fetch()['total']; } else { $stmt = $pdo->prepare("SELECT o.*, b.price as bid_price, b.status as bid_status FROM orders o JOIN bids b ON o.id = b.order_id WHERE b.carrier_id = ? ORDER BY o.id DESC LIMIT 20"); $stmt->execute([$id]); $orders = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ?"); $stmt->execute([$id]); $totalBids = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ? AND status = 'accepted'"); $stmt->execute([$id]); $acceptedBids = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COALESCE(SUM(b.price), 0) as total FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'"); $stmt->execute([$id]); $totalEarned = $stmt->fetch()['total']; } $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY started_at DESC LIMIT 5"); $stmt->execute([$id]); $subscriptions = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY id DESC LIMIT 10"); $stmt->execute([$id]); $invoices = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.reviewed_id = ? ORDER BY r.id DESC"); $stmt->execute([$id]); $reviews = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 10"); $stmt->execute([$id]); $tickets = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE ref_by = ?"); $stmt->execute([$id]); $refCount = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT name, email FROM users WHERE ref_by = ? ORDER BY id DESC LIMIT 20"); $stmt->execute([$id]); $refUsers = $stmt->fetchAll(); require_once BASE_PATH . '/templates/admin/user_view.php'; }
function adminOrders() { global $pdo; $orders = $pdo->query("SELECT o.*, u.name as owner_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 50")->fetchAll(); require_once BASE_PATH . '/templates/admin/orders.php'; }
function adminBids() { global $pdo; require_once BASE_PATH . '/templates/admin/bids.php'; }
// ========== НОВОСТИ (АДМИН) ==========

/**
 * Список всех новостей в админке.
 */
function adminNews() {
    global $pdo;
    require_once BASE_PATH . '/templates/admin/news.php';
}

/**
 * Форма создания новости.
 */
function adminNewsCreate() {
    checkAdmin();
    global $pdo;
    $news = null;
    $error = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = saveNews(null);
        if (!$error) {
            $_SESSION['success'] = '✅ Новость создана';
            header('Location: /admin/news');
            exit;
        }
    }
    
    require_once BASE_PATH . '/templates/admin/news_form.php';
}

/**
 * Форма редактирования новости.
 */
function adminNewsEdit($id) {
    checkAdmin();
    global $pdo;
    $id = (int)$id;
    
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$news) {
        $_SESSION['error'] = 'Новость не найдена';
        header('Location: /admin/news');
        exit;
    }
    
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = saveNews($news);
        if (!$error) {
            $_SESSION['success'] = '✅ Новость обновлена';
            header('Location: /admin/news');
            exit;
        }
        // Перечитываем news после POST (чтобы показать в форме)
        $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $news = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    require_once BASE_PATH . '/templates/admin/news_form.php';
}

/**
 * Удаление новости.
 */
function adminNewsDelete($id) {
    checkAdmin();
    global $pdo;
    $id = (int)$id;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /admin/news');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT image_path FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    
    // Удаляем файл картинки, если есть
    if ($img) {
        $fullPath = BASE_PATH . '/public_html/' . $img;
        if (is_file($fullPath)) @unlink($fullPath);
    }
    
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    
    $_SESSION['success'] = '✅ Новость удалена';
    header('Location: /admin/news');
    exit;
}

/**
 * Сохранение новости (создание или обновление).
 * Контент прогоняется через sanitizeNewsHtml() — whitelist тегов.
 * Возвращает строку ошибки или null при успехе.
 */
function saveNews(?array $existing): ?string {
    global $pdo;
    
    $title   = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = sanitizeNewsHtml($_POST['content'] ?? '');
    $status  = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'published';
    $removeImage = !empty($_POST['remove_image']);
    
    if (empty($title))   return 'Заголовок обязателен';
    if (empty(trim(strip_tags($content)))) return 'Текст новости обязателен';
    
    // Обработка обложки
    $imagePath = $existing['image_path'] ?? null;
    
    if ($removeImage && $imagePath) {
        $fullPath = BASE_PATH . '/public_html/' . $imagePath;
        if (is_file($fullPath)) @unlink($fullPath);
        $imagePath = null;
    }
    
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowed)) {
            return 'Только JPG, PNG, WEBP, GIF';
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return 'Максимум 5 МБ';
        }
        
        $uploadDir = BASE_PATH . '/public_html/uploads/news/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = 'news_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            return 'Не удалось сохранить картинку';
        }
        
        if (!empty($existing['image_path'])) {
            $oldFull = BASE_PATH . '/public_html/' . $existing['image_path'];
            if (is_file($oldFull)) @unlink($oldFull);
        }
        
        $imagePath = 'uploads/news/' . $fileName;
    }
    
    $publishedAt = $existing['published_at'] ?? null;
    if ($status === 'published' && !$publishedAt) {
        $publishedAt = date('Y-m-d H:i:s');
    }
    
    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE news SET
                title = ?, excerpt = ?, content = ?, image_path = ?,
                status = ?, published_at = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $title, $excerpt ?: null, $content, $imagePath,
            $status, $publishedAt,
            $existing['id'],
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO news (title, excerpt, content, image_path, author_id, status, published_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $excerpt ?: null, $content, $imagePath,
            $_SESSION['user_id'], $status, $publishedAt,
        ]);
    }
    
    return null;
}

/**
 * Whitelist-фильтр HTML для новостей.
 * Разрешает только безопасные теги. Удаляет on* атрибуты и javascript:.
 */
function sanitizeNewsHtml(string $html): string {
    $html = trim($html);
    if ($html === '') return '';
    
    // Разрешённые теги
    $allowed = '<p><br><h2><h3><h4><ul><ol><li><strong><b><em><i><u><s><strike>'
             . '<blockquote><a><img><hr><span><div>';
    
    // strip_tags убирает всё, кроме разрешённых
    $html = strip_tags($html, $allowed);
    
    // Убираем on* атрибуты (onclick, onerror и т.д.)
    $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    
    // Убираем javascript: в href/src
    $html = preg_replace('/\s+(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $html);
    
    // Убираем data: в href (но оставляем в src для картинок)
    $html = preg_replace('/\s+href\s*=\s*("|\')\s*data:[^"\']*\1/i', '', $html);
    
    // Принудительно target="_blank" rel="noopener" для внешних ссылок
    $html = preg_replace_callback(
        '/<a\s+([^>]*href\s*=\s*"(https?:\/\/[^"]+)"[^>]*)>/i',
        function($m) {
            $attrs = $m[1];
            // Убираем старые target/rel
            $attrs = preg_replace('/\s+(target|rel)\s*=\s*"[^"]*"/i', '', $attrs);
            return '<a ' . $attrs . ' target="_blank" rel="noopener nofollow">';
        },
        $html
    );
    
    return $html;
}

/**
 * Загрузка картинки внутрь текста новости (для Quill).
 * Возвращает JSON { success, url } или { success: false, error }.
 */
function adminNewsUploadImage(): void {
    checkAdmin();
    global $pdo;
    
    header('Content-Type: application/json; charset=utf-8');
    
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
        exit;
    }
    
    $file = $_FILES['image'];
    
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Только JPG, PNG, WEBP, GIF']);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Максимум 5 МБ']);
        exit;
    }
    
    $uploadDir = BASE_PATH . '/public_html/uploads/news/inline/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'inline_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $fullPath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        echo json_encode(['success' => false, 'error' => 'Не удалось сохранить']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'url' => '/uploads/news/inline/' . $fileName,
    ]);
    exit;
}

// ========== НОВОСТИ (ПУБЛИЧНЫЕ) ==========

/**
 * Публичная лента новостей /news
 */
function newsList() {
    global $pdo;
    require_once BASE_PATH . '/templates/news_list.php';
}

/**
 * Публичная карточка новости /news/view/{id}
 */
function newsView($id) {
    global $pdo;
    $id = (int)$id;
    
    // Инкремент просмотров
    $pdo->prepare("UPDATE news SET views_count = views_count + 1 WHERE id = ? AND status = 'published'")
        ->execute([$id]);
    
    $stmt = $pdo->prepare("
        SELECT n.*, u.name AS author_name, u.company_name AS author_company
        FROM news n
        JOIN users u ON u.id = n.author_id
        WHERE n.id = ? AND n.status = 'published'
    ");
    $stmt->execute([$id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$news) {
        header('Location: /news');
        exit;
    }
    
    require_once BASE_PATH . '/templates/news_view.php';
}
function adminViewOrder($id) { checkAdmin(); global $pdo; $stmt = $pdo->prepare("SELECT o.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone, u.company_name as owner_company, u.rating as owner_rating FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?"); $stmt->execute([$id]); $order = $stmt->fetch(); if (!$order) { header('Location: /admin/orders'); exit; } $stmt = $pdo->prepare("SELECT u.*, b.price as bid_price, b.status as bid_status FROM bids b JOIN users u ON b.carrier_id = u.id WHERE b.order_id = ? AND b.status = 'accepted' LIMIT 1"); $stmt->execute([$id]); $carrier = $stmt->fetch(); $stmt = $pdo->prepare("SELECT b.*, u.name, u.company_name, u.rating, u.phone, u.email FROM bids b JOIN users u ON b.carrier_id = u.id WHERE b.order_id = ? ORDER BY b.id DESC"); $stmt->execute([$id]); $bids = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT h.*, u.name, u.role FROM order_status_history h JOIN users u ON h.changed_by = u.id WHERE h.order_id = ? ORDER BY h.id ASC"); $stmt->execute([$id]); $history = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.order_id = ? ORDER BY m.id ASC"); $stmt->execute([$id]); $messages = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT * FROM documents WHERE order_id = ? ORDER BY id DESC"); $stmt->execute([$id]); $documents = $stmt->fetchAll(); $stmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.order_id = ?"); $stmt->execute([$id]); $reviews = $stmt->fetchAll(); require_once BASE_PATH . '/templates/admin/order_view.php'; }

/**
 * Редактирование заказа админом
 * Пересчёт комиссии при изменении price
 */
function adminEditOrder($id) {
    checkAdmin();
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: /admin/orders');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $allowedWindows = ['morning', 'day', 'evening', 'exact'];
        $pickupWindow   = in_array($_POST['pickup_time_window'] ?? '', $allowedWindows) ? $_POST['pickup_time_window'] : null;
        $deliveryWindow = in_array($_POST['delivery_time_window'] ?? '', $allowedWindows) ? $_POST['delivery_time_window'] : null;

        $pickupTimeExact   = ($pickupWindow === 'exact' && !empty($_POST['pickup_time_exact']))     ? $_POST['pickup_time_exact'] : null;
        $deliveryTimeExact = ($deliveryWindow === 'exact' && !empty($_POST['delivery_time_exact'])) ? $_POST['delivery_time_exact'] : null;

        $allowedStatuses = ['new','accepted','pickup','loaded','in_transit','arrived','unloaded','completed','cancelled','hidden'];
        $newStatus = in_array($_POST['status'] ?? '', $allowedStatuses) ? $_POST['status'] : $order['status'];

        $newPrice = (float)$_POST['price'];

        // Пересчёт комиссии
        $newCommissionAmount = (float)$order['commission_amount'];
        $newCarrierPayout    = (float)$order['carrier_payout'];
        $priceChanged        = ($newPrice != (float)$order['price']);

        if ($priceChanged) {
            if (!empty($order['commission_paid'])) {
                $_SESSION['error'] = 'Нельзя менять цену: комиссия уже оплачена.';
                header('Location: /admin/edit_order/' . $id);
                exit;
            }

            $rate = (float)$order['commission_rate'];

            if ($rate <= 0 && !empty($order['carrier_id'])) {
                require_once BASE_PATH . '/app/lib/Commission.php';
                $calc = Commission::calculate($newPrice, (int)$order['carrier_id']);
                $newCommissionAmount = $calc['commission_amount'];
                $newCarrierPayout    = $calc['carrier_payout'];
            } else {
                $newCommissionAmount = round($newPrice * $rate, 2);
                $newCarrierPayout    = round($newPrice - $newCommissionAmount, 2);
            }
        }

        $vehicleType = trim($_POST['vehicle_type'] ?? '') ?: null;
        
        $stmt = $pdo->prepare("
            UPDATE orders SET
                title = ?, cargo_type = ?, vehicle_type = ?, weight = ?, volume = ?,
                pickup_address = ?, pickup_contact_name = ?, pickup_contact_phone = ?,
                delivery_address = ?, delivery_contact_name = ?, delivery_contact_phone = ?,
                pickup_date = ?, pickup_time_window = ?, pickup_time_exact = ?,
                delivery_date = ?, delivery_time_window = ?, delivery_time_exact = ?,
                price = ?,
                commission_amount = ?, carrier_payout = ?,
                requires_pass = ?, unload_on_floor = ?, needs_manipulator = ?, is_fragile = ?,
                special_comment = ?,
                status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['title'],
            $_POST['cargo_type'],
            $vehicleType,
            $_POST['weight'],
            $_POST['volume'],
            $_POST['pickup_address'],
            trim($_POST['pickup_contact_name'] ?? '') ?: null,
            trim($_POST['pickup_contact_phone'] ?? '') ?: null,
            $_POST['delivery_address'],
            trim($_POST['delivery_contact_name'] ?? '') ?: null,
            trim($_POST['delivery_contact_phone'] ?? '') ?: null,
            $_POST['pickup_date'],
            $pickupWindow,
            $pickupTimeExact,
            $_POST['delivery_date'],
            $deliveryWindow,
            $deliveryTimeExact,
            $newPrice,
            $newCommissionAmount,
            $newCarrierPayout,
            !empty($_POST['requires_pass']) ? 1 : 0,
            !empty($_POST['unload_on_floor']) ? 1 : 0,
            !empty($_POST['needs_manipulator']) ? 1 : 0,
            !empty($_POST['is_fragile']) ? 1 : 0,
            trim($_POST['special_comment'] ?? '') ?: null,
            $newStatus,
            $id,
        ]);

        if ($newStatus !== $order['status']) {
            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $newStatus, $_SESSION['user_id'], 'Статус изменён админом']);
        }

        if ($priceChanged && $newCommissionAmount != (float)$order['commission_amount']) {
            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $order['status'],
                $_SESSION['user_id'],
                "Пересчёт: price {$order['price']}→{$newPrice}, комиссия {$order['commission_amount']}→{$newCommissionAmount}, выплата {$order['carrier_payout']}→{$newCarrierPayout}"
            ]);
        }

        $_SESSION['success'] = '✅ Заказ обновлён';
        header('Location: /admin/order/' . $id);
        exit;
    }

    require_once BASE_PATH . '/templates/admin/order_edit.php';
}

function adminInvoices() { global $pdo; $invoices = $pdo->query("SELECT i.*, u.name, u.email FROM invoices i JOIN users u ON i.user_id = u.id ORDER BY i.id DESC")->fetchAll(); require_once BASE_PATH . '/templates/admin/invoices.php'; }
function adminTickets() { global $pdo; $tickets = $pdo->query("SELECT t.*, u.name, u.email FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.status ASC, t.id DESC")->fetchAll(); require_once BASE_PATH . '/templates/admin/tickets.php'; }
function adminReplyTicket($id) { global $pdo; if ($_SERVER['REQUEST_METHOD'] === 'POST') { $reply = trim($_POST['reply']); if (!empty($reply)) { $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, NULL, 1, ?)"); $stmt->execute([$id, $reply]); $pdo->prepare("UPDATE tickets SET admin_reply = ?, status = 'open', updated_at = NOW() WHERE id = ?")->execute([$reply, $id]); $stmt = $pdo->prepare("SELECT user_id, subject FROM tickets WHERE id = ?"); $stmt->execute([$id]); $ticket = $stmt->fetch(); $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'support', 'Новый ответ', ?, '/support')")->execute([$ticket['user_id'], "Ответ на «{$ticket['subject']}»."]); } header('Location: /admin/tickets'); exit; } $stmt = $pdo->prepare("SELECT t.*, u.name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?"); $stmt->execute([$id]); $ticket = $stmt->fetch(); $stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC"); $stmt->execute([$id]); $messages = $stmt->fetchAll(); require_once BASE_PATH . '/templates/admin/ticket_reply.php'; }
function adminCloseTicket($id) { checkAdmin(); global $pdo; $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?")->execute([$id]); header('Location: /admin/tickets'); exit; }
/**
 * Массовая рассылка — форма + обработка POST.
 * Поддерживает 8 сегментов, персонализацию {name}/{company_name}/{email}.
 * Записывает в mailing_log.
 */
function adminMailing() {
    checkAdmin();
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $target  = $_POST['target'] ?? 'all';
        
        $allowedTargets = ['all', 'carriers', 'owners', 'verified', 'active_30', 'paid', 'with_orders', 'with_bids'];
        if (!in_array($target, $allowedTargets)) $target = 'all';
        
        if (empty($subject) || empty($message)) {
            $error = "Заполните тему и сообщение";
        } else {
            $recipients = getMailingRecipients($pdo, $target);
            $adminName = $_SESSION['user_name'] ?? 'Администратор';
            $adminId = (int)$_SESSION['user_id'];
            
            $sent = 0;
            $failed = 0;
            
            foreach ($recipients as $user) {
                // Персонализация
                $personalSubject = str_replace(
                    ['{name}', '{company_name}', '{email}'],
                    [$user['name'], $user['company_name'] ?: $user['name'], $user['email']],
                    $subject
                );
                $personalBody = str_replace(
                    ['{name}', '{company_name}', '{email}'],
                    [$user['name'], $user['company_name'] ?: $user['name'], $user['email']],
                    $message
                );
                
                $fullBody = "Здравствуйте, {$user['name']}!\n\n{$personalBody}\n\nС уважением,\n{$adminName}\nAVL Logistic";
                
                if (sendEmail($user['email'], $personalSubject, $fullBody)) {
                    $sent++;
                } else {
                    $failed++;
                }
                
                // Уведомление в ЛК
                try {
                    $notifTitle = "📢 {$adminName}: {$subject}";
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link)
                        VALUES (?, 'mailing', ?, ?, '/notifications')
                    ");
                    $stmt->execute([$user['id'], $notifTitle, $personalBody]);
                } catch (Throwable $e) {}
            }
            
            // Записываем в историю
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO mailing_log
                        (admin_id, subject, message, target, recipients_count, sent_count, failed_count)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $adminId, $subject, $message, $target,
                    count($recipients), $sent, $failed,
                ]);
            } catch (Throwable $e) {
                error_log('[adminMailing log] ' . $e->getMessage());
            }
            
            $success = "Рассылка поставлена в очередь! Получателей: " . count($recipients) . " (успешно: {$sent}, ошибок: {$failed})";
        }
    }
    
    require_once BASE_PATH . '/templates/admin/mailing.php';
}

/**
 * Возвращает массив получателей для сегмента.
 * @return array<int, array{id:int, name:string, email:string, company_name:string}>
 */
function getMailingRecipients(PDO $pdo, string $target): array {
    switch ($target) {
        case 'carriers':
            $sql = "SELECT id, name, email, company_name FROM users WHERE role = 'carrier'";
            break;
        case 'owners':
            $sql = "SELECT id, name, email, company_name FROM users WHERE role = 'owner'";
            break;
        case 'verified':
            $sql = "SELECT id, name, email, company_name FROM users WHERE email_verified = 1 AND email_invalid = 0";
            break;
        case 'active_30':
            $sql = "SELECT id, name, email, company_name FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'paid':
            $sql = "SELECT DISTINCT u.id, u.name, u.email, u.company_name
                    FROM users u
                    JOIN subscriptions s ON s.user_id = u.id
                    WHERE u.role = 'carrier'
                      AND s.status = 'active'
                      AND s.expires_at > NOW()
                      AND s.plan IN ('basic', 'pro')";
            break;
        case 'with_orders':
            $sql = "SELECT DISTINCT u.id, u.name, u.email, u.company_name
                    FROM users u
                    JOIN orders o ON o.user_id = u.id
                    WHERE u.role = 'owner'";
            break;
        case 'with_bids':
            $sql = "SELECT DISTINCT u.id, u.name, u.email, u.company_name
                    FROM users u
                    JOIN bids b ON b.carrier_id = u.id
                    WHERE u.role = 'carrier'";
            break;
        case 'all':
        default:
            $sql = "SELECT id, name, email, company_name FROM users WHERE email_invalid = 0";
            break;
    }
    
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * AJAX: счётчики получателей по всем сегментам.
 */
function adminMailingCounts(): void {
    checkAdmin();
    global $pdo;
    
    header('Content-Type: application/json; charset=utf-8');
    
    $targets = ['all', 'carriers', 'owners', 'verified', 'active_30', 'paid', 'with_orders', 'with_bids'];
    $counts = [];
    
    foreach ($targets as $t) {
        $counts[$t] = count(getMailingRecipients($pdo, $t));
    }
    
    echo json_encode(['success' => true, 'counts' => $counts]);
    exit;
}

/**
 * AJAX: отправить тестовое письмо себе.
 */
function adminMailingTest(): void {
    checkAdmin();
    global $pdo;
    
    header('Content-Type: application/json; charset=utf-8');
    
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Тема или сообщение пусты']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT name, email, company_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || empty($admin['email'])) {
        echo json_encode(['success' => false, 'error' => 'Email админа не найден']);
        exit;
    }
    
    $personalSubject = "[ТЕСТ] " . str_replace(
        ['{name}', '{company_name}', '{email}'],
        [$admin['name'], $admin['company_name'] ?: $admin['name'], $admin['email']],
        $subject
    );
    $personalBody = str_replace(
        ['{name}', '{company_name}', '{email}'],
        [$admin['name'], $admin['company_name'] ?: $admin['name'], $admin['email']],
        $message
    );
    
    $fullBody = "Здравствуйте, {$admin['name']}!\n\n{$personalBody}\n\n—\nЭто тестовое письмо. Оно не отправлено пользователям.";
    
    $ok = sendEmail($admin['email'], $personalSubject, $fullBody);
    
    echo json_encode(['success' => $ok]);
    exit;
}

/**
 * История рассылок.
 */
function adminMailingHistory(): void {
    checkAdmin();
    global $pdo;
    require_once BASE_PATH . '/templates/admin/mailing_history.php';
}
function adminPayInvoice($id) { global $pdo; $inv = $pdo->prepare("SELECT * FROM invoices WHERE id=? AND status='pending'"); $inv->execute([$id]); $inv = $inv->fetch(); if (!$inv) { header('Location: /admin/invoices'); exit; } $pdo->prepare("UPDATE invoices SET status='paid', paid_at=NOW() WHERE id=?")->execute([$id]); $pdo->prepare("UPDATE subscriptions SET status='expired' WHERE user_id=? AND status='active'")->execute([$inv['user_id']]); $pdo->prepare("INSERT INTO subscriptions (user_id, plan, status, expires_at) VALUES (?,?, 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH))")->execute([$inv['user_id'], $inv['plan']]); header('Location: /admin/invoices?paid=1'); exit; }
function adminCancelInvoice($id) { global $pdo; $pdo->prepare("UPDATE invoices SET status='cancelled' WHERE id=? AND status='pending'")->execute([$id]); header('Location: /admin/invoices?cancelled=1'); exit; }

// ========== РОУТЕР ==========
$url = $_GET['url'] ?? 'home'; $url = rtrim($url, '/'); $parts = explode('/', $url);
$page = $parts[0] ?? 'home'; $action = $parts[1] ?? 'index'; $id = $parts[2] ?? null;

switch ($page) {
    case 'r': if (!empty($action)) { refRegister($action); break; } header('Location: /'); exit;
    case 'register': register(); break;
    case 'login': login(); break;
    case 'logout': logout(); break;
    case 'verify': verifyEmail(); break;
    case 'forgot': forgotPassword(); break;
    case 'reset': resetPassword(); break;
    case 'ref': refPage(); break;
    case 'orders':
        if ($action == 'create') createOrder();
        elseif ($action == 'edit' && $id) editOrder($id);
        elseif ($action == 'view' && $id) viewOrder($id);
        elseif ($action == 'my') myOrders();
        elseif ($action == 'confirm_unload' && $id) confirmUnloadAndCreatePayout($id);
        elseif ($action == 'return') {
            if ($id) {
                returnSearchResults((int)$id);
            } else {
                returnSearchPage();
            }
        }
        elseif ($action == 'return_cancel' && $id) returnSearchCancel((int)$id);
        else listOrders();
        break;
    case 'bids': if ($action == 'place' && $id) placeBid($id); elseif ($action == 'accept') acceptBid(); break;
    case 'status': if ($action == 'update' && $id) updateOrderStatus($id); break;
    case 'upload': if ($action == 'scan' && $id) uploadScan($id); break;
    case 'documents': if ($action == 'view' && $id) viewDocument($id, $_GET['type'] ?? 'contract'); break;
    case 'agent_report':
        if ($action === 'download' && $id) {
            agentReportDownload($id);
        } else {
            if (is_numeric($action)) {
                $orderId = (int)$action;
            } elseif (is_numeric($id)) {
                $orderId = (int)$id;
            } else {
                header('Location: /dashboard');
                exit;
            }
            
            global $pdo;
            $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $ownerId = (int)$stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $isAdmin = (bool)$stmt->fetchColumn();
            
            if (!$ownerId) {
                header('Location: /dashboard');
                exit;
            }
            
            if ($ownerId !== (int)$_SESSION['user_id'] && !$isAdmin) {
                $_SESSION['error'] = 'Отчёт агента доступен только заказчику (грузовладельцу)';
                header('Location: /orders/view/' . $orderId);
                exit;
            }
            
            require_once BASE_PATH . '/templates/agent_report.php';
        }
        break;
    case 'chat': if ($action == 'send' && $id) sendMessage($id); elseif ($action == 'load' && $id) loadMessages($id); elseif ($action == 'view' && $id) chatView($id); break;
    case 'messages': messagesList(); break;
    case 'review': if ($action == 'create' && $id) createReview($id); break;
    case 'subscribe': activateSubscription(); break;
    case 'invoices': userInvoices(); break;
    case 'notifications': notificationsPage(); break;
    case 'analytics': require_once BASE_PATH . '/templates/analytics.php'; break;
    case 'profile': if ($action == 'edit') editProfile(); else viewProfile(); break;
    case 'payment':
        if ($action == 'success') { require_once BASE_PATH . '/templates/payment_success.php'; }
        elseif ($action == 'fail') { require_once BASE_PATH . '/templates/payment_fail.php'; }
        else header('Location: /dashboard');
        break;
    case 'support':
        if ($action == 'create') createTicket();
        elseif ($action == 'view' && $id) viewTicket($id);
        elseif ($action == 'reply' && $id) replyTicket($id);
        elseif ($action == 'close' && $id) closeTicket($id);
        else listTickets(); break;
    case 'api':
        if ($action == 'mark_read') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $orderId = (int)($_GET['order_id'] ?? 0); $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ? AND is_read = 0"); $stmt->execute([$orderId, $_SESSION['user_id']]); echo json_encode(['success' => true]); exit; }
        if ($action == 'orders_list') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $stmt = $pdo->query("SELECT o.*, u.company_name, u.rating FROM orders o JOIN users u ON o.user_id = u.id WHERE o.status = 'new' ORDER BY o.id DESC LIMIT 50"); echo json_encode($stmt->fetchAll()); exit; }
        if ($action == 'my_orders') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $userId = $_SESSION['user_id']; if ($_SESSION['user_role'] === 'owner') { $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC"); } else { $stmt = $pdo->prepare("SELECT o.*, b.status as bid_status, b.price as bid_price FROM orders o JOIN bids b ON o.id = b.order_id WHERE b.carrier_id = ? ORDER BY o.id DESC"); } $stmt->execute([$userId]); echo json_encode($stmt->fetchAll()); exit; }
        if ($action == 'chat_list') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $userId = $_SESSION['user_id']; $stmt = $pdo->prepare("SELECT o.id, o.title, u.name as companion_name, u.id as companion_id, (SELECT COUNT(*) FROM messages m WHERE m.order_id = o.id AND m.receiver_id = ? AND m.is_read = 0) as unread, (SELECT message FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_message, (SELECT sender_id FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_sender_id, (SELECT created_at FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_time FROM orders o JOIN bids b ON o.id = b.order_id AND b.status = 'accepted' JOIN users u ON b.carrier_id = u.id WHERE o.user_id = ? UNION ALL SELECT o.id, o.title, u.name as companion_name, u.id as companion_id, (SELECT COUNT(*) FROM messages m WHERE m.order_id = o.id AND m.receiver_id = ? AND m.is_read = 0) as unread, (SELECT message FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_message, (SELECT sender_id FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_sender_id, (SELECT created_at FROM messages m WHERE m.order_id = o.id ORDER BY m.id DESC LIMIT 1) as last_time FROM orders o JOIN bids b ON o.id = b.order_id AND b.status = 'accepted' JOIN users u ON o.user_id = u.id WHERE b.carrier_id = ? ORDER BY last_time DESC, id DESC"); $stmt->execute([$userId, $userId, $userId, $userId]); $allChats = $stmt->fetchAll(); $chats = []; foreach ($allChats as $chat) { if (!isset($chats[$chat['id']])) $chats[$chat['id']] = $chat; } $chats = array_values($chats); usort($chats, function($a, $b) { if ($a['unread'] > 0 && $b['unread'] == 0) return -1; if ($b['unread'] > 0 && $a['unread'] == 0) return 1; return strtotime($b['last_time'] ?? '2000-01-01') - strtotime($a['last_time'] ?? '2000-01-01'); }); echo json_encode($chats); exit; }
        if ($action == 'profile') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.phone, u.role, u.company_name, u.inn, u.rating, s.plan, s.expires_at FROM users u LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active' AND s.expires_at > NOW() WHERE u.id = ?"); $stmt->execute([$_SESSION['user_id']]); $user = $stmt->fetch(); if ($user) { echo json_encode($user); } else { echo json_encode(['error' => 'User not found']); } exit; }
        if ($action == 'create_order') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $input = json_decode(file_get_contents('php://input'), true); if (!$input) { echo json_encode(['success' => false, 'error' => 'Неверные данные']); exit; } $stmt = $pdo->prepare("INSERT INTO orders (user_id, title, cargo_type, weight, volume, pickup_address, delivery_address, pickup_date, delivery_date, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); $stmt->execute([$_SESSION['user_id'], $input['title'] ?? '', $input['cargo_type'] ?? '', $input['weight'] ?? '0', $input['volume'] ?? '0', $input['pickup_address'] ?? '', $input['delivery_address'] ?? '', $input['pickup_date'] ?? '', $input['delivery_date'] ?? '', $input['price'] ?? '0']); echo json_encode(['success' => true, 'order_id' => $pdo->lastInsertId()]); exit; }
        if ($action == 'counters') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM messages m JOIN orders o ON m.order_id = o.id JOIN bids b ON o.id = b.order_id AND b.status = 'accepted' WHERE m.receiver_id = ? AND m.is_read = 0 AND (o.user_id = ? OR b.carrier_id = ?)"); $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]); $msg = $stmt->fetch()['cnt']; $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0"); $stmt->execute([$_SESSION['user_id']]); $not = $stmt->fetch()['cnt']; echo json_encode(['messages' => $msg, 'notifications' => $not]); exit; }
        if ($action == 'login') { header('Content-Type: application/json'); $email = $_POST['email'] ?? ''; $password = $_POST['password'] ?? ''; global $pdo; $stmt = $pdo->prepare("SELECT id, name, email, role, password FROM users WHERE email = ?"); $stmt->execute([$email]); $user = $stmt->fetch(); if ($user && !empty($password) && password_verify($password, $user['password'])) { $_SESSION['user_id'] = $user['id']; $_SESSION['user_name'] = $user['name']; $_SESSION['user_email'] = $user['email']; $_SESSION['user_role'] = $user['role']; echo json_encode(['success' => true, 'user' => $user]); } else { echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']); } exit; }
        if ($action == 'register') { header('Content-Type: application/json'); $name = $_POST['name'] ?? ''; $email = $_POST['email'] ?? ''; $phone = $_POST['phone'] ?? ''; $password = $_POST['password'] ?? ''; $role = $_POST['role'] ?? 'carrier'; global $pdo; $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?"); $stmt->execute([$email]); if ($stmt->fetch()) { echo json_encode(['success' => false, 'error' => 'Email уже используется']); exit; } $code = md5($email . time() . rand(1000, 9999)); $refCode = strtoupper(substr(md5($email . time()), 0, 8)); $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, verification_code, ref_code) VALUES (?, ?, ?, ?, ?, ?, ?)"); $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $role, $code, $refCode]); $userId = $pdo->lastInsertId(); if ($role === 'carrier') { $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan, status, expires_at) VALUES (?, 'free', 'active', DATE_ADD(NOW(), INTERVAL 30 DAY))"); $stmt->execute([$userId]); } $_SESSION['user_id'] = $userId; $_SESSION['user_name'] = $name; $_SESSION['user_email'] = $email; $_SESSION['user_role'] = $role; echo json_encode(['success' => true, 'user' => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role]]); exit; }
        if ($action == 'latest_orders') { header('Content-Type: application/json'); global $pdo; $stmt = $pdo->query("SELECT o.id, o.title, o.cargo_type, o.weight, o.pickup_address, o.delivery_address, o.price, o.created_at, u.company_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.status = 'new' ORDER BY o.id DESC LIMIT 3"); echo json_encode($stmt->fetchAll()); exit; }
        if ($action == 'order_detail') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $orderId = (int)($_GET['id'] ?? 0); $stmt = $pdo->prepare("SELECT o.*, u.name, u.company_name, u.rating, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?"); $stmt->execute([$orderId]); $order = $stmt->fetch(); if (!$order) { echo json_encode(['error' => 'Not found']); exit; } $carrier = null; $stmt = $pdo->prepare("SELECT u.name, u.company_name, u.phone, u.rating, u.bank_details, b.price, b.status as bid_status FROM bids b JOIN users u ON b.carrier_id = u.id WHERE b.order_id = ? AND b.status = 'accepted'"); $stmt->execute([$orderId]); $carrier = $stmt->fetch(); echo json_encode(['order' => $order, 'carrier' => $carrier ?: null]); exit; }
        if ($action == 'onboarding_done') { header('Content-Type: application/json'); if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Unauthorized']); exit; } global $pdo; $stmt = $pdo->prepare("UPDATE users SET onboarding_done = 1 WHERE id = ?"); $stmt->execute([$_SESSION['user_id']]); echo json_encode(['success' => true]); exit; }
        break;
    case 'page':
        if ($action == 'carriers') require_once BASE_PATH . '/templates/page_carriers.php';
        elseif ($action == 'owners') require_once BASE_PATH . '/templates/page_owners.php';
        elseif ($action == 'terms') require_once BASE_PATH . '/templates/page_terms.php';
        elseif ($action == 'privacy') require_once BASE_PATH . '/templates/page_privacy.php';
        elseif ($action == 'requisites') require_once BASE_PATH . '/templates/page_requisites.php';
        else header('Location: /');
        break;
    case 'admin': checkAdmin();
        if ($action == 'users') adminUsers();
        elseif ($action == 'user' && $id) adminViewUser($id);
        elseif ($action == 'orders') adminOrders();
        elseif ($action == 'bids') adminBids();
        elseif ($action == 'news' && $id === 'create') adminNewsCreate();
        elseif ($action == 'news' && $id === 'upload_image') adminNewsUploadImage();
        elseif ($action == 'news' && $id === 'edit' && !empty($parts[3])) adminNewsEdit($parts[3]);
        elseif ($action == 'news' && $id === 'delete' && !empty($parts[3])) adminNewsDelete($parts[3]);
        elseif ($action == 'news') adminNews();
        elseif ($action == 'order' && $id) adminViewOrder($id);
        elseif ($action == 'edit_order' && $id) adminEditOrder($id);
        elseif ($action == 'invoices') adminInvoices();
        elseif ($action == 'tickets') adminTickets();
        elseif ($action == 'ticket' && $id) adminReplyTicket($id);
        elseif ($action == 'close_ticket' && $id) adminCloseTicket($id);
        elseif ($action == 'mailing' && $id === 'counts') adminMailingCounts();
elseif ($action == 'mailing' && $id === 'test') adminMailingTest();
elseif ($action == 'mailing' && $id === 'history') adminMailingHistory();
elseif ($action == 'mailing') adminMailing();
        elseif ($action == 'pay_invoice' && $id) adminPayInvoice($id);
        elseif ($action == 'cancel_invoice' && $id) adminCancelInvoice($id);
        elseif ($action == 'payouts' && $id === 'mark_paid') adminMarkPayoutPaid();
        elseif ($action == 'payouts' && $id === 'export') adminPayoutsExport();
        elseif ($action == 'payouts') require_once BASE_PATH . '/templates/admin/payouts.php';
        else adminDashboard(); break;
    case 'pricing': require_once BASE_PATH . '/templates/pricing.php'; break;
        case 'news':
        if ($action === 'view' && $id) {
            newsView((int)$id);
        } else {
            newsList();
        }
        break;
    case 'dashboard': require_once BASE_PATH . '/templates/dashboard.php'; break;
    
    default: if (isset($_SESSION['user_id'])) require_once BASE_PATH . '/templates/dashboard.php'; else require_once BASE_PATH . '/templates/landing.php'; break;
}