<?php
include("debug.php");

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    debug_log("User not logged in");
    header("Location: index.php");
    exit();
}
