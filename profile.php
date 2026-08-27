<?php
session_start();

include("debug.php");
include_once("database.php");
require_once("components/event_card.php");


$target_user_id = $_GET["user"] ?? $_SESSION["user_id"] ?? null;

if (!isset($target_user_id)) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM `users` WHERE user_id = ?";
$result = $conn->execute_query($query, [$target_user_id]);

if ($result->num_rows === 0) {
    header("Location: not_found.php");
    exit();
}

$user = $result->fetch_assoc();
$role = $user["role"];
$profile_image_path = get_image_path($user["profile_icon_id"]);

$participated_events = [];
$event_count = 0;
$log_count = 0;
$highest_height = 0;
if ($role === "USER") {
    $events_result = $conn->execute_query(
        "SELECT e.event_id, e.name, e.description,
                e.start_time AS start_date, e.end_time AS end_date,
                i.path,
                COUNT(l.log_id) AS log_count,
                MAX(l.height) AS highest_height
         FROM participants p
         INNER JOIN events e ON e.event_id = p.event_id
         LEFT JOIN images i ON i.image_id = e.banner_id
         LEFT JOIN logs l ON l.participant_id = p.participant_id
         WHERE p.user_id = ?
         GROUP BY e.event_id, e.name, e.description, e.start_time, e.end_time, i.path
         ORDER BY e.start_time DESC, e.event_id DESC",
        [$target_user_id]
    );
    $participated_events = $events_result->fetch_all(MYSQLI_ASSOC);
    $event_count = count($participated_events);
    foreach ($participated_events as $event) {
        $log_count += (int) $event['log_count'];
        $highest_height = max($highest_height, (float) ($event['highest_height'] ?? 0));
    }
}
$completed_quests = [];
if ($role === "USER") {
    $completed_quests_result = $conn->execute_query(
        "SELECT DISTINCT q.name, q.quest_icon_id, e.name AS event_name
         FROM participants p
         INNER JOIN quest_progress qp ON qp.participant_id = p.participant_id AND qp.rewarded_at IS NOT NULL
         INNER JOIN quests q ON q.quest_id = qp.quest_id
         INNER JOIN events e ON e.event_id = q.event_id
         WHERE p.user_id = ?
         ORDER BY q.quest_id ASC",
        [$target_user_id]
    );
    $completed_quests = $completed_quests_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>

    <link rel="stylesheet" href="styles/profile.css?v=4">
    <?php include("global.php"); ?>

    <script src="scripts/profile.js" defer></script>
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <div class="profile-page">
            <section class="profile-header">
                <div class="profile-image">
                    <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile image">
                </div>
                <div class="profile-info">
                    <?php if ($role === "ADMIN" || $role === "ORGANIZER"): ?>
                        <p class="profile-role">This is an <?= htmlspecialchars(strtolower($role), ENT_QUOTES, 'UTF-8') ?> account</p>
                    <?php endif; ?>
                    <h1><?php echo htmlspecialchars($user["username"]); ?></h1>
                    <p><?php echo htmlspecialchars($user["bio"]); ?></p>
                </div>
            </section>

                    <?php if ($role === "USER"): ?>
                        <div class="profile-tabs">

                        <section class="profile-section">
                            <h2>Completed Quests</h2>
                            <?php if ($completed_quests): ?>
                                <div class="quest-list">
                                    <?php foreach ($completed_quests as $quest): ?>
                                        <div class="quest">
                                            <div class="quest-badge">
                                                <img src="<?= htmlspecialchars(get_image_path((int) $quest['quest_icon_id']), ENT_QUOTES, 'UTF-8') ?>" alt="" width="40" height="40">
                                            </div>
                                            <strong><?= htmlspecialchars($quest['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span class="quest-event"><?= htmlspecialchars($quest['event_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="profile-empty">No quests completed yet.</p>
                            <?php endif; ?>
                        </section>

                        <section class="profile-section">
                            <h2>Participated Events</h2>
                            <?php if ($participated_events): ?>
                                <div class="event-list">
                                    <?php foreach ($participated_events as $event): ?>
                                        <?php renderEventCard($event); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="profile-empty">No participated events yet.</p>
                            <?php endif; ?>
                        </section>

                        <section class="profile-section">
                            <h2>Statistics</h2>
                            <div class="stats">
                                <div class="stat"><strong><?php echo $event_count; ?></strong><span>Participated Events</span></div>
                                <div class="stat"><strong><?php echo number_format($highest_height, 1); ?></strong><span>Highest Height (cm)</span></div>
                                <div class="stat"><strong><?php echo $log_count; ?></strong><span>Trees Logged</span></div>
                                <div class="stat"><strong><?php echo number_format($user["total_points"]); ?></strong><span>Total Points</span></div>
                            </div>
                        </section>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>