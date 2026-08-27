<?php
include_once("database.php");
include_once("check_user.php");

$user_id = (int) $_SESSION['user_id'];
$log_date_result = $conn->execute_query(
    "SELECT COLUMN_NAME AS column_name
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs'
       AND COLUMN_NAME IN ('created_at', 'logged_at', 'date_created')
     ORDER BY FIELD(COLUMN_NAME, 'created_at', 'logged_at', 'date_created')
     LIMIT 1"
);
$log_date_row = $log_date_result->fetch_assoc();
$log_date_column = $log_date_row['column_name'] ?? null;

$quest_result = $conn->execute_query(
    "SELECT q.quest_id, q.name, q.description, q.type, q.requirement, q.reward_points, q.quest_icon_id,
            e.event_id, e.name AS event_name,
            p.participant_id, COALESCE(qp.value, 0) AS stored_progress
     FROM quests q
     INNER JOIN events e ON e.event_id = q.event_id
     INNER JOIN participants p ON p.event_id = e.event_id AND p.user_id = ?
     LEFT JOIN quest_progress qp
        ON qp.quest_id = q.quest_id AND qp.participant_id = p.participant_id
     ORDER BY e.start_time DESC, q.quest_id ASC",
    [$user_id]
);

$quests = $quest_result->fetch_all(MYSQLI_ASSOC);
$participant_ids = array_values(array_unique(array_column($quests, 'participant_id')));
$log_metrics = [];
if ($participant_ids) {
    $placeholders = implode(',', array_fill(0, count($participant_ids), '?'));
    $log_date_select = $log_date_column ? ", `$log_date_column` AS log_date" : '';
    $logs_result = $conn->execute_query(
        "SELECT participant_id, height$log_date_select
         FROM logs
         WHERE participant_id IN ($placeholders)
         ORDER BY participant_id ASC, log_id DESC",
        $participant_ids
    );

    while ($log = $logs_result->fetch_assoc()) {
        $participant_id = (int) $log['participant_id'];
        $log_metrics[$participant_id]['total'] = ($log_metrics[$participant_id]['total'] ?? 0) + 1;
        $log_metrics[$participant_id]['latest_height'] ??= (float) $log['height'];
        if ($log_date_column && !empty($log['log_date'])) {
            $log_metrics[$participant_id]['dates'][] = substr($log['log_date'], 0, 10);
        }
    }

    foreach ($log_metrics as &$metrics) {
        $dates = array_values(array_unique($metrics['dates'] ?? []));
        $streak = 0;
        if ($dates) {
            $streak = 1;
            for ($index = 1; $index < count($dates); $index++) {
                $earlier = new DateTimeImmutable($dates[$index]);
                $later = new DateTimeImmutable($dates[$index - 1]);
                if ($later->diff($earlier)->days !== 1) {
                    break;
                }
                $streak++;
            }
        }
        $metrics['streak'] = $streak;
    }
    unset($metrics);
}

foreach ($quests as $quest) {
    $requirement = (float) $quest['requirement'];
    $metrics = $log_metrics[(int) $quest['participant_id']] ?? [];
    $progress = match ($quest['type']) {
        'HEIGHT' => (float) ($metrics['latest_height'] ?? 0),
        'LOG_STREAK' => (float) ($metrics['streak'] ?? 0),
        'LOG_TOTAL' => (float) ($metrics['total'] ?? 0),
        default => (float) $quest['stored_progress'],
    };

    if ($requirement <= 0 || $progress < $requirement) {
        continue;
    }

    try {
        $conn->begin_transaction();
        $conn->execute_query(
            "INSERT INTO quest_progress (participant_id, quest_id, rewarded_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE rewarded_at = IF(rewarded_at IS NULL, NOW(), rewarded_at)",
            [(int) $quest['participant_id'], (int) $quest['quest_id']]
        );

        if ($conn->affected_rows > 0) {
            $conn->execute_query(
                "UPDATE users
                 SET current_points = current_points + ?,
                     total_points = total_points + ?
                 WHERE user_id = ?",
                [(int) $quest['reward_points'], (int) $quest['reward_points'], $user_id]
            );
        }
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quests</title>
    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/quests.css">
</head>
<body>
    <?php include("header.php"); ?>
    <main class="content quests-page">
        <div class="page-title-bar">
            <h1>Your Quests</h1>
        </div>

        <?php if ($quest_result->num_rows === 0): ?>
            <p class="quests-empty">No quests are available yet.</p>
        <?php else: ?>
            <section class="quest-list" aria-label="Your quests">
                <?php foreach ($quests as $quest): ?>
                    <?php
                    $requirement = (float) $quest['requirement'];
                    $metrics = $log_metrics[(int) $quest['participant_id']] ?? [];
                    $progress = match ($quest['type']) {
                        'HEIGHT' => (float) ($metrics['latest_height'] ?? 0),
                        'LOG_STREAK' => (float) ($metrics['streak'] ?? 0),
                        'LOG_TOTAL' => (float) ($metrics['total'] ?? 0),
                        default => (float) $quest['stored_progress'],
                    };
                    $percentage = $requirement > 0 ? min(100, round(($progress / $requirement) * 100)) : 0;
                    $completed = $progress >= $requirement;
                    ?>
                    <article class="quest-card <?= $completed ? 'is-complete' : '' ?>">
                        <div class="quest-card-header">
                            <span class="quest-icon">
                                <img src="<?= htmlspecialchars(get_image_path((int) $quest['quest_icon_id']), ENT_QUOTES, 'UTF-8') ?>" alt="" width="40" height="40">
                            </span>
                            <span class="quest-status"><?= $completed ? 'Completed' : 'In progress' ?></span>
                        </div>
                        <h2><?= htmlspecialchars($quest['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="quest-event"><?= htmlspecialchars($quest['event_name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="quest-description"><?= nl2br(htmlspecialchars($quest['description'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
                        <div class="quest-progress" aria-label="<?= $percentage ?> percent complete">
                            <span style="width: <?= $percentage ?>%"></span>
                        </div>
                        <div class="quest-meta">
                            <span><?= htmlspecialchars((string) $progress, ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string) $quest['requirement'], ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= (int) $quest['reward_points'] ?> pts</strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php include("footer.php"); ?>
</body>
</html>