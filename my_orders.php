<?php require_once BASE_PATH . '/templates/header.php';

global $pdo;

// Для owner'ов — счётчики по статусам
$statusCounts = [];
$totalCount = 0;
if ($_SESSION['user_role'] === 'owner') {
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt 
        FROM orders 
        WHERE user_id = ? 
        GROUP BY status
    ");
    $stmt->execute([$_SESSION['user_id']]);
    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int)$row['cnt'];
        $totalCount += (int)$row['cnt'];
    }
}

// Табы
$tab = $_GET['tab'] ?? 'active';
$validTabs = ['active', 'pending', 'completed', 'all'];
if (!in_array($tab, $validTabs)) $tab = 'active';

// Фильтр по табу
$filteredOrders = [];
foreach ($orders as $o) {
    $match = false;
    switch ($tab) {
        case 'active':
            $match = in_array($o['status'], ['accepted', 'pickup', 'loaded', 'in_transit', 'arrived', 'unloaded']);
            break;
        case 'pending':
            $match = ($o['status'] === 'new');
            break;
        case 'completed':
            $match = in_array($o['status'], ['completed', 'cancelled']);
            break;
        case 'all':
            $match = true;
            break;
    }
    if ($match) $filteredOrders[] = $o;
}

// Стили для статусов
$statusStyles = [
    'new'        => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Новый',         'dot' => '#3b82f6'],
    'accepted'   => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Принят',        'dot' => '#f59e0b'],
    'pickup'     => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'На погрузке',  'dot' => '#f59e0b'],
    'loaded'     => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Погружен',     'dot' => '#f59e0b'],
    'in_transit' => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'label' => 'В пути',       'dot' => '#4f46e5'],
    'arrived'    => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'label' => 'Прибыл',       'dot' => '#4f46e5'],
    'unloaded'   => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Выгружен',     'dot' => '#10b981'],
    'completed'  => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Завершён',     'dot' => '#10b981'],
    'cancelled'  => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Отменён',      'dot' => '#ef4444'],
];
?>

