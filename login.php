<?php
session_start();
include_once("database.php");
include("debug.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    debug_log("Username: $username");

    $sql = "SELECT user_id, role FROM users WHERE username=? AND password=?";
    $result = $conn->execute_query($sql, [$username, $password]);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role'] = $row['role'];
        debug_log("Login successful");

        header("Location: dashboard.php");
        exit();
    } else {
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
    <link rel="stylesheet" href="styles/login.css?v=5">
    <link rel="stylesheet" href="styles/signup.css?v=2">
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content signup-page">
        <section class="signup-panel" aria-label="Sign in form">
            <div class="signup-panel-heading">
                <span class="panel-eyebrow">Your Treenity account</span>
                <h1>Sign in</h1>
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
                <div class="signup-form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" placeholder="Enter your username" id="username" name="username" autocomplete="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" placeholder="Enter your password" id="password" name="password" autocomplete="current-password" required>
                    </div>
                </div>

                <button class="signup-submit" type="submit">Log in <span aria-hidden="true">&#8599;</span></button>
            </form>

            <p class="signup-footer">New to Treenity? <a href="signup.php">Create an account</a></p>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>