<?php
/**
 * Shared field markup for birth/create.php and birth/edit.php.
 * Expects $certificate (assoc array, every BIRTH_CERTIFICATE_WRITABLE_FIELDS
 * key as a string) and $errors already in scope. No <form> tag of its own.
 */
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Registration Details</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Registration Date <span class="text-danger">*</span></label>
                <input type="date" name="registration_date" class="form-control <?= fieldInvalidClass($errors, 'registration_date') ?>" value="<?= e($certificate['registration_date']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                <?= fieldErrorHtml($errors, 'registration_date') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Place of Registration</label>
                <input type="text" name="place_of_registration" class="form-control <?= fieldInvalidClass($errors, 'place_of_registration') ?>" value="<?= e((string) $certificate['place_of_registration']) ?>" maxlength="150">
                <?= fieldErrorHtml($errors, 'place_of_registration') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Parents</h2>
        <p class="text-muted small">Optional, entered as free text — a parent does not need an existing citizen record for this certificate to be registered.</p>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Father's Full Name</label>
                <input type="text" name="father_full_name" class="form-control <?= fieldInvalidClass($errors, 'father_full_name') ?>" value="<?= e((string) $certificate['father_full_name']) ?>" maxlength="150">
                <?= fieldErrorHtml($errors, 'father_full_name') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Father's National ID</label>
                <input type="text" name="father_national_id" class="form-control <?= fieldInvalidClass($errors, 'father_national_id') ?>" value="<?= e((string) $certificate['father_national_id']) ?>" maxlength="20">
                <?= fieldErrorHtml($errors, 'father_national_id') ?>
            </div>
            <div class="col-md-8">
                <label class="form-label">Mother's Full Name</label>
                <input type="text" name="mother_full_name" class="form-control <?= fieldInvalidClass($errors, 'mother_full_name') ?>" value="<?= e((string) $certificate['mother_full_name']) ?>" maxlength="150">
                <?= fieldErrorHtml($errors, 'mother_full_name') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Mother's National ID</label>
                <input type="text" name="mother_national_id" class="form-control <?= fieldInvalidClass($errors, 'mother_national_id') ?>" value="<?= e((string) $certificate['mother_national_id']) ?>" maxlength="20">
                <?= fieldErrorHtml($errors, 'mother_national_id') ?>
            </div>
        </div>
    </div>
</div>
