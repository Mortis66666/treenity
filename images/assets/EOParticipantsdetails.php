<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    header('Location: login.php');
    exit;
}

$organizer_id   = $_SESSION['user_id'];
$participant_id = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : 0;
$event_id       = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

$stmt = $pdo->prepare("
    SELECT p.participant_id, u.name, u.email, u.tp_number, e.name AS event_name,
        e.start_time, e.end_time, e.event_id
    FROM participants p
    JOIN users u ON p.user_id = u.user_id
    JOIN events e ON p.event_id = e.event_id
    WHERE p.participant_id = ? AND e.organizer_id = ?
");
$stmt->execute([$participant_id, $organizer_id]);
$participant = $stmt->fetch();

if (!$participant) {
    header('Location: eo_participants.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT l.log_id, l.comments, l.height, i.path AS image_path
    FROM logs l
    LEFT JOIN images i ON l.image_id = i.image_id
    WHERE l.participant_id = ?
    ORDER BY l.log_id ASC
");
$stmt->execute([$participant_id]);
$logs = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT q.type, q.requirement, q.reward_points, qp.value,
        LEAST(qp.value, q.requirement) as progress
    FROM quest_progress qp
    JOIN quests q ON qp.quest_id = q.quest_id
    WHERE qp.participant_id = ? AND q.event_id = ?
");
$stmt->execute([$participant_id, $participant['event_id']]);
$quest_progress = $stmt->fetchAll();

$total_points = 0;
foreach ($quest_progress as $qp) {
    if ($qp['value'] >= $qp['requirement']) {
        $total_points += $qp['reward_points'];
    }
}

$heights = array_column($logs, 'height');

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css">
<style>
    .eo-wrap {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #4a9eff;
        font-size: 13px;
        text-decoration: none;
        margin-bottom: 16px;
    }
    .back-link:hover {
        text-decoration: underline;
    }
    .profile-bar {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px; padding: 16px;
        margin-bottom: 20px;
    }
    .avatar-lg {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: #1e3a5f;
        display: flex; align-items:center;
        justify-content: center;
        color: #93c5fd;
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .profile-name {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
    }
    .profile-meta {
        font-size: 12px;
        color: #6b7a99;
        margin-top: 3px;
    }
    .pts-big {
        margin-left: auto;
        text-align: right;
    }
    .pts-big .val {
        font-size: 22px;
        font-weight: 700;
        color: #93c5fd;
    }
    .pts-big .lbl {
        font-size: 11px;
        color: #6b7a99;
    }
    .two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .card {
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .card-title {
        font-size: 13px;
        font-weight: 600;
        color: #6b7a99;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .chart-wrap {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 100px;
        padding-top: 10px;
    }
    .chart-bar-wrap {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        height: 100%;
    }
    .chart-bar {
        width: 100%;
        border-radius: 3px 3px 0 0;
        background: #2563eb;
        opacity: .8; min-height: 4px;
        transition: height .3s;
    }
    .chart-lbl {
        font-size: 9px;
        color: #6b7a99;
    }
    .quest-row {
        margin-bottom: 12px;
    }
    .quest-row:last-child {
        margin: 0;
    }
    .quest-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    .quest-name {
        font-size: 12px;
        font-weight: 600;
        color: #c8d4e8;
    }
    .quest-pts {
        font-size: 11px;
        color: #6b7a99;
    }
    .progress-track {
        width: 100%;
        height: 7px;
        background: #2a3a50;
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        border-radius: 4px;
        background: #2563eb;
        transition: width .4s;
    }
    .progress-fill.done {
        background: #166534;
    }
    .progress-txt {
        font-size: 10px;
        color: #6b7a99;
        margin-top: 3px;
    }
    .log-card {
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
    }
    .log-card:last-child {
        margin: 0;
    }
    .log-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .log-num {
        font-size: 12px;
        font-weight: 700;
        color: #fff;
    }
    .log-height {
        font-size: 12px;
        color: #6b7a99;
    }
    .log-img {
        width: 100%;
        height: 80px;
        background: #0d1117;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2a3a50;
        font-size: 24px;
        margin-bottom: 8px;
        overflow: hidden;
    }
    .log-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .log-comment {
        font-size: 12px;
        color: #c8d4e8;
        font-style: italic;
    }
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #6b7a99;
        font-size: 13px;
    }
    @media (max-width: 700px) { .two-col { grid-template-columns: 1fr; } .profile-bar { flex-wrap: wrap; } }
</style>

<div class="eo-wrap">
    <a href="eo_participants.php?event_id=<?= $participant['event_id'] ?>" class="back-link">&#8592; Back to Participant List</a>

    <div class="profile-bar">
        <div class="avatar-lg"><?= strtoupper(substr($participant['name'], 0, 2)) ?></div>
        <div>
            <div class="profile-name"><?= htmlspecialchars($participant['name']) ?></div>
            <div class="profile-meta">
                <?= htmlspecialchars($participant['tp_number'] ?? '—') ?>
                &nbsp;&bull;&nbsp;
                <?= htmlspecialchars($participant['email']) ?>
                &nbsp;&bull;&nbsp;
                <?= htmlspecialchars($participant['event_name']) ?>
            </div>
        </div>
        <div class="pts-big">
            <div class="val"><?= $total_points ?></div>
            <div class="lbl">Points Earned</div>
        </div>
    </div>

    <div class="two-col">

        <div>
            <div class="card">
                <div class="card-title">Plant Height Chart</div>
                <?php if (empty($logs)): ?>
                    <div class="empty-state">No updates yet.</div>
                <?php else:
                    $max_h = max(array_map(fn($h) => (float)$h, $heights)) ?: 1;
                ?>
                <div class="chart-wrap">
                    <?php foreach ($logs as $i => $log):
                        $pct = $max_h > 0 ? ((float)$log['height'] / $max_h) * 100 : 0;
                    ?>
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height:<?= max($pct, 4) ?>%"></div>
                        <div class="chart-lbl">#<?= $i + 1 ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="font-size:10px;color:#4a5568;margin-top:6px;text-align:center">Height (cm) per update</div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">Quest Progress</div>
                <?php if (empty($quest_progress)): ?>
                    <div class="empty-state">No quests assigned to this event.</div>
                <?php else: ?>
                    <?php foreach ($quest_progress as $qp):
                        $pct = $qp['requirement'] > 0 ? min(100, round(($qp['value'] / $qp['requirement']) * 100)) : 100;
                        $done = $qp['value'] >= $qp['requirement'];
                    ?>
                    <div class="quest-row">
                        <div class="quest-top">
                            <span class="quest-name"><?= htmlspecialchars($qp['type']) ?></span>
                            <span class="quest-pts"><?= $done ? '+' . $qp['reward_points'] . ' pts' : $qp['reward_points'] . ' pts' ?></span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill <?= $done ? 'done' : '' ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <div class="progress-txt"><?= $qp['value'] ?> / <?= $qp['requirement'] ?> &mdash; <?= $pct ?>% <?= $done ? '&#10003; Complete' : '' ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-title">Plant Update History (<?= count($logs) ?> updates)</div>
                <?php if (empty($logs)): ?>
                    <div class="empty-state">This participant has not submitted any plant updates yet.</div>
                <?php else: ?>
                    <?php foreach (array_reverse($logs) as $i => $log): ?>
                    <div class="log-card">
                        <div class="log-header">
                            <span class="log-num">Update #<?= count($logs) - $i ?></span>
                            <span class="log-height">Height: <?= $log['height'] > 0 ? $log['height'] . ' cm' : '—' ?></span>
                        </div>
                        <div class="log-img">
                            <?php if ($log['image_path']): ?>
                                <img src="<?= htmlspecialchars($log['image_path']) ?>" alt="Plant update photo">
                            <?php else: ?>
                                &#127795;
                            <?php endif; ?>
                        </div>
                        <?php if ($log['comments']): ?>
                            <div class="log-comment">"<?= htmlspecialchars($log['comments']) ?>"</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>