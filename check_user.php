<?php
include("debug.php");
include_once("database.php");
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    debug_log("User not logged in");
    header("Location: index.php");
    exit();
}

// Validate user
$query = "SELECT * FROM `users` WHERE user_id = ?";
$result = $conn->execute_query($query, [$_SESSION['user_id']]);

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: index.php");
    exit();
}

function check_user_role($requiredRoles)
{
    global $conn;
    $userId = $_SESSION['user_id'];
    $query = "SELECT role FROM `users` WHERE user_id = ?";
    $result = $conn->execute_query($query, [$userId]);
    $userRole = $result->fetch_assoc()['role'];
    if (!in_array($userRole, $requiredRoles)) {
        header("Location: index.php");
        exit();
    }
}
