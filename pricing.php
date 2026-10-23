<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

// Активная подписка
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$currentSub = $stmt->fetch(PDO::FETCH_ASSOC);

// Отклики в этом месяце
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bids WHERE carrier_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stmt->execute([$_SESSION['user_id']]);
$bidsThisMonth = (int)$stmt->fetchColumn();

// Пробный период
$stmt = $pdo->prepare("SELECT expires_at FROM subscriptions WHERE user_id = ? AND plan = 'free' AND status = 'active' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$trial = $stmt->fetch(PDO::FETCH_ASSOC);
$daysLeft = 0;
if ($trial) {
    $expires = new DateTime($trial['expires_at']);
    $now = new DateTime();
    $daysLeft = $now->diff($expires)->days;
}

// Неоплаченный счёт
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$pendingInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

// Текущий план
$currentPlan = $currentSub['plan'] ?? 'free';
?>

<style>
    /* Hero */
    .pricing-hero {
        text-align: center;
        padding: 20px 0 32px;
        max-width: 720px;
        margin: 0 auto;
    }
    .pricing-hero .tag {
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
    .pricing-hero h1 {
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 900;
        letter-spacing: -1.5px;
        line-height: 1.15;
        margin-bottom: 12px;
    }
    .pricing-hero p {
        font-size: 16px;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    /* Переключатель Месяц / Год */
    .period-toggle {
        display: inline-flex;
        background: var(--border-light);
        padding: 4px;
        margin: 16px auto 0;
        position: relative;
    }
    .period-toggle label {
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        color: var(--text-secondary);
        transition: all 0.2s;
        position: relative;
        z-index: 2;
    }
    .period-toggle label.active {
        color: var(--text);
        background: var(--surface);
    }
    .period-toggle .discount-badge {
        position: absolute;
        top: -12px;
        right: -8px;
        background: var(--success);
        color: white;
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        letter-spacing: 0.5px;
        transform: rotate(-3deg);
    }

    /* Current plan banner */
    .current-banner {
        background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
        border: 1px solid #a7f3d0;
        padding: 20px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .current-banner.free { background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fde68a; }
    .current-banner.pending { background: linear-gradient(135deg, #fef3c7, #fffbeb); border-color: #fcd34d; }
    .current-banner-content { flex: 1; min-width: 240px; }
    .current-banner-title {
        font-size: 16px;
        font-weight: 900;
        letter-spacing: -0.3px;
        margin-bottom: 4px;
    }
    .current-banner-sub {
        font-size: 13px;
        color: #065f46;
    }
    .current-banner.free .current-banner-sub,
    .current-banner.pending .current-banner-sub { color: #92400e; }

    /* Pricing Grid */
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
        align-items: stretch;
    }
    
    .pricing-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 32px 24px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        display: flex;
        flex-direction: column;
    }
    
    .pricing-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.08);
    }
    
    .pricing-card.featured {
        border: 2px solid var(--accent);
        box-shadow: 0 0 40px var(--accent-glow);
    }
    .pricing-card.current {
        border-color: var(--success);
        background: linear-gradient(180deg, #f0fdf4, var(--surface));
    }
    
    .pricing-badge {
        position: absolute;
        top: -12px;
        left: 24px;
        background: var(--accent);
        color: var(--primary);
        padding: 5px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .pricing-badge.current { background: var(--success); color: white; }
    
    .pricing-name {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 16px;
        color: var(--text-secondary);
    }
    
    .pricing-price {
        font-size: 42px;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -2px;
        margin-bottom: 2px;
        line-height: 1;
    }
    .pricing-price .currency { font-size: 22px; letter-spacing: -1px; }
    .pricing-price .period-suffix {
        font-size: 14px;
        color: var(--text-secondary);
        font-weight: 500;
        letter-spacing: 0;
        font-family: inherit;
    }
    .pricing-price .yearly-price {
        display: block;
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 500;
        letter-spacing: 0;
        font-family: inherit;
        margin-top: 4px;
    }
    
    .pricing-period {
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 24px;
    }
    
    .pricing-features {
        list-style: none;
        margin-bottom: 24px;
        flex: 1;
    }
    
    .pricing-features li {
        padding: 8px 0;
        font-size: 13px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        line-height: 1.4;
    }
    
    .pricing-features li .check {
        color: var(--success);
        font-weight: 900;
        font-size: 15px;
        flex-shrink: 0;
    }
    .pricing-features li .cross {
        color: var(--border);
        font-weight: 900;
        font-size: 15px;
        flex-shrink: 0;
    }
    .pricing-features li.muted { color: var(--text-secondary); }
    
    .pricing-cta { margin-top: auto; }
    
    .current-plan-label {
        padding: 12px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--success);
        background: #f0fdf4;
        border: 1px dashed var(--success);
        text-align: center;
    }
    .current-plan-label.pending {
        color: #92400e;
        background: #fffbeb;
        border-color: var(--accent);
    }
    
    .pay-card-btn {
        font-weight: 800;
    }
    .pay-card-btn:disabled { opacity: 0.6; cursor: wait; }

    /* КАЛЬКУЛЯТОР */
    .calc-section {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: white;
        padding: 32px;
        margin-bottom: 40px;
    }
    .calc-title {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 6px;
    }
    .calc-title .accent { color: var(--accent); }
    .calc-subtitle {
        font-size: 13px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 24px;
    }
    .calc-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        margin-bottom: 24px;
    }
    .calc-slider-wrap { display: flex; flex-direction: column; gap: 8px; }
    .calc-slider-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.6);
        font-weight: 700;
    }
    .calc-slider-value {
        font-size: 28px;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -1px;
        color: var(--accent);
        margin-bottom: 8px;
    }
    .calc-slider-value .unit {
        font-size: 14px;
        color: rgba(255,255,255,0.5);
        font-weight: 500;
    }
    .calc-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 4px;
        background: rgba(255,255,255,0.15);
        outline: none;
        cursor: pointer;
    }
    .calc-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: var(--accent);
        cursor: pointer;
        border: 3px solid #0f172a;
        box-shadow: 0 0 0 2px var(--accent);
    }
    .calc-slider::-moz-range-thumb {
        width: 20px;
        height: 20px;
        background: var(--accent);
        cursor: pointer;
        border: 3px solid #0f172a;
        box-shadow: 0 0 0 2px var(--accent);
    }
    .calc-range-info {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
        color: rgba(255,255,255,0.4);
        margin-top: 4px;
    }

    .calc-results {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 16px;
    }
    .calc-result {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        padding: 20px;
        text-align: center;
    }
    .calc-result-name {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
        font-weight: 700;
        margin-bottom: 8px;
    }
    .calc-result-amount {
        font-size: 24px;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -1px;
        color: white;
        line-height: 1;
    }
    .calc-result-note {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        margin-top: 6px;
    }
    .calc-result.best {
        background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(245,158,11,0.05));
        border-color: var(--accent);
    }
    .calc-result.best .calc-result-amount { color: var(--accent); }
    .calc-result.best .calc-result-name { color: var(--accent); }

    .calc-verdict {
        background: rgba(16,185,129,0.1);
        border-left: 3px solid var(--success);
        padding: 16px 20px;
        margin-top: 20px;
        font-size: 14px;
        line-height: 1.5;
    }
    .calc-verdict strong { color: var(--success); }
    .calc-verdict.economy-bad {
        background: rgba(245,158,11,0.1);
        border-color: var(--accent);
    }
    .calc-verdict.economy-bad strong { color: var(--accent); }

    /* Сравнительная таблица */
    .compare-section { margin-bottom: 40px; }
    .compare-table-wrap {
        background: var(--surface);
        border: 1px solid var(--border);
        overflow-x: auto;
    }
    .compare-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }
    .compare-table th {
        padding: 16px 20px;
        background: var(--border-light);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        text-align: left;
        border-bottom: 2px solid var(--border);
    }
    .compare-table th.highlight {
        background: var(--accent);
        color: var(--primary);
    }
    .compare-table td {
        padding: 12px 20px;
        font-size: 13px;
        border-bottom: 1px solid var(--border-light);
    }
    .compare-table tr:last-child td { border-bottom: none; }
    .compare-table td.highlight {
        background: #fffbeb;
        font-weight: 700;
    }
    .compare-table .check { color: var(--success); font-weight: 900; }
    .compare-table .cross { color: var(--text-secondary); opacity: 0.5; }

    /* Сравнение с ATI */
    .ati-section {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 32px;
        margin-bottom: 40px;
    }
    .ati-title {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 6px;
        text-align: center;
    }
    .ati-subtitle {
        font-size: 13px;
        color: var(--text-secondary);
        text-align: center;
        margin-bottom: 24px;
    }
    .ati-table {
        width: 100%;
        border-collapse: collapse;
    }
    .ati-table th {
        padding: 14px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        font-weight: 800;
        text-align: center;
        border-bottom: 2px solid var(--border);
    }
    .ati-table th.us {
        background: var(--accent);
        color: var(--primary);
    }
    .ati-table th:first-child { text-align: left; }
    .ati-table td {
        padding: 14px 12px;
        font-size: 13px;
        text-align: center;
        border-bottom: 1px solid var(--border-light);
    }
    .ati-table td:first-child { text-align: left; color: var(--text-secondary); }
    .ati-table tr:last-child td { border-bottom: none; }
    .ati-table td.us {
        background: #fffbeb;
        font-weight: 700;
        color: var(--text);
    }
    .ati-table .check { color: var(--success); font-weight: 900; font-size: 16px; }
    .ati-table .cross { color: var(--danger); font-weight: 900; font-size: 16px; }
    .ati-table .partial { color: var(--accent); font-weight: 700; font-size: 12px; }

    /* FAQ */
    .faq-section { max-width: 800px; margin: 0 auto; }
    .faq-section h2 {
        text-align: center;
        font-size: 24px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 24px;
    }
    .faq-item {
        background: var(--surface);
        border: 1px solid var(--border);
        margin-bottom: 10px;
        overflow: hidden;
    }
    .faq-item summary {
        padding: 18px 22px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        list-style: none;
        transition: background 0.2s;
        gap: 16px;
    }
    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary::after {
        content: '▼';
        font-size: 10px;
        color: var(--text-secondary);
        transition: transform 0.2s;
        flex-shrink: 0;
    }
    .faq-item[open] summary::after { transform: rotate(180deg); }
    .faq-item summary:hover { background: var(--border-light); }
    .faq-item[open] summary { background: var(--border-light); }
    .faq-answer {
        padding: 0 22px 20px;
        color: var(--text-secondary);
        font-size: 13px;
        line-height: 1.7;
    }
    .faq-answer a { color: var(--accent); font-weight: 700; }

    /* Мобильная */
    @media (max-width: 900px) {
        .pricing-grid { grid-template-columns: 1fr; gap: 16px; }
        .pricing-price { font-size: 34px; }
        .pricing-price .currency { font-size: 18px; }
        .pricing-card { padding: 24px 20px; }
        .current-banner { padding: 16px; }
        .current-banner-title { font-size: 15px; }
        .calc-section { padding: 20px; }
        .calc-grid { grid-template-columns: 1fr; gap: 20px; }
        .calc-results { grid-template-columns: 1fr; }
        .ati-section { padding: 20px; }
        .ati-table th, .ati-table td { padding: 10px 8px; font-size: 11px; }
        .compare-table th, .compare-table td { padding: 10px 14px; font-size: 12px; }
        .period-toggle label { padding: 10px 16px; font-size: 12px; }
    }
