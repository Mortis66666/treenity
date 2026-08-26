<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ORGANIZER') {
    header('Location: login.php');
    exit;
}

$organizer_id  = $_SESSION['user_id'];
$event_id      = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$search        = trim($_GET['search'] ?? '');


$event = null;
if ($event_id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $organizer_id]);
    $event = $stmt->fetch();
    if (!$event) $event_id = null;
}

$stmt = $pdo->prepare("SELECT event_id, name FROM events WHERE organizer_id = ? ORDER BY start_time DESC");
$stmt->execute([$organizer_id]);
$my_events = $stmt->fetchAll();

$participants = [];
if ($event_id) {
    $sql = "
        SELECT p.participant_id, u.user_id, u.name, u.tp_number, u.email,
            COUNT(l.log_id) as log_count
        FROM participants p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN logs l ON l.participant_id = p.participant_id
        WHERE p.event_id = ?
    ";
    $params = [$event_id];
    if ($search !== '') {
        $sql .= " AND (u.name LIKE ? OR u.tp_number LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    $sql .= " GROUP BY p.participant_id ORDER BY u.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $participants = $stmt->fetchAll();
}

$quest_progress = [];
if ($event_id && !empty($participants)) {
    $stmt = $pdo->prepare("
        SELECT qp.participant_id, SUM(qp.value * q.reward_points) as total_points
        FROM quest_progress qp
        JOIN quests q ON qp.quest_id = q.quest_id
        WHERE q.event_id = ?
        GROUP BY qp.participant_id
    ");
    $stmt->execute([$event_id]);
    foreach ($stmt->fetchAll() as $row) {
        $quest_progress[$row['participant_id']] = $row['total_points'];
    }
}

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css">
<style>
    .eo-wrap {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
    }
    .event-select-wrap {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .event-select-wrap label {
        font-size: 13px;
        color: #c8d4e8;
        white-space: nowrap;
        margin: 0;
    }
    select {
        padding: 9px 12px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 6px;
        color: #c8d4e8;
        font-size: 13px;
    }
    select:focus {
        border-color: #2563eb;
        outline: none;
    }
    .card {
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px;
        padding: 18px;
    }
    .card-title {
        font-size: 13px;
        font-weight: 600;
        color: #6b7a99;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .controls {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }
    .search-input {
        flex: 1;
        min-width: 180px;
        padding: 8px 12px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 6px;
        color: #c8d4e8;
        font-size: 13px;
    }
    .search-input:focus {
        border-color: #2563eb;
        outline: none;
    }
    .btn-search {
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        font-weight: 600;
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
        border-bottom: 1px solid #2a3a50;
    }
    td {
        padding: 10px;
        color: #c8d4e8;
        border-bottom: 1px solid #1e2d42;
        vertical-align: middle;
    }
    tr:last-child td {
        border: none;
    }
    tr:hover td {
        background: #1e2d42;
    }
    .avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #1e3a5f;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #93c5fd;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .pts-badge {
        background: #1e3a5f;
        color: #93c5fd;
        font-size: 11px;'
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    .btn-view {
        font-size: 11px;
        color: #4a9eff;
        text-decoration: none;
        font-weight: 600;
    }
    .btn-view:hover {
        text-decoration: underline;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6b7a99;
        font-size: 13px;
    }
    .event-info {
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 7px;
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #c8d4e8;
    }
    .event-info strong {
        color: #fff;
        }
</style>

<div class="eo-wrap">
    <div class="page-title">Participant List</div>

    <div class="event-select-wrap">
        <label>Select Event:</label>
        <form method="GET" style="display:flex;gap:8px;flex:1">
            <select name="event_id" onchange="this.form.submit()">
                <option value="">-- Choose event --</option>
                <?php foreach ($my_events as $ev): ?>
                <option value="<?= $ev['event_id'] ?>" <?= $event_id == $ev['event_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!$event_id): ?>
        <div class="empty-state">Please select an event to view participants.</div>
    <?php else: ?>

    <div class="event-info">
        <strong><?= htmlspecialchars($event['name']) ?></strong>
        &nbsp;&mdash;&nbsp;
        <?= date('d M Y', strtotime($event['start_time'])) ?> to <?= date('d M Y', strtotime($event['end_time'])) ?>
        &nbsp;&mdash;&nbsp;
        <strong><?= count($participants) ?></strong> participant(s)
    </div>

    <div class="card">
        <div class="card-title">Participants</div>

        <form method="GET" class="controls">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <input type="text" name="search" class="search-input" placeholder="Search by name or TP number…" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-search">Search</button>
        </form>

        <?php if (empty($participants)): ?>
            <div class="empty-state">No participants found for this event.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>TP Number</th>
                    <th>Email</th>
                    <th>Plant Updates</th>
                    <th>Points Earned</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $i => $p):
                    $initials = strtoupper(substr($p['name'], 0, 2));
                    $pts = $quest_progress[$p['participant_id']] ?? 0;
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="avatar"><?= $initials ?></div>
                            <span><?= htmlspecialchars($p['name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($p['tp_number'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= $p['log_count'] ?></td>
                    <td><span class="pts-badge"><?= $pts ?> pts</span></td>
                    <td>
                        <a href="eo_participant_detail.php?participant_id=<?= $p['participant_id'] ?>&event_id=<?= $event_id ?>"
                            class="btn-view">View Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>