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

function create_image(string $image_type, array $image_data): int
{
    global $conn;

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $image_type)) {
        throw new InvalidArgumentException("Invalid image type.");
    }

    if (($image_data['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("The image upload failed.");
    }

    if (!is_uploaded_file($image_data['tmp_name'])) {
        throw new RuntimeException("The uploaded file is invalid.");
    }

    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $file_info->file($image_data['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($extensions[$mime_type])) {
        throw new RuntimeException("Only JPEG, PNG, GIF, and WebP images are supported.");
    }

    $directory = __DIR__ . "/images/$image_type";
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create the image directory.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $extensions[$mime_type];
    $relative_path = "$image_type/$filename";
    $absolute_path = "$directory/$filename";

    if (!move_uploaded_file($image_data['tmp_name'], $absolute_path)) {
        throw new RuntimeException("Unable to save the uploaded image.");
    }

    try {
        $result = $conn->execute_query(
            "INSERT INTO images (type, path) VALUES (?, ?)",
            [$image_type, $relative_path]
        );
    } catch (Throwable $exception) {
        unlink($absolute_path);
        throw $exception;
    }

    if (!$result) {
        unlink($absolute_path);
        throw new RuntimeException("Unable to save the image record.");
    }

    return $conn->insert_id;
}
