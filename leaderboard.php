<?php
session_start();

include_once("database.php");
include("pagination.php");

$limit = 10;

$query_total = "SELECT COUNT(*) as total FROM `users`";
$total_result = $conn->execute_query($query_total);

$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
$page = max(1, min((int) ($_GET['page'] ?? 1), max(1, $total_pages)));
$offset = ($page - 1) * $limit;

$query =
    "SELECT
        user_id, username, total_points,
        RANK() OVER (ORDER BY total_points DESC) AS rank
    FROM
        `users`
    WHERE
        role='USER'
    ORDER BY
        total_points DESC LIMIT ?, ?";
$result = $conn->execute_query($query, [$offset, $limit]);

$user_logged_in = false;
$user_row = null;

if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "USER") {
    $user_logged_in = true;
    $user_query =
        "WITH cte AS (
            SELECT
                user_id, username, total_points,
                RANK() OVER (ORDER BY total_points DESC) AS rank
            FROM
                `users`
            WHERE
                role='USER'
        )
        SELECT *
        FROM cte
        WHERE user_id = ?";

    $user_result = $conn->execute_query($user_query, [$_SESSION["user_id"]]);
    $user_row = $user_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard | Treenity</title>

    <link rel="stylesheet" href="styles/leaderboard.css">

    <?php include("global.php"); ?>
</head>

<body>

    <?php include("header.php"); ?>

    <main class="content leaderboard-page">
        <section class="heading">
            <div>
                <!-- <p class="eyebrow">A little friendly momentum</p> -->
                <h1>Leaderboard</h1>
                <p>Find out the top contributors to Treenity's mission.</p>
            </div>
        </section>
        <!-- <section class="leaderboard-heading" aria-labelledby="leaderboard-title">
            <div>
                <p class="eyebrow">A little friendly momentum</p>
                <h1 id="leaderboard-title">Leaderboard</h1>
                <p class="leaderboard-intro">See the people helping Treenity put down roots, one action at a time.</p>
            </div>
            <span class="heading-mark" aria-hidden="true">✳</span>
        </section> -->

        <section class="leaderboard" aria-label="Community rankings">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Username</th>
                            <th scope="col">Points</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($user_logged_in && $user_row): ?>
                            <tr class="user-row self-row">
                                <td class="rank-cell"><span><?php echo htmlspecialchars($user_row['rank']); ?></span></td>
                                <td class="username-cell"><a class="username-link" href="profile.php?user=<?php echo urlencode($user_row['user_id']); ?>"><?php echo htmlspecialchars($user_row['username']); ?></a></td>
                                <td class="points-cell"><?php echo htmlspecialchars($user_row['total_points']); ?> <small>pts</small></td>
                            </tr>
                        <?php endif; ?>

                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td class='rank-cell'><span>" . htmlspecialchars($row['rank']) . "</span></td>";
                            echo "<td class='username-cell'><a class='username-link' href='profile.php?user=" . urlencode($row['user_id']) . "'>" . htmlspecialchars($row['username']) . "</a></td>";
                            echo "<td class='points-cell'>" . htmlspecialchars($row['total_points']) . " <small>pts</small></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php renderPagination($page, $total_pages); ?>


    </main>


    <?php include("footer.php"); ?>
</body>

</html>