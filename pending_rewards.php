<?php
include_once("check_user.php");
checkUserRole(['ADMIN']);

$pending_result = $conn->execute_query(
    "SELECT inventory.inventory_id, inventory.user_id, inventory.item_id,
            inventory.amount, inventory.claimed_at, inventory.status,
            inventory.purchased_at, users.username,
            store.name, store.description, store.image_id, store.cost
     FROM inventory
     INNER JOIN users ON users.user_id = inventory.user_id
     INNER JOIN store ON store.item_id = inventory.item_id
     WHERE inventory.status = ?
     ORDER BY inventory.purchased_at ASC, inventory.inventory_id ASC",
    ['PENDING']
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Rewards</title>

    <link rel="stylesheet" href="styles/rewards.css">
    <link rel="stylesheet" href="styles/inventory.css">
    <link rel="stylesheet" href="styles/pending_rewards.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content rewards-page inventory-page pending-rewards-page">
        <div class="rewards-heading inventory-heading">
            <h1>Pending Rewards</h1>
            <p class="pending-rewards-summary">Items purchased by users awaiting approval.</p>
        </div>

        <?php if ($pending_result->num_rows === 0): ?>
            <p class="inventory-empty">There are no pending rewards.</p>
        <?php else: ?>
            <section class="rewards-grid inventory-grid" aria-label="Pending rewards">
                <?php while ($item = $pending_result->fetch_assoc()): ?>
                    <a class="reward-card inventory-card" href="inventory_item.php?item_id=<?= (int) $item['item_id'] ?>">
                        <img src="<?= htmlspecialchars(get_image_path($item['image_id']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="reward-card-info">
                            <div class="info-row info-name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="info-row info-desc"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="info-row">User: <?= htmlspecialchars($item['username'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="info-row">Quantity: <?= (int) $item['amount'] ?></div>
                            <div class="info-row">Purchased: <?= htmlspecialchars($item['purchased_at'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pending-label"><b><?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?></b></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>