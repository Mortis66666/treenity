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
    <title>Login | Treenity</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/login.css">
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content login-page">
        <!-- <section class="login-intro" aria-labelledby="login-title">
            <p class="login-kicker">Welcome back to the grove</p>
            <h1 id="login-title">Keep growing with your community.</h1>
            <p>Sign in to follow your planting journey, discover new events, and see the good work taking root around you.</p>
            <span class="login-mark" aria-hidden="true">✳</span>
        </section> -->

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
                <label for="username">Username</label>
                <input type="text" placeholder="Enter your username" id="username" name="username" autocomplete="username" required>

                <label for="password">Password</label>
                <input type="password" placeholder="Enter your password" id="password" name="password" autocomplete="current-password" required>

                <button type="submit">Log in <span aria-hidden="true">&#8599;</span></button>
            </form>

            <p class="login-signup">New to Treenity? <a href="signup.php">Create an account</a></p>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>