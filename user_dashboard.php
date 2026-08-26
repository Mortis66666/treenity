<?php
include_once("database.php");
include_once("check_user.php");

$user_id = (int) $_SESSION['user_id'];

$future_events_result = $conn->execute_query(
    "SELECT e.event_id, e.name, e.start_time, e.end_time
     FROM events e
     INNER JOIN participants p ON p.event_id = e.event_id
     WHERE p.user_id = ? AND e.start_time > NOW()
     ORDER BY e.start_time ASC
     LIMIT 5",
    [$user_id]
);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/user_dashboard.css?v=7">
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content user-dashboard-page">
        <div class="page-title-bar">
            <h1>Dashboard</h1>
        </div>

        <nav class="dashboard-actions" aria-label="Dashboard shortcuts">
            <a href="achievements.php">Achievements</a>
            <a href="event_history.php">Your Event<br>History</a>
            <a href="inventory.php">Your Claimed<br>Rewards</a>
            <a href="plant_growth.php">Upload Plant<br>Growth Updates</a>
        </nav>

        <section class="dashboard-section" aria-labelledby="future-events-title">
                <h2 id="future-events-title">Your Future Events :</h2>
                <div class="dashboard-list">
                    <?php if ($future_events_result->num_rows === 0): ?>
                        <p class="dashboard-empty">No future events.</p>
                    <?php else: ?>
                        <?php while ($event = $future_events_result->fetch_assoc()): ?>
                            <a class="dashboard-row" href="event.php?event=<?= (int) $event['event_id'] ?>">
                                <span><?= htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <time datetime="<?= htmlspecialchars($event['start_time'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(date('d M', strtotime($event['start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                </time>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>