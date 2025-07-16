<?php
// Database configuration
// Use environment variables for production, otherwise use local settings.
$is_prod = getenv('APP_ENV') === 'production';

if ($is_prod) {
    // Production settings for VM + Cloud SQL Proxy
    define('DB_HOST', '127.0.0.1'); // Proxy listens on localhost
    define('DB_USER', getenv('DB_USER_2'));
    define('DB_PASS', getenv('DB_PASS_2'));
    define('DB_NAME', getenv('DB_NAME_2'));
} else {
    // Local development settings
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'webgnis_users');
}
define('DB_CHARSET', 'utf8mb4'); // Change this to a secure password in production

// Set error reporting settings
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Other settings
// Use getenv to fetch the secret key from the environment variable for better security.
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'webgnis_secret_key'); // Fallback for local development
define('TOKEN_EXPIRY', 86400); // 24 hours in seconds

// Connect to database
function connectDB() {
    try {
        // All connections now use TCP/IP (to the proxy or local MySQL)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}