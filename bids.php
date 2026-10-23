<?php
require_once __DIR__ . '/auth.php';

function placeBid($orderId) {
    checkAuth();
    global $pdo;
    
    if ($_SESSION['user_role'] !== 'carrier') {
        header('Location: /orders');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
    $stmt->execute([$_SESSION['user_id']]);
    $subscription = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM one_time_purchases WHERE user_id = ? AND order_id = ? AND status = 'paid'");
    $stmt->execute([$_SESSION['user_id'], $orderId]);
    $oneTime = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ? AND MONTH(created_at) = MONTH(NOW())");
    $stmt->execute([$_SESSION['user_id']]);
    $bidsCount = $stmt->fetch()['cnt'];
    
    if (!$subscription && !$oneTime && $bidsCount >= 3) {
        header('Location: /pricing');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("INSERT INTO bids (order_id, carrier_id, price) VALUES (?, ?, ?)");
        $stmt->execute([$orderId, $_SESSION['user_id'], $_POST['price']]);
        header("Location: /orders/view/$orderId");
        exit;
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
        
        if (!$bid) {
            die("Отклик не найден");
        }
        
        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$bid['order_id']]);
        $order = $stmt->fetch();
        
        if ($order['user_id'] != $_SESSION['user_id']) {
            die("Нет доступа");
        }
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE bids SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$bidId]);
        
        $stmt = $pdo->prepare("UPDATE bids SET status = 'rejected' WHERE order_id = ? AND id != ?");
        $stmt->execute([$bid['order_id'], $bidId]);
        
        $stmt = $pdo->prepare("UPDATE orders SET status = 'in_progress' WHERE id = ?");
        $stmt->execute([$bid['order_id']]);
        $pdo->commit();
        
        header("Location: /orders/view/" . $bid['order_id']);
        exit;
    }
}