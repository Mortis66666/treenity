<?php
include_once("database.php");
include_once("check_user.php");

check_user_role(['ADMIN']);

$error_message = '';
$success_message = '';
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $description === '') {
        $error_message = 'Please enter an item name and description.';
    } elseif (!isset($_FILES['icon'])) {
        $error_message = 'Please choose an icon for this item.';
    } else {
        try {
            $image_id = create_image('item', $_FILES['icon']);
            $result = $conn->execute_query(
                "INSERT INTO store (name, description, image_id) VALUES (?, ?, ?)",
                [$name, $description, $image_id]
            );

            if (!$result) {
                throw new RuntimeException('Unable to create the store item.');
            }

            $success_message = 'Item created successfully.';
            $name = '';
            $description = '';
        } catch (Throwable $exception) {
            $error_message = $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Item | Treenity</title>

    <link rel="stylesheet" href="styles/new_item.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content new-item-page">
        <section class="heading">
            <p class="eyebrow">Admin workspace</p>
            <h1>Create a store item</h1>
            <p>Add a new reward for the Treenity community to discover and redeem.</p>
        </section>

        <section class="new-item-panel" aria-label="Create store item form">
            <?php if ($error_message !== ''): ?>
                <p class="form-message form-message-error" role="alert"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($success_message !== ''): ?>
                <p class="form-message form-message-success" role="status"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="name">Item name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" maxlength="100" required>
                </div>

                <div class="form-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" maxlength="1000" required><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-field">
                    <label for="icon">Item icon</label>
                    <input type="file" id="icon" name="icon" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <span class="field-hint">JPEG, PNG, GIF, or WebP</span>
                </div>

                <button type="submit">Create item <span aria-hidden="true">&#8599;</span></button>
            </form>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>