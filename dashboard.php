<?php
global $pdo;
require_once BASE_PATH . '/templates/header.php';

// === СБОР ДАННЫХ ===
if ($_SESSION['user_role'] === 'owner') {
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalOrders = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'new'");
    $stmt->execute([$userId]);
    $pendingOrders = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status IN ('accepted','pickup','loaded','in_transit','arrived','unloaded')");
    $stmt->execute([$userId]);
    $activeOrders = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedOrders = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids b JOIN orders o ON b.order_id = o.id WHERE o.user_id = ?");
    $stmt->execute([$userId]);
    $totalBids = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids b JOIN orders o ON b.order_id = o.id WHERE o.user_id = ? AND b.status = 'pending' AND b.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute([$userId]);
    $newBids = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $totalSpent = (float)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT o.id, o.title, o.pickup_address, o.delivery_address, o.price,
               COUNT(b.id) as bids_count,
               MIN(b.price) as best_price
        FROM orders o
        LEFT JOIN bids b ON b.order_id = o.id AND b.status = 'pending'
        WHERE o.user_id = ? AND o.status = 'new'
        GROUP BY o.id
        HAVING bids_count > 0
        ORDER BY bids_count DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $ordersWithBids = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status NOT IN ('completed','cancelled') ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $activeOrdersList = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'carrier'");
    $carriersCount = (int)$stmt->fetch()['cnt'];
    
} else {
    // Carrier
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ?");
    $stmt->execute([$userId]);
    $totalBids = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ? AND status = 'accepted'");
    $stmt->execute([$userId]);
    $acceptedBids = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status IN ('pickup','loaded','in_transit','arrived','unloaded')");
    $stmt->execute([$userId]);
    $activeDeliveries = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'");
    $stmt->execute([$userId]);
    $completedDeliveries = (int)$stmt->fetch()['cnt'];
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(b.price), 0) as total FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'");
    $stmt->execute([$userId]);
    $totalEarned = (float)$stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT o.*, b.status as bid_status FROM orders o JOIN bids b ON o.id = b.order_id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status NOT IN ('completed','cancelled') ORDER BY o.created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $activeOrdersList = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT o.*, u.company_name, u.rating FROM orders o JOIN users u ON o.user_id = u.id WHERE o.status = 'new' ORDER BY o.created_at DESC LIMIT 5");
    $stmt->execute();
    $availableOrders = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bids WHERE carrier_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stmt->execute([$userId]);
    $bidsThisMonth = (int)$stmt->fetch()['cnt'];
}

// Статусы
$statusLabels = [
    'new' => 'Новый', 'accepted' => 'Назначен', 'pickup' => 'Погрузка',
    'loaded' => 'Загружен', 'in_transit' => 'В пути', 'arrived' => 'Прибыл',
    'unloaded' => 'Выгружен', 'completed' => 'Завершён', 'cancelled' => 'Отменён',
];
$statusIcons = [
    'new' => '🆕', 'accepted' => '🤝', 'pickup' => '🚛',
    'loaded' => '📦', 'in_transit' => '🛣️', 'arrived' => '📍',
    'unloaded' => '✅', 'completed' => '🎉', 'cancelled' => '❌',
];
$statusStyles = [
    'new'        => ['bg' => 'var(--info-bg)',    'color' => 'var(--info)'],
    'accepted'   => ['bg' => 'var(--warning-bg)', 'color' => 'var(--warning)'],
    'pickup'     => ['bg' => 'var(--warning-bg)', 'color' => 'var(--warning)'],
    'loaded'     => ['bg' => 'var(--warning-bg)', 'color' => 'var(--warning)'],
    'in_transit' => ['bg' => 'var(--info-bg)',    'color' => 'var(--info)'],
    'arrived'    => ['bg' => 'var(--info-bg)',    'color' => 'var(--info)'],
    'unloaded'   => ['bg' => 'var(--success-bg)', 'color' => 'var(--success)'],
    'completed'  => ['bg' => 'var(--success-bg)', 'color' => 'var(--success)'],
    'cancelled'  => ['bg' => 'var(--danger-bg)',  'color' => 'var(--danger)'],
];

