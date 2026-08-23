<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<header class="header" id="header">
    <a href="home.php"><img src="images/logo.png" alt="Logo" class="logo"></a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php"><button class="dashboard-button">Dashboard</button></a>
    <?php endif; ?>
    <a href="events.php"><button class="events-button">Events</button></a>
    <a href="leaderboard.php"><button class="leaderboard-button">Leaderboard</button></a>
    <a href="rewards.php"><button class="rewards-button">Rewards</button></a>
    <a href="about.php"><button class="about-button">About</button></a>
    <a href="profile.php"><button class="profile-button">Profile</button></a>
    <a href="logout.php"><button class="logout-button">Logout</button></a>
    <a href="login.php"><button class="login-button">Login</button></a>

    <!-- TODO: User icon + drop down for logout -->
</header>