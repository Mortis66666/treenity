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

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css ">
<style>
    .eo-wrap {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px 60px;
        flex: 1;
        width: 100%;
        box-sizing: border-box;
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
        font-size: 12px;
        color: #6b6355;
        margin-top: 4px;
    }

    .card {
        background: #fff;
        border: 1px solid #e0dacd;
        border-radius: 8px;
        padding: 22px;
        margin-bottom: 18px;
    }

    .card-title {
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 18px;
        color: #1b4332;
        margin-bottom: 16px;
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
    }

    td {
        padding: 10px;
        color: #33302a;
        border-bottom: 1px solid #eee6d8;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
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
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
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
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #ece7d9;
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

    @media (max-width: 768px) {

    * {
        box-sizing: border-box;
    }

    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .content,
    .container,
    .main-content {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 20px 15px;
    }

    h1 {
        font-size: 25px;
        line-height: 1.3;
        margin-bottom: 18px;
    }

    h2 {
        font-size: 21px;
    }

    h3 {
        font-size: 18px;
    }

    .card,
    .form-card,
    .event-card,
    .panel,
    .section-card {
        width: 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }

    input,
    select,
    textarea {
        width: 100%;
        max-width: 100%;
        font-size: 16px;
    }

    textarea {
        min-height: 110px;
    }

    button,
    .btn,
    .btn-primary,
    .btn-secondary,
    .btn-danger,
    .btn-success {
        min-height: 44px;
        max-width: 100%;
    }

    .actions,
    .button-group,
    .form-actions {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 10px;
    }

    .actions button,
    .actions a,
    .button-group button,
    .button-group a,
    .form-actions button,
    .form-actions a {
        width: 100%;
        text-align: center;
    }

    .grid,
    .cards,
    .event-grid,
    .stats-grid,
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr !important;
        gap: 15px;
    }

    .stats,
    .statistics {
        display: grid;
        grid-template-columns: 1fr !important;
        gap: 12px;
    }

    table {
        width: 100%;
        min-width: 650px;
    }

    .table-container,
    .table-responsive,
    .participants-table,
    .responsive-table {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .event-card img,
    .card img,
    .banner,
    .event-image {
        width: 100%;
        height: auto;
        max-width: 100%;
        object-fit: cover;
    }

    .modal,
    .modal-content {
        width: calc(100% - 30px);
        max-width: 100%;
        margin: 15px auto;
    }

    .modal-body {
        max-height: 80vh;
        overflow-y: auto;
    }

    .search,
    .search-box,
    .filter,
    .filter-box {
        width: 100%;
        max-width: 100%;
    }

    .search input,
    .search-box input,
    .filter select {
        width: 100%;
    }

    .profile,
    .participant-details,
    .event-details,
    .quest-details {
        width: 100%;
        max-width: 100%;
    }

    .row,
    .form-row,
    .detail-row {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 12px;
    }

    .col,
    .form-col,
    .detail-col {
        width: 100%;
        max-width: 100%;
    }

    .quest-card,
    .participant-card {
        width: 100%;
        padding: 15px;
    }

    .quest-actions,
    .participant-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .quest-actions button,
    .quest-actions a,
    .participant-actions button,
    .participant-actions a {
        width: 100%;
    }

    .alert,
    .error-box,
    .success-box {
        width: 100%;
        max-width: 100%;
        overflow-wrap: break-word;
    }
}

@media (max-width: 480px) {

    .content,
    .container,
    .main-content {
        padding: 15px 12px;
    }

    h1 {
        font-size: 22px;
    }

    h2 {
        font-size: 19px;
    }

    h3 {
        font-size: 17px;
    }

    .card,
    .form-card,
    .event-card,
    .panel,
    .section-card {
        padding: 15px;
        border-radius: 8px;
    }

    input,
    select,
    textarea {
        padding: 11px;
    }

    button,
    .btn,
    .btn-primary,
    .btn-secondary {
        width: 100%;
    }

    table {
        font-size: 13px;
    }

    th,
    td {
        padding: 8px;
        white-space: nowrap;
    }
}
</style>

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