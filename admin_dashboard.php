<?php
include_once("database.php");
include_once("check_user.php");
include("pagination.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <?php include("global.php"); ?>
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <div class="page-title-bar">
            <h1>Admin Dashboard</h1>
        </div>

        <nav class="dashboard-actions" aria-label="Dashboard shortcuts">
            <a href="analytics.php">View analytics</a>
            <a href="users.php">Manage users</a>
            <a href="rewards.php">Manage stock</a>
            <a href="new_item.php">Create item</a>
            <a href="event_approval.php">Approve events</a>
            <a href="pending_rewards.php">View pending rewards</a>
        </nav>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>