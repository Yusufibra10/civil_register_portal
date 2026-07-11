<?php
/**
 * renderPagination(2, 5, BASE_URL . 'citizens/index.php?page=')
 * $baseUrl must already end where the page number should be appended.
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): void
{
    if ($totalPages <= 1) {
        return;
    }

    echo '<nav aria-label="Page navigation"><ul class="pagination">';
    for ($page = 1; $page <= $totalPages; $page++) {
        $active = $page === $currentPage ? ' active' : '';
        printf(
            '<li class="page-item%s"><a class="page-link" href="%s%d">%d</a></li>',
            $active,
            e($baseUrl),
            $page,
            $page
        );
    }
    echo '</ul></nav>';
}
