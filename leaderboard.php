<?php
include("database.php");

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$query = "SELECT username, total_points FROM `users` ORDER BY total_points DESC LIMIT ?, ?";
$result = $conn->execute_query($query, [$offset, $limit]);

$query_total = "SELECT COUNT(*) as total FROM `users`";
$total_result = $conn->execute_query($query_total);

$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
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

        <div class="leaderboard">

            <h1>Leaderboard</h1>

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

        <div class="pagination">

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($page > 1) {
                echo '<button onclick="window.location=\'?page=1\'">1</button>';
                if ($start > 2) {
                    echo '<span class="ellipsis">...</span>';
                }
            }

            for ($i = $start; $i <= $end; $i++) {
                $active = ($i == $page) ? ' class="active"' : '';
                echo '<button' . $active . ' onclick="window.location=\'?page=' . $i . '\'">' . $i . '</button>';
            }

            if ($page < $total_pages) {
                if ($end < $total_pages - 1) {
                    echo '<span class="ellipsis">...</span>';
                }
                echo '<button onclick="window.location=\'?page=' . $total_pages . '\'">' . $total_pages . '</button>';
            }
            ?>

        </div>

    </main>


    <?php include("footer.php"); ?>
</body>

</html>