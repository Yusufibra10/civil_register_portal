/**
 * Applications module client-side behavior: form validation feedback,
 * the status-change modal wiring, and the rows-per-page selector. Each
 * piece checks its element exists first, since no single page in this
 * module uses everything here.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('applicationForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    // Status-change modal (applications/view.php and applications/index.php):
    // one shared modal per page, populated from whichever transition button
    // was actually clicked. view.php has exactly one application in scope,
    // so its hidden "id" field is pre-filled by PHP and never needs the
    // data-application-id branch below; index.php lists many applications,
    // so its buttons carry the row's id and this fills the field per click.
    var statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) {
                return;
            }
            var codeField = document.getElementById('statusModalCode');
            var labelField = document.getElementById('statusModalLabel');
            if (codeField) {
                codeField.value = button.dataset.statusCode;
            }
            if (labelField) {
                labelField.textContent = '"' + button.dataset.statusLabel + '"';
            }
            if (button.dataset.applicationId) {
                var idField = statusModal.querySelector('input[name="id"]');
                if (idField) {
                    idField.value = button.dataset.applicationId;
                }
            }
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
