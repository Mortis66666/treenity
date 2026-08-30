<?php
session_start();
include_once("database.php");
include("debug.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    debug_log("Username: $username");

    $sql = "SELECT user_id, role, password FROM users WHERE username = ?";
    $result = $conn->execute_query($sql, [$username]);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $password_matches = password_verify($password, $row['password']);
        $legacy_plaintext = !$password_matches && hash_equals((string) $row['password'], $password);

        if ($password_matches || $legacy_plaintext) {
            if ($legacy_plaintext) {
                $conn->execute_query(
                    "UPDATE users SET password = ? WHERE user_id = ?",
                    [password_hash($password, PASSWORD_DEFAULT), $row['user_id']]
                );
            }

            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            debug_log("Login successful");

            header("Location: dashboard.php");
            exit();
        }
    }

    if (!isset($_SESSION['user_id'])) {
        debug_log("Login failed");
        $_SESSION['error'] = "Login failed";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Treenity</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/login.css?v=6">
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content login-page">
        <section class="login-panel" aria-label="Sign in form">
            <div class="login-panel-heading">
                <span class="panel-eyebrow">Your Treenity account</span>
                <h2>Sign in</h2>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <p class="error-message" role="alert"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <p class="success-message" role="status"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8') ?>" method="post">
                <div class="login-form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" placeholder="Enter your username" id="username" name="username" autocomplete="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" placeholder="Enter your password" id="password" name="password" autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit">Log in <span aria-hidden="true">&#8599;</span></button>
            </form>

            <p class="login-signup">New to Treenity? <a href="signup.php">Create an account</a></p>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>