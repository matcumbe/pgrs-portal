<?php
require_once 'config.php';

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set up error handler to return JSON instead of HTML
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    header('Content-Type: application/json');
    die(json_encode([
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'type' => 'PHP Error'
    ]));
});

// Set up exception handler
set_exception_handler(function($e) {
    error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    header('Content-Type: application/json');
    die(json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'type' => 'Exception'
    ]));
});

header('Content-Type: application/json');

// Initialize database connection
$db = null;
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    $db->set_charset(DB_CHARSET);
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die(json_encode([
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
    ]));
}

// Get request path and parameters
$path = $_GET['path'] ?? '';
$query = $_SERVER['QUERY_STRING'];
parse_str($query ?? '', $params);
unset($params['path']);

// Get request method and body
$method = $_SERVER['REQUEST_METHOD'];
$body = null;
if ($method == 'POST' || $method == 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
}

// Log request details
error_log("Admin API Request received: " . $method . " " . $path);
error_log("Query parameters: " . print_r($params, true));
if ($body) {
    error_log("Request body: " . print_r($body, true));
}

try {
    // Router with improved error handling for admin operations
    switch (true) {
        // CRUD operations for stations
        case $method == 'GET' && preg_match('/\/api\/admin\/stations\/(\w+)/', $path, $matches):
            $type = $matches[1];
            error_log("Admin: Fetching stations for type: " . $type);
            $data = getStationsByType($type);
            sendSuccess($data);
            break;
            
        case $method == 'GET' && preg_match('/\/api\/admin\/station\/(\w+)/', $path, $matches):
            $id = $matches[1];
            error_log("Admin: Fetching station by ID: " . $id);
            $data = getStationById($id);
            sendSuccess($data);
            break;
            
        case $method == 'POST' && $path == '/api/admin/station':
            error_log("Admin: Creating new station");
            $data = createStation($body);
            sendSuccess($data);
            break;
            
        case $method == 'PUT' && preg_match('/\/api\/admin\/station\/(\w+)/', $path, $matches):
            $id = $matches[1];
            error_log("Admin: Updating station with ID: " . $id);
            $data = updateStation($id, $body);
            sendSuccess($data);
            break;
            
        case $method == 'DELETE' && preg_match('/\/api\/admin\/station\/(\w+)/', $path, $matches):
            $id = $matches[1];
            error_log("Admin: Deleting station with ID: " . $id);
            $data = deleteStation($id);
            sendSuccess($data);
            break;
            
        // Location data endpoints
        case $method == 'GET' && $path == '/api/admin/regions':
            error_log("Admin: Fetching regions");
            $data = getRegions();
            sendSuccess($data);
            break;
            
        case $method == 'GET' && $path == '/api/admin/provinces':
            $region = $params['region'] ?? '';
            error_log("Admin: Fetching provinces for region: " . $region);
            $data = getProvinces($region);
            sendSuccess($data);
            break;
            
        case $method == 'GET' && $path == '/api/admin/cities':
            $province = $params['province'] ?? '';
            error_log("Admin: Fetching cities for province: " . $province);
            $data = getCities($province);
            sendSuccess($data);
            break;
            
        case $method == 'GET' && $path == '/api/admin/barangays':
            $city = $params['city'] ?? '';
            error_log("Admin: Fetching barangays for city: " . $city);
            $data = getBarangays($city);
            sendSuccess($data);
            break;
            
        default:
            error_log("Admin API: Invalid path requested: " . $path);
            sendError('Not found', 404);
    }
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    sendError($e->getMessage(), 500);
}

// Close database connection
if ($db) {
    $db->close();
}

// Standard error response function
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Standard success response function
function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Get stations by type with improved error handling
function getStationsByType($type) {
    global $db;
    
    $table = '';
    switch (strtolower($type)) {
        case 'vertical':
            $table = 'vgcp_stations';
            break;
        case 'horizontal':
            $table = 'hgcp_stations';
            break;
        case 'gravity':
            $table = 'grav_stations';
            break;
        default:
            throw new Exception('Invalid station type');
    }
    
    $sql = "SELECT * FROM $table WHERE station_id IS NOT NULL";
    $result = $db->query($sql);
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $stations = [];
    while ($row = $result->fetch_assoc()) {
        // Transform the data to match expected format
        $transformedRow = transformStationData($row, $type);
        $stations[] = $transformedRow;
    }
    
    return $stations;
}

// Get station by ID with improved error handling
function getStationById($id) {
    global $db;
    
    // Try each table
    $tables = ['vgcp_stations', 'hgcp_stations', 'grav_stations'];
    
    foreach ($tables as $table) {
        $sql = "SELECT * FROM $table WHERE station_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $station = $result->fetch_assoc();
            
            // Determine type from table
            $type = '';
            if ($table == 'vgcp_stations') {
                $type = 'vertical';
            } elseif ($table == 'hgcp_stations') {
                $type = 'horizontal';
            } elseif ($table == 'grav_stations') {
                $type = 'gravity';
            }
            
            // Transform the data to match expected format
            $transformedStation = transformStationData($station, $type);
            $transformedStation['type'] = $type;
            
            return $transformedStation;
        }
    }
    
    throw new Exception('Station not found');
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

