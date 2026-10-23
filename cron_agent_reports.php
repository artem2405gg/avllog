<?php
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    $secretKey = 'AVL_CRON_' . md5(__DIR__ . '|agent_reports');
    if (($_GET['key'] ?? '') !== $secretKey) { http_response_code(403); exit('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
}
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/lib/AgentReport.php';

try {
    $count = (new AgentReport($pdo))->autoAcceptOld();
    $msg = '[' . date('Y-m-d H:i:s') . "] Auto-accepted: {$count}\n";
    echo $msg;
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    file_put_contents($logDir . '/cron_agent_reports.log', $msg, FILE_APPEND);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}