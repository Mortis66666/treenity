<?php
session_start();

include("database.php");
include("check_user.php");
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

        <a href="users.php">users</a>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>