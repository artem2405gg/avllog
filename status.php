<?php
function updateOrderStatus($orderId) {
    checkAuth();
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /orders/view/' . $orderId);
        exit;
    }
    
    $newStatus = $_POST['status'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: /orders/my');
        exit;
    }
    
    $isOwner = ($order['user_id'] == $_SESSION['user_id']);
    
    $stmt = $pdo->prepare("SELECT carrier_id FROM bids WHERE order_id = ? AND status = 'accepted'");
    $stmt->execute([$orderId]);
    $bid = $stmt->fetch();
    $isCarrier = ($bid && $bid['carrier_id'] == $_SESSION['user_id']);
    
    if (!$isOwner && !$isCarrier) {
        die("Нет доступа");
    }
    
    $allowedTransitions = [
        'new' => ['accepted', 'cancelled'],
        'accepted' => ['pickup', 'cancelled'],
        'pickup' => ['loaded', 'cancelled'],
        'loaded' => ['in_transit'],
        'in_transit' => ['arrived'],
        'arrived' => ['unloaded'],
        'unloaded' => ['completed'],
    ];
    
    if (!isset($allowedTransitions[$order['status']]) || !in_array($newStatus, $allowedTransitions[$order['status']])) {
        die("Нельзя переключить с «{$order['status']}» на «{$newStatus}»");
    }
    
    if ($isOwner && !in_array($newStatus, ['accepted', 'completed', 'cancelled'])) {
        die("Грузовладелец не может установить этот статус");
    }
    
    if ($isCarrier && !in_array($newStatus, ['pickup', 'loaded', 'in_transit', 'arrived', 'unloaded', 'cancelled'])) {
        die("Перевозчик не может установить этот статус");
    }
    
    // Обновляем статус заказа
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    // Записываем в историю
    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, ?, ?)");
    $stmt->execute([$orderId, $newStatus, $_SESSION['user_id']]);
    
    header('Location: /orders/view/' . $orderId . '?status_updated=1');
    exit;
}