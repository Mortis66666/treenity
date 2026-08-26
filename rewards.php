<?php
include_once("database.php");

if (!isset($_SESSION)) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$is_user = $is_logged_in && ($_SESSION['role'] ?? '') === 'USER';
$csrf_token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_user) {
        $message = 'Please log in before redeeming a reward.';
        $message_class = 'error-message';
    } elseif (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $message = 'Your session has expired. Please refresh the page and try again.';
        $message_class = 'error-message';
    } else {
        $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$item_id || !$quantity) {
            $message = 'Choose a valid reward quantity.';
            $message_class = 'error-message';
        } else {
            try {
                $conn->begin_transaction();

                $item_result = $conn->execute_query(
                    "SELECT name, stock_left, cost FROM store WHERE item_id = ? FOR UPDATE",
                    [$item_id]
                );
                $user_result = $conn->execute_query(
                    "SELECT total_points FROM users WHERE user_id = ? FOR UPDATE",
                    [$_SESSION['user_id']]
                );

                $item = $item_result->fetch_assoc();
                $user = $user_result->fetch_assoc();
                $total_cost = $item ? (int) $item['cost'] * $quantity : 0;

                if (!$item || !$user) {
                    throw new RuntimeException('The reward or account could not be found.');
                }
                if ($quantity > (int) $item['stock_left']) {
                    throw new RuntimeException('There is not enough stock for that quantity.');
                }
                if ($total_cost > (int) $user['total_points']) {
                    throw new RuntimeException('You do not have enough points for that quantity.');
                }

                $conn->execute_query(
                    "UPDATE store SET stock_left = stock_left - ? WHERE item_id = ?",
                    [$quantity, $item_id]
                );
                $conn->execute_query(
                    "UPDATE users SET total_points = total_points - ? WHERE user_id = ?",
                    [$total_cost, $_SESSION['user_id']]
                );
                $conn->execute_query(
                    "INSERT INTO inventory (user_id, item_id, amount, purchased_at) VALUES (?, ?, ?, NOW())",
                    [$_SESSION['user_id'], $item_id, $quantity]
                );
                $conn->commit();

                $message = "Redeemed {$quantity} x " . $item['name'] . " for {$total_cost} points.";
                $message_class = 'success-message';
            } catch (Throwable $exception) {
                $conn->rollback();
                $message = $exception->getMessage();
                $message_class = 'error-message';
            }
        }
    }
}

$sql = "SELECT item_id, name, description, stock_left, image_id, cost FROM store ORDER BY name ASC";
$result = $conn->execute_query($sql);

$user_points = null;
if ($is_user) {
    $user_result = $conn->execute_query("SELECT total_points FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
    $user = $user_result->fetch_assoc();
    $user_points = $user ? (int) $user['total_points'] : 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/rewards.css?v=9">
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content rewards-page">

        <div class="rewards-heading">
            <h1>Rewards</h1>
            <?php if ($is_user) : ?>
                <p class="points-balance">Your point balance : <strong><?php echo htmlspecialchars($user_points); ?> points</strong></p>
            <?php endif; ?>
        </div>

        <?php if ($message !== '') : ?>
            <p class="<?php echo $message_class; ?>" role="<?php echo $message_class === 'error-message' ? 'alert' : 'status'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($result->num_rows > 0) : ?>
            <section class="rewards-grid" aria-label="Available rewards">
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <?php
                        $image_path = get_image_path($row['image_id']);
                        $stock_class = ($row['stock_left'] <= 10) ? "info-row stock-low" : "info-row";
                        ?>
                        <div class="reward-card">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="reward-card-info">
                                <div class="info-row info-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="info-row info-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                                <div class="info-row">Cost: <?php echo htmlspecialchars($row['cost']); ?> Points</div>
                                <div class="<?php echo $stock_class; ?>">Stock left: <?php echo htmlspecialchars($row['stock_left']); ?></div>
                                <?php if ($is_user && (int) $row['stock_left'] > 0) : ?>
                                    <form class="redeem-form" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($row['item_id']); ?>">
                                        <div class="quantity-control">
                                            <label for="quantity-<?php echo htmlspecialchars($row['item_id']); ?>">Quantity : </label>
                                            <button type="button" class="quantity-button" data-action="decrease" aria-label="Decrease quantity">-</button>
                                            <input id="quantity-<?php echo htmlspecialchars($row['item_id']); ?>" name="quantity" type="number" min="1" max="<?php echo htmlspecialchars($row['stock_left']); ?>" value="1" inputmode="numeric">
                                            <button type="button" class="quantity-button" data-action="increase" aria-label="Increase quantity">+</button>
                                        </div>
                                        <button class="redeem-button" type="submit">Redeem</button>
                                    </form>
                                <?php elseif ($is_user) : ?>
                                    <p class="sold-out">Sold out</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
            </section>
        <?php else : ?>
            <p class="rewards-empty">No rewards available right now.</p>
        <?php endif; ?>

    </main>

    <?php include("footer.php"); ?>
    <script src="scripts/rewards.js"></script>
</body>

</html>