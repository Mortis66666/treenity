<?php
session_start();
require 'database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $tp_number = trim($_POST['tp_number'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($username === '') $errors[] = 'Username is required.';
    if ($name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $result = $conn->execute_query(
            "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$username, $email]
        );
        if ($result->num_rows > 0) {
            $errors[] = 'Username or email already exists.';
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $conn->execute_query(
            "INSERT INTO users (username, name, email, tp_number, password, role)
             VALUES (?, ?, ?, ?, ?, 'USER')",
            [$username, $name, $email, $tp_number, $hashed_password]
        );
        header('Location: login.php?registered=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Treenity</title>
    <?php include 'global.php'; ?>
    <link rel="stylesheet" href="styles/signup.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <main class="content signup-page">
        <section class="signup-panel" aria-labelledby="signup-title">
            <div class="signup-panel-heading">
                <span class="panel-eyebrow">Join the Treenity community</span>
                <h1 id="signup-title">Create an account</h1>
                <p>Start your planting journey and take part in community events.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="signup-form-grid">
                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" maxlength="100" autocomplete="name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" maxlength="50" autocomplete="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="100" autocomplete="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="tp_number">TP number <span>(optional)</span></label>
                        <input type="text" id="tp_number" name="tp_number" maxlength="20" autocomplete="off"
                            value="<?= htmlspecialchars($_POST['tp_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" minlength="6" autocomplete="new-password" required>
                        <small>Use at least 6 characters.</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="signup-submit">Create account <span aria-hidden="true">&#8599;</span></button>
            </form>

            <p class="signup-footer">Already have an account? <a href="login.php">Log in</a></p>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>

</html>