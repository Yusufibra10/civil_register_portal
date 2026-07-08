/**
 * Login page only — loaded via $pageScript from layouts/login.php, same
 * convention layouts/app.php uses for other page-specific scripts.
 * Purely cosmetic: toggles the password field's visibility, nothing here
 * touches form submission.
 */
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('togglePassword');
    var passwordInput = document.getElementById('password');

    if (toggle && passwordInput) {
        toggle.addEventListener('click', function () {
            var showing = passwordInput.type === 'text';
            passwordInput.type = showing ? 'password' : 'text';

            var icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', showing);
                icon.classList.toggle('fa-eye-slash', !showing);
            }

            toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    }
});
