<?php
include_once("database.php");
include_once("check_user.php");

$user_id = (int) $_SESSION['user_id'];
$errors = [];
$success = '';

$user_result = $conn->execute_query(
    "SELECT username, name, email, tp_number, password, profile_icon_id FROM users WHERE user_id = ?",
    [$user_id]
);
$user = $user_result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tp_number = trim($_POST['tp_number'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($new_password !== '' || $current_password !== '' || $confirm_new_password !== '') {
        if ($current_password === '') {
            $errors[] = 'Enter your current password.';
        } elseif (
            !password_verify($current_password, $user['password'])
            && !hash_equals((string) $user['password'], $current_password)
        ) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($new_password !== $confirm_new_password) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (!$errors) {
        $duplicate_result = $conn->execute_query(
            "SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id <> ?",
            [$username, $email, $user_id]
        );
        if ($duplicate_result->num_rows > 0) {
            $errors[] = 'That username or email is already in use.';
        }
    }

    $image_id = null;
    if (!$errors && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $image_id = create_image('profile', $_FILES['profile_image']);
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors) {
        $fields = [$username, $name, $email, $tp_number];
        $set_clause = 'username = ?, name = ?, email = ?, tp_number = ?';

        if ($new_password !== '') {
            $set_clause .= ', password = ?';
            $fields[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        if ($image_id !== null) {
            $set_clause .= ', profile_icon_id = ?';
            $fields[] = $image_id;
        }

        $fields[] = $user_id;
        $conn->execute_query("UPDATE users SET $set_clause WHERE user_id = ?", $fields);
        $success = 'Your settings have been updated.';
        $user['username'] = $username;
        $user['name'] = $name;
        $user['email'] = $email;
        $user['tp_number'] = $tp_number;
        if ($image_id !== null) {
            $user['profile_icon_id'] = $image_id;
        }
    }
} else {
    $username = $user['username'];
    $name = $user['name'];
    $email = $user['email'];
    $tp_number = $user['tp_number'];
}

$profile_image_path = !empty($user['profile_icon_id'])
    ? get_image_path((int) $user['profile_icon_id'])
    : 'images/invalid.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>

    <link rel="stylesheet" href="styles/settings.css?v=2">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content settings-page">
        <section class="settings-panel" aria-labelledby="settings-title">
            <h1 id="settings-title">Settings</h1>

            <?php if ($errors): ?>
                <div class="settings-message settings-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success !== ''): ?>
                <p class="settings-message settings-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form class="settings-form" method="post" enctype="multipart/form-data">
                <div class="profile-image-editor">
                    <img src="<?= htmlspecialchars($profile_image_path, ENT_QUOTES, 'UTF-8') ?>" alt="Current profile image">
                    <label class="image-change" for="profile_image" title="Change profile image">&#9998;</label>
                    <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>

                <div class="settings-fields">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" maxlength="50" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" maxlength="100" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="255" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="tp_number">TP Number</label>
                    <input id="tp_number" name="tp_number" type="text" maxlength="30" value="<?= htmlspecialchars($tp_number, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <section class="password-section" aria-labelledby="password-title">
                    <h2 id="password-title">Change Password</h2>
                    <p>Leave these fields blank to keep your current password.</p>
                    <div class="password-fields">
                        <label for="current_password">Current Password</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password">

                        <label for="new_password">New Password</label>
                        <input id="new_password" name="new_password" type="password" autocomplete="new-password">

                        <label for="confirm_new_password">Confirm New Password</label>
                        <input id="confirm_new_password" name="confirm_new_password" type="password" autocomplete="new-password">
                    </div>
                </section>

                <button type="submit">Confirm Changes</button>
            </form>
        </section>

    </main>

    <?php include("footer.php"); ?>
    <script>
        document.getElementById('profile_image').addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (!file) {
                return;
            }

            document.querySelector('.profile-image-editor img').src = URL.createObjectURL(file);
        });
    </script>
</body>

</html>