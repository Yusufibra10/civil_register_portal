/**
 * Birth Certificate module client-side behavior: Bootstrap validation
 * feedback for create.php/edit.php, and the rows-per-page selector on
 * index.php. Each piece checks its element exists first, since no single
 * page in this module uses everything here.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('birthForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

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
