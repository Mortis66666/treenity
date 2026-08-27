<?php
include_once("check_user.php");

check_user_role(['ADMIN', 'USER']);

$item_id = filter_input(INPUT_GET, 'item_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$item_id) {
    header("Location: not_found.php");
    exit();
}

$item_result = $conn->execute_query(
    "SELECT inventory.inventory_id, inventory.amount, inventory.purchased_at, inventory.claimed_at, inventory.status, inventory.user_id,
            store.name, store.description, store.cost, store.image_id
     FROM inventory
     INNER JOIN store ON store.item_id = inventory.item_id
     WHERE inventory.item_id = ?
     ORDER BY inventory.inventory_id DESC
     LIMIT 1",
    [$item_id]
);
$item = $item_result->fetch_assoc();


if (!$item) {
    header("Location: not_found.php");
    exit();
}

if ($item['user_id'] !== $_SESSION['user_id'] && $_SESSION['role'] !== 'ADMIN') {
    header("Location: not_found.php");
    exit();
}

$csrf_token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_owned') {
    check_user_role(['ADMIN']);

    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit('Invalid request.');
    }

    $conn->execute_query(
        "UPDATE inventory SET status = 'OWNED', claimed_at = NOW() WHERE inventory_id = ? AND status = 'PENDING'",
        [$item['inventory_id']]
    );

    header("Location: inventory_item.php?item_id=" . $item_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Item</title>
    <link rel="stylesheet" href="styles/inventory_item.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content inventory-item-page">
        <a class="inventory-item-back" href="inventory.php">Back to inventory</a>
        <article class="inventory-item-detail">
            <div class="inventory-item-image-panel">
                <img src="<?= htmlspecialchars(get_image_path($item['image_id']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="inventory-item-info">
                <h1><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="inventory-item-description"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>

                <dl class="inventory-item-data">
                    <div>
                        <dt>Quantity</dt>
                        <dd><?= (int) $item['amount'] ?></dd>
                    </div>
                    <div>
                        <dt>Cost</dt>
                        <dd><?= (int) $item['cost'] ?> points each</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Purchase date</dt>
                        <dd><?= htmlspecialchars($item['purchased_at'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Claimed at</dt>
                        <dd><?= $item['claimed_at'] ? htmlspecialchars($item['claimed_at'], ENT_QUOTES, 'UTF-8') : 'Not claimed yet' ?></dd>
                    </div>
                </dl>
            </div>
        </article>

        <?php if ($_SESSION['role'] === 'ADMIN' && $item['status'] !== 'OWNED'): ?>
            <form class="inventory-item-action" method="post">
                <input type="hidden" name="action" value="mark_owned">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <button class="qr-code-toggle" type="submit">Mark as owned</button>
            </form>
        <?php endif; ?>

        <section class="inventory-item-qr" aria-labelledby="qr-code-title">
            <button class="qr-code-toggle" id="qr-code-toggle" type="button" aria-controls="qr-code" aria-expanded="false">
                Show QR code
            </button>
            <div class="qr-code" id="qr-code" hidden>
                <h2 id="qr-code-title">Scan this item</h2>
            </div>
        </section>
    </main>

    <?php include("footer.php"); ?>

    <script src="scripts/lib/qrcode.min.js"></script>

    <script>
        const qrCodeContainer = document.getElementById('qr-code');
        const qrCodeToggle = document.getElementById('qr-code-toggle');
        let qrCodeCreated = false;

        qrCodeToggle.addEventListener('click', () => {
            const isExpanded = qrCodeToggle.getAttribute('aria-expanded') === 'true';
            qrCodeToggle.setAttribute('aria-expanded', String(!isExpanded));
            qrCodeToggle.textContent = isExpanded ? 'Show QR code' : 'Hide QR code';
            qrCodeContainer.hidden = isExpanded;

            if (!isExpanded && !qrCodeCreated) {
                new QRCode(qrCodeContainer, window.location.href);
                qrCodeCreated = true;
            }
        });
    </script>
</body>

</html>