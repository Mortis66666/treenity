<?php
include_once("database.php");
require_once(__DIR__ . "/components/event_card.php");

$query = "SELECT e.*, e.start_time AS start_date, e.end_time AS end_date, i.path
          FROM `events` AS e
          LEFT JOIN images AS i ON e.banner_id = i.image_id
          ORDER BY e.start_time ASC, e.event_id ASC";
$result = $conn->execute_query($query);
$events = [
    'upcoming' => [],
    'ongoing' => [],
    'past' => []
];
$now = new DateTime();

while ($event = $result->fetch_assoc()) {
    $start_time = !empty($event['start_date']) ? new DateTime($event['start_date']) : null;
    $end_time = !empty($event['end_date']) ? new DateTime($event['end_date']) : null;

    if ($start_time && $now < $start_time) {
        $events['upcoming'][] = $event;
    } elseif ($end_time && $now > $end_time) {
        $events['past'][] = $event;
    } else {
        $events['ongoing'][] = $event;
    }
}

function renderEventList(array $events, string $empty_message): void
{
    if (!$events) {
        echo '<p class="events-empty">' . htmlspecialchars($empty_message, ENT_QUOTES, 'UTF-8') . '</p>';
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
    <title>Events</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/events.css">


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content events-page">
        <div class="page-title-bar">
            <h1>Events</h1>
        </div>

        <div class="events-tabs" data-tabs>
            <div class="tab-list" role="tablist" aria-label="Event status">
                <button class="tab-button is-active" id="upcoming-tab" type="button" role="tab" aria-selected="true" aria-controls="upcoming-panel">Upcoming</button>
                <button class="tab-button" id="ongoing-tab" type="button" role="tab" aria-selected="false" aria-controls="ongoing-panel" tabindex="-1">Ongoing</button>
                <button class="tab-button" id="past-tab" type="button" role="tab" aria-selected="false" aria-controls="past-panel" tabindex="-1">Past</button>
            </div>

            <section class="tab-panel is-active" id="upcoming-panel" role="tabpanel" aria-labelledby="upcoming-tab">
                <?php renderEventList($events['upcoming'], 'There are no upcoming events right now.'); ?>
            </section>
            <section class="tab-panel" id="ongoing-panel" role="tabpanel" aria-labelledby="ongoing-tab" hidden>
                <?php renderEventList($events['ongoing'], 'There are no ongoing events right now.'); ?>
            </section>
            <section class="tab-panel" id="past-panel" role="tabpanel" aria-labelledby="past-tab" hidden>
                <?php renderEventList($events['past'], 'There are no past events to show.'); ?>
            </section>
        </div>
    </main>

    <?php include("footer.php"); ?>
    <script src="scripts/profile.js"></script>
</body>

</html>