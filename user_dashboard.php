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
    <title>User Dashboard</title>

    <?php include("global.php"); ?>
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <div class="page-title-bar">
            <h1>User Dashboard</h1>
        </div>

        <a href="achievements.php">Achievements</a>
        <a href="event_history.php">Your Event History</a>
        <a href="inventory.php">Your Inventory</a>
        <a href="plant_growth.php">Upload Plant Growth Updates</a>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>