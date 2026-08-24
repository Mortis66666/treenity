<?php
include_once("database.php");
include("pagination.php");

$limit = 10;

$query_total = "SELECT COUNT(*) as total FROM `users`";
$total_result = $conn->execute_query($query_total);

$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
$page = max(1, min((int) ($_GET['page'] ?? 1), max(1, $total_pages)));
$offset = ($page - 1) * $limit;

$query = "SELECT username, total_points FROM `users` ORDER BY total_points DESC LIMIT ?, ?";
$result = $conn->execute_query($query, [$offset, $limit]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="stylesheet" href="styles/leaderboard.css">

    <?php include("global.php"); ?>
</head>

<body>

    <?php include("header.php"); ?>

    <main class="content">

        <div class="page-title-bar">
            <h1>Leaderboard</h1>
        </div>

        <div class="leaderboard">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Points</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $rank = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $rank . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['total_points']) . "</td>";
                        echo "</tr>";
                        $rank++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php renderPagination($page, $total_pages); ?>


    </main>


    <?php include("footer.php"); ?>
</body>

</html>