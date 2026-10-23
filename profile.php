<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if ($role === 'owner') {
    // Статистика owner'а
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('accepted','pickup','loaded','in_transit','arrived','unloaded')");
    $stmt->execute([$userId]);
    $activeOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $totalSpent = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bids b JOIN orders o ON b.order_id = o.id WHERE o.user_id = ?");
    $stmt->execute([$userId]);
    $totalBidsReceived = (int)$stmt->fetchColumn();

} else {
    // Статистика carrier'а
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bids WHERE carrier_id = ?");
    $stmt->execute([$userId]);
    $totalBids = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bids WHERE carrier_id = ? AND status = 'accepted'");
    $stmt->execute([$userId]);
    $acceptedBids = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'");
    $stmt->execute([$userId]);
    $completedDeals = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(b.price), 0) FROM bids b JOIN orders o ON b.order_id = o.id WHERE b.carrier_id = ? AND b.status = 'accepted' AND o.status = 'completed'");
    $stmt->execute([$userId]);
    $totalEarned = (float)$stmt->fetchColumn();

    // Активная подписка
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Отзывы о пользователе
$stmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name, u.company_name as reviewer_company FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.reviewed_id = ? ORDER BY r.id DESC LIMIT 5");
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$initial = mb_strtoupper(mb_substr($profile['name'] ?? 'U', 0, 1));
?>

