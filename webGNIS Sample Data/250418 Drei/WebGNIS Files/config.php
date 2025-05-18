<?php
// Start output buffering immediately to catch any premature output or errors from config itself.
ob_start();
$_config_php_error_occurred = false; // Flag to track if config setup failed

try {
    // Original headers related to direct access or OPTIONS are deferred until the direct action handler block

    // Database Configuration
    define('DB_HOST', '127.0.0.1'); // Change this to your actual database host if needed
    define('DB_USER', 'root');      // Change this to your actual database username
    define('DB_PASS', '');          // Change this to your actual database password
    define('DB_NAME', 'webgnis_db'); // Change this to your actual database name
    define('DB_CHARSET', 'utf8mb4');

    // Test the database connection at config load time and log errors
    // The function definition must precede its call or be globally available
    if (!function_exists('config_testDatabaseConnection')) { // Renamed to avoid conflict if already defined elsewhere
        function config_testDatabaseConnection() { // Renamed
            try {
                // Ensure DB_HOST etc. are defined before this call, which they are now.
                $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if ($db->connect_error) {
                    error_log("[CONFIG] Database connection failed: " . $db->connect_error);
                    // Optionally, throw an exception here to be caught by the outer try-catch
                    // throw new Exception("[CONFIG] Database connection failed: " . $db->connect_error);
                    return false; 
                }
                $db->close();
                error_log("[CONFIG] Database connection successful");
                return true;
            } catch (Exception $e) {
                error_log("[CONFIG] Database connection exception: " . $e->getMessage());
                // throw $e; // Re-throw to be caught by outer try-catch
                return false;
            }
        }
    }
    config_testDatabaseConnection(); // Call the connection test

    // Application Configuration
    define('APP_NAME', 'Geodetic Network Information System (GNIS)');
    define('APP_VERSION', '1.0.0');
    define('APP_ENV', 'development'); // Change to 'production' in production

    // Error Reporting (these ini_set might be overridden by including scripts like api.php)
    if (APP_ENV === 'development') {
        error_reporting(E_ALL);
        ini_set('display_errors', 1); // This is dangerous if it outputs HTML
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
    // Forcing display_errors to 0 here to prevent HTML leakage from config itself.
    // The calling scripts (api.php) manage their own display_errors settings.
    ini_set('display_errors', 0);


    // Set up error logging (main place for this)
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log'); // Ensure this path is writable

    // Session Configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // ini_set('session.cookie_secure', 1); // Enable this if using HTTPS

    // Time Zone
    date_default_timezone_set('Asia/Manila');

    // API Configuration
    define('API_VERSION', 'v1');
    define('API_RATE_LIMIT', 99999); // Requests per minute
    define('API_CACHE_TIME', 99999); // Cache time in seconds

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

    // API settings
    define('BASE_URL', 'http://localhost/webgnis/'); // Adjust if your dev URL is different
    define('JWT_SECRET', 'webgnis-secret-key-please-change-in-production');
    define('TOKEN_EXPIRY', 86400); // 24 hours in seconds

    // Station price configuration
    define('PRICE_HORIZONTAL', 300);
    define('PRICE_VERTICAL', 300);
    define('PRICE_GRAVITY', 300);

    // Debug mode (true for development, false for production)
    define('DEBUG_MODE', true);

    // File upload configuration
    define('UPLOAD_PATH', 'uploads/'); // Ensure this directory exists and is writable
    define('MAX_UPLOAD_SIZE', 5242880); // 5MB

} catch (Throwable $t) { // Catch any error or exception during the critical config setup
    $_config_php_error_occurred = true;
    // Log the error using the already configured error_log (if possible) or default logger
    error_log("[CRITICAL_CONFIG_ERROR] " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine() . "\nStack trace:\n" . $t->getTraceAsString());

    if (ob_get_level() > 0) {
        ob_end_clean(); // Clean any partial output from the failed config
    }
    ob_start(); // Start a new buffer for the JSON error message

    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow common methods
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500); // Internal Server Error
    }

    echo json_encode([
        'success' => false,
        'message' => 'Critical error during server configuration. Please check server logs. Message: ' . $t->getMessage(),
        'source' => 'config_initialization_failure',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    if (ob_get_level() > 0) {
        ob_end_flush(); // Send the JSON error
    }
    exit; // Halt script execution
}

// If config setup was successful (no exception caught and script didn't exit):
if (!$_config_php_error_occurred) {
    // Clean the output buffer that was started at the beginning of this script.
    // This ensures that config.php, when included, doesn't accidentally output anything
    // (like whitespace) that could break JSON output of the parent script.
    if (ob_get_level() > 0) {
         ob_end_clean(); // Use ob_end_clean to discard buffer contents silently
    }
}

// --- Function definitions and Direct Action Handler (for when config.php is called directly) ---
// These are outside the critical setup try-catch block. They will only be defined/run if setup succeeded.

// Original testDatabaseConnection (if needed by other parts, ensure it's defined, renamed original for clarity)
if (!function_exists('testDatabaseConnection')) {
    function testDatabaseConnection() {
        return config_testDatabaseConnection(); // Call the one defined and tested above
    }
}


// Connection function to establish database connection (used by other API files if they don't manage their own)
if (!function_exists('connectDB')) {
    function connectDB() {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            // This function's error handling uses returnResponse, which exits.
            // This can be problematic if called from within a try-catch block of another script.
            // For now, keeping original behavior.
            if (DEBUG_MODE) {
                // returnResponse will handle headers, JSON, and exit
                returnResponse('error', "Connection failed: " . $conn->connect_error, null, 500);
            } else {
                returnResponse('error', "Database connection failed. Please try again later.", null, 500);
            }
        }
        $conn->set_charset(DB_CHARSET); // Set charset for the connection
        return $conn;
    }
}

