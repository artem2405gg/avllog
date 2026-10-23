<?php require_once BASE_PATH . '/templates/header.php'; ?>

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
    <a href="/orders" style="color:var(--text-secondary); text-decoration:none;">📋 Заказы</a>
    <span style="opacity:0.4;"> / </span>
    <a href="/orders/return" style="color:var(--text-secondary); text-decoration:none;">Обратные рейсы</a>
    <span style="opacity:0.4;"> / </span>
    <span style="color:var(--text); font-weight:600;">Результаты поиска</span>
</div>

<!-- Шапка поиска -->
<div class="card" style="border-left:4px solid var(--accent);">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
        <div>
            <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1.5px; color:var(--accent); margin-bottom:8px;">
                🔄 Поиск обратного рейса
            </div>
            <h2 style="margin:0 0 8px;"><?= htmlspecialchars($search['city']) ?></h2>
            <div class="text-muted" style="font-size:13px;">
                📅 <?= date('d.m.Y', strtotime($search['date_from'])) ?> — <?= date('d.m.Y', strtotime($search['date_to'])) ?>
                <?php if ($search['capacity_tons']): ?>
                    · ⚖️ до <?= $search['capacity_tons'] ?> т
                <?php endif; ?>
                <?php if ($search['vehicle_type']): ?>
                    · 🚛 <?= htmlspecialchars($search['vehicle_type']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; font-weight:700; margin-bottom:4px;">
                Найдено
            </div>
            <div style="font-size:32px; font-weight:900; font-family:var(--font-mono); line-height:1; color:var(--accent);">
                <?= count($orders) ?>
            </div>
        </div>
    </div>
    
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:16px; padding-top:16px; border-top:1px solid var(--border-light);">
        <a href="/orders/return" class="btn btn-outline btn-sm">← К поиску</a>
        <a href="/orders/return_cancel/<?= (int)$search['id'] ?>" 
           class="btn btn-outline btn-sm" 
           onclick="return confirm('Отменить поиск?');">
            Отменить поиск
        </a>
    </div>
</div>

<?php if (empty($orders)): ?>

    <div class="card" style="text-align:center; padding:60px 24px;">
        <div style="font-size:64px; margin-bottom:16px; opacity:0.4;">🔍</div>
        <h2 style="margin:0 0 12px;">Ничего не нашли</h2>
        <p class="text-muted" style="max-width:500px; margin:0 auto 24px;">
            В городе <strong><?= htmlspecialchars($search['city']) ?></strong>
            на даты <?= date('d.m.Y', strtotime($search['date_from'])) ?> — <?= date('d.m.Y', strtotime($search['date_to'])) ?>
            подходящих заказов нет.
            <br>Попробуйте расширить радиус или изменить даты.
        </p>
        <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
            <a href="/orders/return" class="btn btn-accent">Изменить параметры</a>
            <a href="/orders" class="btn btn-outline">Все заказы</a>
        </div>
    </div>

<?php else: ?>

    <?php foreach ($orders as $o): 
        $isNew = (strtotime($o['created_at']) ?? 0) > time() - 3600;
    ?>
        <div class="card" style="display:grid; grid-template-columns:1fr auto; gap:20px; align-items:center;">
            
            <!-- Левая часть -->
            <div style="min-width:0;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                    <h3 style="margin:0; font-size:17px; font-weight:800;">
                        <?= htmlspecialchars($o['title']) ?>
                    </h3>
                    <?php if ($isNew): ?>
                        <span class="badge badge-warning">🔥 Новый</span>
                    <?php endif; ?>
                </div>
                
                <!-- Маршрут -->
                <div style="display:flex; align-items:center; gap:10px; font-size:14px; margin-bottom:10px; flex-wrap:wrap;">
                    <span style="font-weight:700;"><?= htmlspecialchars(explode(',', $o['pickup_address'])[0]) ?></span>
                    <span style="color:var(--accent); font-size:18px;">→</span>
                    <span style="font-weight:700;"><?= htmlspecialchars(explode(',', $o['delivery_address'])[0]) ?></span>
                    <?php if (!empty($o['distance_km'])): ?>
                        <span class="text-muted" style="font-size:12px;">
                            (<?= number_format($o['distance_km']) ?> км)
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Метаданные -->
                <div style="display:flex; gap:16px; font-size:12px; color:var(--text-secondary); flex-wrap:wrap;">
                    <span>📦 <?= htmlspecialchars($o['cargo_type']) ?></span>
                    <span>⚖️ <?= $o['weight'] ?> т</span>
                    <span>📅 <?= date('d.m.Y', strtotime($o['pickup_date'])) ?></span>
                    <span>⭐ <?= number_format((float)$o['owner_rating'], 1) ?></span>
                    <?php if (!empty($o['bids_count'])): ?>
                        <span>📥 <?= $o['bids_count'] ?> откл.</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Правая часть -->
            <div style="text-align:right; display:flex; flex-direction:column; gap:8px; align-items:flex-end; min-width:180px;">
                <div style="font-size:26px; font-weight:900; color:var(--success); font-family:var(--font-mono); letter-spacing:-1px; line-height:1;">
                    <?= number_format((float)$o['price'], 0, ',', ' ') ?> ₽
                </div>
                <?php if (!empty($o['price_per_km'])): ?>
                    <div class="text-muted" style="font-size:11px;">
                        <strong style="color:var(--accent); font-family:var(--font-mono);">
                            <?= $o['price_per_km'] ?> ₽/км
                        </strong>
                    </div>
                <?php endif; ?>
                <a href="/orders/view/<?= $o['id'] ?>" class="btn btn-accent btn-sm">
                    Откликнуться →
                </a>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>