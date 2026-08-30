<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("database.php");


function save_submitted_quests($conn, $event_id)
{
    $types = $_POST['quest_type'] ?? [];
    $descriptions = $_POST['quest_description'] ?? [];
    $requirements = $_POST['quest_requirement'] ?? [];
    $rewards = $_POST['quest_reward_points'] ?? [];

    foreach ($types as $index => $type) {

        $type = trim($type);
        $description = trim($descriptions[$index] ?? '');
        $requirement = (int)($requirements[$index] ?? 0);
        $reward_points = (int)($rewards[$index] ?? 0);

        if ($type === '' || $requirement <= 0) {
            continue;
        }

        $conn->execute_query(
            "INSERT INTO quests
            (event_id, name, description, type, requirement, reward_points)
            VALUES (?, ?, ?, ?, ?, ?)",
            [
                $event_id,
                $type,
                $description,
                $type,
                $requirement,
                $reward_points
            ]
        );
    }
}


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANIZER') {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$edit_mode = $event_id > 0;

$name = '';
$description = '';
$start_time = '';
$end_time = '';
$banner_id = null;
$existing_quests = [];

if ($edit_mode) {

    $result = $conn->execute_query(
        "SELECT event_id, banner_id, name, description, start_time, end_time
        FROM events
        WHERE event_id = ? AND organizer_id = ?",
        [$event_id, $organizer_id]
    );

    $event = $result->fetch_assoc();

    if (!$event) {
        header("Location: eo_events.php");
        exit();
    }

    $name = $event['name'] ?? '';
    $description = $event['description'] ?? '';
    $start_time = $event['start_time'] ?? '';
    $end_time = $event['end_time'] ?? '';
    $banner_id = $event['banner_id'] ?? null;

    if ($start_time) {
        $start_time = date('Y-m-d\TH:i', strtotime($start_time));
    }

    if ($end_time) {
        $end_time = date('Y-m-d\TH:i', strtotime($end_time));
    }

    $quests_result = $conn->execute_query(
        "SELECT quest_id, name, description, type, requirement, reward_points
        FROM quests
        WHERE event_id = ?
        ORDER BY quest_id DESC",
        [$event_id]
    );
    $existing_quests = $quests_result->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $banner_id = $edit_mode ? ($event['banner_id'] ?? null) : null;
    $action = $_POST['action'] ?? 'create';

    if (!empty($_POST['remove_banner'])) {
        $banner_id = null;
    }

    if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $banner_id = create_image('banner', $_FILES['banner']);
        } else {
            $error = 'The banner upload failed.';
        }
    }
   

    if ($action === 'draft') {

        if ($error ?? null) {

        } elseif ($edit_mode) {

            $conn->execute_query(
                "UPDATE events
                SET banner_id = ?, name = ?, description = ?, start_time = ?, end_time = ?
                WHERE event_id = ? AND organizer_id = ?",
                [
                    $banner_id,
                    $name,
                    $description,
                    $start_time !== '' ? $start_time : null,
                    $end_time !== '' ? $end_time : null,
                    $event_id,
                    $organizer_id
                ]
            );

            save_submitted_quests($conn, $event_id);

        } else {

            $conn->execute_query(
                "INSERT INTO events
                (organizer_id, banner_id, name, description, start_time, end_time)
                VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $organizer_id,
                    $banner_id,
                    $name,
                    $description,
                    $start_time !== '' ? $start_time : null,
                    $end_time !== '' ? $end_time : null
                ]
            );

            save_submitted_quests($conn, $conn->insert_id);
        }

        header("Location: eo_events.php?draft_saved=1");
        exit();
    }

    if ($name === '') {

        $error = "Event name is required.";

    } elseif ($start_time === '') {

        $error = "Start time is required.";

    } elseif ($end_time === '') {

        $error = "End time is required.";

    } elseif (strtotime($end_time) <= strtotime($start_time)) {

        $error = "End time must be after the start time.";

    } else {

        if ($edit_mode) {

            $conn->execute_query(
                "UPDATE events
                SET banner_id = ?, name = ?, description = ?, start_time = ?, end_time = ?
                WHERE event_id = ? AND organizer_id = ?",
                [
                    $banner_id,
                    $name,
                    $description,
                    $start_time,
                    $end_time,
                    $event_id,
                    $organizer_id
                ]
            );

            save_submitted_quests($conn, $event_id);

        } else {

            $conn->execute_query(
                "INSERT INTO events
                (organizer_id, banner_id, name, description, start_time, end_time)
                VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $organizer_id,
                    $banner_id,
                    $name,
                    $description,
                    $start_time,
                    $end_time
                ]
            );

            save_submitted_quests($conn, $conn->insert_id);
        }

        header("Location: eo_events.php?created=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $edit_mode ? 'Edit Event' : 'Create Event'; ?>
    </title>

    <style>

        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        h1 {
            font-family: Georgia, 'Times New Roman', serif;
            color: #1b4332;
            margin-bottom: 25px;
        }

        .form-card {
            background: #fff;
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #1b4332;
        }

        input,
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #d8cfc0;
            border-radius: 6px;
            font-size: 14px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .banner-upload {
            border: 2px dashed #d8cfc0;
            border-radius: 8px;
            background: #faf8f3;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            position: relative;
            transition: border-color 0.15s, background 0.15s;
        }

        .banner-upload:hover,
        .banner-upload.dragover {
            border-color: #1b4332;
            background: #f2f5ee;
        }

        .banner-upload input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .banner-upload-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .banner-upload-text {
            font-size: 14px;
            color: #1b4332;
            font-weight: 600;
        }

        .banner-upload-hint {
            font-size: 12px;
            color: #8a8272;
            margin-top: 4px;
        }

        .banner-preview-wrap {
            margin-bottom: 12px;
            display: none;
        }

        .banner-preview-wrap.show {
            display: block;
        }

        .banner-preview-wrap img {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0dacd;
            display: block;
            margin-bottom: 8px;
        }

        .banner-remove-btn {
            background: #ece7dc;
            color: #842029;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .banner-remove-btn:hover {
            background: #e2d9c8;
        }

        .quest-section-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 17px;
            color: #1b4332;
            margin: 30px 0 12px;
        }

        .quest-row {
            border: 1px solid #e0dacd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            background: #faf8f3;
        }

        .quest-row-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .quest-row-label {
            font-size: 13px;
            font-weight: 700;
            color: #1b4332;
        }

        .quest-remove-btn {
            background: none;
            border: none;
            color: #b42318;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
        }

        .quest-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .quest-fields .full-width {
            grid-column: 1 / -1;
        }

        .quest-fields .form-group {
            margin-bottom: 0;
        }

        .btn-add-quest {
            background: #ece7dc;
            color: #1b4332;
            border: 1px dashed #b9ae94;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        .btn-add-quest:hover {
            background: #e2d9c8;
        }

        .existing-quests {
            margin-bottom: 18px;
        }

        .existing-quest-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e0dacd;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .existing-quest-item .eq-type {
            font-weight: 700;
            color: #1b4332;
        }

        .existing-quest-item .eq-meta {
            color: #8a8272;
        }

        .existing-quests-hint {
            font-size: 12px;
            color: #8a8272;
            margin: -8px 0 14px;
        }

        .existing-quests-hint a {
            color: #1b4332;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-draft {
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: #1b4332;
        }

        .btn-primary:hover {
            background: #2d6a4f;
        }

        .btn-draft {
            background: #92620c;
        }

        .btn-draft:hover {
            background: #7a5108;
        }

        .btn-secondary {
            background: #ece7dc;
            color: #1b4332;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .error-box {
            background: #f8d7da;
            border: 1px solid #e5aeb5;
            color: #842029;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {

    * {
        box-sizing: border-box;
    }

    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .content,
    .container,
    .main-content {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 20px 15px;
    }

    h1 {
        font-size: 25px;
        line-height: 1.3;
        margin-bottom: 18px;
    }

    h2 {
        font-size: 21px;
    }

    h3 {
        font-size: 18px;
    }

    .card,
    .form-card,
    .event-card,
    .panel,
    .section-card {
        width: 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }

    input,
    select,
    textarea {
        width: 100%;
        max-width: 100%;
        font-size: 16px;
    }

    .banner-upload {
        padding: 24px 15px;
    }

    .banner-preview-wrap img {
        max-height: 180px;
    }

    .quest-fields {
        grid-template-columns: 1fr;
    }

    textarea {
        min-height: 110px;
    }

    button,
    .btn,
    .btn-primary,
    .btn-secondary,
    .btn-danger,
    .btn-success {
        min-height: 44px;
        max-width: 100%;
    }

    .actions,
    .button-group,
    .form-actions {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 10px;
    }

    .actions button,
    .actions a,
    .button-group button,
    .button-group a,
    .form-actions button,
    .form-actions a {
        width: 100%;
        text-align: center;
    }

    .grid,
    .cards,
    .event-grid,
    .stats-grid,
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr !important;
        gap: 15px;
    }

    .stats,
    .statistics {
        display: grid;
        grid-template-columns: 1fr !important;
        gap: 12px;
    }

    table {
        width: 100%;
        min-width: 650px;
    }

    .table-container,
    .table-responsive,
    .participants-table,
    .responsive-table {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .event-card img,
    .card img,
    .banner,
    .event-image {
        width: 100%;
        height: auto;
        max-width: 100%;
        object-fit: cover;
    }

    .modal,
    .modal-content {
        width: calc(100% - 30px);
        max-width: 100%;
        margin: 15px auto;
    }

    .modal-body {
        max-height: 80vh;
        overflow-y: auto;
    }

    .search,
    .search-box,
    .filter,
    .filter-box {
        width: 100%;
        max-width: 100%;
    }

    .search input,
    .search-box input,
    .filter select {
        width: 100%;
    }

    .profile,
    .participant-details,
    .event-details,
    .quest-details {
        width: 100%;
        max-width: 100%;
    }

    .row,
    .form-row,
    .detail-row {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 12px;
    }

    .col,
    .form-col,
    .detail-col {
        width: 100%;
        max-width: 100%;
    }

    .quest-card,
    .participant-card {
        width: 100%;
        padding: 15px;
    }

    .quest-actions,
    .participant-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .quest-actions button,
    .quest-actions a,
    .participant-actions button,
    .participant-actions a {
        width: 100%;
    }

    .alert,
    .error-box,
    .success-box {
        width: 100%;
        max-width: 100%;
        overflow-wrap: break-word;
    }
}

