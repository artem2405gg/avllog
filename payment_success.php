<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;
$invoiceId = (int)($_GET['invoice'] ?? 0);
$invoice = null; $subscription = null;

if ($invoiceId) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
    $stmt->execute([$invoiceId, $_SESSION['user_id']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT plan, expires_at FROM subscriptions WHERE user_id = ? AND status='active' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div style="max-width:600px;margin:60px auto;text-align:center;padding:60px 40px;background:var(--surface);border:1px solid var(--border);">
    <div style="font-size:80px;margin-bottom:24px;">🎉</div>
    <h1 style="font-size:28px;font-weight:900;margin-bottom:12px;color:var(--success);">Оплата прошла!</h1>
    <p style="font-size:16px;color:var(--text-secondary);margin-bottom:32px;line-height:1.6;">
        Средства поступили на счёт платформы.<br>
        <span id="activation-status" style="color:var(--text-secondary);font-size:14px;padding:16px;display:block;">
            <span style="display:inline-block;width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 1s linear infinite;vertical-align:middle;margin-right:8px;"></span>
            Проверяем статус...
        </span>
    </p>

    <?php if ($invoice): ?>
        <div style="background:var(--border-light);padding:20px;margin:24px 0;text-align:left;">
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:14px;">
                <span style="color:var(--text-secondary);">Счёт</span>
                <span style="font-weight:700;">#<?= $invoice['id'] ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px;">
                <span style="color:var(--text-secondary);">Сумма</span>
                <span style="font-weight:700;"><?= number_format((float)$invoice['amount'], 0, ',', ' ') ?> ₽</span>
            </div>
        </div>
    <?php endif; ?>

    <div style="background:#f0fdf4;padding:14px;margin:20px 0;font-size:13px;color:#065f46;text-align:left;border-left:3px solid #10b981;">
        <strong>ℹ️ Что дальше?</strong><br>
        Деньги заморожены на счёте платформы и будут переведены перевозчику после подтверждения выгрузки.
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="/orders/my" class="btn btn-primary">📦 Мои сделки</a>
        <a href="/dashboard" class="btn btn-outline">📊 В кабинет</a>
    </div>
</div>

<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<?php if ($invoiceId): ?>
<script>
(function() {
    const invoiceId = <?= $invoiceId ?>;
    const el = document.getElementById('activation-status');
    let attempts = 0;
    const maxAttempts = 15;

    function check() {
        attempts++;
        fetch('/tinkoff_status.php?invoice_id=' + invoiceId, {credentials: 'same-origin'})
            .then(r => r.json())
            .then(d => {
                if (!d.success) throw new Error(d.error || 'Ошибка');
                const sub = d.data.subscription, order = d.data.order, ts = d.data.tinkoff_status;

                if (sub && sub.expires_at) { el.innerHTML = '✅ <strong style="color:var(--success);">Подписка активирована!</strong>'; return; }
                if (order && order.commission_paid) { el.innerHTML = '✅ <strong style="color:var(--success);">Оплата зафиксирована. Деньги на счёте платформы.</strong>'; return; }
                if (ts === 'CONFIRMED') { el.innerHTML = '✅ Оплата подтверждена банком. Обрабатываем...'; if (attempts < maxAttempts) setTimeout(check, 2000); return; }
                if (attempts >= maxAttempts) { el.innerHTML = '⏳ Если статус не обновился — обновите страницу.'; return; }
                el.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 1s linear infinite;vertical-align:middle;margin-right:8px;"></span> Ожидаем банк... (' + attempts + '/' + maxAttempts + ')';
                setTimeout(check, 3000);
            })
            .catch(err => {
                if (attempts >= maxAttempts) el.innerHTML = '⚠️ Ошибка проверки. Обновите страницу.';
                else setTimeout(check, 3000);
            });
    }
    setTimeout(check, 1500);
})();
</script>
<?php endif; ?>
<?php require_once BASE_PATH . '/templates/footer.php'; ?>