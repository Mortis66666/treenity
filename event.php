<?php
include_once("database.php");

if (!isset($_SESSION)) {
    session_start();
}

$event_id = filter_input(INPUT_GET, 'event', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$event_id = $event_id ?: filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$message = '';
$message_class = '';
$is_registered = false;

if (!$event_id) {
    header("Location: not_found.php");
    exit();
}

$event_result = $conn->execute_query(
    "SELECT e.event_id, e.name, e.description,
            e.start_time, e.end_time, e.banner_id, u.username AS organizer_name,
            i.path AS banner_path
     FROM events e
     LEFT JOIN users u ON u.user_id = e.organizer_id
     LEFT JOIN images i ON i.image_id = e.banner_id
     WHERE e.event_id = ?",
    [$event_id]
);
$event = $event_result->fetch_assoc();

if (!$event) {
    header("Location: not_found.php");
    exit();
}

$is_user = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'USER';
if ($is_user) {
    $registration_result = $conn->execute_query(
        "SELECT participant_id FROM participants WHERE event_id = ? AND user_id = ?",
        [$event_id, $_SESSION['user_id']]
    );
    $is_registered = $registration_result->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_user) {
        $message = 'Only users can register for events.';
        $message_class = 'error-message';
    } elseif ($is_registered) {
        $message = 'You are already registered for this event.';
        $message_class = 'error-message';
    } else {
        try {
            $conn->execute_query(
                "INSERT IGNORE INTO participants (event_id, user_id) VALUES (?, ?)",
                [$event_id, $_SESSION['user_id']]
            );
            $is_registered = true;
            $message = 'You are registered for this event.';
            $message_class = 'success-message';
        } catch (Throwable $exception) {
            $message = 'Unable to register for this event.';
            $message_class = 'error-message';
        }
    }
}

$start_time = new DateTime($event['start_time']);
$end_time = new DateTime($event['end_time']);
$now = new DateTime();
$status = $now < $start_time ? 'Upcoming' : ($now <= $end_time ? 'Ongoing' : 'Ended');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event</title>

    <link rel="stylesheet" href="styles/event.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content event-page">
        <article class="event-detail">
            <?php if (!empty($event['banner_path'])): ?>
                <img class="event-detail-banner" src="<?= htmlspecialchars('images/' . $event['banner_path'], ENT_QUOTES, 'UTF-8') ?>" alt="">
            <?php endif; ?>
            <div class="event-detail-body">
                <div class="event-detail-heading">
                    <span class="event-status event-status--<?= strtolower($status) ?>"><?= $status ?></span>
                    <h1><?= htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="event-organizer">Organized by <?= htmlspecialchars($event['organizer_name'] ?? 'Treenity', ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <?php if ($message !== ''): ?>
                    <p class="event-message <?= htmlspecialchars($message_class, ENT_QUOTES, 'UTF-8') ?>" role="<?= $message_class === 'error-message' ? 'alert' : 'status' ?>">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <p class="event-description"><?= nl2br(htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8')) ?></p>

                <dl class="event-details">
                    <div><dt>Start time</dt><dd><?= htmlspecialchars($start_time->format('d M Y, g:i A'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <div><dt>End time</dt><dd><?= htmlspecialchars($end_time->format('d M Y, g:i A'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                </dl>

                <?php if ($is_user && !$is_registered && $status !== 'Ended'): ?>
                    <form method="post" onsubmit="return confirm('Register for this event?');">
                        <button class="register-button" type="submit">Register Now!</button>
                    </form>
                <?php elseif ($is_registered): ?>
                    <p class="registered-message">You are registered for this event.</p>
                <?php elseif (!$is_user): ?>
                    <p class="event-login-message">Log in as a user to register for this event.</p>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>