<style>
    /* Header профиля */
    .profile-header {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 28px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    .profile-avatar {
        width: 96px;
        height: 96px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 900;
        flex-shrink: 0;
    }
    .profile-main {
        flex: 1;
        min-width: 240px;
    }
    .profile-name {
        font-size: 26px;
        font-weight: 900;
        letter-spacing: -0.5px;
        margin-bottom: 6px;
    }
    .profile-subline {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .profile-role {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .profile-role.owner { background: #dbeafe; color: #1e40af; }
    .profile-role.carrier { background: #d1fae5; color: #065f46; }
    .profile-role .verified {
        color: var(--success);
        font-size: 13px;
    }
    .profile-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* KPI */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .kpi-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        transition: all 0.25s;
        text-decoration: none;
        color: inherit;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border-color: var(--accent);
    }
    .kpi-value {
        font-size: 28px;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -1px;
        line-height: 1;
        margin-bottom: 6px;
    }
    .kpi-value.money { font-size: 22px; }
    .kpi-value.accent { color: var(--accent); }
    .kpi-value.success { color: var(--success); }
    .kpi-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
    }

    /* Сетка контента */
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    /* Панель */
    .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        margin-bottom: 16px;
    }
    .panel-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
        margin-bottom: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .panel-title a {
        font-size: 12px;
        color: var(--accent);
        text-decoration: none;
    }

    /* Info rows */
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
        gap: 12px;
    }
    .info-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .info-row:first-child {
        padding-top: 0;
    }
    .info-row .label {
        color: var(--text-secondary);
        flex-shrink: 0;
    }
    .info-row .value {
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }
    .info-row .value.verified { color: var(--success); }

    /* Требует внимания */
    .onboarding-block {
        background: linear-gradient(135deg, var(--accent), #d97706);
        color: var(--primary);
        padding: 20px;
        margin-bottom: 16px;
    }
    .onboarding-block h3 {
        font-size: 16px;
        font-weight: 900;
        margin-bottom: 10px;
        color: var(--primary);
    }
    .onboarding-checklist {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .checklist-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        opacity: 0.85;
    }
    .checklist-item.done {
        opacity: 1;
        text-decoration: line-through;
    }
    .checklist-item .check {
        width: 20px;
        height: 20px;
        background: rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 12px;
        flex-shrink: 0;
    }
    .checklist-item.done .check {
        background: var(--primary);
        color: white;
    }

    /* Отзывы */
    .review-item {
        padding: 14px 0;
        border-bottom: 1px solid var(--border-light);
    }
    .review-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .review-head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 6px;
        gap: 8px;
    }
    .review-author {
        font-weight: 800;
        font-size: 13px;
    }
    .review-stars {
        color: var(--accent);
        font-size: 13px;
        white-space: nowrap;
    }
    .review-text {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.5;
    }
    .review-date {
        font-size: 11px;
        color: var(--text-secondary);
        opacity: 0.7;
        margin-top: 4px;
    }

    /* Пустое */
    .empty-mini {
        text-align: center;
        padding: 24px 12px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .empty-mini .icon {
        font-size: 32px;
        opacity: 0.5;
        margin-bottom: 8px;
    }

    /* Мобильная версия */
    @media (max-width: 768px) {
        .profile-header { padding: 20px; gap: 16px; }
        .profile-avatar { width: 72px; height: 72px; font-size: 30px; }
        .profile-name { font-size: 20px; }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .kpi-value { font-size: 22px; }
        .kpi-value.money { font-size: 18px; }
        .profile-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Хлебные крошки -->
<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/dashboard" style="color:var(--text-secondary); text-decoration:none;">📊 Панель</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Личный кабинет</span>
</div>

<!-- Шапка профиля -->
<div class="profile-header">
    <div class="profile-avatar"><?= htmlspecialchars($initial) ?></div>
    <div class="profile-main">
        <div class="profile-name"><?= htmlspecialchars($profile['name']) ?></div>
        <div class="profile-subline">
            <span class="profile-role <?= $role ?>">
                <?= $role === 'owner' ? '🏢 Грузовладелец' : '🚛 Перевозчик' ?>
                <?php if (!empty($profile['email_verified'])): ?>
                    <span class="verified">✓</span>
                <?php endif; ?>
            </span>
            <?php if (!empty($profile['company_name'])): ?>
                <span>· <?= htmlspecialchars($profile['company_name']) ?></span>
            <?php endif; ?>
            <span>· На платформе с <?= date('m.Y', strtotime($profile['created_at'])) ?></span>
        </div>
    </div>
    <div class="profile-actions">
        <a href="/profile/edit" class="btn btn-primary">✏️ Редактировать</a>
        <a href="/support" class="btn btn-outline">🎧 Поддержка</a>
    </div>
</div>

<!-- KPI -->
<?php if ($role === 'owner'): ?>
    <div class="kpi-grid">
        <a href="/orders/my" class="kpi-card">
            <div class="kpi-value"><?= $totalOrders ?></div>
            <div class="kpi-label">Всего заказов</div>
        </a>
        <a href="/orders/my?tab=active" class="kpi-card">
            <div class="kpi-value <?= $activeOrders > 0 ? 'accent' : '' ?>"><?= $activeOrders ?></div>
            <div class="kpi-label">В работе</div>
        </a>
        <a href="/orders/my?tab=completed" class="kpi-card">
            <div class="kpi-value success"><?= $completedOrders ?></div>
            <div class="kpi-label">Завершено</div>
        </a>
        <div class="kpi-card" style="cursor:default;">
            <div class="kpi-value accent"><?= $totalBidsReceived ?></div>
            <div class="kpi-label">Получено откликов</div>
        </div>
    </div>
<?php else: ?>
    <div class="kpi-grid">
        <div class="kpi-card" style="cursor:default;">
            <div class="kpi-value"><?= $totalBids ?></div>
            <div class="kpi-label">Всего откликов</div>
        </div>
        <a href="/orders/my?tab=active" class="kpi-card">
            <div class="kpi-value accent"><?= $acceptedBids ?></div>
            <div class="kpi-label">Принято заказов</div>
        </a>
        <a href="/orders/my?tab=completed" class="kpi-card">
            <div class="kpi-value success"><?= $completedDeals ?></div>
            <div class="kpi-label">Завершено</div>
        </a>
        <div class="kpi-card" style="cursor:default;">
            <div class="kpi-value money success"><?= $totalEarned > 999 ? number_format($totalEarned, 0, ',', ' ') : '0' ?> ₽</div>
            <div class="kpi-label">Заработано</div>
        </div>
    </div>
<?php endif; ?>

<!-- Онбординг для новичков -->
<?php
// Проверяем, что уже сделано
$hasInn = !empty($profile['inn']);
$hasPhone = !empty($profile['phone']);
$hasCompany = !empty($profile['company_name']);
$hasBank = !empty($profile['bank_details']);

// Для owner'а — создал ли заказ
$hasCreatedOrder = false;
if ($role === 'owner' && $totalOrders > 0) $hasCreatedOrder = true;

// Для carrier'а — откликнулся ли
$hasBid = false;
if ($role === 'carrier' && $totalBids > 0) $hasBid = true;

// Показываем онбординг, если не всё сделано
$onboardingItems = [];
if ($role === 'owner') {
    $onboardingItems = [
        ['done' => $hasCompany, 'text' => 'Указать название компании'],
        ['done' => $hasInn, 'text' => 'Указать ИНН'],
        ['done' => $hasPhone, 'text' => 'Добавить телефон'],
        ['done' => $hasCreatedOrder, 'text' => 'Создать первый заказ', 'link' => '/orders/create'],
    ];
} else {
    $onboardingItems = [
        ['done' => $hasCompany, 'text' => 'Указать название компании'],
        ['done' => $hasInn, 'text' => 'Указать ИНН'],
        ['done' => $hasPhone, 'text' => 'Добавить телефон'],
        ['done' => $hasBank, 'text' => 'Указать банковские реквизиты', 'link' => '/profile/edit'],
        ['done' => $hasBid, 'text' => 'Откликнуться на заказ', 'link' => '/orders'],
    ];
}
$notDoneCount = count(array_filter($onboardingItems, fn($i) => !$i['done']));
?>

<?php if ($notDoneCount > 0): ?>
<div class="onboarding-block">
    <h3>🎯 Завершите настройку профиля</h3>
    <div class="onboarding-checklist">
        <?php foreach ($onboardingItems as $item): ?>
            <?php if ($item['done']): ?>
                <div class="checklist-item done">
                    <div class="check">✓</div>
                    <span><?= htmlspecialchars($item['text']) ?></span>
                </div>
            <?php else: ?>
                <div class="checklist-item">
                    <div class="check"></div>
                    <?php if (!empty($item['link'])): ?>
                        <a href="<?= $item['link'] ?>" style="color:var(--primary); text-decoration:underline;">
                            <?= htmlspecialchars($item['text']) ?> →
                        </a>
                    <?php else: ?>
                        <a href="/profile/edit" style="color:var(--primary); text-decoration:underline;">
                            <?= htmlspecialchars($item['text']) ?> →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Основной контент -->
<div class="profile-grid">
    
    <!-- Левая колонка -->
    <div>
        <!-- Контактные данные -->
        <div class="panel">
            <div class="panel-title">
                <span>📋 Контактные данные</span>
                <a href="/profile/edit">Изменить →</a>
            </div>
            
            <div class="info-row">
                <span class="label">Email</span>
                <span class="value <?= !empty($profile['email_verified']) ? 'verified' : '' ?>">
                    <?= htmlspecialchars($profile['email']) ?>
                    <?= !empty($profile['email_verified']) ? '✓' : '⚠' ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">Телефон</span>
                <span class="value"><?= htmlspecialchars($profile['phone'] ?: '— не указан') ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">Компания</span>
                <span class="value"><?= htmlspecialchars($profile['company_name'] ?: '— не указана') ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">ИНН</span>
                <span class="value"><?= htmlspecialchars($profile['inn'] ?: '— не указан') ?></span>
            </div>
        </div>
        
        <!-- Реквизиты (для carrier'а) -->
        <?php if ($role === 'carrier'): ?>
        <div class="panel">
            <div class="panel-title">
                <span>💳 Банковские реквизиты</span>
                <a href="/profile/edit">Изменить →</a>
            </div>
            <?php if (!empty($profile['bank_details'])): ?>
                <div style="background:var(--border-light); padding:14px; font-family:'SF Mono',monospace; font-size:12px; line-height:1.7; white-space:pre-line; border:1px solid var(--border);">
<?= htmlspecialchars($profile['bank_details']) ?>
                </div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:8px;">
                    💡 Реквизиты видны заказчику при принятии ставки
                </div>
            <?php else: ?>
                <div class="empty-mini">
                    <div class="icon">💳</div>
                    <p style="margin-bottom:12px;">Реквизиты не указаны. Заказчики не смогут перевести оплату.</p>
                    <a href="/profile/edit" class="btn btn-accent btn-sm">Указать реквизиты</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Правая колонка -->
    <div>
        <!-- Быстрые действия -->
        <div class="panel">
            <div class="panel-title">⚡ Быстрые действия</div>
            <div style="display:flex; flex-direction:column; gap:8px; margin-top:12px;">
                <?php if ($role === 'owner'): ?>
                    <a href="/orders/create" class="btn btn-primary" style="width:100%; padding:12px;">📦 Создать заказ</a>
                    <a href="/orders/my" class="btn btn-outline" style="width:100%; padding:12px;">📋 Мои сделки</a>
                <?php else: ?>
                    <a href="/orders" class="btn btn-primary" style="width:100%; padding:12px;">📋 Найти заказы</a>
                    <a href="/orders/my" class="btn btn-outline" style="width:100%; padding:12px;">🚛 Мои доставки</a>
                    <a href="/pricing" class="btn btn-outline" style="width:100%; padding:12px;">💎 Тарифы</a>
                <?php endif; ?>
                <a href="/support" class="btn btn-outline" style="width:100%; padding:12px;">🎧 Поддержка</a>
            </div>
        </div>
        
        <!-- Подписка (для carrier'а) -->
        <?php if ($role === 'carrier'): ?>
        <div class="panel">
            <div class="panel-title">
                <span>💎 Подписка</span>
                <a href="/pricing">Изменить →</a>
            </div>
            <?php if (!empty($subscription)): ?>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="display:inline-flex; align-items:center; gap:4px; background:#d1fae5; color:#065f46; padding:4px 10px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px;">
                            ✅ Активна
                        </span>
                        <div style="font-size:11px; color:var(--text-secondary); margin-top:6px;">
                            до <?= date('d.m.Y', strtotime($subscription['expires_at'])) ?>
                        </div>
                    </div>
                    <span style="font-weight:900; text-transform:uppercase; font-size:16px;">
                        <?= htmlspecialchars($subscription['plan']) ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="empty-mini">
                    <div class="icon">💎</div>
                    <p style="margin-bottom:12px;">Бесплатный тариф: 3 отклика в месяц</p>
                    <a href="/pricing" class="btn btn-accent btn-sm">Перейти на Basic</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Отзывы -->
        <div class="panel">
            <div class="panel-title">
                <span>⭐ Отзывы <?= count($reviews) > 0 ? '(' . count($reviews) . ')' : '' ?></span>
            </div>
            <?php if (empty($reviews)): ?>
                <div class="empty-mini">
                    <div class="icon">⭐</div>
                    <p>
                        <?php if ($role === 'owner'): ?>
                            Отзывов пока нет — они появятся после завершённых сделок
                        <?php else: ?>
                            Отзывов пока нет — завершите первую сделку, чтобы получить отзыв
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                    <div class="review-item">
                        <div class="review-head">
                            <div class="review-author"><?= htmlspecialchars($r['reviewer_company'] ?: $r['reviewer_name']) ?></div>
                            <div class="review-stars">
                                <?= str_repeat('⭐', (int)$r['rating']) ?>
                            </div>
                        </div>
                        <?php if (!empty($r['comment'])): ?>
                            <div class="review-text"><?= htmlspecialchars($r['comment']) ?></div>
                        <?php endif; ?>
                        <div class="review-date"><?= date('d.m.Y', strtotime($r['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>