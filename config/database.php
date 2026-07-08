<?php
/**
 * PDO connection factory.
 * getDB() returns a lazily-created, reused PDO instance rather than a
 * global variable, so any file that needs the database just calls getDB().
 */

// Production credentials belong in config/database.local.php (gitignored,
// copy from database.local.php.example) — never edit the values below for
// a real deployment. This file's defaults are for local XAMPP only, where
// root/blank-password is the standard out-of-the-box MySQL account.
if (is_file(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'civil_registry_portal');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('A system error occurred. Please try again later.');
        }
    }

    return $pdo;
}
