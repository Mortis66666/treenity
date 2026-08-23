<?php
include("database.php");

$query = "SELECT * FROM `events` as e LEFT JOIN images as i ON e.banner_id = i.image_id";
$result = $conn->execute_query($query);
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
            <h1>Upcoming events</h1>
            <h1>TODO: sort by: Upcoming | Ongoing | Past</h1>
        </div>

        <section class="event-list" aria-label="Available events">
            <?php if ($result->num_rows === 0): ?>
                <p class="events-empty">There are no events to show right now.</p>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <article class="event-card">
                        <?php if (!empty($row["path"])): ?>
                            <img
                                class="event-card-image"
                                src="<?= htmlspecialchars("images/" . $row["path"], ENT_QUOTES, "UTF-8") ?>"
                                alt="">
                        <?php else: ?>
                            <div class="event-card-image event-card-image--empty" aria-hidden="true">No image</div>
                        <?php endif; ?>
                        <div class="event-card-body">
                            <h2><?= htmlspecialchars($row["name"], ENT_QUOTES, "UTF-8") ?></h2>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>