</style>

<!-- Hero -->
<div class="pricing-hero">
    <div class="tag">💰 Тарифы для перевозчиков</div>
    <h1>Платите 3% с оборота или фикс</h1>
    <p>Отклики без лимитов на всех тарифах. Разница только в комиссии: Free — 3% с каждой сделки, Basic и Pro — 0% за фиксированную подписку.</p>
    
    <div class="period-toggle" id="periodToggle">
        <label class="active" data-period="month">Месяц</label>
        <label data-period="year">
            Год
            <span class="discount-badge">-17%</span>
        </label>
    </div>
</div>

<!-- Flash messages -->
<?php if (isset($_GET['activated'])): ?>
    <div class="alert alert-success">✅ <?= $_SESSION['success'] ?? 'Тариф активирован!' ?> <?php unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['invoiced'])): ?>
    <div class="alert alert-success">✅ <?= $_SESSION['success'] ?? 'Счёт создан!' ?> <?php unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">❌ <?= $_SESSION['error'] ?? 'Ошибка!' ?> <?php unset($_SESSION['error']); ?></div>
<?php endif; ?>

<!-- Текущий тариф -->
<?php if ($currentSub && $currentPlan !== 'free'): ?>
    <div class="current-banner">
        <div class="current-banner-content">
            <div class="current-banner-title">✅ У вас активна подписка «<?= htmlspecialchars(ucfirst($currentPlan)) ?>»</div>
            <div class="current-banner-sub">
                Действует до <strong><?= date('d.m.Y', strtotime($currentSub['expires_at'])) ?></strong>
                · Комиссия 0% с оборота
                · Откликов в этом месяце: <strong><?= $bidsThisMonth ?></strong>
            </div>
        </div>
    </div>
