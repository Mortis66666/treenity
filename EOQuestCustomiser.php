<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ORGANIZER') {
    header('Location: login.php');
    exit;
}

$organizer_id = $_SESSION['user_id'];
$success = '';
$errors  = [];

$stmt = $pdo->prepare("SELECT event_id, name FROM events WHERE organizer_id = ? ORDER BY start_time DESC");
$stmt->execute([$organizer_id]);
$my_events = $stmt->fetchAll();

$selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : ($my_events[0]['event_id'] ?? null);

if ($selected_event_id) {
    $stmt = $pdo->prepare("SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?");
    $stmt->execute([$selected_event_id, $organizer_id]);
    if (!$stmt->fetch()) $selected_event_id = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $event_id      = (int)$_POST['event_id'];
        $type          = trim($_POST['type'] ?? '');
        $requirement   = (int)($_POST['requirement'] ?? 0);
        $reward_points = (int)($_POST['reward_points'] ?? 0);

        if ($type === '')        $errors[] = 'Quest type is required.';
        if ($requirement <= 0)  $errors[] = 'Requirement must be greater than 0.';
        if ($reward_points <= 0) $errors[] = 'Reward points must be greater than 0.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO quests (event_id, type, requirement, reward_points) VALUES (?, ?, ?, ?)");
            $stmt->execute([$event_id, $type, $requirement, $reward_points]);
            $success = 'Quest created successfully!';
            $selected_event_id = $event_id;
        }
    }

    if ($_POST['action'] === 'delete') {
        $quest_id = (int)$_POST['quest_id'];
        
        $stmt = $pdo->prepare("
            DELETE q FROM quests q
            JOIN events e ON q.event_id = e.event_id
            WHERE q.quest_id = ? AND e.organizer_id = ?
        ");
        $stmt->execute([$quest_id, $organizer_id]);
        $success = 'Quest deleted.';
    }
}


$quests = [];
if ($selected_event_id) {
    $stmt = $pdo->prepare("SELECT * FROM quests WHERE event_id = ? ORDER BY quest_id ASC");
    $stmt->execute([$selected_event_id]);
    $quests = $stmt->fetchAll();
}

$quest_types = ['CHECK_IN', 'PHOTO', 'DISTANCE', 'LOG', 'COMPLETION'];

include 'header.php';
?>

