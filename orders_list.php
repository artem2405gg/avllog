<?php require_once BASE_PATH . '/templates/header.php'; ?>

<style>
    /* Фильтры */
    .orders-filters {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        margin-bottom: 20px;
    }
    .orders-filters .row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 12px;
        align-items: end;
    }
    .orders-filters .form-group {
        margin-bottom: 0;
    }
    .orders-filters input,
    .orders-filters select {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid var(--border);
        font-size: 14px;
        background: var(--border-light);
        font-family: inherit;
    }
    .orders-filters input:focus,
    .orders-filters select:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--surface);
    }
    .orders-filters .buttons {
        display: flex;
        gap: 8px;
    }
    .orders-filters .btn {
        padding: 12px 20px;
        white-space: nowrap;
    }
    
    /* Табы */
    .orders-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 20px;
        border-bottom: 2px solid var(--border);
    }
    .orders-tab {
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-secondary);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .orders-tab:hover {
        color: var(--text);
    }
    .orders-tab.active {
        color: var(--text);
        border-bottom-color: var(--accent);
    }
    .orders-tab .count {
        background: var(--border-light);
        color: var(--text-secondary);
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 10px;
    }
    .orders-tab.active .count {
        background: var(--accent);
        color: var(--primary);
    }
    
    /* Карточка заказа */
    .order-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        margin-bottom: 12px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 24px;
        align-items: center;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
    }
    .order-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--accent);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border-color: var(--accent);
    }
    .order-card:hover::before {
        opacity: 1;
    }
    .order-card.is-new::before {
        opacity: 1;
    }
    
    /* Левая часть */
    .order-left {
        min-width: 0;
    }
    .order-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .order-title {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
        color: var(--text);
        letter-spacing: -0.3px;
    }
    .order-badge-new {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--accent);
        color: var(--primary);
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        animation: pulseNew 2s ease-in-out infinite;
    }
    @keyframes pulseNew {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    /* Маршрут */
    .order-route {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        font-size: 15px;
        color: var(--text);
        flex-wrap: wrap;
    }
    .order-route .city {
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .order-route .arrow {
        color: var(--text-secondary);
        font-size: 18px;
        line-height: 1;
    }
    
    /* Параметры */
    .order-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        margin-bottom: 14px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .order-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .order-meta .value {
        color: var(--text);
        font-weight: 600;
    }
    
    /* Заказчик */
    .order-customer {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px solid var(--border-light);
        font-size: 13px;
        color: var(--text-secondary);
    }
    .order-customer .avatar {
        width: 28px;
        height: 28px;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 12px;
        flex-shrink: 0;
    }
    .order-customer .name {
        color: var(--text);
        font-weight: 600;
    }
    .order-customer .rating {
        color: var(--accent);
        font-weight: 700;
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    
    /* Правая часть — цена и кнопка */
    .order-right {
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: flex-end;
        min-width: 180px;
    }
    .order-price {
        font-size: 26px;
        font-weight: 900;
        color: var(--success);
        font-family: var(--font-mono);
        letter-spacing: -1px;
        line-height: 1;
    }
    .order-price-label {
        font-size: 10px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .order-right .btn {
        padding: 12px 24px;
        font-size: 13px;
        white-space: nowrap;
        width: 100%;
    }
    
    /* Пустое состояние */
    .empty-state {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state .icon {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.4;
    }
    .empty-state h3 {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .empty-state p {
        color: var(--text-secondary);
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    /* Мобильная версия */
    @media (max-width: 768px) {
        .orders-filters .row {
            grid-template-columns: 1fr;
        }
        .orders-filters .buttons {
            width: 100%;
        }
        .orders-filters .btn {
            flex: 1;
        }
        .order-card {
            grid-template-columns: 1fr;
            gap: 16px;
            padding: 16px;
        }
        .order-right {
            width: 100%;
            min-width: 0;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-light);
            padding-top: 14px;
        }
        .order-price {
            font-size: 22px;
        }
        .order-right .btn {
            width: auto;
            padding: 10px 20px;
        }
        .order-title {
            font-size: 16px;
        }
    }
</style>

<h2 style="margin-bottom: 20px;">📋 Доступные заказы</h2>

<!-- Фильтры -->
<div class="orders-filters">
    <form method="GET" action="/orders">
        <div class="row">
            <div class="form-group">
                <label>Откуда</label>
                <input type="text" name="city_from" value="<?= htmlspecialchars($_GET['city_from'] ?? '') ?>" placeholder="Москва">
            </div>
            <div class="form-group">
                <label>Куда</label>
                <input type="text" name="city_to" value="<?= htmlspecialchars($_GET['city_to'] ?? '') ?>" placeholder="Казань">
            </div>
            <div class="buttons">
                <button type="submit" class="btn btn-primary">🔍 Найти</button>
                <?php if (!empty($_GET['city_from']) || !empty($_GET['city_to'])): ?>
                    <a href="/orders" class="btn btn-outline">✕ Сбросить</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Табы: Свежие / Все -->
<?php
$now = time();
$freshOrders = array_filter($orders, function($o) use ($now) {
    return (strtotime($o['created_at']) ?? 0) > $now - 86400;
});
$freshCount = count($freshOrders);
$allCount = count($orders);
$activeTab = $_GET['tab'] ?? ($freshCount > 0 ? 'fresh' : 'all');
$displayOrders = $activeTab === 'fresh' ? array_values($freshOrders) : $orders;
?>

<?php if ($allCount > 0): ?>
<div class="orders-tabs">
    <a href="/orders?tab=fresh<?= !empty($_GET['city_from']) ? '&city_from=' . urlencode($_GET['city_from']) : '' ?><?= !empty($_GET['city_to']) ? '&city_to=' . urlencode($_GET['city_to']) : '' ?>" 
       class="orders-tab <?= $activeTab === 'fresh' ? 'active' : '' ?>">
        🔥 Свежие <span class="count"><?= $freshCount ?></span>
    </a>
    <a href="/orders?tab=all<?= !empty($_GET['city_from']) ? '&city_from=' . urlencode($_GET['city_from']) : '' ?><?= !empty($_GET['city_to']) ? '&city_to=' . urlencode($_GET['city_to']) : '' ?>" 
       class="orders-tab <?= $activeTab === 'all' ? 'active' : '' ?>">
        📋 Все <span class="count"><?= $allCount ?></span>
    </a>
</div>
<?php endif; ?>

<?php if (empty($displayOrders)): ?>
    <!-- Пустое состояние -->
    <div class="empty-state">
        <div class="icon"><?= $activeTab === 'fresh' ? '🔍' : '📦' ?></div>
        <?php if ($activeTab === 'fresh'): ?>
            <h3>Свежих заказов нет</h3>
            <p>Посмотрите все заказы или создайте свой</p>
            <a href="/orders?tab=all" class="btn btn-primary">📋 Показать все заказы</a>
        <?php elseif (!empty($_GET['city_from']) || !empty($_GET['city_to'])): ?>
            <h3>Ничего не найдено</h3>
            <p>Попробуйте изменить фильтры</p>
            <a href="/orders" class="btn btn-primary">✕ Сбросить фильтры</a>
        <?php else: ?>
            <h3>Пока нет активных заказов</h3>
            <p>Как только грузовладельцы опубликуют заказы — они появятся здесь</p>
            <a href="/dashboard" class="btn btn-outline">📊 В личный кабинет</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($displayOrders as $order): 
        $isNew = (strtotime($order['created_at']) ?? 0) > $now - 86400;
        $companyInitial = mb_substr($order['company_name'] ?: 'U', 0, 1);
        $rating = (float)$order['rating'];
    ?>
        <div class="order-card <?= $isNew ? 'is-new' : '' ?>">
            <div class="order-left">
                <!-- Заголовок + NEW -->
                <div class="order-header">
                    <h3 class="order-title"><?= htmlspecialchars($order['title']) ?></h3>
                    <?php if ($isNew): ?>
                        <span class="order-badge-new">🔥 Новый</span>
                    <?php endif; ?>
                </div>
                
                <!-- Маршрут -->
                <div class="order-route">
                    <span class="city">📍 <?= htmlspecialchars(explode(',', $order['pickup_address'])[0]) ?></span>
                    <span class="arrow">→</span>
                    <span class="city">🏁 <?= htmlspecialchars(explode(',', $order['delivery_address'])[0]) ?></span>
                </div>
                
                <!-- Параметры -->
                <div class="order-meta">
                    <span>📦 <span class="value"><?= htmlspecialchars($order['cargo_type']) ?></span></span>
                    <span>⚖️ <span class="value"><?= $order['weight'] ?> т</span></span>
                    <span>📐 <span class="value"><?= $order['volume'] ?> м³</span></span>
                    <span>📅 <span class="value"><?= date('d.m.Y', strtotime($order['pickup_date'])) ?></span></span>
                </div>
                
                <!-- Заказчик -->
                <div class="order-customer">
                    <div class="avatar"><?= htmlspecialchars($companyInitial) ?></div>
                    <span class="name"><?= htmlspecialchars($order['company_name'] ?: 'Частное лицо') ?></span>
                    <?php if ($rating > 0): ?>
                        <span class="rating">⭐ <?= number_format($rating, 1) ?></span>
                    <?php else: ?>
                        <span class="rating" style="color: var(--text-secondary); opacity: 0.6;">🆕 Новый</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Правая часть -->
            <div class="order-right">
                <div>
                    <div class="order-price-label">Цена</div>
                    <div class="order-price"><?= number_format($order['price'], 0, ',', ' ') ?> ₽</div>
                </div>
                <a href="/orders/view/<?= $order['id'] ?>" class="btn btn-primary">Подробнее →</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>