<?php elseif ($trial && $daysLeft > 0): ?>
    <div class="current-banner free">
        <div class="current-banner-content">
            <div class="current-banner-title">🎁 Пробный период Free</div>
            <div class="current-banner-sub">
                Осталось <strong><?= $daysLeft ?> дней</strong>
                · Комиссия 3% с оборота
                · Откликов в этом месяце: <strong><?= $bidsThisMonth ?></strong>
            </div>
        </div>
        <a href="#plans" class="btn btn-accent">Улучшить тариф →</a>
    </div>
<?php endif; ?>

<!-- Неоплаченный счёт -->
<?php if ($pendingInvoice): ?>
    <div class="current-banner pending">
        <div class="current-banner-content">
            <div class="current-banner-title">⏳ Ожидает оплаты: тариф «<?= htmlspecialchars($pendingInvoice['plan']) ?>»</div>
            <div class="current-banner-sub">
                Сумма: <strong><?= number_format($pendingInvoice['amount'], 0, ',', ' ') ?> ₽</strong>
                · Создан <?= date('d.m.Y H:i', strtotime($pendingInvoice['created_at'])) ?>
            </div>
        </div>
        <?php if ($pendingInvoice['payment_method'] === 'card' && !empty($pendingInvoice['payment_url'])): ?>
            <a href="<?= htmlspecialchars($pendingInvoice['payment_url']) ?>" class="btn btn-accent">
                💳 Продолжить оплату
            </a>
        <?php else: ?>
            <a href="mailto:info@avllogist.ru" class="btn btn-outline">Написать для оплаты</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- КАЛЬКУЛЯТОР -->
