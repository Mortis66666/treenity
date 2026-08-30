<?php
include_once("database.php");
include_once("check_user.php");

check_user_role(['ADMIN']);

function analyticsCount(string $query): int
{
    global $conn;
    $result = $conn->query($query);
    return $result ? (int) ($result->fetch_assoc()['total'] ?? 0) : 0;
}

function analyticsRows(string $query): array
{
    global $conn;
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$total_users = analyticsCount("SELECT COUNT(*) AS total FROM users");
$total_logs = analyticsCount("SELECT COUNT(*) AS total FROM logs");
$total_items = analyticsCount("SELECT COUNT(*) AS total FROM store");
$low_stock_items = analyticsCount("SELECT COUNT(*) AS total FROM store WHERE stock_left <= 10");

$monthly_users = analyticsRows(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
    GROUP BY month_key
    ORDER BY month_key ASC"
);


$monthly_logs = [];
$monthly_logs = analyticsRows(
    "SELECT DATE_FORMAT(logged_at, '%Y-%m') AS month_key, COUNT(*) AS total
    FROM logs
    WHERE logged_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
    GROUP BY month_key
    ORDER BY month_key ASC"
);

$months = [];
$month_cursor = new DateTime('first day of -11 months');
for ($index = 0; $index < 12; $index++) {
    $month_key = $month_cursor->format('Y-m');
    $months[$month_key] = [
        'label' => $month_cursor->format('M'),
        'users' => 0,
        'logs' => 0
    ];
    $month_cursor->modify('+1 month');
}
foreach ($monthly_users as $row) {
    if (isset($months[$row['month_key']])) {
        $months[$row['month_key']]['users'] = (int) $row['total'];
    }
}
foreach ($monthly_logs as $row) {
    if (isset($months[$row['month_key']])) {
        $months[$row['month_key']]['logs'] = (int) $row['total'];
    }
}

$chart_max = 1;
foreach ($months as $month) {
    $chart_max = max($chart_max, $month['users'], $month['logs']);
}

$recent_logs = analyticsRows(
    "SELECT l.log_id, l.height, l.comments, u.username
    FROM logs l
    JOIN participants p ON p.participant_id = l.participant_id
    JOIN users u ON u.user_id = p.user_id
    ORDER BY l.log_id DESC
    LIMIT 6"
);
$inventory = analyticsRows(
    "SELECT name, stock_left, cost FROM store ORDER BY stock_left ASC, name ASC LIMIT 6"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Treenity</title>

    <link rel="stylesheet" href="styles/analytics.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content analytics-page">
        <section class="heading">
            <div>
                <p class="eyebrow">Admin workspace</p>
                <h1>Analytics</h1>
                <p>A clear view of community activity, tree updates, and reward inventory.</p>
            </div>
            <a class="analytics-link" href="users.php">Manage users <span aria-hidden="true">&#8599;</span></a>
        </section>

        <section class="analytics-stats" aria-label="Key statistics">
            <article class="analytics-stat stat-users">
                <span class="stat-icon" aria-hidden="true">&#9679;</span>
                <strong><?= number_format($total_users) ?></strong>
                <span>Registered users</span>
            </article>
            <article class="analytics-stat stat-logs">
                <span class="stat-icon" aria-hidden="true">&#8962;</span>
                <strong><?= number_format($total_logs) ?></strong>
                <span>Tree logs recorded</span>
            </article>
            <article class="analytics-stat stat-items">
                <span class="stat-icon" aria-hidden="true">&#9733;</span>
                <strong><?= number_format($total_items) ?></strong>
                <span>Reward items</span>
            </article>
            <article class="analytics-stat stat-stock">
                <span class="stat-icon" aria-hidden="true">&#9888;</span>
                <strong><?= number_format($low_stock_items) ?></strong>
                <span>Low-stock items</span>
            </article>
        </section>

        <section class="analytics-panel trend-panel" aria-labelledby="trend-title">
            <div class="panel-heading">
                <div>
                    <p class="panel-kicker">Last 12 months</p>
                    <h2 id="trend-title">Community growth</h2>
                </div>
                <div class="chart-legend" aria-label="Chart legend">
                    <span><i class="legend-swatch users-swatch"></i>Users</span>
                    <span><i class="legend-swatch logs-swatch"></i>Logs</span>
                </div>
            </div>
            <div class="trend-chart" role="img" aria-label="Monthly users and tree logs for the last 12 months">
                <?php foreach ($months as $month): ?>
                    <div class="month-column">
                        <div class="bar-area">
                            <span class="bar users-bar" style="height: <?= max(3, round($month['users'] / $chart_max * 100)) ?>%" title="<?= $month['users'] ?> users"></span>
                            <span class="bar logs-bar" style="height: <?= max(3, round($month['logs'] / $chart_max * 100)) ?>%" title="<?= $month['logs'] ?> logs"></span>
                        </div>
                        <span class="month-label"><?= htmlspecialchars($month['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="analytics-columns">
            <section class="analytics-panel" aria-labelledby="activity-title">
                <div class="panel-heading compact-heading">
                    <div>
                        <p class="panel-kicker">Latest entries</p>
                        <h2 id="activity-title">Recent tree logs</h2>
                    </div>
                </div>
                <?php if (!$recent_logs): ?>
                    <p class="empty-panel">No tree logs have been recorded yet.</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="activity-row">
                                <span class="activity-mark" aria-hidden="true">&#8226;</span>
                                <div>
                                    <strong><?= htmlspecialchars($log['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars($log['comments'] ?: 'Tree update recorded', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <b><?= htmlspecialchars($log['height'] ?? '-', ENT_QUOTES, 'UTF-8') ?> cm</b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="analytics-panel" aria-labelledby="inventory-title">
                <div class="panel-heading compact-heading">
                    <div>
                        <p class="panel-kicker">Reward catalogue</p>
                        <h2 id="inventory-title">Inventory watch</h2>
                    </div>
                    <a class="panel-link" href="new_item.php">Add item</a>
                </div>
                <?php if (!$inventory): ?>
                    <p class="empty-panel">No reward items are available.</p>
                <?php else: ?>
                    <div class="inventory-list">
                        <?php foreach ($inventory as $item): ?>
                            <div class="inventory-row">
                                <div>
                                    <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= number_format((int) $item['cost']) ?> points</span>
                                </div>
                                <b class="<?= (int) $item['stock_left'] <= 10 ? 'is-low' : '' ?>"><?= number_format((int) $item['stock_left']) ?> left</b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>