<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ORGANIZER') {
    header('Location: login.php');
    exit;
}

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $name       = trim($_POST['name'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $stock      = (int)($_POST['stock_left'] ?? 0);

        if ($name === '')   $errors[] = 'Item name is required.';
        if ($stock < 0)     $errors[] = 'Stock cannot be negative.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO store (name, description, stock_left) VALUES (?, ?, ?)");
            $stmt->execute([$name, $desc, $stock]);
            $success = 'Item added to store.';
        }
    }

    if ($_POST['action'] === 'update_stock') {
        $item_id   = (int)$_POST['item_id'];
        $new_stock = (int)$_POST['stock_left'];
        if ($new_stock < 0) {
            $errors[] = 'Stock cannot be negative.';
        } else {
            $stmt = $pdo->prepare("UPDATE store SET stock_left = ? WHERE item_id = ?");
            $stmt->execute([$new_stock, $item_id]);
            $success = 'Stock updated.';
        }
    }

    if ($_POST['action'] === 'delete') {
        $item_id = (int)$_POST['item_id'];
        $stmt = $pdo->prepare("DELETE FROM store WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $success = 'Item deleted.';
    }
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM store";
$params = [];
if ($search !== '') {
    $sql .= " WHERE name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY item_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css">
<style>
    .eo-wrap {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
    }
    .two-col {
        display: grid;
        grid-template-columns: 1fr 1.6fr;
        gap: 16px;
    }
    .card {
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px; padding: 18px;
        margin-bottom: 16px;
    }
    .card-title {
        font-size: 13px;
        font-weight: 600;
        color: #6b7a99;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    label {
        display: block;
        font-size: 12px;
        color: #6b7a99;
        margin-bottom: 5px;
        font-weight: 600;
    }
    input[type="text"], input[type="number"], textarea {
        width: 100%;
        padding: 9px 12px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 6px;
        color: #c8d4e8;
        font-size: 13px;
        box-sizing: border-box;
        margin-bottom: 12px;
    }
    input:focus, textarea:focus {
        border-color: #2563eb;
        outline: none;
    }
    textarea {
        min-height:
        60px; resize: vertical;
    }
    .btn-primary {
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
    }
    .btn-primary:hover {
        background: #1648c0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    th {
        text-align: left;
        padding: 8px 10px;
        color: #6b7a99;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        border-bottom: 1px solid #2a3a50;
    }
    td {
        padding: 10px;
        color: #c8d4e8;
        border-bottom: 1px solid #1e2d42;
        vertical-align: middle;
    }
    tr:last-child td {
        border: none;
    }
    .badge {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 600;
    }
    .badge-ok  {
        background: #14532d;
        color: #86efac;
    }
    .badge-low {
        background: #451a03;
        color: #fcd34d;
    }
    .badge-out {
        background: #1f2937;
        color: #6b7280;
        border: 1px solid #374151;
    }
    .stock-input {
        width: 60px;
        padding: 4px 7px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 4px;
        color: #c8d4e8;
        font-size: 12px;
        text-align: center;
    }
    .btn-sm {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 5px;
        border: none; cursor:
        pointer;
        font-weight: 600;
    }
    .btn-save {
        background: #14532d;
        color: #86efac;
    }
    .btn-save:hover {
        background: #166534;
    }
    .btn-del {
        background: #450a0a;
        color: #fca5a5;
    }
    .btn-del:hover {
        background: #7f1d1d;
    }
    .alert-success {
        background: #14532d;
        border: 1px solid #166534;
        color: #86efac;
        padding: 10px 16px;
        border-radius: 7px;
        margin-bottom: 14px;
        font-size: 13px;
    }
    .alert-error {
        background: #450a0a;
        border: 1px solid #7f1d1d;
        color: #fca5a5;
        padding: 10px 16px;
        border-radius: 7px;
        margin-bottom: 14px;
        font-size: 13px;
    }
    .search-row {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
    }
    .search-input {
        flex: 1;
        padding: 8px 12px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 6px;
        color: #c8d4e8;
        font-size: 13px;
    }
    .search-input:focus {
        border-color: #2563eb;
        outline: none;
    }
    .btn-search {
        background: #1a56db;
        color: #fff; border: none;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        font-weight: 600;
    }
    @media (max-width: 700px) { .two-col { grid-template-columns: 1fr; } }
</style>

<div class="eo-wrap">
    <div class="page-title">Inventory</div>

    <?php if ($success): ?><div class="alert-success">&#10003; <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert-error">&#x26A0; <?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <div class="two-col">

        <div>
            <div class="card">
                <div class="card-title">+ Add Item to Store</div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <label>Item Name *</label>
                    <input type="text" name="name" maxlength="100"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. Water Bottle" required>

                    <label>Description</label>
                    <textarea name="description" placeholder="Short description…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                    <label>Stock Quantity</label>
                    <input type="number" name="stock_left" min="0"
                        value="<?= htmlspecialchars($_POST['stock_left'] ?? 0) ?>">

                    <button type="submit" class="btn-primary">Add Item</button>
                </form>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-title">Store Items</div>
                <form method="GET" class="search-row">
                    <input type="text" name="search" class="search-input" placeholder="Search items…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">Search</button>
                </form>

                <?php if (empty($items)): ?>
                    <p style="color:#6b7a99;font-size:13px;text-align:center;padding:20px 0">No items found.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Item</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            if ($item['stock_left'] == 0) { $badge = 'badge-out'; $label = 'Out of stock'; }
                            elseif ($item['stock_left'] <= 5) { $badge = 'badge-low'; $label = 'Low stock'; }
                            else { $badge = 'badge-ok'; $label = 'Available'; }
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;color:#fff"><?= htmlspecialchars($item['name']) ?></div>
                                <div style="font-size:11px;color:#6b7a99"><?= htmlspecialchars($item['description'] ?? '') ?></div>
                            </td>
                            <td>
                                <form method="POST" style="display:flex;gap:5px;align-items:center">
                                    <input type="hidden" name="action" value="update_stock">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <input type="number" name="stock_left" class="stock-input" value="<?= $item['stock_left'] ?>" min="0">
                                    <button type="submit" class="btn-sm btn-save">Save</button>
                                </form>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <button type="submit" class="btn-sm btn-del">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>