<div class="calc-section">
    <div class="calc-title">💡 Калькулятор: <span class="accent">окупается ли Basic?</span></div>
    <div class="calc-subtitle">Двигайте ползунки — увидите, сколько экономите</div>

    <div class="calc-grid">
        <div class="calc-slider-wrap">
            <div class="calc-slider-label">Заказов в месяц</div>
            <div class="calc-slider-value"><span id="calcOrdersVal">3</span><span class="unit"> сделок</span></div>
            <input type="range" min="1" max="30" value="3" class="calc-slider" id="calcOrders">
            <div class="calc-range-info"><span>1</span><span>30</span></div>
        </div>

        <div class="calc-slider-wrap">
            <div class="calc-slider-label">Средний чек</div>
            <div class="calc-slider-value"><span id="calcAvgVal">100 000</span><span class="unit"> ₽</span></div>
            <input type="range" min="10000" max="500000" step="10000" value="100000" class="calc-slider" id="calcAvg">
            <div class="calc-range-info"><span>10 000 ₽</span><span>500 000 ₽</span></div>
        </div>
    </div>

    <div class="calc-results">
        <div class="calc-result">
            <div class="calc-result-name">🚀 Free</div>
            <div class="calc-result-amount" id="calcFree">9 000 ₽</div>
            <div class="calc-result-note">комиссия 3% / мес</div>
        </div>
        <div class="calc-result best" id="calcBasicCard">
            <div class="calc-result-name">💼 Basic</div>
            <div class="calc-result-amount" id="calcBasic">1 990 ₽</div>
            <div class="calc-result-note" id="calcBasicNote">фикс, без комиссии</div>
        </div>
        <div class="calc-result">
            <div class="calc-result-name">🏢 Pro</div>
            <div class="calc-result-amount" id="calcPro">4 990 ₽</div>
            <div class="calc-result-note">фикс, без комиссии</div>
        </div>
    </div>

    <div class="calc-verdict" id="calcVerdict">
        ✅ Экономия на Basic: <strong>7 010 ₽/мес</strong> · <strong>84 120 ₽/год</strong>
    </div>
</div>

