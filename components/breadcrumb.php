<?php
/**
 * renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Citizens' => null])
 * A null URL renders that crumb as the current, inactive page.
 */
function renderBreadcrumb(array $items): void
{
    echo '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    foreach ($items as $label => $url) {
        if ($url === null) {
            printf('<li class="breadcrumb-item active" aria-current="page">%s</li>', e($label));
        } else {
            printf('<li class="breadcrumb-item"><a href="%s">%s</a></li>', e($url), e($label));
        }
    }
    echo '</ol></nav>';
}
