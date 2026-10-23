<?php require_once BASE_PATH . '/templates/header.php'; ?>

<style>
    .ref-link-box {
        background: #f9fafb;
        border: 2px dashed var(--border);
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        margin: 20px 0;
    }
    .ref-link {
        font-size: 18px;
        font-weight: 700;
        color: var(--primary);
        word-break: break-all;
    }
    .ref-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .ref-stat {
        text-align: center;
        padding: 20px;
        background: var(--surface);
        border: 1px solid var(--border);
    }
    .ref-stat .value { font-size: 32px; font-weight: 900; font-family: var(--font-mono); }
    .ref-stat .label { font-size: 11px; color: var(--text-secondary); text-transform: uppercase; margin-top: 4px; }
    @media (max-width: 768px) { .ref-stats { grid-template-columns: 1fr 1fr; } }
</style>

<h1>🤝 Партнёрская программа</h1>
<p class="text-muted">Приглашайте друзей и получайте бонусы</p>

<div class="ref-stats">
    <div class="ref-stat">
        <div class="value"><?= $refCount ?></div>
        <div class="label">Приглашено</div>
    </div>
    <div class="ref-stat">
        <div class="value"><?= $bonusDays ?></div>
        <div class="label">Бонусных дней</div>
    </div>
    <div class="ref-stat">
        <div class="value">+14</div>
        <div class="label">Дней за друга</div>
    </div>
</div>

<div class="card">
    <h3>🔗 Ваша реферальная ссылка</h3>
    <p style="color:var(--text-secondary); margin-bottom:12px;">Поделитесь ссылкой — и получите <strong>+14 дней подписки</strong> за каждого нового перевозчика.</p>
    
    <div class="ref-link-box">
        <div class="ref-link" id="refLink"><?= $refLink ?></div>
    </div>
    
    <button class="btn btn-accent btn-block" onclick="copyRef()">📋 Скопировать ссылку</button>
</div>

<?php if (!empty($refUsers)): ?>
<div class="card" style="margin-top:16px;">
    <h3>👥 Приглашённые пользователи</h3>
    <table style="width:100%;">
        <tr style="color:var(--text-secondary); font-size:11px; text-transform:uppercase;">
            <th style="text-align:left; padding:8px 0;">Имя</th>
            <th style="text-align:left; padding:8px 0;">Email</th>
            <th style="text-align:left; padding:8px 0;">Дата</th>
        </tr>
        <?php foreach ($refUsers as $ru): ?>
        <tr style="border-top:1px solid var(--border-light);">
            <td style="padding:10px 0;"><?= htmlspecialchars($ru['name']) ?></td>
            <td style="padding:10px 0;"><?= htmlspecialchars($ru['email']) ?></td>
            <td style="padding:10px 0;"><?= date('d.m.Y', strtotime($ru['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<script>
function copyRef() {
    var link = document.getElementById('refLink').textContent;
    navigator.clipboard.writeText(link).then(function() {
        alert('Ссылка скопирована!');
    });
}
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>