<!-- Тарифы -->
<div id="plans" class="pricing-grid">
    
    <!-- Free -->
    <div class="pricing-card <?= $currentPlan === 'free' && $currentSub ? 'current' : '' ?>">
        <?php if ($currentPlan === 'free' && $currentSub): ?>
            <div class="pricing-badge current">Текущий</div>
        <?php endif; ?>
        <div class="pricing-name">🚀 Старт</div>
        <div class="pricing-price">
            0 <span class="currency">₽</span>
        </div>
        <div class="pricing-period">навсегда · платите только 3% с оборота</div>
        
        <ul class="pricing-features">
            <li><span class="check">✓</span> <strong>Безлимит откликов</strong></li>
            <li><span class="check">✓</span> Просмотр всех заказов</li>
            <li><span class="check">✓</span> Чат с заказчиком</li>
            <li><span class="check">✓</span> Базовые документы (PDF)</li>
            <li class="muted"><span class="cross">—</span> <strong>Комиссия 3%</strong> с оборота</li>
            <li class="muted"><span class="cross">—</span> Без приоритета в выдаче</li>
            <li class="muted"><span class="cross">—</span> Без ЭДО и аналитики</li>
        </ul>
        
        <div class="pricing-cta">
            <?php if ($currentPlan === 'free' && $currentSub): ?>
                <div class="current-plan-label">Текущий план</div>
                <div style="font-size:11px; color:var(--text-secondary); text-align:center; margin-top:8px;">
                    Откликов в этом месяце: <?= $bidsThisMonth ?>
                </div>
            <?php else: ?>
                <form method="POST" action="/subscribe">
                    <input type="hidden" name="plan" value="free">
                    <button type="submit" class="btn btn-outline btn-block">Перейти на Free</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Basic -->
    <div class="pricing-card featured <?= $currentPlan === 'basic' ? 'current' : '' ?>">
        <?php if ($currentPlan === 'basic'): ?>
            <div class="pricing-badge current">Текущий</div>
        <?php else: ?>
            <div class="pricing-badge">💎 Популярный</div>
        <?php endif; ?>
        <div class="pricing-name">💼 Базовый</div>
        <div class="pricing-price">
            <span class="price-monthly">1 990</span><span class="price-yearly" style="display:none;">1 659</span>
            <span class="currency">₽</span>
            <span class="period-suffix">/мес</span>
            <span class="yearly-price" style="display:none;">19 908 ₽ при оплате за год</span>
        </div>
        <div class="pricing-period" id="basicPeriod">в месяц · экономия до 40 000 ₽/год</div>
        
        <ul class="pricing-features">
            <li><span class="check">✓</span> <strong>Безлимит откликов</strong></li>
            <li><span class="check">✓</span> Просмотр всех заказов</li>
            <li><span class="check">✓</span> Чат с заказчиком</li>
            <li><span class="check">✓</span> Полный пакет документов</li>
            <li><span class="check">✓</span> <strong style="color:var(--success);">Комиссия 0%</strong> с оборота</li>
            <li><span class="check">✓</span> Приоритет в выдаче</li>
            <li><span class="check">✓</span> Хранение документов 1 год</li>
        </ul>
        
        <div class="pricing-cta">
            <?php if ($currentPlan === 'basic'): ?>
                <div class="current-plan-label">Текущий план</div>
            <?php elseif ($pendingInvoice && $pendingInvoice['plan'] === 'basic'): ?>
                <div class="current-plan-label pending">⏳ Ожидает оплаты</div>
                <?php if ($pendingInvoice['payment_method'] === 'card' && !empty($pendingInvoice['payment_url'])): ?>
                    <a href="<?= htmlspecialchars($pendingInvoice['payment_url']) ?>" class="btn btn-accent btn-block" style="margin-top:12px;">
                        💳 Продолжить оплату
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" 
                        class="btn btn-accent btn-block pay-card-btn" 
                        data-plan="basic" 
                        onclick="payWithCard('basic', this)">
                    💳 Оплатить картой
                </button>
                <form method="POST" action="/subscribe" style="margin-top: 8px;">
                    <input type="hidden" name="plan" value="basic">
                    <button type="submit" class="btn btn-outline btn-block" 
                            style="font-size: 11px; padding: 8px 12px; text-transform: none;">
                        🧾 Оплатить по счёту (Сбербанк)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pro -->
    <div class="pricing-card <?= $currentPlan === 'pro' ? 'current' : '' ?>">
        <?php if ($currentPlan === 'pro'): ?>
            <div class="pricing-badge current">Текущий</div>
        <?php endif; ?>
        <div class="pricing-name">🏢 Pro</div>
        <div class="pricing-price">
            <span class="price-monthly">4 990</span><span class="price-yearly" style="display:none;">4 159</span>
            <span class="currency">₽</span>
            <span class="period-suffix">/мес</span>
            <span class="yearly-price" style="display:none;">49 900 ₽ при оплате за год</span>
        </div>
        <div class="pricing-period" id="proPeriod">в месяц · для автопарков</div>
        
        <ul class="pricing-features">
            <li><span class="check">✓</span> <strong>Безлимит откликов</strong></li>
            <li><span class="check">✓</span> Просмотр всех заказов</li>
            <li><span class="check">✓</span> Чат с заказчиком</li>
            <li><span class="check">✓</span> Полный пакет документов</li>
            <li><span class="check">✓</span> <strong style="color:var(--success);">Комиссия 0%</strong> с оборота</li>
            <li><span class="check">✓</span> Приоритет в выдаче</li>
            <li><span class="check">✓</span> Хранение документов 3 года</li>
            <li><span class="check">✓</span> ЭДО интеграция</li>
            <li><span class="check">✓</span> Мульти-аккаунт (до 5 водителей)</li>
            <li><span class="check">✓</span> Персональный менеджер</li>
        </ul>
        
        <div class="pricing-cta">
            <?php if ($currentPlan === 'pro'): ?>
                <div class="current-plan-label">Текущий план</div>
            <?php elseif ($pendingInvoice && $pendingInvoice['plan'] === 'pro'): ?>
                <div class="current-plan-label pending">⏳ Ожидает оплаты</div>
                <?php if ($pendingInvoice['payment_method'] === 'card' && !empty($pendingInvoice['payment_url'])): ?>
                    <a href="<?= htmlspecialchars($pendingInvoice['payment_url']) ?>" class="btn btn-accent btn-block" style="margin-top:12px;">
                        💳 Продолжить оплату
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" 
                        class="btn btn-accent btn-block pay-card-btn" 
                        data-plan="pro" 
                        onclick="payWithCard('pro', this)">
                    💳 Оплатить картой
                </button>
                <form method="POST" action="/subscribe" style="margin-top: 8px;">
                    <input type="hidden" name="plan" value="pro">
                    <button type="submit" class="btn btn-outline btn-block" 
                            style="font-size: 11px; padding: 8px 12px; text-transform: none;">
                        🧾 Оплатить по счёту (Сбербанк)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- Сравнение тарифов -->
