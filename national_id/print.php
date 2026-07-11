<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once ROOT_PATH . '/helpers/national_ids_repository.php';

$id = (int) ($_GET['id'] ?? 0);
$nationalId = $id > 0 ? findNationalIdById($id) : null;

if ($nationalId === null) {
    setFlash('danger', 'That National ID was not found.');
    redirect('national_id/index.php');
}

$pageTitle = 'National ID ' . $nationalId['id_number'];
$backUrl = BASE_URL . 'national_id/index.php';

$genderDisplay = $nationalId['gender'] === 'Male' ? 'Lab/Male' : 'Dheddig/Female';

ob_start();
?>
<div class="d-flex justify-content-center">
    <div class="nid-card">
        <div class="nid-card__notch" aria-hidden="true"></div>

        <div class="nid-card__header">
            <div class="nid-card__country nid-card__country--so">Jamhuuriyadda Somaliland</div>
            <div class="nid-card__emblem" aria-hidden="true"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="nid-card__country nid-card__country--en">Republic of Somaliland</div>
        </div>
        <div class="nid-card__subtitle">
            <span>Kaadhka Aqoonsiga Muwaadinka</span>
            <span>National Identity Card</span>
        </div>

        <div class="nid-card__body">
            <div class="nid-card__chipcol">
                <div class="nid-card__chip" aria-hidden="true"></div>
                <div class="nid-card__seal" aria-hidden="true"><i class="fa-solid fa-scale-balanced"></i></div>
            </div>

            <div class="nid-card__fields">
                <div class="nid-card__field">
                    <div class="nid-card__label">Tirsiga Aqoonsiga / ID Number</div>
                    <div class="nid-card__value nid-card__value--id"><?= e($nationalId['id_number']) ?></div>
                </div>
                <div class="nid-card__field">
                    <div class="nid-card__label">Magaca / Name</div>
                    <div class="nid-card__value nid-card__value--name-first"><?= e(mb_strtoupper($nationalId['first_name'])) ?></div>
                    <div class="nid-card__value nid-card__value--name-rest"><?= e(mb_strtoupper(trim($nationalId['middle_name'] . ' ' . $nationalId['last_name']))) ?></div>
                </div>
                <div class="nid-card__field">
                    <div class="nid-card__label">Taar. Dhalashada / D.O.B</div>
                    <div class="nid-card__value"><?= formatDate($nationalId['date_of_birth'], 'd-m-Y') ?></div>
                </div>
                <div class="nid-card__field">
                    <div class="nid-card__label">Taar. La Bixiyey / Date of Issue</div>
                    <div class="nid-card__value"><?= formatDate($nationalId['issue_date'], 'd-m-Y') ?></div>
                </div>
                <div class="nid-card__field">
                    <div class="nid-card__label">Jinsiga / Gender</div>
                    <div class="nid-card__value"><?= e($genderDisplay) ?></div>
                </div>
            </div>

            <div class="nid-card__photo">
                <?php if (!empty($nationalId['photo_path'])): ?>
                    <img src="<?= e(asset('uploads/citizens/' . $nationalId['photo_path'])) ?>" alt="Photo of <?= e($nationalId['first_name'] . ' ' . $nationalId['last_name']) ?>">
                <?php else: ?>
                    <i class="fa-solid fa-user"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<p class="text-center text-muted small mt-3 mb-0">Issued under the Civil Registry Portal &middot; <?= e($nationalId['id_number']) ?> &middot; Expires <?= formatDate($nationalId['expiry_date']) ?></p>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/layouts/print.php';
