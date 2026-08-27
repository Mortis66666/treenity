<?php
include_once("database.php");

if (!isset($_SESSION)) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$is_user = $is_logged_in && ($_SESSION['role'] ?? '') === 'USER';
$is_admin = $is_logged_in && ($_SESSION['role'] ?? '') === 'ADMIN';
$csrf_token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$flash = $_SESSION['rewards_flash'] ?? null;
unset($_SESSION['rewards_flash']);
$message = $flash['message'] ?? '';
$message_class = $flash['class'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_user && !$is_admin) {
        $message = 'Please log in before managing rewards.';
        $message_class = 'error-message';
    } elseif (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $message = 'Your session has expired. Please refresh the page and try again.';
        $message_class = 'error-message';
    } else {
        $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $action = $_POST['action'] ?? 'redeem';

        if (!$item_id || !$quantity) {
            $message = $action === 'add_stock' ? 'Choose a valid stock quantity.' : 'Choose a valid reward quantity.';
            $message_class = 'error-message';
        } elseif ($action === 'add_stock' && !$is_admin) {
            $message = 'You are not allowed to add stock.';
            $message_class = 'error-message';
        } elseif ($action !== 'add_stock' && !$is_user) {
            $message = 'Only users can redeem rewards.';
            $message_class = 'error-message';
        } else {
            try {
                $conn->begin_transaction();

                $item_result = $conn->execute_query(
                    "SELECT name, stock_left, cost FROM store WHERE item_id = ? FOR UPDATE",
                    [$item_id]
                );
                $user_result = $is_user ? $conn->execute_query(
                    "SELECT current_points FROM users WHERE user_id = ? FOR UPDATE",
                    [$_SESSION['user_id']]
                ) : null;

                $item = $item_result->fetch_assoc();
                $user = $user_result ? $user_result->fetch_assoc() : null;
                $total_cost = $item ? (int) $item['cost'] * $quantity : 0;

                if (!$item || ($is_user && !$user)) {
                    throw new RuntimeException('The reward or account could not be found.');
                }
                if ($action === 'add_stock') {
                    $conn->execute_query(
                        "UPDATE store SET stock_left = stock_left + ? WHERE item_id = ?",
                        [$quantity, $item_id]
                    );
                    $message = "Added {$quantity} stock to " . $item['name'] . ".";
                } else {
                    if ($quantity > (int) $item['stock_left']) {
                        throw new RuntimeException('There is not enough stock for that quantity.');
                    }
                    if ($total_cost > (int) $user['current_points']) {
                        throw new RuntimeException('You do not have enough points for that quantity.');
                    }

                    $conn->execute_query(
                        "UPDATE store SET stock_left = stock_left - ? WHERE item_id = ?",
                        [$quantity, $item_id]
                    );
                    $conn->execute_query(
                        "UPDATE users SET current_points = current_points - ? WHERE user_id = ?",
                        [$total_cost, $_SESSION['user_id']]
                    );
                    $conn->execute_query(
                        "INSERT INTO inventory (user_id, item_id, amount, purchased_at, STATUS) VALUES (?, ?, ?, NOW(), 'PENDING')",
                        [$_SESSION['user_id'], $item_id, $quantity]
                    );
                    $message = "Redeemed {$quantity} x " . $item['name'] . " for {$total_cost} points.";
                }
                $conn->commit();

                $message_class = 'success-message';
            } catch (Throwable $exception) {
                $conn->rollback();
                $message = $exception->getMessage();
                $message_class = 'error-message';
            }
        }
    }

    $_SESSION['rewards_flash'] = [
        'message' => $message,
        'class' => $message_class
    ];
    header('Location: rewards.php');
    exit();
}

$sql = "SELECT item_id, name, description, stock_left, image_id, cost FROM store ORDER BY name ASC";
$result = $conn->execute_query($sql);

$user_points = null;
if ($is_user) {
    $user_result = $conn->execute_query("SELECT current_points FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
    $user = $user_result->fetch_assoc();
    $user_points = $user ? (int) $user['current_points'] : 0;
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
            <div class="rewards-title-row">
                <h1>Rewards</h1>
                <?php if ($is_admin) : ?>
                    <a class="add-reward-button" href="new_item.php">Add new reward</a>
                <?php endif; ?>
            </div>
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
                            <?php if ($is_user || $is_admin) : ?>
                                <?php if ($is_admin || (int) $row['stock_left'] > 0) : ?>
                                    <form class="redeem-form" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($row['item_id']); ?>">
                                        <?php if ($is_admin) : ?>
                                            <input type="hidden" name="action" value="add_stock">
                                        <?php endif; ?>
                                        <div class="quantity-control">
                                            <label for="quantity-<?php echo htmlspecialchars($row['item_id']); ?>"><?php echo $is_admin ? 'Add stock :' : 'Quantity :'; ?> </label>
                                            <button type="button" class="quantity-button" data-action="decrease" aria-label="Decrease quantity">-</button>
                                            <input id="quantity-<?php echo htmlspecialchars($row['item_id']); ?>" name="quantity" type="number" min="1" <?php echo $is_user ? 'max="' . htmlspecialchars($row['stock_left']) . '"' : ''; ?> value="1" inputmode="numeric">
                                            <button type="button" class="quantity-button" data-action="increase" aria-label="Increase quantity">+</button>
                                        </div>
                                        <button class="redeem-button<?php echo $is_admin ? ' add-stock-button' : ''; ?>" type="submit"><?php echo $is_admin ? 'Add stock' : 'Redeem'; ?></button>
                                    </form>
                                <?php else : ?>
                                    <p class="sold-out">Sold out</p>
                                <?php endif; ?>
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