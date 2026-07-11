<?php $user = currentUser(); ?>
<nav class="topbar navbar navbar-expand px-3 py-2 border-bottom bg-white">
    <button class="btn btn-link text-dark d-lg-none me-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
        <i class="fa-solid fa-bars"></i>
    </button>

    <form class="d-none d-md-flex flex-grow-1 me-3" role="search" id="navbarSearchForm">
        <input class="form-control" type="search" placeholder="Search anything...">
    </form>

    <div class="d-flex align-items-center ms-auto gap-3">
        <a href="#" class="position-relative text-secondary" title="Notifications">
            <i class="fa-regular fa-bell fs-5"></i>
        </a>
        <div class="dropdown">
            <button class="btn btn-link text-decoration-none dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="fw-semibold text-dark"><?= e($user['full_name'] ?? 'Guest') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>settings/index.php">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
