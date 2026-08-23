<?php
include("check_user.php");

$pages = [
	"USER" => "user_dashboard.php",
	"ADMIN" => "admin_dashboard.php",
	"ORGANIZER" => "organizer_dashboard.php"
];

$role = ($_SESSION['role'] ?? '');
$dashboard_page = $pages[$role] ?? null;

if (!array_key_exists($role, $pages)) {
    debug_log("Invalid User Role");
    $_SESSION['error'] = "Invalid user role";
    
    header("Location: login.php");
    exit();
}

include($dashboard_page);
?>
