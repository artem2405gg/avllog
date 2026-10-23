<?php
require_once BASE_PATH . '/templates/header.php';

global $pdo;

// Информация о собеседнике
$stmt = $pdo->prepare("SELECT last_activity, role FROM users WHERE id = ?");
$stmt->execute([$companion['id']]);
$companionActivity = $stmt->fetch();
$companionRole = $companionActivity['role'] ?? 'owner';
$online = $companionActivity 
    && !empty($companionActivity['last_activity']) 
    && (time() - strtotime($companionActivity['last_activity'])) < 300;

$companionInitial = mb_substr($companion['company_name'] ?: $companion['name'], 0, 1);
$companionName = $companion['company_name'] ?: $companion['name'];
?>

<style>
    /* Обёртка чата */
    .chat-page {
        max-width: 800px;
        margin: 0 auto;
    }

    /* Хлебные крошки */
    .chat-breadcrumbs {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .chat-breadcrumbs a {
        color: var(--text-secondary);
        text-decoration: none;
        transition: color 0.2s;
    }
    .chat-breadcrumbs a:hover { color: var(--accent); }
    .chat-breadcrumbs .sep { opacity: 0.4; }
    .chat-breadcrumbs .current { color: var(--text); font-weight: 600; }

    /* Контейнер чата */
    .chat-container {
        background: var(--surface);
        border: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 200px);
        min-height: 500px;
        overflow: hidden;
        position: relative;
    }

    /* Шапка чата */
    .chat-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border-light);
        background: var(--surface);
        flex-shrink: 0;
    }

    .chat-back-btn {
        width: 40px;
        height: 40px;
        background: var(--border-light);
        color: var(--text);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .chat-back-btn:hover {
        background: var(--border);
        transform: translateX(-2px);
    }

    .chat-header-avatar {
        position: relative;
        flex-shrink: 0;
    }
    .chat-header-avatar .avatar-circle {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 800;
        color: white;
        border-radius: 50%;
    }
    .chat-header-avatar .avatar-circle.owner {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
    .chat-header-avatar .avatar-circle.carrier {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    .chat-header-avatar .online-dot {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        border: 2px solid var(--surface);
        border-radius: 50%;
        background: var(--success);
    }

    .chat-header-info {
        flex: 1;
        min-width: 0;
    }
    .chat-header-name {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -0.2px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 2px;
    }
    .chat-header-status {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .chat-header-status.online { color: var(--success); }
    .chat-header-status.offline { color: var(--text-secondary); }
    .chat-header-order {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
    }

    .chat-header-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
    .chat-header-btn {
        width: 40px;
        height: 40px;
        background: var(--border-light);
        color: var(--text-secondary);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .chat-header-btn:hover {
        background: var(--accent);
        color: var(--primary);
    }

    /* Область сообщений */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: var(--bg);
        background-image: 
            radial-gradient(circle at 20px 20px, rgba(0,0,0,0.02) 0.5px, transparent 0.5px),
            radial-gradient(circle at 60px 60px, rgba(0,0,0,0.02) 0.5px, transparent 0.5px);
        background-size: 80px 80px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        scroll-behavior: smooth;
    }

    /* Разделитель дат */
    .chat-date-divider {
        text-align: center;
        margin: 16px 0;
    }
    .chat-date-divider span {
        background: var(--border-light);
        color: var(--text-secondary);
        padding: 4px 12px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-radius: 12px;
    }

    /* Строка сообщения */
    .message-row {
        display: flex;
        gap: 8px;
        max-width: 78%;
        animation: msgIn 0.25s ease-out;
    }
    @keyframes msgIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .message-row.mine {
        align-self: flex-end;
        flex-direction: row-reverse;
    }
    .message-row:not(.mine) {
        align-self: flex-start;
    }

    /* Аватар собеседника */
    .message-avatar {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 800;
        color: white;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 4px;
    }
    .message-avatar.owner { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
    .message-avatar.carrier { background: linear-gradient(135deg, #10b981, #059669); }
    .message-row.mine .message-avatar { display: none; }
    .message-avatar.ghost { visibility: hidden; }

    /* Пузырь */
    .message-content {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
    }
    .message-bubble {
        padding: 10px 14px;
        font-size: 14px;
        line-height: 1.45;
        word-wrap: break-word;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .message-row.mine .message-bubble {
        background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
        color: white;
        border-radius: 18px 18px 4px 18px;
    }
    .message-row:not(.mine) .message-bubble {
        background: var(--surface);
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: 18px 18px 18px 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .message-bubble img {
        max-width: 260px;
        max-height: 260px;
        border-radius: 12px;
        display: block;
        cursor: pointer;
    }
    .message-bubble .file-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: rgba(255,255,255,0.08);
        border-radius: 8px;
        text-decoration: none;
        color: inherit;
        margin-bottom: 4px;
    }
    .message-row:not(.mine) .message-bubble .file-link {
        background: var(--border-light);
    }
    .message-bubble .file-icon { font-size: 24px; flex-shrink: 0; }
    .message-bubble .file-name {
        font-size: 12px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 180px;
    }

    /* Время */
    .message-time {
        font-size: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 0 4px;
    }
    .message-row.mine .message-time {
        color: var(--text-secondary);
        justify-content: flex-end;
    }
    .message-row:not(.mine) .message-time {
        color: var(--text-secondary);
    }
    .message-time .check {
        font-size: 11px;
        color: var(--accent);
    }

    /* Пустое состояние */
    .chat-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        text-align: center;
        padding: 40px 20px;
    }
    .chat-empty-icon {
        font-size: 64px;
        opacity: 0.4;
        margin-bottom: 16px;
    }
    .chat-empty h3 {
        font-size: 16px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 6px;
    }
    .chat-empty p {
        font-size: 13px;
        max-width: 320px;
    }

    /* Поле ввода */
    .chat-input-wrap {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid var(--border-light);
        background: var(--surface);
        flex-shrink: 0;
    }

    .chat-attach-btn {
        width: 44px;
        height: 44px;
        background: var(--border-light);
        color: var(--text-secondary);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .chat-attach-btn:hover {
        background: var(--accent);
        color: var(--primary);
    }

    .chat-file-name {
        font-size: 11px;
        color: var(--accent);
        font-weight: 700;
        padding: 4px 10px;
        background: #fffbeb;
        border: 1px solid var(--accent);
        display: flex;
        align-items: center;
        gap: 6px;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 4px;
        flex-shrink: 0;
    }
    .chat-file-name .remove {
        cursor: pointer;
        color: var(--danger);
        font-weight: 900;
    }

    .chat-textarea {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid var(--border);
        background: var(--border-light);
        font-size: 14px;
        font-family: inherit;
        line-height: 1.4;
        resize: none;
        min-height: 44px;
        max-height: 140px;
        overflow-y: auto;
        transition: all 0.2s;
    }
    .chat-textarea:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--surface);
    }
    .chat-textarea::placeholder {
        color: var(--text-secondary);
    }

    .chat-send-btn {
        width: 44px;
        height: 44px;
        background: var(--accent);
        color: var(--primary);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
        border-radius: 50%;
    }
    .chat-send-btn:hover:not(:disabled) {
        transform: scale(1.05);
        box-shadow: 0 4px 12px var(--accent-glow);
    }
    .chat-send-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* Мобильная версия */
    @media (max-width: 768px) {
        .chat-page {
            margin: -16px -12px 0;
            max-width: none;
        }
        .chat-breadcrumbs {
            padding: 12px 16px 8px;
        }
        .chat-container {
            height: calc(100vh - 130px);
            min-height: 400px;
            border: none;
            border-radius: 0;
        }
        .chat-header {
            padding: 10px 14px;
            gap: 10px;
        }
        .chat-back-btn,
        .chat-header-btn {
            width: 36px;
            height: 36px;
        }
        .chat-header-avatar .avatar-circle {
            width: 38px;
            height: 38px;
            font-size: 16px;
        }
        .chat-header-name { font-size: 14px; }
        .chat-messages { padding: 14px 12px; }
        .message-row { max-width: 88%; }
        .message-bubble { font-size: 13px; padding: 8px 12px; }
        .message-bubble img { max-width: 200px; max-height: 200px; }
        .chat-input-wrap { padding: 10px 12px; }
        .chat-attach-btn,
        .chat-send-btn { width: 40px; height: 40px; font-size: 16px; }
        .chat-textarea { font-size: 14px; }
    }
</style>

<div class="chat-page">
    
    <!-- Хлебные крошки -->
    <div class="chat-breadcrumbs">
        <a href="/messages">💬 Сообщения</a>
        <span class="sep">/</span>
        <span class="current"><?= htmlspecialchars($companionName) ?></span>
    </div>

    <!-- Чат -->
    <div class="chat-container">
        
        <!-- Шапка чата -->
        <div class="chat-header">
            <a href="/messages" class="chat-back-btn">←</a>
            
            <div class="chat-header-avatar">
                <div class="avatar-circle <?= $companionRole ?>">
                    <?= htmlspecialchars($companionInitial) ?>
                </div>
                <?php if ($online): ?>
                    <div class="online-dot"></div>
                <?php endif; ?>
            </div>
            
            <div class="chat-header-info">
                <div class="chat-header-name">
                    <?= htmlspecialchars($companionName) ?>
                    <span class="chat-header-status <?= $online ? 'online' : 'offline' ?>">
                        <?= $online ? '● Онлайн' : '● Не в сети' ?>
                    </span>
                </div>
                <div class="chat-header-order">
                    📦 <?= htmlspecialchars(mb_substr($order['title'], 0, 50)) ?><?= mb_strlen($order['title']) > 50 ? '…' : '' ?>
                </div>
            </div>
            
            <div class="chat-header-actions">
                <a href="/orders/view/<?= $orderId ?>" class="chat-header-btn" title="Открыть заказ">📋</a>
            </div>
        </div>

        <!-- Сообщения -->
        <div class="chat-messages" id="chatMessages">
            <div class="chat-empty">
                <div class="chat-empty-icon">💬</div>
                <h3>Нет сообщений</h3>
                <p>Начните переписку — задайте вопрос или договоритесь о деталях</p>
            </div>
        </div>

        <!-- Поле ввода -->
        <div class="chat-input-wrap">
            <button class="chat-attach-btn" onclick="document.getElementById('fileUpload').click()" title="Прикрепить файл">📎</button>
            <input type="file" id="fileUpload" 
                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx" 
                   style="display:none;" 
                   onchange="handleFileSelect()">
            
            <div style="flex:1; min-width:0;">
                <div id="fileNameWrap" class="chat-file-name" style="display:none;">
                    <span>📎</span>
                    <span id="fileName">file.pdf</span>
                    <span class="remove" onclick="removeFile()">×</span>
                </div>
                <textarea id="messageInput" 
                          class="chat-textarea" 
                          placeholder="Напишите сообщение..."
                          rows="1"
                          oninput="autoResize(this); updateSendBtn();"
                          onkeydown="handleKeyDown(event)"></textarea>
            </div>
            
            <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()" disabled>➤</button>
        </div>
        
    </div>
</div>

<script>
    // ============================================
    // КОНСТАНТЫ
    // ============================================
    const orderId = <?= (int)$orderId ?>;
    const myId = <?= (int)$_SESSION['user_id'] ?>;
    const receiverId = <?= (int)$companion['id'] ?>;
    const companionInitial = <?= json_encode($companionInitial) ?>;
    const companionRole = <?= json_encode($companionRole) ?>;
    
    // ============================================
    // СОСТОЯНИЕ
    // ============================================
    let lastMessageId = 0;
    let lastMessageCount = 0;
    let isLoading = false;
    let selectedFile = null;
    
    // ============================================
    // АВТОРЕСАЙЗ TEXTAREA
    // ============================================
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
    }
    
    function updateSendBtn() {
        var input = document.getElementById('messageInput');
        var btn = document.getElementById('sendBtn');
        var hasText = input.value.trim().length > 0;
        btn.disabled = !hasText && !selectedFile;
    }
    
    // ============================================
    // ФАЙЛЫ
    // ============================================
    function handleFileSelect() {
        var input = document.getElementById('fileUpload');
        var wrap = document.getElementById('fileNameWrap');
        var nameEl = document.getElementById('fileName');
        
        if (input.files.length > 0) {
            selectedFile = input.files[0];
            // Сокращаем имя файла если длинное
            var name = selectedFile.name;
            nameEl.textContent = name.length > 20 ? name.substring(0, 17) + '...' : name;
            wrap.style.display = 'flex';
        } else {
            removeFile();
        }
        updateSendBtn();
    }
    
    function removeFile() {
        selectedFile = null;
        document.getElementById('fileUpload').value = '';
        document.getElementById('fileNameWrap').style.display = 'none';
        updateSendBtn();
    }
    
    // ============================================
    // ОТПРАВКА (Enter = отправить, Shift+Enter = новая строка)
    // ============================================
    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }
    
    function sendMessage() {
        var input = document.getElementById('messageInput');
        var message = input.value.trim();
        
        if (!message && !selectedFile) return;
        
        var formData = new FormData();
        formData.append('message', message);
        formData.append('receiver_id', receiverId);
        if (selectedFile) formData.append('file', selectedFile);
        
        // Очищаем поле
        input.value = '';
        input.style.height = 'auto';
        removeFile();
        updateSendBtn();
        
        // Отправляем
        fetch('/chat/send/' + orderId, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function() {
                lastMessageId = 0;
                lastMessageCount = 0;
                loadMessages(true);
            })
            .catch(function(err) {
                console.error('[chat] send error:', err);
                alert('Не удалось отправить сообщение');
            });
        
        input.focus();
    }
    
    // ============================================
    // ВРЕМЯ
    // ============================================
    function formatTime(dateString) {
        var date = new Date(dateString.replace(' ', 'T'));
        return String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0');
    }
    
    function isSameDay(d1, d2) {
        return d1.getFullYear() === d2.getFullYear() 
            && d1.getMonth() === d2.getMonth() 
            && d1.getDate() === d2.getDate();
    }
    
    function formatDateDivider(dateString) {
        var date = new Date(dateString.replace(' ', 'T'));
        var today = new Date();
        var yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (isSameDay(date, today)) return 'Сегодня';
        if (isSameDay(date, yesterday)) return 'Вчера';
        
        var months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
        return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
    }
    
    // ============================================
    // ЭКРАНИРОВАНИЕ HTML
    // ============================================
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ============================================
    // ПОСТРОЕНИЕ HTML СООБЩЕНИЙ
    // ============================================
    function buildMessagesHTML(messages) {
        if (!messages || messages.length === 0) {
            return '<div class="chat-empty">' +
                '<div class="chat-empty-icon">💬</div>' +
                '<h3>Нет сообщений</h3>' +
                '<p>Начните переписку — задайте вопрос или договоритесь о деталях</p>' +
                '</div>';
        }
        
        var html = '';
        var lastDate = null;
        
        for (var i = 0; i < messages.length; i++) {
            var m = messages[i];
            var msgDate = new Date(m.created_at.replace(' ', 'T'));
            var isMine = m.sender_id == myId;
            
            // Разделитель дат
            if (!lastDate || !isSameDay(lastDate, msgDate)) {
                html += '<div class="chat-date-divider"><span>' + formatDateDivider(m.created_at) + '</span></div>';
                lastDate = msgDate;
            }
            
            // Показываем аватар если предыдущее сообщение от другого отправителя
            var showAvatar = !isMine && (i === 0 || messages[i-1].sender_id != m.sender_id);
            var avatarHTML = '';
            if (!isMine) {
                if (showAvatar) {
                    avatarHTML = '<div class="message-avatar ' + companionRole + '">' + escapeHtml(companionInitial) + '</div>';
                } else {
                    avatarHTML = '<div class="message-avatar ghost"></div>';
                }
            }
            
            // Содержимое
            var contentHTML = '';
            
            if (m.file_path) {
                if (m.file_type === 'image') {
                    contentHTML += '<a href="/' + m.file_path + '" target="_blank">' +
                        '<img src="/' + m.file_path + '" loading="lazy" onerror="this.outerHTML=\'📷 Изображение\'">' +
                        '</a>';
                } else {
                    var fileName = m.file_path.split('/').pop();
                    contentHTML += '<a href="/' + m.file_path + '" target="_blank" class="file-link">' +
                        '<span class="file-icon">📄</span>' +
                        '<span class="file-name">' + escapeHtml(fileName) + '</span>' +
                        '</a>';
                }
            }
            
            if (m.message) {
                contentHTML += '<span>' + escapeHtml(m.message) + '</span>';
            }
            
            var checkHTML = isMine ? '<span class="check">✓✓</span>' : '';
            
            html += '<div class="message-row ' + (isMine ? 'mine' : '') + '" data-id="' + m.id + '">' +
                avatarHTML +
                '<div class="message-content">' +
                    '<div class="message-bubble">' + contentHTML + '</div>' +
                    '<div class="message-time">' + formatTime(m.created_at) + checkHTML + '</div>' +
                '</div>' +
            '</div>';
        }
        
        return html;
    }
    
    // ============================================
    // ЗАГРУЗКА СООБЩЕНИЙ
    // ============================================
    function loadMessages(silent) {
        if (isLoading) return;
        isLoading = true;
        
        fetch('/chat/load/' + orderId, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(messages) {
                isLoading = false;
                
                var newCount = messages ? messages.length : 0;
                var newLastId = (messages && messages.length > 0) ? messages[messages.length - 1].id : 0;
                
                // Если ничего не изменилось — не перерисовываем
                if (newCount === lastMessageCount && newLastId === lastMessageId) return;
                
                lastMessageCount = newCount;
                lastMessageId = newLastId;
                
                var container = document.getElementById('chatMessages');
                
                // Проверяем, был ли пользователь у нижнего края
                var wasAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 80;
                
                container.innerHTML = buildMessagesHTML(messages);
                
                // Прокрутка вниз если был внизу или это первая загрузка
                if (wasAtBottom || silent) {
                    container.scrollTop = container.scrollHeight;
                }
            })
            .catch(function(err) {
                isLoading = false;
                console.error('[chat] load error:', err);
            });
    }
    
    // ============================================
    // ИНИЦИАЛИЗАЦИЯ
    // ============================================
    loadMessages(true);
    
    // Автообновление каждые 3 секунды
    setInterval(function() { loadMessages(false); }, 3000);
    
    // Фокус на поле ввода
    document.getElementById('messageInput').focus();
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>