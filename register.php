<?php
require_once BASE_PATH . '/templates/header.php';

// Активная роль
$activeRole = $_GET['role'] ?? $_POST['role'] ?? 'owner';
if (!in_array($activeRole, ['owner', 'carrier'])) $activeRole = 'owner';
?>

<style>
    .reg-wrap {
        min-height: calc(100vh - 200px);
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: stretch;
        margin: -36px -24px;
        background: var(--surface);
    }

    /* Форма */
    .reg-form-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 40px 80px;
    }

    .reg-form-side .logo-mobile {
        display: none;
        font-weight: 900;
        font-size: 18px;
        letter-spacing: -0.5px;
        margin-bottom: 24px;
    }

    /* Переключатель роли */
    .role-switch {
        display: flex;
        background: var(--border-light);
        padding: 4px;
        margin-bottom: 24px;
        gap: 4px;
    }
    .role-btn {
        flex: 1;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 800;
        background: transparent;
        border: none;
        cursor: pointer;
        font-family: inherit;
        color: var(--text-secondary);
        transition: all 0.25s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .role-btn.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .role-btn:not(.active):hover {
        color: var(--text);
    }

    .reg-title {
        font-size: 26px;
        font-weight: 900;
        letter-spacing: -0.8px;
        margin-bottom: 6px;
        line-height: 1.2;
    }

    .reg-subtitle {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 24px;
    }

    /* Поля в 2 колонки */
    .reg-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .reg-fields .full {
        grid-column: 1 / -1;
    }

    .reg-field label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }

    .reg-field input {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--border);
        font-size: 15px;
        font-family: inherit;
        background: var(--border-light);
        transition: all 0.2s;
        color: var(--text);
    }

    .reg-field input:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--surface);
    }

    .reg-field .hint {
        font-size: 11px;
        color: var(--text-secondary);
        margin-top: 5px;
    }

    /* Согласие */
    .reg-agree {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin: 20px 0;
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.5;
    }
    .reg-agree input {
        width: 16px;
        height: 16px;
        margin-top: 2px;
        accent-color: var(--accent);
        flex-shrink: 0;
    }
    .reg-agree a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 700;
    }

    .reg-submit {
        width: 100%;
        padding: 16px 24px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        background: var(--success);
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

    .reg-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }

    .reg-footer {
        text-align: center;
        font-size: 13px;
        color: var(--text-secondary);
        margin-top: 20px;
    }

    .reg-footer a {
        color: var(--text);
        font-weight: 800;
        text-decoration: none;
        border-bottom: 2px solid var(--accent);
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

    /* Промо */
    .reg-promo-side {
        background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
        color: white;
        padding: 40px 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .reg-promo-side::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 400px;
        height: 400px;
        background: rgba(245, 158, 11, 0.15);
        border-radius: 50%;
    }

    .reg-promo-content {
        position: relative;
        z-index: 1;
    }

    .reg-promo-badge {
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

    .reg-promo-title {
        font-size: 30px;
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.15;
        margin-bottom: 20px;
    }

    .reg-promo-title .accent {
        color: var(--accent);
    }

    .reg-promo-text {
        font-size: 14px;
        opacity: 0.75;
        line-height: 1.6;
        margin-bottom: 28px;
    }

    /* Что вы получите */
    .benefits-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .benefit-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .benefit-icon {
        width: 36px;
        height: 36px;
        background: var(--accent);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        border-radius: 8px;
    }

    .benefit-text strong {
        display: block;
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 2px;
    }

    .benefit-text span {
        font-size: 12px;
        opacity: 0.7;
        line-height: 1.4;
    }

    /* Мобильная */
    @media (max-width: 900px) {
        .reg-wrap {
            grid-template-columns: 1fr;
            margin: -16px -12px;
        }
        .reg-form-side {
            padding: 32px 20px;
            order: 2;
        }
        .reg-promo-side { display: none; }
        .reg-form-side .logo-mobile { display: block; }
        .reg-title { font-size: 22px; }
        .reg-fields { grid-template-columns: 1fr; }
    }
</style>

<div class="reg-wrap">
    <!-- Форма -->
    <div class="reg-form-side">
        <div class="logo-mobile">AVL LOGISTIC</div>

        <!-- Переключатель роли -->
        <div class="role-switch">
            <button type="button" class="role-btn <?= $activeRole === 'owner' ? 'active' : '' ?>" onclick="switchRole('owner')">
                📦 Я грузовладелец
            </button>
            <button type="button" class="role-btn <?= $activeRole === 'carrier' ? 'active' : '' ?>" onclick="switchRole('carrier')">
                🚛 Я перевозчик
            </button>
        </div>

        <!-- Заголовок -->
        <div id="roleHeader">
            <?php if ($activeRole === 'owner'): ?>
                <h1 class="reg-title">Размещайте заказы бесплатно</h1>
                <p class="reg-subtitle">Найдите проверенного перевозчика за 2 минуты</p>
            <?php else: ?>
                <h1 class="reg-title">Получайте заказы без посредников</h1>
                <p class="reg-subtitle">30 дней бесплатного доступа ко всем заказам</p>
            <?php endif; ?>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register" id="regForm">
            <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($activeRole) ?>">

            <div class="reg-fields">
                <div class="reg-field full">
                    <label>Имя <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="name" required placeholder="Иван Петров"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="reg-field">
                    <label>Email <span style="color:var(--danger);">*</span></label>
                    <input type="email" name="email" required placeholder="email@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="reg-field">
                    <label>Телефон</label>
                    <input type="tel" name="phone" placeholder="+7 (___) ___-__-__"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="reg-field full">
                    <label>Пароль <span style="color:var(--danger);">*</span></label>
                    <input type="password" name="password" required minlength="6"
                           placeholder="Минимум 6 символов">
                    <div class="hint">Придумайте надёжный пароль</div>
                </div>

                <!-- Поля для owner'а -->
                <div class="reg-field" data-role="owner" style="<?= $activeRole !== 'owner' ? 'display:none;' : '' ?>">
                    <label>Компания</label>
                    <input type="text" name="company_name" placeholder="ООО «Логистик»"
                           value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                </div>

                <div class="reg-field" data-role="owner" style="<?= $activeRole !== 'owner' ? 'display:none;' : '' ?>">
                    <label>ИНН <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="inn" placeholder="1234567890" maxlength="12"
                           value="<?= htmlspecialchars($_POST['inn'] ?? '') ?>">
                    <div class="hint">Обязательно для создания заказов</div>
                </div>

                <!-- Поля для carrier'а -->
                <div class="reg-field" data-role="carrier" style="<?= $activeRole !== 'carrier' ? 'display:none;' : '' ?>">
                    <label>Компания / ИП</label>
                    <input type="text" name="company_name" placeholder="ИП Иванов / ООО «Транс»"
                           value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                </div>

                <div class="reg-field" data-role="carrier" style="<?= $activeRole !== 'carrier' ? 'display:none;' : '' ?>">
                    <label>ИНН</label>
                    <input type="text" name="inn" placeholder="1234567890" maxlength="12"
                           value="<?= htmlspecialchars($_POST['inn'] ?? '') ?>">
                </div>
            </div>

            <!--
                АКЦЕПТ ПУБЛИЧНОЙ ОФЕРТЫ
                — name="accept_offer" уходит на бэкенд в register()
                — value="1" для явной проверки в PHP
                — при ошибке сохраняем состояние галочки
            -->
            <label class="reg-agree">
                <input type="checkbox" name="accept_offer" value="1" required
                       <?= !empty($_POST['accept_offer']) ? 'checked' : '' ?>>
                <span>
                    Я принимаю условия
                    <a href="/page/terms" target="_blank">Публичной оферты</a>
                    (редакция <?= htmlspecialchars(OFFER_VERSION) ?> от <?= date('d.m.Y', strtotime(OFFER_DATE)) ?>)
                    и <a href="/page/privacy" target="_blank">Политики конфиденциальности</a>.
                    С момента регистрации оферта считается акцептованной в полном объёме.
                </span>
            </label>

            <button type="submit" class="reg-submit" id="regBtn">
                🚀 Зарегистрироваться
            </button>
        </form>

        <div class="reg-footer">
            Уже есть аккаунт? <a href="/login">Войти</a>
        </div>
    </div>

    <!-- Промо -->
    <div class="reg-promo-side">
        <div class="reg-promo-content">
            <div class="reg-promo-badge">● Работает по всей России</div>

            <div id="promoContent">
                <?php if ($activeRole === 'owner'): ?>
                    <h2 class="reg-promo-title">
                        Заказ за 2 минуты<br>
                        <span class="accent">Бесплатно навсегда</span>
                    </h2>
                    <p class="reg-promo-text">
                        Опубликуйте груз — перевозчики сами предложат цены. Выбирайте лучшего и контролируйте перевозку онлайн.
                    </p>

                    <div class="benefits-list">
                        <div class="benefit-item">
                            <div class="benefit-icon">📦</div>
                            <div class="benefit-text">
                                <strong>Заказ за 2 минуты</strong>
                                <span>Укажите маршрут, груз, бюджет — публикуйте</span>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">💰</div>
                            <div class="benefit-text">
                                <strong>Бесплатно для вас</strong>
                                <span>Никаких комиссий и подписок для грузовладельцев</span>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">📄</div>
                            <div class="benefit-text">
                                <strong>Документы автоматом</strong>
                                <span>Договор, ТН, акт — формируются онлайн</span>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">⭐</div>
                            <div class="benefit-text">
                                <strong>Проверенные перевозчики</strong>
                                <span>С рейтингом, отзывами и историей сделок</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <h2 class="reg-promo-title">
                        Заказы без посредников<br>
                        <span class="accent">30 дней бесплатно</span>
                    </h2>
                    <p class="reg-promo-text">
                        Получите доступ ко всем заказам. Откликайтесь, получайте выгодные сделки и работайте напрямую с грузовладельцами.
                    </p>

                    <div class="benefits-list">
                        <div class="benefit-item">
                            <div class="benefit-icon">📋</div>
                            <div class="benefit-text">
                                <strong>Все заказы в одном окне</strong>
                                <span>Ежедневно 50+ новых заявок по всей России</span>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">🎁</div>
                            <div class="benefit-text">
                                <strong>30 дней бесплатно</strong>
                                <span>Пробный период на все функции без оплаты</span>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">💰</div>
                            <div class="benefit-text">
                                <strong>Прямые выплаты</strong>
                                <span>Оплата напрямую от грузовладельца</span>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">📄</div>
                            <div class="benefit-text">
                                <strong>Документы автоматом</strong>
                                <span>Договор, ТН, акт — не надо печатать</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchRole(role) {
    // Переключаем кнопки
    document.querySelectorAll('.role-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    event.currentTarget.classList.add('active');

    // Обновляем hidden input
    document.getElementById('roleInput').value = role;

    // Показываем нужные поля
    document.querySelectorAll('[data-role]').forEach(function(el) {
        el.style.display = el.dataset.role === role ? '' : 'none';
    });

    // Обновляем заголовок
    var header = document.getElementById('roleHeader');

    if (role === 'owner') {
        header.innerHTML = '<h1 class="reg-title">Размещайте заказы бесплатно</h1><p class="reg-subtitle">Найдите проверенного перевозчика за 2 минуты</p>';
    } else {
        header.innerHTML = '<h1 class="reg-title">Получайте заказы без посредников</h1><p class="reg-subtitle">30 дней бесплатного доступа ко всем заказам</p>';
    }

    // Обновляем URL без перезагрузки
    var url = new URL(window.location.href);
    url.searchParams.set('role', role);
    history.replaceState({}, '', url);
}
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>