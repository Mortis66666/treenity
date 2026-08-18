<?php
include("database.php");

$query = "SELECT * FROM `events` as e LEFT JOIN images as i ON e.banner_id = i.image_id";
$result = $conn->execute_query($query);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<p>" . $row["name"] . "</p>";
            echo "<image src='images/" . $row["path"] . "'>";
        }
        ?>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>