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
    
    return $result->fetch_all(MYSQLI_ASSOC);
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
            if ($table == 'vgcp_stations') {
                $station['type'] = 'vertical';
            } elseif ($table == 'hgcp_stations') {
                $station['type'] = 'horizontal';
            } elseif ($table == 'grav_stations') {
                $station['type'] = 'gravity';
            }
            
            return $station;
        }
    }
    
    throw new Exception('Station not found');
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
    
    // Build SQL INSERT statement dynamically based on provided fields
    $columns = [];
    $placeholders = [];
    $types = '';
    $values = [];
    
    foreach ($data as $key => $value) {
        // Skip type field as it's not in the database
        if ($key === 'type') continue;
        
        $columns[] = $key;
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
    
    // Build SQL UPDATE statement dynamically based on provided fields
    $updates = [];
    $types = '';
    $values = [];
    
    foreach ($data as $key => $value) {
        // Skip type field and station_id as they shouldn't be updated
        if ($key === 'type' || $key === 'station_id') continue;
        
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
    
    return ['id' => $id, 'affected_rows' => $stmt->affected_rows];
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