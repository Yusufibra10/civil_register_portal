<?php
/**
 * renderTimeline(array $events): void
 *
 * $events is a list, oldest first, of:
 *   ['label' => string, 'variant' => string, 'by' => ?string, 'at' => DATETIME string, 'remarks' => ?string]
 *
 * Built for applications/view.php's status history, but generic enough
 * for any future chronological event list.
 */
function renderTimeline(array $events): void
{
    if (empty($events)) {
        echo '<div class="empty-state"><i class="fa-regular fa-clock"></i><p>No history yet.</p></div>';
        return;
    }

    echo '<ul class="timeline list-unstyled mb-0">';
    foreach ($events as $event) {
        echo '<li class="timeline-item">';
        echo '<span class="timeline-dot bg-' . e($event['variant']) . '"></span>';
        echo '<div class="timeline-content">';
        echo '<div class="d-flex justify-content-between align-items-start gap-2">';
        echo '<span class="fw-semibold">' . e($event['label']) . '</span>';
        echo '<span class="text-muted small text-nowrap">' . e(formatDate($event['at'], 'd M Y, g:i A')) . '</span>';
        echo '</div>';
        if (!empty($event['by'])) {
            echo '<div class="text-muted small">by ' . e($event['by']) . '</div>';
        }
        if (!empty($event['remarks'])) {
            echo '<div class="small mt-1">' . e($event['remarks']) . '</div>';
        }
        echo '</div></li>';
    }
    echo '</ul>';
}
