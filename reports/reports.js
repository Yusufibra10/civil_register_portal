/**
 * Reports module client-side behavior: show the custom date-range
 * inputs only when "Custom Range" is selected.
 */
document.addEventListener('DOMContentLoaded', function () {
    var periodSelect = document.getElementById('periodSelect');
    var customFields = document.querySelectorAll('.custom-range-field');

    if (periodSelect && customFields.length) {
        periodSelect.addEventListener('change', function () {
            var isCustom = periodSelect.value === 'custom';
            customFields.forEach(function (field) {
                field.classList.toggle('d-none', !isCustom);
            });
        });
    }
});
