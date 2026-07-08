<?php
/**
 * Shared field markup for users/create.php and users/edit.php.
 * Expects $user, $errors, $roles, $isCreate, and $isSelf already in scope.
 * No <form> tag of its own.
 */
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Account</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control <?= fieldInvalidClass($errors, 'full_name') ?>" value="<?= e($user['full_name']) ?>" required maxlength="150">
                <?= fieldErrorHtml($errors, 'full_name') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control <?= fieldInvalidClass($errors, 'username') ?>" value="<?= e($user['username']) ?>" required maxlength="50">
                <?= fieldErrorHtml($errors, 'username') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control <?= fieldInvalidClass($errors, 'email') ?>" value="<?= e($user['email']) ?>" required maxlength="150">
                <?= fieldErrorHtml($errors, 'email') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control <?= fieldInvalidClass($errors, 'phone') ?>" value="<?= e((string) $user['phone']) ?>" maxlength="20">
                <?= fieldErrorHtml($errors, 'phone') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role_id" class="form-select <?= fieldInvalidClass($errors, 'role_id') ?>" required <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="">Select...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= (int) $user['role_id'] === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isSelf): ?>
                    <input type="hidden" name="role_id" value="<?= (int) $user['role_id'] ?>">
                    <div class="form-text">You cannot change your own role.</div>
                <?php endif; ?>
                <?= fieldErrorHtml($errors, 'role_id') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select <?= fieldInvalidClass($errors, 'status') ?>" required <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
                <?php if ($isSelf): ?>
                    <input type="hidden" name="status" value="<?= e($user['status']) ?>">
                    <div class="form-text">You cannot change your own status.</div>
                <?php endif; ?>
                <?= fieldErrorHtml($errors, 'status') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $isCreate ? 'Password' : 'Change Password' ?></h2>
        <?php if (!$isCreate): ?><p class="text-muted small">Leave blank to keep the current password.</p><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><?= $isCreate ? 'Password' : 'New Password' ?> <?= $isCreate ? '<span class="text-danger">*</span>' : '' ?></label>
                <input type="password" name="password" class="form-control <?= fieldInvalidClass($errors, 'password') ?>" <?= $isCreate ? 'required' : '' ?> minlength="8" maxlength="100" autocomplete="new-password">
                <?= fieldErrorHtml($errors, 'password') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label"><?= $isCreate ? 'Confirm Password' : 'Confirm New Password' ?></label>
                <input type="password" name="password_confirmation" class="form-control <?= fieldInvalidClass($errors, 'password_confirmation') ?>" minlength="8" maxlength="100" autocomplete="new-password">
                <?= fieldErrorHtml($errors, 'password_confirmation') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Photo</h2>
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="<?= e(asset('uploads/users/' . $user['profile_photo'])) ?>" class="photo-preview" id="photoPreview" alt="Current photo">
            <?php else: ?>
                <img src="" class="photo-preview d-none" id="photoPreview" alt="Preview">
            <?php endif; ?>
            <div class="flex-grow-1">
                <input type="file" name="photo" id="photoInput" class="form-control <?= fieldInvalidClass($errors, 'photo') ?>" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG, or WEBP. Max 2MB.</div>
                <?= fieldErrorHtml($errors, 'photo') ?>
                <?php if (!empty($user['profile_photo'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removePhoto">
                        <label class="form-check-label small" for="removePhoto">Remove current photo</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
