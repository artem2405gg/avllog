<?php
require_once __DIR__ . '/auth.php';
function createOrder() {
    checkAuth();
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, title, cargo_type, weight, volume, pickup_address, delivery_address, pickup_date, delivery_date, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['cargo_type'],
            $_POST['weight'],
            $_POST['volume'],
            $_POST['pickup_address'],
            $_POST['delivery_address'],
            $_POST['pickup_date'],
            $_POST['delivery_date'],
            $_POST['price']
        ]);
        
        header('Location: /orders/my');
        exit;
    }
    
    require_once __DIR__ . '/../templates/order_create.php';
}

function listOrders() {
    checkAuth();
    global $pdo;
    
    // Поиск и фильтры
    $where = "WHERE status = 'new'";
    $params = [];
    
    if (!empty($_GET['city_from'])) {
        $where .= " AND pickup_address LIKE ?";
        $params[] = '%' . $_GET['city_from'] . '%';
    }
    if (!empty($_GET['city_to'])) {
        $where .= " AND delivery_address LIKE ?";
        $params[] = '%' . $_GET['city_to'] . '%';
    }
    if (!empty($_GET['cargo_type'])) {
        $where .= " AND cargo_type = ?";
        $params[] = $_GET['cargo_type'];
    }
    
    $stmt = $pdo->prepare("SELECT o.*, u.company_name, u.rating FROM orders o JOIN users u ON o.user_id = u.id $where ORDER BY o.created_at DESC LIMIT 20");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    require_once __DIR__ . '/../templates/orders_list.php';
}

function viewOrder($id) {
    checkAuth();
    global $pdo;
    
    // Заказ с информацией о грузовладельце
    $stmt = $pdo->prepare("SELECT o.*, u.name, u.company_name, u.rating, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: /orders');
        exit;
    }
    
    // Отклики по этому заказу
    $stmt = $pdo->prepare("SELECT b.*, u.company_name, u.rating FROM bids b JOIN users u ON b.carrier_id = u.id WHERE b.order_id = ? ORDER BY b.price ASC");
    $stmt->execute([$id]);
    $bids = $stmt->fetchAll();
    
    // Проверяем, делал ли текущий перевозчик отклик
    $myBid = null;
    if ($_SESSION['user_role'] === 'carrier') {
        $stmt = $pdo->prepare("SELECT * FROM bids WHERE order_id = ? AND carrier_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $myBid = $stmt->fetch();
    }
    
    require_once __DIR__ . '/../templates/order_view.php';
}

function myOrders() {
    checkAuth();
    global $pdo;
    
    if ($_SESSION['user_role'] === 'owner') {
        // Заказы, которые создал я
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    } else {
        // Заказы, на которые я откликнулся
        $stmt = $pdo->prepare("SELECT o.*, b.status as bid_status FROM orders o JOIN bids b ON o.id = b.order_id WHERE b.carrier_id = ? ORDER BY b.created_at DESC");
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
    
    require_once __DIR__ . '/../templates/my_orders.php';
}