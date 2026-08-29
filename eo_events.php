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

if (isset($_POST['delete_event_id'])) {
    $delete_event_id = (int)$_POST['delete_event_id'];

    $conn->execute_query(
        "DELETE FROM events WHERE event_id = ? AND organizer_id = ?",
        [$delete_event_id, $organizer_id]
    );

    header("Location: eo_events.php?deleted=1");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT
            e.event_id,
            e.name,
            e.start_time,
            e.end_time,
            COUNT(p.participant_id) AS participant_count
        FROM events e
        LEFT JOIN participants p ON e.event_id = p.event_id
        WHERE e.organizer_id = ?";

$params = [$organizer_id];

if ($search != '') {
    $sql .= " AND e.name LIKE ?";
    $params[] = "%" . $search . "%";
}

$sql .= " GROUP BY e.event_id, e.name, e.start_time, e.end_time
          ORDER BY e.start_time DESC";

$result = $conn->execute_query($sql, $params);
$all_events = $result->fetch_all(MYSQLI_ASSOC);

$events = [];
$draft_count = 0;
$now = date("Y-m-d H:i:s");

foreach ($all_events as $ev) {
    if (empty($ev['start_time']) || empty($ev['end_time'])) {
        $status = "Draft";
        $draft_count++;
    } elseif ($now < $ev['start_time']) {
        $status = "Upcoming";
    } elseif ($now > $ev['end_time']) {
        $status = "Ended";
    } else {
        $status = "Active";
    }

    if ($filter == 'all') {
        if ($status != "Draft") {
            $ev['status'] = $status;
            $events[] = $ev;
        }
    } elseif (strtolower($filter) == strtolower($status)) {
        $ev['status'] = $status;
        $events[] = $ev;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Organised</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .content {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .content h1 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 32px;
            color: #1b4332;
            margin: 0;
        }

        .content h3 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 17px;
            color: #1b4332;
            margin: 0 0 6px 0;
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
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #2d6a4f;
        }

        .btn-secondary {
            background: #f4f1eb;
            color: #1b4332;
            border: 1px solid #d8cfc0;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: #e8e2d7;
        }

        .success-box {
            background: #d8f0dc;
            border: 1px solid #9bd4a8;
            color: #1b4332;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #e0dacd;
            margin-bottom: 18px;
            overflow-x: auto;
        }

        .tab {
            padding: 8px 18px;
            font-size: 14px;
            color: #6b6355;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
        }

        .tab.active {
            color: #1b4332;
            border-bottom-color: #1b4332;
            font-weight: 600;
        }

        .tab:hover {
            color: #1b4332;
        }

        .search-form {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .search-form input[type="text"] {
            flex: 1;
            max-width: 300px;
            padding: 9px 12px;
            border: 1px solid #d8cfc0;
            border-radius: 6px;
            font-size: 14px;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .event-card {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 18px;
        }

        .event-card p {
            font-size: 13px;
            color: #6b6355;
            margin: 6px 0;
        }

        .status-tag {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }

        .status-active {
            background: #d8f0dc;
            color: #1b4332;
        }

        .status-ended {
            background: #ece7dc;
            color: #7a7264;
        }

        .status-upcoming {
            background: #dbe8f5;
            color: #1c4e80;
        }

        .status-draft {
            background: #fbeacb;
            color: #92620c;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .card-actions a {
            color: #1b4332;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .card-actions a:hover {
            text-decoration: underline;
        }

        .card-actions form {
            margin: 0;
        }

        .card-actions button {
            background: none;
            border: none;
            color: #a33;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        .card-actions button:hover {
            text-decoration: underline;
        }

        .empty-state {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 35px 20px;
            text-align: center;
            color: #6b6355;
        }

        .empty-state h3 {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .content {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 20px 15px;
            }

            .content h1 {
                font-size: 25px;
                line-height: 1.3;
            }

            .page-header {
                align-items: stretch;
                flex-direction: column;
            }

            .page-header .btn-primary {
                width: 100%;
                text-align: center;
            }

            .event-grid {
                grid-template-columns: 1fr !important;
                gap: 15px;
            }

            .event-card {
                width: 100%;
            }

            .search-form {
                flex-direction: column;
                width: 100%;
            }

            .search-form input[type="text"] {
                max-width: none;
                width: 100%;
            }

            .search-form button {
                width: 100%;
            }

            .card-actions {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }

            .card-actions a,
            .card-actions button {
                width: 100%;
                text-align: center;
                padding: 10px;
            }

            .card-actions form {
                width: 100%;
            }

            .tabs {
                width: 100%;
            }

            .tab {
                padding: 8px 12px;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px 12px;
            }

            .content h1 {
                font-size: 22px;
            }

            .event-card {
                padding: 15px;
            }
        }
    </style>

    <?php include("global.php"); ?>

</head>

<body>

<?php include("header.php"); ?>

<main class="content">

    <div class="page-header">

        <h1>Events Organised</h1>

        <a href="eo_create_event.php" class="btn-primary">
            Create Event
        </a>

    </div>

    <?php if (isset($_GET['created'])) { ?>
        <div class="success-box">
            Event created successfully!
        </div>
    <?php } ?>

    <?php if (isset($_GET['draft_saved'])) { ?>
        <div class="success-box">
            Draft saved. You can come back and finish it any time.
        </div>
    <?php } ?>

    <?php if (isset($_GET['deleted'])) { ?>
        <div class="success-box">
            Event deleted.
        </div>
    <?php } ?>

    <div class="tabs">

        <a
            href="eo_events.php?filter=all"
            class="tab <?php if ($filter == 'all') echo 'active'; ?>"
        >
            All
        </a>

        <a
            href="eo_events.php?filter=active"
            class="tab <?php if (strtolower($filter) == 'active') echo 'active'; ?>"
        >
            Active
        </a>

        <a
            href="eo_events.php?filter=upcoming"
            class="tab <?php if (strtolower($filter) == 'upcoming') echo 'active'; ?>"
        >
            Upcoming
        </a>

        <a
            href="eo_events.php?filter=ended"
            class="tab <?php if (strtolower($filter) == 'ended') echo 'active'; ?>"
        >
            Ended
        </a>

        <a
            href="eo_events.php?filter=draft"
            class="tab <?php if (strtolower($filter) == 'draft') echo 'active'; ?>"
        >
            Drafts
            <?php if ($draft_count > 0) { ?>
                (<?php echo $draft_count; ?>)
            <?php } ?>
        </a>

    </div>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search events..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <input
            type="hidden"
            name="filter"
            value="<?php echo htmlspecialchars($filter); ?>"
        >

        <button type="submit" class="btn-primary">
            Search
        </button>

        <?php if ($search != '') { ?>

            <a
                href="eo_events.php?filter=<?php echo urlencode($filter); ?>"
                class="btn-secondary"
            >
                Clear
            </a>

        <?php } ?>

    </form>

    <?php if (count($events) > 0) { ?>

        <div class="event-grid">

            <?php foreach ($events as $ev) { ?>

                <div class="event-card">

                    <span class="status-tag status-<?php echo strtolower(htmlspecialchars($ev['status'])); ?>">
                        <?php echo htmlspecialchars($ev['status']); ?>
                    </span>

                    <h3>
                        <?php echo htmlspecialchars($ev['name']); ?>
                    </h3>

                    <?php if (!empty($ev['start_time'])) { ?>

                        <p>
                            <strong>Start:</strong>
                            <?php
                            echo htmlspecialchars(
                                date(
                                    "d M Y, h:i A",
                                    strtotime($ev['start_time'])
                                )
                            );
                            ?>
                        </p>

                    <?php } else { ?>

                        <p>
                            <strong>Start:</strong>
                            Not set
                        </p>

                    <?php } ?>

                    <?php if (!empty($ev['end_time'])) { ?>

                        <p>
                            <strong>End:</strong>
                            <?php
                            echo htmlspecialchars(
                                date(
                                    "d M Y, h:i A",
                                    strtotime($ev['end_time'])
                                )
                            );
                            ?>
                        </p>

                    <?php } else { ?>

                        <p>
                            <strong>End:</strong>
                            Not set
                        </p>

                    <?php } ?>

                    <p>
                        <strong>Participants:</strong>
                        <?php echo (int)$ev['participant_count']; ?>
                    </p>

                    <div class="card-actions">

                        <a href="eo_event_details.php?event_id=<?php echo (int)$ev['event_id']; ?>">
                            View Details
                        </a>

                        <a href="eo_questcustomiser.php?event_id=<?php echo (int)$ev['event_id']; ?>">
                            Quest Customiser
                        </a>

                        <form
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this event?');"
                        >

                            <input
                                type="hidden"
                                name="delete_event_id"
                                value="<?php echo (int)$ev['event_id']; ?>"
                            >

                            <button type="submit">
                                Delete
                            </button>

                        </form>

                    </div>

                </div>

            <?php } ?>

        </div>

    <?php } else { ?>

        <div class="empty-state">

            <h3>No events found</h3>

            <?php if ($search != '') { ?>

                <p>
                    No events matched your search.
                </p>

            <?php } else { ?>

                <p>
                    You don't have any events in this category yet.
                </p>

            <?php } ?>

            <a href="eo_create_event.php" class="btn-primary">
                Create Event
            </a>

        </div>

    <?php } ?>

</main>

</body>
</html>