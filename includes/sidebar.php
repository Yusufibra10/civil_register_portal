<?php
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$systemName = setting('system_name', 'Civil Registry Portal');
$systemLogo = setting('system_logo');
?>
<aside class="sidebar bg-dark text-white" id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2 px-3 py-3">
        <?php if ($systemLogo): ?>
            <img src="<?= e(asset('uploads/settings/' . $systemLogo)) ?>" alt="" style="width:24px;height:24px;object-fit:contain;border-radius:4px;">
        <?php else: ?>
            <i class="fa-solid fa-landmark"></i>
        <?php endif; ?>
        <div>
            <div class="fw-bold small text-uppercase"><?= e($systemName) ?></div>
            <div class="text-white-50" style="font-size:11px;">E-Government System</div>
        </div>
    </div>
    <nav class="nav flex-column px-2">
        <a class="nav-link <?= $currentDir === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard/index.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a>
        <a class="nav-link <?= $currentDir === 'birth' ? 'active' : '' ?>" href="<?= BASE_URL ?>birth/index.php"><i class="fa-solid fa-baby me-2"></i>Birth Certificates</a>
        <a class="nav-link <?= $currentDir === 'citizens' ? 'active' : '' ?>" href="<?= BASE_URL ?>citizens/index.php"><i class="fa-solid fa-users me-2"></i>Citizens</a>
        <a class="nav-link <?= $currentDir === 'national_id' ? 'active' : '' ?>" href="<?= BASE_URL ?>national_id/index.php"><i class="fa-solid fa-id-card me-2"></i>National ID</a>
        <a class="nav-link <?= $currentDir === 'applications' ? 'active' : '' ?>" href="<?= BASE_URL ?>applications/index.php"><i class="fa-solid fa-file-circle-check me-2"></i>ID Applications</a>
        <?php if (hasPermission('reports.view')): ?>
        <a class="nav-link <?= $currentDir === 'reports' ? 'active' : '' ?>" href="<?= BASE_URL ?>reports/index.php"><i class="fa-solid fa-chart-column me-2"></i>Reports</a>
        <?php endif; ?>
        <?php if (hasPermission('users.view') || hasPermission('roles.manage') || hasPermission('settings.manage')): ?>
        <hr class="text-white-50 my-2">
        <?php endif; ?>
        <?php if (hasPermission('users.view')): ?>
        <a class="nav-link <?= $currentDir === 'users' ? 'active' : '' ?>" href="<?= BASE_URL ?>users/index.php"><i class="fa-solid fa-user-gear me-2"></i>Users</a>
        <?php endif; ?>
        <?php if (hasPermission('roles.manage')): ?>
        <a class="nav-link <?= $currentDir === 'roles' ? 'active' : '' ?>" href="<?= BASE_URL ?>roles/index.php"><i class="fa-solid fa-user-shield me-2"></i>Roles &amp; Permissions</a>
        <?php endif; ?>
        <?php if (hasPermission('settings.manage')): ?>
        <a class="nav-link <?= $currentDir === 'settings' ? 'active' : '' ?>" href="<?= BASE_URL ?>settings/index.php"><i class="fa-solid fa-gear me-2"></i>Settings</a>
        <?php endif; ?>
        <hr class="text-white-50 my-2">
        <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
    </nav>
</aside>
