<?php
// config/database.php
// Centralized PDO Database Connection Configuration for XAMPP MySQL
// Academic Operations System - Student CRUD Application

// Database Configuration Constants
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'student_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Global PDO instance and connection handler
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Create the global PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $dbConnected = true;
    $dbConnectionError = null;

} catch (PDOException $e) {
    // Log the actual detailed error securely to the server error log
    error_log("Database Connection Failure: " . $e->getMessage());
    
    $pdo = null;
    $dbConnected = false;
    // Safe, user-friendly error message that does not expose passwords or credentials
    $dbConnectionError = "Unable to connect to the MySQL database server. Please verify that MySQL is started in XAMPP and the database 'student_management' has been imported.";
}

/**
 * Reusable helper function to retrieve the active PDO instance
 * 
 * @return PDO|null
 */
function getDbConnection() {
    global $pdo;
    return $pdo;
}