<div class="compare-section">
    <h2 style="text-align:center; font-size:22px; font-weight:900; letter-spacing:-0.5px; margin-bottom:20px;">
        Подробное сравнение
    </h2>
    <div class="compare-table-wrap">
        <table class="compare-table">
            <thead>
                <tr>
                    <th>Возможность</th>
                    <th>🚀 Free</th>
                    <th class="highlight">💼 Basic</th>
                    <th>🏢 Pro</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Откликов в месяц</td>
                    <td><span class="check">✓</span> Безлимит</td>
                    <td class="highlight"><span class="check">✓</span> Безлимит</td>
                    <td><span class="check">✓</span> Безлимит</td>
                </tr>
                <tr>
                    <td>Комиссия с оборота</td>
                    <td><strong>3%</strong></td>
                    <td class="highlight"><span class="check">✓</span> 0%</td>
                    <td><span class="check">✓</span> 0%</td>
                </tr>
                <tr>
                    <td>Просмотр всех заказов</td>
                    <td><span class="check">✓</span></td>
                    <td class="highlight"><span class="check">✓</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
                <tr>
                    <td>Чат с заказчиком</td>
                    <td><span class="check">✓</span></td>
                    <td class="highlight"><span class="check">✓</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
                <tr>
                    <td>Документы (договор, ТН, акт)</td>
                    <td><span class="check">✓</span></td>
                    <td class="highlight"><span class="check">✓</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
                <tr>
                    <td>Приоритет в выдаче</td>
                    <td><span class="cross">✗</span></td>
                    <td class="highlight"><span class="check">✓</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
                <tr>
                    <td>Хранение документов</td>
                    <td>—</td>
                    <td class="highlight">1 год</td>
                    <td>3 года</td>
                </tr>
                <tr>
                    <td>ЭДО-интеграция</td>
                    <td><span class="cross">✗</span></td>
                    <td class="highlight"><span class="cross">✗</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
                <tr>
                    <td>Мульти-аккаунт (до 5 водителей)</td>
                    <td><span class="cross">✗</span></td>
                    <td class="highlight"><span class="cross">✗</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
                <tr>
                    <td>Персональный менеджер</td>
                    <td><span class="cross">✗</span></td>
                    <td class="highlight"><span class="cross">✗</span></td>
                    <td><span class="check">✓</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Сравнение с ATI -->
<div class="ati-section">
    <div class="ati-title">⚔️ AVL Logistic vs ATI.SU</div>
    <div class="ati-subtitle">Честное сравнение — что вы получаете за свои деньги</div>

    <table class="ati-table">
        <thead>
            <tr>
                <th>Что важно</th>
                <th>ATI.SU</th>
                <th class="us">🚛 AVL Logistic</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Комиссия для грузовладельца</td>
                <td><span class="cross">✗</span> Есть</td>
                <td class="us"><span class="check">✓</span> <strong>0 ₽</strong></td>
            </tr>
            <tr>
                <td>Стоимость для перевозчика</td>
                <td><span class="partial">Подписка + доплаты</span></td>
                <td class="us"><strong>3% с оборота или 1 990 ₽/мес</strong></td>
            </tr>
            <tr>
                <td>Оплата картой / СБП</td>
                <td><span class="cross">✗</span> Нет</td>
                <td class="us"><span class="check">✓</span> Есть (Tinkoff)</td>
            </tr>
            <tr>
                <td>Эскроу (защита сделки)</td>
                <td><span class="cross">✗</span> Отдельно 2-4.5%</td>
                <td class="us"><span class="check">✓</span> <strong>Встроено, 0₽</strong></td>
            </tr>
            <tr>
                <td>Документы (договор, ТН, УПД)</td>
                <td><span class="partial">Ручные</span></td>
                <td class="us"><span class="check">✓</span> Автоматом</td>
            </tr>
            <tr>
                <td>Отчёт агента (ст. 1008 ГК)</td>
                <td><span class="cross">✗</span> Нет</td>
                <td class="us"><span class="check">✓</span> Автоматом</td>
            </tr>
            <tr>
                <td>Встроенный чат</td>
                <td><span class="partial">Частично</span></td>
                <td class="us"><span class="check">✓</span> Полноценный</td>
            </tr>
            <tr>
                <td>Рейтинг и отзывы</td>
                <td><span class="partial">Частично</span></td>
                <td class="us"><span class="check">✓</span> Публичный профиль</td>
            </tr>
            <tr>
                <td>Мобильное приложение</td>
                <td><span class="cross">✗</span> Нет</td>
                <td class="us"><span class="check">✓</span> iOS (в разработке)</td>
            </tr>
            <tr>
                <td>Регистрация</td>
                <td><span class="partial">2-3 дня (проверка)</span></td>
                <td class="us"><span class="check">✓</span> <strong>2 минуты</strong></td>
            </tr>
            <tr>
                <td>Откликов в месяц</td>
                <td><span class="partial">По лимитам подписки</span></td>
                <td class="us"><span class="check">✓</span> <strong>Безлимит</strong></td>
            </tr>
            <tr>
                <td>Комиссия с оборота (Free)</td>
                <td><span class="cross">✗</span> Есть</td>
                <td class="us"><strong>3% — конкурентно</strong></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--border-light); display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; font-size:13px;">
        <div style="flex:1; min-width:240px;">
            <strong>💡 Итог:</strong> ATI берёт деньги за доступ. Мы — за результат (3% с оборота) или за фикс с реальной экономией.
        </div>
        <a href="#plans" class="btn btn-accent">Выбрать тариф →</a>
    </div>
</div>

