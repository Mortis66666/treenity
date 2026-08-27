<?php
session_start();
require("database.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'ORGANISER') {
    header("Location: login.php");
    exit();
}

$organiser_id = $_SESSION['user_id'];
$participant_id = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : 0;

$stmt = $pdo->prepare("SELECT p.participant_id, u.name, u.email, u.tp_number, e.name AS event_name, e.event_id
                        FROM participants p
                        JOIN users u ON p.user_id = u.user_id
                        JOIN events e ON p.event_id = e.event_id
                        WHERE p.participant_id = ? AND e.organiser_id = ?");
$stmt->execute(array($participant_id, $organiser_id));
$participant = $stmt->fetch();

if (!$participant) {
    header("Location: eo_participants.php");
    exit();
}

$logs_stmt = $pdo->prepare("SELECT l.log_id, l.comments, l.height, i.path AS image_path
                            FROM logs l
                            LEFT JOIN images i ON l.image_id = i.image_id
                            WHERE l.participant_id = ?
                            ORDER BY l.log_id ASC");
$logs_stmt->execute(array($participant_id));
$logs = $logs_stmt->fetchAll();

$quest_stmt = $pdo->prepare("SELECT q.type, q.requirement, q.reward_points, qp.value
                            FROM quest_progress qp
                            JOIN quests q ON qp.quest_id = q.quest_id
                            WHERE qp.participant_id = ? AND q.event_id = ?");
$quest_stmt->execute(array($participant_id, $participant['event_id']));
$quest_progress = $quest_stmt->fetchAll();

$total_points = 0;
foreach ($quest_progress as $qp) {
    if ($qp['value'] >= $qp['requirement']) {
        $total_points = $total_points + $qp['reward_points'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Detail</title>

    <style>
.content {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}

.back-link {
    display: inline-block;
    color: #1b4332;
    font-size: 14px;
    text-decoration: none;
    margin-bottom: 18px;
    font-weight: 600;
}

.back-link:hover {
    text-decoration: underline;
}

.profile-box {
    background: #f4f1ea;
    border: 1px solid #e0dacd;
    border-radius: 8px;
    padding: 22px;
    margin-bottom: 20px;
}

.profile-box h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 22px;
    color: #1b4332;
    margin: 0 0 8px 0;
}

.profile-box p {
    font-size: 14px;
    color: #6b6355;
    margin: 4px 0;
}

.points-display {
    font-size: 20px;
    font-weight: 700;
    color: #1b4332;
    margin-top: 10px;
}

.detail-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.section-box {
    background: #fff;
    border: 1px solid #e0dacd;
    border-radius: 8px;
    padding: 22px;
}

.content h2 {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 18px;
    color: #1b4332;
    margin-bottom: 16px;
}

.quest-progress-row {
    margin-bottom: 16px;
}

.quest-progress-row:last-child {
    margin-bottom: 0;
}

.quest-progress-row p {
    font-size: 13px;
    color: #33302a;
    margin: 0 0 6px 0;
}

.progress-bar-track {
    width: 100%;
    height: 8px;
    background: #e0dacd;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: #1b4332;
    border-radius: 5px;
}

.progress-text {
    font-size: 12px;
    color: #6b6355;
    margin-top: 4px;
}

.log-card {
    background: #f4f1ea;
    border: 1px solid #e0dacd;
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 10px;
}

.log-card:last-child {
    margin-bottom: 0;
}

.log-card p {
    font-size: 13px;
    color: #33302a;
    margin: 6px 0;
}

.log-image {
    width: 100%;
    max-height: 160px;
    object-fit: cover;
    border-radius: 6px;
    margin: 8px 0;
}

.log-comment {
    font-style: italic;
    color: #6b6355;
}

@media (max-width: 700px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }
}

</style>
    <?php include("global.php"); ?>

</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

        <a href="eo_participants.php?event_id=<?php echo $participant['event_id']; ?>" class="back-link">&laquo; Back to Participant List</a>

        <div class="profile-box">
            <h2><?php echo htmlspecialchars($participant['name']); ?></h2>
            <p>
                <?php echo htmlspecialchars($participant['tp_number']); ?> &nbsp;-&nbsp;
                <?php echo htmlspecialchars($participant['email']); ?> &nbsp;-&nbsp;
                <?php echo htmlspecialchars($participant['event_name']); ?>
            </p>
            <p class="points-display"><?php echo $total_points; ?> Points Earned</p>
        </div>

        <div class="detail-layout">

            <div class="section-box">
                <h2>Quest Progress</h2>

                <?php if (count($quest_progress) == 0) { ?>
                    <p>No quests assigned to this event.</p>
                <?php } else { ?>

                    <?php foreach ($quest_progress as $qp) {
                        if ($qp['requirement'] > 0) {
                            $percent = round(($qp['value'] / $qp['requirement']) * 100);
                            if ($percent > 100) {
                                $percent = 100;
                            }
                        } else {
                            $percent = 0;
                        }
                        $is_done = ($qp['value'] >= $qp['requirement']);
                    ?>
                    <div class="quest-progress-row">
                        <p><?php echo htmlspecialchars($qp['type']); ?> - <?php echo $qp['reward_points']; ?> pts</p>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                        <p class="progress-text">
                            <?php echo $qp['value']; ?> / <?php echo $qp['requirement']; ?>
                            <?php if ($is_done) { ?> - Complete<?php } ?>
                        </p>
                    </div>
                    <?php } ?>

                <?php } ?>
            </div>

            <div class="section-box">
                <h2>Plant Update History (<?php echo count($logs); ?> updates)</h2>

                <?php if (count($logs) == 0) { ?>
                    <p>This participant has not submitted any plant updates yet.</p>
                <?php } else { ?>

                    <?php $count = count($logs); foreach ($logs as $log) { ?>
                    <div class="log-card">
                        <p><b>Update #<?php echo $count; ?></b> - Height: <?php echo $log['height']; ?> cm</p>

                        <?php if ($log['image_path']) { ?>
                            <img src="images/<?php echo htmlspecialchars($log['image_path']); ?>" alt="Plant photo" class="log-image">
                        <?php } ?>

                        <?php if ($log['comments']) { ?>
                            <p class="log-comment">"<?php echo htmlspecialchars($log['comments']); ?>"</p>
                        <?php } ?>
                    </div>
                    <?php $count--; } ?>

                <?php } ?>
            </div>

        </div>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>