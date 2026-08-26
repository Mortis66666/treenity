<?php
include_once("database.php");
require_once(__DIR__ . "/components/event_card.php");

$query = "SELECT e.*, i.path FROM events AS e LEFT JOIN images AS i ON e.banner_id = i.image_id LIMIT 3";
$result = $conn->execute_query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treenity | Grow something lasting</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/events.css">


</head>

<body>
    <?php include("header.php"); ?>

    <main class="content home-page">
        <section class="home-hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="home-kicker">Plant today. Remember tomorrow.</p>
                <h1 id="hero-title">Give your corner of the world a little more green.</h1>
                <p class="hero-description">Treenity brings people together to plant trees, join local events, and watch every new leaf become part of a bigger story.</p>
                <a class="primary-action" href="events.php">Start planting now! <span aria-hidden="true">&#8599;</span></a>
            </div>
            <div class="hero-image-wrap">
                <img src="images/image.png" alt="A mature tree standing in a green hillside">
                <span class="hero-note">One tree<br>at a time.</span>
            </div>
        </section>

        <section class="how-it-works" aria-labelledby="how-title">
            <div class="section-intro">
                <p class="home-kicker">A simple habit with a long horizon</p>
                <h2 id="how-title">How Treenity works</h2>
            </div>
            <div class="steps">
                <article class="step"><span class="step-number">01</span>
                    <h3>Find your event</h3>
                    <p>Browse planting days near you and reserve a place with people who care about the same patch of earth.</p>
                </article>
                <article class="step"><span class="step-number">02</span>
                    <h3>Plant and take part</h3>
                    <p>Show up, get your hands in the soil, and make a small contribution that will outlast the afternoon.</p>
                </article>
                <article class="step"><span class="step-number">03</span>
                    <h3>Watch it grow</h3>
                    <p>Log photo updates as your tree grows so your progress, and the community around it, stays visible.</p>
                </article>
            </div>
        </section>

        <section class="featured-events" aria-labelledby="featured-title">
            <div class="events-section-heading">
                <div>
                    <p class="home-kicker">Get involved nearby</p>
                    <h2 id="featured-title">Planting days in motion</h2>
                </div>
                <a class="text-link" href="events.php">See all ongoing events <span aria-hidden="true">&#8594;</span></a>
            </div>
            <div class="home-event-list event-list">
                <?php if ($result->num_rows === 0): ?>
                    <p class="home-events-empty">New planting days are being prepared. Check back soon.</p>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php renderEventCard($row); ?>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>