<!-- FAQ -->
<div class="faq-section">
    <h2>Частые вопросы</h2>
    
    <details class="faq-item" open>
        <summary>Как оплатить подписку?</summary>
        <div class="faq-answer">
            <strong>Картой онлайн</strong> — нажмите «💳 Оплатить картой». Вы перейдёте на защищённую страницу Tinkoff Kassa, оплатите картой, и подписка активируется автоматически в течение 1–2 минут.
            <br><br>
            <strong>По счёту (Сбербанк)</strong> — нажмите «🧾 Оплатить по счёту». Мы выставим счёт на email. После оплаты напишите нам — активируем в течение часа.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Чем тарифы различаются, если отклики безлимитные?</summary>
        <div class="faq-answer">
            Только <strong>комиссией</strong> и дополнительными возможностями:
            <br><br>
            <strong>Free</strong> — платите 3% с каждой сделки. Например, заказ на 100 000 ₽ → комиссия 3 000 ₽, вы получаете 97 000 ₽.
            <br><br>
            <strong>Basic</strong> (1 990 ₽/мес) — комиссия 0%, + приоритет в выдаче. Окупается с 2 сделок.
            <br><br>
            <strong>Pro</strong> (4 990 ₽/мес) — комиссия 0%, + ЭДО, мульти-аккаунт, персональный менеджер.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Есть ли скидка за годовую оплату?</summary>
        <div class="faq-answer">
            Да! При оплате за год — <strong>скидка 17%</strong> (2 месяца в подарок):
            <ul style="margin:8px 0 0 20px;">
                <li>Basic: <s>23 880 ₽</s> → <strong>19 908 ₽/год</strong></li>
                <li>Pro: <s>59 880 ₽</s> → <strong>49 900 ₽/год</strong></li>
            </ul>
            <br>
            Переключатель «Месяц / Год» — наверху страницы. При выборе «Год» цены на карточках пересчитываются автоматически.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Можно ли сменить тариф?</summary>
        <div class="faq-answer">
            Да, в любой момент. Остаток дней по текущему тарифу будет учтён при оплате нового. Для перехода с Basic на Pro доплатите разницу пропорционально оставшимся дням.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Чем AVL Logistic отличается от ATI.SU?</summary>
        <div class="faq-answer">
            <strong>1. Комиссия:</strong> ATI берёт деньги за доступ и доп. услуги. Мы — за результат: 3% с оборота или фикс.<br>
            <strong>2. Эскроу:</strong> у нас встроено бесплатно (деньги защищены до выгрузки), у ATI — отдельно 2-4.5%.<br>
            <strong>3. Документы:</strong> у нас формируются автоматом, включая отчёт агента по ст. 1008 ГК РФ.<br>
            <strong>4. Оплата:</strong> у нас — карта, СБП через Tinkoff. У ATI — только банковский перевод.<br>
            <strong>5. Регистрация:</strong> у нас 2 минуты, у ATI 2-3 дня на проверку.<br>
            <strong>6. Отклики:</strong> у нас безлимит на всех тарифах.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Нужно ли платить грузовладельцам?</summary>
        <div class="faq-answer">
            Нет, для грузовладельцев платформа полностью бесплатна. Мы берём подписку только с перевозчиков. Никаких комиссий, скрытых платежей и ограничений.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Как работает возврат средств?</summary>
        <div class="faq-answer">
            Мы возвращаем деньги в течение 7 дней с момента оплаты, если вы не использовали подписку (не было успешных сделок). Напишите на <a href="mailto:info@avllogist.ru">info@avllogist.ru</a> с номером платежа.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Как быстро активируется подписка?</summary>
        <div class="faq-answer">
            При оплате картой — <strong>автоматически в течение 1–2 минут</strong> после подтверждения платежа банком. При оплате по счёту — в течение часа после того, как вы напишете нам о переводе.
        </div>
    </details>
    
    <details class="faq-item">
        <summary>Можно ли получить счёт для бухгалтерии?</summary>
        <div class="faq-answer">
            Да. После оплаты мы формируем акт выполненных работ и счёт-фактуру (если применимо). Все документы доступны в разделе <a href="/invoices">Счета</a>. ИП на УСН — без НДС.
        </div>
    </details>
</div>

<script>
/**
 * КАЛЬКУЛЯТОР
 */
