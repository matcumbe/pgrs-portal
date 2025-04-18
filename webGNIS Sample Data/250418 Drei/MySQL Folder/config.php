<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'webgnis_db');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Geodetic Network Information System (GNIS)');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // Change to 'production' in production

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Time Zone
date_default_timezone_set('Asia/Manila');

// API Configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // Requests per minute
define('API_CACHE_TIME', 300); // Cache time in seconds

// Map Configuration
define('DEFAULT_LATITUDE', 14.5995);
define('DEFAULT_LONGITUDE', 120.9842);
define('DEFAULT_ZOOM', 10);
define('MAX_ZOOM', 19);
define('MIN_ZOOM', 5);

// Search Configuration
define('MAX_SEARCH_RESULTS', 1000);
define('DEFAULT_SEARCH_RADIUS', 10); // in kilometers
define('MAX_SEARCH_RADIUS', 100); // in kilometers 