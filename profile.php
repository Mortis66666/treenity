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


// Fake participated events data
$participated_events = [
    [
        "event_id" => 1,
        "name" => "Tree Planting Day",
        "description" => "A hands-on community planting day restoring local green space.",
        "start_date" => "2026-06-12",
        "end_date" => "2026-06-12"
    ],
    [
        "event_id" => 2,
        "name" => "Beach Cleanup",
        "description" => "Working together to keep the coastline clean and welcoming.",
        "start_date" => "2026-05-28",
        "end_date" => "2026-05-28"
    ],
    [
        "event_id" => 3,
        "name" => "Community Garden",
        "description" => "Growing food and connection in the neighborhood garden.",
        "start_date" => "2026-05-14",
        "end_date" => "2026-05-14"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>

    <link rel="stylesheet" href="styles/profile.css">
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
                    <h1><?php echo htmlspecialchars($user["username"]); ?></h1>
                    <p><?php echo htmlspecialchars($role === "USER" ? $user["bio"] : "This is an $role account"); ?></p>
                </div>
            </section>

            <?php if ($role === "USER"): ?>
                <div class="profile-tabs" data-tabs>
                    <div class="tab-list" role="tablist" aria-label="Profile views">
                        <button class="tab-button is-active" id="profile-tab" type="button" role="tab" aria-selected="true" aria-controls="profile-panel">Profile</button>
                        <button class="tab-button" id="logs-tab" type="button" role="tab" aria-selected="false" aria-controls="logs-panel" tabindex="-1">Logs</button>
                    </div>

                    <section class="tab-panel is-active" id="profile-panel" role="tabpanel" aria-labelledby="profile-tab">

                        <section class="profile-section">
                            <h2>Achievements</h2>
                            <div class="achievement-list">
                                <div class="achievement">
                                    <div class="badge">★</div><span>First Event</span>
                                </div>
                                <div class="achievement">
                                    <div class="badge">★</div><span>Tree Planter</span>
                                </div>
                                <div class="achievement">
                                    <div class="badge">★</div><span>7 Day Streak</span>
                                </div>
                                <div class="achievement">
                                    <div class="badge">★</div><span>Community Helper</span>
                                </div>
                            </div>
                        </section>

                        <section class="profile-section">
                            <h2>Participated Events</h2>
                            <div class="event-list">
                                <?php foreach ($participated_events as $event): ?>
                                    <?php renderEventCard($event); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="profile-section">
                            <h2>Statistics</h2>
                            <div class="stats">
                                <div class="stat"><strong>24</strong><span>Participated Events</span></div>
                                <div class="stat"><strong>12</strong><span>Highest Daily Streak</span></div>
                                <div class="stat"><strong>156</strong><span>Trees Logged</span></div>
                                <div class="stat"><strong><?php echo number_format($user["total_points"]); ?></strong><span>Total Points</span></div>
                            </div>
                        </section>
                    </section>

                    <section class="tab-panel logs-panel" id="logs-panel" role="tabpanel" aria-labelledby="logs-tab" hidden>
                        <h2>Logs</h2>
                        <p>No activity logs are available yet.</p>
                    </section>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>