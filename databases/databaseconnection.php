<?php
$hostname = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_DATABASE') ?: 'kooza_db';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'Alex@mysql123';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

// For debugging
$isProduction = getenv('ENVIRONMENT') === 'production';

try {
    // First connect without database
    $dsn = "mysql:host=$hostname;port=$port;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
    ];

    $conn = new PDO($dsn, $username, $password, $options);

    // Now try to use the database
    $conn->exec("USE `$db_name`");
} catch (\PDOException $e) {
    if ($isProduction) {
        // Safe error for production
        echo "<div style='color:red'>Database connection error. Please try again later.</div>";
        error_log("Database Error: " . $e->getMessage());
    } else {
        // Detailed error for development
        echo "<pre>Database Error: " . $e->getMessage() . "</pre>";
    }
    die();
}
