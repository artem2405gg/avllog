<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

$userId = $_SESSION['user_id'];

// Фильтр
$tab = $_GET['tab'] ?? 'all';
if (!in_array($tab, ['all', 'open', 'closed'])) $tab = 'all';

// Считаем по категориям
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
    FROM tickets WHERE user_id = ?");
$stmt->execute([$userId]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
$counts['open_count'] = (int)($counts['open_count'] ?? 0);
$counts['closed_count'] = (int)($counts['closed_count'] ?? 0);
$counts['total'] = (int)($counts['total'] ?? 0);

// Загружаем тикеты с последним сообщением
$where = '';
if ($tab === 'open') $where = "AND t.status = 'open'";
elseif ($tab === 'closed') $where = "AND t.status = 'closed'";

$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_message,
           (SELECT is_admin FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_is_admin,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) as last_at
    FROM tickets t
    WHERE t.user_id = ? $where
    ORDER BY t.updated_at DESC, t.id DESC
");
$stmt->execute([$userId]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Header */
    .support-header {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 24px 28px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .support-header h1 {
        font-size: 24px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }
    .support-header p {
        color: var(--text-secondary);
        font-size: 13px;
    }

    /* Табы */
    .tickets-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 20px;
        border-bottom: 2px solid var(--border);
        overflow-x: auto;
    }
    .tickets-tab {
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-secondary);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .tickets-tab:hover { color: var(--text); }
    .tickets-tab.active {
        color: var(--text);
        border-bottom-color: var(--accent);
    }
    .tickets-tab .count {
        background: var(--border-light);
        color: var(--text-secondary);
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 800;
        border-radius: 10px;
    }
    .tickets-tab.active .count {
        background: var(--accent);
        color: var(--primary);
    }

    /* Карточка тикета */
    .ticket-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 18px 20px;
        margin-bottom: 10px;
        display: flex;
        gap: 16px;
        align-items: flex-start;
        text-decoration: none;
        color: inherit;
        transition: all 0.25s;
        position: relative;
    }
    .ticket-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border-color: var(--accent);
    }
    .ticket-card.has-new::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--accent);
    }
    .ticket-icon {
        width: 44px;
        height: 44px;
        background: var(--border-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .ticket-card.closed .ticket-icon {
        opacity: 0.5;
    }
    .ticket-body {
        flex: 1;
        min-width: 0;
    }
    .ticket-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 6px;
    }
    .ticket-subject {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -0.2px;
        line-height: 1.3;
        word-break: break-word;
    }
    .ticket-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .ticket-status.open { background: #fef3c7; color: #92400e; }
    .ticket-status.closed { background: #d1fae5; color: #065f46; }
    .ticket-preview {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.4;
        margin-bottom: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ticket-meta {
        display: flex;
        gap: 16px;
        font-size: 11px;
        color: var(--text-secondary);
        flex-wrap: wrap;
    }
    .ticket-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Пусто */
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

    /* Быстрые ссылки */
    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .quick-link {
        padding: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        display: flex;
        gap: 12px;
        align-items: center;
        transition: all 0.2s;
    }
    .quick-link:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
    }
    .quick-link .ql-icon {
        width: 40px;
        height: 40px;
        background: var(--border-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .quick-link .ql-title {
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 2px;
    }
    .quick-link .ql-sub {
        font-size: 11px;
        color: var(--text-secondary);
    }

    /* Мобильная */
    @media (max-width: 768px) {
        .support-header { padding: 18px; }
        .support-header h1 { font-size: 20px; }
        .ticket-card { padding: 14px; gap: 12px; }
        .ticket-icon { width: 36px; height: 36px; font-size: 16px; }
        .ticket-subject { font-size: 14px; }
        .ticket-preview { font-size: 12px; }
    }
</style>

<!-- Хлебные крошки -->
<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/dashboard" style="color:var(--text-secondary); text-decoration:none;">📊 Панель</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Поддержка</span>
</div>

<!-- Header -->
<div class="support-header">
    <div>
        <h1>🎧 Техподдержка</h1>
        <p>Отвечаем в течение 1-2 часов в рабочее время</p>
    </div>
    <a href="/support/create" class="btn btn-primary">➕ Создать обращение</a>
</div>

<!-- Сообщение об успехе -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Быстрые ссылки -->
<?php if ($counts['total'] === 0): ?>
<div class="quick-links">
    <a href="/page/owners" class="quick-link">
        <div class="ql-icon">📖</div>
        <div>
            <div class="ql-title">Как создать заказ</div>
            <div class="ql-sub">Пошаговое руководство</div>
        </div>
    </a>
    <a href="/pricing" class="quick-link">
        <div class="ql-icon">💎</div>
        <div>
            <div class="ql-title">Тарифы и подписки</div>
            <div class="ql-sub">Как работает оплата</div>
        </div>
    </a>
    <a href="mailto:info@avllogist.ru" class="quick-link">
        <div class="ql-icon">✉️</div>
        <div>
            <div class="ql-title">Написать на email</div>
            <div class="ql-sub">info@avllogist.ru</div>
        </div>
    </a>
    <a href="tel:+79215411481" class="quick-link">
        <div class="ql-icon">📞</div>
        <div>
            <div class="ql-title">Позвонить</div>
            <div class="ql-sub">+7 (921) 541-14-81</div>
        </div>
    </a>
</div>
<?php endif; ?>

<!-- Табы -->
<?php if ($counts['total'] > 0): ?>
<div class="tickets-tabs">
    <a href="/support?tab=all" class="tickets-tab <?= $tab === 'all' ? 'active' : '' ?>">
        📋 Все <span class="count"><?= $counts['total'] ?></span>
    </a>
    <a href="/support?tab=open" class="tickets-tab <?= $tab === 'open' ? 'active' : '' ?>">
        🟡 Открытые <span class="count"><?= $counts['open_count'] ?></span>
    </a>
    <a href="/support?tab=closed" class="tickets-tab <?= $tab === 'closed' ? 'active' : '' ?>">
        ✅ Закрытые <span class="count"><?= $counts['closed_count'] ?></span>
    </a>
</div>
<?php endif; ?>

<!-- Список тикетов -->
<?php if (empty($tickets)): ?>
    <div class="empty-state">
        <div class="icon">🎧</div>
        <?php if ($tab !== 'all'): ?>
            <h3>Нет <?= $tab === 'open' ? 'открытых' : 'закрытых' ?> обращений</h3>
            <p>Посмотрите все обращения или создайте новое</p>
            <a href="/support?tab=all" class="btn btn-outline" style="margin-right:8px;">📋 Все обращения</a>
            <a href="/support/create" class="btn btn-primary">➕ Создать</a>
        <?php else: ?>
            <h3>У вас пока нет обращений</h3>
            <p>Задайте вопрос — ответим в течение 1-2 часов</p>
            <a href="/support/create" class="btn btn-primary">➕ Создать обращение</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($tickets as $ticket): 
        $isClosed = $ticket['status'] === 'closed';
        $hasNewAdminReply = !empty($ticket['last_is_admin']) && !$isClosed;
        $previewText = $ticket['last_message'] ?? $ticket['message'];
        $lastAt = $ticket['last_at'] ?? $ticket['created_at'];
    ?>
        <a href="/support/view/<?= $ticket['id'] ?>" class="ticket-card <?= $isClosed ? 'closed' : '' ?> <?= $hasNewAdminReply ? 'has-new' : '' ?>">
            <div class="ticket-icon"><?= $isClosed ? '✅' : '🎧' ?></div>
            <div class="ticket-body">
                <div class="ticket-head">
                    <div class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></div>
                    <span class="ticket-status <?= $isClosed ? 'closed' : 'open' ?>">
                        <?= $isClosed ? 'Закрыт' : 'Открыт' ?>
                    </span>
                </div>
                <div class="ticket-preview">
                    <?php if (!empty($ticket['last_is_admin'])): ?>
                        <strong>🎧 Поддержка:</strong>
                    <?php endif; ?>
                    <?= htmlspecialchars(mb_substr($previewText, 0, 120)) ?>
                </div>
                <div class="ticket-meta">
                    <span>🆔 #<?= $ticket['id'] ?></span>
                    <span>📅 <?= date('d.m.Y H:i', strtotime($lastAt)) ?></span>
                    <?php if (!empty($ticket['admin_reply']) && !$isClosed): ?>
                        <span style="color:var(--accent); font-weight:700;">💬 Есть ответ</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>