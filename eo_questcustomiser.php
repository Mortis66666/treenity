<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'ORGANIZER') {
    header("Location: ../login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$error = '';
$success = '';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    header("Location: ../eo_events.php");
    exit();
}

$event_result = $conn->execute_query(
    "SELECT event_id, name FROM events WHERE event_id = ? AND organizer_id = ?",
    [$event_id, $organizer_id]
);

$event = $event_result->fetch_assoc();

if (!$event) {
    header("Location: ../eo_events.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $requirement = (int)($_POST['requirement'] ?? 0);
        $reward_points = (int)($_POST['reward_points'] ?? 0);

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

    <title>Quest Customiser</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f7f5ef;
            color: #1b4332;
            font-family: Arial, sans-serif;
        }

        .content {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        h1 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 30px;
            margin-bottom: 8px;
            color: #1b4332;
        }

        h2 {
            color: #1b4332;
        }

        .event-name {
            color: #666;
            margin-bottom: 25px;
        }

        .form-card,
        .quest-card {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
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
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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

        .btn-secondary {
            background: #ece7dc;
            color: #1b4332;
        }

        .btn-danger {
            background: #b42318;
            color: #fff;
        }

        .quest-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .quest-title {
            margin: 0 0 8px;
            font-size: 19px;
        }

        .quest-description {
            color: #666;
            margin: 0 0 15px;
            line-height: 1.5;
        }

        .quest-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        .info-box {
            background: #f7f5ef;
            border-radius: 6px;
            padding: 12px;
        }

        .info-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
        }

        .message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        }

        @media (max-width: 768px) {

            .content {
                padding: 25px 15px 40px;
            }

            h1 {
                font-size: 25px;
            }

            .form-card,
            .quest-card {
                padding: 18px;
            }

            .quest-header {
                flex-direction: column;
            }

            .quest-header form {
                width: 100%;
            }

            .quest-header form button {
                width: 100%;
            }

            .quest-info {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .actions button,
            .actions .btn {
                width: 100%;
            }

            input,
            select,
            textarea {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {

            .content {
                padding: 18px 12px 30px;
            }

            h1 {
                font-size: 22px;
            }

            .form-card,
            .quest-card {
                padding: 15px;
            }
        }

    </style>

</head>

<body>

<?php include("header.php"); ?>

<main class="content">

    <h1>Quest Customiser</h1>

    <div class="event-name">
        <?php echo htmlspecialchars($event['name']); ?>
    </div>

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

    <div class="form-card">

        <h2>Create Quest</h2>

        <form method="POST">

            <input type="hidden" name="action" value="create">

            <div class="form-group">

                <label for="name">Quest Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                >

            </div>

            <div class="form-group">

                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

            </div>

            <div class="form-group">

                <label for="type">Quest Type</label>

                <select id="type" name="type" required>

                    <option value="">Select quest type</option>

                    <option value="LOG_TOTAL">Total Logs</option>

                    <option value="LOG_STREAK">Logging Streak</option>

                    <option value="HEIGHT">Plant Height</option>

                </select>

            </div>

            <div class="form-group">

                <label for="requirement">Requirement</label>

                <input
                    type="number"
                    id="requirement"
                    name="requirement"
                    min="1"
                    required
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
                    value="<?php echo htmlspecialchars($_POST['reward_points'] ?? ''); ?>"
                >

            </div>

            <div class="actions">

                <button type="submit" class="btn btn-primary">
                    Create Quest
                </button>

                <a href="../eo_events.php" class="btn btn-secondary">
                    Back
                </a>

            </div>

        </form>

    </div>

    <h2>Existing Quests</h2>

    <?php if (count($quests) === 0): ?>

        <div class="quest-card empty">
            No quests have been created for this event yet.
        </div>

    <?php else: ?>

        <?php foreach ($quests as $quest): ?>

            <div class="quest-card">

                <div class="quest-header">

                    <div>

                        <h3 class="quest-title">
                            <?php echo htmlspecialchars($quest['name']); ?>
                        </h3>

                        <p class="quest-description">
                            <?php echo htmlspecialchars($quest['description']); ?>
                        </p>

                    </div>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="delete"
                        >

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

                <div class="quest-info">

                    <div class="info-box">

                        <div class="info-label">
                            Type
                        </div>

                        <div class="info-value">
                            <?php echo htmlspecialchars($quest['type']); ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Requirement
                        </div>

                        <div class="info-value">
                            <?php echo (int)$quest['requirement']; ?>
                        </div>

                    </div>

                    <div class="info-box">

                        <div class="info-label">
                            Reward Points
                        </div>

                        <div class="info-value">
                            <?php echo (int)$quest['reward_points']; ?> points
                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

<?php include("footer.php"); ?>

</body>
</html>

<?php ob_end_flush(); ?>