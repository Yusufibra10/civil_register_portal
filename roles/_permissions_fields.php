<?php
/**
 * Shared permission-checkbox grid for roles/create.php and roles/edit.php.
 * Expects $permissionsGrouped (from getPermissionsGroupedByModule()) and
 * $checkedPermissionIds already in scope. No <form> tag of its own.
 */
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Permissions</h2>
        <?php foreach ($permissionsGrouped as $module => $permissions): ?>
            <div class="mb-3 pb-3 border-bottom">
                <div class="fw-semibold small text-uppercase text-muted mb-2"><?= e($module) ?></div>
                <div class="row">
                    <?php foreach ($permissions as $permission): ?>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permission_ids[]"
                                       value="<?= (int) $permission['id'] ?>" id="perm<?= (int) $permission['id'] ?>"
                                       <?= in_array((int) $permission['id'], $checkedPermissionIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="perm<?= (int) $permission['id'] ?>">
                                    <?= e($permission['description']) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
