<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANIZER') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$errors = array();

// If we're continuing an existing draft, event_id will be in the URL.
// We only ever load it here if it belongs to this organiser AND is still a draft.
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$draft_event = null;

if ($event_id > 0) {
    $draft_result = $conn->execute_query("SELECT * FROM events WHERE event_id = ? AND organizer_id = ? AND status = 'draft'", [$event_id, $organizer_id]);
    $draft_event = $draft_result->fetch_assoc();
    if (!$draft_event) {
        $event_id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $action = isset($_POST['action']) ? $_POST['action'] : 'publish';
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $verification_code = trim($_POST['verification_code']);

    if ($action == 'publish') {
        // Full validation - a published event needs everything filled in.
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
    } else {
        // Draft - only the name is required so the organiser can find it again later.
        if ($name == '') {
            $errors[] = "Please give your draft a name so you can find it later.";
        }
        if ($start_time != '' && $end_time != '' && $start_time >= $end_time) {
            $errors[] = "End time must be after start time.";
        }
    }

    $banner_id = ($draft_event && $draft_event['banner_id']) ? $draft_event['banner_id'] : null;

    if (isset($_FILES['banner']) && $_FILES['banner']['name'] != '') {
        $file_type = $_FILES['banner']['type'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Banner must be an image file (JPG, PNG, GIF, WEBP).";
        } else {
            $conn->execute_query("INSERT INTO images (type) VALUES ('banner')");
            $banner_id = $conn->insert_id;

            $target_path = "images/banner/" . $banner_id . ".png";
            move_uploaded_file($_FILES['banner']['tmp_name'], $target_path);
        }
    }

    if (count($errors) == 0) {
        $status = ($action == 'publish') ? 'published' : 'draft';

        if ($event_id > 0) {
            // Updating an existing draft (either saving it again as a draft, or publishing it now).
            $conn->execute_query("UPDATE events SET banner_id = ?, name = ?, description = ?, verification_code = ?, start_time = ?, end_time = ?, status = ? WHERE event_id = ? AND organizer_id = ?", [$banner_id, $name, $description, $verification_code, $start_time, $end_time, $status, $event_id, $organizer_id]);
        } else {
            // Brand new event or brand new draft.
            $conn->execute_query("INSERT INTO events (banner_id, organizer_id, name, description, verification_code, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [$banner_id, $organizer_id, $name, $description, $verification_code, $start_time, $end_time, $status]);
        }

        if ($status == 'draft') {
            header("Location: eo_events.php?draft_saved=1");
        } else {
            header("Location: eo_events.php?created=1");
        }
        exit;
    }
}

// Helper to decide what value to show in each field:
// 1. Whatever was just typed in (if the form failed validation)
// 2. Otherwise, the saved draft's value (if we're continuing a draft)
// 3. Otherwise, blank
function field_value($post_key, $draft_key, $draft_event) {
    if (isset($_POST[$post_key])) {
        return $_POST[$post_key];
    }
    if ($draft_event) {
        return $draft_event[$draft_key];
    }
    return '';
}

$name_value = field_value('name', 'name', $draft_event);
$description_value = field_value('description', 'description', $draft_event);
$verification_code_value = field_value('verification_code', 'verification_code', $draft_event);

if (isset($_POST['start_time'])) {
    $start_time_value = $_POST['start_time'];
} else if ($draft_event) {
    $start_time_value = str_replace(' ', 'T', substr($draft_event['start_time'], 0, 16));
} else {
    $start_time_value = '';
}

if (isset($_POST['end_time'])) {
    $end_time_value = $_POST['end_time'];
} else if ($draft_event) {
    $end_time_value = str_replace(' ', 'T', substr($draft_event['end_time'], 0, 16));
} else {
    $end_time_value = '';
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

.draft-notice {
    background: #fbeacb;
    border: 1px solid #e0b76b;
    color: #92620c;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
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

.btn-draft {
    background: #fff;
    color: #1b4332;
    border: 1px solid #1b4332;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 20px;
    margin-right: 10px;
}

.btn-draft:hover {
    background: #f4f1ea;
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

        <h1><?php echo $draft_event ? "Continue Draft" : "Create Event"; ?></h1>
        <p class="page-sub">
            <?php if ($draft_event) { ?>
                Finish the details below, then publish or save your changes as a draft again.
            <?php } else { ?>
                Fill in the details below to create a new event. You don't have to finish now &mdash; you can save your progress as a draft.
            <?php } ?>
        </p>

        <?php if ($draft_event) { ?>
        <div class="draft-notice">You're editing a saved draft. It won't be visible to participants until you publish it.</div>
        <?php } ?>

        <?php if (count($errors) > 0) { ?>
        <div class="error-box">
            <?php foreach ($errors as $error) { ?>
                <p><?php echo $error; ?></p>
            <?php } ?>
        </div>
        <?php } ?>

        <form method="POST" action="eo_create_event.php" enctype="multipart/form-data">

            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

            <div class="section-box">
                <h2>Event Details</h2>

                <label for="name">Event Name</label>
                <input type="text" id="name" name="name" maxlength="100"
                    value="<?php echo htmlspecialchars($name_value); ?>">

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($description_value); ?></textarea>

                <label for="start_time">Start Date and Time</label>
                <input type="datetime-local" id="start_time" name="start_time"
                    value="<?php echo $start_time_value; ?>">

                <label for="end_time">End Date and Time</label>
                <input type="datetime-local" id="end_time" name="end_time"
                    value="<?php echo $end_time_value; ?>">

                <label for="verification_code">Verification Code</label>
                <input type="text" id="verification_code" name="verification_code" maxlength="20"
                    value="<?php echo htmlspecialchars($verification_code_value); ?>">
            </div>

            <div class="section-box">
                <h2>Banner Image</h2>
                <?php if ($draft_event && $draft_event['banner_id']) { ?>
                    <p style="font-size:13px;color:#6b6355;margin:0 0 8px 0;">A banner is already saved for this draft. Choose a new file only if you want to replace it.</p>
                <?php } ?>
                <input type="file" name="banner" accept="image/*">
            </div>

            <button type="submit" name="action" value="publish" class="btn-primary">Publish Event</button>
            <button type="submit" name="action" value="draft" class="btn-draft">Save as Draft</button>
            <a href="eo_events.php" class="btn-secondary">Cancel</a>

        </form>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>