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

if (isset($_POST['delete_event_id'])) {
    $conn->execute_query(
        "DELETE FROM events WHERE event_id = ? AND organizer_id = ?",
        [$_POST['delete_event_id'], $organizer_id]
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

$params = array($organizer_id);

if ($search != '') {
    $sql .= " AND e.name LIKE ?";
    $params[] = "%" . $search . "%";
}

$sql .= " GROUP BY e.event_id, e.name, e.start_time, e.end_time
        ORDER BY e.start_time DESC";

$result = $conn->execute_query($sql, $params);
$all_events = $result->fetch_all(MYSQLI_ASSOC);

$events = array();
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
        }

        .tab {
            padding: 8px 18px;
            font-size: 14px;
            color: #6b6355;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
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

        @media (max-width: 600px) {
            .event-grid {
                grid-template-columns: 1fr;
            }

            .search-form input[type="text"] {
                max-width: none;
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

            <a href="?filter=all"
            class="tab <?php if ($filter == 'all') echo 'active'; ?>">
                All
            </a>

            <a href="?filter=active"
            class="tab <?php if ($filter == 'active') echo 'active'; ?>">
                Active
            </a>

            <a href="?filter=upcoming"
            class="tab <?php if ($filter == 'upcoming') echo 'active'; ?>">
                Upcoming
            </a>

            <a href="?filter=ended"
            class="tab <?php if ($filter == 'ended') echo 'active'; ?>">
                Ended
            </a>

            <a href="?filter=draft"
            class="tab <?php if ($filter == 'draft') echo 'active'; ?>">
                Drafts

                <?php if ($draft_count > 0) { ?>
                    (<?php echo $draft_count; ?>)
                <?php } ?>

            </a>

        </div>

        <form method="GET"
            action="eo_events.php"
            class="search-form">

            <input type="hidden"
                name="filter"
                value="<?php echo htmlspecialchars($filter); ?>">

            <input type="text"
                name="search"
                placeholder="Search events..."
                value="<?php echo htmlspecialchars($search); ?>">

            <button type="submit"
                    class="btn-primary">
                Search
            </button>

        </form>

        <?php if (count($events) == 0) { ?>

            <p>No events found.</p>

        <?php } else { ?>

            <div class="event-grid">

                <?php foreach ($events as $ev) { ?>

                    <div class="event-card">

                        <h3>
                            <?php echo htmlspecialchars($ev['name']); ?>
                        </h3>

                        <span class="status-tag status-<?php echo strtolower($ev['status']); ?>">
                            <?php echo $ev['status']; ?>
                        </span>

                        <?php if ($ev['status'] == 'Draft') { ?>

                            <p>
                                Not published yet.
                                Finish the details to make it live.
                            </p>

                            <?php if ($ev['start_time'] && $ev['end_time']) { ?>

                                <p>
                                    <?php
                                    echo date(
                                        "d M Y, g:ia",
                                        strtotime($ev['start_time'])
                                    );
                                    ?>

                                    to

                                    <?php
                                    echo date(
                                        "d M Y, g:ia",
                                        strtotime($ev['end_time'])
                                    );
                                    ?>
                                </p>

                            <?php } ?>

                            <div class="card-actions">

                                <a href="eo_create_event.php?event_id=<?php echo $ev['event_id']; ?>">
                                    Continue Editing
                                </a>

                                <form method="POST"
                                    action="eo_events.php"
                                    onsubmit="return confirm('Delete this draft?');">

                                    <input type="hidden"
                                        name="delete_event_id"
                                        value="<?php echo $ev['event_id']; ?>">

                                    <button type="submit">
                                        Delete
                                    </button>

                                </form>

                            </div>

                        <?php } else { ?>

                            <p>
                                <?php
                                echo date(
                                    "d M Y, g:ia",
                                    strtotime($ev['start_time'])
                                );
                                ?>

                                to

                                <?php
                                echo date(
                                    "d M Y, g:ia",
                                    strtotime($ev['end_time'])
                                );
                                ?>
                            </p>

                            <p>
                                <?php echo $ev['participant_count']; ?>
                                participants
                            </p>

                            <div class="card-actions">

                                <a href="eo_participants.php?event_id=<?php echo $ev['event_id']; ?>">
                                    Participants
                                </a>

                                <a href="eo_questcustomiser.php?event_id=<?php echo $ev['event_id']; ?>">
                                    Quests
                                </a>

                                <form method="POST"
                                    action="eo_events.php"
                                    onsubmit="return confirm('Delete this event?');">

                                    <input type="hidden"
                                        name="delete_event_id"
                                        value="<?php echo $ev['event_id']; ?>">

                                    <button type="submit">
                                        Delete
                                    </button>

                                </form>

                            </div>

                        <?php } ?>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </main>

    <?php include("footer.php"); ?>

</body>

</html>

<?php ob_end_flush(); ?>