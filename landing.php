<?php
/**
 * templates/landing.php — главная страница для неавторизованных
 * Отдельный лендинг с собственным head, без header.php
 */
$pageTitle = "AVL Logistic — заказать перевозку груза за 2 минуты";
$pageDescription = "Найдите проверенного перевозчика для вашего груза. Бесплатно для грузовладельцев. Договор и документы онлайн. Отклики за 10 минут.";
$currentUrl = 'https://avllogist.ru' . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="грузоперевозки, заказать перевозку, найти перевозчика, доставка грузов, межгород, фура, логистика">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AVL Logistic">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
    <meta property="og:image" content="https://avllogist.ru/og-image.jpg">
    <meta property="og:locale" content="ru_RU">

    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
        ym(109902906, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/109902906" style="position:absolute; left:-9999px;" alt="" /></div></noscript>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            /* ============ ОСНОВА (графит) ============ */
            --black: #292524;
            --white: #ffffff;
            --gray-50: #f5f4f2;
            --gray-100: #e7e5e4;
            --gray-200: #d6d3d1;
            --gray-300: #a8a29e;
            --gray-400: #78716c;
            --gray-500: #57534e;
            --gray-600: #44403c;
            --gray-700: #292524;
            --gray-900: #1c1917;

            /* ============ АКЦЕНТ — КИРПИЧ ============ */
            --accent: #b91c1c;
            --accent-dark: #991b1b;
            --accent-glow: rgba(185, 28, 28, 0.2);

            /* ============ СЕМАНТИКА ============ */
            --success: #15803d;
            --danger: #b91c1c;

            /* ============ ТЕКСТ И ГРАНИЦЫ ============ */
            --border: #d6d3d1;
            --text: #1c1917;
            --text-secondary: #57534e;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: var(--white);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            z-index: 100;
            padding: 16px 0;
        }
        .navbar-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
        }
        .logo-mark {
            width: 44px;
            height: 44px;
            background: var(--black);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
            position: relative;
        }
        .logo-mark::after {
            content: '';
            position: absolute;
            bottom: -3px;
            right: -3px;
            width: 14px;
            height: 14px;
            background: var(--accent);
        }
        .logo-text .main {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 1.5px;
            line-height: 1;
        }
        .logo-text .sub {
            font-size: 9px;
            color: var(--text-secondary);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .nav-actions a {
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            padding: 10px 16px;
            transition: all 0.2s;
        }
        .nav-link { color: var(--text-secondary); }
        .nav-link:hover { color: var(--text); }
        .nav-btn {
            background: var(--black);
            color: white;
            border: 2px solid var(--black);
        }
        .nav-btn:hover { background: var(--accent); border-color: var(--accent); color: #ffffff; }

        /* Notification strip */
        .notification-strip {
            background: linear-gradient(90deg, #fee2e2, #fef2f2);
            padding: 12px 24px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #991b1b;
            border-bottom: 1px solid #fecaca;
        }
        .notification-strip a {
            color: #991b1b;
            font-weight: 800;
            text-decoration: underline;
        }

        /* Hero */
        .hero {
            padding: 80px 0 60px;
            position: relative;
            background: linear-gradient(180deg, var(--white) 0%, var(--gray-50) 100%);
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(185,28,28,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-inner {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
            border: 1px solid #fecaca;
        }
        .hero-badge .dot {
            width: 6px;
            height: 6px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .hero h1 {
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1.05;
            margin-bottom: 20px;
        }
        .hero h1 .accent {
            background: linear-gradient(180deg, transparent 60%, rgba(185,28,28,0.35) 60%);
            padding: 0 4px;
        }
        .hero p {
            font-size: clamp(15px, 2vw, 19px);
            color: var(--text-secondary);
            max-width: 620px;
            margin: 0 auto 32px;
            line-height: 1.6;
        }
        .hero-cta {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 18px 32px;
            background: var(--accent);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            border: 2px solid var(--accent);
            transition: all 0.25s;
            box-shadow: 0 8px 24px var(--accent-glow);
        }
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            background: var(--accent-dark);
            box-shadow: 0 12px 32px rgba(185,28,28,0.4);
        }
        .btn-hero-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 18px 32px;
            background: var(--white);
            color: var(--text);
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            border: 2px solid var(--border);
            transition: all 0.25s;
        }
        .btn-hero-secondary:hover {
            border-color: var(--black);
            transform: translateY(-3px);
        }
        .hero-note {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .hero-note strong { color: var(--text); font-weight: 700; }

        /* Stats strip */
        .stats-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            padding: 32px 40px;
            background: var(--white);
            border: 1px solid var(--border);
            margin-top: 60px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .stat-item { text-align: center; }
        .stat-value {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -1px;
            color: var(--text);
            font-family: 'SF Mono', monospace;
        }
        .stat-value .accent { color: var(--accent); }
        .stat-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        /* Секции */
        .section { padding: 80px 0; }
        .section-gray { background: var(--gray-50); }
        .section-dark { background: var(--black); color: white; }
        .section-head {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 56px;
        }
        .section-head .tag {
            display: inline-block;
            padding: 6px 14px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .section-dark .section-head .tag {
            background: rgba(185,28,28,0.2);
            color: #fca5a5;
        }
        .section-head h2 {
            font-size: clamp(26px, 3.5vw, 40px);
            font-weight: 900;
            letter-spacing: -1.5px;
            line-height: 1.15;
            margin-bottom: 12px;
        }
        .section-head p {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        .section-dark .section-head p { color: rgba(255,255,255,0.7); }

        /* Как это работает */
        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .step-card {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 32px 28px;
            position: relative;
            transition: all 0.3s;
        }
        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(28,25,23,0.08);
            border-color: var(--accent);
        }
        .step-num {
            position: absolute;
            top: -20px;
            left: 28px;
            width: 48px;
            height: 48px;
            background: var(--accent);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 900;
        }
        .step-icon {
            font-size: 40px;
            margin-bottom: 16px;
            margin-top: 16px;
        }
        .step-title {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 10px;
            letter-spacing: -0.3px;
        }
        .step-text {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Сравнение */
        .compare-table {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .compare-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .compare-table th {
            padding: 16px 20px;
            background: var(--gray-50);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            text-align: left;
            border-bottom: 2px solid var(--border);
        }
        .compare-table th.highlight {
            background: var(--accent);
            color: #ffffff;
        }
        .compare-table td {
            padding: 16px 20px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
        }
        .compare-table tr:last-child td { border-bottom: none; }
        .compare-table td.highlight {
            background: #fef2f2;
            font-weight: 700;
        }
        .compare-table .check { color: var(--success); font-weight: 900; }
        .compare-table .cross { color: var(--danger); font-weight: 900; }

        /* Преимущества */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .feature-card {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 28px;
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(28,25,23,0.06);
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 16px;
        }
        .feature-title {
            font-size: 16px;
            font-weight: 900;
            margin-bottom: 8px;
            letter-spacing: -0.2px;
        }
        .feature-text {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Инструменты доверия */
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .trust-card {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 24px;
            display: flex;
            gap: 16px;
            transition: all 0.3s;
        }
        .section-dark .trust-card {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
        .trust-card:hover {
            border-color: var(--accent);
        }
        .trust-icon {
            width: 48px;
            height: 48px;
            background: #fef2f2;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .section-dark .trust-icon {
            background: rgba(185,28,28,0.2);
            color: #fca5a5;
        }
        .trust-body { flex: 1; }
        .trust-problem {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #fca5a5;
            margin-bottom: 6px;
        }
        .trust-title {
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 6px;
            letter-spacing: -0.2px;
        }
        .section-dark .trust-title { color: white; }
        .trust-text {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .section-dark .trust-text { color: rgba(255,255,255,0.65); }

        /* Страхование */
        .insurance-banner {
            max-width: 1000px;
            margin: 0 auto;
            background: linear-gradient(135deg, var(--black), #1c1917);
            color: white;
            padding: 48px 56px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 32px;
            position: relative;
            overflow: hidden;
        }
        .insurance-banner::before {
            content: '🛡️';
            position: absolute;
            right: -30px;
            bottom: -40px;
            font-size: 240px;
            opacity: 0.05;
        }
        .insurance-content { position: relative; z-index: 1; max-width: 600px; }
        .insurance-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(185,28,28,0.25);
            color: #fca5a5;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .insurance-title {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.8px;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        .insurance-title .accent { color: #fca5a5; }
        .insurance-text {
            font-size: 15px;
            color: rgba(255,255,255,0.7);
            line-height: 1.6;
        }
        .insurance-cta { position: relative; z-index: 1; flex-shrink: 0; }

        /* ============================================
           Блок основателя — Split-screen с видео
           ============================================ */
        .founder-section {
            position: relative;
            background: var(--gray-50);
            padding: 100px 24px;
            overflow: hidden;
        }
        .founder-bg-decor {
            position: absolute;
            top: -100px;
            right: -100px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(185,28,28,0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        .founder-inner {
            max-width: 1120px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .founder-video-side {
            position: relative;
        }
        .founder-video-frame {
            position: relative;
            width: 100%;
            aspect-ratio: 9 / 16;
            border-radius: 20px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 24px 60px rgba(28,25,23,0.25);
            transform: rotate(-2deg);
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .founder-video-frame:hover {
            transform: rotate(0deg) scale(1.02);
        }
        .founder-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 30%;
            display: block;
            -webkit-object-fit: cover;
        }
        .founder-video-shade {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 180px;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.6) 100%);
            pointer-events: none;
        }
        .founder-video-progress {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            height: 3px;
            background: rgba(255,255,255,0.25);
            border-radius: 3px;
            z-index: 3;
            overflow: hidden;
        }
        .founder-video-progress-bar {
            height: 100%;
            width: 0%;
            background: var(--accent);
            transition: width 0.15s linear;
            box-shadow: 0 0 12px var(--accent-glow);
        }
        .founder-video-sound {
            position: absolute;
            bottom: 16px;
            left: 16px;
            right: 16px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.25);
            color: white;
            padding: 12px 18px;
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            z-index: 4;
        }
        .founder-video-sound:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }
        .founder-video-sound.unmuted {
            background: var(--accent);
            border-color: var(--accent);
            color: #ffffff;
        }
        .founder-video-sound .sound-icon {
            font-size: 16px;
        }
        .founder-video-tag {
            position: absolute;
            top: 24px;
            left: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 24px;
            color: white;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            z-index: 4;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulseDot 2s ease-in-out infinite;
        }
        @keyframes pulseDot {
            0% { box-shadow: 0 0 0 0 rgba(21,128,61,0.7); }
            70% { box-shadow: 0 0 0 10px rgba(21,128,61,0); }
            100% { box-shadow: 0 0 0 0 rgba(21,128,61,0); }
        }
        .founder-video-accent {
            position: absolute;
            bottom: -12px;
            left: 12px;
            right: 12px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
            z-index: -1;
        }

        .founder-text-side {
            display: flex;
            flex-direction: column;
        }
        .founder-tag {
            display: inline-block;
            align-self: flex-start;
            padding: 6px 14px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .founder-title {
            font-size: clamp(32px, 4vw, 52px);
            font-weight: 900;
            letter-spacing: -1.5px;
            line-height: 1.05;
            color: var(--text);
            margin-bottom: 12px;
        }
        .founder-role {
            font-size: 16px;
            color: var(--accent);
            font-weight: 800;
            letter-spacing: 0.3px;
            margin-bottom: 28px;
        }
        .founder-text {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.75;
            margin-bottom: 16px;
        }
        .founder-text:last-of-type {
            margin-bottom: 32px;
        }
        .founder-contacts {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .founder-contact {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            background: var(--white);
            border: 2px solid var(--border);
            color: var(--text);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .founder-contact:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(185,28,28,0.15);
        }
        .founder-contact .fc-icon {
            font-size: 22px;
            flex-shrink: 0;
        }
        .founder-contact .fc-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }
        .founder-contact .fc-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            .founder-inner {
                grid-template-columns: 320px 1fr;
                gap: 40px;
            }
            .founder-text { font-size: 15px; }
        }

        @media (max-width: 768px) {
            .founder-section {
                padding: 60px 20px;
            }
            .founder-inner {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .founder-video-side {
                max-width: 320px;
                margin: 0 auto;
                width: 100%;
            }
            .founder-video-frame {
                transform: rotate(0deg);
            }
            .founder-video-frame:hover {
                transform: scale(1.02);
            }
            .founder-title {
                font-size: 32px;
                text-align: center;
            }
            .founder-role {
                text-align: center;
            }
            .founder-tag {
                align-self: center;
            }
            .founder-text {
                text-align: center;
                font-size: 15px;
            }
            .founder-contacts {
                flex-direction: column;
                gap: 10px;
            }
            .founder-contact {
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* CTA */
        .cta-final {
            background: linear-gradient(135deg, var(--black), #1c1917);
            color: white;
            text-align: center;
            padding: 80px 24px;
            position: relative;
            overflow: hidden;
        }
        .cta-final::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(185,28,28,0.2) 0%, transparent 70%);
        }
        .cta-final .container { position: relative; z-index: 1; }
        .cta-final h2 {
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 900;
            letter-spacing: -1.5px;
            margin-bottom: 16px;
            line-height: 1.15;
        }
        .cta-final p {
            font-size: 17px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 32px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: var(--black);
            color: rgba(255,255,255,0.6);
            padding: 40px 24px 32px;
            font-size: 13px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .footer-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .footer-links a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--accent); }

        /* Мобильная версия */
        @media (max-width: 768px) {
            .navbar { padding: 12px 0; }
            .logo-mark { width: 36px; height: 36px; font-size: 16px; }
            .logo-text .main { font-size: 13px; }
            .logo-text .sub { font-size: 8px; }
            .nav-actions .nav-link { display: none; }
            .nav-actions a { padding: 8px 14px; font-size: 12px; }

            .hero { padding: 48px 0 32px; }
            .hero-cta { flex-direction: column; align-items: stretch; }
            .btn-hero-primary, .btn-hero-secondary { justify-content: center; padding: 16px 24px; font-size: 14px; }

            .stats-strip {
                grid-template-columns: repeat(2, 1fr);
                padding: 24px 20px;
                margin-top: 40px;
            }
            .stat-value { font-size: 22px; }

            .section { padding: 56px 0; }
            .section-head { margin-bottom: 40px; }

            .steps { grid-template-columns: 1fr; gap: 20px; }
            .step-card { padding: 28px 24px; }

            .trust-grid { grid-template-columns: 1fr; }

            .insurance-banner { padding: 32px 24px; flex-direction: column; align-items: stretch; text-align: center; }
            .insurance-title { font-size: 22px; }
            .insurance-cta { text-align: center; }

            .cta-final { padding: 60px 20px; }
            .cta-final .btn-hero-primary { width: 100%; }

            .compare-table { overflow-x: auto; }
            .compare-table table { min-width: 700px; }

            .footer-inner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="container navbar-inner">
        <a href="/" class="logo">
            <div class="logo-mark">A</div>
            <div class="logo-text">
                <div class="main">AVL LOGISTIC</div>
                <div class="sub">Грузоперевозки</div>
            </div>
        </a>
        <div class="nav-actions">
            <a href="/login" class="nav-link">Вход</a>
            <a href="/register?role=owner" class="nav-btn">Регистрация</a>
        </div>
    </div>
</nav>

<!-- Notification strip -->
<div class="notification-strip">
    📦 Закажите перевозку груза за 2 минуты — <a href="/register?role=owner">бесплатно для грузовладельцев →</a>
</div>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="dot"></span>
                <span>Работает по всей России</span>
            </div>

            <h1>
                Закажите перевозку груза <span class="accent">за 2 минуты</span>
            </h1>

            <p>
                Найдите проверенного перевозчика для вашего груза. Бесплатно для грузовладельцев. Без посредников. Договор и документы — онлайн.
            </p>

            <div class="hero-cta">
                <a href="/register?role=owner" class="btn-hero-primary">
                    📦 Создать заказ бесплатно
                </a>
                <a href="/register?role=carrier" class="btn-hero-secondary">
                    🚛 Я перевозчик — найти заказы
                </a>
            </div>

            <p class="hero-note">
                ✅ <strong>Бесплатно</strong> для грузовладельцев · ⚡ Отклики перевозчиков за 10 минут · 📄 Документы автоматом
            </p>

            <div class="stats-strip">
                <div class="stat-item">
                    <div class="stat-value">2 <span class="accent">мин</span></div>
                    <div class="stat-label">Создать заказ</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><span class="accent">0₽</span></div>
                    <div class="stat-label">Для грузовладельцев</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">10 <span class="accent">мин</span></div>
                    <div class="stat-label">До первого отклика</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Доступ к заказам</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Как это работает -->
<section class="section section-gray">
    <div class="container">
        <div class="section-head">
            <div class="tag">Как это работает</div>
            <h2>Три шага — и груз в пути</h2>
            <p>Без сложных форм и лишних звонков. Всё онлайн.</p>
        </div>

        <div class="steps">
            <div class="step-card">
                <div class="step-num">1</div>
                <div class="step-icon">📋</div>
                <div class="step-title">Создайте заказ</div>
                <div class="step-text">Укажите маршрут, тип груза, вес и бюджет. Это займёт меньше минуты.</div>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <div class="step-icon">🤝</div>
                <div class="step-title">Выберите перевозчика</div>
                <div class="step-text">Получите отклики от водителей с ценами. Сравните по рейтингу и выберите лучшего.</div>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <div class="step-icon">🚛</div>
                <div class="step-title">Отслеживайте перевозку</div>
                <div class="step-text">Следите за статусом онлайн, общайтесь в чате, получайте готовые документы.</div>
            </div>
        </div>
    </div>
</section>

<!-- Сравнение -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="tag">Почему мы</div>
            <h2>Дешевле диспетчеров и удобнее бирж</h2>
            <p>Сравните AVL Logistic с традиционными способами поиска перевозчиков</p>
        </div>

        <div class="compare-table">
            <table>
                <thead>
                    <tr>
                        <th>Что вы получаете</th>
                        <th>Диспетчер</th>
                        <th>Биржи</th>
                        <th class="highlight">🚛 AVL Logistic</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Комиссия для грузовладельца</td>
                        <td><span class="cross">✗</span> 15-20%</td>
                        <td><span class="cross">✗</span> Есть</td>
                        <td class="highlight"><span class="check">✓</span> 0%</td>
                    </tr>
                    <tr>
                        <td>Создание заказа</td>
                        <td>По телефону</td>
                        <td>Самостоятельно</td>
                        <td class="highlight"><span class="check">✓</span> 2 минуты</td>
                    </tr>
                    <tr>
                        <td>Документы (договор, ТН, акт)</td>
                        <td>Вручную</td>
                        <td><span class="cross">✗</span></td>
                        <td class="highlight"><span class="check">✓</span> Автоматом</td>
                    </tr>
                    <tr>
                        <td>Встроенный чат</td>
                        <td><span class="cross">✗</span></td>
                        <td>Частично</td>
                        <td class="highlight"><span class="check">✓</span> Есть</td>
                    </tr>
                    <tr>
                        <td>Рейтинг перевозчиков</td>
                        <td>Со слов</td>
                        <td>Частично</td>
                        <td class="highlight"><span class="check">✓</span> Отзывы</td>
                    </tr>
                    <tr>
                        <td>Отслеживание статуса</td>
                        <td>Звонки</td>
                        <td><span class="cross">✗</span></td>
                        <td class="highlight"><span class="check">✓</span> Онлайн</td>
                    </tr>
                    <tr>
                        <td>Мобильное приложение</td>
                        <td><span class="cross">✗</span></td>
                        <td><span class="cross">✗</span></td>
                        <td class="highlight"><span class="check">✓</span> iOS</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Преимущества -->
<section class="section section-gray">
    <div class="container">
        <div class="section-head">
            <div class="tag">Преимущества</div>
            <h2>Почему AVL Logistic</h2>
            <p>Мы учли всё, что нужно для удобной работы</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📄</div>
                <div class="feature-title">Документы автоматом</div>
                <div class="feature-text">Договор-заявка, транспортная накладная и акт формируются онлайн. Распечатайте в один клик.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <div class="feature-title">Встроенный чат</div>
                <div class="feature-text">Общайтесь с перевозчиком прямо на платформе. Прикрепляйте фото и документы.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Работает с телефона</div>
                <div class="feature-text">Полноценная мобильная версия. Создавайте заказы и меняйте статусы на ходу.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <div class="feature-title">Рейтинг и отзывы</div>
                <div class="feature-text">Выбирайте надёжных партнёров по реальным отзывам и истории сделок.</div>
            </div>
        </div>
    </div>
</section>

<!-- Инструменты доверия -->
<section class="section section-dark">
    <div class="container">
        <div class="section-head">
            <div class="tag">Инструменты доверия</div>
            <h2>Мы защищаем вас на каждом шаге</h2>
            <p>Знаем о рисках рынка грузоперевозок и закрыли их</p>
        </div>

        <div class="trust-grid">
            <div class="trust-card">
                <div class="trust-icon">🛡️</div>
                <div class="trust-body">
                    <div class="trust-problem">Риск: мошенники</div>
                    <div class="trust-title">Верификация и бейдж «Проверен»</div>
                    <div class="trust-text">Проверяем ИНН компаний через ФНС. Все проверенные пользователи получают бейдж.</div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-icon">📋</div>
                <div class="trust-body">
                    <div class="trust-problem">Риск: потеря времени</div>
                    <div class="trust-title">Гарантия первого отклика за 15 минут</div>
                    <div class="trust-text">Если за 15 минут никто не откликнулся — вернём размещение бесплатно или предложим альтернативу.</div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-icon">🧾</div>
                <div class="trust-body">
                    <div class="trust-problem">Риск: налоговые вопросы</div>
                    <div class="trust-title">Готовые документы с печатями</div>
                    <div class="trust-text">Договор-заявка и акт формируются по образцу. Скачайте PDF и распечатайте с подписями и печатями.</div>
                </div>
            </div>
            <div class="trust-card">
                <div class="trust-icon">👤</div>
                <div class="trust-body">
                    <div class="trust-problem">Риск: сайт-однодневка</div>
                    <div class="trust-title">Лицо основателя и прямой контакт</div>
                    <div class="trust-text">Открыто показываем команду. Свяжитесь с основателем напрямую — без ботов.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Страхование -->
<section class="section">
    <div class="container">
        <div class="insurance-banner">
            <div class="insurance-content">
                <div class="insurance-tag">🛡️ Страхование груза</div>
                <div class="insurance-title">
                    Страхование груза до <span class="accent">10 000 000 ₽</span> за 1 клик
                </div>
                <div class="insurance-text">
                    Застрахуйте груз при создании заказа. Если что-то пойдёт не так — страховая покроет ущерб в полном объёме.
                </div>
            </div>
            <div class="insurance-cta">
                <a href="/register?role=owner" class="btn-hero-primary" style="white-space:nowrap;">
                    🛡️ Застраховать груз
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Основатель — Split-screen с видео -->
<section class="founder-section">
    
    <div class="founder-bg-decor"></div>
    
    <div class="founder-inner">
        
        <div class="founder-video-side">
            <div class="founder-video-frame">
                
                <div class="founder-video-progress">
                    <div class="founder-video-progress-bar"></div>
                </div>
                
                <video 
    class="founder-video" 
    poster="/uploads/founder/founder-poster.jpg"
    playsinline 
    webkit-playsinline
    muted 
    loop 
    autoplay 
    preload="auto"
    disablepictureinpicture
    controlslist="nodownload noplaybackrate"
    id="founderVideo">
    <source src="/uploads/founder/founder.mp4" type="video/mp4">
</video>
                
                <div class="founder-video-shade"></div>
                
                <button class="founder-video-sound" onclick="toggleFounderSound(this)" title="Включить звук">
                    <span class="sound-icon">🔇</span>
                    <span class="sound-label">Включить звук</span>
                </button>
                
                <div class="founder-video-tag">
                    <span class="pulse-dot"></span>
                    На связи
                </div>
                
            </div>
            
            <div class="founder-video-accent"></div>
        </div>
        
        <div class="founder-text-side">
            
            <div class="founder-tag">О проекте</div>
            
            <h2 class="founder-title">Артём Виноградов</h2>
            
            <p class="founder-role">Основатель AVL Logistic</p>
            
            <p class="founder-text">
                Мы создали AVL Logistic, чтобы убрать посредников из грузоперевозок. 
                Каждый день сталкиваемся с одной проблемой: сложно найти надёжного 
                перевозчика, договориться о цене, оформить документы.
            </p>
            
            <p class="founder-text">
                Решили автоматизировать всё это в одном месте. Если у вас есть вопросы — 
                напишите мне лично, я отвечаю на все сообщения.
            </p>
            
            <div class="founder-contacts">
                <a href="tel:+79215411481" class="founder-contact">
                    <span class="fc-icon">📞</span>
                    <div>
                        <div class="fc-label">Позвонить</div>
                        <div class="fc-value">+7 (921) 541-14-81</div>
                    </div>
                </a>
                <a href="mailto:info@avllogist.ru" class="founder-contact">
                    <span class="fc-icon">✉️</span>
                    <div>
                        <div class="fc-label">Написать</div>
                        <div class="fc-value">info@avllogist.ru</div>
                    </div>
                </a>
            </div>
            
        </div>
        
    </div>
    
</section>

<!-- Финальный CTA -->
<section class="cta-final">
    <div class="container">
        <h2>Начните перевозить грузы за 2 минуты</h2>
        <p>Регистрация занимает минуту. Бесплатно для грузовладельцев. Первые отклики — за 10 минут.</p>
        <a href="/register?role=owner" class="btn-hero-primary" style="display:inline-flex; padding:20px 40px; font-size:16px;">
            🚀 Создать заказ бесплатно
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
        <div>
            © <?= date('Y') ?> AVL Logistic — платформа грузоперевозок
        </div>
        <div class="footer-links">
            <a href="/page/owners">Грузовладельцам</a>
            <a href="/page/carriers">Перевозчикам</a>
            <a href="/pricing">Тарифы</a>
            <a href="/page/privacy">Политика конфиденциальности</a>
            <a href="mailto:info@avllogist.ru">info@avllogist.ru</a>
        </div>
    </div>
</footer>

<script>
// ============================================
// Функция звука
// ============================================
function toggleFounderSound(btn) {
    var video = document.getElementById('founderVideo');
    var icon  = btn.querySelector('.sound-icon');
    var label = btn.querySelector('.sound-label');
    
    if (!video) return;
    
    if (video.muted) {
        video.muted = false;
        video.volume = 1;
        if (video.paused) video.play().catch(function(){});
        
        btn.classList.add('unmuted');
        if (icon) icon.textContent = '🔊';
        if (label) label.textContent = 'Выключить звук';
    } else {
        video.muted = true;
        btn.classList.remove('unmuted');
        if (icon) icon.textContent = '🔇';
        if (label) label.textContent = 'Включить звук';
    }
}

// ============================================
// Инициализация с iOS-фиксами
// ============================================
(function() {
    'use strict';
    
    var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    function initFounderVideo() {
        var video = document.getElementById('founderVideo');
        var progressBar = document.querySelector('.founder-video-progress-bar');
        
        if (!video) return;
        
        console.log('[founderVideo] initialized, iOS:', isIOS);
        
        if (progressBar) {
            video.addEventListener('timeupdate', function() {
                if (video.duration > 0) {
                    var percent = (video.currentTime / video.duration) * 100;
                    progressBar.style.width = percent + '%';
                }
            });
        }
        
        if (isIOS) {
            var unlockOnce = function() {
                if (video.paused) {
                    video.play().catch(function(err) {
                        console.warn('[founderVideo] iOS unlock play failed:', err.name);
                    });
                }
                document.removeEventListener('touchstart', unlockOnce);
                document.removeEventListener('click', unlockOnce);
            };
            
            document.addEventListener('touchstart', unlockOnce, { once: true, passive: true });
            document.addEventListener('click', unlockOnce, { once: true });
        }
        
        function forcePlay() {
            var promise = video.play();
            if (promise !== undefined) {
                promise.then(function() {
                    console.log('[founderVideo] playing');
                }).catch(function(err) {
                    console.warn('[founderVideo] autoplay failed:', err.name);
                    setTimeout(function() {
                        video.muted = true;
                        video.play().catch(function(){});
                    }, 500);
                });
            }
        }
        forcePlay();
        
        setInterval(function() {
            if (!video) return;
            if (document.visibilityState !== 'visible') return;
            
            if (video.paused) {
                console.log('[founderVideo] watchdog: resuming');
                video.muted = true;
                video.play().catch(function(){});
            }
            
            if (video.duration > 0 && video.currentTime >= video.duration - 0.5) {
                video.currentTime = 0;
                video.play().catch(function(){});
            }
        }, 2000);
        
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && video.paused) {
                video.muted = true;
                video.play().catch(function(){});
            }
        });
        
        window.addEventListener('scroll', function() {
            if (video.paused && document.visibilityState === 'visible') {
                video.play().catch(function(){});
            }
        }, { passive: true });
        
        video.addEventListener('pause', function() {
            if (document.visibilityState === 'visible') {
                setTimeout(function() {
                    if (video.paused) video.play().catch(function(){});
                }, 100);
            }
        });
        
        video.addEventListener('ended', function() {
            video.currentTime = 0;
            video.play().catch(function(){});
        });
        
        video.addEventListener('loadeddata', function() {
            console.log('[founderVideo] loaded, ready to play');
            if (video.paused) video.play().catch(function(){});
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFounderVideo);
    } else {
        initFounderVideo();
    }
})();
</script>

</body>
</html>