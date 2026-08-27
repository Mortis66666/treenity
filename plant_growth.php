<?php
include_once("database.php");
include_once("check_user.php");

$user_id = (int) $_SESSION['user_id'];
$errors = [];
$success = '';
$selected_event_id = (int) ($_POST['event_id'] ?? 0);
$height = trim($_POST['height'] ?? '');
$comments = trim($_POST['comments'] ?? '');

$event_result = $conn->execute_query(
    "SELECT DISTINCT e.event_id, e.name, p.participant_id
     FROM events e
     INNER JOIN participants p ON p.event_id = e.event_id AND p.user_id = ?
     ORDER BY e.start_time DESC, e.event_id DESC",
    [$user_id]
);
$events = [];
while ($event = $event_result->fetch_assoc()) {
    $events[] = $event;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event = null;
    foreach ($events as $event) {
        if ((int) $event['event_id'] === $selected_event_id) {
            $selected_event = $event;
            break;
        }
    }

    if (!$selected_event) {
        $errors[] = 'Choose a valid event.';
    }
    if ($height === '' || !is_numeric($height) || (float) $height <= 0) {
        $errors[] = 'Enter a height greater than 0 cm.';
    }
    if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Choose a plant photo to upload.';
    }

    if (!$errors) {
        try {
            $image_id = create_image('growth', $_FILES['photo']);
            $conn->execute_query(
                "INSERT INTO logs (participant_id, height, comments, image_id) VALUES (?, ?, ?, ?)",
                [(int) $selected_event['participant_id'], (float) $height, $comments, $image_id]
            );
            $success = 'Plant growth update submitted successfully.';
            $selected_event_id = 0;
            $height = '';
            $comments = '';
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Plant Growth</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/plant_growth.css?v=2">


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content growth-page">
        <section class="growth-panel" aria-labelledby="growth-title">
            <div class="growth-heading">
                <h1 id="growth-title">Upload Plant Growth Updates</h1>
            </div>

            <?php if ($errors): ?>
                <div class="message message-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($success !== ''): ?>
                <p class="message message-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if (!$events): ?>
                <p class="growth-empty">No events are available yet.</p>
            <?php else: ?>
                <form class="growth-form" method="post" enctype="multipart/form-data">
                    <label for="event_id">Choose an event</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">Select an event</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?= (int) $event['event_id'] ?>" <?= $selected_event_id === (int) $event['event_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="photo">Upload a photo</label>
                    <label class="photo-upload" for="photo">
                        <span aria-hidden="true">&#128247;</span>
                        <strong>Upload a Photo</strong>
                        <small>JPEG, PNG, GIF, or WebP</small>
                        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    </label>

                    <label for="height">Enter Height (cm)</label>
                    <input id="height" name="height" type="number" min="0.1" step="0.1" value="<?= htmlspecialchars($height, ENT_QUOTES, 'UTF-8') ?>" required>

                    <label for="comments">Comments</label>
                    <textarea id="comments" name="comments" rows="4"><?= htmlspecialchars($comments, ENT_QUOTES, 'UTF-8') ?></textarea>

                    <button type="submit">Submit Update</button>
                </form>
            <?php endif; ?>
        </section>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>