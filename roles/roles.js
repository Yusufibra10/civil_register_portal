/**
 * Roles module client-side behavior: form validation and the shared
 * delete-confirmation modal on index.php (same pattern as citizens.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('roleForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button || !button.dataset.roleId) {
                return;
            }
            var idField = document.getElementById('deleteRoleId');
            var nameField = document.getElementById('deleteRoleName');
            if (idField) {
                idField.value = button.dataset.roleId;
            }
            if (nameField) {
                nameField.textContent = button.dataset.roleName;
            }
        });
    }
});
