<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

// Считаем счётчики
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM orders o
    JOIN bids b ON o.id = b.order_id AND b.status = 'accepted'
    WHERE (o.user_id = ? OR b.carrier_id = ?)
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$totalChats = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as unread
    FROM messages m
    JOIN orders o ON m.order_id = o.id
    JOIN bids b ON o.id = b.order_id AND b.status = 'accepted'
    WHERE m.receiver_id = ?
      AND m.is_read = 0
      AND (o.user_id = ? OR b.carrier_id = ?)
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$unreadChats = (int)$stmt->fetchColumn();
?>

<style>
    /* Header */
    .messages-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .messages-header h1 {
        font-size: 26px;
        font-weight: 900;
        letter-spacing: -1px;
        margin-bottom: 4px;
    }
    .messages-header p {
        color: var(--text-secondary);
        font-size: 14px;
    }

    /* Поиск */
    .messages-search {
        position: relative;
        margin-bottom: 20px;
    }
    .messages-search input {
        width: 100%;
        padding: 14px 16px 14px 44px;
        border: 2px solid var(--border);
        background: var(--surface);
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
    }
    .messages-search input:focus {
        outline: none;
        border-color: var(--primary);
    }
    .messages-search .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        color: var(--text-secondary);
        pointer-events: none;
    }

    /* Список чатов */
    .chats-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Карточка чата */
    .chat-card {
        display: grid;
        grid-template-columns: 48px 1fr auto;
        gap: 14px;
        padding: 14px 18px;
        background: var(--surface);
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
        align-items: center;
        position: relative;
    }
    .chat-card:hover {
        border-color: var(--accent);
        transform: translateX(2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    }
    .chat-card.unread {
        background: linear-gradient(90deg, #fffbeb 0%, var(--surface) 30%);
    }
    .chat-card.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--accent);
    }

    /* Аватар */
    .chat-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }
    .chat-avatar {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 800;
        color: white;
        border-radius: 50%;
    }
    .chat-avatar.owner {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
    .chat-avatar.carrier {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    .chat-online-dot {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 14px;
        height: 14px;
        background: var(--success);
        border: 2px solid var(--surface);
        border-radius: 50%;
    }

    /* Содержимое */
    .chat-content {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .chat-row-top {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .chat-name {
        font-size: 15px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -0.2px;
    }
    .chat-role {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2px 7px;
    }
    .chat-role.owner { background: #dbeafe; color: #1e40af; }
    .chat-role.carrier { background: #d1fae5; color: #065f46; }

    .chat-order-title {
        font-size: 11px;
        color: var(--text-secondary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .chat-order-title .order-icon {
        opacity: 0.6;
    }

    .chat-preview {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .chat-preview .you {
        color: var(--text);
        font-weight: 700;
    }
    .chat-preview.empty {
        font-style: italic;
        opacity: 0.6;
    }

    /* Правая часть */
    .chat-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
        flex-shrink: 0;
        min-width: 60px;
    }
    .chat-time {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 600;
        white-space: nowrap;
    }
    .chat-time.recent {
        color: var(--accent);
    }
    .chat-unread-badge {
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        background: var(--danger);
        color: white;
        border-radius: 11px;
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .chat-arrow {
        color: var(--text-secondary);
        font-size: 14px;
        opacity: 0.5;
        transition: all 0.2s;
    }
    .chat-card:hover .chat-arrow {
        color: var(--accent);
        opacity: 1;
        transform: translateX(2px);
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
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Мобильная версия */
    @media (max-width: 768px) {
        .messages-header h1 { font-size: 22px; }
        .chat-card { padding: 12px 14px; gap: 12px; grid-template-columns: 44px 1fr auto; }
        .chat-avatar { width: 44px; height: 44px; font-size: 16px; }
        .chat-name { font-size: 14px; }
        .chat-preview { font-size: 12px; -webkit-line-clamp: 1; }
        .chat-meta { min-width: 50px; }
        .chat-time { font-size: 10px; }
    }
</style>

<!-- Header -->
<div class="messages-header">
    <div>
        <h1>💬 Сообщения</h1>
        <p>
            <?php if ($unreadChats > 0): ?>
                <span style="color:var(--danger); font-weight:700;"><?= $unreadChats ?> новых</span> · 
            <?php endif; ?>
            <?= $totalChats ?> <?= $totalChats === 1 ? 'переписка' : ($totalChats < 5 ? 'переписки' : 'переписок') ?>
        </p>
    </div>
</div>

<!-- Поиск -->
<div class="messages-search">
    <span class="search-icon">🔍</span>
    <input type="text" id="chatSearch" placeholder="Поиск по имени или заказу..." oninput="filterChats()">
</div>

<!-- Список чатов -->
<div class="chats-list" id="chatsList">
    <?php if (empty($chats)): ?>
        <div class="empty-state">
            <div class="icon">💬</div>
            <h3>Пока нет сообщений</h3>
            <p>Сообщения появятся здесь, когда вы выберете перевозчика или вас выберут исполнителем.</p>
            <a href="/orders" class="btn btn-primary">📋 Найти заказы</a>
        </div>
    <?php else: ?>
        <?php foreach ($chats as $chat): 
            // Определяем статус "онлайн"
            $online = false;
            $companionRole = 'owner'; // по умолчанию
            if (!empty($chat['companion_name'])) {
                $stmt = $pdo->prepare("SELECT role, last_activity FROM users WHERE name = ? OR company_name = ? LIMIT 1");
                $stmt->execute([$chat['companion_name'], $chat['companion_name']]);
                $act = $stmt->fetch();
                if ($act) {
                    $companionRole = $act['role'] ?? 'owner';
                    $online = $act['last_activity'] && (time() - strtotime($act['last_activity'])) < 300;
                }
            }
            
            $initial = mb_substr($chat['companion_name'] ?? '?', 0, 1);
            $isUnread = $chat['unread'] > 0;
            $timeInfo = formatChatTime($chat['last_time'] ?? null);
            $isRecent = $timeInfo['is_recent'] ?? false;
        ?>
            <a href="/chat/view/<?= $chat['id'] ?>" 
               class="chat-card <?= $isUnread ? 'unread' : '' ?>"
               data-name="<?= htmlspecialchars(mb_strtolower($chat['companion_name'] ?? '')) ?>"
               data-order="<?= htmlspecialchars(mb_strtolower($chat['title'] ?? '')) ?>">
                
                <!-- Аватар -->
                <div class="chat-avatar-wrap">
                    <div class="chat-avatar <?= $companionRole ?>">
                        <?= htmlspecialchars($initial) ?>
                    </div>
                    <?php if ($online): ?>
                        <div class="chat-online-dot"></div>
                    <?php endif; ?>
                </div>
                
                <!-- Содержимое -->
                <div class="chat-content">
                    <div class="chat-row-top">
                        <div class="chat-name"><?= htmlspecialchars($chat['companion_name'] ?? 'Пользователь') ?></div>
                        <span class="chat-role <?= $companionRole ?>">
                            <?= $companionRole === 'owner' ? 'Грузовладелец' : 'Перевозчик' ?>
                        </span>
                        <?php if ($online): ?>
                            <span style="font-size:11px; color:var(--success); font-weight:700;">● онлайн</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($chat['title'])): ?>
                        <div class="chat-order-title">
                            <span class="order-icon">📦</span>
                            <?= htmlspecialchars(mb_substr($chat['title'], 0, 50)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="chat-preview <?= empty($chat['last_message']) ? 'empty' : '' ?>">
                        <?php if (!empty($chat['last_message'])): ?>
                            <?php if ($chat['last_sender_id'] == $_SESSION['user_id']): ?>
                                <span class="you">Вы:</span>
                            <?php endif; ?>
                            <?= htmlspecialchars(mb_substr($chat['last_message'], 0, 80)) ?><?= mb_strlen($chat['last_message']) > 80 ? '…' : '' ?>
                        <?php else: ?>
                            Начните переписку — задайте вопрос
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Мета -->
                <div class="chat-meta">
                    <?php if (!empty($chat['last_time'])): ?>
                        <div class="chat-time <?= $isRecent ? 'recent' : '' ?>">
                            <?= $timeInfo['label'] ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($isUnread): ?>
                        <div class="chat-unread-badge"><?= $chat['unread'] ?></div>
                    <?php else: ?>
                        <span class="chat-arrow">→</span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Поиск по чатам
function filterChats() {
    var query = document.getElementById('chatSearch').value.toLowerCase().trim();
    document.querySelectorAll('.chat-card').forEach(function(card) {
        var name = card.dataset.name || '';
        var order = card.dataset.order || '';
        var matches = !query || name.includes(query) || order.includes(query);
        card.style.display = matches ? '' : 'none';
    });
}

// Автообновление счётчика непрочитанных
setInterval(function() {
    fetch('/api/counters', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Обновляем бейджи в шапке
            document.querySelectorAll('.msg-counter').forEach(function(b) {
                if (data.messages > 0) { b.textContent = data.messages; b.classList.remove('hidden'); }
                else { b.classList.add('hidden'); }
            });
        })
        .catch(function() {});
}, 5000);
</script>

<?php
// Функция форматирования времени
function formatChatTime($dateString) {
    if (empty($dateString)) return ['label' => '', 'is_recent' => false];
    
    $time = strtotime($dateString);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return ['label' => 'только что', 'is_recent' => true];
    } elseif ($diff < 3600) {
        $min = floor($diff / 60);
        return ['label' => $min . ' мин', 'is_recent' => true];
    } elseif (date('Y-m-d') == date('Y-m-d', $time)) {
        return ['label' => date('H:i', $time), 'is_recent' => true];
    } elseif (date('Y-m-d', strtotime('-1 day')) == date('Y-m-d', $time)) {
        return ['label' => 'вчера', 'is_recent' => false];
    } elseif ($diff < 86400 * 7) {
        $days = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        return ['label' => $days[(int)date('w', $time)], 'is_recent' => false];
    } else {
        return ['label' => date('d.m.y', $time), 'is_recent' => false];
    }
}
?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>