<?php
require_once BASE_PATH . '/templates/header.php';

global $pdo;

// Загружаем все сообщения
$stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC");
$stmt->execute([$ticket['id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Инфо о тикете
$isClosed = $ticket['status'] === 'closed';
$lastAdminReply = null;
foreach (array_reverse($messages) as $m) {
    if (!empty($m['is_admin'])) {
        $lastAdminReply = $m;
        break;
    }
}
?>

<style>
    /* Header */
    .ticket-top {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px 24px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 16px;
        flex-wrap: wrap;
    }
    .ticket-top-info { flex: 1; min-width: 240px; }
    .ticket-top h1 {
        font-size: 20px;
        font-weight: 900;
        letter-spacing: -0.4px;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    .ticket-top-meta {
        display: flex;
        gap: 16px;
        font-size: 12px;
        color: var(--text-secondary);
        flex-wrap: wrap;
    }
    .ticket-top-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .ticket-top-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Статус */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-pill.open { background: #fef3c7; color: #92400e; }
    .status-pill.closed { background: #d1fae5; color: #065f46; }

    /* Чат */
    .chat-wrap {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 24px;
        margin-bottom: 16px;
    }
    .chat-messages {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 24px;
    }
    .chat-msg {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .chat-msg.mine {
        flex-direction: row-reverse;
    }
    .chat-avatar {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 900;
        color: white;
        flex-shrink: 0;
    }
    .chat-avatar.admin { background: #10b981; }
    .chat-avatar.user { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
    .chat-bubble {
        max-width: 75%;
        padding: 12px 16px;
        font-size: 14px;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-wrap;
    }
    .chat-msg.admin .chat-bubble {
        background: #f0fdf4;
        border: 1px solid #c3e6cb;
    }
    .chat-msg.mine .chat-bubble {
        background: var(--border-light);
        border: 1px solid var(--border);
    }
    .chat-meta {
        font-size: 11px;
        color: var(--text-secondary);
        margin-top: 6px;
        display: flex;
        gap: 8px;
    }

    /* Форма ответа */
    .reply-form {
        padding-top: 20px;
        border-top: 1px solid var(--border-light);
    }
    .reply-form textarea {
        width: 100%;
        padding: 14px;
        border: 2px solid var(--border);
        font-family: inherit;
        font-size: 14px;
        background: var(--border-light);
        min-height: 100px;
        resize: vertical;
        transition: all 0.2s;
    }
    .reply-form textarea:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--surface);
    }
    .reply-form-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    /* Закрытый тикет */
    .ticket-closed-note {
        padding: 16px;
        background: #f0fdf4;
        border: 1px solid #c3e6cb;
        font-size: 13px;
        color: #065f46;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .ticket-closed-note .icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    /* Мобильная */
    @media (max-width: 768px) {
        .ticket-top { padding: 16px; }
        .ticket-top h1 { font-size: 17px; }
        .chat-wrap { padding: 16px; }
        .chat-bubble { max-width: 85%; font-size: 13px; }
        .chat-avatar { width: 32px; height: 32px; font-size: 14px; }
    }
</style>

<!-- Хлебные крошки -->
<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/support" style="color:var(--text-secondary); text-decoration:none;">🎧 Поддержка</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Обращение #<?= $ticket['id'] ?></span>
</div>

<!-- Верхняя панель -->
<div class="ticket-top">
    <div class="ticket-top-info">
        <h1><?= htmlspecialchars($ticket['subject']) ?></h1>
        <div class="ticket-top-meta">
            <span>🆔 #<?= $ticket['id'] ?></span>
            <span>📅 Создан: <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></span>
            <span class="status-pill <?= $isClosed ? 'closed' : 'open' ?>">
                <?= $isClosed ? '✅ Закрыт' : '🟡 Открыт' ?>
            </span>
        </div>
    </div>
    <div class="ticket-top-actions">
        <a href="/support" class="btn btn-outline btn-sm">← К списку</a>
    </div>
</div>

<!-- Переписка -->
<div class="chat-wrap">
    <div class="chat-messages">
        <?php if (empty($messages)): ?>
            <!-- Если сообщений нет — показываем исходное -->
            <div class="chat-msg mine">
                <div class="chat-avatar user"><?= htmlspecialchars(mb_substr($_SESSION['user_name'], 0, 1)) ?></div>
                <div>
                    <div class="chat-bubble"><?= htmlspecialchars($ticket['message']) ?></div>
                    <div class="chat-meta" style="justify-content:flex-end;">
                        <span>👤 Вы</span>
                        <span><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): 
                $isAdmin = !empty($msg['is_admin']);
                $isMine = !$isAdmin;
                $cls = $isAdmin ? 'admin' : 'mine';
                $avatarCls = $isAdmin ? 'admin' : 'user';
                $avatarText = $isAdmin ? '🎧' : htmlspecialchars(mb_substr($_SESSION['user_name'], 0, 1));
            ?>
                <div class="chat-msg <?= $cls ?>">
                    <div class="chat-avatar <?= $avatarCls ?>"><?= $avatarText ?></div>
                    <div>
                        <div class="chat-bubble"><?= htmlspecialchars($msg['message']) ?></div>
                        <div class="chat-meta" style="<?= $isMine ? 'justify-content:flex-end;' : '' ?>">
                            <span><?= $isAdmin ? '🎧 Поддержка' : '👤 Вы' ?></span>
                            <span><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Форма ответа / закрытый тикет -->
    <?php if ($isClosed): ?>
        <div class="ticket-closed-note">
            <div class="icon">✅</div>
            <div>
                <strong>Обращение закрыто</strong>
                <div style="margin-top:4px; opacity:0.9;">
                    Если проблема не решена — создайте новое обращение.
                </div>
                <a href="/support/create" class="btn btn-success btn-sm" style="margin-top:12px;">➕ Создать новое</a>
            </div>
        </div>
    <?php else: ?>
        <form method="POST" action="/support/reply/<?= $ticket['id'] ?>" class="reply-form">
            <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin-bottom:8px;">
                Добавить сообщение
            </label>
            <textarea name="message" required placeholder="Опишите подробнее или задайте уточняющий вопрос..."></textarea>
            <div class="reply-form-actions">
                <button type="submit" class="btn btn-primary">📤 Отправить</button>
                <?php if (!empty($ticket['admin_reply'])): ?>
                    <button type="submit" formaction="/support/close/<?= $ticket['id'] ?>" class="btn btn-outline" onclick="return confirm('Закрыть обращение?');">
                        ✅ Проблема решена — закрыть
                    </button>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>