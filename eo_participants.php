<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] != 'ORGANIZER'
) {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];

$participant_id = isset($_GET['participant_id'])
    ? (int)$_GET['participant_id']
    : 0;

$result = $conn->execute_query(
    "SELECT
        p.participant_id,
        u.name,
        u.email,
        u.tp_number,
        e.name AS event_name,
        e.event_id
    FROM participants p
    JOIN users u ON p.user_id = u.user_id
    JOIN events e ON p.event_id = e.event_id
    WHERE p.participant_id = ?
    AND e.organizer_id = ?",
    [$participant_id, $organizer_id]
);

$participant = $result->fetch_assoc();

if (!$participant) {

    $event_id = isset($_GET['event_id'])
        ? (int)$_GET['event_id']
        : 0;

    if ($event_id > 0) {
        header(
            "Location: eo_participants_list.php?event_id=" . $event_id
        );
    } else {
        header("Location: eo_participants_list.php");
    }

    exit();
}

$logs_result = $conn->execute_query(
    "SELECT
        l.log_id,
        l.comments,
        l.height,
        i.path AS image_path
    FROM logs l
    LEFT JOIN images i ON l.image_id = i.image_id
    WHERE l.participant_id = ?
    ORDER BY l.log_id ASC",
    [$participant_id]
);

$logs = $logs_result->fetch_all(MYSQLI_ASSOC);

$quest_result = $conn->execute_query(
    "SELECT
        q.type,
        q.requirement,
        q.reward_points,
        qp.value
    FROM quest_progress qp
    JOIN quests q ON qp.quest_id = q.quest_id
    WHERE qp.participant_id = ?
    AND q.event_id = ?",
    [
        $participant_id,
        $participant['event_id']
    ]
);

$quest_progress = $quest_result->fetch_all(MYSQLI_ASSOC);

$total_points = 0;

foreach ($quest_progress as $qp) {

    if ($qp['value'] >= $qp['requirement']) {

        $total_points =
            $total_points + $qp['reward_points'];
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Participant Detail
    </title>

    <style>

        .content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        .back-link {
            display: inline-block;
            color: #1b4332;
            font-size: 14px;
            text-decoration: none;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .profile-box {
            background: #f4f1ea;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .profile-box h2 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 22px;
            color: #1b4332;
            margin: 0 0 8px 0;
        }

        .profile-box p {
            font-size: 14px;
            color: #6b6355;
            margin: 4px 0;
        }

        .points-display {
            font-size: 20px;
            font-weight: 700;
            color: #1b4332;
            margin-top: 10px;
        }

        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .section-box {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 22px;
        }

        .content h2 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 18px;
            color: #1b4332;
            margin-bottom: 16px;
        }

        .quest-progress-row {
            margin-bottom: 16px;
        }

        .quest-progress-row:last-child {
            margin-bottom: 0;
        }

        .quest-progress-row p {
            font-size: 13px;
            color: #33302a;
            margin: 0 0 6px 0;
        }

        .progress-bar-track {
            width: 100%;
            height: 8px;
            background: #e0dacd;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: #1b4332;
            border-radius: 5px;
        }

        .progress-text {
            font-size: 12px;
            color: #6b6355;
            margin-top: 4px;
        }

        .log-card {
            background: #f4f1ea;
            border: 1px solid #e0dacd;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 10px;
        }

        .log-card:last-child {
            margin-bottom: 0;
        }

        .log-card p {
            font-size: 13px;
            color: #33302a;
            margin: 6px 0;
        }

        .log-image {
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 6px;
            margin: 8px 0;
        }

        .log-comment {
            font-style: italic;
            color: #6b6355;
        }

        @media (max-width: 700px) {

            .detail-layout {
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

@media (max-width: 768px) {
    .participants-container {
        width: 100%;
        overflow-x: auto;
    }

    .participants-container table {
        min-width: 700px;
    }

    .participant-search {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .participant-search input,
    .participant-search button {
        width: 100%;
    }
}

    </style>

    <?php include("global.php"); ?>

</head>

<body>

    <?php include("header.php"); ?>

    <main class="content">

        <a
            href="eo_participants_list.php?event_id=<?php echo $participant['event_id']; ?>"
            class="back-link"
        >
            &laquo; Back to Participant List
        </a>

        <div class="profile-box">

            <h2>
                <?php
                echo htmlspecialchars($participant['name']);
                ?>
            </h2>

            <p>

                <?php
                echo htmlspecialchars(
                    $participant['tp_number']
                );
                ?>

                &nbsp;-&nbsp;

                <?php
                echo htmlspecialchars(
                    $participant['email']
                );
                ?>

                &nbsp;-&nbsp;

                <?php
                echo htmlspecialchars(
                    $participant['event_name']
                );
                ?>

            </p>

            <p class="points-display">

                <?php echo $total_points; ?>

                Points Earned

            </p>

        </div>

        <div class="detail-layout">


            <div class="section-box">

                <h2>
                    Quest Progress
                </h2>

                <?php if (count($quest_progress) == 0) { ?>

                    <p>
                        No quests assigned to this event.
                    </p>

                <?php } else { ?>

                    <?php foreach ($quest_progress as $qp) {

                        if ($qp['requirement'] > 0) {

                            $percent = round(
                                ($qp['value'] / $qp['requirement']) * 100
                            );

                            if ($percent > 100) {
                                $percent = 100;
                            }

                        } else {

                            $percent = 0;

                        }

                        $is_done =
                            ($qp['value'] >= $qp['requirement']);

                    ?>

                        <div class="quest-progress-row">

                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $qp['type']
                                );
                                ?>

                                -

                                <?php
                                echo $qp['reward_points'];
                                ?>

                                pts

                            </p>

                            <div class="progress-bar-track">

                                <div
                                    class="progress-bar-fill"
                                    style="width: <?php echo $percent; ?>%;"
                                ></div>

                            </div>

                            <p class="progress-text">

                                <?php echo $qp['value']; ?>

                                /

                                <?php echo $qp['requirement']; ?>

                                <?php if ($is_done) { ?>

                                    - Complete

                                <?php } ?>

                            </p>

                        </div>

                    <?php } ?>

                <?php } ?>

            </div>


            <div class="section-box">

                <h2>

                    Plant Update History
                    (<?php echo count($logs); ?> updates)

                </h2>

                <?php if (count($logs) == 0) { ?>

                    <p>
                        This participant has not submitted
                        any plant updates yet.
                    </p>

                <?php } else { ?>

                    <?php

                    $count = count($logs);

                    foreach ($logs as $log) {

                    ?>

                        <div class="log-card">

                            <p>

                                <b>
                                    Update #<?php echo $count; ?>
                                </b>

                                -

                                Height:
                                <?php echo $log['height']; ?>
                                cm

                            </p>

                            <?php if ($log['image_path']) { ?>

                                <img
                                    src="images/<?php echo htmlspecialchars($log['image_path']); ?>"
                                    alt="Plant photo"
                                    class="log-image"
                                >

                            <?php } ?>

                            <?php if ($log['comments']) { ?>

                                <p class="log-comment">

                                    "<?php
                                    echo htmlspecialchars(
                                        $log['comments']
                                    );
                                    ?>"

                                </p>

                            <?php } ?>

                        </div>

                    <?php

                        $count--;

                    }

                    ?>

                <?php } ?>

            </div>

        </div>

    </main>

    <?php include("footer.php"); ?>

</body>

</html>

<?php ob_end_flush(); ?>