<?php
require_once BASE_PATH . '/templates/header.php';
?>

<style>
    .auth-wrap {
        min-height: calc(100vh - 200px);
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: stretch;
        margin: -36px -24px;
        background: var(--surface);
    }

    /* Левая часть — форма */
    .auth-form-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px 80px;
    }

    .auth-form-side .logo-mobile {
        display: none;
        font-weight: 900;
        font-size: 18px;
        letter-spacing: -0.5px;
        margin-bottom: 32px;
    }

    .auth-title {
        font-size: 32px;
        font-weight: 900;
        letter-spacing: -1px;
        margin-bottom: 8px;
        line-height: 1.2;
    }

    .auth-subtitle {
        font-size: 14px;
        color: var(--text-secondary);
        margin-bottom: 32px;
    }

    .auth-field {
        margin-bottom: 18px;
    }

    .auth-field label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 8px;
    }

    .auth-field input {
        width: 100%;
        padding: 16px 18px;
        border: 2px solid var(--border);
        font-size: 15px;
        font-family: inherit;
        background: var(--border-light);
        transition: all 0.2s;
        color: var(--text);
    }

    .auth-field input:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--surface);
    }

    .auth-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        font-size: 13px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .auth-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }

    .auth-checkbox input {
        width: 16px;
        height: 16px;
        accent-color: var(--accent);
    }

    .auth-row a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 700;
    }

    .auth-row a:hover { text-decoration: underline; }

    .auth-submit {
        width: 100%;
        padding: 16px 24px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        background: var(--primary);
        color: white;
        border: none;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.25s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .auth-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }

    .auth-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 24px 0;
        color: var(--text-secondary);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }

    .auth-divider::before,
    .auth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .auth-footer {
        text-align: center;
        font-size: 13px;
        color: var(--text-secondary);
        margin-top: 24px;
    }

    .auth-footer a {
        color: var(--text);
        font-weight: 800;
        text-decoration: none;
        border-bottom: 2px solid var(--accent);
    }

    /* Правая часть — маркетинг */
    .auth-promo-side {
        background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
        color: white;
        padding: 60px 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .auth-promo-side::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 400px;
        height: 400px;
        background: rgba(245, 158, 11, 0.15);
        border-radius: 50%;
    }

    .auth-promo-side::after {
        content: '';
        position: absolute;
        bottom: -150px;
        left: -100px;
        width: 300px;
        height: 300px;
        background: rgba(245, 158, 11, 0.1);
        border-radius: 50%;
    }

    .auth-promo-content {
        position: relative;
        z-index: 1;
    }

    .auth-promo-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: rgba(245, 158, 11, 0.2);
        color: var(--accent);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 24px;
    }

    .auth-promo-title {
        font-size: 34px;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.15;
        margin-bottom: 20px;
    }

    .auth-promo-title .accent {
        color: var(--accent);
    }

    .auth-promo-text {
        font-size: 15px;
        opacity: 0.75;
        line-height: 1.6;
        margin-bottom: 32px;
    }

    .auth-promo-features {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .auth-promo-feature {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
    }

    .auth-promo-feature .check {
        width: 24px;
        height: 24px;
        background: var(--accent);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 13px;
        flex-shrink: 0;
        border-radius: 50%;
    }

    /* Ошибки */
    .auth-error {
        padding: 14px 18px;
        background: #fee2e2;
        border-left: 3px solid var(--danger);
        color: #991b1b;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    /* Мобильная */
    @media (max-width: 900px) {
        .auth-wrap {
            grid-template-columns: 1fr;
            margin: -16px -12px;
        }
        .auth-form-side {
            padding: 40px 24px;
            order: 2;
        }
        .auth-promo-side {
            display: none;
        }
        .auth-form-side .logo-mobile {
            display: block;
        }
        .auth-title { font-size: 26px; }
    }
</style>

<div class="auth-wrap">
    <!-- Форма -->
    <div class="auth-form-side">
        <div class="logo-mobile">AVL LOGISTIC</div>
        
        <h1 class="auth-title">С возвращением 👋</h1>
        <p class="auth-subtitle">Войдите, чтобы управлять заказами</p>

        <?php if (isset($error) && $error): ?>
            <div class="auth-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" id="loginForm">
            <div class="auth-field">
                <label>Email</label>
                <input type="email" name="email" required autofocus
                       placeholder="email@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="auth-field">
                <label>Пароль</label>
                <input type="password" name="password" required
                       placeholder="Минимум 6 символов">
            </div>

            <div class="auth-row">
                <label class="auth-checkbox">
                    <input type="checkbox" name="remember" value="1" checked>
                    <span>Запомнить меня</span>
                </label>
                <a href="/forgot">Забыли пароль?</a>
            </div>

            <button type="submit" class="auth-submit" id="loginBtn">
                🔑 Войти в аккаунт
            </button>
        </form>

        <div class="auth-footer">
            Нет аккаунта? <a href="/register">Зарегистрироваться</a>
        </div>
    </div>

    <!-- Промо -->
    <div class="auth-promo-side">
        <div class="auth-promo-content">
            <div class="auth-promo-badge">● Работает по всей России</div>
            <h2 class="auth-promo-title">
                Перевозки<br>
                <span class="accent">без посредников</span>
            </h2>
            <p class="auth-promo-text">
                Прямая связь грузовладельцев и перевозчиков. Никаких диспетчеров, скрытых комиссий и лишних звонков.
            </p>

            <div class="auth-promo-features">
                <div class="auth-promo-feature">
                    <span class="check">✓</span>
                    <span>Отклики на заказы за 10 минут</span>
                </div>
                <div class="auth-promo-feature">
                    <span class="check">✓</span>
                    <span>Договор и документы автоматом</span>
                </div>
                <div class="auth-promo-feature">
                    <span class="check">✓</span>
                    <span>Проверенные перевозчики и рейтинги</span>
                </div>
                <div class="auth-promo-feature">
                    <span class="check">✓</span>
                    <span>Бесплатно для грузовладельцев</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function() {
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Входим...';
});
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>