/**
 * Convert decimal degrees to degrees, minutes, seconds
 */
function convertDecimalToDMS($decimal) {
    if (!is_numeric($decimal)) {
        return [0, 0, 0];
    }
    
    $degrees = intval($decimal);
    $minutes = intval(($decimal - $degrees) * 60);
    $seconds = round((($decimal - $degrees) * 60 - $minutes) * 60, 6);
    
    return [$degrees, $minutes, $seconds];
}

// Create new station with improved error handling
function createStation($data) {
    global $db;
    
    if (!$data || !isset($data['type']) || !isset($data['station_name'])) {
        throw new Exception('Invalid station data');
    }
    
    $type = strtolower($data['type']);
    $table = '';
    
    switch ($type) {
        case 'vertical':
            $table = 'vgcp_stations';
            break;
        case 'horizontal':
            $table = 'hgcp_stations';
            break;
        case 'gravity':
            $table = 'grav_stations';
            break;
        default:
            throw new Exception('Invalid station type');
    }
    
    // Generate station_id if not provided
    if (!isset($data['station_id']) || empty($data['station_id'])) {
        $data['station_id'] = generateStationId($type);
    }
    
    // Transform coordinates from decimal to DMS format if needed
    $transformedData = transformDataForDatabase($data, $type);
    
    // Build SQL INSERT statement dynamically based on provided fields
    $columns = [];
    $placeholders = [];
    $types = '';
    $values = [];
    
    foreach ($transformedData as $key => $value) {
        // Skip type field as it's not in the database
        if ($key === 'type') continue;
        
        // Skip station_code for non-gravity stations
        if ($key === 'station_code' && $type !== 'gravity') continue;
        
        // Handle the order field specially
        if ($key === 'order' || $key === 'station_order') {
            $columns[] = '`order`';
        } else {
            $columns[] = $key;
        }
        
        $placeholders[] = '?';
        
        // Determine parameter type
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        
        $values[] = $value;
    }
    
    $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    error_log("SQL: " . $sql);
    error_log("Types: " . $types);
    error_log("Values: " . print_r($values, true));
    
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    // Bind parameters dynamically
    $bindParams = array($types);
    foreach ($values as $key => $value) {
        $bindParams[] = &$values[$key];
    }
    
    call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    return ['id' => $data['station_id']];
}

/**
 * Transform data from frontend format to database format
 */
function transformDataForDatabase($data, $type) {
    $transformed = $data;
    
    // Convert decimal coordinates to DMS format
    if (isset($data['latitude']) && is_numeric($data['latitude'])) {
        $dms = convertDecimalToDMS($data['latitude']);
        $transformed['latitude_degrees'] = $dms[0];
        $transformed['latitude_minutes'] = $dms[1];
        $transformed['latitude_seconds'] = $dms[2];
        unset($transformed['latitude']);
    }
    
    if (isset($data['longitude']) && is_numeric($data['longitude'])) {
        $dms = convertDecimalToDMS($data['longitude']);
        $transformed['longitude_degrees'] = $dms[0];
        $transformed['longitude_minutes'] = $dms[1];
        $transformed['longitude_seconds'] = $dms[2];
        unset($transformed['longitude']);
    }
    
    // Map field names for different station types
    if ($type === 'vertical') {
        if (isset($data['elevation'])) {
            $transformed['elevation_m'] = $data['elevation'];
            unset($transformed['elevation']);
        }
    } elseif ($type === 'gravity') {
        // Gravity stations use station_code instead of station_id
        if (isset($data['station_id'])) {
            $transformed['station_code'] = $data['station_id'];
            unset($transformed['station_id']);
        }
    }
    
    return $transformed;
}

// Update existing station with improved error handling
function updateStation($id, $data) {
    global $db;
    
    if (!$data || !isset($data['type'])) {
        throw new Exception('Invalid station data');
    }
    
    $type = strtolower($data['type']);
    $table = '';
    
    switch ($type) {
        case 'vertical':
            $table = 'vgcp_stations';
            break;
        case 'horizontal':
            $table = 'hgcp_stations';
            break;
        case 'gravity':
            $table = 'grav_stations';
            break;
        default:
            throw new Exception('Invalid station type');
    }
    
    // Check if station exists
    $checkSql = "SELECT station_id FROM $table WHERE station_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bind_param('s', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Station not found');
    }
    
    // Transform data for database format
    $transformedData = transformDataForDatabase($data, $type);
    
    // Build SQL UPDATE statement dynamically based on provided fields
    $updates = [];
    $types = '';
    $values = [];
    
    foreach ($transformedData as $key => $value) {
        // Skip type field and station_id as they shouldn't be updated
        if ($key === 'type' || $key === 'station_id') continue;
        
        // Skip station_code for non-gravity stations
        if ($key === 'station_code' && $type !== 'gravity') continue;
        
        $updates[] = "$key = ?";
        
        // Determine parameter type
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        
        $values[] = $value;
    }
    
    // Add id as last parameter
    $types .= 's';
    $values[] = $id;
    
    $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE station_id = ?";
    
    error_log("SQL: " . $sql);
    error_log("Types: " . $types);
    error_log("Values: " . print_r($values, true));
    
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    // Bind parameters dynamically
    $bindParams = array($types);
    foreach ($values as $key => $value) {
        $bindParams[] = &$values[$key];
    }
    
    call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    return ['id' => $id];
}

