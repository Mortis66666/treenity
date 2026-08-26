<?php
session_start();
require 'database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organiser') {
    header("Location: login.php");
    exit();
}

$organiser_id = $_SESSION['user_id'];

if(isset($_GET['delete_event_id'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = :event_id AND organiser_id = :organiser_id");
    $stmt->execute([$_GET['delete_event_id'], $organiser_id]);
    header("Location: EODashboard.php?deleted=1");
    exit();
}

//Filter
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
SELECT e.event_id, e.name, e.start_time, e.end_time, COUNT(p.participant_id) AS participant_count
FROM events e
LEFT JOIN participants p ON e.event_id = p.event_id
WHERE e.organiser_id = :organiser_id
";
$params = [$organiser_id];

if ($search !== '') {
    $sql .= " AND e.name LIKE :search";
    $params['search'] = '%' . $search . '%';
}

$sql .= " GROUP BY e.event_id ORDER BY e.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();


$now = new DateTime();
$filtered = array_filter($events, function($ev) use ($filter, $now) {
    $start = new DateTime($ev['start_time']);
    $end = new DateTime($ev['end_time']);

    switch ($filter) {
        case 'active':
            return $start <= $now && $end >= $now;
        case 'upcoming':
            return $start > $now;
        case 'past':
            return $end < $now;
        default:
            return true; // 'all'
    }
});

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css">
<style>
    .eo-wrap {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
    }
    .btn-primary {
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary:hover {
        background: #1648c0;
    }
    .controls{
        display: flex;
        gap: 10px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .search-input{
        border-color: #2563eb;
        outline: none;
    }
    .tabs{
        display: flex;
        border-bottom: 1px solid #2a3a50;
        margin-bottom: 20px;
    }
    .tab{
        padding: 8px 16px;
        font-size: 13px;
        color: #6b7a99;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
    }
    .tab.active{
        color: #93c5fd;
        border-bottom-color: #2563eb;
    }
    .tab:hover {
        color: #c8d4e8;
    }
    .event-grid{
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 14px;
    }
    .event-card{
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px;
        overflow: hidden;
    }
    .event-banner{
        width: 100%;
        height: 120px;
        background: #0d1117;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2a3a50;
        font-size: 28px;
    }
    .event-banner img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .event-body{
        padding: 14px;
    }
    .event-name{
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 6px;
    }
    .event-meta{
        font-size: 11px;
        color: #6b7a99;
        margin-bottom: 10px;
        line-height: 1.6;
    }
    .badge{
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 10px;
    }
    .badge-active{
        background: #14532d;
        color: #86efac;
    }
    .badge-ended{
        background: #1f2937;
        color: #6b7280;
        border: 1px solid #374151;
    }
    .badge-upcoming {
        background: #1e3a5f;
        color: #93c5fd;
    }
    .event-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    }
    .btn-sm {
        font-size: 11px;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration:
        none;
        display: inline-block;
        font-weight: 600;
        border: none;
    }
    .btn-blue   {
    background: #1e3a5f;
    color: #93c5fd;
    }
    .btn-blue:hover {
        background: #1a56db;
        color: #fff;
    }
    .btn-green  {
        background: #14532d;
        color: #86efac;
    }
    .btn-green:hover {
        background: #166534;
    }
    .btn-red    {
        background:
        #450a0a; color:
        #fca5a5;
    }
    .btn-red:hover {
        background:
        #7f1d1d;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6b7a99;
    }
    .alert-success {
        background: #14532d;
        border: 1px solid #166534;
        color: #86efac;
        padding: 10px 16px;
        border-radius: 7px;
        margin-bottom: 16px;
        font-size: 13px;
    }
    @media (max-width: 600px) { .event-grid { grid-template-columns: 1fr; } .search-input { width: 100%; } }
</style>
<div class="eo-wrap">
    <div class="page-header">
        <div class="page-title">Events Organised</div>
        <a href="eo_create_event.php" class="btn-primary">+ Create Event</a>
    </div>

    <?php if (isset($_GET['created'])): ?>
        <div class="alert-success">&#10003; Event created successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert-success">&#10003; Event deleted.</div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?filter=all&search=<?= urlencode($search) ?>"      class="tab <?= $filter==='all'?'active':'' ?>">All</a>
        <a href="?filter=active&search=<?= urlencode($search) ?>"   class="tab <?= $filter==='active'?'active':'' ?>">Active</a>
        <a href="?filter=upcoming&search=<?= urlencode($search) ?>" class="tab <?= $filter==='upcoming'?'active':'' ?>">Upcoming</a>
        <a href="?filter=ended&search=<?= urlencode($search) ?>"    class="tab <?= $filter==='ended'?'active':'' ?>">Ended</a>
    </div>

    <!-- Search -->
    <div class="controls">
        <form method="GET" style="display:flex;gap:8px;">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="text" name="search" class="search-input" placeholder="Search events…" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-primary" style="padding:8px 14px;">Search</button>
        </form>
    </div>

    <?php if (empty($filtered)): ?>
        <div class="empty-state">No events found.</div>
    <?php else: ?>
    <div class="event-grid">
        <?php foreach ($filtered as $ev):
            $start = new DateTime($ev['start_time']);
            $end   = new DateTime($ev['end_time']);
            if ($now < $start) { $status = 'Upcoming'; $badge = 'badge-upcoming'; }
            elseif ($now > $end) { $status = 'Ended'; $badge = 'badge-ended'; }
            else { $status = 'Active'; $badge = 'badge-active'; }
        ?>
        <div class="event-card">
            <div class="event-banner">&#127795;</div>
            <div class="event-body">
                <div class="event-name"><?= htmlspecialchars($ev['name']) ?></div>
                <span class="badge <?= $badge ?>"><?= $status ?></span>
                <div class="event-meta">
                    &#128197; <?= date('d M Y, g:ia', strtotime($ev['start_time'])) ?> &ndash; <?= date('d M Y, g:ia', strtotime($ev['end_time'])) ?><br>
                    &#128100; <?= $ev['participant_count'] ?> participants<br>
                    &#128273; Code: <strong><?= htmlspecialchars($ev['verification_code']) ?></strong>
                </div>
                <div class="event-actions">
                    <a href="eo_participants.php?event_id=<?= $ev['event_id'] ?>" class="btn-sm btn-blue">Participants</a>
                    <a href="eo_quest_customizer.php?event_id=<?= $ev['event_id'] ?>" class="btn-sm btn-green">Quests</a>
                    <a href="eo_create_event.php?edit=<?= $ev['event_id'] ?>" class="btn-sm btn-blue">Edit</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                        <input type="hidden" name="delete_event_id" value="<?= $ev['event_id'] ?>">
                        <button type="submit" class="btn-sm btn-red">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>