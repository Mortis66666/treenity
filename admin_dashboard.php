<?php
include_once("database.php");
include_once("check_user.php");
include("pagination.php");

$proposal_count_result = $conn->execute_query(
    "SELECT COUNT(*) AS total
     FROM events e
     INNER JOIN users u ON u.user_id = e.organizer_id
     WHERE e.is_published = 0 AND u.role = 'USER'"
);
$proposal_count = (int) $proposal_count_result->fetch_assoc()['total'];
$proposal_limit = 10;
$proposal_total_pages = max(1, (int) ceil($proposal_count / $proposal_limit));
$proposal_page = max(1, min((int) ($_GET['page'] ?? 1), $proposal_total_pages));
$proposal_offset = ($proposal_page - 1) * $proposal_limit;

$proposal_result = $conn->execute_query(
    "SELECT e.event_id, e.name, u.username AS proposer_name
     FROM events e
     INNER JOIN users u ON u.user_id = e.organizer_id
     WHERE e.is_published = 0 AND u.role = 'USER'
     ORDER BY e.event_id DESC
     LIMIT ?, ?",
    [$proposal_offset, $proposal_limit]
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="styles/users.css">
    <link rel="stylesheet" href="styles/admin_dashboard.css">
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
            <a href="pending_rewards.php">View pending rewards</a>
        </nav>

        <section class="pending-event-proposals" aria-labelledby="pending-event-proposals-title">
            <h2 id="pending-event-proposals-title">Event proposals waiting for approval</h2>

            <?php if ($proposal_count === 0): ?>
                <p>No event proposals are waiting for approval.</p>
            <?php else: ?>
                <div class="users-list pending-proposals-list">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Event proposal</th>
                                <th scope="col">Proposed by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($proposal = $proposal_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="event_approval.php?event_id=<?= (int) $proposal['event_id'] ?>">
                                            <?= htmlspecialchars($proposal['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($proposal['proposer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($proposal_page, $proposal_total_pages); ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>