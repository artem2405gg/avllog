<?php
// Динамические Title и Description
$currentUrl = 'https://avllogist.ru' . $_SERVER['REQUEST_URI'];
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$pageTitle = "AVL Logistic — платформа грузоперевозок";
$pageDescription = "Найдите перевозчика за 2 минуты. Без посредников. Договор онлайн.";
$ogImage = "https://avllogist.ru/og-image.jpg";

if (strpos($currentPath, '/orders/view') === 0) {
    $pageTitle = "Заказ на перевозку — AVL Logistic";
    $pageDescription = "Детали заказа. Отклики перевозчиков, цены, документы онлайн.";
} elseif (strpos($currentPath, '/orders/create') === 0) {
    $pageTitle = "Создать заказ на перевозку — AVL Logistic";
    $pageDescription = "Создайте заказ за 2 минуты. Получите отклики с ценами.";
} elseif (strpos($currentPath, '/orders/return') === 0) {
    $pageTitle = "Обратные рейсы — AVL Logistic";
    $pageDescription = "Найдите попутный груз. Не ездите пустым.";
} elseif (strpos($currentPath, '/orders') === 0) {
    $pageTitle = "Заказы на грузоперевозки — AVL Logistic";
    $pageDescription = "Актуальные заказы. Найдите попутный груз или разместите заказ.";
} elseif (strpos($currentPath, '/pricing') === 0) {
    $pageTitle = "Тарифы — AVL Logistic";
    $pageDescription = "Тарифы для перевозчиков: Бесплатный, Базовый, Про.";
} elseif (strpos($currentPath, '/register') === 0) {
    $pageTitle = "Регистрация — AVL Logistic";
    $pageDescription = "Бесплатная регистрация. Для грузовладельцев и перевозчиков.";
} elseif (strpos($currentPath, '/login') === 0) {
    $pageTitle = "Вход — AVL Logistic";
    $pageDescription = "Войдите в свой аккаунт.";
} elseif (strpos($currentPath, '/dashboard') === 0) {
    $pageTitle = "Панель управления — AVL Logistic";
    $pageDescription = "Ваша панель управления.";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="грузоперевозки, поиск перевозчика, заказ грузоперевозки, логистика, доставка грузов, межгород">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AVL Logistic">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:locale" content="ru_RU">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"Organization","name":"AVL Logistic","url":"https://avllogist.ru","logo":"https://avllogist.ru/logo.png","telephone":"+7 (921) 541-14-81","address":{"@type":"PostalAddress","addressCountry":"RU"}}
    </script>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#292524">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AVL Logistic">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=109902906', 'ym');
        ym(109902906, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/109902906" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <?php if (!empty($_SESSION['metrika_goal'])): ?>
    <script>
    (function(){try{ym(109902906,'reachGoal','<?= htmlspecialchars($_SESSION['metrika_goal'], ENT_QUOTES) ?>');console.log('[Metrika] goal sent: <?= htmlspecialchars($_SESSION['metrika_goal'], ENT_QUOTES) ?>');}catch(e){console.error('[Metrika] error:',e);}})();
    </script>
    <?php unset($_SESSION['metrika_goal']); ?>
    <?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            /* ============ ФОН ============ */
            --bg: #f5f4f2;
            --surface: #ffffff;

            /* ============ ТЕКСТ ============ */
            --text: #1c1917;
            --text-secondary: #57534e;

            /* ============ ГРАНИЦЫ ============ */
            --border: #d6d3d1;
            --border-light: #e7e5e4;

            /* ============ ОСНОВА ============ */
            --primary: #292524;
            --primary-hover: #1c1917;

            /* ============ АКЦЕНТ ============ */
            --accent: #b91c1c;
            --accent-hover: #991b1b;
            --accent-glow: rgba(185, 28, 28, 0.2);

            /* ============ СЕМАНТИКА ============ */
            --success: #15803d;
            --success-bg: #dcfce7;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
            --info: #1e40af;
            --info-bg: #dbeafe;
            --warning: #a16207;
            --warning-bg: #fef3c7;

            /* ============ ТЕНИ ============ */
            --shadow-sm: 0 1px 3px rgba(28, 25, 23, 0.04);
            --shadow: 0 2px 8px rgba(28, 25, 23, 0.06);
            --shadow-lg: 0 8px 30px rgba(28, 25, 23, 0.08);

            --font-mono: 'SF Mono', 'Cascadia Code', monospace;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        /* Тонкая сетка фона — тёплая */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: linear-gradient(45deg, var(--border) 1px, transparent 1px), linear-gradient(-45deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px; background-position: 0 0, 20px 20px;
            opacity: 0.35; z-index: 0; pointer-events: none;
        }
        /* Верхняя кирпичная полоса */
        body::after {
            content: ''; position: fixed; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--accent), #dc2626, var(--accent));
            background-size: 200% 100%;
            z-index: 9999;
            animation: topStrip 4s linear infinite;
        }
        @keyframes topStrip {
            0% { background-position: 0% 0; }
            100% { background-position: 200% 0; }
        }

        /* ============ NAVBAR ============ */
        .navbar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 64px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 3px;
            z-index: 100;
            transition: box-shadow 0.3s, background 0.3s;
        }
        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(28, 25, 23, 0.06);
        }

        /* Логотип */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            transition: opacity 0.2s;
        }
        .logo:hover { opacity: 0.85; }
        .logo-img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
        }
        @media (min-width: 900px) {
            .logo-img {
                width: 42px;
                height: 42px;
            }
        }
        .logo-text {
            line-height: 1.1;
            display: none;
        }
        .logo-text .main {
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }
        .logo-text .sub {
            font-size: 9px;
            color: var(--text-secondary);
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        @media (min-width: 900px) {
            .logo-text { display: block; }
        }

        /* Основная навигация */
        .nav-main {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
            border-radius: 6px;
        }
        .nav-item:hover {
            color: var(--text);
            background: var(--border-light);
        }
        .nav-item.active {
            color: var(--text);
            background: var(--border-light);
        }
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 12px;
            right: 12px;
            height: 2px;
            background: var(--accent);
        }
        .nav-item .icon { font-size: 15px; }

        /* Кнопка "Создать" для owner'а — акцентная */
        .nav-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: var(--accent);
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: all 0.2s;
            border-radius: 6px;
            margin-left: 4px;
        }
        .nav-cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px var(--accent-glow);
            background: var(--accent-hover);
        }

        /* Правая часть — иконки */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Иконка с бейджем */
        .nav-icon {
            position: relative;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            border-radius: 8px;
            font-size: 17px;
        }
        .nav-icon:hover {
            background: var(--border-light);
            color: var(--text);
        }
        .nav-icon.active {
            background: var(--border-light);
            color: var(--text);
        }
        .nav-icon .badge-count {
            position: absolute;
            top: 2px;
            right: 2px;
            min-width: 16px;
            height: 16px;
            background: var(--danger);
            color: white;
            font-size: 9px;
            font-weight: 800;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid var(--surface);
        }
        .nav-icon .badge-count.hidden { display: none; }

        /* User menu */
        .user-menu-wrap {
            position: relative;
            margin-left: 8px;
        }
        .user-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 12px 4px 4px;
            background: var(--border-light);
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            border-radius: 24px;
        }
        .user-btn:hover {
            background: var(--border);
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .user-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-caret {
            font-size: 10px;
            color: var(--text-secondary);
        }

        /* Выпадающее меню */
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 220px;
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s;
            z-index: 200;
        }
        .user-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .user-dropdown-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-light);
        }
        .user-dropdown-header .name {
            font-weight: 800;
            font-size: 14px;
            line-height: 1.3;
        }
        .user-dropdown-header .email {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-dropdown-header .role {
            display: inline-block;
            margin-top: 6px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--border-light);
            color: var(--text-secondary);
        }
        .user-dropdown-header .role.owner { background: var(--info-bg); color: var(--info); }
        .user-dropdown-header .role.carrier { background: var(--success-bg); color: #065f46; }
        .user-dropdown-header .role.admin { background: #fce7f3; color: #be185d; }

        .user-dropdown-links {
            padding: 6px;
        }
        .user-dropdown-links a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.15s;
            border-radius: 6px;
        }
        .user-dropdown-links a:hover {
            background: var(--border-light);
        }
        .user-dropdown-links a.danger {
            color: var(--danger);
        }
        .user-dropdown-links a.danger:hover {
            background: var(--danger-bg);
        }
        .user-dropdown-links .divider {
            height: 1px;
            background: var(--border-light);
            margin: 6px 0;
        }

        /* Бургер */
        .burger {
            display: none;
            background: transparent;
            border: none;
            font-size: 22px;
            cursor: pointer;
            padding: 8px 12px;
            color: var(--text);
            border-radius: 6px;
            transition: background 0.2s;
        }
        .burger:hover { background: var(--border-light); }

        /* ============ MOBILE MENU ============ */
        .mobile-menu {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--surface);
            z-index: 1000;
            padding: 20px 16px;
            flex-direction: column;
            overflow-y: auto;
            animation: slideIn 0.25s ease-out;
        }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        .mobile-menu.active { display: flex; }
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .mobile-menu-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .mobile-menu-user .avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            border-radius: 50%;
        }
        .mobile-menu-user .info .name {
            font-weight: 800;
            font-size: 14px;
        }
        .mobile-menu-user .info .role {
            font-size: 11px;
            color: var(--text-secondary);
        }
        .mobile-menu-links {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
        }
        .mobile-menu-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: var(--text);
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            border-radius: 8px;
            transition: all 0.15s;
        }
        .mobile-menu-links a:hover, .mobile-menu-links a.active {
            background: var(--border-light);
        }
        .mobile-menu-links a.danger {
            color: var(--danger);
        }
        .mobile-menu-links .icon {
            font-size: 20px;
            width: 28px;
            text-align: center;
        }
        .close-menu {
            background: var(--border-light);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ============ BOTTOM NAV ============ */
        .bottom-nav {
            display: none;
            position: fixed !important;
            bottom: 0 !important;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border);
            z-index: 999;
            padding: 6px 0;
            padding-bottom: max(6px, env(safe-area-inset-bottom));
        }
        .bottom-nav-inner {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2px;
        }
        .bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            padding: 6px 4px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            transition: color 0.2s;
        }
        .bottom-nav a .icon {
            font-size: 20px;
            line-height: 1;
        }
        .bottom-nav a.active {
            color: var(--accent);
        }
        .bottom-nav a .badge-count {
            position: absolute;
            top: 2px;
            right: calc(50% - 18px);
            min-width: 14px;
            height: 14px;
            background: var(--danger);
            color: white;
            font-size: 8px;
            font-weight: 800;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            border: 1.5px solid var(--surface);
        }

        /* ============ CONTAINER ============ */
        .container {
            max-width: 1120px;
            margin: 24px auto;
            padding: 0 24px;
            position: relative;
            z-index: 1;
        }

        /* ============ CARD ============ */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 16px;
            position: relative;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 3px;
            height: 0;
            background: var(--accent);
            transition: height 0.3s ease-out;
        }
        .card:hover::before { height: 32px; }
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        /* ============ BUTTONS ============ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 22px;
            border: 2px solid transparent;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            letter-spacing: 0.3px;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
            border-radius: 6px;
        }
        .btn-primary { background: var(--primary); color: white; border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); box-shadow: 0 6px 20px rgba(28, 25, 23, 0.2); transform: translateY(-1px); }
        .btn-accent { background: var(--accent); color: #ffffff; border-color: var(--accent); font-weight: 800; }
        .btn-accent:hover { background: var(--accent-hover); box-shadow: 0 6px 20px var(--accent-glow); transform: translateY(-1px); }
        .btn-outline { background: transparent; color: var(--text); border-color: var(--border); }
        .btn-outline:hover { border-color: var(--primary); background: var(--border-light); }
        .btn-ghost { background: transparent; color: var(--text-secondary); border: none; }
        .btn-ghost:hover { background: var(--border-light); color: var(--text); }
        .btn-success { background: var(--success); color: white; border-color: var(--success); }
        .btn-danger { background: transparent; color: var(--danger); border-color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }
        .btn-sm { padding: 7px 14px; font-size: 11px; }
        .btn-lg { padding: 15px 30px; font-size: 14px; }
        .btn-block { width: 100%; }

        /* ============ FORMS ============ */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 13px 14px;
            border: 2px solid var(--border);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: var(--border-light);
            border-radius: 6px;
            color: var(--text);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(28, 25, 23, 0.06);
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* ============ ALERTS ============ */
        .alert {
            padding: 13px 16px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
            border-left: 3px solid;
            border-radius: 4px;
        }
        .alert-success { background: var(--success-bg); color: #166534; border-color: var(--success); }
        .alert-error { background: var(--danger-bg); color: #991b1b; border-color: var(--danger); }
        .alert-warning { background: var(--warning-bg); color: #92400e; border-color: var(--warning); }

        /* ============ TEXT ============ */
        h1 { font-size: 26px; font-weight: 900; letter-spacing: -1px; margin-bottom: 8px; }
        h2 { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 16px; }
        h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .text-mono { font-family: var(--font-mono); font-size: 13px; color: var(--text-secondary); }
        .text-muted { color: var(--text-secondary); font-size: 14px; }

        /* ============ BADGE ============ */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid var(--border);
            border-radius: 4px;
        }
        .badge-warning { background: var(--warning-bg); color: #92400e; border-color: var(--warning); }
        .badge-success { background: var(--success-bg); color: #166534; border-color: var(--success); }

        /* ============ INFO TABLE ============ */
        .info-table { width: 100%; }
        .info-table td { padding: 10px 0; border-bottom: 1px solid var(--border-light); }
        .info-table td:first-child {
            color: var(--text-secondary);
            font-weight: 700;
            width: 30%;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* ============ SUPPORT FLOAT ============ */
        .support-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            text-decoration: none;
            z-index: 998;
            box-shadow: 0 4px 20px rgba(28, 25, 23, 0.25);
            transition: all 0.2s;
        }
        .support-float:hover {
            transform: scale(1.1);
            background: var(--accent);
            color: #ffffff;
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 900px) {
            .nav-main { display: none; }
            .nav-right .nav-icon,
            .nav-right .user-menu-wrap { display: none; }
            .burger { display: flex; align-items: center; justify-content: center; }
            .bottom-nav { display: block !important; }
            .container {
                margin: 16px auto;
                padding: 0 12px 90px 12px !important;
            }
            .grid-2 { grid-template-columns: 1fr; gap: 12px; }
            h1 { font-size: 22px; }
            h2 { font-size: 18px; }
            .card { padding: 18px; }
            .btn { padding: 10px 18px; font-size: 12px; }
            .support-float { bottom: 76px; right: 14px; width: 44px; height: 44px; font-size: 20px; }
        }
    </style>
</head>
<body>

<?php
// Проверяем авторизацию и получаем данные для шапки
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = false;
$unreadMessages = 0;
$unreadNotif = 0;
$currentUser = null;

if ($isLoggedIn) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadMessages = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadNotif = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT name, email, role, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = !empty($currentUser['is_admin']);
}

$currentUri = $_SERVER['REQUEST_URI'];
function isActive($prefix) {
    global $currentUri;
    return strpos($currentUri, $prefix) === 0 ? 'active' : '';
}
?>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <a href="/<?= $isLoggedIn ? 'dashboard' : '' ?>" class="logo">
        <img src="/uploads/logo.png" alt="AVL Logistic" class="logo-img">
        <div class="logo-text">
            <div class="main">AVL LOGISTIC</div>
            <div class="sub">Грузоперевозки</div>
        </div>
    </a>

    <?php if ($isLoggedIn): ?>
    <div class="nav-main">
        <a href="/dashboard" class="nav-item <?= isActive('/dashboard') ?>">
            <span class="icon">📊</span> Панель
        </a>
        <a href="/orders" class="nav-item <?= (strpos($currentUri, '/orders') === 0 && strpos($currentUri, '/orders/my') === false && strpos($currentUri, '/orders/create') === false && strpos($currentUri, '/orders/return') === false) ? 'active' : '' ?>">
            <span class="icon">📋</span> Заказы
        </a>
        <a href="/orders/my" class="nav-item <?= isActive('/orders/my') ?>">
            <span class="icon">📦</span> Сделки
        </a>
        <?php if ($_SESSION['user_role'] === 'carrier'): ?>
        <a href="/orders/return" class="nav-item <?= isActive('/orders/return') ?>">
            <span class="icon">🔄</span> Обратные рейсы
        </a>
        <?php endif; ?>
        <a href="/analytics" class="nav-item <?= isActive('/analytics') ?>">
            <span class="icon">📊</span> Аналитика
        </a>
        <?php if ($_SESSION['user_role'] === 'owner'): ?>
        <a href="/orders/create" class="nav-cta">
            <span>➕</span> Создать заказ
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="nav-right">
        <?php if ($isLoggedIn): ?>
        <a href="/messages" class="nav-icon <?= isActive('/messages') ?>" title="Чат">
            💬
            <span class="badge-count msg-counter <?= $unreadMessages === 0 ? 'hidden' : '' ?>"><?= $unreadMessages ?></span>
        </a>
        <a href="/notifications" class="nav-icon <?= isActive('/notifications') ?>" title="Уведомления">
            🔔
            <span class="badge-count notif-counter <?= $unreadNotif === 0 ? 'hidden' : '' ?>"><?= $unreadNotif ?></span>
        </a>
        <div class="user-menu-wrap">
            <button class="user-btn" onclick="toggleUserMenu(event)">
                <div class="user-avatar"><?= mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1)) ?></div>
                <span class="user-name"><?= htmlspecialchars(mb_substr($currentUser['name'] ?? 'Пользователь', 0, 12)) ?></span>
                <span class="user-caret">▼</span>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <div class="user-dropdown-header">
                    <div class="name"><?= htmlspecialchars($currentUser['name'] ?? 'Пользователь') ?></div>
                    <div class="email"><?= htmlspecialchars($currentUser['email'] ?? '') ?></div>
                    <?php if ($isAdmin): ?>
                        <span class="role admin">🛡️ Администратор</span>
                    <?php elseif ($currentUser['role'] === 'owner'): ?>
                        <span class="role owner">🏢 Грузовладелец</span>
                    <?php else: ?>
                        <span class="role carrier">🚛 Перевозчик</span>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown-links">
                    <a href="/profile">👤 Мой профиль</a>
                    <a href="/orders/my">📦 Мои сделки</a>
                    <a href="/invoices">🧾 Счета</a>
                    <?php if ($_SESSION['user_role'] === 'carrier'): ?>
                        <a href="/pricing">💎 Тарифы</a>
                        <a href="/orders/return">🔄 Обратные рейсы</a>
                    <?php endif; ?>
                    <a href="/support">🎧 Поддержка</a>
                    <?php if ($isAdmin): ?>
                        <div class="divider"></div>
                        <a href="/admin">🛡️ Админ-панель</a>
                    <?php endif; ?>
                    <div class="divider"></div>
                    <a href="/logout" class="danger">🚪 Выйти</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <a href="/login" class="nav-item">Вход</a>
        <a href="/register?role=owner" class="btn btn-accent btn-sm" style="padding:9px 16px;">Регистрация</a>
        <?php endif; ?>
        <button class="burger" onclick="document.getElementById('mobileMenu').classList.add('active')">☰</button>
    </div>
</nav>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <?php if ($isLoggedIn): ?>
        <div class="mobile-menu-user">
            <div class="avatar"><?= mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1)) ?></div>
            <div class="info">
                <div class="name"><?= htmlspecialchars($currentUser['name'] ?? 'Пользователь') ?></div>
                <div class="role">
                    <?= $isAdmin ? '🛡️ Админ' : ($currentUser['role'] === 'owner' ? '🏢 Грузовладелец' : '🚛 Перевозчик') ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <span style="font-weight:900; font-size:16px; letter-spacing:2px;">AVL LOGISTIC</span>
        <?php endif; ?>
        <button class="close-menu" onclick="closeMobileMenu()">✕</button>
    </div>
    
    <div class="mobile-menu-links">
        <?php if ($isLoggedIn): ?>
            <a href="/dashboard" class="<?= isActive('/dashboard') ?>">
                <span class="icon">📊</span> Панель
            </a>
            <a href="/orders" class="<?= isActive('/orders') ?>">
                <span class="icon">📋</span> Заказы
            </a>
            <?php if ($_SESSION['user_role'] === 'owner'): ?>
            <a href="/orders/create">
                <span class="icon">➕</span> Создать заказ
            </a>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] === 'carrier'): ?>
            <a href="/orders/return" class="<?= isActive('/orders/return') ?>">
                <span class="icon">🔄</span> Обратные рейсы
            </a>
            <?php endif; ?>
            <a href="/analytics" class="<?= isActive('/analytics') ?>">
                <span class="icon">📊</span> Аналитика
            </a>
            <a href="/orders/my" class="<?= isActive('/orders/my') ?>">
                <span class="icon">📦</span> Мои сделки
            </a>
            <a href="/messages" class="<?= isActive('/messages') ?>">
                <span class="icon">💬</span> Чаты <?php if ($unreadMessages > 0): ?><span style="background:var(--danger); color:white; font-size:10px; padding:2px 7px; border-radius:10px; margin-left:auto;"><?= $unreadMessages ?></span><?php endif; ?>
            </a>
            <a href="/notifications" class="<?= isActive('/notifications') ?>">
                <span class="icon">🔔</span> Уведомления <?php if ($unreadNotif > 0): ?><span style="background:var(--danger); color:white; font-size:10px; padding:2px 7px; border-radius:10px; margin-left:auto;"><?= $unreadNotif ?></span><?php endif; ?>
            </a>
            <a href="/invoices">
                <span class="icon">🧾</span> Счета
            </a>
            <?php if ($_SESSION['user_role'] === 'carrier'): ?>
            <a href="/pricing">
                <span class="icon">💎</span> Тарифы
            </a>
            <?php endif; ?>
            <a href="/profile">
                <span class="icon">👤</span> Профиль
            </a>
            <a href="/support">
                <span class="icon">🎧</span> Поддержка
            </a>
            <?php if ($isAdmin): ?>
                <a href="/admin" style="color:var(--accent);">
                    <span class="icon">🛡️</span> Админ-панель
                </a>
            <?php endif; ?>
            <a href="/logout" class="danger">
                <span class="icon">🚪</span> Выйти
            </a>
        <?php else: ?>
            <a href="/login">
                <span class="icon">🔑</span> Войти
            </a>
            <a href="/register?role=owner">
                <span class="icon">📝</span> Регистрация
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- BOTTOM NAV (mobile) -->
<?php if ($isLoggedIn && strpos($currentUri, '/chat') === false): ?>
<div class="bottom-nav">
    <div class="bottom-nav-inner">
        <a href="/dashboard" class="<?= isActive('/dashboard') ?>">
            <span class="icon">📊</span>
            Панель
        </a>
        <a href="/orders" class="<?= (strpos($currentUri, '/orders') === 0 && strpos($currentUri, '/orders/my') === false) ? 'active' : '' ?>">
            <span class="icon">📋</span>
            Заказы
        </a>
        <?php if ($_SESSION['user_role'] === 'owner'): ?>
        <a href="/orders/create" style="color:var(--accent);">
            <span class="icon">➕</span>
            Создать
        </a>
        <?php else: ?>
        <a href="/orders/return" class="<?= isActive('/orders/return') ?>">
            <span class="icon">🔄</span>
            Обратные
        </a>
        <?php endif; ?>
        <a href="/messages" class="<?= isActive('/messages') ?>">
            <span class="icon">💬</span>
            Чаты
            <span class="badge-count msg-counter <?= $unreadMessages === 0 ? 'hidden' : '' ?>"><?= $unreadMessages ?></span>
        </a>
        <a href="/orders/my" class="<?= isActive('/orders/my') ?>">
            <span class="icon">📦</span>
            Сделки
        </a>
    </div>
