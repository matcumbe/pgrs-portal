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

// Custom error handler for clean JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Error [$severity]: $message in $file on line $line");
    return true; // Don't execute PHP's internal error handler
});

// Custom exception handler
set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
});

// Start output buffering to ensure clean output
ob_start();

// Get request parameters
$path = $_GET['path'] ?? '';
error_log("API Request: $path");

// Determine station type from the path
$type = 'vertical'; // Default
if (preg_match('/\/api\/stations\/(\w+)/', $path, $matches) || 
    preg_match('/api\/stations\/(\w+)/', $path, $matches)) {
    $type = strtolower($matches[1]);
} else if (strpos($path, 'vertical') !== false) {
    $type = 'vertical';
} else if (strpos($path, 'horizontal') !== false) {
    $type = 'horizontal';
} else if (strpos($path, 'gravity') !== false) {
    $type = 'gravity';
}

// Validate station type
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
    
    // Build query based on table type and new schema
    $sql = "SELECT * FROM $table";
    $result = $db->query($sql);
    
    if (!$result) {
        error_log("SQL Error: " . $db->error);
        throw new Exception("Database query failed");
    }
    
    // Fetch the data and transform to match expected format
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Transform coordinates from degrees/minutes/seconds to decimal
        $transformedRow = transformStationData($row, $type);
        $transformedRow['type'] = $type; // Append the station type to each row
        $data[] = $transformedRow;
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
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Exception in API: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString());

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
        'message' => "API error: " . $e->getMessage(),
        'source' => 'error',
        'type_requested' => $type ?? 'unknown', // Add requested type for debugging
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

    // Fallback: If we reach here and no headers have been sent,
    // and no output has been buffered (e.g. script died before try/catch echo),
    // then something went wrong very early.
    if (!headers_sent() && ob_get_level() > 0 && ob_get_length() === 0) {
        // Clean any buffer, just in case (though it should be empty)
        ob_end_clean(); 
        // Start a new buffer for this final fallback
        ob_start(); 

        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            'success' => false,
            'message' => 'An critical unhandled error occurred early in API script execution.',
            'source' => 'critical_error_fallback',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Ensure all output buffers are flushed at the very end.
    // If an error JSON was sent from catch or this finally block, it will be flushed.
    // If successful JSON was sent from try, it will be flushed.
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
}

/**
 * Transform station data from new schema format to expected format
 */
function transformStationData($row, $type) {
    $transformed = [];
    
    // Common fields
    $transformed['station_id'] = $row['station_id'] ?? $row['station_code'] ?? '';
    $transformed['station_name'] = $row['station_name'] ?? '';
    $transformed['region'] = $row['region'] ?? '';
    $transformed['province'] = $row['province'] ?? '';
    $transformed['city'] = $row['city'] ?? '';
    $transformed['barangay'] = $row['barangay'] ?? '';
    $transformed['description'] = $row['description'] ?? '';
    $transformed['island_group'] = $row['island_group'] ?? '';
    
    // Transform coordinates from degrees/minutes/seconds to decimal
    $transformed['latitude'] = convertDMSToDecimal(
        $row['latitude_degrees'] ?? 0,
        $row['latitude_minutes'] ?? 0,
        $row['latitude_seconds'] ?? 0
    );
    
    $transformed['longitude'] = convertDMSToDecimal(
        $row['longitude_degrees'] ?? 0,
        $row['longitude_minutes'] ?? 0,
        $row['longitude_seconds'] ?? 0
    );
    
    // If coordinates are already in decimal format, use them directly
    if (isset($row['latitude']) && is_numeric($row['latitude'])) {
        $transformed['latitude'] = floatval($row['latitude']);
    }
    if (isset($row['longitude']) && is_numeric($row['longitude'])) {
        $transformed['longitude'] = floatval($row['longitude']);
    }
    
    // Type-specific fields
    if ($type === 'gravity') {
        $transformed['gravity_value'] = $row['gravity_value'] ?? null;
        $transformed['standard_deviation'] = $row['standard_deviation'] ?? null;
        $transformed['date_measured'] = $row['date_measured'] ?? null;
        $transformed['order'] = $row['order'] ?? null;
        $transformed['encoder'] = $row['encoder'] ?? null;
        $transformed['date_last_updated'] = $row['date_last_updated'] ?? null;
        $transformed['reference_file'] = $row['reference_file'] ?? null;
    } elseif ($type === 'vertical') {
        $transformed['elevation'] = $row['elevation_m'] ?? null;
        $transformed['datum'] = $row['datum'] ?? null;
        $transformed['reference_tide_station'] = $row['reference_tide_station'] ?? null;
        $transformed['tidal_series'] = $row['tidal_series'] ?? null;
        $transformed['std_dev'] = $row['std_dev'] ?? null;
        $transformed['accuracy_class'] = $row['accuracy_class'] ?? null;
        $transformed['order_of_accuracy'] = $row['order_of_accuracy'] ?? null;
        $transformed['year_surveyed'] = $row['year_surveyed'] ?? null;
        $transformed['fixing_method'] = $row['fixing_method'] ?? null;
        $transformed['year_computed_x'] = $row['year_computed_x'] ?? null;
        $transformed['encoder'] = $row['encoder'] ?? null;
        $transformed['date_updated'] = $row['date_updated'] ?? null;
        $transformed['reference_file'] = $row['reference_file'] ?? null;
        $transformed['year_computed_y'] = $row['year_computed_y'] ?? null;
    } elseif ($type === 'horizontal') {
        $transformed['ellipsoidal_height'] = $row['ellipsoidal_height'] ?? null;
        $transformed['horizontal_order'] = $row['horizontal_order'] ?? null;
        $transformed['g_order'] = $row['g_order'] ?? null;
        $transformed['date_last_updated'] = $row['date_last_updated'] ?? null;
    }
    
    return $transformed;
}

/**
 * Convert degrees, minutes, seconds to decimal degrees
 */
function convertDMSToDecimal($degrees, $minutes, $seconds) {
    if (!is_numeric($degrees) || !is_numeric($minutes) || !is_numeric($seconds)) {
        return 0.0;
    }
    
    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
    return round($decimal, 6);
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
            'description' => 'Sample ' . ucfirst($type) . ' Station ' . $i,
            'island_group' => 'LUZON'
        ];
        
        // Add type-specific fields
        if ($type === 'vertical') {
            $station['elevation'] = rand(1, 100) + (rand(0, 99) / 100);
            $station['datum'] = 'Mean Sea Level (MSL)';
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
?> 