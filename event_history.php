<?php
include_once("database.php");
include_once("check_user.php");
require_once(__DIR__ . "/components/event_card.php");

$user_id = (int) $_SESSION['user_id'];
$events = [
    'all' => [],
    'upcoming' => [],
    'ongoing' => [],
    'attended' => [],
    'missed' => []
];

$event_result = $conn->execute_query(
    "SELECT e.*, e.start_time AS start_date, e.end_time AS end_date, i.path,
            EXISTS (
                SELECT 1 FROM participants p
                WHERE p.event_id = e.event_id AND p.user_id = ?
            ) AS attended
    FROM events e
    LEFT JOIN images i ON e.banner_id = i.image_id
     ORDER BY e.start_time DESC, e.event_id DESC",
    [$user_id]
);

while ($event = $event_result->fetch_assoc()) {
    $events['all'][] = $event;
    $start_time = strtotime($event['start_date']);
    $end_time = strtotime($event['end_date']);
    if ($start_time > time()) {
        $events['upcoming'][] = $event;
        continue;
    }
    if ($end_time >= time()) {
        $events['ongoing'][] = $event;
    } else {
        $events[$event['attended'] ? 'attended' : 'missed'][] = $event;
    }
}

function renderHistoryEvents(array $events, string $empty_message): void
{
    if (!$events) {
        echo '<p class="history-empty">' . htmlspecialchars($empty_message, ENT_QUOTES, 'UTF-8') . '</p>';
        return;
    }

    echo '<div class="event-list">';
    foreach ($events as $event) {
        renderEventCard($event);
    }
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event History</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/events.css">
    <link rel="stylesheet" href="styles/event_history.css">


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content event-history-page">
        <div class="page-title-bar">
            <h1>Event History</h1>
        </div>

        <div class="history-tabs" data-tabs>
            <div class="tab-list" role="tablist" aria-label="Event history status">
                <button class="tab-button is-active" id="all-tab" type="button" role="tab" aria-selected="true" aria-controls="all-panel">All</button>
                <button class="tab-button" id="upcoming-tab" type="button" role="tab" aria-selected="false" aria-controls="upcoming-panel" tabindex="-1">Upcoming</button>
                <button class="tab-button" id="ongoing-tab" type="button" role="tab" aria-selected="false" aria-controls="ongoing-panel" tabindex="-1">Ongoing</button>
                <button class="tab-button" id="attended-tab" type="button" role="tab" aria-selected="false" aria-controls="attended-panel" tabindex="-1">Attended</button>
                <button class="tab-button" id="missed-tab" type="button" role="tab" aria-selected="false" aria-controls="missed-panel" tabindex="-1">Missed</button>
            </div>

            <section class="tab-panel is-active" id="all-panel" role="tabpanel" aria-labelledby="all-tab">
                <?php renderHistoryEvents($events['all'], 'No events to show.'); ?>
            </section>
            <section class="tab-panel" id="upcoming-panel" role="tabpanel" aria-labelledby="upcoming-tab" hidden>
                <?php renderHistoryEvents($events['upcoming'], 'No upcoming events.'); ?>
            </section>
            <section class="tab-panel" id="ongoing-panel" role="tabpanel" aria-labelledby="ongoing-tab" hidden>
                <?php renderHistoryEvents($events['ongoing'], 'No ongoing events.'); ?>
            </section>
            <section class="tab-panel" id="attended-panel" role="tabpanel" aria-labelledby="attended-tab" hidden>
                <?php renderHistoryEvents($events['attended'], 'No attended events.'); ?>
            </section>
            <section class="tab-panel" id="missed-panel" role="tabpanel" aria-labelledby="missed-tab" hidden>
                <?php renderHistoryEvents($events['missed'], 'No missed events.'); ?>
            </section>
        </div>

    </main>

    <?php include("footer.php"); ?>
    <script src="scripts/profile.js"></script>
</body>

</html>