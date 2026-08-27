<?php
session_start();
require("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'organizer') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$event = null;
if ($event_id > 0) {
    $event_stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND organizer_id = ?");
    $event_stmt->execute(array($event_id, $organizer_id));
    $event = $event_stmt->fetch();
    if (!$event) {
        $event_id = 0;
    }
}

$my_events_stmt = $pdo->prepare("SELECT event_id, name FROM events WHERE organizer_id = ? ORDER BY start_time DESC");
$my_events_stmt->execute(array($organizer_id));
$my_events = $my_events_stmt->fetchAll();

$participants = array();
if ($event_id > 0) {
    $sql = "SELECT p.participant_id, u.name, u.tp_number, u.email, COUNT(l.log_id) AS log_count
            FROM participants p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN logs l ON l.participant_id = p.participant_id
            WHERE p.event_id = ?";
    $params = array($event_id);

    if ($search != '') {
        $sql .= " AND (u.name LIKE ? OR u.tp_number LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }

    $sql .= " GROUP BY p.participant_id ORDER BY u.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $participants = $stmt->fetchAll();
}

$points_by_participant = array();
if ($event_id > 0 && count($participants) > 0) {
    $points_stmt = $pdo->prepare("SELECT qp.participant_id, SUM(qp.value * q.reward_points) AS total_points
                                   FROM quest_progress qp
                                   JOIN quests q ON qp.quest_id = q.quest_id
                                   WHERE q.event_id = ?
                                   GROUP BY qp.participant_id");
    $points_stmt->execute(array($event_id));
    $points_rows = $points_stmt->fetchAll();
    foreach ($points_rows as $row) {
        $points_by_participant[$row['participant_id']] = $row['total_points'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant List</title>

    <style>
.content {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}

.content h1 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 32px;
    color: #1b4332;
    margin-bottom: 20px;
}

.content h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 18px;
    color: #1b4332;
    margin-bottom: 16px;
}

.event-select-form {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.event-select-form label {
    font-size: 14px;
    color: #1b4332;
    font-weight: 600;
}

.event-select-form select {
    padding: 9px 12px;
    border: 1px solid #d8cfc0;
    border-radius: 6px;
    font-size: 14px;
    background: #fdfcfa;
}

.event-info-box {
    background: #f4f1ea;
    border: 1px solid #e0dacd;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 18px;
    font-size: 14px;
    color: #33302a;
}

.section-box {
    background: #fff;
    border: 1px solid #e0dacd;
    border-radius: 8px;
    padding: 22px;
}

.search-form {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.search-form input[type="text"] {
    flex: 1;
    min-width: 200px;
    padding: 9px 12px;
    border: 1px solid #d8cfc0;
    border-radius: 6px;
    font-size: 14px;
}

.btn-primary {
    background: #1b4332;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary:hover {
    background: #2d6a4f;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.data-table th {
    text-align: left;
    padding: 10px;
    color: #6b6355;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-bottom: 2px solid #e0dacd;
}

.data-table td {
    padding: 12px 10px;
    color: #33302a;
    border-bottom: 1px solid #eee6d8;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover td {
    background: #faf8f3;
}

.data-table a {
    color: #1b4332;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
}

.data-table a:hover {
    text-decoration: underline;
}

</style>
    <?php include("global.php"); ?>

</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

        <h1>Participant List</h1>

        <form method="GET" action="eo_participants.php" class="event-select-form">
            <label for="event_id">Select Event:</label>
            <select name="event_id" id="event_id" onchange="this.form.submit()">
                <option value="">-- Choose event --</option>
                <?php foreach ($my_events as $ev) { ?>
                    <option value="<?php echo $ev['event_id']; ?>" <?php if ($event_id == $ev['event_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($ev['name']); ?>
                    </option>
                <?php } ?>
            </select>
        </form>

        <?php if ($event_id == 0) { ?>
            <p>Please select an event to view participants.</p>
        <?php } else { ?>

        <div class="event-info-box">
            <b><?php echo htmlspecialchars($event['name']); ?></b> &nbsp;-&nbsp;
            <?php echo date("d M Y", strtotime($event['start_time'])); ?> to
            <?php echo date("d M Y", strtotime($event['end_time'])); ?>
            &nbsp;-&nbsp; <?php echo count($participants); ?> participant(s)
        </div>

        <div class="section-box">
            <h2>Participants</h2>

            <form method="GET" action="eo_participants.php" class="search-form">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <input type="text" name="search" placeholder="Search by name or TP number..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary">Search</button>
            </form>

            <?php if (count($participants) == 0) { ?>
                <p>No participants found for this event.</p>
            <?php } else { ?>

            <table class="data-table">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>TP Number</th>
                    <th>Email</th>
                    <th>Plant Updates</th>
                    <th>Points Earned</th>
                    <th></th>
                </tr>

                <?php $i = 1; foreach ($participants as $p) {
                    $points = isset($points_by_participant[$p['participant_id']]) ? $points_by_participant[$p['participant_id']] : 0;
                ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['tp_number']); ?></td>
                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                    <td><?php echo $p['log_count']; ?></td>
                    <td><?php echo $points; ?> pts</td>
                    <td>
                        <a href="eo_participantDetail.php?participant_id=<?php echo $p['participant_id']; ?>&event_id=<?php echo $event_id; ?>">View Detail</a>
                    </td>
                </tr>
                <?php $i++; } ?>

            </table>

            <?php } ?>
        </div>

        <?php } ?>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>