<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

$userId = $_SESSION['user_id'];

// Фильтр
$tab = $_GET['tab'] ?? 'all';
if (!in_array($tab, ['all', 'unread', 'bids', 'orders', 'chats', 'support'])) $tab = 'all';

// Счётчики
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
    SUM(CASE WHEN type IN ('bid','bid_accepted') THEN 1 ELSE 0 END) as bids_count,
    SUM(CASE WHEN type IN ('order','payment','subscription','invoice') THEN 1 ELSE 0 END) as orders_count,
    SUM(CASE WHEN type = 'message' THEN 1 ELSE 0 END) as chats_count,
    SUM(CASE WHEN type IN ('support','mailing') THEN 1 ELSE 0 END) as support_count
    FROM notifications WHERE user_id = ?");
$stmt->execute([$userId]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
foreach ($counts as $k => $v) $counts[$k] = (int)$v;

// Фильтр SQL
$where = "";
switch ($tab) {
    case 'unread':  $where = "AND is_read = 0"; break;
    case 'bids':    $where = "AND type IN ('bid','bid_accepted')"; break;
    case 'orders':  $where = "AND type IN ('order','payment','subscription','invoice')"; break;
    case 'chats':   $where = "AND type = 'message'"; break;
    case 'support': $where = "AND type IN ('support','mailing')"; break;
}

$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? $where
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Группировка по датам
$today = []; $yesterday = []; $older = [];
$todayDate = date('Y-m-d');
$yesterdayDate = date('Y-m-d', strtotime('-1 day'));

foreach ($notifications as $n) {
    $nDate = date('Y-m-d', strtotime($n['created_at']));
    if ($nDate === $todayDate) $today[] = $n;
    elseif ($nDate === $yesterdayDate) $yesterday[] = $n;
    else $older[] = $n;
}

// Иконки и стили типов
$typeStyles = [
    'bid'          => ['icon' => '📥', 'bg' => '#eef2ff', 'color' => '#4361ee', 'label' => 'Отклик'],
    'bid_accepted' => ['icon' => '🤝', 'bg' => '#d1fae5', 'color' => '#059669', 'label' => 'Вас выбрали'],
    'order'        => ['icon' => '📦', 'bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Заказ'],
    'message'      => ['icon' => '💬', 'bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Чат'],
    'support'      => ['icon' => '🎧', 'bg' => '#fce7f3', 'color' => '#be185d', 'label' => 'Поддержка'],
    'mailing'      => ['icon' => '📢', 'bg' => '#fff7ed', 'color' => '#ea580c', 'label' => 'Рассылка'],
    'ref'          => ['icon' => '🎁', 'bg' => '#f0fdf4', 'color' => '#166534', 'label' => 'Реферал'],
    'subscription' => ['icon' => '💎', 'bg' => '#ede9fe', 'color' => '#6d28d9', 'label' => 'Подписка'],
    'payment'      => ['icon' => '💰', 'bg' => '#d1fae5', 'color' => '#059669', 'label' => 'Оплата'],
    'invoice'      => ['icon' => '🧾', 'bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Счёт'],
    'new_order'    => ['icon' => '🚛', 'bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Новый заказ'],
    'hourly_orders'=> ['icon' => '⏰', 'bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Напоминание'],
];
?>

<style>
    /* Header */
    .notif-header-block {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 22px 24px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .notif-header-block h1 {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }
    .notif-header-block p {
        color: var(--text-secondary);
        font-size: 13px;
    }

    /* Табы */
    .notif-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 16px;
        border-bottom: 2px solid var(--border);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .notif-tab {
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-secondary);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .notif-tab:hover { color: var(--text); }
    .notif-tab.active {
        color: var(--text);
        border-bottom-color: var(--accent);
    }
    .notif-tab .count {
        background: var(--border-light);
        color: var(--text-secondary);
        padding: 2px 7px;
        font-size: 10px;
        font-weight: 800;
        border-radius: 10px;
    }
    .notif-tab.active .count {
        background: var(--accent);
        color: var(--primary);
    }
    .notif-tab .count.alert {
        background: #ef4444;
        color: white;
    }

    /* Группа */
    .notif-group {
        margin-bottom: 20px;
    }
    .notif-group-title {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 10px;
        padding-left: 4px;
    }

    /* Карточка */
    .notif-item {
        background: var(--surface);
        border: 1px solid var(--border);
        margin-bottom: 8px;
        display: flex;
        gap: 14px;
        padding: 16px;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }
    .notif-item:hover {
        border-color: var(--accent);
        transform: translateX(2px);
    }
    .notif-item.unread {
        background: linear-gradient(90deg, #fffbeb, var(--surface) 30%);
    }
    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--accent);
    }

    .notif-item-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        border-radius: 50%;
    }

    .notif-item-body {
        flex: 1;
        min-width: 0;
    }

    .notif-item-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 4px;
    }

    .notif-item-title {
        font-size: 14px;
        font-weight: 800;
        line-height: 1.3;
        letter-spacing: -0.2px;
    }
    .notif-item.unread .notif-item-title {
        color: var(--text);
    }
    .notif-item-title:not(.unread) {
        color: var(--text-secondary);
        font-weight: 700;
    }

    .notif-item-time {
        font-size: 11px;
        color: var(--text-secondary);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .notif-item-message {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.5;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notif-item-footer {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .notif-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 10px;
    }

    .notif-arrow {
        color: var(--text-secondary);
        font-size: 16px;
        opacity: 0.5;
        margin-left: auto;
    }

    /* Пусто */
    .notif-empty {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 60px 20px;
        text-align: center;
    }
    .notif-empty .icon {
        font-size: 64px;
        opacity: 0.4;
        margin-bottom: 16px;
    }
    .notif-empty h3 {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .notif-empty p {
        color: var(--text-secondary);
        font-size: 14px;
        margin-bottom: 20px;
    }

    /* Мобильная */
    @media (max-width: 768px) {
        .notif-header-block { padding: 18px; }
        .notif-header-block h1 { font-size: 18px; }
        .notif-item { padding: 12px; gap: 10px; }
        .notif-item-icon { width: 36px; height: 36px; font-size: 18px; }
        .notif-item-title { font-size: 13px; }
        .notif-item-message { font-size: 12px; }
    }
</style>

<!-- Хлебные крошки -->
<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/dashboard" style="color:var(--text-secondary); text-decoration:none;">📊 Панель</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Уведомления</span>
</div>

<!-- Header -->
<div class="notif-header-block">
    <div>
        <h1>🔔 Уведомления</h1>
        <p>Все события платформы в одном месте</p>
    </div>
    <?php if ($counts['unread_count'] > 0): ?>
        <a href="/notifications?mark_all=1" class="btn btn-outline btn-sm">✓ Прочитать все</a>
    <?php endif; ?>
</div>

<!-- Табы -->
<?php if ($counts['total'] > 0): ?>
<div class="notif-tabs">
    <a href="/notifications?tab=all" class="notif-tab <?= $tab === 'all' ? 'active' : '' ?>">
        Все <span class="count"><?= $counts['total'] ?></span>
    </a>
    <?php if ($counts['unread_count'] > 0): ?>
        <a href="/notifications?tab=unread" class="notif-tab <?= $tab === 'unread' ? 'active' : '' ?>">
            Непрочитанные <span class="count alert"><?= $counts['unread_count'] ?></span>
        </a>
    <?php endif; ?>
    <?php if ($counts['bids_count'] > 0): ?>
        <a href="/notifications?tab=bids" class="notif-tab <?= $tab === 'bids' ? 'active' : '' ?>">
            📥 Отклики <span class="count"><?= $counts['bids_count'] ?></span>
        </a>
    <?php endif; ?>
    <?php if ($counts['orders_count'] > 0): ?>
        <a href="/notifications?tab=orders" class="notif-tab <?= $tab === 'orders' ? 'active' : '' ?>">
            📦 Заказы <span class="count"><?= $counts['orders_count'] ?></span>
        </a>
    <?php endif; ?>
    <?php if ($counts['chats_count'] > 0): ?>
        <a href="/notifications?tab=chats" class="notif-tab <?= $tab === 'chats' ? 'active' : '' ?>">
            💬 Чаты <span class="count"><?= $counts['chats_count'] ?></span>
        </a>
    <?php endif; ?>
    <?php if ($counts['support_count'] > 0): ?>
        <a href="/notifications?tab=support" class="notif-tab <?= $tab === 'support' ? 'active' : '' ?>">
            🎧 Поддержка <span class="count"><?= $counts['support_count'] ?></span>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Список -->
<?php if (empty($notifications)): ?>
    <div class="notif-empty">
        <div class="icon">🔔</div>
        <?php if ($tab !== 'all'): ?>
            <h3>Нет уведомлений в этой категории</h3>
            <p>Посмотрите все уведомления</p>
            <a href="/notifications" class="btn btn-outline">📋 Все уведомления</a>
        <?php else: ?>
            <h3>Пока тишина</h3>
            <p>Новые отклики, сообщения и события появятся здесь</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    
    <?php 
    $groups = [
        ['items' => $today, 'label' => 'Сегодня'],
        ['items' => $yesterday, 'label' => 'Вчера'],
        ['items' => $older, 'label' => 'Ранее'],
    ];
    ?>
    
    <?php foreach ($groups as $g): if (empty($g['items'])) continue; ?>
        <div class="notif-group">
            <div class="notif-group-title"><?= $g['label'] ?></div>
            <?php foreach ($g['items'] as $n): 
                $type = $n['type'] ?? 'system';
                $style = $typeStyles[$type] ?? ['icon' => '🔔', 'bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Событие'];
                $isUnread = !$n['is_read'];
                $link = $n['link'] ?? null;
                $tag = $link ? 'a' : 'div';
            ?>
                <<?= $tag ?> <?= $link ? 'href="' . htmlspecialchars($link) . '"' : '' ?> class="notif-item <?= $isUnread ? 'unread' : '' ?>">
                    <div class="notif-item-icon" style="background: <?= $style['bg'] ?>; color: <?= $style['color'] ?>;">
                        <?= $style['icon'] ?>
                    </div>
                    <div class="notif-item-body">
                        <div class="notif-item-head">
                            <div class="notif-item-title" style="<?= $isUnread ? '' : 'color:var(--text-secondary);' ?>">
                                <?= htmlspecialchars($n['title']) ?>
                            </div>
                            <div class="notif-item-time"><?= date('H:i', strtotime($n['created_at'])) ?></div>
                        </div>
                        <?php if (!empty($n['message'])): ?>
                            <div class="notif-item-message"><?= htmlspecialchars($n['message']) ?></div>
                        <?php endif; ?>
                        <div class="notif-item-footer">
                            <span class="notif-type-badge" style="background: <?= $style['bg'] ?>; color: <?= $style['color'] ?>;">
                                <?= $style['label'] ?>
                            </span>
                            <?php if ($link): ?>
                                <span class="notif-arrow">→</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    
<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>