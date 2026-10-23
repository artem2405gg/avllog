<?php require_once BASE_PATH . '/templates/header.php'; ?>

<style>
    .create-support {
        max-width: 720px;
        margin: 0 auto;
    }
    .create-support-header {
        text-align: center;
        margin-bottom: 28px;
    }
    .create-support-header .icon {
        font-size: 48px;
        margin-bottom: 10px;
    }
    .create-support-header h1 {
        font-size: 26px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 8px;
    }
    .create-support-header p {
        color: var(--text-secondary);
        font-size: 14px;
    }

    /* Категории */
    .category-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }
    .category-btn {
        padding: 14px 16px;
        background: var(--surface);
        border: 2px solid var(--border);
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
        font-family: inherit;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .category-btn:hover {
        border-color: var(--accent);
        background: #fffbeb;
    }
    .category-btn.selected {
        border-color: var(--accent);
        background: #fef3c7;
    }
    .category-btn .icon {
        font-size: 22px;
        flex-shrink: 0;
    }
    .category-btn .text {
        font-weight: 800;
        font-size: 13px;
        line-height: 1.3;
    }
    .category-btn .sub {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
        display: block;
        margin-top: 2px;
    }

    /* Форма */
    .form-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 24px;
        margin-bottom: 16px;
    }
    .field {
        margin-bottom: 18px;
    }
    .field:last-child {
        margin-bottom: 0;
    }
    .field label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }
    .field label .required {
        color: var(--danger);
    }
    .field input,
    .field textarea,
    .field select {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--border);
        font-size: 14px;
        font-family: inherit;
        background: var(--border-light);
        transition: all 0.2s;
        color: var(--text);
        resize: vertical;
    }
    .field input:focus,
    .field textarea:focus,
    .field select:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--surface);
    }
    .field textarea {
        min-height: 140px;
        line-height: 1.5;
    }
    .field-hint {
        font-size: 11px;
        color: var(--text-secondary);
        margin-top: 6px;
    }

    /* Submit */
    .submit-block {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .submit-block .btn {
        padding: 16px 24px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Контакты внизу */
    .contacts-note {
        text-align: center;
        padding: 20px;
        font-size: 12px;
        color: var(--text-secondary);
        background: var(--border-light);
        margin-top: 16px;
    }
    .contacts-note a {
        color: var(--accent);
        font-weight: 700;
        text-decoration: none;
    }

    /* Мобильная */
    @media (max-width: 768px) {
        .category-grid { grid-template-columns: 1fr; }
        .create-support-header h1 { font-size: 20px; }
        .form-panel { padding: 18px; }
    }
</style>

<div class="create-support">
    
    <!-- Хлебные крошки -->
    <div style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
        <a href="/support" style="color:var(--text-secondary); text-decoration:none;">🎧 Поддержка</a>
        <span style="opacity:0.4;"> / </span>
        <span style="color:var(--text); font-weight:600;">Новое обращение</span>
    </div>
    
    <!-- Header -->
    <div class="create-support-header">
        <div class="icon">✍️</div>
        <h1>Новое обращение</h1>
        <p>Опишите проблему — ответим в течение 1-2 часов</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" id="ticketForm">
        
        <!-- Категория -->
        <div class="form-panel">
            <label style="display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin-bottom:12px;">
                1. Что случилось?
            </label>
            <div class="category-grid">
                <button type="button" class="category-btn" onclick="selectCategory(this, 'Проблема с заказом')">
                    <span class="icon">📦</span>
                    <span>
                        <span class="text">Проблема с заказом</span>
                        <span class="sub">Создание, редактирование, отклики</span>
                    </span>
                </button>
                <button type="button" class="category-btn" onclick="selectCategory(this, 'Проблема с оплатой')">
                    <span class="icon">💳</span>
                    <span>
                        <span class="text">Проблема с оплатой</span>
                        <span class="sub">Подписка, комиссия, счёт</span>
                    </span>
                </button>
                <button type="button" class="category-btn" onclick="selectCategory(this, 'Вопрос по тарифам')">
                    <span class="icon">💎</span>
                    <span>
                        <span class="text">Вопрос по тарифам</span>
                        <span class="sub">Подписки, лимиты, оплата</span>
                    </span>
                </button>
                <button type="button" class="category-btn" onclick="selectCategory(this, 'Жалоба на пользователя')">
                    <span class="icon">⚠️</span>
                    <span>
                        <span class="text">Жалоба на пользователя</span>
                        <span class="sub">Нарушение правил, мошенничество</span>
                    </span>
                </button>
                <button type="button" class="category-btn" onclick="selectCategory(this, 'Техническая проблема')">
                    <span class="icon">🔧</span>
                    <span>
                        <span class="text">Техническая проблема</span>
                        <span class="sub">Ошибки, не работает сайт</span>
                    </span>
                </button>
                <button type="button" class="category-btn" onclick="selectCategory(this, 'Другое')">
                    <span class="icon">💬</span>
                    <span>
                        <span class="text">Другое</span>
                        <span class="sub">Общий вопрос, идея</span>
                    </span>
                </button>
            </div>
            <input type="hidden" name="category" id="categoryInput" required>
        </div>

        <!-- Тема и сообщение -->
        <div class="form-panel">
            <div class="field">
                <label>2. Тема обращения <span class="required">*</span></label>
                <input type="text" name="subject" id="subjectInput" required maxlength="150"
                       placeholder="Кратко опишите суть проблемы">
                <div class="field-hint">💡 Например: «Не приходят уведомления о новых заказах»</div>
            </div>
            
            <div class="field">
                <label>3. Подробное описание <span class="required">*</span></label>
                <textarea name="message" required maxlength="5000"
                          placeholder="Опишите ситуацию подробно:&#10;— Что вы делали?&#10;— Что пошло не так?&#10;— Что ожидали увидеть?&#10;— Номер заказа (если есть)"></textarea>
                <div class="field-hint">💡 Чем подробнее — тем быстрее поможем</div>
            </div>
        </div>

        <!-- Отправка -->
        <div class="submit-block">
            <button type="submit" class="btn btn-primary">
                📤 Отправить обращение
            </button>
            <a href="/support" class="btn btn-outline" style="text-align:center;">← Назад к обращениям</a>
        </div>
    </form>

    <!-- Контакты -->
    <div class="contacts-note">
        Срочный вопрос? Позвоните: <a href="tel:+79215411481">+7 (921) 541-14-81</a><br>
        Или напишите на <a href="mailto:info@avllogist.ru">info@avllogist.ru</a>
    </div>
</div>

<script>
function selectCategory(btn, category) {
    document.querySelectorAll('.category-btn').forEach(function(b) {
        b.classList.remove('selected');
    });
    btn.classList.add('selected');
    document.getElementById('categoryInput').value = category;
    
    // Автозаполнение темы
    var subjectInput = document.getElementById('subjectInput');
    if (!subjectInput.value) {
        subjectInput.value = category;
        subjectInput.focus();
        subjectInput.select();
    }
}

// Валидация перед отправкой
document.getElementById('ticketForm').addEventListener('submit', function(e) {
    if (!document.getElementById('categoryInput').value) {
        e.preventDefault();
        alert('Выберите категорию обращения');
        return false;
    }
    var btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '⏳ Отправляем...';
});
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>