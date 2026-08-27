<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANIZER') {
    header("Location: login.php");
    exit();
}

$success = '';
$errors = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] == 'add') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $cost = (int)$_POST['cost'];
        $stock_left = (int)$_POST['stock_left'];

        if ($name == '') {
            $errors[] = "Item name is required.";
        }
        if ($stock_left < 0) {
            $errors[] = "Stock cannot be negative.";
        }

        if (count($errors) == 0) {
            $conn->execute_query("INSERT INTO store (name, description, cost, stock_left) VALUES (?, ?, ?, ?)", [$name, $description, $cost, $stock_left]);
            $success = "Item added to store.";
        }
    }

    if ($_POST['action'] == 'update_stock') {
        $item_id = (int)$_POST['item_id'];
        $new_stock = (int)$_POST['stock_left'];

        if ($new_stock < 0) {
            $errors[] = "Stock cannot be negative.";
        } else {
            $conn->execute_query("UPDATE store SET stock_left = ? WHERE item_id = ?", [$new_stock, $item_id]);
            $success = "Stock updated.";
        }
    }

    if ($_POST['action'] == 'delete') {
        $item_id = (int)$_POST['item_id'];
        $conn->execute_query("DELETE FROM store WHERE item_id = ?", [$item_id]);
        $success = "Item deleted.";
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM store";
$params = array();
if ($search != '') {
    $sql .= " WHERE name LIKE ?";
    $params[] = "%" . $search . "%";
}
$sql .= " ORDER BY item_id DESC";

$result = $conn->execute_query($sql, $params);
$items = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>

    <style>
.content {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}

.content h1 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 32px;
    color: #1b4332;
    margin-bottom: 20px;
}

.content h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 18px;
    color: #1b4332;
    margin-bottom: 16px;
}

.success-box {
    background: #d8f0dc;
    border: 1px solid #9bd4a8;
    color: #1b4332;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.error-box {
    background: #fbe3e3;
    border: 1px solid #e08d8d;
    color: #8a2e2e;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.inventory-layout {
    display: grid;
    grid-template-columns: 1fr 1.6fr;
    gap: 20px;
}

.section-box {
    background: #fff;
    border: 1px solid #e0dacd;
    border-radius: 8px;
    padding: 22px;
}

form label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #1b4332;
    margin-bottom: 6px;
    margin-top: 14px;
}

form label:first-of-type {
    margin-top: 0;
}

form input[type="text"],
form input[type="number"],
form textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d8cfc0;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    background: #fdfcfa;
    box-sizing: border-box;
}

form input:focus,
form textarea:focus {
    outline: none;
    border-color: #1b4332;
}

form textarea {
    resize: vertical;
}

.btn-primary {
    background: #1b4332;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 18px;
}

.btn-primary:hover {
    background: #2d6a4f;
}

.search-form {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
}

.search-form input[type="text"] {
    flex: 1;
    padding: 9px 12px;
    border: 1px solid #d8cfc0;
    border-radius: 6px;
    font-size: 14px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    text-align: left;
    padding: 10px;
    color: #6b6355;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-bottom: 2px solid #e0dacd;
}

.data-table td {
    padding: 10px;
    color: #33302a;
    border-bottom: 1px solid #eee6d8;
    vertical-align: middle;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.stock-form {
    display: flex;
    gap: 6px;
    align-items: center;
}

.stock-input {
    width: 60px;
    padding: 5px 8px;
    border: 1px solid #d8cfc0;
    border-radius: 5px;
    font-size: 13px;
    text-align: center;
}

.stock-form button {
    background: #1b4332;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
}

.stock-form button:hover {
    background: #2d6a4f;
}

.status-tag {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
}

.status-ok {
    background: #d8f0dc;
    color: #1b4332;
}

.status-low {
    background: #fbeacb;
    color: #92620c;
}

.status-out {
    background: #ece7dc;
    color: #7a7264;
}

.data-table form {
    margin: 0;
}

.data-table td button {
    background: none;
    border: 1px solid #e08d8d;
    color: #a33;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
}

.data-table td button:hover {
    background: #fbe3e3;
}

@media (max-width: 700px) {
    .inventory-layout {
        grid-template-columns: 1fr;
    }
}

</style>
    <?php include("global.php"); ?>

</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

        <h1>Inventory</h1>

        <?php if ($success != '') { ?>
            <div class="success-box"><?php echo $success; ?></div>
        <?php } ?>
        <?php foreach ($errors as $error) { ?>
            <div class="error-box"><?php echo $error; ?></div>
        <?php } ?>

        <div class="inventory-layout">

            <div class="section-box">
                <h2>Add Item to Store</h2>
                <form method="POST" action="eo_inventory.php">
                    <input type="hidden" name="action" value="add">

                    <label for="name">Item Name</label>
                    <input type="text" id="name" name="name" maxlength="100" placeholder="e.g. Water Bottle" required>

                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>

                    <label for="cost">Cost (points)</label>
                    <input type="number" id="cost" name="cost" min="0" value="0">

                    <label for="stock_left">Stock Quantity</label>
                    <input type="number" id="stock_left" name="stock_left" min="0" value="0">

                    <button type="submit" class="btn-primary">Add Item</button>
                </form>
            </div>

            <div class="section-box">
                <h2>Store Items</h2>

                <form method="GET" action="eo_inventory.php" class="search-form">
                    <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-primary">Search</button>
                </form>

                <?php if (count($items) == 0) { ?>
                    <p>No items found.</p>
                <?php } else { ?>

                <table class="data-table">
                    <tr>
                        <th>Item</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($items as $item) {
                        if ($item['stock_left'] == 0) {
                            $status_label = "Out of stock";
                            $status_class = "status-out";
                        } else if ($item['stock_left'] <= 5) {
                            $status_label = "Low stock";
                            $status_class = "status-low";
                        } else {
                            $status_label = "Available";
                            $status_class = "status-ok";
                        }
                    ?>
                    <tr>
                        <td>
                            <b><?php echo htmlspecialchars($item['name']); ?></b><br>
                            <small><?php echo htmlspecialchars($item['description']); ?></small>
                        </td>
                        <td><?php echo $item['cost']; ?> pts</td>
                        <td>
                            <form method="POST" action="eo_inventory.php" class="stock-form">
                                <input type="hidden" name="action" value="update_stock">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <input type="number" name="stock_left" value="<?php echo $item['stock_left']; ?>" min="0" class="stock-input">
                                <button type="submit">Save</button>
                            </form>
                        </td>
                        <td><span class="status-tag <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                        <td>
                            <form method="POST" action="eo_inventory.php" onsubmit="return confirm('Delete this item?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>

                </table>

                <?php } ?>
            </div>

        </div>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>