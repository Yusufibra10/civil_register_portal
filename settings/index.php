<?php
require_once __DIR__ . '/../config/config.php';
$requiredPermission = 'settings.manage';
require_once __DIR__ . '/../middleware/permission.php';
require_once ROOT_PATH . '/helpers/settings_repository.php';
require_once ROOT_PATH . '/helpers/uploads.php';
require_once ROOT_PATH . '/components/breadcrumb.php';

$pageTitle = 'Settings';
$errors = [];

$fields = [
    'system_name'         => setting('system_name', 'Civil Registry Portal'),
    'office_address'      => setting('office_address'),
    'contact_phone'       => setting('contact_phone'),
    'contact_email'       => setting('contact_email'),
    'email_from_name'     => setting('email_from_name'),
    'email_from_address'  => setting('email_from_address'),
    'theme_color'         => setting('theme_color', 'navy'),
];
$currentLogo = setting('system_logo');

$themeOptions = [
    'navy'   => 'Navy & Gold (default)',
    'forest' => 'Forest Green',
    'maroon' => 'Maroon',
    'slate'  => 'Slate Blue',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        redirect('settings/index.php');
    }

    $input = sanitizeArray($_POST);
    $fields = array_merge($fields, array_intersect_key($input, $fields));

    $errors = validate($input, [
        'system_name'        => 'required|max:150',
        'office_address'     => 'max:255',
        'contact_phone'      => 'phone|max:20',
        'contact_email'      => 'email|max:150',
        'email_from_name'    => 'max:150',
        'email_from_address' => 'email|max:150',
        'theme_color'        => 'required|in:navy,forest,maroon,slate',
    ]);

    $logoErrors = validateImageUpload($_FILES['logo'] ?? []);
    if (!empty($logoErrors)) {
        $errors['logo'] = $logoErrors;
    }

    if (empty($errors)) {
        $newLogoFilename = $currentLogo;

        if (!empty($_FILES['logo']['name'])) {
            $newLogoFilename = storeUploadedImage($_FILES['logo'], 'settings');
            if (!empty($currentLogo)) {
                deleteUploadedImage($currentLogo, 'settings');
            }
        } elseif (!empty($input['remove_logo'])) {
            if (!empty($currentLogo)) {
                deleteUploadedImage($currentLogo, 'settings');
            }
            $newLogoFilename = null;
        }

        saveSettings(array_merge($fields, ['system_logo' => (string) $newLogoFilename]), 'general', (int) $_SESSION['user_id']);

        logActivity($_SESSION['user_id'], 'UPDATE_SETTINGS', 'settings', 'Updated system settings.');
        setFlash('success', 'Settings saved successfully.');
        redirect('settings/index.php');
    }
}

ob_start();
?>
<?php renderBreadcrumb(['Dashboard' => BASE_URL . 'dashboard/index.php', 'Settings' => null]); ?>

<h1 class="h4 mb-3">Settings</h1>

<form method="post" enctype="multipart/form-data" novalidate id="settingsForm">
    <?= csrfField() ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">System Identity</h2>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">System Name <span class="text-danger">*</span></label>
                    <input type="text" name="system_name" class="form-control <?= fieldInvalidClass($errors, 'system_name') ?>" value="<?= e($fields['system_name']) ?>" required maxlength="150">
                    <?= fieldErrorHtml($errors, 'system_name') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Theme</label>
                    <select name="theme_color" class="form-select <?= fieldInvalidClass($errors, 'theme_color') ?>">
                        <?php foreach ($themeOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $fields['theme_color'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= fieldErrorHtml($errors, 'theme_color') ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Logo</label>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($currentLogo): ?>
                            <img src="<?= e(asset('uploads/settings/' . $currentLogo)) ?>" class="photo-preview" id="photoPreview" alt="Current logo">
                        <?php else: ?>
                            <img src="" class="photo-preview d-none" id="photoPreview" alt="Preview">
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <input type="file" name="logo" id="photoInput" class="form-control <?= fieldInvalidClass($errors, 'logo') ?>" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG, or WEBP. Max 2MB. Shown in the sidebar and on the login page.</div>
                            <?= fieldErrorHtml($errors, 'logo') ?>
                            <?php if ($currentLogo): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removePhoto">
                                    <label class="form-check-label small" for="removePhoto">Remove current logo</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Office &amp; Contact</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Office Address</label>
                    <input type="text" name="office_address" class="form-control <?= fieldInvalidClass($errors, 'office_address') ?>" value="<?= e($fields['office_address']) ?>" maxlength="255">
                    <?= fieldErrorHtml($errors, 'office_address') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control <?= fieldInvalidClass($errors, 'contact_phone') ?>" value="<?= e($fields['contact_phone']) ?>" maxlength="20">
                    <?= fieldErrorHtml($errors, 'contact_phone') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control <?= fieldInvalidClass($errors, 'contact_email') ?>" value="<?= e($fields['contact_email']) ?>" maxlength="150">
                    <?= fieldErrorHtml($errors, 'contact_email') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h2 class="h6 mb-3">Email Settings</h2>
            <p class="text-muted small">Stored for future use — this system does not yet send email itself, so these values aren't wired to a mail server.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">From Name</label>
                    <input type="text" name="email_from_name" class="form-control <?= fieldInvalidClass($errors, 'email_from_name') ?>" value="<?= e($fields['email_from_name']) ?>" maxlength="150">
                    <?= fieldErrorHtml($errors, 'email_from_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">From Address</label>
                    <input type="email" name="email_from_address" class="form-control <?= fieldInvalidClass($errors, 'email_from_address') ?>" value="<?= e($fields['email_from_address']) ?>" maxlength="150">
                    <?= fieldErrorHtml($errors, 'email_from_address') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save Settings</button>
    </div>
</form>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Backup &amp; Restore</h2>
        <p class="text-muted small">Placeholder for a future phase — not implemented yet. A real backup would need to run safely against a live database (consistent snapshot, large-file handling, restore verification), which is more than a "download" button should quietly promise.</p>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" disabled><i class="fa-solid fa-download me-1"></i>Download Backup</button>
            <button type="button" class="btn btn-outline-secondary" disabled><i class="fa-solid fa-upload me-1"></i>Restore from File</button>
        </div>
    </div>
</div>

<a href="<?= BASE_URL ?>settings/activity_logs.php" class="small"><i class="fa-solid fa-clock-rotate-left me-1"></i>View system activity log</a>
<?php
$content = ob_get_clean();
$pageScript = 'settings/settings.js';
require ROOT_PATH . '/layouts/app.php';
