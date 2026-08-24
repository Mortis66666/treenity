<?php

function renderEventCard(array $event): void
{
    $event_name = htmlspecialchars($event['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $image_path = !empty($event['path'])
        ? htmlspecialchars('images/' . $event['path'], ENT_QUOTES, 'UTF-8')
        : '';
?>
    <article class="event-card">
        <?php if ($image_path !== ''): ?>
            <img
                class="event-card-image"
                src="<?= $image_path ?>"
                alt="">
        <?php else: ?>
            <div class="event-card-image event-card-image--empty" aria-hidden="true">No image</div>
        <?php endif; ?>
        <div class="event-card-body">
            <h2><?= $event_name ?></h2>
        </div>
    </article>
<?php
}
