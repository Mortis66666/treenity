<?php
include_once("database.php");
include_once("check_user.php");

check_user_role(['ADMIN']);

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$event_id = $event_id ?: filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$event_id) {
    header("Location: not_found.php");
    exit();
}

$csrf_token = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $message = 'The request could not be verified. Please try again.';
        $message_class = 'error-message';
    } else {
        try {
            $conn->begin_transaction();

            $event_result = $conn->execute_query(
                "SELECT organizer_id FROM events WHERE event_id = ? FOR UPDATE",
                [$event_id]
            );
            $event_to_approve = $event_result->fetch_assoc();

            if (!$event_to_approve) {
                throw new RuntimeException('The event could not be found.');
            }

            $conn->execute_query(
                "UPDATE users SET role = 'ORGANIZER' WHERE user_id = ? AND role = 'USER'",
                [$event_to_approve['organizer_id']]
            );
            $conn->commit();

            $message = 'Event approved. The proposer can now continue and publish this draft.';
            $message_class = 'success-message';
        } catch (Throwable $exception) {
            $conn->rollback();
            $message = 'Unable to approve the event.';
            $message_class = 'error-message';
        }
    }
}

$event_result = $conn->execute_query(
    "SELECT e.event_id, e.name, e.description, e.start_time, e.end_time, e.is_published,
            u.username AS proposer_name, u.user_id AS proposer_id
     FROM events e
     INNER JOIN users u ON u.user_id = e.organizer_id
     WHERE e.event_id = ?",
    [$event_id]
);
$event = $event_result->fetch_assoc();

if (!$event) {
    header("Location: not_found.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Approval</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/event_approval.css">


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content event-approval-page">
        <div class="page-title-bar">
            <h1>Event Approval</h1>
        </div>


        <?php if ($message !== ''): ?>
            <p class="<?= htmlspecialchars($message_class, ENT_QUOTES, 'UTF-8') ?>" role="<?= $message_class === 'error-message' ? 'alert' : 'status' ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <article class="event-approval-detail">
            <div class="event-approval-body">
                <div class="event-approval-heading">
                    <span class="event-approval-status">Awaiting approval</span>
                    <h2><?= htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="event-approval-proposer">Proposed by <?= htmlspecialchars($event['proposer_name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <p class="event-approval-description"><?= nl2br(htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8')) ?></p>

                <dl class="event-approval-details">
                    <div>
                        <dt>Start time</dt>
                        <dd><?= htmlspecialchars($event['start_time'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>End time</dt>
                        <dd><?= htmlspecialchars($event['end_time'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>

                <?php if (($event['is_published'] ?? 0) === 0): ?>
                    <form method="post">
                        <input type="hidden" name="event_id" value="<?= (int) $event['event_id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="approve-button" type="submit">Approve event</button>
                    </form>
                <?php else: ?>
                    <p class="event-approval-complete">This event has already been approved.</p>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>