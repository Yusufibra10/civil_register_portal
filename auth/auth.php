<?php
/**
 * Login POST handler — the "controller" behind auth/login.php's form.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/guest.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/login.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session expired. Please try again.');
    redirect('auth/login.php');
}

$data = sanitizeArray($_POST);

$errors = validate($data, [
    'email'    => 'required|email',
    'password' => 'required',
]);

if (!empty($errors)) {
    $_SESSION['old'] = $data;
    setFlash('danger', 'Please enter a valid email and password.');
    redirect('auth/login.php');
}

// Brute-force throttle: 5 failed attempts against the same email within
// 15 minutes blocks further tries, regardless of whether the account
// exists — this also protects against enumerating valid emails by timing.
if (isRateLimited('LOGIN_FAILED', $data['email'], 5, 15)) {
    setFlash('danger', 'Too many failed login attempts. Please try again in 15 minutes.');
    redirect('auth/login.php');
}

$stmt = getDB()->prepare(
    'SELECT id, full_name, password, status FROM users WHERE email = :email AND deleted_at IS NULL'
);
$stmt->execute(['email' => $data['email']]);
$user = $stmt->fetch();

if (!$user || !password_verify($data['password'], $user['password'])) {
    logActivity(null, 'LOGIN_FAILED', 'auth', $data['email']);
    setFlash('danger', 'Incorrect email or password.');
    redirect('auth/login.php');
}

if ($user['status'] !== 'active') {
    setFlash('danger', 'Your account is not active. Contact an administrator.');
    redirect('auth/login.php');
}

// Regenerate the session ID on privilege change to prevent session fixation.
session_regenerate_id(true);
$_SESSION['user_id']       = $user['id'];
$_SESSION['last_activity'] = time();
unset($_SESSION['old']);

getDB()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);
logActivity($user['id'], 'LOGIN', 'auth', $user['full_name'] . ' logged in.');

if (!empty($data['remember'])) {
    issueRememberToken((int) $user['id']);
}

redirect('dashboard/index.php');
