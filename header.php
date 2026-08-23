<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<header class="header" id="header">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php"><button class="dashboard-button">Dashboard</button></a>
    <?php endif; ?>
    <a href="events.php"><button class="events-button">Events</button></a>
    <a href="leaderboard.php"><button class="leaderboard-button">Leaderboard</button></a>
    <a href="rewards.php"><button class="rewards-button">Rewards</button></a>
    <a href="about.php"><button class="about-button">About</button></a>
    <a href="logout.php"><button class="logout-button">Logout</button></a>
</header>