<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANIZER') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$success = '';
$errors = array();

$result = $conn->execute_query("SELECT event_id, name FROM events WHERE organizer_id = ? ORDER BY start_time DESC", [$organizer_id]);
$my_events = $result->fetch_all(MYSQLI_ASSOC);

$selected_event_id = 0;
if (isset($_GET['event_id'])) {
    $selected_event_id = (int)$_GET['event_id'];
} else if (count($my_events) > 0) {
    $selected_event_id = $my_events[0]['event_id'];
}

if ($selected_event_id > 0) {
    $check_result = $conn->execute_query("SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?", [$selected_event_id, $organizer_id]);
    if (!$check_result->fetch_assoc()) {
        $selected_event_id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] == 'create') {
        $event_id = (int)$_POST['event_id'];
        $type = trim($_POST['type']);
        $description = trim($_POST['description'] ?? '');
        $requirement = (int)$_POST['requirement'];
        $reward_points = (int)$_POST['reward_points'];

        if ($type == '') {
            $errors[] = "Quest type is required.";
        }
        if ($description == '') {
            $errors[] = "Quest description is required.";
        }
        if ($requirement <= 0) {
            $errors[] = "Requirement must be greater than 0.";
        }
        if ($reward_points <= 0) {
            $errors[] = "Reward points must be greater than 0.";
        }

        if (count($errors) == 0) {
            $conn->execute_query("INSERT INTO quests (event_id, description, type, requirement, reward_points) VALUES (?, ?, ?, ?, ?)", [$event_id, $description, $type, $requirement, $reward_points]);
            $success = "Quest created successfully!";
            $selected_event_id = $event_id;
        }
    }

    if ($_POST['action'] == 'delete') {
        $quest_id = (int)$_POST['quest_id'];
        $conn->execute_query("DELETE q FROM quests q, events e WHERE q.event_id = e.event_id AND q.quest_id = ? AND e.organizer_id = ?", [$quest_id, $organizer_id]);
        $success = "Quest deleted.";
    }
}

$quests = array();
if ($selected_event_id > 0) {
    $quest_result = $conn->execute_query("SELECT * FROM quests WHERE event_id = ? ORDER BY quest_id ASC", [$selected_event_id]);
    $quests = $quest_result->fetch_all(MYSQLI_ASSOC);
}

$quest_types = array('LOG_TOTAL', 'LOG_STREAK', 'HEIGHT');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Customizer</title>

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

.success-box {
    background: #d8f0dc;
    border: 1px solid #9bd4a8;
    color: #1b4332;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.error-box {
    background: #fbe3e3;
    border: 1px solid #e08d8d;
    color: #8a2e2e;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.event-select-form {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
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

.quest-layout {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 20px;
}

.section-box {
    background: #fff;
    border: 1px solid #e0dacd;
    border-radius: 8px;
    padding: 22px;
}

form label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #1b4332;
    margin-bottom: 6px;
    margin-top: 14px;
}

form label:first-of-type {
    margin-top: 0;
}

form select,
form input[type="number"],
form textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d8cfc0;
    border-radius: 6px;
    font-size: 14px;
    background: #fdfcfa;
    box-sizing: border-box;
}

form textarea {
    resize: vertical;
}

form select:focus,
form input:focus,
form textarea:focus {
    outline: none;
    border-color: #1b4332;
}

.btn-primary {
    background: #1b4332;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 18px;
}

.btn-primary:hover {
    background: #2d6a4f;
}

.quest-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #f4f1ea;
    border: 1px solid #e0dacd;
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 8px;
}

.quest-item:last-child {
    margin-bottom: 0;
}

.quest-type {
    font-weight: 700;
    color: #1b4332;
    margin: 0 0 4px 0;
}

.quest-meta {
    font-size: 13px;
    color: #6b6355;
    margin: 0;
}

.quest-item form {
    margin: 0;
}

.quest-item button {
    background: none;
    border: 1px solid #e08d8d;
    color: #a33;
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
}

.quest-item button:hover {
    background: #fbe3e3;
}

@media (max-width: 700px) {
    .quest-layout {
        grid-template-columns: 1fr;
    }
}

</style>
    <?php include("global.php"); ?>

</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

        <h1>Quest Customizer</h1>

        <?php if ($success != '') { ?>
            <div class="success-box"><?php echo $success; ?></div>
        <?php } ?>
        <?php foreach ($errors as $error) { ?>
            <div class="error-box"><?php echo $error; ?></div>
        <?php } ?>

        <form method="GET" action="eo_questcustomiser.php" class="event-select-form">
            <label for="event_id">Select Event:</label>
            <select name="event_id" id="event_id" onchange="this.form.submit()">
                <option value="">-- Choose event --</option>
                <?php foreach ($my_events as $ev) { ?>
                    <option value="<?php echo $ev['event_id']; ?>" <?php if ($selected_event_id == $ev['event_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($ev['name']); ?>
                    </option>
                <?php } ?>
            </select>
        </form>

        <?php if ($selected_event_id == 0) { ?>
            <p>Please select an event to manage quests.</p>
        <?php } else { ?>

        <div class="quest-layout">

            <div class="section-box">
                <h2>Create New Quest</h2>
                <form method="POST" action="eo_questcustomiser.php">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">

                    <label for="type">Quest Type</label>
                    <select name="type" id="type" required>
                        <option value="">-- Select quest type --</option>
                        <?php foreach ($quest_types as $qt) { ?>
                            <option value="<?php echo $qt; ?>"><?php echo $qt; ?></option>
                        <?php } ?>
                    </select>

                    <label for="description">Quest Description</label>
                    <textarea name="description" id="description" rows="4" placeholder="Describe this quest" required></textarea>

                    <label for="requirement">Requirement (number needed)</label>
                    <input type="number" name="requirement" id="requirement" min="1" placeholder="e.g. 3">

                    <label for="reward_points">Reward Points</label>
                    <input type="number" name="reward_points" id="reward_points" min="1" placeholder="e.g. 100">

                    <button type="submit" class="btn-primary">Create Quest</button>
                </form>
            </div>

            <div class="section-box">
                <h2>Quests for this Event</h2>

                <?php if (count($quests) == 0) { ?>
                    <p>No quests yet. Create one on the left.</p>
                <?php } else { ?>

                    <?php foreach ($quests as $q) { ?>
                    <div class="quest-item">
                        <div class="quest-info">
                            <p class="quest-type"><?php echo htmlspecialchars($q['type']); ?></p>
                            <p class="quest-description"><?php echo htmlspecialchars($q['description'] ?? ''); ?></p>
                            <p class="quest-meta">
                                Requirement: <b><?php echo $q['requirement']; ?></b> &nbsp;|&nbsp;
                                Reward: <b><?php echo $q['reward_points']; ?> pts</b>
                            </p>
                        </div>
                        <form method="POST" action="eo_questcustomiser.php" onsubmit="return confirm('Delete this quest?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="quest_id" value="<?php echo $q['quest_id']; ?>">
                            <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </div>
                    <?php } ?>

                <?php } ?>
            </div>

        </div>

        <?php } ?>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>