// Delete station with improved error handling
function deleteStation($id) {
    global $db;
    
    // Try each table
    $tables = ['vgcp_stations', 'hgcp_stations', 'grav_stations'];
    $deleted = false;
    
    foreach ($tables as $table) {
        $sql = "DELETE FROM $table WHERE station_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $deleted = true;
            error_log("Deleted station from table: $table");
            
            // Delete associated measurements if any
            deleteAssociatedMeasurements($id, $table);
        }
    }
    
    if (!$deleted) {
        throw new Exception('Station not found');
    }
    
    return ['id' => $id, 'status' => 'deleted'];
}

// Delete associated measurements when a station is deleted
function deleteAssociatedMeasurements($stationId, $stationTable) {
    global $db;
    
    $measurementTable = null;
    
    if ($stationTable == 'vgcp_stations') {
        $measurementTable = 'vgcp_measurements';
    } elseif ($stationTable == 'hgcp_stations') {
        $measurementTable = 'hgcp_measurements';
    } elseif ($stationTable == 'grav_stations') {
        $measurementTable = 'gravity_measurements';
    }
    
    if ($measurementTable) {
        $sql = "DELETE FROM $measurementTable WHERE station_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $stationId);
        $stmt->execute();
        
        error_log("Deleted associated measurements from: $measurementTable");
    }
}

// Generate unique station ID
function generateStationId($type) {
    global $db;
    
    $prefix = '';
    switch ($type) {
        case 'vertical':
            $prefix = 'V';
            break;
        case 'horizontal':
            $prefix = 'H';
            break;
        case 'gravity':
            $prefix = 'G';
            break;
    }
    
    $timestamp = date('YmdHis');
    $random = mt_rand(1000, 9999);
    $id = $prefix . $timestamp . $random;
    
    return $id;
}

// Get all regions
function getRegions() {
    global $db;
    
    $sql = "SELECT DISTINCT region FROM vgcp_stations WHERE region IS NOT NULL AND region != ''
            UNION 
            SELECT DISTINCT region FROM hgcp_stations WHERE region IS NOT NULL AND region != ''
            UNION
            SELECT DISTINCT region FROM grav_stations WHERE region IS NOT NULL AND region != ''
            ORDER BY region";
    
    $result = $db->query($sql);
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $regions = [];
    while ($row = $result->fetch_assoc()) {
        $regions[] = ['name' => $row['region']];
    }
    
    return $regions;
}

// Get provinces, optionally filtered by region
function getProvinces($region = '') {
    global $db;
    
    $where = '';
    if (!empty($region)) {
        $where = "WHERE region = '$region'";
    }
    
    $sql = "SELECT DISTINCT province FROM vgcp_stations $where
            UNION 
            SELECT DISTINCT province FROM hgcp_stations $where
            UNION
            SELECT DISTINCT province FROM grav_stations $where
            ORDER BY province";
    
    $result = $db->query($sql);
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = ['name' => $row['province']];
    }
    
    return $provinces;
}

// Get cities, optionally filtered by province
function getCities($province = '') {
    global $db;
    
    $where = '';
    if (!empty($province)) {
        $where = "WHERE province = '$province'";
    }
    
    $sql = "SELECT DISTINCT city FROM vgcp_stations $where
            UNION 
            SELECT DISTINCT city FROM hgcp_stations $where
            UNION
            SELECT DISTINCT city FROM grav_stations $where
            ORDER BY city";
    
    $result = $db->query($sql);
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $cities = [];
    while ($row = $result->fetch_assoc()) {
        $cities[] = ['name' => $row['city']];
    }
    
    return $cities;
}

// Get barangays, optionally filtered by city
function getBarangays($city = '') {
    global $db;
    
    $where = '';
    if (!empty($city)) {
        $where = "WHERE city = '$city'";
    }
    
    $sql = "SELECT DISTINCT barangay FROM vgcp_stations $where
            UNION 
            SELECT DISTINCT barangay FROM hgcp_stations $where
            UNION
            SELECT DISTINCT barangay FROM grav_stations $where
            ORDER BY barangay";
    
    $result = $db->query($sql);
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $barangays = [];
    while ($row = $result->fetch_assoc()) {
        $barangays[] = ['name' => $row['barangay']];
    }
    
    return $barangays;
} 