<?php
require_once BASE_PATH . '/templates/header.php';
global $pdo;

require_once BASE_PATH . '/app/lib/Analytics.php';
$analytics = new Analytics($pdo);

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Период
$period = (int)($_GET['period'] ?? 30);
if (!in_array($period, [30, 90, 365])) $period = 30;

// Табы
$tab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview', 'routes', 'people'];
if (!in_array($tab, $validTabs)) $tab = 'overview';

// Данные в зависимости от роли
if ($role === 'owner') {
    $stats = $analytics->getOwnerStats($userId, $period);
    $chartData = $analytics->getOwnerMonthlyChart($userId, 6);
    $topRoutes = $analytics->getOwnerTopRoutes($userId, 10);
    $topPeople = $analytics->getOwnerTopCarriers($userId, 10);
    $chartType = 'spent';
} else {
    $stats = $analytics->getCarrierStats($userId, $period);
    $chartData = $analytics->getCarrierMonthlyChart($userId, 6);
    $topRoutes = $analytics->getCarrierTopRoutes($userId, 10);
    $topPeople = $analytics->getCarrierTopClients($userId, 10);
    $chartType = 'earned';
}
?>

<style>
    /* Hero */
    .an-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .an-hero h1 {
        font-size: 28px;
        font-weight: 900;
        letter-spacing: -1px;
        margin-bottom: 4px;
    }
    .an-hero p {
        color: var(--text-secondary);
        font-size: 14px;
    }
    
    /* Period switcher */
    .an-period {
        display: flex;
        gap: 4px;
        background: var(--border-light);
        padding: 4px;
    }
    .an-period a {
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-secondary);
        text-decoration: none;
        transition: all 0.2s;
    }
    .an-period a.active {
        background: var(--primary);
        color: white;
    }
    .an-period a:not(.active):hover {
        color: var(--text);
    }
    
    /* Tabs */
    .an-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 20px;
        border-bottom: 2px solid var(--border);
    }
    .an-tab {
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-secondary);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .an-tab:hover { color: var(--text); }
    .an-tab.active {
        color: var(--text);
        border-bottom-color: var(--accent);
    }
    
    /* KPI grid */
    .an-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .an-kpi {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 20px;
        transition: all 0.25s;
    }
    .an-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .an-kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .an-kpi-icon {
        font-size: 22px;
        opacity: 0.7;
    }
    .an-kpi-trend {
        font-size: 11px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .an-kpi-trend.up { background: #d1fae5; color: #065f46; }
    .an-kpi-trend.down { background: #fee2e2; color: #991b1b; }
    .an-kpi-trend.flat { background: var(--border-light); color: var(--text-secondary); }
    
    .an-kpi-value {
        font-size: 28px;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -1px;
        line-height: 1;
        margin-bottom: 6px;
    }
    .an-kpi-value.accent { color: var(--accent); }
    .an-kpi-value.success { color: var(--success); }
    .an-kpi-value.danger { color: var(--danger); }
    
    .an-kpi-label {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
    }
    .an-kpi-sublabel {
        font-size: 11px;
        color: var(--text-secondary);
        margin-top: 4px;
    }
    
    /* Chart */
    .an-chart {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 24px;
        margin-bottom: 20px;
    }
    .an-chart-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 12px;
        flex-wrap: wrap;
    }
    .an-chart-title {
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-secondary);
    }
    .an-chart-wrap {
        position: relative;
        height: 280px;
    }
    
    /* Table */
    .an-table-wrap {
        background: var(--surface);
        border: 1px solid var(--border);
        overflow-x: auto;
    }
    .an-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 500px;
    }
    .an-table th {
        background: var(--border-light);
        padding: 12px 16px;
        text-align: left;
        font-size: 10px;
        text-transform: uppercase;
        color: var(--text-secondary);
        font-weight: 800;
        letter-spacing: 1px;
    }
    .an-table td {
        padding: 14px 16px;
        border-top: 1px solid var(--border-light);
        font-size: 13px;
        vertical-align: middle;
    }
    .an-table tr:hover { background: var(--border-light); }
    
    .an-rank {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background: var(--border-light);
        color: var(--text-secondary);
        font-weight: 800;
        font-size: 12px;
        border-radius: 50%;
    }
    .an-rank.top1 { background: #fef3c7; color: #92400e; }
    .an-rank.top2 { background: #e5e7eb; color: #374151; }
    .an-rank.top3 { background: #fed7aa; color: #9a3412; }
    
    /* Empty state */
    .an-empty {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 60px 20px;
        text-align: center;
    }
    .an-empty-icon {
        font-size: 64px;
        opacity: 0.4;
        margin-bottom: 16px;
    }
    .an-empty h3 {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 8px;
    }
    .an-empty p {
        color: var(--text-secondary);
        font-size: 14px;
        margin-bottom: 20px;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    @media (max-width: 768px) {
        .an-hero h1 { font-size: 22px; }
        .an-kpi-value { font-size: 22px; }
        .an-chart-wrap { height: 220px; }
        .an-table th, .an-table td { padding: 10px 12px; font-size: 12px; }
    }
</style>

<!-- Hero -->
<div class="an-hero">
    <div>
        <h1>📊 Аналитика</h1>
        <p><?= $role === 'owner' ? 'Показатели ваших заказов и перевозок' : 'Показатели вашей работы на платформе' ?></p>
    </div>
    <div class="an-period">
        <a href="?period=30&tab=<?= $tab ?>" class="<?= $period === 30 ? 'active' : '' ?>">30 дней</a>
        <a href="?period=90&tab=<?= $tab ?>" class="<?= $period === 90 ? 'active' : '' ?>">90 дней</a>
        <a href="?period=365&tab=<?= $tab ?>" class="<?= $period === 365 ? 'active' : '' ?>">Год</a>
    </div>
</div>

<!-- Tabs -->
<div class="an-tabs">
    <a href="?period=<?= $period ?>&tab=overview" class="an-tab <?= $tab === 'overview' ? 'active' : '' ?>">
        📈 Обзор
    </a>
    <a href="?period=<?= $period ?>&tab=routes" class="an-tab <?= $tab === 'routes' ? 'active' : '' ?>">
        📍 Маршруты
    </a>
    <a href="?period=<?= $period ?>&tab=people" class="an-tab <?= $tab === 'people' ? 'active' : '' ?>">
        👥 <?= $role === 'owner' ? 'Перевозчики' : 'Заказчики' ?>
    </a>
</div>

<?php if ($tab === 'overview'): ?>

    <!-- KPI блок -->
    <div class="an-kpi-grid">
        <?php if ($role === 'owner'): ?>
            <!-- OWNER KPI -->
            <div class="an-kpi">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">📦</span>
                    <?php if ($stats['orders']['trend'] != 0): ?>
                        <span class="an-kpi-trend <?= $stats['orders']['trend'] > 0 ? 'up' : 'down' ?>">
                            <?= $stats['orders']['trend'] > 0 ? '↑' : '↓' ?> <?= abs($stats['orders']['trend']) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="an-kpi-value"><?= $stats['orders']['total'] ?></div>
                <div class="an-kpi-label">Заказов за период</div>
                <div class="an-kpi-sublabel">
                    <?= $stats['orders']['completed'] ?> завершено · <?= $stats['orders']['active'] ?> в работе
                </div>
            </div>
            
            <div class="an-kpi">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">📥</span>
                </div>
                <div class="an-kpi-value accent"><?= $stats['bids_count'] ?></div>
                <div class="an-kpi-label">Откликов получено</div>
                <?php if ($stats['avg_response_hours']): ?>
                    <div class="an-kpi-sublabel">
                        ⏱ Первый отклик за ~<?= $stats['avg_response_hours'] ?> ч
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="an-kpi">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">💰</span>
                </div>
                <div class="an-kpi-value"><?= number_format($stats['spent']['total'], 0, ',', ' ') ?> ₽</div>
                <div class="an-kpi-label">Сумма перевозок</div>
                <?php if ($stats['spent']['trend'] != 0): ?>
                    <div class="an-kpi-sublabel">
                        <?= $stats['spent']['trend'] > 0 ? '↑' : '↓' ?> <?= abs($stats['spent']['trend']) ?>% к прошлому периоду
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="an-kpi" style="background: linear-gradient(135deg, #f0fdf4, var(--surface)); border-color: #10b981;">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">💎</span>
                </div>
                <div class="an-kpi-value success"><?= number_format($stats['savings'], 0, ',', ' ') ?> ₽</div>
                <div class="an-kpi-label">Экономия vs диспетчер</div>
                <div class="an-kpi-sublabel">Вы бы отдали диспетчеру 15% от оборота</div>
            </div>
            
        <?php else: ?>
            <!-- CARRIER KPI -->
            <div class="an-kpi">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">📥</span>
                    <?php if ($stats['bids']['trend'] != 0): ?>
                        <span class="an-kpi-trend <?= $stats['bids']['trend'] > 0 ? 'up' : 'down' ?>">
                            <?= $stats['bids']['trend'] > 0 ? '↑' : '↓' ?> <?= abs($stats['bids']['trend']) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="an-kpi-value"><?= $stats['bids']['total'] ?></div>
                <div class="an-kpi-label">Откликов за период</div>
                <div class="an-kpi-sublabel">
                    <?= $stats['bids']['accepted'] ?> принято · <?= $stats['bids']['rejected'] ?> отклонено
                </div>
            </div>
            
            <div class="an-kpi">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">🎯</span>
                    <?php if ($stats['is_top']): ?>
                        <span class="an-kpi-trend up">TOP</span>
                    <?php endif; ?>
                </div>
                <div class="an-kpi-value accent"><?= $stats['win_rate'] ?>%</div>
                <div class="an-kpi-label">Win rate</div>
                <div class="an-kpi-sublabel">
                    Средний по платформе: <?= $stats['platform_win_rate'] ?>%
                </div>
            </div>
            
            <div class="an-kpi" style="background: linear-gradient(135deg, #f0fdf4, var(--surface)); border-color: #10b981;">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">💰</span>
                    <?php if ($stats['earnings']['trend'] != 0): ?>
                        <span class="an-kpi-trend <?= $stats['earnings']['trend'] > 0 ? 'up' : 'down' ?>">
                            <?= $stats['earnings']['trend'] > 0 ? '↑' : '↓' ?> <?= abs($stats['earnings']['trend']) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <div class="an-kpi-value success"><?= number_format($stats['earnings']['total'], 0, ',', ' ') ?> ₽</div>
                <div class="an-kpi-label">Заработано</div>
                <div class="an-kpi-sublabel">
                    <?= $stats['earnings']['deals_count'] ?> сделок
                </div>
            </div>
            
            <div class="an-kpi">
                <div class="an-kpi-header">
                    <span class="an-kpi-icon">📊</span>
                </div>
                <div class="an-kpi-value"><?= number_format($stats['earnings']['avg_deal'], 0, ',', ' ') ?> ₽</div>
                <div class="an-kpi-label">Средний чек</div>
                <div class="an-kpi-sublabel">за одну перевозку</div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- График -->
    <div class="an-chart">
        <div class="an-chart-head">
            <div class="an-chart-title">📈 Динамика за 6 месяцев</div>
        </div>
        <div class="an-chart-wrap">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
    
<?php elseif ($tab === 'routes'): ?>
    
    <!-- Топ маршрутов -->
    <?php if (empty($topRoutes)): ?>
        <div class="an-empty">
            <div class="an-empty-icon">📍</div>
            <h3>Пока нет данных по маршрутам</h3>
            <p>Завершите первую сделку — и здесь появится статистика по вашим маршрутам</p>
        </div>
    <?php else: ?>
        <div class="an-table-wrap">
            <table class="an-table">
                <tr>
                    <th>#</th>
                    <th>Маршрут</th>
                    <th>Поездок</th>
                    <?php if ($role === 'carrier'): ?>
                        <th>Средняя цена</th>
                        <th>Всего заработано</th>
                    <?php else: ?>
                        <th>Средняя цена</th>
                        <th>Всего потрачено</th>
                    <?php endif; ?>
                    <th>Расстояние</th>
                    <th>₽/км</th>
                </tr>
                <?php foreach ($topRoutes as $i => $r): 
                    $rankClass = $i < 3 ? 'top' . ($i + 1) : '';
                ?>
                    <tr>
                        <td><span class="an-rank <?= $rankClass ?>"><?= $i + 1 ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($r['from_city']) ?></strong>
                            <span style="color:var(--text-secondary);"> → </span>
                            <strong><?= htmlspecialchars($r['to_city']) ?></strong>
                        </td>
                        <td><?= $r['trips'] ?></td>
                        <td style="font-weight:700;"><?= number_format($r['avg_price'], 0, ',', ' ') ?> ₽</td>
                        <td style="font-weight:700; color:var(--success);"><?= number_format($r['total_price'], 0, ',', ' ') ?> ₽</td>
                        <td><?= $r['distance'] ? $r['distance'] . ' км' : '—' ?></td>
                        <td style="font-weight:700;"><?= $r['price_per_km'] ? $r['price_per_km'] . ' ₽' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
    
<?php elseif ($tab === 'people'): ?>
    
    <!-- Топ перевозчиков/заказчиков -->
    <?php if (empty($topPeople)): ?>
        <div class="an-empty">
            <div class="an-empty-icon">👥</div>
            <h3>Пока нет данных</h3>
            <p>
                <?= $role === 'owner' 
                    ? 'Завершите первую сделку — появятся ваши проверенные перевозчики' 
                    : 'Завершите первую сделку — появятся ваши постоянные заказчики' 
                ?>
            </p>
        </div>
    <?php else: ?>
        <div class="an-table-wrap">
            <table class="an-table">
                <tr>
                    <th>#</th>
                    <th><?= $role === 'owner' ? 'Перевозчик' : 'Заказчик' ?></th>
                    <th>Рейтинг</th>
                    <th>Сделок</th>
                    <th><?= $role === 'owner' ? 'Потрачено' : 'Заработано' ?></th>
                </tr>
                <?php foreach ($topPeople as $i => $p): 
                    $rankClass = $i < 3 ? 'top' . ($i + 1) : '';
                    $rating = (float)$p['rating'];
                ?>
                    <tr>
                        <td><span class="an-rank <?= $rankClass ?>"><?= $i + 1 ?></span></td>
                        <td>
                            <a href="/admin/user/<?= $p['id'] ?>" style="color:var(--text); text-decoration:none;">
                                <strong><?= htmlspecialchars($p['company_name'] ?: $p['name']) ?></strong>
                            </a>
                        </td>
                        <td>
                            <?php if ($rating > 0): ?>
                                ⭐ <?= number_format($rating, 1) ?>
                            <?php else: ?>
                                <span style="color:var(--text-secondary);">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['deals_count'] ?></td>
                        <td style="font-weight:700; color:var(--success);">
                            <?= number_format($p['total_amount'] ?? $p['total_earned'] ?? 0, 0, ',', ' ') ?> ₽
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
    
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
    var chartType = '<?= $chartType ?>'; // spent | earned
    var isOwner = <?= $role === 'owner' ? 'true' : 'false' ?>;
    
    if (!chartData || chartData.length === 0) return;
    
    var labels = chartData.map(function(d) { return d.label; });
    var values = chartData.map(function(d) { 
        return isOwner ? d.spent : d.earned;
    });
    
    var ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: isOwner ? 'Потрачено (₽)' : 'Заработано (₽)',
                data: values,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#0a0a0a',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString('ru-RU') + ' ₽';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU') + ' ₽';
                        },
                        color: '#64748b',
                        font: { size: 11 }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, weight: '600' }
                    },
                    grid: { display: false }
                }
            }
        }
    });
})();
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>