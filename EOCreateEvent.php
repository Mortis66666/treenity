<?php
    session_start();
    require 'database.php';

    if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organiser') {
        header("Location: login.php");
        exit();
    }

    $organizer_id = $_SESSION['user_id'];
    $error= [];
    $success='';

    if($_SERVER['REQUEST_METHOD'] ==='POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $verif_code = trim($_POST['verification_code'] ?? '');

        if ($name === '') $error[] = 'Event name is required.';
        if ($start_time === '') $error[] = 'Start date/time is required.';
        if ($end_time === '') $error[] = 'End date/time is required.';
        if ($start_time && $end_time && $start_time >= $end_time) $error[] = 'End time must be after start time.';
        if ($verif_code === '') $error[] = 'Verification code is required.';

        $banner_path = null;
        if (isset($_FILES['banner']['name'])) {
            $allowed =[ 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $ftype = mine_content_type($_FILES['banner']['tmp_name']);
            if (!in_array($ftype, $allowed)) {
                $error[] = 'Invalid banner file type. Allowed types: JPEG, PNG, GIF, WEBP.';
            } else {
                $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
                $fname = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest = 'image/' . $fname;
                if (!move_uploaded_file($_FILES['banner']['tmp_name'], $dest)) {
                    $stmt = $pdo->prepare("INSERT INTO images (type, path) VALUES ('banner', :path)");
                    $stmt->execute([$dest]);
                    $banner_id = $pdo->lastInsertId();
                }else {
                    $error[] = 'Failed to upload banner image.';
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO events (organizer_id, name, description, start_time, end_time, verification_code, banner_path) VALUES (:organizer_id, :name, :description, :start_time, :end_time, :verification_code, :banner_path)");
            $stmt->execute([
                ':organizer_id' => $organizer_id,
                ':name' => $name,
                ':description' => $description,
                ':start_time' => $start_time,
                ':end_time' => $end_time,
                ':verification_code' => $verif_code,
                ':banner_path' => $banner_path
            ]);
            $new_event_id = $pdo->lastInsertId();
            $success = 'Event created successfully.';
            header("Location: eo_events.php?created=1");
            exit;
        }
    }

    include 'header.php';
?>

<link rel="stylesheet" href="style/global.\css">
<style>
    .eo-wrap {
        max-width:800px;
        margin: 30px;
        padding: 0 20px;
    }
    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 6px;
    }
    .page-sub{
        font-size: 14px;
        color: #fff;
        margin-bottom: 20px;
    }
    .card{
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px;
        padding: 24px;
        margin-bottom: 18px;
    }
    .card-title{
        font-size: 13px;
        font-weight: 600;
        color: #6b7a99;
        margin-bottom: 16px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .form-group{
        margin-bottom: 16px;
    }
    label{
        display: block;
        font-size: 12px;
        color: #6b7a99;
        margin-bottom: 5px;
        font-weight: 600;
    }
    input[type="text"], input[type="datetime-local"], textarea, select{
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #2a3a50;
        border-radius: 6px;
        background: #111827;
        color: #c8d4e8;
        font-size: 13px;
        box-sizing: border-box;
    }
    input:focus, textarea:focus{
        outline: none;
        border-color: #2563eb;
    }
    textarea{
        resize: vertical;
        min-height: 80px;
    }
    .two-col{
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .btn-row{
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .btn-primary{
        background: #1a2236;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 7px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }
    .btn-primary:hover{
        background: #1648c0;
    }
    .btn-secondary{
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 7px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }
    .btn-secondary:hover{
        background: #22304a;
    }
    .error-box {
        background: #450a0a;
        border: 1px solid #7f1d1d;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 18px;
    }
    .error-box p{
        color: #fca5a5;
        font-size: 13px;
        margin: 3px 0;
    }
    .upload-area{
        border: 1px dashed #2a3a50;
        border-radius: 6px;
        padding: 20px;
        text-align: center;
        color: #6b7a99;
        font-size: 13px;
        cursor: pointer;
        position: relative;
    }
    .upload-area input{
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .upload-area:hover{
        background: #2563eb;
    }
    #preview-img{
        max-width: 100%;
        max-height: 140px;
        border-radius: 6px;
        margin-top: 10px;
        display: none;
    }
    @media (max-width: 600px) {
        .two-col{
            grid-template-columns: 1fr;
        }
        .btn-row{
            flex-direction: column;
        }
    }
</style>

<div class="eo-wrap">
    <div class="Create Events">Create Events</div>
    <div class="page-sub">Fill in the details below to create a new event.</div>

    <?php if (!empty($error)): ?>
        <div class="error-box">
            <?php foreach ($error as $e): ?><p>&#x26A0; <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="card">
            <div class="card-title">Event Details</div>
            <div class="form-group">
                <label for="name">Event Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars($description ?? '') ?></textarea>
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label for="start_time">Start Date/Time</label>
                    <input type="datetime-local" id="start_time" name="start_time" value="<?= htmlspecialchars($start_time ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_time">End Date/Time</label>
                    <input type="datetime-local" id="end_time" name="end_time" value="<?= htmlspecialchars($end_time ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="verification_code">Verification Code</label>
                <input type="text" id="verification_code" name="verification_code" value="<?= htmlspecialchars($verif_code ?? '') ?>" required>
            </div>
            <div class="card">
                <div class="card-title">Event Banner</div>
                <div class="upload-area" id="upload-area">
                    <input type="file" id="banner" name="banner" accept="image/*">
                    <div id="upload-text">Click to upload a banner image (JPG, PNG, GIF, WEBP)</div>
                    <img id="preview-img" src="#" alt="Banner Preview">
                </div>
            </div>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn-primary">Create Event</button>
            <a href="eo_events.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    document.getElementById('banner').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('preview-img');
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                document.getElementById('upload-text').style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const start = document.getElementById('start_time').value;
        const end = document.getElementById('end_time').value;
        if (start && end && start >= end) {
            e.preventDefault();
            alert('End time must be after start time.');
        }
    });
</script>
<?php include 'footer.php'; ?>