/**
 * National ID module client-side behavior: just the rows-per-page
 * selector on index.php. view.php/print.php/collect.php need no JS.
 */
document.addEventListener('DOMContentLoaded', function () {
    var perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', perPageSelect.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }
});
