/**
 * Dashboard-only behavior: reads the JSON data island rendered by
 * dashboard/index.php and builds the two Chart.js charts. Kept out of
 * assets/js/app.js because no other page uses Chart.js.
 */
document.addEventListener('DOMContentLoaded', function () {
    var dataEl = document.getElementById('dashboardData');
    if (!dataEl) {
        return;
    }

    var data = JSON.parse(dataEl.textContent);

    function hideLoading(canvasId) {
        var el = document.querySelector('[data-chart-loading="' + canvasId + '"]');
        if (el) {
            el.remove();
        }
    }

    var monthlyCtx = document.getElementById('monthlyRegistrationsChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: data.monthlyRegistrations.labels,
                datasets: [{
                    label: 'New Citizens',
                    data: data.monthlyRegistrations.data,
                    borderColor: '#14284b',
                    backgroundColor: 'rgba(20, 40, 75, 0.08)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#14284b',
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
        hideLoading('monthlyRegistrationsChart');
    }

    var statusCtx = document.getElementById('statusDistributionChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: data.statusDistribution.labels,
                datasets: [{
                    data: data.statusDistribution.data,
                    backgroundColor: ['#c79a3f', '#1b5fae', '#2f9e5b', '#c0392b', '#6b4c9a'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            },
        });
        hideLoading('statusDistributionChart');
    }
});
