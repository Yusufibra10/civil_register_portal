<?php
/**
 * One-time seeder: creates one demo user per role, with a properly
 * bcrypt-hashed password (password_hash(), not a hand-written hash).
 *
 * database/ is blocked from the web by .htaccess, so run this from a
 * terminal in the project root:
 *
 *     php database/seed.php
 *
 * Safe to run more than once — existing users are left untouched.
 */
require_once __DIR__ . '/../config/config.php';

$pdo = getDB();

$demoUsers = [
    ['Admin',   'System Administrator', 'admin@civilregistry.gov',   'Admin@12345'],
    ['Officer', 'Registry Officer',     'officer@civilregistry.gov', 'Officer@12345'],
    ['Viewer',  'Records Viewer',       'viewer@civilregistry.gov',  'Viewer@12345'],
];

foreach ($demoUsers as [$roleName, $fullName, $email, $plainPassword]) {
    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name');
    $roleStmt->execute(['name' => $roleName]);
    $roleId = $roleStmt->fetchColumn();

    if (!$roleId) {
        echo "Skipped {$email}: role '{$roleName}' not found — run database/schema.sql or migration_phase4.sql first.\n";
        continue;
    }

    $existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $existsStmt->execute(['email' => $email]);
    if ($existsStmt->fetchColumn()) {
        echo "Skipped {$email}: already exists.\n";
        continue;
    }

    $username = strstr($email, '@', true);

    $insertStmt = $pdo->prepare(
        'INSERT INTO users (role_id, full_name, email, username, password, status)
         VALUES (:role_id, :full_name, :email, :username, :password, "active")'
    );
    $insertStmt->execute([
        'role_id'   => $roleId,
        'full_name' => $fullName,
        'email'     => $email,
        'username'  => $username,
        'password'  => password_hash($plainPassword, PASSWORD_BCRYPT),
    ]);

    echo "Created {$roleName}: {$email} / {$plainPassword}\n";
}

echo "Done. Change these demo passwords before any real deployment.\n";
