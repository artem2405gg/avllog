<?php
require_once BASE_PATH . '/templates/header.php';

global $pdo;

$statusLabels = [
    'new'         => ['label' => 'Новый',                'icon' => '🆕'],
    'accepted'    => ['label' => 'Перевозчик назначен',  'icon' => '🤝'],
    'pickup'      => ['label' => 'Выезд на погрузку',    'icon' => '🚛'],
    'loaded'      => ['label' => 'Груз погружен',        'icon' => '📦'],
    'in_transit'  => ['label' => 'В пути',               'icon' => '🛣️'],
    'arrived'     => ['label' => 'Прибыл на выгрузку',   'icon' => '📍'],
    'unloaded'    => ['label' => 'Груз выгружен',        'icon' => '✅'],
    'completed'   => ['label' => 'Сделка завершена',     'icon' => '🎉'],
    'cancelled'   => ['label' => 'Отменён',              'icon' => '❌'],
    'hidden'      => ['label' => 'Скрыт',                'icon' => '🔒'],
];

$currentStatus = $statusLabels[$order['status']] ?? $statusLabels['new'];

$isOwner   = ($order['user_id'] == $_SESSION['user_id']);
$isCarrier = false;
$acceptedBid = null;

$stmt = $pdo->prepare("SELECT * FROM bids WHERE order_id = ? AND status = 'accepted'");
$stmt->execute([$order['id']]);
$acceptedBid = $stmt->fetch();

if ($acceptedBid && $acceptedBid['carrier_id'] == $_SESSION['user_id']) {
    $isCarrier = true;
}

// === ПРАВО РЕДАКТИРОВАНИЯ ===
$canEdit = ($isOwner && in_array($order['status'], ['new', 'hidden']));

// Информация о перевозчике
$carrierInfo = null;
if ($acceptedBid) {
    $stmt = $pdo->prepare("SELECT name, company_name, phone, rating, bank_details FROM users WHERE id = ?");
    $stmt->execute([$acceptedBid['carrier_id']]);
    $carrierInfo = $stmt->fetch();
}

// Информация о заказчике
$stmt = $pdo->prepare("
    SELECT u.name, u.company_name, u.rating, u.inn, u.created_at,
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'completed') as completed_orders
    FROM users u WHERE u.id = ?
");
$stmt->execute([$order['user_id']]);
$ownerInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Сканы
$stmt = $pdo->prepare("SELECT * FROM documents WHERE order_id = ? AND type = 'scan' ORDER BY created_at DESC");
$stmt->execute([$order['id']]);
$scans = $stmt->fetchAll();

// Мой отклик (для carrier'а)
$myBid = null;
if ($_SESSION['user_role'] === 'carrier') {
    $stmt = $pdo->prepare("SELECT * FROM bids WHERE order_id = ? AND carrier_id = ?");
    $stmt->execute([$order['id'], $_SESSION['user_id']]);
    $myBid = $stmt->fetch();
}

$statusOrder = ['new', 'accepted', 'pickup', 'loaded', 'in_transit', 'arrived', 'unloaded', 'completed'];
$currentStep = array_search($order['status'], $statusOrder);
if ($currentStep === false) $currentStep = 0;
$totalSteps = count($statusOrder) - 1;
$progress = $order['status'] === 'cancelled' ? 0 : round(($currentStep / $totalSteps) * 100);

$displayPrice = $order['price'];
$isNew = (strtotime($order['created_at']) ?? 0) > time() - 86400;

// === Логика поэтапной оплаты ===
$commissionRate   = (float)($order['commission_rate'] ?? 0);
$commissionAmount = (float)($order['commission_amount'] ?? 0);
$carrierPayout    = (float)($order['carrier_payout'] ?? ($acceptedBid['price'] ?? 0));
$commissionPaid   = !empty($order['commission_paid']);

$prepaymentPercent   = (int)($order['prepayment_percent'] ?? 30);
$prepaymentAmount    = (float)($order['prepayment_amount'] ?? round($carrierPayout * 0.3, 2));
$finalAmount         = (float)($order['final_amount'] ?? round($carrierPayout * 0.7, 2));
$prepaymentPaid      = !empty($order['prepayment_paid']);
$prepaymentConfirmed = !empty($order['prepayment_confirmed']);
$finalPaid           = !empty($order['final_paid']);
$finalConfirmed      = !empty($order['final_confirmed']);

// Агентская схема: достаточно оплаты заказа
$commissionOk = $commissionPaid || $commissionAmount <= 0;
$canStartPickup = $commissionOk;

// === Агентская схема ===
$payoutToCarrierAt  = $order['payout_to_carrier_at'] ?? null;
$agentReportNumber  = $order['agent_report_number'] ?? null;
$payoutDocument     = $order['payout_document'] ?? null;

$totalPayment = $commissionAmount + $carrierPayout;

// === Окна времени, контакты, особые условия ===
$timeLabels = [
    'morning' => '🌅 Утро (8:00–12:00)',
    'day'     => '☀️ День (12:00–17:00)',
    'evening' => '🌆 Вечер (17:00–21:00)',
    'exact'   => '🕐 Точно',
];

$pickupWindowText = '';
if (!empty($order['pickup_time_window'])) {
    $pickupWindowText = $timeLabels[$order['pickup_time_window']] ?? '';
    if ($order['pickup_time_window'] === 'exact' && !empty($order['pickup_time_exact'])) {
        $pickupWindowText = '🕐 В ' . substr($order['pickup_time_exact'], 0, 5);
    }
}

