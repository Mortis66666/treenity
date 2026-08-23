<?php
session_start();

include("debug.php");


$target_user_id = $_GET["user"] ?? $_SESSION["user_id"];

if (!isset($target_user_id)) {
    header("Location: login.php");
    exit();
}



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
                <div class="profile-image"><span>Profile Image</span></div>
                <div class="profile-info">
                    <h1>Username</h1>
                    <p>This is the user's short biography. They enjoy participating in environmental events and helping the community.</p>
                </div>
            </section>

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
                            <div class="event">
                                <h3>Tree Planting Day</h3>
                                <p>12 June 2026</p>
                            </div>
                            <div class="event">
                                <h3>Beach Cleanup</h3>
                                <p>28 May 2026</p>
                            </div>
                            <div class="event">
                                <h3>Community Garden</h3>
                                <p>14 May 2026</p>
                            </div>
                        </div>
                    </section>

                    <section class="profile-section">
                        <h2>Statistics</h2>
                        <div class="stats">
                            <div class="stat"><strong>24</strong><span>Participated Events</span></div>
                            <div class="stat"><strong>12</strong><span>Highest Daily Streak</span></div>
                            <div class="stat"><strong>156</strong><span>Trees Logged</span></div>
                            <div class="stat"><strong>2,450</strong><span>Total Points</span></div>
                        </div>
                    </section>
                </section>

                <section class="tab-panel logs-panel" id="logs-panel" role="tabpanel" aria-labelledby="logs-tab" hidden>
                    <h2>Logs</h2>
                    <p>No activity logs are available yet.</p>
                </section>
            </div>
        </div>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>