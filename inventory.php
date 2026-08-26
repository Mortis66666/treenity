<?php
include_once("database.php");

if (!isset($_SESSION)) {
    session_start();
}

$target_user_id = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$target_user_id = $target_user_id ?: ($_SESSION['user_id'] ?? null);

if (!$target_user_id) {
    header("Location: login.php");
    exit();
}

$user_result = $conn->execute_query(
    "SELECT user_id, username FROM users WHERE user_id = ?",
    [$target_user_id]
);
$user = $user_result->fetch_assoc();

if (!$user) {
    header("Location: not_found.php");
    exit();
}

$inventory_result = $conn->execute_query(
    "SELECT inventory.item_id, inventory.amount, inventory.claimed_at,
            store.name, store.description, store.image_id, store.cost
     FROM inventory
     INNER JOIN store ON store.item_id = inventory.item_id
     WHERE inventory.user_id = ?
     ORDER BY inventory.claimed_at IS NOT NULL ASC, inventory.claimed_at DESC, inventory.item_id DESC",
    [$target_user_id]
);

$pending_items = [];
$claimed_items = [];
$all_items = [];
while ($item = $inventory_result->fetch_assoc()) {
    if ($item['claimed_at'] === null) {
        $pending_items[] = $item;
    } else {
        $claimed_items[] = $item;
    }
    $all_items[] = $item;
}

function showInventoryItems(array $items): void
{
    if (!$items) {
        echo '<p class="inventory-empty">No redeemed rewards in this section.</p>';
        return;
    }

    echo '<section class="rewards-grid inventory-grid" aria-label="Redeemed rewards">';
    foreach ($items as $item) {
        $image_path = get_image_path($item['image_id']);
        $item_url = 'inventory_item.php?item_id=' . (int) $item['item_id'];
        echo '<a class="reward-card inventory-card" href="' . htmlspecialchars($item_url, ENT_QUOTES, 'UTF-8') . '">';
        echo '<img src="' . htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">';
        echo '<div class="reward-card-info">';
        echo '<div class="info-row info-name">' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="info-row info-desc">' . htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="info-row">Quantity: ' . (int) $item['amount'] . '</div>';
        echo '<div class="info-row">Cost: ' . (int) $item['cost'] . ' points each</div>';
        if ($item['claimed_at'] !== null) {
            echo '<div class="claimed-date" style="color: #39706e;">Claimed: ' . htmlspecialchars($item['claimed_at'], ENT_QUOTES, 'UTF-8') . '</div>';
        } else {
            echo '<div class="pending-label" style="color: #39706e;"><b>Pending</b></div>';
        }
        echo '</div></a>';
    }
    echo '</section>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>

    <link rel="stylesheet" href="styles/rewards.css?v=2">
    <link rel="stylesheet" href="styles/inventory.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content rewards-page inventory-page">
        <div class="rewards-heading inventory-heading">
            <h1>Your Inventory</h1>
        </div>

        <div class="inventory-tabs" data-tabs>
            <div class="tab-list" role="tablist" aria-label="Redeemed reward status">
                <button class="tab-button is-active" id="all-tab" type="button" role="tab" aria-selected="true" aria-controls="all-panel">All</button>
                <button class="tab-button" id="pending-tab" type="button" role="tab" aria-selected="false" aria-controls="pending-panel" tabindex="-1">Pending</button>
                <button class="tab-button" id="claimed-tab" type="button" role="tab" aria-selected="false" aria-controls="claimed-panel" tabindex="-1">Claimed</button>
            </div>
            <section class="tab-panel is-active" id="all-panel" role="tabpanel" aria-labelledby="all-tab">
                <?php showInventoryItems($all_items); ?>
            </section>
            <section class="tab-panel" id="pending-panel" role="tabpanel" aria-labelledby="pending-tab" hidden>
                <?php showInventoryItems($pending_items); ?>
            </section>
            <section class="tab-panel" id="claimed-panel" role="tabpanel" aria-labelledby="claimed-tab" hidden>
                <?php showInventoryItems($claimed_items); ?>
            </section>
        </div>

    </main>

    <?php include("footer.php"); ?>
    <script src="scripts/profile.js"></script>
</body>

</html>