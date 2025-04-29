<?php

define('DB_HOST', 'localhost:8889'); 
define('DB_NAME', 'cat_sanctuary'); 
define('DB_USER', 'root'); 
define('DB_PASS', 'root'); // Default MAMP MySQL password is 'root' & XAMPP is ''

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

?>