<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ORGANIZER') {
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard - Treenity</title>
    <?php include("global.php"); ?>
<style>
    * {
        box-sizing: border-box;
    }

    .eo-wrap {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px 60px;
        flex: 1;
        width: 100%;
    }

    .page-title {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 32px;
        color: #1b4332;
        margin-bottom: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }

    .stats-card {
        background: #fff;
        border: 1px solid #e0dacd;
        border-radius: 8px;
        padding: 18px;
    }

    .stats-card .val {
        font-size: 28px;
        font-weight: 700;
        color: #1b4332;
    }

    .stats-card .lbl {
        font-size: 13px;
        color: #6b6355;
        margin-top: 4px;
    }

    .card {
        background: #fff;
        border: 1px solid #e0dacd;
        border-radius: 8px;
        padding: 22px;
        margin-bottom: 18px;
        width: 100%;
    }

    .card-title {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 19px;
        color: #1b4332;
        margin-bottom: 16px;
    }


    .table-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    th {
        text-align: left;
        padding: 10px;
        color: #6b6355;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 2px solid #e0dacd;
        white-space: nowrap;
    }

    td {
        padding: 10px;
        color: #33302a;
        border-bottom: 1px solid #eee6d8;
        white-space: nowrap;
    }

    tr:last-child td {
        border-bottom: none;
    }


    .event-list-mobile {
        display: none;
    }

    .event-item {
        border: 1px solid #e0dacd;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
    }

    .event-item:last-child {
        margin-bottom: 0;
    }

    .event-item-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
    }

    .event-item-name {
        font-weight: 700;
        color: #1b4332;
        font-size: 17px;
        overflow-wrap: break-word;
    }

    .event-item-row {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        color: #33302a;
        padding: 6px 0;
        border-top: 1px solid #eee6d8;
    }

    .event-item-row span:first-child {
        color: #6b6355;
    }

    .event-item .btn-view {
        display: block;
        text-align: center;
        margin-top: 12px;
        padding: 12px;
        border: 1px solid #e0dacd;
        border-radius: 6px;
        font-size: 14px;
    }

    .badge {
        font-size: 13px;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-active {
        background: #d8f0dc;
        color: #1b4332;
    }

    .badge-ended {
        background: #ece7dc;
        color: #7a7264;
    }

    .badge-upcoming {
        background: #dbe8f5;
        color: #1c4e80;
    }

    .btn-view {
        font-size: 13px;
        font-weight: 600;
        color: #1b4332;
        text-decoration: none;
    }

    .btn-view:hover {
        text-decoration: underline;
    }

    .quick-links {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .btn-primary {
        background: #1b4332;
        color: #fff;
        border: none;
        padding: 13px 20px;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary:hover {
        background: #2d6a4f;
    }

    .btn-secondary {
        background: #f4f1ea;
        color: #1b4332;
        border: 1px solid #e0dacd;
        padding: 13px 20px;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #ece7d9;
    }


    @media (max-width: 768px) {
        .eo-wrap {
            padding: 0 15px 40px;
            margin: 20px auto;
        }

        .page-title {
            font-size: 27px;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .card {
            padding: 18px;
        }
    }


    @media (max-width: 600px) {
        .quick-links {
            flex-direction: column;
        }

        .quick-links a {
            width: 100%;
            text-align: center;
        }

        .table-container {
            display: none;
        }

        .event-list-mobile {
            display: block;
        }
    }

    @media (max-width: 400px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .stats-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
        }

        .stats-card .val {
            font-size: 26px;
        }

        .stats-card .lbl {
            font-size: 13px;
            margin-top: 0;
        }
    }
</style>
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

<div class="eo-wrap">
    <div class="page-title">Organizer Dashboard</div>

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

    <div class="quick-links">
        <a href="eo_create_event.php" class="btn-primary">Create New Event</a>
        <a href="eo_events.php" class="btn-secondary">View All Events</a>
        <a href="eo_questcustomiser.php" class="btn-secondary">Quest Customizer</a>
    </div>

    <div class="card">
        <div class="card-title">Recent Events</div>
        <?php if (empty($recent_events)): ?>
            <p style="color:#6b6355;font-size:13px;">No events yet. <a href="eo_create_event.php" style="color:#1b4332;font-weight:600;">Create one now.</a></p>
        <?php else: ?>
            <div class="table-container">
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
            </div>

            <div class="event-list-mobile">
                <?php foreach ($recent_events as $event):
                    $now = new DateTime();
                    $start = new DateTime($event['start_time']);
                    $end = new DateTime($event['end_time']);
                    if ($now < $start) $status = 'Upcoming';
                    elseif ($now > $end) $status = 'Ended';
                    else $status = 'Active';
                    $badge = $status === 'Active' ? 'badge-active' : ($status === 'Ended' ? 'badge-ended' : 'badge-upcoming');
                ?>
                    <div class="event-item">
                        <div class="event-item-top">
                            <div class="event-item-name"><?= htmlspecialchars($event['name']) ?></div>
                            <span class="badge <?= $badge ?>"><?= $status ?></span>
                        </div>
                        <div class="event-item-row">
                            <span>Start</span>
                            <span><?= htmlspecialchars($event['start_time']) ?></span>
                        </div>
                        <div class="event-item-row">
                            <span>End</span>
                            <span><?= htmlspecialchars($event['end_time']) ?></span>
                        </div>
                        <div class="event-item-row">
                            <span>Participants</span>
                            <span><?= htmlspecialchars($event['participant_count']) ?></span>
                        </div>
                        <a href="eo_participants.php?event_id=<?= $event['event_id'] ?>" class="btn-view">View Participants</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>