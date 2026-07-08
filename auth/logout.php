<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

logActivity($_SESSION['user_id'] ?? null, 'LOGOUT', 'auth', 'User logged out.');
clearRememberToken();

$_SESSION = [];
session_destroy();

session_start();
setFlash('success', 'You have been logged out.');
redirect('auth/login.php');
