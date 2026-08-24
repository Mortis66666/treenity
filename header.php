<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<header class="header" id="header">
    <a class="header-brand" href="home.php">
        <img src="images/assets/logo.jpg" alt="Treenity" class="logo">
        <span>Treenity</span>
    </a>

    <nav class="header-nav" aria-label="Main navigation">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a href="events.php">Events</a>
        <a href="leaderboard.php">Leaderboard</a>
        <a href="rewards.php">Rewards</a>
        <a href="about.php">About</a>
    </nav>

    <div class="header-account">
        <?php if (isset($_SESSION['user_id'])): ?>
            <details class="profile-menu">
                <summary aria-label="Open profile menu" title="Profile menu">
                    <span class="profile-icon" aria-hidden="true">&#128100;</span>
                    <span class="profile-menu-label">Account</span>
                </summary>
                <div class="profile-dropdown">
                    <a href="profile.php">Profile</a>
                    <a href="profile.php?tab=settings">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </details>
        <?php else: ?>
            <a class="login-link" href="login.php">Login</a>
        <?php endif; ?>
    </div>
</header>