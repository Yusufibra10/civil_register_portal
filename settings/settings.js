/**
 * Settings module client-side behavior: form validation, logo preview,
 * and the rows-per-page selector on the activity log. Each piece checks
 * its element exists first.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('settingsForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

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
                photoInput.setCustomValidity('Logo must be a JPG, PNG, or WEBP image.');
                photoInput.reportValidity();
                photoInput.value = '';
                return;
            }
            if (file.size > MAX_BYTES) {
                photoInput.setCustomValidity('Logo must be smaller than 2MB.');
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
