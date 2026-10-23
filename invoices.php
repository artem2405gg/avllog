<?php require_once BASE_PATH . '/templates/header.php'; ?>

<style>
    .invoice-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 28px;
        margin-bottom: 16px;
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .invoice-status {
        display: inline-block;
        padding: 6px 16px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .invoice-status.pending { background: #fffbeb; color: #92400e; border: 1px solid var(--accent); }
    .invoice-status.paid { background: #f0fdf4; color: #166534; border: 1px solid var(--success); }
    .payment-details {
        background: #f9fafb;
        padding: 20px;
        border: 1px solid var(--border);
        font-family: var(--font-mono);
        font-size: 13px;
        line-height: 1.8;
        white-space: pre-line;
        margin: 16px 0;
    }
    .copy-btn {
        cursor: pointer;
        font-size: 12px;
        padding: 8px 16px;
        background: var(--border-light);
        border: 1px solid var(--border);
        font-weight: 600;
    }
</style>

<h1>🧾 Мои счета</h1>
<p class="text-muted">Счета на оплату тарифов и реквизиты для оплаты</p>

<?php if (empty($invoices)): ?>
    <div class="card" style="text-align:center; padding:40px;">
        <div style="font-size:48px;">🧾</div>
        <h3>Счетов пока нет</h3>
        <p style="color:var(--text-secondary);">Выберите тариф на <a href="/pricing">странице тарифов</a></p>
    </div>
<?php else: ?>
    <?php foreach ($invoices as $inv): ?>
        <div class="invoice-card">
            <div class="invoice-header">
                <div>
                    <h3>Счёт №<?= $inv['id'] ?></h3>
                    <div class="text-muted">от <?= date('d.m.Y H:i', strtotime($inv['created_at'])) ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:24px; font-weight:900; font-family:var(--font-mono);">
                        <?= number_format($inv['amount'], 0, ',', ' ') ?> ₽
                    </div>
                    <span class="invoice-status <?= $inv['status'] ?>">
                        <?= $inv['status'] == 'pending' ? '⏳ Ожидает' : ($inv['status'] == 'paid' ? '✅ Оплачен' : '❌ Отменён') ?>
                    </span>
                </div>
            </div>
            
            <p><strong>Тариф:</strong> <?= $inv['plan'] ?></p>
            
            <?php if ($inv['status'] == 'pending' && $inv['payment_details']): ?>
                <h4 style="margin-top:16px;">💰 Реквизиты для оплаты</h4>
                <div class="payment-details" id="details-<?= $inv['id'] ?>">
                    <?= htmlspecialchars($inv['payment_details']) ?>
                </div>
                <button class="copy-btn" onclick="copyDetails('details-<?= $inv['id'] ?>')">📋 Скопировать реквизиты</button>
                <p style="font-size:12px; color:var(--text-secondary); margin-top:8px;">
                    После оплаты напишите на <a href="mailto:info@avllogist.ru">info@avllogist.ru</a> — активируем в течение часа.
                </p>
            <?php elseif ($inv['status'] == 'paid'): ?>
                <p style="color:var(--success);">✅ Оплачено <?= date('d.m.Y H:i', strtotime($inv['paid_at'])) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function copyDetails(id) {
    const text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(() => alert('Реквизиты скопированы!'));
}
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>