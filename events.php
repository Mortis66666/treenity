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
    <title>Base</title>

    <link rel="stylesheet" href="styles/base.css">
    <?php include("global.php"); ?>


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content events-page">
        <div class="events-heading">
            <p class="eyebrow">Discover something new</p>
            <h1>Upcoming events</h1>
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