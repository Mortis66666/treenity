<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];

$result = $conn->execute_query("SELECT COUNT(*) AS total_events FROM events WHERE organizer_id = ?", [$organizer_id]);
$total_events = $result->fetch_assoc()['total_events'];

$result = $conn->execute_query("
SELECT COUNT(p.participant_id) as total
from participants p
JOIN events e ON p.event_id = e.event_id
WHERE e.organizer_id = ?
", [$organizer_id]);
$total_participants = $result->fetch_assoc()['total'];

$result = $conn->execute_query("
SELECT COUNT(l.log_id) as total
FROM logs l
JOIN participants p ON l.participant_id = p.participant_id
JOIN events e ON p.event_id = e.event_id
WHERE e.organizer_id = ?
", [$organizer_id]);
$total_logs = $result->fetch_assoc()['total'];

$result = $conn->execute_query("
SELECT COUNT(*) AS total from events
WHERE organizer_id = ? AND start_time <= NOW() AND end_time >= NOW()
", [$organizer_id]);
$total_active_events = $result->fetch_assoc()['total'];

$result = $conn->execute_query("
SELECT e.event_id, e.name, e.start_time, e.end_time, COUNT(p.participant_id) AS participant_count
FROM events e
LEFT JOIN participants p ON e.event_id = p.event_id
WHERE e.organizer_id = ?
GROUP BY e.event_id
ORDER BY e.last_updated DESC
LIMIT 5
", [$organizer_id]);
$recent_events = $result->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css ">
<style>
    .eo-wrap {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }

    .stats-card {
        background: #1a2236;
        border: 1px solid #2c3e50;
        border-radius: 10px;
        padding: 16px;
    }

    .stats-card .val {
        font-size: 28px;
        font-weight: 700;
        color: #fff;
    }

    .stats-card .lbl {
        font-size: 12px;
        color: #6b7a99;
        margin-top: 4px;
    }

    .card {
        background: #1a2236;
        border: 1px solid #2c3e50;
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 18px;
    }

    .card-title {
        font-size: 13px;
        font-weight: 600;
        color: #6b7a99;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5em;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    th {
        text-align: left;
        padding: 8px 10px;
        color: #6b7a99;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        border-bottom: 1px solid #2c3e50;
    }

    td {
        padding: 8px 10px;
        color: #c8d4e8;
        border-bottom: 1px solid #1e2d42;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .badge {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 600;
    }

    .badge-active {
        background: #14532d;
        color: #86efac;
    }

    .badge-ended {
        background: #1f2937;
        color: #6b7280;
        border: 1px solid #374151;
    }

    .badge-upcoming {
        background: #1e3a8a;
        color: #93c5fd;
    }

    .btn-view {
        font-size: 11px;
        color: #4a9eff;
        text-decoration: none;
    }

    .btn-view:hover {
        text-decoration: underline;
    }

    .quick-links {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
    }

    .btn-primary {
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary:hover {
        background: #1648c0;
    }

    .btn-secondary {
        background: #1e2236;
        color: #c8d4e8;
        border: 1px solid #2a3a50;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #22304a;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .quick-links {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="eo-wrap">
    <div class="page-title">organizer Dashboard</div>

    <!--stats-->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="val"><?= $total_events ?></div>
            <div class="lbl">Events Organised</div>
        </div>
        <div class="stats-card">
            <div class="val"><?= $total_participants ?></div>
            <div class="lbl">Total Participants</div>
        </div>
        <div class="stats-card">
            <div class="val"><?= $total_logs ?></div>
            <div class="lbl">Total Plants Created</div>
        </div>
        <div class="stats-card">
            <div class="val"><?= $total_active_events ?></div>
            <div class="lbl">Active Events</div>
        </div>
    </div>

    <!--quick links-->
    <div class="quick-links">
        <a href="eo_create_event.php" class="btn-primary">Create New Event</a>
        <a href="eo_events.php" class="btn-secondary">View All Events</a>
        <a href="eo_quest_customizer.php" class="btn-secondary">Quest Customizer</a>
        <a href="eo_inventory.php" class="btn-secondary">Inventory</a>
    </div>

    <div class="card">
        <div class="card-title">Recent Events</div>
        <?php if (empty($recent_events)): ?>
            <p style="color:#6b7a99;font-size:13px;">No events yet. <a href="eo_create_event.php" style="color:#4a9eff;">Create one now.</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Participants</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_events as $event):
                        $now = new DateTime();
                        $start = new DateTime($event['start_time']);
                        $end = new DateTime($event['end_time']);
                        if ($now < $start) $status = 'Upcoming';
                        elseif ($now > $end) $status = 'Ended';
                        else $status = 'Active';
                        $badge = $status === 'Active' ? 'badge-active' : ($status === 'Ended' ? 'badge-ended' : 'badge-upcoming');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($event['name']) ?></td>
                            <td><?= htmlspecialchars($event['start_time']) ?></td>
                            <td><?= htmlspecialchars($event['end_time']) ?></td>
                            <td><?= htmlspecialchars($event['participant_count']) ?></td>
                            <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                            <td><a href="eo_participants.php?event_id=<?= $event['event_id'] ?>" class="btn-view">View Participants</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>