// Return error/success responses in a consistent format (used by other API files)
if (!function_exists('returnResponse')) {
    function returnResponse($status, $message, $data = null, $code = 200) {
        // This function directly echoes and exits. It should manage its own headers and output buffering if needed.
        if (ob_get_level() > 0) { // If called when a buffer is active, clean it before new output.
            // This is a defensive measure. Ideally, caller manages buffering.
            // ob_end_clean(); 
        }
        // ob_start(); // Start buffer for this response

        if (!headers_sent()) {
            http_response_code($code);
            // These headers are crucial if this function is responsible for the entire response
            header("Access-Control-Allow-Origin: *"); 
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
            header("Content-Type: application/json; charset=UTF-8");
        }
        
        $response = [
            'status' => $status,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        // if(ob_get_level() > 0) ob_end_flush();
        exit; 
    }
}

// Function to get config data (for direct call to config.php?action=get-config)
if (!function_exists('getConfigData')) {
    function getConfigData() {
        // This function directly echoes JSON and exits.
        // Headers should be set before this is called by the direct action handler block.
        $config = [
            'status' => 'success',
            'data' => [
                'station_prices' => [
                    'horizontal' => PRICE_HORIZONTAL,
                    'vertical' => PRICE_VERTICAL,
                    'gravity' => PRICE_GRAVITY
                ],
                'upload_limits' => [
                    'max_size' => MAX_UPLOAD_SIZE,
                    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']
                ],
                'base_url' => BASE_URL
            ]
        ];
        
        echo json_encode($config);
        exit; // Important: getConfigData is meant to be a terminal action.
    }
}

// Handle direct API requests to config.php (e.g., ?action=get-config)
// This block is for when config.php is the entry point, not when included.
if (isset($_GET['action']) && !$_config_php_error_occurred) { // Proceed only if config setup was ok
    // Start a new output buffer specifically for this direct action response.
    if(ob_get_level() > 0) { // Clean any buffer that might exist (e.g. from a previous ob_end_clean failure)
        ob_end_clean();
    }
    ob_start();

    // Set headers for this direct response
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");

    // Handle preflight CORS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        if(ob_get_level() > 0) ob_end_flush(); // Send headers & exit
        exit;
    }

    $action = $_GET['action'];
    try {
        switch ($action) {
            case 'get-config':
                getConfigData(); // This function echoes JSON and exits
                break;
            default:
                // Use a simplified direct JSON error for invalid actions here,
                // as returnResponse might have complex interactions with buffering if called here.
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid action specified for config.php']);
                // Not using returnResponse here to avoid its exit call conflicting with this block's exit.
        }
    } catch (Exception $e) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(['status' => 'error', 'message' => 'Server error processing config.php action: ' . $e->getMessage()]);
    }
    if(ob_get_level() > 0) ob_end_flush(); // Send the output from this action
    exit; // Ensure script terminates after handling the direct action
}

// If config.php is just included (no direct action and no error during setup),
// it should have reached here silently, with its initial buffer cleaned.
?> 