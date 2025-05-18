<?php
// Include database configuration
require_once 'config.php';

// Configure error handling - still log errors but return clean JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Custom error handler for clean JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    error_log("StationsAPI Error [$severity]: $message in $file on line $line");
    return true; // Don't execute PHP's internal error handler
});

// Custom exception handler
set_exception_handler(function($exception) {
    error_log("StationsAPI Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
});

// Start output buffering to ensure clean output
ob_start();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request parameters
$path = $_GET['path'] ?? '';
$requestedType = $_GET['type'] ?? '';
error_log("Stations API Request: $path, Type: $requestedType");

// Determine station type from various sources
$type = 'vertical'; // Default

// Check direct type parameter first
if (!empty($requestedType) && in_array(strtolower($requestedType), ['vertical', 'horizontal', 'gravity'])) {
    $type = strtolower($requestedType);
}
// Then check path patterns
else if (preg_match('/\/api\/stations\/(\w+)/', $path, $matches) || 
    preg_match('/api\/stations\/(\w+)/', $path, $matches)) {
    $type = strtolower($matches[1]);
} else if (strpos($path, 'vertical') !== false) {
    $type = 'vertical';
} else if (strpos($path, 'horizontal') !== false) {
    $type = 'horizontal';
} else if (strpos($path, 'gravity') !== false) {
    $type = 'gravity';
}

// Final validation
if (!in_array($type, ['vertical', 'horizontal', 'gravity'])) {
    $type = 'vertical'; // Default to vertical if invalid
}

try {
    // Attempt database connection
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    
    $db->set_charset(DB_CHARSET);
    
    // Map type to table name
    $tables = [
        'vertical' => 'vgcp_stations',
        'horizontal' => 'hgcp_stations',
        'gravity' => 'grav_stations'
    ];
    
    $table = $tables[$type];
    
    // Check if table exists
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        error_log("Table '$table' does not exist");
        throw new Exception("Table not found");
    }
    
    // Query the data - Remove LIMIT 100 to fetch all records
    $sql = "SELECT * FROM $table"; // Removed LIMIT 100
    $result = $db->query($sql);
    
    if (!$result) {
        error_log("SQL Error: " . $db->error);
        throw new Exception("Database query failed");
    }
    
    // Fetch the data
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    if (empty($data)) {
        error_log("No data found in table '$table'");
        $data = generateSampleStationData($type, 20);
    }
    
    // Output success
    echo json_encode([
        'success' => true,
        'data' => $data,
        'source' => 'database',
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type
    ]);
    
} catch (Exception $e) {
    error_log("Exception in stations-api: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\\nStack trace:\\n" . $e->getTraceAsString());

    // Clean any previous output buffer if it exists and has content
    if (ob_get_level() > 0 && ob_get_length() > 0) {
        ob_end_clean();
    }
    
    // Start a new buffer for the JSON error response
    ob_start();

    if (!headers_sent()) {
        http_response_code(500); // Internal Server Error
        header("Content-Type: application/json; charset=UTF-8"); // Ensure correct content type
    }
    
    echo json_encode([
        'success' => false,
        'message' => "stations-api error: " . $e->getMessage(),
        'source' => 'error',
        'type_requested' => $type ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Flush this JSON error response
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    exit; // Terminate script after sending error response

} finally {
    // This block will run even if an exit occurs in the catch block.
    // Close database connection if it was successfully opened.
    if (isset($db) && $db instanceof mysqli && empty($db->connect_error)) {
        $db->close();
    }

    // Fallback for critical early errors
    if (!headers_sent() && ob_get_level() > 0 && ob_get_length() === 0) {
        if (ob_get_level() > 0) ob_end_clean();
        ob_start();
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            'success' => false,
            'message' => 'A critical unhandled error occurred early in stations-api script execution.',
            'source' => 'critical_error_fallback',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Final flush of any output buffers
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
}

// Function to generate sample station data as fallback
function generateSampleStationData($type, $count = 20) {
    $data = [];
    
    for ($i = 1; $i <= $count; $i++) {
        $stationId = $type[0] . str_pad($i, 3, '0', STR_PAD_LEFT);
        $stationName = strtoupper($type) . '-' . $i;
        
        $station = [
            'station_id' => $stationId,
            'station_name' => $stationName,
            'latitude' => 14.5995 + (rand(-100, 100) / 1000),
            'longitude' => 120.9842 + (rand(-100, 100) / 1000),
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'city' => 'Manila',
            'barangay' => 'Barangay ' . rand(1, 20),
            'description' => 'Sample ' . ucfirst($type) . ' Station ' . $i
        ];
        
        // Add type-specific fields
        if ($type === 'vertical') {
            $station['elevation'] = rand(1, 100) + (rand(0, 99) / 100);
            $station['elevation_order'] = '1st';
            $station['accuracy_class'] = 'Class ' . rand(1, 3);
        } else if ($type === 'horizontal') {
            $station['ellipsoidal_height'] = rand(1, 100) + (rand(0, 99) / 100);
            $station['horizontal_order'] = '1st';
        } else if ($type === 'gravity') {
            $station['gravity_value'] = 978100 + rand(0, 500);
            $station['order'] = '1st';
        }
        
        $data[] = $station;
    }
    
    return $data;
} 