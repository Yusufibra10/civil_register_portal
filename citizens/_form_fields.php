<?php
/**
 * Shared field markup for citizens/create.php and citizens/edit.php.
 * Expects $citizen (assoc array, every CITIZEN_WRITABLE_FIELDS key as a
 * string) and $errors (assoc array from validate()) already in scope.
 * Not a standalone page — has no <form> tag of its own, since create.php
 * and edit.php need different actions and hidden fields around it.
 */
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Personal Information</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control <?= fieldInvalidClass($errors, 'first_name') ?>" value="<?= e($citizen['first_name']) ?>" required maxlength="100">
                <?= fieldErrorHtml($errors, 'first_name') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control <?= fieldInvalidClass($errors, 'middle_name') ?>" value="<?= e((string) $citizen['middle_name']) ?>" maxlength="100">
                <?= fieldErrorHtml($errors, 'middle_name') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control <?= fieldInvalidClass($errors, 'last_name') ?>" value="<?= e($citizen['last_name']) ?>" required maxlength="100">
                <?= fieldErrorHtml($errors, 'last_name') ?>
            </div>

            <div class="col-md-3">
                <label class="form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select <?= fieldInvalidClass($errors, 'gender') ?>" required>
                    <option value="">Select...</option>
                    <option value="Male" <?= $citizen['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $citizen['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
                <?= fieldErrorHtml($errors, 'gender') ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date" name="date_of_birth" class="form-control <?= fieldInvalidClass($errors, 'date_of_birth') ?>" value="<?= e($citizen['date_of_birth']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                <?= fieldErrorHtml($errors, 'date_of_birth') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Place of Birth <span class="text-danger">*</span></label>
                <input type="text" name="place_of_birth" class="form-control <?= fieldInvalidClass($errors, 'place_of_birth') ?>" value="<?= e($citizen['place_of_birth']) ?>" required maxlength="150">
                <?= fieldErrorHtml($errors, 'place_of_birth') ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">Marital Status</label>
                <select name="marital_status" class="form-select <?= fieldInvalidClass($errors, 'marital_status') ?>">
                    <option value="">Select...</option>
                    <?php foreach (['Single', 'Married', 'Divorced', 'Widowed'] as $option): ?>
                        <option value="<?= $option ?>" <?= $citizen['marital_status'] === $option ? 'selected' : '' ?>><?= $option ?></option>
                    <?php endforeach; ?>
                </select>
                <?= fieldErrorHtml($errors, 'marital_status') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nationality <span class="text-danger">*</span></label>
                <input type="text" name="nationality" class="form-control <?= fieldInvalidClass($errors, 'nationality') ?>" value="<?= e($citizen['nationality']) ?>" required maxlength="100">
                <?= fieldErrorHtml($errors, 'nationality') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Occupation</label>
                <input type="text" name="occupation" class="form-control <?= fieldInvalidClass($errors, 'occupation') ?>" value="<?= e((string) $citizen['occupation']) ?>" maxlength="100">
                <?= fieldErrorHtml($errors, 'occupation') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Contact &amp; Location</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control <?= fieldInvalidClass($errors, 'phone') ?>" value="<?= e((string) $citizen['phone']) ?>" maxlength="20" placeholder="+252 63 4000000">
                <?= fieldErrorHtml($errors, 'phone') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control <?= fieldInvalidClass($errors, 'email') ?>" value="<?= e((string) $citizen['email']) ?>" maxlength="150">
                <?= fieldErrorHtml($errors, 'email') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control <?= fieldInvalidClass($errors, 'address') ?>" value="<?= e((string) $citizen['address']) ?>" maxlength="255">
                <?= fieldErrorHtml($errors, 'address') ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">Region</label>
                <input type="text" name="region" class="form-control <?= fieldInvalidClass($errors, 'region') ?>" value="<?= e((string) $citizen['region']) ?>" maxlength="100">
                <?= fieldErrorHtml($errors, 'region') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">District</label>
                <input type="text" name="district" class="form-control <?= fieldInvalidClass($errors, 'district') ?>" value="<?= e((string) $citizen['district']) ?>" maxlength="100">
                <?= fieldErrorHtml($errors, 'district') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Village</label>
                <input type="text" name="village" class="form-control <?= fieldInvalidClass($errors, 'village') ?>" value="<?= e((string) $citizen['village']) ?>" maxlength="100">
                <?= fieldErrorHtml($errors, 'village') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Identification &amp; Status</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">National ID Number</label>
                <input type="text" name="national_id_number" class="form-control <?= fieldInvalidClass($errors, 'national_id_number') ?>" value="<?= e((string) $citizen['national_id_number']) ?>" maxlength="20" placeholder="Leave blank if not yet issued">
                <?= fieldErrorHtml($errors, 'national_id_number') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select <?= fieldInvalidClass($errors, 'status') ?>" required>
                    <option value="active" <?= $citizen['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $citizen['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <?= fieldErrorHtml($errors, 'status') ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Registration Date <span class="text-danger">*</span></label>
                <input type="date" name="registration_date" class="form-control <?= fieldInvalidClass($errors, 'registration_date') ?>" value="<?= e($citizen['registration_date']) ?>" max="<?= e(date('Y-m-d')) ?>" required>
                <?= fieldErrorHtml($errors, 'registration_date') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Photo</h2>
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($citizen['photo_path'])): ?>
                <img src="<?= e(asset('uploads/citizens/' . $citizen['photo_path'])) ?>" class="photo-preview" id="photoPreview" alt="Current photo">
            <?php else: ?>
                <img src="" class="photo-preview d-none" id="photoPreview" alt="Preview">
            <?php endif; ?>
            <div class="flex-grow-1">
                <input type="file" name="photo" id="photoInput" class="form-control <?= fieldInvalidClass($errors, 'photo') ?>" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG, or WEBP. Max 2MB.</div>
                <?= fieldErrorHtml($errors, 'photo') ?>
                <?php if (!empty($citizen['photo_path'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removePhoto">
                        <label class="form-check-label small" for="removePhoto">Remove current photo</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
