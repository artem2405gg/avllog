<?php
require_once BASE_PATH . '/templates/header.php';

$pageTitle = 'Политика конфиденциальности — AVL Logistic';
$pageDescription = 'Политика обработки персональных данных платформы AVL Logistic';
?>

<style>
    /* Те же стили что и в page_terms.php */
    .legal-page { max-width: 900px; margin: 0 auto; padding: 20px 0 60px; }
    .legal-hero {
        background: linear-gradient(135deg, var(--surface), var(--border-light));
        border: 1px solid var(--border);
        padding: 40px 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .legal-hero::before {
        content: '';
        position: absolute;
        top: -50px; right: -50px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(245,158,11,0.08) 0%, transparent 70%);
    }
    .legal-hero .tag {
        display: inline-block;
        padding: 6px 14px;
        background: #fffbeb;
        color: #92400e;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 16px;
    }
    .legal-hero h1 { font-size: 32px; font-weight: 900; letter-spacing: -1px; line-height: 1.15; margin-bottom: 12px; }
    .legal-hero .meta { font-size: 13px; color: var(--text-secondary); }
    .legal-content { background: var(--surface); border: 1px solid var(--border); padding: 40px; }
    .legal-content h2 {
        font-size: 20px; font-weight: 800;
        margin-top: 32px; margin-bottom: 14px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--border-light);
    }
    .legal-content h2:first-child { margin-top: 0; }
    .legal-content p { font-size: 14px; line-height: 1.75; margin-bottom: 12px; }
    .legal-content ul, .legal-content ol { padding-left: 24px; margin-bottom: 14px; }
    .legal-content li { font-size: 14px; line-height: 1.7; margin-bottom: 6px; }
    .legal-content strong { font-weight: 800; }
    .legal-content a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .legal-content a:hover { text-decoration: underline; }
    .legal-content .highlight {
        background: #fffbeb;
        border-left: 3px solid var(--accent);
        padding: 14px 18px;
        margin: 16px 0;
        font-size: 13px;
        line-height: 1.7;
    }
    @media (max-width: 768px) {
        .legal-hero { padding: 24px 20px; }
        .legal-hero h1 { font-size: 22px; }
        .legal-content { padding: 24px 20px; }
        .legal-content h2 { font-size: 17px; }
    }
</style>