$deliveryWindowText = '';
if (!empty($order['delivery_time_window'])) {
    $deliveryWindowText = $timeLabels[$order['delivery_time_window']] ?? '';
    if ($order['delivery_time_window'] === 'exact' && !empty($order['delivery_time_exact'])) {
        $deliveryWindowText = '🕐 В ' . substr($order['delivery_time_exact'], 0, 5);
    }
}

$specialConditions = [];
if (!empty($order['requires_pass']))     $specialConditions[] = ['🚪', 'Требуется пропуск'];
if (!empty($order['unload_on_floor']))   $specialConditions[] = ['🏢', 'Разгрузка на этаже'];
if (!empty($order['needs_manipulator'])) $specialConditions[] = ['🏗️', 'Нужен манипулятор'];
if (!empty($order['is_fragile']))        $specialConditions[] = ['⚠️', 'Хрупкий груз'];
?>

<style>
    .breadcrumbs {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
        overflow: hidden;
        min-width: 0;
    }
    .breadcrumbs a { color: var(--text-secondary); text-decoration: none; transition: color 0.2s; white-space: nowrap; }
    .breadcrumbs a:hover { color: var(--accent); }
    .breadcrumbs .sep { opacity: 0.4; flex-shrink: 0; }
    .breadcrumbs .current {
        color: var(--text);
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }

    .order-top {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 24px;
        padding: 24px;
        background: var(--surface);
        border: 1px solid var(--border);
        margin-bottom: 16px;
        align-items: start;
    }
    .order-top-left { min-width: 0; }
    .order-top-title {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        word-break: break-word;
    }
    .order-top-title .badge-new {
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        background: var(--accent);
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .order-top-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: var(--border-light);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .order-top-right {
        text-align: right;
        min-width: 220px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: flex-end;
    }
    .order-price-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        font-weight: 700;
        margin-bottom: 2px;
    }
    .order-price {
        font-size: 32px;
        font-weight: 900;
        color: var(--success);
        font-family: var(--font-mono);
        letter-spacing: -1.5px;
        line-height: 1;
    }
    .order-top-right .btn { width: 100%; padding: 14px 24px; font-size: 14px; }

    .progress-wrap {
        padding: 16px 24px;
        background: var(--surface);
        border: 1px solid var(--border);
        margin-bottom: 16px;
    }
    .progress-bar { background: var(--border-light); height: 6px; overflow: hidden; }
    .progress-fill { background: linear-gradient(90deg, #4361ee, #2ec4b6); height: 100%; transition: width 0.5s; }
    .status-steps { display: flex; justify-content: space-between; font-size: 10px; color: var(--text-secondary); gap: 4px; margin-top: 10px; }
    .status-step { text-align: center; flex: 1; min-width: 50px; }
    .status-step.done { color: #4361ee; font-weight: 600; }
    .status-step.current { color: #2ec4b6; font-weight: 700; }
    .status-step .dot { width: 8px; height: 8px; background: var(--border); margin: 4px auto; }
    .status-step.done .dot { background: #4361ee; }
    .status-step.current .dot { background: #2ec4b6; width: 10px; height: 10px; }

    .order-body {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 16px;
        align-items: start;
    }
    .order-main { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .order-side { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 90px; }

    .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        min-width: 0;
    }
    .panel-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .route-compact {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 16px;
        align-items: center;
        padding: 20px;
        background: var(--border-light);
        margin-bottom: 16px;
    }
    .route-point { min-width: 0; }
    .route-point-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .route-point-city { font-size: 18px; font-weight: 800; letter-spacing: -0.3px; line-height: 1.3; word-break: break-word; }
    .route-point-address { font-size: 12px; color: var(--text-secondary); margin-top: 4px; line-height: 1.4; word-break: break-word; }
    .route-arrow { font-size: 24px; color: var(--accent); text-align: center; }

    .cargo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .cargo-item { padding: 12px; background: var(--border-light); text-align: center; min-width: 0; }
    .cargo-item .label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-secondary);
        font-weight: 700;
        margin-bottom: 4px;
    }
    .cargo-item .value { font-size: 16px; font-weight: 800; letter-spacing: -0.3px; word-break: break-word; }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
        gap: 12px;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .label { color: var(--text-secondary); flex-shrink: 0; }
    .info-row .value { font-weight: 700; text-align: right; word-break: break-word; }

    .customer-card { display: flex; flex-direction: column; gap: 12px; }
    .customer-header { display: flex; align-items: center; gap: 12px; }
    .customer-avatar {
        width: 48px; height: 48px;
        background: var(--primary); color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; font-weight: 800; flex-shrink: 0;
    }
    .customer-name { font-weight: 800; font-size: 15px; line-height: 1.3; word-break: break-word; }
    .customer-meta { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
    .customer-rating {
        display: flex; align-items: center; gap: 6px;
        padding: 8px 12px; background: var(--border-light);
        font-size: 13px; font-weight: 700;
    }
    .verified-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; color: var(--success); }

    .status-actions { display: flex; flex-direction: column; gap: 10px; }
    .status-actions .btn { width: 100%; padding: 14px 20px; font-size: 14px; }

    .payment-block {
        background: #fffbeb;
        border: 1px solid #f59e0b;
        padding: 20px;
        min-width: 0;
    }
    .payment-block h3 { font-size: 16px; font-weight: 800; margin-bottom: 16px; }
    
    .payment-stage {
        background: white;
        padding: 16px;
        margin-bottom: 12px;
        min-width: 0;
    }
    .payment-stage:last-child { margin-bottom: 0; }
    .payment-stage h4 {
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .payment-status {
        font-size: 13px;
        font-weight: 700;
        padding: 8px 0;
        word-break: break-word;
    }
    .payment-status.success { color: var(--success); }
    .payment-status.pending { color: #f59e0b; }
    .payment-status.info { color: var(--text-secondary); }
    .payment-status.danger { color: var(--danger); }

    .timeline { position: relative; padding-left: 24px; }
    .timeline::before { content: ''; position: absolute; left: 8px; top: 6px; bottom: 6px; width: 2px; background: var(--border); }
    .timeline-item { position: relative; margin-bottom: 16px; }
    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-dot {
        position: absolute; left: -20px; top: 4px;
        width: 10px; height: 10px; background: var(--accent);
        box-shadow: 0 0 0 3px var(--surface);
    }

    .bid-card {
        padding: 14px;
        border: 1px solid var(--border);
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .bid-card:last-child { margin-bottom: 0; }
    .bid-card .bid-info { flex: 1; min-width: 0; }
    .bid-card .bid-carrier { font-weight: 800; font-size: 14px; word-break: break-word; }
    .bid-card .bid-price { font-size: 18px; font-weight: 900; color: var(--success); font-family: var(--font-mono); }

    .scan-preview {
        display: inline-block;
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }
    .scan-preview:hover { border-color: var(--accent); }
    .scan-preview img { width: 120px; height: 120px; object-fit: cover; display: block; }
    .scan-preview .scan-icon {
        width: 120px; height: 120px;
        display: flex; align-items: center; justify-content: center;
        font-size: 36px; background: var(--border-light);
    }
    .scan-preview .scan-name { font-size: 10px; padding: 4px 6px; text-align: center; background: var(--border-light); }

    .conditions-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }
    .condition-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #fef3c7;
        color: #92400e;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #fde68a;
    }
    .special-comment-box {
        background: var(--border-light);
        padding: 14px 16px;
        border-left: 3px solid var(--accent);
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Внутренние «quick actions» для мобилки */
    .btn-map-link {
        display: inline-block;
        font-size: 12px;
        color: var(--accent);
        text-decoration: none;
        font-weight: 700;
    }

    /* ============================================================ */
    /* МОБИЛЬНАЯ ОПТИМИЗАЦИЯ                                        */
    /* ============================================================ */
    @media (max-width: 900px) {
        .breadcrumbs { font-size: 12px; margin-bottom: 12px; }

        .order-top {
            grid-template-columns: 1fr;
            padding: 16px;
            gap: 14px;
        }
        .order-top-right {
            min-width: 0;
            width: 100%;
            align-items: stretch;
            text-align: left;
            gap: 8px;
        }
        .order-top-right .btn { padding: 12px 16px; font-size: 13px; }
        .order-top-title { font-size: 20px; }
        .order-price { font-size: 28px; }

        .progress-wrap { padding: 12px 16px; margin-bottom: 12px; }
        .status-steps { font-size: 9px; }
        .status-step { min-width: 0; }
        .status-step .dot { width: 6px; height: 6px; margin: 3px auto; }
        .status-step.current .dot { width: 8px; height: 8px; }

        .order-body { grid-template-columns: 1fr; gap: 12px; }
        .order-side { position: static; gap: 12px; }

        .panel { padding: 16px; margin-bottom: 0; }
        .panel-title { font-size: 11px; margin-bottom: 12px; }

        .route-compact {
            grid-template-columns: 1fr;
            text-align: left;
            padding: 14px;
            gap: 10px;
        }
        .route-arrow { transform: rotate(90deg); margin: -4px 0; }
        .route-point-city { font-size: 16px; }
        .route-point-address { font-size: 11px; }

        .cargo-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .cargo-item { padding: 10px 8px; }
        .cargo-item .value { font-size: 14px; }

        .info-row { font-size: 12px; padding: 8px 0; gap: 8px; }

        .customer-avatar { width: 42px; height: 42px; font-size: 18px; }
        .customer-name { font-size: 14px; }
        .customer-rating { padding: 6px 10px; font-size: 12px; }

        .status-actions { flex-direction: row; flex-wrap: wrap; gap: 8px; }
        .status-actions .btn { width: auto; flex: 1; min-width: 130px; padding: 12px 14px; font-size: 13px; }

        .payment-block { padding: 16px; }
        .payment-block h3 { font-size: 15px; margin-bottom: 12px; }
        .payment-stage { padding: 12px; margin-bottom: 10px; }
        .payment-stage h4 { font-size: 12px; }
        .payment-status { font-size: 12px; padding: 6px 0; }

        .timeline { padding-left: 20px; }
        .timeline-item { margin-bottom: 12px; }
        .timeline-dot { left: -17px; width: 8px; height: 8px; }

        .bid-card { padding: 12px; gap: 8px; }
        .bid-card .bid-price { font-size: 16px; }
        .bid-card .btn { padding: 8px 12px; font-size: 11px; }

        .scan-preview img,
        .scan-preview .scan-icon { width: 90px; height: 90px; }
        .scan-preview .scan-icon { font-size: 28px; }

        .condition-badge { padding: 6px 10px; font-size: 11px; }
        .special-comment-box { padding: 12px 14px; font-size: 12px; }

        .btn-map-link {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            background: var(--border-light);
            border-radius: 6px;
            margin-top: 8px;
            font-size: 13px;
        }

        /* Форма отклика — iOS не зумит при 16px */
        input[name="price"] {
            font-size: 18px !important;
        }
    }

    /* ============================================================ */
    /* ОЧЕНЬ УЗКИЕ ЭКРАНЫ (iPhone SE, старые Android)              */
    /* ============================================================ */
    @media (max-width: 480px) {
        .order-top { padding: 14px; }
        .order-top-title { font-size: 18px; }
        .order-price { font-size: 24px; }
        .order-top-right .btn { padding: 11px 14px; font-size: 12px; }

        .status-steps { gap: 2px; }
        .status-step { font-size: 0; } /* скрываем подписи */
        .status-step .dot { margin: 0 auto; }

        .panel { padding: 14px; }
        .panel-title { font-size: 10px; letter-spacing: 1.2px; }

        .route-point-city { font-size: 15px; }

        .cargo-grid { grid-template-columns: 1fr 1fr; gap: 6px; }
        .cargo-item { padding: 8px 6px; }
        .cargo-item .label { font-size: 9px; }
        .cargo-item .value { font-size: 13px; }

        .customer-avatar { width: 38px; height: 38px; font-size: 16px; }

        .payment-stage h4 { font-size: 11px; }

        .status-actions .btn { min-width: 100%; }
    }
</style>

<!-- Хлебные крошки -->
<div class="breadcrumbs">
    <a href="/orders">📋 Заказы</a>
    <span class="sep">/</span>
    <span class="current">#<?= $order['id'] ?> — <?= htmlspecialchars($order['title']) ?></span>
</div>

<!-- Flash-сообщения -->
<?php if (isset($_GET['scan_uploaded'])): ?><div class="alert alert-success">✅ Скан загружен!</div><?php endif; ?>
<?php if (isset($_GET['status_updated'])): ?><div class="alert alert-success">✅ Статус обновлён!</div><?php endif; ?>
<?php if (isset($_GET['payout_created'])): ?><div class="alert alert-success">✅ Выгрузка подтверждена! Отчёт агента сформирован, деньги будут переведены в течение 3 рабочих дней.</div><?php endif; ?>

<!-- Верхний блок -->
<div class="order-top">
    <div class="order-top-left">
        <div class="order-top-title">
            <?= htmlspecialchars($order['title']) ?>
            <?php if ($isNew && $order['status'] === 'new'): ?>
                <span class="badge-new">🔥 Новый</span>
            <?php endif; ?>
        </div>
        <div class="order-top-status">
            <?= $currentStatus['icon'] ?> <?= $currentStatus['label'] ?>
        </div>
    </div>
    
    <div class="order-top-right">
        <div>
            <div class="order-price-label"><?= $acceptedBid ? 'Цена перевозки' : 'Бюджет заказа' ?></div>
            <div class="order-price"><?= number_format((float)$displayPrice, 0, ',', ' ') ?> ₽</div>
        </div>
        
        <?php if ($canEdit): ?>
            <a href="/orders/edit/<?= $order['id'] ?>" class="btn btn-primary">✏️ Редактировать</a>
        <?php endif; ?>
        
        <?php if ($isCarrier && !$myBid && $order['status'] == 'new'): ?>
            <a href="#bid-form" class="btn btn-accent">💰 Предложить цену</a>
        <?php elseif ($myBid && $order['status'] == 'new'): ?>
            <div style="padding: 12px; background:#f0fdf4; color:#166534; font-size:13px; font-weight:700; text-align:center;">
                ✅ Вы откликнулись: <?= number_format($myBid['price'], 0, ',', ' ') ?> ₽
            </div>
        <?php elseif ($order['status'] != 'new' && $order['status'] != 'cancelled' && $order['status'] != 'hidden'): ?>
            <a href="/chat/view/<?= $order['id'] ?>" class="btn btn-outline">💬 Открыть чат</a>
        <?php endif; ?>
        
        <?php if ($isOwner && $order['status'] == 'new' && !empty($bids)): ?>
            <a href="#bids-section" class="btn btn-primary">📥 Отклики (<?= count($bids) ?>)</a>
        <?php endif; ?>
    </div>
</div>

<!-- Прогресс-бар -->
<?php if ($order['status'] != 'cancelled' && $order['status'] != 'new' && $order['status'] != 'hidden'): ?>
<div class="progress-wrap">
    <div class="progress-bar"><div class="progress-fill" style="width: <?= $progress ?>%;"></div></div>
    <div class="status-steps">
        <?php 
        $stepNames = ['new'=>'Новый','accepted'=>'Назначен','pickup'=>'Погрузка','loaded'=>'Загружен','in_transit'=>'В пути','arrived'=>'Прибыл','unloaded'=>'Выгружен','completed'=>'Завершён'];
        foreach ($statusOrder as $key):
            $si = array_search($key, $statusOrder);
            $cls = $si < $currentStep ? 'done' : ($si == $currentStep ? 'current' : '');
        ?>
            <div class="status-step <?= $cls ?>"><div class="dot"></div><?= $stepNames[$key] ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- 2 колонки -->
<div class="order-body">
    
    <!-- ЛЕВАЯ КОЛОНКА -->
    <div class="order-main">
        
        <!-- Маршрут -->
        <div class="panel">
            <div class="panel-title">🗺 Маршрут перевозки</div>
            <div class="route-compact">
                <div class="route-point">
                    <div class="route-point-label">📍 Откуда</div>
                    <div class="route-point-city"><?= htmlspecialchars(explode(',', $order['pickup_address'])[0]) ?></div>
                    <div class="route-point-address"><?= htmlspecialchars($order['pickup_address']) ?></div>
                </div>
                <div class="route-arrow">→</div>
                <div class="route-point">
                    <div class="route-point-label">🏁 Куда</div>
                    <div class="route-point-city"><?= htmlspecialchars(explode(',', $order['delivery_address'])[0]) ?></div>
                    <div class="route-point-address"><?= htmlspecialchars($order['delivery_address']) ?></div>
                </div>
            </div>
            
            <div class="info-row">
                <span class="label">📅 Погрузка</span>
                <span class="value">
                    <?= date('d.m.Y', strtotime($order['pickup_date'])) ?>
                    <?php if ($pickupWindowText): ?>
                        · <?= $pickupWindowText ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="label">📅 Доставка</span>
                <span class="value">
                    <?= date('d.m.Y', strtotime($order['delivery_date'])) ?>
                    <?php if ($deliveryWindowText): ?>
                        · <?= $deliveryWindowText ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if (!empty($order['pickup_contact_name']) || !empty($order['pickup_contact_phone']) || !empty($order['delivery_contact_name']) || !empty($order['delivery_contact_phone'])): ?>
                <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border-light);">
                    <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:var(--text-secondary); margin-bottom:10px;">
                        📞 Контакты на точках
                    </div>
                    <?php if (!empty($order['pickup_contact_name']) || !empty($order['pickup_contact_phone'])): ?>
                        <div class="info-row">
                            <span class="label">📍 Погрузка</span>
                            <span class="value">
                                <?= htmlspecialchars($order['pickup_contact_name'] ?? '') ?>
                                <?php if (!empty($order['pickup_contact_phone'])): ?>
                                    <br><a href="tel:<?= htmlspecialchars($order['pickup_contact_phone']) ?>" style="color:var(--accent); text-decoration:none;"><?= htmlspecialchars($order['pickup_contact_phone']) ?></a>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['delivery_contact_name']) || !empty($order['delivery_contact_phone'])): ?>
                        <div class="info-row">
                            <span class="label">🏁 Выгрузка</span>
                            <span class="value">
                                <?= htmlspecialchars($order['delivery_contact_name'] ?? '') ?>
                                <?php if (!empty($order['delivery_contact_phone'])): ?>
                                    <br><a href="tel:<?= htmlspecialchars($order['delivery_contact_phone']) ?>" style="color:var(--accent); text-decoration:none;"><?= htmlspecialchars($order['delivery_contact_phone']) ?></a>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <a href="https://yandex.ru/maps/?rtext=<?= urlencode($order['pickup_address']) ?>~<?= urlencode($order['delivery_address']) ?>&rtt=auto" 
               target="_blank" 
               class="btn-map-link">
                🗺 Показать на Яндекс.Картах →
            </a>
        </div>
        
        <!-- Груз -->
        <div class="panel">
            <div class="panel-title">📦 Характеристики груза</div>
            <div class="cargo-grid" style="grid-template-columns: <?= !empty($order['vehicle_type']) ? 'repeat(4, 1fr)' : 'repeat(3, 1fr)' ?>;">
                <div class="cargo-item">
                    <div class="label">Тип груза</div>
                    <div class="value"><?= htmlspecialchars($order['cargo_type']) ?></div>
                </div>
                <?php if (!empty($order['vehicle_type'])): ?>
                <div class="cargo-item">
                    <div class="label">Тип ТС</div>
                    <div class="value">🚛 <?= htmlspecialchars($order['vehicle_type']) ?></div>
                </div>
                <?php endif; ?>
                <div class="cargo-item">
                    <div class="label">Вес</div>
                    <div class="value"><?= $order['weight'] ?> т</div>
                </div>
                <div class="cargo-item">
                    <div class="label">Объём</div>
                    <div class="value"><?= $order['volume'] ?> м³</div>
                </div>
            </div>
        </div>
        
        <!-- Особые условия -->
        <?php if (!empty($specialConditions) || !empty($order['special_comment'])): ?>
        <div class="panel">
            <div class="panel-title">⚠️ Особые условия</div>
            
            <?php if (!empty($specialConditions)): ?>
                <div class="conditions-badges">
                    <?php foreach ($specialConditions as $cond): ?>
                        <span class="condition-badge">
                            <span><?= $cond[0] ?></span>
                            <span><?= $cond[1] ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($order['special_comment'])): ?>
                <div class="special-comment-box">
                    💬 <?= nl2br(htmlspecialchars($order['special_comment'])) ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Агентская схема: подтверждение выгрузки -->
        <?php if ($isOwner && $order['status'] === 'unloaded' && empty($payoutToCarrierAt) && $commissionOk): ?>
            <div style="background:#fef3c7;border-left:4px solid var(--accent);padding:20px;">
                <h3 style="margin:0 0 8px;font-size:16px;">💸 Груз выгружен — подтвердите для перевода</h3>
                <p style="font-size:14px;margin:0 0 12px;color:var(--text-secondary);line-height:1.6;">
                    Перевозчик выгрузил груз. Подтвердите — платформа сформирует 
                    <strong>Отчёт агента</strong> и переведёт перевозчику 
                    <strong><?= number_format((float)$order['carrier_payout'], 0, ',', ' ') ?> ₽</strong>
                    в течение 3 рабочих дней.
                </p>
                <form method="POST" action="/orders/confirm_unload/<?= $order['id'] ?>" style="display:inline;">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Подтвердить выгрузку и запустить выплату перевозчику?');">
                        ✅ Подтвердить выгрузку и перевести
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($agentReportNumber)): ?>
            <div style="background:#f0fdf4;border-left:4px solid var(--success);padding:16px;">
                <h3 style="margin:0 0 8px;font-size:14px;">📋 Отчёт агента №<?= htmlspecialchars($agentReportNumber) ?></h3>
                <a href="/agent_report/<?= $order['id'] ?>" class="btn btn-outline" style="font-size:13px;padding:6px 12px;">
                    Открыть отчёт →
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($payoutToCarrierAt)): ?>
            <div style="background:#f0fdf4;border-left:4px solid var(--success);padding:16px;">
                <div style="font-size:13px;color:#065f46;font-weight:700;">
                    ✅ Деньги переведены перевозчику <?= date('d.m.Y', strtotime($payoutToCarrierAt)) ?>
                    <?php if (!empty($payoutDocument)): ?>
                        (ПП №<?= htmlspecialchars($payoutDocument) ?>)
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Расчёты по заказу -->
        <?php if (in_array($order['status'], ['accepted', 'pickup', 'loaded', 'in_transit', 'arrived', 'unloaded', 'completed']) && $acceptedBid): ?>
        <div class="payment-block">
            <h3>💰 Расчёты по заказу</h3>
            
            <div class="info-row">
                <span class="label">Стоимость перевозки</span>
                <span class="value"><?= number_format((float)$order['price'], 0, ',', ' ') ?> ₽</span>
            </div>
            <?php if ($commissionRate > 0): ?>
            <div class="info-row">
                <span class="label" style="color:#92400e;">Комиссия (<?= number_format($commissionRate * 100, 1, '.', '') ?>%)</span>
                <span class="value" style="color:#92400e;"><?= number_format($commissionAmount, 0, ',', ' ') ?> ₽</span>
            </div>
            <?php endif; ?>
            <div class="info-row" style="border-top:2px solid #f59e0b; border-bottom:none; padding-top:12px; margin-top:8px;">
                <span class="label" style="font-weight:800; color:var(--text); font-size:14px;">Перевозчику</span>
                <span class="value" style="font-size:18px; color:var(--success);"><?= number_format($carrierPayout, 0, ',', ' ') ?> ₽</span>
            </div>
            
            <div class="payment-stage" style="margin-top:16px;">
                <h4>1️⃣ Оплата через Tinkoff</h4>
                <?php if ($commissionPaid): ?>
                    <div class="payment-status success">✅ Оплачено<?= !empty($order['commission_paid_at']) ? ' — ' . date('d.m.Y H:i', strtotime($order['commission_paid_at'])) : '' ?></div>
                    <div style="background:#f0fdf4;padding:10px;margin-top:8px;font-size:12px;color:#065f46;border-left:3px solid #10b981;">
                        🔒 Деньги заморожены на счёте платформы и будут переведены перевозчику после подтверждения выгрузки.
                    </div>
                <?php elseif ($isOwner): ?>
                    <div class="payment-status info" style="margin-bottom:12px;">
                        Оплатите через Tinkoff: комиссия <strong><?= number_format($commissionAmount, 0, ',', ' ') ?> ₽</strong>
                        + перевозка <strong><?= number_format($carrierPayout, 0, ',', ' ') ?> ₽</strong>
                        = <strong style="color:var(--accent-dark);"><?= number_format($totalPayment, 0, ',', ' ') ?> ₽</strong>
                    </div>
                    <div style="background:#f0fdf4;padding:10px;margin-bottom:10px;font-size:12px;color:#065f46;border-left:3px solid #10b981;">
                        🔒 <strong>Защита сделки:</strong> деньги замораживаются на счёте платформы 
                        и будут переведены перевозчику после подтверждения выгрузки.
                    </div>
                    <a href="/order_pay.php?order_id=<?= $order['id'] ?>" class="btn btn-accent btn-sm">
                        💳 Оплатить <?= number_format($totalPayment, 0, ',', ' ') ?> ₽
                    </a>
                <?php else: ?>
                    <div class="payment-status pending">⏳ Ожидаем оплату от заказчика</div>
                <?php endif; ?>
            </div>
            
            <?php if ($commissionPaid): ?>
            <div class="payment-stage">
                <h4>2️⃣ Выплата перевозчику</h4>
                <?php if (!empty($payoutToCarrierAt)): ?>
                    <div class="payment-status success">
                        ✅ Переведено <?= date('d.m.Y', strtotime($payoutToCarrierAt)) ?>
                        <?php if (!empty($payoutDocument)): ?>
                            <br><small>ПП №<?= htmlspecialchars($payoutDocument) ?></small>
                        <?php endif; ?>
                    </div>
                <?php elseif ($order['status'] === 'unloaded' || $order['status'] === 'completed'): ?>
                    <?php if ($isOwner): ?>
                        <div class="payment-status pending">
                            ⏳ Подтвердите выгрузку, чтобы запустить выплату
                        </div>
                        <form method="POST" action="/orders/confirm_unload/<?= $order['id'] ?>" style="margin-top:12px;">
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Подтвердить выгрузку и запустить выплату?');">
                                ✅ Подтвердить выгрузку
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="payment-status info">⏳ Ожидаем подтверждения от заказчика</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="payment-status info">⏳ Выплата будет запущена после выгрузки груза</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Отклики owner'а -->
        <?php if ($isOwner && !empty($bids) && $order['status'] == 'new'): ?>
        <div class="panel" id="bids-section">
            <div class="panel-title">
                <span>📥 Отклики перевозчиков (<?= count($bids) ?>)</span>
            </div>
            <?php foreach ($bids as $bid): ?>
                <div class="bid-card">
                    <div class="bid-info">
                        <div class="bid-carrier"><?= htmlspecialchars($bid['company_name'] ?: 'Перевозчик') ?></div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                            ⭐ <?= number_format((float)$bid['rating'], 1) ?>
                        </div>
                    </div>
                    <div class="bid-price"><?= number_format($bid['price'], 0, ',', ' ') ?> ₽</div>
                    <?php if ($bid['status'] == 'pending'): ?>
                        <form method="POST" action="/bids/accept">
                            <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm">Выбрать</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Форма отклика -->
        <?php if ($_SESSION['user_role'] == 'carrier' && !$myBid && $order['status'] == 'new'): ?>
        <div class="panel" id="bid-form" style="background: linear-gradient(135deg, var(--accent), #d97706); color: var(--primary);">
            <h3 style="margin-bottom:8px; font-size:16px; font-weight:800;">💰 Предложить свою цену</h3>
            <p style="font-size:13px; margin-bottom:16px; opacity:0.9;">Укажите стоимость перевозки — заказчик выберет лучшего</p>
            <form method="POST" action="/bids/place/<?= $order['id'] ?>">
                <input type="number" name="price" required placeholder="<?= (int)$order['price'] ?>" min="1"
                       style="width:100%; padding:14px; font-size:18px; font-weight:800; border:none; font-family:var(--font-mono); text-align:center; margin-bottom:10px; box-sizing:border-box;">
                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:14px; background: var(--primary); color: white;">🚛 Отправить отклик</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Документы -->
        <?php if ($order['status'] != 'new' && $order['status'] != 'cancelled' && $order['status'] != 'hidden'): ?>
        <div class="panel" style="background:#f0fdf4; border-color:#10b981;">
            <div class="panel-title">📄 Документы по сделке</div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
                <a href="/documents/view/<?= $order['id'] ?>?type=contract" class="btn btn-sm btn-outline" target="_blank">📋 Договор</a>
                <a href="/documents/view/<?= $order['id'] ?>?type=waybill" class="btn btn-sm btn-outline" target="_blank">🚛 ТН</a>
                <?php if ($order['status'] == 'completed'): ?>
                    <a href="/documents/view/<?= $order['id'] ?>?type=act" class="btn btn-sm btn-outline" target="_blank">✅ Акт</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($scans)): ?>
                <div style="padding-top:12px; border-top:1px solid #c3e6cb;">
                    <div style="font-size:12px; font-weight:700; margin-bottom:8px;">📤 Загруженные сканы ТН:</div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php foreach ($scans as $scan): 
                            $ext = strtolower(pathinfo($scan['file_path'], PATHINFO_EXTENSION));
                        ?>
                            <a href="/<?= htmlspecialchars($scan['file_path']) ?>" target="_blank" class="scan-preview">
                                <?php if ($ext === 'pdf'): ?>
                                    <div class="scan-icon">📄</div>
                                <?php else: ?>
                                    <img src="/<?= htmlspecialchars($scan['file_path']) ?>" alt="Скан" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="scan-icon" style="display:none;">🖼️</div>
                                <?php endif; ?>
                                <div class="scan-name"><?= date('d.m.Y', strtotime($scan['created_at'])) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($isCarrier && in_array($order['status'], ['unloaded','completed'])): ?>
                <div style="padding-top:12px; margin-top:12px; border-top:1px solid #c3e6cb;">
                    <div style="font-size:12px; font-weight:700; margin-bottom:8px;">📤 Загрузить скан ТН:</div>
                    <form method="POST" action="/upload/scan/<?= $order['id'] ?>" enctype="multipart/form-data">
                        <input type="file" name="scan" accept=".pdf,.jpg,.jpeg,.png" style="margin-bottom:8px; max-width:100%;">
                        <button type="submit" class="btn btn-sm btn-accent">Загрузить</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- История -->
        <?php if ($order['status'] != 'new' && $order['status'] != 'cancelled' && $order['status'] != 'hidden'): ?>
        <div class="panel">
            <div class="panel-title">📋 История заказа</div>
            <?php
            $stmt = $pdo->prepare("SELECT h.*, u.name, u.role FROM order_status_history h JOIN users u ON h.changed_by = u.id WHERE h.order_id = ? ORDER BY h.created_at ASC");
            $stmt->execute([$order['id']]);
            $historyItems = $stmt->fetchAll();
            ?>
            <?php if (empty($historyItems)): ?>
                <div style="color:var(--text-secondary); font-size:13px;">История пока пуста</div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($historyItems as $item): 
                        $info = $statusLabels[$item['status']] ?? ['label'=>$item['status'],'icon'=>'📍'];
                        $time = strtotime($item['created_at']);
                    ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div style="font-weight:700; font-size:14px;"><?= $info['icon'] ?> <?= $info['label'] ?></div>
                            <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">
                                <?= date('d.m.Y H:i', $time) ?>
                                · <?= htmlspecialchars($item['name']) ?> 
                                (<?= $item['role'] == 'owner' ? 'Заказчик' : 'Перевозчик' ?>)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Отзыв -->
        <?php if ($order['status'] == 'completed'): 
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ? AND reviewer_id = ?");
            $stmt->execute([$order['id'], $_SESSION['user_id']]);
            $hasReview = $stmt->fetch();
        ?>
        <div class="panel" style="text-align:center;">
            <?php if (!$hasReview): ?>
                <div style="font-size:32px; margin-bottom:8px;">⭐</div>
                <div style="font-weight:800; margin-bottom:6px;">Оцените сделку</div>
                <div style="font-size:13px; color:var(--text-secondary); margin-bottom:14px;">Ваш отзыв поможет другим</div>
                <a href="/review/create/<?= $order['id'] ?>" class="btn btn-primary">Оставить отзыв</a>
            <?php else: ?>
                <div style="font-size:32px; margin-bottom:8px;">✅</div>
                <div style="font-weight:800;">Спасибо за отзыв!</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <a href="/orders" class="btn btn-ghost" style="padding:12px 0;">← К списку заказов</a>
        
    </div>
    
    <!-- ПРАВАЯ КОЛОНКА (sidebar) -->
    <div class="order-side">
        
        <!-- Заказчик -->
        <div class="panel">
            <div class="panel-title">🏢 Заказчик</div>
            <div class="customer-card">
                <div class="customer-header">
                    <div class="customer-avatar"><?= htmlspecialchars(mb_substr($ownerInfo['company_name'] ?: $ownerInfo['name'], 0, 1)) ?></div>
                    <div style="min-width:0;">
                        <div class="customer-name"><?= htmlspecialchars($ownerInfo['company_name'] ?: $ownerInfo['name']) ?></div>
                        <?php if ($ownerInfo['inn']): ?>
                            <div class="verified-badge">✅ Верифицирован</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="customer-rating">
                    <?php if ((float)$ownerInfo['rating'] > 0): ?>
                        <span style="color:var(--accent);">⭐ <?= number_format((float)$ownerInfo['rating'], 1) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-secondary);">🆕 Новый заказчик</span>
                    <?php endif; ?>
                </div>
                
                <div class="info-row">
                    <span class="label">Всего заказов</span>
                    <span class="value"><?= (int)$ownerInfo['total_orders'] ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Завершено</span>
                    <span class="value"><?= (int)$ownerInfo['completed_orders'] ?></span>
                </div>
                <?php if ($ownerInfo['created_at']): ?>
                <div class="info-row">
                    <span class="label">На платформе</span>
                    <span class="value"><?= date('m.Y', strtotime($ownerInfo['created_at'])) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($order['status'] != 'new' && $order['status'] != 'cancelled' && $order['status'] != 'hidden'): ?>
                    <div style="padding-top:12px; border-top:1px solid var(--border-light);">
                        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Телефон</div>
                        <a href="tel:<?= htmlspecialchars($order['phone']) ?>" style="font-weight:700; color:var(--text); text-decoration:none; font-size:14px;">
                            📞 <?= htmlspecialchars($order['phone']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Перевозчик -->
        <?php if ($carrierInfo): ?>
        <div class="panel">
            <div class="panel-title">🚛 Перевозчик</div>
            <div class="customer-card">
                <div class="customer-header">
                    <div class="customer-avatar" style="background:#10b981;"><?= htmlspecialchars(mb_substr($carrierInfo['company_name'] ?: $carrierInfo['name'], 0, 1)) ?></div>
                    <div style="min-width:0;">
                        <div class="customer-name"><?= htmlspecialchars($carrierInfo['company_name'] ?: $carrierInfo['name']) ?></div>
                        <?php if ((float)$carrierInfo['rating'] > 0): ?>
                            <div class="customer-meta">⭐ <?= number_format((float)$carrierInfo['rating'], 1) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($order['status'] != 'new' && $order['status'] != 'cancelled' && $order['status'] != 'hidden'): ?>
                    <a href="tel:<?= htmlspecialchars($carrierInfo['phone']) ?>" class="btn btn-sm btn-outline" style="text-align:center;">
                        📞 <?= htmlspecialchars($carrierInfo['phone']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Кнопка «Ищу обратный рейс» для carrier'а -->
        <?php if ($isCarrier && in_array($order['status'], ['unloaded', 'completed'])): ?>
            <div class="panel" style="background:linear-gradient(135deg,#4361ee,#2ec4b6); color:white;">
                <div class="panel-title" style="color:white; opacity:0.9;">🔄 Обратный рейс</div>
                <p style="font-size:13px; margin:0 0 14px; line-height:1.5;">
                    Машина освободится в <strong><?= htmlspecialchars(explode(',', $order['delivery_address'])[0]) ?></strong>.
                    Найдите попутный груз, чтобы не ехать пустым.
                </p>
                <a href="/orders/return" class="btn btn-primary" style="width:100%; text-align:center; background:white; color:#4361ee;">
                    🔍 Найти обратный рейс
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Кнопки статуса (для carrier'а) -->
        <?php if ($isCarrier && $acceptedBid): ?>
            <?php
            $nextStatus = null;
            $nextLabel = null;
            switch ($order['status']) {
                case 'accepted': 
                    if ($canStartPickup) {
                        $nextStatus = 'pickup'; $nextLabel = '🚛 Выехал на погрузку';
                    }
                    break;
                case 'pickup': $nextStatus = 'loaded'; $nextLabel = '📦 Груз погружен'; break;
                case 'loaded': $nextStatus = 'in_transit'; $nextLabel = '🛣️ В пути'; break;
                case 'in_transit': $nextStatus = 'arrived'; $nextLabel = '📍 Прибыл на выгрузку'; break;
                case 'arrived': $nextStatus = 'unloaded'; $nextLabel = '✅ Груз выгружен'; break;
            }
            ?>
            <?php if ($nextStatus): ?>
            <div class="panel">
                <div class="panel-title">⚡ Действия</div>
                <div class="status-actions">
                    <form method="POST" action="/status/update/<?= $order['id'] ?>">
                        <input type="hidden" name="status" value="<?= $nextStatus ?>">
                        <button type="submit" class="btn btn-primary btn-lg"><?= $nextLabel ?></button>
                    </form>
                </div>
            </div>
            <?php elseif ($order['status'] === 'accepted' && !$commissionPaid): ?>
            <div class="panel" style="background:#fffbeb; border-color:#f59e0b;">
                <div style="font-size:13px; color:#92400e; text-align:center;">
                    ⏳ Ожидаем оплату заказа от заказчика
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Завершение (для owner'а) -->
        <?php if ($isOwner && $order['status'] == 'unloaded' && empty($payoutToCarrierAt)): ?>
            <div class="panel">
                <div class="panel-title">⚡ Действия</div>
                <div class="status-actions">
                    <form method="POST" action="/orders/confirm_unload/<?= $order['id'] ?>">
                        <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Подтвердить выгрузку и перевести деньги перевозчику?');">
                            ✅ Подтвердить выгрузку и перевести
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Отмена -->
        <?php if (($isOwner || $isCarrier) && in_array($order['status'], ['new','accepted','pickup'])): ?>
            <div class="panel">
                <div class="panel-title">⚠️ Опасная зона</div>
                <form method="POST" action="/status/update/<?= $order['id'] ?>" onsubmit="return confirm('Отменить заказ? Это действие нельзя отменить.');">
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="btn btn-danger" style="width:100%;">❌ Отменить заказ</button>
                </form>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>