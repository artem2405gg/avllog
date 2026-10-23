<?php require_once BASE_PATH . '/templates/header.php'; ?>

<style>
    .stars-display { color: #fbbf24; font-size: 20px; letter-spacing: 2px; }
    .stars-display .empty { color: #d1d5db; }
    .review-card { padding: 16px 0; border-bottom: 1px solid #f0f0f0; }
    .review-card:last-child { border-bottom: none; }
    .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
</style>

<div class="card">
    <a href="javascript:history.back()" style="color: var(--primary);">← Назад</a>
    
    <h2 style="margin-top: 12px;">
        <?= htmlspecialchars($user['company_name'] ?: $user['name']) ?>
    </h2>
    
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
        <div class="stars-display">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <?= $i <= round($user['rating']) ? '★' : '<span class="empty">★</span>' ?>
            <?php endfor; ?>
        </div>
        <span style="font-weight: 700; font-size: 22px;"><?= $user['rating'] ?></span>
        <span style="color: var(--gray);">(<?= count($reviews) ?> отзывов)</span>
    </div>
    
    <?php if (empty($reviews)): ?>
        <div style="text-align: center; padding: 30px; color: var(--gray);">
            <div style="font-size: 40px;">📝</div>
            <p>Пока нет отзывов</p>
        </div>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <div class="review-card">
                <div class="review-header">
                    <strong><?= htmlspecialchars($r['reviewer_company'] ?: $r['reviewer_name']) ?></strong>
                    <div class="stars-display" style="font-size: 14px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= $r['rating'] ? '★' : '<span class="empty">★</span>' ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if (!empty($r['comment'])): ?>
                    <p style="font-size: 14px; color: #374151;"><?= htmlspecialchars($r['comment']) ?></p>
                <?php endif; ?>
                <div style="font-size: 11px; color: var(--gray); margin-top: 4px;">
                    Заказ: <?= htmlspecialchars(mb_substr($r['order_title'], 0, 40)) ?> · 
                    <?= date('d.m.Y', strtotime($r['created_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>