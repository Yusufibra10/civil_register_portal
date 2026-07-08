/**
 * Small, framework-free behaviors shared by every page. Module-specific
 * scripts belong in their own module folder, not here.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Mobile sidebar toggle (paired with includes/navbar.php's #sidebarToggle button)
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // Auto-dismiss flash alerts after 5 seconds.
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            var alert = bootstrap.Alert.getOrCreateInstance(alertEl);
            alert.close();
        }, 5000);
    });

    // Print layout's Print/Back bar (layouts/print.php). Kept out of an
    // inline onclick= so the page can run under a script-src CSP with no
    // 'unsafe-inline' exception.
    var printButton = document.getElementById('printPageButton');
    if (printButton) {
        printButton.addEventListener('click', function () {
            window.print();
        });
    }

    // Only fall back to browser history when the page didn't set a real
    // destination (layouts/print.php's $backUrl) — history.back() can
    // land somewhere unexpected if the print page was opened directly
    // or in a new tab, so a real href is preferred whenever one exists.
    var printBackLink = document.getElementById('printBackLink');
    if (printBackLink && printBackLink.getAttribute('href') === '#') {
        printBackLink.addEventListener('click', function (e) {
            e.preventDefault();
            history.back();
        });
    }

    // Navbar search box (includes/navbar.php) has no search endpoint wired
    // up yet — suppress the default GET-to-self submit until it does.
    var navbarSearchForm = document.getElementById('navbarSearchForm');
    if (navbarSearchForm) {
        navbarSearchForm.addEventListener('submit', function (e) {
            e.preventDefault();
        });
    }
});