@media (max-width: 480px) {

    .content,
    .container,
    .main-content {
        padding: 15px 12px;
    }

    h1 {
        font-size: 22px;
    }

    h2 {
        font-size: 19px;
    }

    h3 {
        font-size: 17px;
    }

    .card,
    .form-card,
    .event-card,
    .panel,
    .section-card {
        padding: 15px;
        border-radius: 8px;
    }

    input,
    select,
    textarea {
        padding: 11px;
    }

    button,
    .btn,
    .btn-primary,
    .btn-secondary {
        width: 100%;
    }

    table {
        font-size: 13px;
    }

    th,
    td {
        padding: 8px;
        white-space: nowrap;
    }
}

    </style>

    <?php include("global.php"); ?>

</head>

<body>

    <?php include("header.php"); ?>

    <main class="content">

        <h1>
            <?php echo $edit_mode ? 'Edit Event' : 'Create Event'; ?>
        </h1>

        <?php if (isset($error)) { ?>

            <div class="error-box">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <div class="form-card">

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">

                    <label for="name">
                        Event Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                    ><?php echo htmlspecialchars($description); ?></textarea>

                </div>

                <div class="form-group">
                    <label for="banner">Banner image</label>

                    <div class="banner-preview-wrap<?php echo ($edit_mode && $banner_id) ? ' show' : ''; ?>" id="bannerPreviewWrap">
                        <img
                            id="bannerPreviewImg"
                            src="<?php echo ($edit_mode && $banner_id) ? htmlspecialchars(get_image_path($banner_id)) : ''; ?>"
                            alt="Banner preview"
                        >
                        <button type="button" class="banner-remove-btn" id="bannerRemoveBtn">Remove Banner</button>
                    </div>

                    <input type="hidden" name="remove_banner" id="removeBannerField" value="">

                    <div class="banner-upload" id="bannerUpload">
                        <div class="banner-upload-icon">🖼️</div>
                        <div class="banner-upload-text" id="bannerUploadText">Click or drag an image here to upload</div>
                        <div class="banner-upload-hint">JPEG, PNG, GIF or WebP</div>
                        <input id="banner" name="banner" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                </div>

                <script>
                (function () {
                    var uploadBox = document.getElementById('bannerUpload');
                    var fileInput = document.getElementById('banner');
                    var previewWrap = document.getElementById('bannerPreviewWrap');
                    var previewImg = document.getElementById('bannerPreviewImg');
                    var uploadText = document.getElementById('bannerUploadText');
                    var removeBtn = document.getElementById('bannerRemoveBtn');
                    var removeField = document.getElementById('removeBannerField');

                    function showPreview(file) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            previewImg.src = e.target.result;
                            previewWrap.classList.add('show');
                        };
                        reader.readAsDataURL(file);
                        uploadText.textContent = file.name;
                        removeField.value = '';
                    }

                    fileInput.addEventListener('change', function () {
                        if (fileInput.files && fileInput.files[0]) {
                            showPreview(fileInput.files[0]);
                        }
                    });

                    uploadBox.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        uploadBox.classList.add('dragover');
                    });

                    uploadBox.addEventListener('dragleave', function () {
                        uploadBox.classList.remove('dragover');
                    });

                    uploadBox.addEventListener('drop', function (e) {
                        e.preventDefault();
                        uploadBox.classList.remove('dragover');
                        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                            fileInput.files = e.dataTransfer.files;
                            showPreview(e.dataTransfer.files[0]);
                        }
                    });

                    removeBtn.addEventListener('click', function () {
                        fileInput.value = '';
                        previewWrap.classList.remove('show');
                        previewImg.src = '';
                        uploadText.textContent = 'Click or drag an image here to upload';
                        removeField.value = '1';
                    });
                })();
                </script>

                <div class="form-group">

                    <label for="start_time">
                        Start Time
                    </label>

                    <input
                        type="datetime-local"
                        id="start_time"
                        name="start_time"
                        value="<?php echo htmlspecialchars($start_time); ?>"
                    >

                </div>

                <div class="form-group">

                    <label for="end_time">
                        End Time
                    </label>

                    <input
                        type="datetime-local"
                        id="end_time"
                        name="end_time"
                        value="<?php echo htmlspecialchars($end_time); ?>"
                    >

                </div>

                <div class="quest-section-title">Quests</div>

                <?php if ($edit_mode && count($existing_quests) > 0): ?>
                    <div class="existing-quests">
                        <?php foreach ($existing_quests as $quest): ?>
                            <div class="existing-quest-item">
                                <div>
                                    <span class="eq-type"><?php echo htmlspecialchars($quest['type']); ?></span>
                                    &nbsp;&mdash;&nbsp;
                                    <span class="eq-meta">Requires <?php echo (int)$quest['requirement']; ?>, rewards <?php echo (int)$quest['reward_points']; ?> pts</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="existing-quests-hint">
                        To edit or delete existing quests, use the <a href="eo_questcustomiser.php?event_id=<?php echo (int)$event_id; ?>">Quest Customiser</a>.
                    </p>
                <?php endif; ?>

                <div id="questRows"></div>

                <button type="button" class="btn-add-quest" id="addQuestBtn">+ Add a Quest</button>

                <template id="questRowTemplate">
                    <div class="quest-row">
                        <div class="quest-row-top">
                            <span class="quest-row-label">New Quest</span>
                            <button type="button" class="quest-remove-btn">Remove</button>
                        </div>
                        <div class="quest-fields">
                            <div class="form-group">
                                <label>Quest Type</label>
                                <select name="quest_type[]">
                                    <option value="">Select quest type</option>
                                    <option value="LOG_TOTAL">LOG_TOTAL</option>
                                    <option value="LOG_STREAK">LOG_STREAK</option>
                                    <option value="HEIGHT">HEIGHT</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Requirement</label>
                                <input type="number" name="quest_requirement[]" min="1" placeholder="e.g. 3">
                            </div>
                            <div class="form-group">
                                <label>Reward Points</label>
                                <input type="number" name="quest_reward_points[]" min="0" placeholder="e.g. 100">
                            </div>
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="quest_description[]" placeholder="Describe this quest"></textarea>
                            </div>
                        </div>
                    </div>
                </template>

                <script>
                (function () {
                    var addBtn = document.getElementById('addQuestBtn');
                    var rowsContainer = document.getElementById('questRows');
                    var template = document.getElementById('questRowTemplate');

                    addBtn.addEventListener('click', function () {
                        var clone = template.content.cloneNode(true);
                        var row = clone.querySelector('.quest-row');

                        clone.querySelector('.quest-remove-btn').addEventListener('click', function () {
                            row.remove();
                        });

                        rowsContainer.appendChild(clone);
                    });
                })();
                </script>

                <div class="actions">

                    <button
                        type="submit"
                        name="action"
                        value="create"
                        class="btn-primary"
                    >
                        <?php echo $edit_mode ? 'Update Event' : 'Create Event'; ?>
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="draft"
                        class="btn-draft"
                    >
                        Save as Draft
                    </button>

                    <a
                        href="eo_events.php"
                        class="btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </main>

    <?php include("footer.php"); ?>

</body>

</html>

<?php ob_end_flush(); ?>