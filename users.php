<?php
session_start();
include("database.php");
include("check_user.php");
include("pagination.php");

$query_total = "SELECT COUNT(*) as total FROM `users`";
$total_result = $conn->execute_query($query_total);
$total_rows = $total_result->fetch_assoc()['total'];

$limit = 10;
$total_pages = ceil($total_rows / $limit);
$page = max(1, min((int) ($_GET['page'] ?? 1), $total_pages));
$offset = ($page - 1) * $limit;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>

    <link rel="stylesheet" href="styles/users.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <div class="page-title-bar">
            <h1>Users</h1>
        </div>

        <div class="users-list">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>TP number</th>
                        <th>Role</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $query = "SELECT * FROM users";
                    $result = $conn->execute_query($query);
                    $rank = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $rank . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['tp_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
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