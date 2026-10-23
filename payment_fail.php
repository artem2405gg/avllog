<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

$invoiceId = (int)($_GET['invoice'] ?? 0);
$invoice = null;

if ($invoiceId) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
    $stmt->execute([$invoiceId, $_SESSION['user_id']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    .payment-result {
        max-width: 600px;
        margin: 60px auto;
        text-align: center;
        padding: 60px 40px;
        background: var(--surface);
        border: 1px solid var(--border);
    }
    .payment-icon {
        font-size: 80px;
        margin-bottom: 24px;
    }
    .payment-title {
        font-size: 28px;
        font-weight: 900;
        margin-bottom: 12px;
        color: var(--danger);
    }
    .payment-subtitle {
        font-size: 16px;
        color: var(--text-secondary);
        margin-bottom: 32px;
        line-height: 1.6;
    }
</style>

<div class="payment-result">
    <div class="payment-icon">😔</div>
    <h1 class="payment-title">Платёж не прошёл</h1>
    <p class="payment-subtitle">
        К сожалению, оплата не была завершена.<br>
        Возможные причины: недостаточно средств, ошибка банка, отмена пользователем.
    </p>

    <?php if ($invoice): ?>
        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 24px;">
            Счёт № <?= $invoice['id'] ?> на сумму
            <strong><?= number_format($invoice['amount'], 0, ',', ' ') ?> ₽</strong>
        </p>
    <?php endif; ?>

    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <a href="/pricing" class="btn btn-accent">🔄 Попробовать снова</a>
        <a href="/support" class="btn btn-outline">🎧 Написать в поддержку</a>
    </div>

    <p style="font-size: 12px; color: var(--text-secondary); margin-top: 32px;">
        Если деньги были списаны, но подписка не активировалась — напишите нам на
        <a href="mailto:info@avllogist.ru">info@avllogist.ru</a>, разберёмся в течение часа.
    </p>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>