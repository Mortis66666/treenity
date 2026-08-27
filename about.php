<?php

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>

    <?php include("global.php"); ?>
    <link rel="stylesheet" href="styles/about.css">
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content about-page">
        <div class="page-title-bar">
            <h1>About Us</h1>
        </div>

        <section class="about-intro" aria-labelledby="about-intro-title">
            <div>
                <p class="about-kicker">Growing a greener community</p>
                <h2 id="about-intro-title">Planting trees is easier when we grow together.</h2>
            </div>
            <p class="about-lead">Treenity is an easy-to-use website for organising and joining tree planting events. We bring people, organisers, and local communities together so that turning an idea into a day of meaningful action feels simple.</p>
        </section>

        <section class="about-content" aria-labelledby="why-title">
            <div class="about-copy">
                <p class="about-kicker">Why Treenity exists</p>
                <h2 id="why-title">Small actions become lasting change.</h2>
                <p>Finding the right people, sharing event details, and keeping track of progress should not get in the way of planting a tree. Treenity gives organisers one clear place to create events, manage participants, and build momentum.</p>
                <p>For volunteers, it makes discovering local planting opportunities and joining a shared purpose straightforward. Every event is a chance to care for the environment while meeting people who want to make a difference too.</p>
            </div>

            <div class="about-principles">
                <article>
                    <span class="principle-number">01</span>
                    <h3>Make it simple</h3>
                    <p>Clear tools help organisers plan confidently and help everyone find a way to participate.</p>
                </article>
                <article>
                    <span class="principle-number">02</span>
                    <h3>Build community</h3>
                    <p>Shared events turn individual efforts into friendships, local networks, and collective action.</p>
                </article>
                <article>
                    <span class="principle-number">03</span>
                    <h3>Keep growing</h3>
                    <p>Progress and updates help us remember that the impact of one planting day continues long after it ends.</p>
                </article>
            </div>
        </section>

        <section class="about-callout" aria-labelledby="callout-title">
            <div>
                <p class="about-kicker">Your next step</p>
                <h2 id="callout-title">There is always room for one more tree.</h2>
            </div>
            <div class="about-actions">
                <a class="about-action about-action-primary" href="events.php">Explore events <span aria-hidden="true">&#8599;</span></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="about-action about-action-secondary" href="propose_event.php">Propose an event <span aria-hidden="true">&#8594;</span></a>
                <?php else: ?>
                    <a class="about-action about-action-secondary" href="signup.php">Join Treenity <span aria-hidden="true">&#8594;</span></a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include("footer.php"); ?>
</body>

</html>