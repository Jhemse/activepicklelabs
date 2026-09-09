<?php
/**
 * db.php - single shared database connection (PDO / MySQL via XAMPP)
 * Every other PHP file includes this one to get $pdo.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'activepicklelabs');
define('DB_USER', 'root');
define('DB_PASS', '');   // default XAMPP MySQL root password is empty

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()) .
        '<br>Make sure Apache + MySQL are running in XAMPP and the "activepicklelabs" database has been imported.');
}