</div>
<?php endif; ?>

<!-- SUPPORT FLOAT -->
<a href="/support" class="support-float" title="Техподдержка">🎧</a>

<!-- CONTAINER START -->
<div class="container">

<script>
function toggleUserMenu(e) {
    e.stopPropagation();
    var dd = document.getElementById('userDropdown');
    if (dd) dd.classList.toggle('active');
}

document.addEventListener('click', function(e) {
    var dd = document.getElementById('userDropdown');
    if (dd && dd.classList.contains('active')) {
        if (!e.target.closest('.user-menu-wrap')) {
            dd.classList.remove('active');
        }
    }
});

function closeMobileMenu() {
    document.getElementById('mobileMenu').classList.remove('active');
}

window.addEventListener('scroll', function() {
    var n = document.getElementById('navbar');
    if (window.scrollY > 10) n.classList.add('scrolled');
    else n.classList.remove('scrolled');
});

function updateCounters() {
    fetch('/api/counters', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.querySelectorAll('.msg-counter').forEach(function(b) {
                if (data.messages > 0) { b.textContent = data.messages; b.classList.remove('hidden'); }
                else { b.classList.add('hidden'); }
            });
            document.querySelectorAll('.notif-counter').forEach(function(b) {
                if (data.notifications > 0) { b.textContent = data.notifications; b.classList.remove('hidden'); }
                else { b.classList.add('hidden'); }
            });
        })
        .catch(function() {});
}
<?php if ($isLoggedIn): ?>
setInterval(updateCounters, 30000);
<?php endif; ?>
</script>