<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

require_once BASE_PATH . '/app/lib/AgentReport.php';
$ar = new AgentReport($pdo);

$orderId = (int)($orderId ?? 0);
$report = $ar->getByOrder($orderId);

if (!$report) {
    echo '<div class="card"><h2>Отчёт агента</h2><p>Отчёт ещё не сформирован. Он появится после подтверждения выгрузки груза.</p></div>';
    require_once BASE_PATH . '/templates/footer.php';
    exit;
}

// Проверяем права: отчёт виден owner'у и админу
$stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$orderOwnerId = (int)$stmt->fetchColumn();

$isOwner = ($orderOwnerId === (int)$_SESSION['user_id']);
$stmtAdmin = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmtAdmin->execute([$_SESSION['user_id']]);
$isAdmin = (bool)$stmtAdmin->fetchColumn();

if (!$isOwner && !$isAdmin) {
    header('Location: /dashboard');
    exit;
}
?>

<style>
    .report-wrap { max-width: 900px; margin: 0 auto; }
    .report-actions {
        display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;
    }
    .report-meta {
        background: var(--surface); border: 1px solid var(--border);
        padding: 20px; margin-bottom: 16px;
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
    }
    .report-meta .item .label {
        font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px;
        color: var(--text-secondary); font-weight: 800; margin-bottom: 4px;
    }
    .report-meta .item .value { font-weight: 800; font-size: 14px; }
    .report-iframe {
        width: 100%; height: 900px; border: 1px solid var(--border);
        background: white;
    }
    @media (max-width: 768px) {
        .report-meta { grid-template-columns: 1fr; }
        .report-iframe { height: 600px; }
    }
</style>

<div class="report-wrap">
    <div class="breadcrumbs" style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
        <a href="/orders/view/<?= $orderId ?>" style="color:var(--text-secondary); text-decoration:none;">← К заказу #<?= $orderId ?></a>
    </div>

    <h1 style="margin-bottom:8px;">📋 Отчёт агента</h1>
    <p style="color:var(--text-secondary); font-size:14px; margin-bottom:20px;">
        Отчёт по агентскому договору (ст. 1008 ГК РФ)
    </p>

    <div class="report-meta">
        <div class="item">
            <div class="label">Номер отчёта</div>
            <div class="value"><?= htmlspecialchars($report['report_number']) ?></div>
        </div>
        <div class="item">
            <div class="label">Дата</div>
            <div class="value"><?= date('d.m.Y H:i', strtotime($report['sent_at'])) ?></div>
        </div>
        <div class="item">
            <div class="label">Статус</div>
            <div class="value">
                <?php if ($report['status'] === 'accepted'): ?>
                    <span style="color:var(--success);">✅ Принят</span>
                <?php elseif ($report['status'] === 'disputed'): ?>
                    <span style="color:var(--danger);">⚠️ Оспорен</span>
                <?php else: ?>
                    <span style="color:var(--accent);">⏳ Ожидает принятия</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="report-actions">
        <a href="/agent_report/download/<?= $orderId ?>" target="_blank" class="btn btn-primary">📄 Скачать PDF</a>
        <button onclick="window.print()" class="btn btn-outline">🖨️ Распечатать</button>
    </div>

    <iframe class="report-iframe" srcdoc="<?= htmlspecialchars($report['content'], ENT_QUOTES) ?>"></iframe>
</div>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>