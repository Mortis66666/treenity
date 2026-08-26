<?php

function renderEventCard(array $event): void
{
    $event_name = htmlspecialchars($event['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $event_id = (int) ($event['event_id'] ?? 0);
    $description = trim($event['description'] ?? '');
    $description_limit = 140;
    if (strlen($description) > $description_limit) {
        $description = rtrim(substr($description, 0, $description_limit)) . '...';
    }
    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

    $start_value = $event['start_date'] ?? $event['start_datetime'] ?? $event['start_at'] ?? null;
    $end_value = $event['end_date'] ?? $event['end_datetime'] ?? $event['end_at'] ?? null;
    $start_date = $start_value ? new DateTime($start_value) : null;
    $end_date = $end_value ? new DateTime($end_value) : null;
    $now = new DateTime();
    if ($start_date && $now < $start_date) {
        $status = 'UPCOMING';
    } elseif ($end_date && $now > $end_date) {
        $status = 'ENDED';
    } else {
        $status = 'ONGOING';
    }
    $status_class = strtolower($status);
    $date_range = '';
    if ($start_date && $end_date) {
        $date_range = $start_date->format('d M Y') . ' - ' . $end_date->format('d M Y');
    } elseif ($start_date) {
        $date_range = $start_date->format('d M Y');
    }
    $image_path = !empty($event['path'])
        ? htmlspecialchars('images/' . $event['path'], ENT_QUOTES, 'UTF-8')
        : '';
?>
    <a class="event-card-link" href="event.php?event=<?= $event_id ?>">
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
                <div class="event-card-meta">
                    <span class="event-status event-status--<?= $status_class ?>"><?= $status ?></span>
                    <?php if ($date_range !== ''): ?>
                        <time><?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?></time>
                    <?php endif; ?>
                </div>
                <h2><?= $event_name ?></h2>
                <?php if ($description !== ''): ?>
                    <p class="event-card-description"><?= $description ?></p>
                <?php endif; ?>
            </div>
        </article>
    </a>
<?php
}
