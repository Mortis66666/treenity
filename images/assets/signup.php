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
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, name, email, tp_number, password, role)
            VALUES (?, ?, ?, ?, ?, 'USER')
        ");
        $stmt->execute([$username, $name, $email, $tp_number, $hashed]);
        header('Location: login.php?registered=1');
        exit;
    }
}

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css">
<style>
    .auth-wrap {
        max-width: 440px;
        margin: 50px auto;
        padding: 0 20px;
    }
    .auth-card {
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 12px;
        padding: 30px;
    }
    .auth-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 6px;
        text-align: center;
    }
    .auth-sub {
        font-size: 13px;
        color: #6b7a99;
        margin-bottom: 24px;
        text-align: center;
    }
    .form-group {
        margin-bottom: 16px;
    }
    label {
        display: block;
        font-size: 12px;
        color: #6b7a99;
        margin-bottom: 5px;
        font-weight: 600;
    }
    input[type="text"], input[type="email"], input[type="password"] {
        width: 100%;
        padding: 10px 12px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 6px;
        color: #c8d4e8;
        font-size: 13px;
        box-sizing: border-box;
    }
    input:focus {
        border-color: #2563eb;
        outline: none;
    }
    .btn-primary {
        width: 100%;
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 11px;
        border-radius: 7px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 6px;
    }
    .btn-primary:hover {
        background: #1648c0;
    }
    .error-box {
        background: #450a0a;
        border: 1px solid #7f1d1d;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 18px;
    }
    .error-box p {
        color: #fca5a5;
        font-size: 13px;
        margin: 3px 0;
    }
    .auth-footer {
        text-align: center;
        font-size: 13px;
        color: #6b7a99;
        margin-top: 18px;
    }
    .auth-footer a {
        color: #4a9eff;
        text-decoration: none;
    }
    .auth-footer a:hover {
        text-decoration: underline;
    }
</style>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-title">Create Account</div>
        <div class="auth-sub">Join Treenity APU and start your journey</div>

        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $e): ?><p>&#x26A0; <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" maxlength="50"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="100"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="tp_number">TP Number</label>
                <input type="text" id="tp_number" name="tp_number" maxlength="20"
                    value="<?= htmlspecialchars($_POST['tp_number'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>

            <button type="submit" class="btn-primary">Sign Up</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>