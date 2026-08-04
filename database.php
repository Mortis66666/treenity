<?php

$config_file = file_get_contents("config.json");
$config = json_decode($config_file, true);

$servername = "localhost";
$username = "root";
$password = "";
$database = $config["database"];

// Set timezone
date_default_timezone_set("Asia/Kuala_Lumpur");

// Create a connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