// Онбординг
$stmt = $pdo->prepare("SELECT onboarding_done FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userOnboard = $stmt->fetch();
if (!$userOnboard['onboarding_done']) {
    require_once BASE_PATH . '/templates/onboarding.php';
}

// === ПОСЛЕДНИЕ НОВОСТИ (для блока на дашборде) ===
$latestNews = [];
try {
    $stmt = $pdo->query("
        SELECT id, title, excerpt, content, image_path, published_at, created_at
        FROM news
        WHERE status = 'published'
        ORDER BY COALESCE(published_at, created_at) DESC, id DESC
        LIMIT 3
    ");
    $latestNews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $latestNews = [];
}
?>

<style>
    /* Приветствие */
    .dash-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dash-header h1 {
        font-size: 26px;
        font-weight: 900;
        letter-spacing: -1px;
        margin-bottom: 4px;
    }
    .dash-header p {
        color: var(--text-secondary);
        font-size: 14px;
    }
    .dash-date {
        font-family: var(--font-mono);
        font-size: 12px;
        color: var(--text-secondary);
        background: var(--border-light);
        padding: 8px 14px;
    }

    /* Главный CTA */
    .main-cta {
        background: linear-gradient(135deg, var(--accent), var(--accent-hover));
        color: #ffffff;
        padding: 32px 28px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        position: relative;
        overflow: hidden;
    }
    .main-cta::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    .main-cta-content {
        position: relative;
        z-index: 1;
        flex: 1;
        min-width: 240px;
    }
    .main-cta h2 {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 6px;
        color: #ffffff;
    }
    .main-cta p {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 16px;
    }
    .main-cta .btn {
        background: var(--primary);
        color: white;
        padding: 14px 28px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.25s;
        position: relative;
        z-index: 1;
    }
    .main-cta .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(28, 25, 23, 0.3);
    }

    /* Инфо-панель сверху */
    .info-strip {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 14px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 13px;
    }
    .info-strip .item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-secondary);
    }
    .info-strip .item strong {
        color: var(--text);
        font-weight: 800;
    }
    .info-strip .sep {
        color: var(--border);
    }

    /* KPI */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    .kpi-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        transition: all 0.25s;
        position: relative;
        text-decoration: none;
        color: inherit;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(28, 25, 23, 0.08);
        border-color: var(--accent);
    }
    .kpi-card .kpi-icon {
        font-size: 22px;
        margin-bottom: 12px;
        display: block;
    }
    .kpi-card .kpi-value {
        font-size: 28px;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -1px;
        line-height: 1;
        margin-bottom: 6px;
    }
    .kpi-card .kpi-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
    }
    .kpi-card.kpi-alert {
        background: var(--warning-bg);
        border-color: var(--warning);
    }
    .kpi-card.kpi-alert .kpi-value {
        color: #92400e;
        animation: pulseAlert 2s ease-in-out infinite;
    }
    @keyframes pulseAlert {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    /* Сетка */
    .dash-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 20px;
        align-items: start;
    }

    /* Карточки */
    .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        margin-bottom: 16px;
    }
    .panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        gap: 8px;
    }
    .panel-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
    }
    .panel-title strong {
        color: var(--text);
        margin-left: 6px;
        font-size: 14px;
    }
    .panel-link {
        font-size: 12px;
        color: var(--accent);
        font-weight: 700;
        text-decoration: none;
    }

    /* Строка заказа */
    .order-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid var(--border-light);
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
        gap: 12px;
    }
    .order-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .order-row:first-child {
        padding-top: 0;
    }
    .order-row:hover {
        padding-left: 6px;
    }
    .order-row-info { flex: 1; min-width: 0; }
    .order-row-title {
        font-weight: 700;
        font-size: 14px;
        line-height: 1.3;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .order-row-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-secondary);
        flex-wrap: wrap;
    }
    .order-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .order-row-price {
        font-size: 16px;
        font-weight: 900;
        font-family: var(--font-mono);
        color: var(--success);
        white-space: nowrap;
        text-align: right;
    }
    .order-row-price .bids {
        display: block;
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 600;
        font-family: inherit;
        margin-top: 2px;
    }

    /* Пустое */
    .empty-panel {
        text-align: center;
        padding: 32px 20px;
    }
    .empty-panel-icon {
        font-size: 42px;
        opacity: 0.5;
        margin-bottom: 10px;
    }
    .empty-panel h4 {
        font-size: 15px;
        font-weight: 800;
        margin-bottom: 6px;
    }
    .empty-panel p {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 16px;
    }

    /* Требует внимания */
    .attention-item {
        display: flex;
        gap: 10px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-light);
    }
    .attention-item:last-child {
        border-bottom: none;
    }
    .attention-icon {
        width: 32px;
        height: 32px;
        background: var(--warning-bg);
        color: #92400e;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .attention-info {
        flex: 1;
        min-width: 0;
    }
    .attention-info strong {
        font-size: 13px;
        font-weight: 800;
        display: block;
        margin-bottom: 2px;
    }
    .attention-info span {
        font-size: 12px;
        color: var(--text-secondary);
    }

    /* ============ НОВОСТИ ============ */
    .news-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--border);
    }
    .news-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .news-header h2 {
        font-size: 16px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .news-header h2 span { font-size: 20px; }

    .news-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .news-card {
        display: flex;
        flex-direction: column;
        background: var(--surface);
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: all 0.25s;
        overflow: hidden;
        position: relative;
    }
    .news-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(28, 25, 23, 0.08);
        border-color: var(--accent);
    }
    .news-card-cover {
        width: 100%;
        aspect-ratio: 16/9;
        overflow: hidden;
        background: linear-gradient(135deg, var(--primary), #1c1917);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .news-card-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .news-card-cover .placeholder {
        font-size: 42px;
        opacity: 0.5;
    }
    .news-card-body {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
    }
    .news-card-date {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--text-secondary);
    }
    .news-card-title {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.2px;
        line-height: 1.35;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .news-card-excerpt {
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.5;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .news-card-cta {
        font-size: 11px;
        font-weight: 800;
        color: var(--accent);
        margin-top: auto;
        padding-top: 6px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Мобильная */
    @media (max-width: 768px) {
        .kpi-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .kpi-card { padding: 14px 12px; }
        .kpi-card .kpi-value { font-size: 22px; }
        .kpi-card .kpi-icon { font-size: 18px; margin-bottom: 8px; }
        .kpi-card .kpi-label { font-size: 9px; letter-spacing: 1px; }
        .dash-grid { grid-template-columns: 1fr; }
        .main-cta { padding: 24px 20px; }
        .main-cta h2 { font-size: 18px; }
        .order-row-price { font-size: 14px; }
        .news-grid { grid-template-columns: 1fr; gap: 12px; }
        .news-header h2 { font-size: 13px; }
    }
</style>

<!-- Приветствие -->
<div class="dash-header">
    <div>
        <h1>👋 Привет, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'Пользователь')[0]) ?>!</h1>
        <p><?= $_SESSION['user_role'] === 'owner' ? 'Управляйте перевозками эффективно' : 'Находите выгодные заказы и зарабатывайте' ?></p>
    </div>
    <div class="dash-date"><?= date('d.m.Y') ?> · <?= date('H:i') ?></div>
</div>

<!-- Подтверждение email -->
<?php 
$stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$verified = $stmt->fetch()['email_verified'];
$_SESSION['email_verified'] = (bool)$verified;
if (!$verified): 
?>
    <div class="alert" style="background:var(--warning-bg); color:#92400e; border-color:var(--warning);">
        ⚠️ <strong>Подтвердите email!</strong> Письмо отправлено на <strong><?= $_SESSION['user_email'] ?></strong>. Проверьте папку «Спам».
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- OWNER: главный CTA + метрики -->
<!-- ============================================ -->
<?php if ($_SESSION['user_role'] === 'owner'): ?>

    <?php if ($totalOrders === 0): ?>
        <div class="main-cta">
            <div class="main-cta-content">
                <h2>🚀 Создайте первый заказ</h2>
                <p><strong><?= $carriersCount ?> перевозчиков</strong> уже ждут ваших заказов. Первые отклики с ценами — за 10 минут.</p>
            </div>
            <a href="/orders/create" class="btn">📦 Создать заказ бесплатно</a>
        </div>
    <?php elseif ($newBids > 0): ?>
        <div class="main-cta">
            <div class="main-cta-content">
                <h2>📥 <?= $newBids ?> <?= $newBids === 1 ? 'новый отклик' : 'новых отклика' ?></h2>
                <p>Перевозчики предложили цены на ваши заказы. Выберите лучшего.</p>
            </div>
            <a href="/orders/my?tab=pending" class="btn">Посмотреть отклики →</a>
        </div>
    <?php elseif ($activeOrders > 0): ?>
        <div class="info-strip">
            <span class="item">🚛 <strong><?= $activeOrders ?></strong> <?= $activeOrders === 1 ? 'сделка в работе' : 'сделки в работе' ?></span>
            <span class="sep">|</span>
            <span class="item">📦 <strong><?= $pendingOrders ?></strong> ждут откликов</span>
            <span class="sep">|</span>
            <span class="item">📥 Всего откликов: <strong><?= $totalBids ?></strong></span>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <a href="/orders/my?tab=pending" class="kpi-card <?= $pendingOrders > 0 ? '' : 'kpi-empty' ?>">
            <span class="kpi-icon">⏳</span>
            <div class="kpi-value"><?= $pendingOrders ?></div>
            <div class="kpi-label">Ждут откликов</div>
        </a>
        <a href="/orders/my?tab=active" class="kpi-card">
            <span class="kpi-icon">⚡</span>
            <div class="kpi-value"><?= $activeOrders ?></div>
            <div class="kpi-label">В работе</div>
        </a>
        <a href="/orders/my?tab=completed" class="kpi-card">
            <span class="kpi-icon">✅</span>
            <div class="kpi-value"><?= $completedOrders ?></div>
            <div class="kpi-label">Завершено</div>
        </a>
    </div>

    <div class="dash-grid">
        <div>
            <?php if (!empty($ordersWithBids)): ?>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">📥 Требуют внимания <strong><?= count($ordersWithBids) ?></strong></div>
                    <a href="/orders/my?tab=pending" class="panel-link">Все заказы →</a>
                </div>
                <?php foreach ($ordersWithBids as $o): ?>
                    <a href="/orders/view/<?= $o['id'] ?>#bids-section" class="attention-item" style="text-decoration:none; color:inherit;">
                        <div class="attention-icon">📥</div>
                        <div class="attention-info">
                            <strong><?= htmlspecialchars(mb_substr($o['title'], 0, 40)) ?></strong>
                            <span><?= $o['bids_count'] ?> <?= $o['bids_count'] == 1 ? 'отклик' : 'откликов' ?> · от <strong style="color:var(--success);"><?= number_format($o['best_price'], 0, ',', ' ') ?> ₽</strong></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">📋 Последние заказы</div>
                    <a href="/orders/my" class="panel-link">Все →</a>
                </div>
                <?php if (empty($activeOrdersList)): ?>
                    <div class="empty-panel">
                        <div class="empty-panel-icon">📭</div>
                        <h4>Пока нет заказов</h4>
                        <p>Опубликуйте первый — получите отклики с ценами</p>
                        <a href="/orders/create" class="btn btn-primary btn-sm">📦 Создать заказ</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeOrdersList as $o): 
                        $st = $statusStyles[$o['status']] ?? $statusStyles['new'];
                        $stmtB = $pdo->prepare("SELECT COUNT(*) FROM bids WHERE order_id = ? AND status = 'pending'");
                        $stmtB->execute([$o['id']]);
                        $bCount = (int)$stmtB->fetchColumn();
                    ?>
                        <a href="/orders/view/<?= $o['id'] ?>" class="order-row">
                            <div class="order-row-info">
                                <div class="order-row-title"><?= htmlspecialchars($o['title']) ?></div>
                                <div class="order-row-meta">
                                    <span class="order-status-pill" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                        <?= $statusIcons[$o['status']] ?? '📍' ?> <?= $statusLabels[$o['status']] ?? $o['status'] ?>
                                    </span>
                                    <span><?= htmlspecialchars(mb_substr($o['pickup_address'], 0, 20)) ?> → <?= htmlspecialchars(mb_substr($o['delivery_address'], 0, 20)) ?></span>
                                </div>
                            </div>
                            <div class="order-row-price">
                                <?= number_format($o['price'], 0, ',', ' ') ?> ₽
                                <?php if ($bCount > 0): ?>
                                    <span class="bids">📥 <?= $bCount ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="panel">
                <div class="panel-title">Быстрые действия</div>
                <div style="display:flex; flex-direction:column; gap:8px; margin-top:12px;">
                    <a href="/orders/create" class="btn btn-primary" style="width:100%; padding:12px;">📦 Создать заказ</a>
                    <a href="/orders/my" class="btn btn-outline" style="width:100%; padding:12px;">📋 Мои сделки</a>
                    <a href="/messages" class="btn btn-outline" style="width:100%; padding:12px;">💬 Сообщения</a>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">📊 Сводка</div>
                <div style="margin-top:12px;">
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-light); font-size:13px;">
                        <span style="color:var(--text-secondary);">Всего заказов</span>
                        <strong><?= $totalOrders ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-light); font-size:13px;">
                        <span style="color:var(--text-secondary);">Получено откликов</span>
                        <strong style="color:<?= $totalBids > 0 ? 'var(--success)' : 'inherit' ?>;"><?= $totalBids ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-light); font-size:13px;">
                        <span style="color:var(--text-secondary);">Потрачено</span>
                        <strong><?= number_format($totalSpent, 0, ',', ' ') ?> ₽</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; font-size:13px;">
                        <span style="color:var(--text-secondary);">Доступно перевозчиков</span>
                        <strong><?= $carriersCount ?></strong>
                    </div>
                </div>
            </div>

            <?php if ($pendingOrders > 0 && $newBids === 0): ?>
            <div class="panel" style="background:var(--warning-bg); border-color:var(--warning);">
                <div style="display:flex; gap:10px;">
                    <div style="font-size:24px;">💡</div>
                    <div>
                        <div style="font-weight:800; font-size:13px; margin-bottom:4px;">Совет</div>
                        <div style="font-size:12px; color:var(--text-secondary); line-height:1.5;">
                            Чем подробнее описан груз — тем больше откликов. Добавьте фото или уточните требования к машине.
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
<!-- ============================================ -->
<!-- CARRIER -->
<!-- ============================================ -->

    <?php if (!$subscription): ?>
        <div class="sub-alert">
            <div>
                <strong>⚠️ Пробный период Free</strong><br>
                <small>Использовано откликов: <?= $bidsThisMonth ?> из 3 в этом месяце</small>
            </div>
            <a href="/pricing" class="btn btn-accent btn-sm">Оформить подписку</a>
        </div>
    <?php endif; ?>

    <?php if (empty($availableOrders)): ?>
        <div class="main-cta">
            <div class="main-cta-content">
                <h2>📭 Пока нет новых заказов</h2>
                <p>Мы пришлём уведомление, как только грузовладельцы опубликуют заказы. Проверьте позже или настройте подписку на уведомления.</p>
            </div>
            <a href="/orders" class="btn">📋 Обновить список</a>
        </div>
    <?php else: ?>
        <div class="info-strip">
            <span class="item">🆕 <strong><?= count($availableOrders) ?></strong> новых заказов в ленте</span>
            <span class="sep">|</span>
            <span class="item">📋 Всего откликов: <strong><?= $totalBids ?></strong></span>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <a href="/orders/my?tab=active" class="kpi-card">
            <span class="kpi-icon">🚛</span>
            <div class="kpi-value"><?= $activeDeliveries ?></div>
            <div class="kpi-label">В доставке</div>
        </a>
        <a href="/orders/my" class="kpi-card">
            <span class="kpi-icon">🤝</span>
            <div class="kpi-value"><?= $acceptedBids ?></div>
            <div class="kpi-label">Принято заказов</div>
        </a>
        <a href="/profile" class="kpi-card">
            <span class="kpi-icon">💰</span>
            <div class="kpi-value"><?= $totalEarned > 999 ? round($totalEarned / 1000) . 'k' : number_format($totalEarned, 0) ?> ₽</div>
            <div class="kpi-label">Заработано</div>
        </a>
    </div>

    <div class="dash-grid">
        <div>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">🆕 Доступные заказы</div>
                    <a href="/orders" class="panel-link">Все →</a>
                </div>
                <?php if (empty($availableOrders)): ?>
                    <div class="empty-panel">
                        <div class="empty-panel-icon">📭</div>
                        <h4>Заказов пока нет</h4>
                        <p>Новые появятся здесь сразу после публикации</p>
                        <a href="/orders" class="btn btn-outline btn-sm">📋 Обновить</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($availableOrders as $o): ?>
                        <a href="/orders/view/<?= $o['id'] ?>" class="order-row">
                            <div class="order-row-info">
                                <div class="order-row-title"><?= htmlspecialchars(mb_substr($o['title'], 0, 40)) ?></div>
                                <div class="order-row-meta">
                                    <span><?= htmlspecialchars($o['company_name'] ?: 'Заказчик') ?></span>
                                    <span>·</span>
                                    <span><?= htmlspecialchars(mb_substr($o['pickup_address'], 0, 15)) ?> → <?= htmlspecialchars(mb_substr($o['delivery_address'], 0, 15)) ?></span>
                                </div>
                            </div>
                            <div class="order-row-price">
                                <?= number_format($o['price'], 0, ',', ' ') ?> ₽
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($activeOrdersList)): ?>
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">🚛 Мои доставки</div>
                    <a href="/orders/my?tab=active" class="panel-link">Все →</a>
                </div>
                <?php foreach ($activeOrdersList as $o): 
                    $st = $statusStyles[$o['status']] ?? $statusStyles['new'];
                ?>
                    <a href="/orders/view/<?= $o['id'] ?>" class="order-row">
                        <div class="order-row-info">
                            <div class="order-row-title"><?= htmlspecialchars($o['title']) ?></div>
                            <div class="order-row-meta">
                                <span class="order-status-pill" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                    <?= $statusIcons[$o['status']] ?? '📍' ?> <?= $statusLabels[$o['status']] ?? $o['status'] ?>
                                </span>
                                <span><?= htmlspecialchars(mb_substr($o['pickup_address'], 0, 18)) ?> → <?= htmlspecialchars(mb_substr($o['delivery_address'], 0, 18)) ?></span>
                            </div>
                        </div>
                        <div class="order-row-price"><?= number_format($o['price'], 0, ',', ' ') ?> ₽</div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="panel">
                <div class="panel-title">Быстрые действия</div>
                <div style="display:flex; flex-direction:column; gap:8px; margin-top:12px;">
                    <a href="/orders" class="btn btn-primary" style="width:100%; padding:12px;">📋 Найти заказы</a>
                    <a href="/orders/my" class="btn btn-outline" style="width:100%; padding:12px;">🚛 Мои доставки</a>
                    <a href="/profile/edit" class="btn btn-outline" style="width:100%; padding:12px;">💳 Реквизиты</a>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">💎 Подписка</div>
                <div style="margin-top:12px;">
                    <?php if ($subscription): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <span class="badge badge-success">Активна</span>
                                <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">
                                    до <?= date('d.m.Y', strtotime($subscription['expires_at'])) ?>
                                </div>
                            </div>
                            <span style="font-weight:900; text-transform:uppercase; font-size:14px;"><?= $subscription['plan'] ?></span>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-secondary); font-size:13px; margin-bottom:12px; line-height:1.5;">
                            Бесплатный тариф: 3 отклика в месяц (<?= $bidsThisMonth ?> использовано)
                        </p>
                        <a href="/pricing" class="btn btn-accent btn-sm btn-block">Перейти на Basic</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">📊 Сводка</div>
                <div style="margin-top:12px;">
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-light); font-size:13px;">
                        <span style="color:var(--text-secondary);">Всего откликов</span>
                        <strong><?= $totalBids ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-light); font-size:13px;">
                        <span style="color:var(--text-secondary);">Принято заказов</span>
                        <strong><?= $acceptedBids ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0; font-size:13px;">
                        <span style="color:var(--text-secondary);">Завершено</span>
                        <strong style="color:var(--success);"><?= $completedDeliveries ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- ============ НОВОСТИ ============ -->
