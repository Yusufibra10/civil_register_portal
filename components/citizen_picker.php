<?php
/**
 * Shared "select an existing citizen" step for birth/create.php and
 * applications/create.php — both modules require the citizen to already
 * exist, so this is a search-then-select widget, not a citizen form.
 *
 * Expects, already in scope:
 *   $selectedCitizen — array from findCitizenById(), or null if none chosen yet
 *   $searchQuery     — current search term (string, possibly empty)
 *   $searchResults   — array of matching citizens (only used when $selectedCitizen is null)
 *   $pickerBaseUrl   — this page's own URL, so search/select/change all GET back to it
 *
 * Requires components/avatar.php (renderAvatar) and getCitizensList() to
 * already be loaded by the including page.
 *
 * When a citizen is selected, this renders a hidden <input name="citizen_id">
 * — the including page must wrap this partial in its own <form method="post">
 * for that value to actually submit.
 */
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h6 mb-3">Citizen</h2>
        <?php if ($selectedCitizen): ?>
            <div class="d-flex align-items-center gap-3">
                <?php renderAvatar($selectedCitizen['photo_path'], 'citizens', $selectedCitizen['first_name'], $selectedCitizen['last_name'], 'sm'); ?>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= e($selectedCitizen['first_name'] . ' ' . $selectedCitizen['last_name']) ?></div>
                    <div class="text-muted small">
                        <?= e($selectedCitizen['citizen_uid']) ?> &middot; <?= e($selectedCitizen['gender']) ?> &middot; DOB <?= formatDate($selectedCitizen['date_of_birth']) ?>
                    </div>
                </div>
                <a href="<?= e($pickerBaseUrl) ?>" class="btn btn-sm btn-outline-secondary">Change</a>
            </div>
            <input type="hidden" name="citizen_id" value="<?= (int) $selectedCitizen['id'] ?>">
        <?php else: ?>
            <form method="get" action="<?= e($pickerBaseUrl) ?>" class="d-flex gap-2 mb-3">
                <input type="text" name="q" class="form-control" placeholder="Search by name or Citizen ID..." value="<?= e($searchQuery) ?>" autofocus>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <?php if ($searchQuery !== '' && empty($searchResults)): ?>
                <div class="empty-state py-3">
                    <i class="fa-solid fa-user-slash"></i>
                    <p>No citizens match &ldquo;<?= e($searchQuery) ?>&rdquo;.</p>
                </div>
            <?php elseif (!empty($searchResults)): ?>
                <div class="list-group">
                    <?php foreach ($searchResults as $result): ?>
                        <a href="<?= e($pickerBaseUrl) ?>?citizen_id=<?= (int) $result['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                            <?php renderAvatar($result['photo_path'], 'citizens', $result['first_name'], $result['last_name'], 'sm'); ?>
                            <div>
                                <div class="fw-semibold"><?= e($result['first_name'] . ' ' . $result['last_name']) ?></div>
                                <div class="text-muted small"><?= e($result['citizen_uid']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">Search above to find the citizen this record belongs to.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
