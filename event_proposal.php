<?php
include_once("database.php");
include_once("check_user.php");

if (($_SESSION['role'] ?? '') !== 'USER') {
    header("Location: index.php");
    exit();
}

$errors = [];
$success = '';
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$verification_code = trim($_POST['verification_code'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$end_time = trim($_POST['end_time'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '') {
        $errors[] = 'Event name is required.';
    }
    if ($description === '') {
        $errors[] = 'Event description is required.';
    }
    if ($verification_code === '') {
        $errors[] = 'Verification code is required.';
    }
    if ($start_time === '') {
        $errors[] = 'Start date/time is required.';
    }
    if ($end_time === '') {
        $errors[] = 'End date/time is required.';
    }
    if ($start_time !== '' && $end_time !== '' && $start_time >= $end_time) {
        $errors[] = 'End time must be after start time.';
    }

    $banner_id = null;
    $banner_upload = $_FILES['banner'] ?? null;
    if ($banner_upload && $banner_upload['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($banner_upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The banner upload failed.';
        }
    }

    if (!$errors) {
        try {
            if ($banner_upload && $banner_upload['error'] === UPLOAD_ERR_OK) {
                $banner_id = create_image('banner', $banner_upload);
            }

            $conn->execute_query(
                "INSERT INTO events
                    (banner_id, organizer_id, name, description, verification_code, start_time, end_time, is_published)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0)",
                [$banner_id, (int) $_SESSION['user_id'], $name, $description, $verification_code, $start_time, $end_time]
            );
            $success = 'Event proposal submitted successfully.';
            $name = $description = $verification_code = $start_time = $end_time = '';
        } catch (Throwable $exception) {
            $errors[] = 'Unable to submit the event proposal.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propose an Event</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/event_proposal.css">


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content event-proposal-page">
        <section class="event-proposal-panel" aria-labelledby="proposal-title">
            <div class="event-proposal-heading">
                <h1 id="proposal-title">Propose an Event</h1>
                <p>Your proposal will be sent for review before being published.</p>
            </div>

            <?php if ($errors): ?>
                <div class="form-message form-message-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success !== ''): ?>
                <p class="form-message form-message-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="name">Event name</label>
                    <input id="name" name="name" type="text" maxlength="255" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-field">
                    <label for="banner">Banner image</label>
                    <input id="banner" name="banner" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>

                <div class="form-field">
                    <label for="verification_code">Verification code</label>
                    <input id="verification_code" name="verification_code" type="text" maxlength="20" value="<?= htmlspecialchars($verification_code, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="time-fields">
                    <div class="form-field">
                        <label for="start_time">Start time</label>
                        <input id="start_time" name="start_time" type="datetime-local" value="<?= htmlspecialchars($start_time, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="end_time">End time</label>
                        <input id="end_time" name="end_time" type="datetime-local" value="<?= htmlspecialchars($end_time, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <button type="submit">Submit Proposal</button>
            </form>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>