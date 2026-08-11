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

function get_image_path($image_id)
{
    global $conn;

    $query = "SELECT path from images WHERE image_id=?";
    $result = $conn->execute_query($query, [$image_id]);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return "images/" . $row['path'];
    } else {
        return "images/invalid.png";
    }
}