<style>
    /* Табы */
    .deals-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 24px;
        border-bottom: 2px solid var(--border);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .deals-tab {
        padding: 14px 20px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-secondary);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .deals-tab:hover { color: var(--text); }
    .deals-tab.active {
        color: var(--text);
        border-bottom-color: var(--accent);
    }
    .deals-tab .count {
        background: var(--border-light);
        color: var(--text-secondary);
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 800;
        border-radius: 10px;
        min-width: 22px;
        text-align: center;
    }
    .deals-tab.active .count {
        background: var(--accent);
        color: var(--primary);
    }
    
    /* Карточка сделки */
    .deal-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        margin-bottom: 12px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
        align-items: center;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }
    .deal-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--accent);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .deal-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .deal-card:hover::before { opacity: 1; }
    
    /* Левая часть */
    .deal-left { min-width: 0; }
    .deal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .deal-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        color: var(--text);
        letter-spacing: -0.3px;
    }
    .deal-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .deal-status .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Маршрут */
    .deal-route {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        font-size: 14px;
        color: var(--text);
        flex-wrap: wrap;
    }
    .deal-route .city {
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .deal-route .arrow {
        color: var(--text-secondary);
        font-size: 16px;
        line-height: 1;
    }
    
    /* Мета */
    .deal-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        font-size: 12px;
        color: var(--text-secondary);
    }
    .deal-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .deal-meta .value {
        color: var(--text);
        font-weight: 600;
    }
    
    /* Правый блок */
    .deal-right {
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
        min-width: 200px;
    }
    .deal-price-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        font-weight: 700;
    }
    .deal-price {
        font-size: 22px;
        font-weight: 900;
        color: var(--success);
        font-family: var(--font-mono);
        letter-spacing: -1px;
        line-height: 1;
    }
    .deal-actions {
        display: flex;
        gap: 8px;
        margin-top: 4px;
    }
    .deal-actions .btn {
        padding: 10px 16px;
        font-size: 12px;
        white-space: nowrap;
    }
    
    /* Бейдж с количеством откликов */
    .bid-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        border-radius: 4px;
        transition: all 0.2s;
    }
    .bid-badge.has-bids {
        background: var(--accent);
        color: var(--primary);
        animation: bounce 2s ease-in-out infinite;
    }
    .bid-badge.no-bids {
        background: var(--border-light);
        color: var(--text-secondary);
    }
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* Пустое состояние */
    .empty-state {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state .icon {
        font-size: 64px;
        opacity: 0.4;
        margin-bottom: 16px;
    }
    .empty-state h3 {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .empty-state p {
        color: var(--text-secondary);
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    /* Мобильная версия */
    @media (max-width: 768px) {
        .deal-card {
            grid-template-columns: 1fr;
            gap: 14px;
            padding: 16px;
        }
        .deal-right {
            min-width: 0;
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border-light);
        }
        .deal-price { font-size: 20px; }
        .deal-actions { flex-direction: row; }
        .deal-actions .btn { padding: 8px 14px; }
    }
</style>

<h2 style="margin-bottom: 8px;">📦 Мои сделки</h2>
<p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 24px;">
    <?= $_SESSION['user_role'] === 'owner' ? 'Заказы, которые вы создали' : 'Заказы, на которые вы откликнулись' ?>
</p>

<!-- Табы -->
<?php if ($_SESSION['user_role'] === 'owner'): ?>
<div class="deals-tabs">
    <?php
    $activeCount = 0;
    foreach (['accepted', 'pickup', 'loaded', 'in_transit', 'arrived', 'unloaded'] as $st) {
        $activeCount += $statusCounts[$st] ?? 0;
    }
    $pendingCount = $statusCounts['new'] ?? 0;
    $completedCount = ($statusCounts['completed'] ?? 0) + ($statusCounts['cancelled'] ?? 0);
    ?>
    <a href="/orders/my?tab=active" class="deals-tab <?= $tab === 'active' ? 'active' : '' ?>">
        ⚡ В работе <span class="count"><?= $activeCount ?></span>
    </a>
    <a href="/orders/my?tab=pending" class="deals-tab <?= $tab === 'pending' ? 'active' : '' ?>">
        ⏳ Ждут откликов <span class="count"><?= $pendingCount ?></span>
    </a>
    <a href="/orders/my?tab=completed" class="deals-tab <?= $tab === 'completed' ? 'active' : '' ?>">
        ✅ Завершённые <span class="count"><?= $completedCount ?></span>
    </a>
    <a href="/orders/my?tab=all" class="deals-tab <?= $tab === 'all' ? 'active' : '' ?>">
        📋 Все <span class="count"><?= $totalCount ?></span>
    </a>
</div>
<?php endif; ?>

<?php if (empty($filteredOrders)): ?>
    <!-- Пустое состояние -->
    <div class="empty-state">
        <div class="icon">
            <?php
            if ($tab === 'active') echo '⚡';
            elseif ($tab === 'pending') echo '⏳';
            elseif ($tab === 'completed') echo '✅';
            else echo '📦';
            ?>
        </div>
        <?php if ($tab === 'active'): ?>
            <h3>Нет активных сделок</h3>
            <p>Здесь появятся заказы, по которым уже выбран перевозчик</p>
            <a href="/orders/my?tab=pending" class="btn btn-outline">⏳ Ждут откликов (<?= $pendingCount ?>)</a>
        <?php elseif ($tab === 'pending'): ?>
            <h3>Нет заказов, ожидающих откликов</h3>
            <p>Все ваши заказы уже приняты или завершены</p>
            <a href="/orders/create" class="btn btn-primary">📦 Создать новый заказ</a>
        <?php elseif ($tab === 'completed'): ?>
            <h3>Нет завершённых сделок</h3>
            <p>Здесь будут ваши закрытые заказы и отменённые</p>
            <a href="/orders/my?tab=active" class="btn btn-outline">⚡ К активным</a>
        <?php else: ?>
            <h3>У вас пока нет заказов</h3>
            <p>Создайте первый — перевозчики откликнутся с ценами</p>
            <a href="/orders/create" class="btn btn-primary">🚀 Создать заказ бесплатно</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($filteredOrders as $order):
        $st = $statusStyles[$order['status']] ?? $statusStyles['new'];
        
        // Считаем отклики по этому заказу
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $bidsCount = (int)$stmt->fetch()['cnt'];
        
        // Для carrier'а — свой отклик
        $myBidStatus = null;
        if ($_SESSION['user_role'] === 'carrier') {
            $myBidStatus = $order['bid_status'] ?? null;
        }
    ?>
        <div class="deal-card">
            <div class="deal-left">
                <!-- Заголовок + статус -->
                <div class="deal-header">
                    <h3 class="deal-title"><?= htmlspecialchars($order['title']) ?></h3>
                    <span class="deal-status" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>;">
                        <span class="dot" style="background: <?= $st['dot'] ?>;"></span>
                        <?= $st['label'] ?>
                    </span>
                </div>
                
                <!-- Маршрут -->
                <div class="deal-route">
                    <span class="city">📍 <?= htmlspecialchars(explode(',', $order['pickup_address'])[0]) ?></span>
                    <span class="arrow">→</span>
                    <span class="city">🏁 <?= htmlspecialchars(explode(',', $order['delivery_address'])[0]) ?></span>
                </div>
                
                <!-- Мета -->
                <div class="deal-meta">
                    <span>📦 <span class="value"><?= htmlspecialchars($order['cargo_type']) ?></span></span>
                    <span>⚖️ <span class="value"><?= $order['weight'] ?> т</span></span>
                    <?php if (!empty($order['pickup_date'])): ?>
                        <span>📅 <span class="value"><?= date('d.m.Y', strtotime($order['pickup_date'])) ?></span></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Правая часть -->
            <div class="deal-right">
                <div>
                    <div class="deal-price-label"><?= $order['status'] === 'new' ? 'Бюджет' : 'Стоимость' ?></div>
                    <div class="deal-price"><?= number_format((float)$order['price'], 0, ',', ' ') ?> ₽</div>
                </div>
                
                <div class="deal-actions">
                    <?php if ($_SESSION['user_role'] === 'owner' && $order['status'] === 'new'): ?>
                        <!-- Owner, заказ ждёт откликов -->
                        <?php if ($bidsCount > 0): ?>
                            <a href="/orders/view/<?= $order['id'] ?>#bids-section" class="bid-badge has-bids">
                                📥 <?= $bidsCount ?> <?= $bidsCount === 1 ? 'отклик' : ($bidsCount < 5 ? 'отклика' : 'откликов') ?>
                            </a>
                        <?php else: ?>
                            <span class="bid-badge no-bids">⏳ Ждём отклики</span>
                        <?php endif; ?>
                        <a href="/orders/view/<?= $order['id'] ?>" class="btn btn-outline">Открыть</a>
                    <?php elseif ($_SESSION['user_role'] === 'owner'): ?>
                        <a href="/orders/view/<?= $order['id'] ?>" class="btn btn-primary">Открыть сделку →</a>
                    <?php else: ?>
                        <!-- Carrier -->
                        <?php if ($myBidStatus === 'accepted'): ?>
                            <a href="/orders/view/<?= $order['id'] ?>" class="btn btn-success">⚡ Открыть сделку</a>
                        <?php elseif ($myBidStatus === 'pending'): ?>
                            <a href="/orders/view/<?= $order['id'] ?>" class="btn btn-outline">⏳ Ваш отклик ждёт</a>
                        <?php else: ?>
                            <a href="/orders/view/<?= $order['id'] ?>" class="btn btn-outline">Открыть</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>