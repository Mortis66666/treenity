<?php
include_once("database.php");
include_once("check_user.php");

check_user_role(['ADMIN']);

$csrf_token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$action_error = '';
$old_values = [
    'username' => '',
    'tp_number' => '',
    'role' => ''
];

$roles_result = $conn->execute_query("SELECT DISTINCT role FROM `users` WHERE role IS NOT NULL AND role <> '' ORDER BY role ASC");
$roles = [];
while ($role_row = $roles_result->fetch_assoc()) {
    $roles[] = $role_row['role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = $_POST['csrf_token'] ?? '';
    $old_values['username'] = trim($_POST['username'] ?? '');
    $old_values['tp_number'] = trim($_POST['tp_number'] ?? '');
    $old_values['role'] = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!hash_equals($csrf_token, $posted_token)) {
        $action_error = 'The request could not be verified. Please try again.';
    } elseif ($old_values['username'] === '' || strlen($old_values['username']) < 5 || strlen($old_values['username']) > 20) {
        $action_error = 'Username must be between 5 and 20 characters.';
    } elseif ($old_values['tp_number'] === '') {
        $action_error = 'Please provide a TP number.';
    } elseif ($password === '') {
        $action_error = 'Please provide a password.';
    } elseif ($password !== $confirm_password) {
        $action_error = 'Passwords do not match.';
    } elseif (!in_array($old_values['role'], $roles, true)) {
        $action_error = 'Please select a valid role.';
    } else {
        try {
            $result = $conn->execute_query(
                "INSERT INTO `users` (username, tp_number, password, role) VALUES (?, ?, ?, ?)",
                [$old_values['username'], $old_values['tp_number'], password_hash($password, PASSWORD_DEFAULT), $old_values['role']]
            );

            if ($result) {
                header('Location: users.php');
                exit();
            }

            $action_error = 'Unable to create the user.';
        } catch (mysqli_sql_exception $exception) {
            $action_error = $exception->getCode() === 1062
                ? 'That username or TP number is already in use.'
                : 'Unable to create the user.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>

    <link rel="stylesheet" href="styles/users.css">
    <?php include("global.php"); ?>
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">
        <div class="page-title-bar">
            <h1>Create new user</h1>
            <a class="back-to-users" href="users.php">Back to users</a>
        </div>

        <?php if ($action_error !== ''): ?>
            <p class="user-form-error" role="alert"><?= htmlspecialchars($action_error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form class="user-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <label for="username">Username</label>
            <input id="username" name="username" type="text" maxlength="20" value="<?= htmlspecialchars($old_values['username'], ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="tp-number">TP number</label>
            <input id="tp-number" name="tp_number" type="text" value="<?= htmlspecialchars($old_values['tp_number'], ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="">Select a role</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $role === $old_values['role'] ? 'selected' : '' ?>><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="confirm-password">Confirm password</label>
            <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required>

            <div class="user-form-actions">
                <a class="back-to-users" href="users.php">Cancel</a>
                <button class="save-user" type="submit">Create user</button>
            </div>
        </form>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>