<link rel="stylesheet" href="styles/global.css">
<style>
    .eo-wrap {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 20px;
    }
    .page-title {
        font-size: 22px;
        font-weight: 700; color:
        #fff;
        margin-bottom: 20px;
    }
    .two-col {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 16px;
    }
    .card {
        background: #1a2236;
        border: 1px solid #2a3a50;
        border-radius: 10px; padding: 18px;
        margin-bottom: 16px;
    }
    .card-title {
        font-size: 13px;
        font-weight: 600;
        color: #6b7a99;
        margin-bottom: 14px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    label {
        display: block;
        font-size: 12px;
        color: #6b7a99;
        margin-bottom: 5px;
        font-weight: 600;
    }
    select, input[type="number"], input[type="text"] {
        width: 100%; padding: 9px 12px; background: #111827;
        border: 1px solid #2a3a50; border-radius: 6px;
        color: #c8d4e8; font-size: 13px; box-sizing: border-box; margin-bottom: 12px;
    }
    select:focus, input:focus {
        border-color: #2563eb;
        outline: none;
    }
    .btn-primary {
        background: #1a56db;
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
    }
    .btn-primary:hover {
        background: #1648c0;
    }
    .quest-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #111827;
        border: 1px solid #2a3a50;
        border-radius: 7px;
        margin-bottom: 7px;
    }
    .quest-item:last-child {
        margin: 0;}
    .quest-icon {
        font-size: 18px;
        width: 30px;
        text-align: center;
    }
    .quest-info {
        flex: 1;
    }
    .quest-type {
        font-size: 12px;
        font-weight: 700;
        color: #fff;
    }
    .quest-meta {
        font-size: 11px;
        color: #6b7a99;
        margin-top: 2px;
    }
    .btn-del {
        background: #450a0a;
        color: #fca5a5; border:
        none; border-radius: 5px;
        padding: 5px 10px;
        font-size: 11px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-del:hover {
        background: #7f1d1d;
    }
    .empty-state {
        color: #6b7a99;
        font-size: 13px;
        text-align: center;
        padding: 20px 0;
    }
    .alert-success {
        background: #14532d;
        border: 1px solid #166534;
        color: #86efac;
        padding: 10px 16px;
        border-radius: 7px;
        margin-bottom: 14px;
        font-size: 13px;
    }
    .alert-error {
        background: #450a0a;
        border: 1px solid #7f1d1d;
        color: #fca5a5;
        padding: 10px 16px;
        border-radius: 7px;
        margin-bottom: 14px;
        font-size: 13px;
    }
    .event-select-wrap {
        margin-bottom: 20px;
        display: flex; gap: 10px;
        align-items: center;
    }
    .event-select-wrap select {
        margin: 0;
    }
    .type-icon {
        CHECK_IN: '✅';}
    .pts-badge {
        background: #1e3a5f;
        color: #93c5fd;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    @media (max-width: 700px) { .two-col { grid-template-columns: 1fr; } }
</style>

<div class="eo-wrap">
    <div class="page-title">Quest Customizer</div>

    <?php if ($success): ?><div class="alert-success">&#10003; <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="alert-error">&#x26A0; <?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <div class="event-select-wrap">
        <label style="margin:0;white-space:nowrap;font-size:13px;color:#c8d4e8;">Select Event:</label>
        <form method="GET" style="display:flex;gap:8px;flex:1">
            <select name="event_id" onchange="this.form.submit()" style="margin:0">
                <option value="">-- Choose event --</option>
                <?php foreach ($my_events as $ev): ?>
                <option value="<?= $ev['event_id'] ?>" <?= $selected_event_id == $ev['event_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!$selected_event_id): ?>
        <div class="empty-state" style="padding:40px">Please select an event to manage quests.</div>
    <?php else: ?>

    <div class="two-col">

        <div>
            <div class="card">
                <div class="card-title">+ Create New Quest</div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">

                    <label>Quest Type *</label>
                    <select name="type" required>
                        <option value="">-- Select quest type --</option>
                        <?php foreach ($quest_types as $qt): ?>
                        <option value="<?= $qt ?>" <?= (($_POST['type'] ?? '') === $qt) ? 'selected' : '' ?>>
                            <?= $qt ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Requirement (numeric) *</label>
                    <input type="number" name="requirement" min="1"
                        value="<?= htmlspecialchars($_POST['requirement'] ?? '') ?>"
                        placeholder="e.g. 3 (photos needed)">

                    <label>Reward Points *</label>
                    <input type="number" name="reward_points" min="1"
                        value="<?= htmlspecialchars($_POST['reward_points'] ?? '') ?>"
                        placeholder="e.g. 100">

                    <button type="submit" class="btn-primary">Create Quest</button>
                </form>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-title">Quests for this Event</div>
                <?php if (empty($quests)): ?>
                    <div class="empty-state">No quests yet. Create one on the left.</div>
                <?php else: ?>
                    <?php
                    $icons = [
                        'CHECK_IN'   => '✅',
                        'PHOTO'      => '📸',
                        'DISTANCE'   => '🏃',
                        'LOG'        => '📝',
                        'COMPLETION' => '🏆',
                    ];
                    foreach ($quests as $q): ?>
                    <div class="quest-item">
                        <div class="quest-icon"><?= $icons[$q['type']] ?? '⭐' ?></div>
                        <div class="quest-info">
                            <div class="quest-type"><?= htmlspecialchars($q['type']) ?></div>
                            <div class="quest-meta">
                                Requirement: <strong><?= $q['requirement'] ?></strong>
                                &nbsp;|&nbsp;
                                Reward: <span class="pts-badge"><?= $q['reward_points'] ?> pts</span>
                            </div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Delete this quest?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="quest_id" value="<?= $q['quest_id'] ?>">
                            <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                            <button type="submit" class="btn-del">Delete</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>