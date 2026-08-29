<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'ORGANIZER') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$error = '';
$success = '';

$events_result = $conn->execute_query(
    "SELECT event_id, name FROM events WHERE organizer_id = ? ORDER BY start_time DESC",
    [$organizer_id]
);
$organizer_events = $events_result->fetch_all(MYSQLI_ASSOC);

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0 && count($organizer_events) > 0) {
    $event_id = (int)$organizer_events[0]['event_id'];
}

if ($event_id <= 0) {
    header("Location: eo_events.php");
    exit();
}

$event_result = $conn->execute_query(
    "SELECT event_id, name FROM events WHERE event_id = ? AND organizer_id = ?",
    [$event_id, $organizer_id]
);

$event = $event_result->fetch_assoc();

if (!$event) {
    header("Location: eo_events.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {

        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $requirement = (int)($_POST['requirement'] ?? 0);
        $reward_points = (int)($_POST['reward_points'] ?? 0);

        $name = $type;

        if ($name === '' || $type === '' || $requirement <= 0 || $reward_points < 0) {
            $error = "Please fill in all required fields correctly.";
        } else {

            $sql = "INSERT INTO quests
                    (event_id, name, description, type, requirement, reward_points)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $conn->execute_query(
                $sql,
                [
                    $event_id,
                    $name,
                    $description,
                    $type,
                    $requirement,
                    $reward_points
                ]
            );

            header("Location: eo_questcustomiser.php?event_id=" . $event_id . "&success=1");
            exit();
        }
    }

    if ($action === 'delete') {

        $quest_id = (int)($_POST['quest_id'] ?? 0);

        if ($quest_id > 0) {

            $conn->execute_query(
                "DELETE FROM quests WHERE quest_id = ? AND event_id = ?",
                [$quest_id, $event_id]
            );

            header("Location: eo_questcustomiser.php?event_id=" . $event_id);
            exit();
        }
    }
}

if (isset($_GET['success'])) {
    $success = "Quest created successfully.";
}

$quests_result = $conn->execute_query(
    "SELECT quest_id, name, description, type, requirement, reward_points
    FROM quests
    WHERE event_id = ?
    ORDER BY quest_id DESC",
    [$event_id]
);

$quests = $quests_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quest Customizer</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f7f5ef;
            color: #1b4332;
            font-family: Georgia, 'Times New Roman', serif;
        }

        .content {
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        h1 {
            text-align: center;
            font-size: 30px;
            margin: 0 0 25px;
            color: #1b4332;
        }

        .event-select {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            font-family: Arial, sans-serif;
        }

        .event-select label {
            font-weight: 600;
            font-size: 14px;
        }

        .event-select select {
            padding: 8px 12px;
            border: 1px solid #d8cfc0;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .panel {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 25px;
            font-family: Arial, sans-serif;
        }

        .panel h2 {
            margin: 0 0 20px;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 19px;
            color: #1b4332;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #1b4332;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d8cfc0;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            font-family: Arial, sans-serif;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button,
        .btn {
            border: none;
            padding: 11px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: #1b4332;
            color: #fff;
        }

        .btn-danger {
            background: #fff;
            color: #b42318;
            border: 1px solid #e5aeb5;
            padding: 6px 14px;
            font-size: 13px;
        }

        .quest-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .quest-card {
            background: #f7f5ef;
            border: 1px solid #ece5d5;
            border-radius: 8px;
            padding: 15px 18px;
        }

        .quest-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .quest-type {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .quest-description {
            color: #555;
            margin: 0 0 10px;
            font-size: 14px;
            line-height: 1.4;
        }

        .quest-meta {
            font-size: 13px;
            color: #777;
        }

        .message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #e5aeb5;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #a3cfbb;
        }

        .empty {
            text-align: center;
            padding: 30px 15px;
            color: #777;
            font-family: Arial, sans-serif;
        }

        @media (max-width: 768px) {

            .layout {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 18px;
            }

            input,
            select,
            textarea {
                font-size: 16px;
            }
        }

    </style>

    <?php include("global.php"); ?>

</head>

<body>

<?php include("header.php"); ?>

<main class="content">

    <h1>Quest Customizer</h1>

    <?php if (count($organizer_events) > 0): ?>

        <form class="event-select" method="GET">

            <label for="event_id">Select Event:</label>

            <select id="event_id" name="event_id" onchange="this.form.submit()">

                <?php foreach ($organizer_events as $ev): ?>

                    <option
                        value="<?php echo (int)$ev['event_id']; ?>"
                        <?php echo ((int)$ev['event_id'] === $event_id) ? 'selected' : ''; ?>
                    >
                        <?php echo htmlspecialchars($ev['name']); ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </form>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="message error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <?php if ($success !== ''): ?>

        <div class="message success">
            <?php echo htmlspecialchars($success); ?>
        </div>

    <?php endif; ?>

    <div class="layout">

        <div class="panel">

            <h2>Create New Quest</h2>

            <form method="POST">

                <input type="hidden" name="action" value="create">

                <div class="form-group">

                    <label for="type">Quest Type</label>

                    <select id="type" name="type" required>

                        <option value="">Select quest type</option>

                        <option value="LOG_TOTAL">LOG_TOTAL</option>

                        <option value="LOG_STREAK">LOG_STREAK</option>

                        <option value="HEIGHT">HEIGHT</option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="description">Quest Description</label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe this quest"
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

                </div>

                <div class="form-group">

                    <label for="requirement">Requirement (number needed)</label>

                    <input
                        type="number"
                        id="requirement"
                        name="requirement"
                        min="1"
                        required
                        placeholder="e.g. 3"
                        value="<?php echo htmlspecialchars($_POST['requirement'] ?? ''); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="reward_points">Reward Points</label>

                    <input
                        type="number"
                        id="reward_points"
                        name="reward_points"
                        min="0"
                        required
                        placeholder="e.g. 100"
                        value="<?php echo htmlspecialchars($_POST['reward_points'] ?? ''); ?>"
                    >

                </div>

                <button type="submit" class="btn btn-primary">
                    Create Quest
                </button>

            </form>

        </div>

        <div class="panel">

            <h2>Quests for this Event</h2>

            <?php if (count($quests) === 0): ?>

                <div class="empty">
                    No quests have been created for this event yet.
                </div>

            <?php else: ?>

                <div class="quest-list">

                    <?php foreach ($quests as $quest): ?>

                        <div class="quest-card">

                            <div class="quest-card-header">

                                <h3 class="quest-type">
                                    <?php echo htmlspecialchars($quest['type']); ?>
                                </h3>

                                <form method="POST">

                                    <input type="hidden" name="action" value="delete">

                                    <input
                                        type="hidden"
                                        name="quest_id"
                                        value="<?php echo (int)$quest['quest_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                        onclick="return confirm('Delete this quest?');"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                            <p class="quest-description">
                                <?php echo htmlspecialchars($quest['description']); ?>
                            </p>

                            <div class="quest-meta">
                                Requirement: <?php echo (int)$quest['requirement']; ?>
                                &nbsp;|&nbsp;
                                Reward: <?php echo (int)$quest['reward_points']; ?> pts
                            </div>

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

<?php ob_end_flush(); ?>