<div class="legal-page">
    <div class="legal-hero">
        <div class="tag">Юридические документы</div>
        <h1>Политика конфиденциальности</h1>
        <div class="meta">
            Политика обработки персональных данных · 
            Редакция от <?= date('d.m.Y') ?>
        </div>
    </div>

    <div class="legal-content">
        
        <h2>1. Общие положения</h2>
        <p>
            Настоящая Политика конфиденциальности (далее — «Политика») 
            определяет порядок обработки и защиты персональных данных 
            пользователей платформы <a href="https://avllogist.ru">avllogist.ru</a> 
            (далее — «Платформа»).
        </p>
        <p>
            Оператор персональных данных: <strong>ИП Гусева Анастасия Максимовна</strong> 
            (ИНН 352403547008, ОГРНИП 324350000057980).
        </p>

        <h2>2. Какие данные мы собираем</h2>
        
        <h3>2.1. Данные, которые вы предоставляете:</h3>
        <ul>
            <li>ФИО, название компании;</li>
            <li>Email, телефон;</li>
            <li>ИНН, ОГРН/ОГРНИП;</li>
            <li>Пароль от аккаунта;</li>
            <li>Банковские реквизиты (для перевозчиков);</li>
            <li>Юридический адрес.</li>
        </ul>

        <h3>2.2. Данные, которые собираются автоматически:</h3>
        <ul>
            <li>IP-адрес, тип устройства, браузер;</li>
            <li>Cookies и данные сессии;</li>
            <li>История действий на Платформе;</li>
            <li>Геолокация (при смене статусов перевозчиком — с согласия).</li>
        </ul>

        <h2>3. Цели обработки</h2>
        <ul>
            <li>Регистрация и авторизация на Платформе;</li>
            <li>Организация Сделок между грузовладельцами и перевозчиками;</li>
            <li>Формирование документов;</li>
            <li>Обработка платежей;</li>
            <li>Уведомления о событиях на Платформе;</li>
            <li>Улучшение качества сервиса;</li>
            <li>Соблюдение требований законодательства РФ.</li>
        </ul>

        <h2>4. Передача данных третьим лицам</h2>
        <p>
            Ваши данные могут быть переданы третьим лицам в следующих случаях:
        </p>
        <ul>
            <li><strong>Платёжные сервисы</strong> (Т-Банк, Tinkoff Kassa) — 
                для обработки платежей;</li>
            <li><strong>Государственные органы</strong> — по официальному запросу;</li>
            <li><strong>Другие Пользователи</strong> — в объёме, необходимом 
                для совершения Сделки (имя, компания, ИНН, рейтинг).</li>
        </ul>
        <p>
            <strong>Мы не продаём и не передаём персональные данные 
            третьим лицам в маркетинговых целях.</strong>
        </p>

        <h2>5. Cookies и аналитика</h2>
        <p>
            Мы используем cookies для:
        </p>
        <ul>
            <li>сохранения сессии и авторизации;</li>
            <li>аналитики (Яндекс.Метрика);</li>
            <li>улучшения работы Платформы.</li>
        </ul>
        <p>
            Вы можете отключить cookies в настройках браузера, но это может 
            нарушить работу Платформы.
        </p>

        <h2>6. Хранение и защита данных</h2>
        <ul>
            <li>Данные хранятся на серверах в РФ;</li>
            <li>Пароли хранятся в виде хеша (bcrypt);</li>
            <li>Соединение защищено HTTPS;</li>
            <li>Доступ к данным ограничен сотрудниками Администрации.</li>
        </ul>
        <p>
            Срок хранения персональных данных — <strong>до удаления аккаунта 
            Пользователем</strong> + 3 года (для соблюдения требований 
            финансового законодательства).
        </p>

        <h2>7. Ваши права</h2>
        <p>
            В соответствии с ФЗ-152 «О персональных данных» вы имеете право:
        </p>
        <ul>
            <li>получить информацию о том, как обрабатываются ваши данные;</li>
            <li>потребовать исправления недостоверных данных;</li>
            <li>отозвать согласие на обработку;</li>
            <li>удалить аккаунт и персональные данные.</li>
        </ul>
        <p>
            Для реализации прав — напишите на 
            <a href="mailto:info@avllogist.ru">info@avllogist.ru</a>.
        </p>

        <h2>8. Геолокация</h2>
        <p>
            При смене статуса заказа Перевозчиком через мобильное устройство 
            мы можем запрашивать геолокацию. Это нужно для:
        </p>
        <ul>
            <li>фиксации фактического места события;</li>
            <li>защиты обеих сторон Сделки в спорных ситуациях.</li>
        </ul>
        <p>
            Геолокация запрашивается только в момент смены статуса и 
            сохраняется в истории заказа.
        </p>

        <h2>9. Изменение Политики</h2>
        <p>
            Мы вправе изменять Политику. Новая редакция вступает в силу 
            с момента публикации. Продолжение использования Платформы 
            означает согласие с изменениями.
        </p>

        <h2>10. Контакты</h2>
        <p>
            <strong>ИП Гусева Анастасия Максимовна</strong><br>
            ИНН: 352403547008<br>
            ОГРНИП: 324350000057980<br>
            Email: <a href="mailto:info@avllogist.ru">info@avllogist.ru</a><br>
            Телефон: <a href="tel:+79215411481">+7 (921) 541-14-81</a>
        </p>

        <p style="margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border-light); font-size: 12px; color: var(--text-secondary);">
            Редакция от <?= date('d.m.Y') ?>.
        </p>
    </div>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>