<?php
session_start();
require("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANISER') {
    header("Location: login.php");
    exit();
}

$organiser_id = $_SESSION['user_id'];
$errors = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $verification_code = trim($_POST['verification_code']);

    if ($name == '') {
        $errors[] = "Event name is required.";
    }
    if ($start_time == '') {
        $errors[] = "Start date/time is required.";
    }
    if ($end_time == '') {
        $errors[] = "End date/time is required.";
    }
    if ($start_time != '' && $end_time != '' && $start_time >= $end_time) {
        $errors[] = "End time must be after start time.";
    }
    if ($verification_code == '') {
        $errors[] = "Verification code is required.";
    }

    $banner_id = null;

    if (isset($_FILES['banner']) && $_FILES['banner']['name'] != '') {
        $file_type = $_FILES['banner']['type'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Banner must be an image file (JPG, PNG, GIF, WEBP).";
        } else {
            $insert_image = $pdo->prepare("INSERT INTO images (type) VALUES ('banner')");
            $insert_image->execute();
            $banner_id = $pdo->lastInsertId();

            $target_path = "images/banner/" . $banner_id . ".png";
            move_uploaded_file($_FILES['banner']['tmp_name'], $target_path);
        }
    }

    if (count($errors) == 0) {
        $insert_event = $pdo->prepare("INSERT INTO events (banner_id, organiser_id, name, description, verification_code, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_event->execute(array($banner_id, $organiser_id, $name, $description, $verification_code, $start_time, $end_time));

        header("Location: eo_events.php?created=1");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>

    <style>
.content {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}

.content h1 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 32px;
    color: #1b4332;
    margin-bottom: 6px;
    text-align: center;
}

.content h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 18px;
    color: #1b4332;
    margin-bottom: 16px;
}

.page-sub {
    text-align: center;
    color: #6b6355;
    margin-bottom: 30px;
}

.section-box {
    background: #fff;
    border: 1px solid #e0dacd;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

form label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #1b4332;
    margin-bottom: 6px;
    margin-top: 16px;
}

form label:first-of-type {
    margin-top: 0;
}

form input[type="text"],
form input[type="datetime-local"],
form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d8cfc0;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    color: #33302a;
    background: #fdfcfa;
    box-sizing: border-box;
}

form input:focus,
form textarea:focus {
    outline: none;
    border-color: #1b4332;
}

form textarea {
    resize: vertical;
}

form input[type="file"] {
    margin-top: 6px;
}

.error-box {
    background: #fbe3e3;
    border: 1px solid #e08d8d;
    border-radius: 6px;
    padding: 14px 18px;
    margin-bottom: 20px;
}

.error-box p {
    color: #8a2e2e;
    margin: 4px 0;
    font-size: 14px;
}

.btn-primary {
    background: #1b4332;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 20px;
    margin-right: 10px;
}

.btn-primary:hover {
    background: #2d6a4f;
}

.btn-secondary {
    color: #1b4332;
    text-decoration: none;
    font-weight: 600;
}

.btn-secondary:hover {
    text-decoration: underline;
}

</style>
    <?php include("global.php"); ?>

</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

        <h1>Create Event</h1>
        <p class="page-sub">Fill in the details below to create a new event.</p>

        <?php if (count($errors) > 0) { ?>
        <div class="error-box">
            <?php foreach ($errors as $error) { ?>
                <p><?php echo $error; ?></p>
            <?php } ?>
        </div>
        <?php } ?>

        <form method="POST" action="EOCreateEvent.php" enctype="multipart/form-data">

            <div class="section-box">
                <h2>Event Details</h2>

                <label for="name">Event Name</label>
                <input type="text" id="name" name="name" maxlength="100"
                    value="<?php if (isset($_POST['name'])) echo htmlspecialchars($_POST['name']); ?>" required>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php if (isset($_POST['description'])) echo htmlspecialchars($_POST['description']); ?></textarea>

                <label for="start_time">Start Date and Time</label>
                <input type="datetime-local" id="start_time" name="start_time"
                    value="<?php if (isset($_POST['start_time'])) echo $_POST['start_time']; ?>" required>

                <label for="end_time">End Date and Time</label>
                <input type="datetime-local" id="end_time" name="end_time"
                    value="<?php if (isset($_POST['end_time'])) echo $_POST['end_time']; ?>" required>

                <label for="verification_code">Verification Code</label>
                <input type="text" id="verification_code" name="verification_code" maxlength="20"
                    value="<?php if (isset($_POST['verification_code'])) echo htmlspecialchars($_POST['verification_code']); ?>" required>
            </div>

            <div class="section-box">
                <h2>Banner Image</h2>
                <input type="file" name="banner" accept="image/*">
            </div>

            <button type="submit" class="btn-primary">Create Event</button>
            <a href="EOEvents.php" class="btn-secondary">Cancel</a>

        </form>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>