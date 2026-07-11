<?php
/**
 * Convenience alias for the common "Admin only" gate (Users, Settings).
 * A thin wrapper over role.php so the Admin rule is defined in one place.
 */
$allowedRoles = ['Admin'];
require __DIR__ . '/role.php';