(function() {
    const ordersSlider = document.getElementById('calcOrders');
    const avgSlider = document.getElementById('calcAvg');
    const ordersVal = document.getElementById('calcOrdersVal');
    const avgVal = document.getElementById('calcAvgVal');
    const calcFree = document.getElementById('calcFree');
    const calcBasic = document.getElementById('calcBasic');
    const calcPro = document.getElementById('calcPro');
    const calcVerdict = document.getElementById('calcVerdict');
    const basicCard = document.getElementById('calcBasicCard');

    const BASIC_PRICE = 1990;
    const PRO_PRICE = 4990;
    const FREE_RATE = 0.03;

    function fmt(n) {
        return new Intl.NumberFormat('ru-RU').format(Math.round(n));
    }

    function update() {
        const orders = parseInt(ordersSlider.value, 10);
        const avg = parseInt(avgSlider.value, 10);

        const freeCost = FREE_RATE * orders * avg;
        const basicCost = BASIC_PRICE;
        const proCost = PRO_PRICE;

        ordersVal.textContent = orders;
        avgVal.textContent = fmt(avg);

        calcFree.textContent = fmt(freeCost) + ' ₽';
        calcBasic.textContent = fmt(basicCost) + ' ₽';
        calcPro.textContent = fmt(proCost) + ' ₽';

        const savingsBasic = freeCost - basicCost;
        const savingsPro = freeCost - proCost;

        let best = 'free';
        if (savingsBasic > 0 && savingsBasic >= savingsPro) best = 'basic';
        else if (savingsPro > 0) best = 'pro';

        document.querySelectorAll('.calc-result').forEach(c => c.classList.remove('best'));

        if (best === 'basic') basicCard.classList.add('best');
        else if (best === 'pro') document.getElementById('calcPro').closest('.calc-result').classList.add('best');
        else document.getElementById('calcFree').closest('.calc-result').classList.add('best');

        if (best === 'free') {
            calcVerdict.className = 'calc-verdict economy-bad';
            calcVerdict.innerHTML = '⚠️ При таком объёме выгоднее <strong>оставаться на Free</strong>. Комиссия ' + fmt(freeCost) + ' ₽/мес меньше стоимости подписки.';
        } else if (best === 'basic') {
            calcVerdict.className = 'calc-verdict';
            calcVerdict.innerHTML = '✅ Экономия на Basic: <strong>' + fmt(savingsBasic) + ' ₽/мес</strong> · <strong>' + fmt(savingsBasic * 12) + ' ₽/год</strong>';
        } else {
            calcVerdict.className = 'calc-verdict';
            calcVerdict.innerHTML = '🏆 Pro выгоднее на <strong>' + fmt(savingsPro) + ' ₽/мес</strong> · <strong>' + fmt(savingsPro * 12) + ' ₽/год</strong> (по сравнению с Free)';
        }
    }

    ordersSlider.addEventListener('input', update);
    avgSlider.addEventListener('input', update);
    update();
})();

/**
 * ПЕРЕКЛЮЧАТЕЛЬ МЕСЯЦ / ГОД
 */
(function() {
    const toggle = document.getElementById('periodToggle');
    const labels = toggle.querySelectorAll('label');
    const monthlyPrices = document.querySelectorAll('.price-monthly');
    const yearlyPrices = document.querySelectorAll('.price-yearly');
    const yearlyNotes = document.querySelectorAll('.yearly-price');
    const basicPeriod = document.getElementById('basicPeriod');
    const proPeriod = document.getElementById('proPeriod');

    function setPeriod(period) {
        labels.forEach(l => l.classList.toggle('active', l.dataset.period === period));

        if (period === 'year') {
            monthlyPrices.forEach(el => el.style.display = 'none');
            yearlyPrices.forEach(el => el.style.display = 'inline');
            yearlyNotes.forEach(el => el.style.display = 'block');
            if (basicPeriod) basicPeriod.textContent = 'при оплате за год · скидка 17%';
            if (proPeriod) proPeriod.textContent = 'при оплате за год · скидка 17%';
        } else {
            monthlyPrices.forEach(el => el.style.display = 'inline');
            yearlyPrices.forEach(el => el.style.display = 'none');
            yearlyNotes.forEach(el => el.style.display = 'none');
            if (basicPeriod) basicPeriod.textContent = 'в месяц · экономия до 40 000 ₽/год';
            if (proPeriod) proPeriod.textContent = 'в месяц · для автопарков';
        }
    }

    labels.forEach(l => {
        l.addEventListener('click', () => setPeriod(l.dataset.period));
    });
})();

/**
 * ОПЛАТА — учитывает переключатель Месяц/Год
 */
async function payWithCard(plan, btn) {
    if (btn.disabled) return;

    const activePeriod = document.querySelector('.period-toggle label.active');
    const period = activePeriod ? activePeriod.dataset.period : 'month';
    const realPlan = (period === 'year') ? (plan + '_yearly') : plan;

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Создаём платёж...';

    try {
        const resp = await fetch('/tinkoff_init.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ plan: realPlan })
        });

        const data = await resp.json();

        if (!resp.ok || !data.success) {
            const msg = data.error || 'Не удалось создать платёж';
            alert('Ошибка: ' + msg);
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }

        const paymentUrl = data.data.payment_url;
        if (!paymentUrl) {
            alert('Ошибка: банк не вернул ссылку для оплаты');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }

        try {
            sessionStorage.setItem('avl_last_invoice', data.data.invoice_id);
            sessionStorage.setItem('avl_last_payment_id', data.data.payment_id);
        } catch (e) {}

        btn.innerHTML = '➡️ Переходим на оплату...';
        window.location.href = paymentUrl;

    } catch (err) {
        console.error('[payWithCard] error:', err);
        alert('Не удалось связаться с сервером. Попробуйте ещё раз.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>