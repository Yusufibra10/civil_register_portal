/**
 * Citizens module client-side behavior: Bootstrap validation feedback,
 * photo preview, delete-modal wiring, and the per-page selector. Every
 * piece checks the relevant element exists first, since index.php,
 * create.php, edit.php, and view.php each use only some of this file.
 */
document.addEventListener('DOMContentLoaded', function () {
    // ---- Bootstrap-style client-side validation (feedback only — the
    // server re-validates everything regardless, since client checks can
    // always be bypassed). ----
    var form = document.getElementById('citizenForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    // ---- Photo preview + client-side size/type check ----
    var photoInput = document.getElementById('photoInput');
    var photoPreview = document.getElementById('photoPreview');
    var removePhotoCheckbox = document.getElementById('removePhoto');
    var MAX_BYTES = 2 * 1024 * 1024;
    var ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files[0];
            if (!file) {
                return;
            }

            if (!ALLOWED_TYPES.includes(file.type)) {
                photoInput.setCustomValidity('Photo must be a JPG, PNG, or WEBP image.');
                photoInput.reportValidity();
                photoInput.value = '';
                return;
            }

            if (file.size > MAX_BYTES) {
                photoInput.setCustomValidity('Photo must be smaller than 2MB.');
                photoInput.reportValidity();
                photoInput.value = '';
                return;
            }

            photoInput.setCustomValidity('');
            photoPreview.src = URL.createObjectURL(file);
            photoPreview.classList.remove('d-none');

            if (removePhotoCheckbox) {
                removePhotoCheckbox.checked = false;
            }
        });
    }

    // ---- Delete confirmation modal (citizens/index.php) ----
    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button || !button.dataset.citizenId) {
                return; // view.php's modal has no trigger button — nothing to populate
            }
            var idField = document.getElementById('deleteCitizenId');
            var nameField = document.getElementById('deleteCitizenName');
            if (idField) {
                idField.value = button.dataset.citizenId;
            }
            if (nameField) {
                nameField.textContent = button.dataset.citizenName;
            }
        });
    }

    // ---- Rows-per-page selector (citizens/index.php) ----
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