<?php if (!empty($latestNews)): ?>
<div class="news-section">
    <div class="news-header">
        <h2><span>📰</span> Последние новости</h2>
        <a href="/news" class="btn btn-outline btn-sm">Все новости →</a>
    </div>
    <div class="news-grid">
        <?php foreach ($latestNews as $n): 
            $publishedAt = $n['published_at'] ?: $n['created_at'];
            $excerpt = $n['excerpt'] ?: mb_substr(strip_tags($n['content']), 0, 140) . '…';
        ?>
            <a href="/news/view/<?= $n['id'] ?>" class="news-card">
                <div class="news-card-cover">
                    <?php if (!empty($n['image_path'])): ?>
                        <img src="/<?= htmlspecialchars($n['image_path']) ?>" 
                             alt="<?= htmlspecialchars($n['title']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <span class="placeholder">📰</span>
                    <?php endif; ?>
                </div>
                <div class="news-card-body">
                    <div class="news-card-date">📅 <?= date('d.m.Y', strtotime($publishedAt)) ?></div>
                    <h3 class="news-card-title"><?= htmlspecialchars($n['title']) ?></h3>
                    <p class="news-card-excerpt"><?= htmlspecialchars($excerpt) ?></p>
                    <div class="news-card-cta">Читать →</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>