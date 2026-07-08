/**
 * Users module client-side behavior: form validation, photo preview,
 * password-confirmation match check, and the rows-per-page selector.
 * Each piece checks its element exists first.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('userForm');
    var password = document.querySelector('input[name=password]');
    var passwordConfirm = document.querySelector('input[name=password_confirmation]');

    function checkPasswordsMatch() {
        if (!password || !passwordConfirm) {
            return;
        }
        if (password.value && password.value !== passwordConfirm.value) {
            passwordConfirm.setCustomValidity('Passwords do not match.');
        } else {
            passwordConfirm.setCustomValidity('');
        }
    }

    if (password && passwordConfirm) {
        password.addEventListener('input', checkPasswordsMatch);
        passwordConfirm.addEventListener('input', checkPasswordsMatch);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            checkPasswordsMatch();
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    // Photo preview (same pattern as citizens.js)
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
