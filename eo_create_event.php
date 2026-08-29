<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANIZER') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$edit_mode = $event_id > 0;

$name = '';
$description = '';
$start_time = '';
$end_time = '';

if ($edit_mode) {

    $result = $conn->execute_query(
        "SELECT event_id, name, description, start_time, end_time
         FROM events
         WHERE event_id = ? AND organizer_id = ?",
        [$event_id, $organizer_id]
    );

    $event = $result->fetch_assoc();

    if (!$event) {
        header("Location: eo_events.php");
        exit();
    }

    $name = $event['name'] ?? '';
    $description = $event['description'] ?? '';
    $start_time = $event['start_time'] ?? '';
    $end_time = $event['end_time'] ?? '';

    if ($start_time) {
        $start_time = date('Y-m-d\TH:i', strtotime($start_time));
    }

    if ($end_time) {
        $end_time = date('Y-m-d\TH:i', strtotime($end_time));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $action = $_POST['action'] ?? 'create';

    if ($action === 'draft') {

        if ($edit_mode) {

            $conn->execute_query(
                "UPDATE events
                 SET name = ?, description = ?, start_time = ?, end_time = ?
                 WHERE event_id = ? AND organizer_id = ?",
                [
                    $name,
                    $description,
                    $start_time !== '' ? $start_time : null,
                    $end_time !== '' ? $end_time : null,
                    $event_id,
                    $organizer_id
                ]
            );

        } else {

            $conn->execute_query(
                "INSERT INTO events
                 (organizer_id, name, description, start_time, end_time)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $organizer_id,
                    $name,
                    $description,
                    $start_time !== '' ? $start_time : null,
                    $end_time !== '' ? $end_time : null
                ]
            );
        }

        header("Location: eo_events.php?draft_saved=1");
        exit();
    }

    if ($name === '') {

        $error = "Event name is required.";

    } elseif ($start_time === '') {

        $error = "Start time is required.";

    } elseif ($end_time === '') {

        $error = "End time is required.";

    } elseif (strtotime($end_time) <= strtotime($start_time)) {

        $error = "End time must be after the start time.";

    } else {

        if ($edit_mode) {

            $conn->execute_query(
                "UPDATE events
                 SET name = ?, description = ?, start_time = ?, end_time = ?
                 WHERE event_id = ? AND organizer_id = ?",
                [
                    $name,
                    $description,
                    $start_time,
                    $end_time,
                    $event_id,
                    $organizer_id
                ]
            );

        } else {

            $conn->execute_query(
                "INSERT INTO events
                 (organizer_id, name, description, start_time, end_time)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $organizer_id,
                    $name,
                    $description,
                    $start_time,
                    $end_time
                ]
            );
        }

        header("Location: eo_events.php?created=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $edit_mode ? 'Edit Event' : 'Create Event'; ?>
    </title>

    <style>

        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        h1 {
            font-family: Georgia, 'Times New Roman', serif;
            color: #1b4332;
            margin-bottom: 25px;
        }

        .form-card {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 25px;
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
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #d8cfc0;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-draft {
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: #1b4332;
        }

        .btn-primary:hover {
            background: #2d6a4f;
        }

        .btn-draft {
            background: #92620c;
        }

        .btn-draft:hover {
            background: #7a5108;
        }

        .btn-secondary {
            background: #ece7dc;
            color: #1b4332;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .error-box {
            background: #f8d7da;
            border: 1px solid #e5aeb5;
            color: #842029;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
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

    <?php include("global.php"); ?>

</head>

<body>

    <?php include("header.php"); ?>

    <main class="content">

        <h1>
            <?php echo $edit_mode ? 'Edit Event' : 'Create Event'; ?>
        </h1>

        <?php if (isset($error)) { ?>

            <div class="error-box">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <div class="form-card">

            <form method="POST">

                <div class="form-group">

                    <label for="name">
                        Event Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                    ><?php echo htmlspecialchars($description); ?></textarea>

                </div>

                <div class="form-group">

                    <label for="start_time">
                        Start Time
                    </label>

                    <input
                        type="datetime-local"
                        id="start_time"
                        name="start_time"
                        value="<?php echo htmlspecialchars($start_time); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="end_time">
                        End Time
                    </label>

                    <input
                        type="datetime-local"
                        id="end_time"
                        name="end_time"
                        value="<?php echo htmlspecialchars($end_time); ?>"
                    >

                </div>

                <div class="actions">

                    <button
                        type="submit"
                        name="action"
                        value="create"
                        class="btn-primary"
                    >
                        <?php echo $edit_mode ? 'Update Event' : 'Create Event'; ?>
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="draft"
                        class="btn-draft"
                    >
                        Save as Draft
                    </button>

                    <a
                        href="eo_events.php"
                        class="btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </main>

    <?php include("footer.php"); ?>

</body>

</html>

<?php ob_end_flush(); ?>