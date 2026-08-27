<?php
include_once("database.php");
if (!isset($_SESSION)) {
    session_start();
}

$profile_icon_path = '';
if (isset($_SESSION['user_id'])) {
    $profile_result = $conn->execute_query(
        "SELECT profile_icon_id, username FROM `users` WHERE user_id = ?",
        [$_SESSION['user_id']]
    );
    $user_data = $profile_result->fetch_assoc();
    if (!empty($user_data['profile_icon_id'])) {
        $profile_icon_path = get_image_path((int) $user_data['profile_icon_id']);
    }

    $username = $user_data['username'];
}
?>

<header class="header" id="header">
    <a class="header-brand" href="home.php">
        <img src="images/assets/logo.svg" alt="Treenity" class="logo">
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
                    <?php if ($profile_icon_path !== ''): ?>
                        <img class="profile-icon profile-icon-image" src="<?= htmlspecialchars($profile_icon_path, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <span class="profile-icon" aria-hidden="true">&#128100;</span>
                    <?php endif; ?>
                    <span class="profile-menu-label"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                </summary>
                <div class="profile-dropdown">
                    <a href="profile.php">Profile</a>
                    <a href="settings.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </details>
        <?php else: ?>
            <a class="login-link" href="login.php">Login</a>
        <?php endif; ?>
    </div>
</header>