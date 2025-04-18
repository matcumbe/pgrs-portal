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

// Log request details
error_log("Request received: " . $_SERVER['REQUEST_METHOD'] . " " . $path);
error_log("Query parameters: " . print_r($params, true));

try {
    // Router with improved error handling
    switch (true) {
        case preg_match('/\/api\/stations\/(\w+)/', $path, $matches):
            $type = $matches[1];
            error_log("Fetching stations for type: " . $type);
            $data = getStationsByType($type);
            sendSuccess($data);
            break;
            
        case preg_match('/\/api\/station\/(\w+)/', $path, $matches):
            getStationById($matches[1]);
            break;
            
        case $path === '/api/provinces':
            $region = $params['region'] ?? '';
            error_log("Fetching provinces for region: " . $region);
            $data = getProvinces($region);
            sendSuccess($data);
            break;
            
        case $path === '/api/cities':
            $province = $params['province'] ?? '';
            error_log("Fetching cities for province: " . $province);
            $data = getCities($province);
            sendSuccess($data);
            break;
            
        case $path === '/api/search':
            error_log("Performing search with params: " . print_r($params, true));
            $data = searchStations($params);
            sendSuccess($data);
            break;
            
        case $path === '/api/orders':
            getOrders($params['type'] ?? '');
            break;
            
        case $path === '/api/accuracy-classes':
            getAccuracyClasses();
            break;
            
        case $path === '/api/barangays':
            getUniqueBarangays($params['city'] ?? '');
            break;
            
        case $path === '/api/regions':
            getUniqueRegions();
            break;
            
        case $path === '/api/radius-search':
            error_log("Performing radius search with params: " . print_r($params, true));
            $data = searchByRadius($params);
            sendSuccess($data);
            break;
            
        default:
            error_log("Invalid path requested: " . $path);
            sendError('Not found', 404);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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

// Get stations by type
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

// Get station by ID
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
            echo json_encode($result->fetch_assoc());
            return;
        }
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'Station not found']);
}

// Get provinces by region
function getProvinces($region) {
    global $db;
    
    $sql = "SELECT DISTINCT province FROM (
        SELECT province FROM vgcp_stations WHERE region = ?
        UNION
        SELECT province FROM hgcp_stations WHERE region = ?
        UNION
        SELECT province FROM grav_stations WHERE region = ?
    ) as provinces ORDER BY province";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $region, $region, $region);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get cities by province
function getCities($province) {
    global $db;
    
    $sql = "SELECT DISTINCT city FROM (
        SELECT city FROM vgcp_stations WHERE province = ?
        UNION
        SELECT city FROM hgcp_stations WHERE province = ?
        UNION
        SELECT city FROM grav_stations WHERE province = ?
    ) as cities ORDER BY city";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $province, $province, $province);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get orders based on GCP type
function getOrders($type) {
    global $db;
    
    $column = '';
    $table = '';
    
    switch ($type) {
        case 'vertical':
            $table = 'vgcp_stations';
            $column = 'elevation_order';
            break;
        case 'horizontal':
            $table = 'hgcp_stations';
            $column = 'horizontal_order';
            break;
        case 'gravity':
            $table = 'grav_stations';
            $column = '`order`';
            break;
        default:
            die(json_encode(['error' => 'Invalid station type']));
    }
    
    $sql = "SELECT DISTINCT $column FROM $table WHERE $column IS NOT NULL ORDER BY $column";
    $result = $db->query($sql);
    
    if ($result) {
        $orders = array_map(function($row) use ($column) {
            return $row[str_replace('`', '', $column)];
        }, $result->fetch_all(MYSQLI_ASSOC));
        echo json_encode($orders);
    } else {
        echo json_encode(['error' => $db->error]);
    }
}

// Get accuracy classes (VGCP only)
function getAccuracyClasses() {
    global $db;
    
    $sql = "SELECT DISTINCT accuracy_class FROM vgcp_stations WHERE accuracy_class IS NOT NULL ORDER BY accuracy_class";
    $result = $db->query($sql);
    
    if ($result) {
        $classes = array_map(function($row) {
            return $row['accuracy_class'];
        }, $result->fetch_all(MYSQLI_ASSOC));
        echo json_encode($classes);
    } else {
        echo json_encode(['error' => $db->error]);
    }
}

// Get unique barangays for a given city
function getUniqueBarangays($city) {
    global $db;
    
    if (empty($city)) {
        echo json_encode([]);
        return;
    }
    
    $sql = "SELECT DISTINCT barangay FROM (
        SELECT barangay FROM vgcp_stations WHERE city = ? AND barangay IS NOT NULL AND barangay != ''
        UNION
        SELECT barangay FROM hgcp_stations WHERE city = ? AND barangay IS NOT NULL AND barangay != ''
        UNION
        SELECT barangay FROM grav_stations WHERE city = ? AND barangay IS NOT NULL AND barangay != ''
    ) as barangays ORDER BY barangay";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $city, $city, $city);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $barangays = array_map(function($row) {
            return $row['barangay'];
        }, $result->fetch_all(MYSQLI_ASSOC));
        echo json_encode(array_values(array_filter($barangays)));
    } else {
        echo json_encode(['error' => $db->error]);
    }
}

// Get unique regions from all station types
function getUniqueRegions() {
    global $db;
    
    $sql = "SELECT DISTINCT region FROM (
        SELECT region FROM vgcp_stations WHERE region IS NOT NULL AND region != ''
        UNION
        SELECT region FROM hgcp_stations WHERE region IS NOT NULL AND region != ''
        UNION
        SELECT region FROM grav_stations WHERE region IS NOT NULL AND region != ''
    ) as regions ORDER BY region";
    
    $result = $db->query($sql);
    
    if ($result) {
        $regions = array_map(function($row) {
            return $row['region'];
        }, $result->fetch_all(MYSQLI_ASSOC));
        echo json_encode(array_values(array_filter($regions)));
    } else {
        echo json_encode(['error' => $db->error]);
    }
}

// Get unique provinces for a given region
function getUniqueProvinces($region) {
    global $db;
    
    if (empty($region)) {
        echo json_encode([]);
        return;
    }
    
    $sql = "SELECT DISTINCT province FROM (
        SELECT province FROM vgcp_stations WHERE region = ? AND province IS NOT NULL AND province != ''
        UNION
        SELECT province FROM hgcp_stations WHERE region = ? AND province IS NOT NULL AND province != ''
        UNION
        SELECT province FROM grav_stations WHERE region = ? AND province IS NOT NULL AND province != ''
    ) as provinces ORDER BY province";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $region, $region, $region);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $provinces = array_map(function($row) {
            return $row['province'];
        }, $result->fetch_all(MYSQLI_ASSOC));
        echo json_encode(array_values(array_filter($provinces)));
    } else {
        echo json_encode(['error' => $db->error]);
    }
}

// Get unique cities for a given province
function getUniqueCities($province) {
    global $db;
    
    if (empty($province)) {
        echo json_encode([]);
        return;
    }
    
    $sql = "SELECT DISTINCT city FROM (
        SELECT city FROM vgcp_stations WHERE province = ? AND city IS NOT NULL AND city != ''
        UNION
        SELECT city FROM hgcp_stations WHERE province = ? AND city IS NOT NULL AND city != ''
        UNION
        SELECT city FROM grav_stations WHERE province = ? AND city IS NOT NULL AND city != ''
    ) as cities ORDER BY city";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sss', $province, $province, $province);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $cities = array_map(function($row) {
            return $row['city'];
        }, $result->fetch_all(MYSQLI_ASSOC));
        echo json_encode(array_values(array_filter($cities)));
    } else {
        echo json_encode(['error' => $db->error]);
    }
}

// Search stations
function searchStations($params) {
    global $db;
    
    $type = $params['type'] ?? '';
    $table = '';
    $orderColumn = '';
    
    switch ($type) {
        case 'vertical':
            $table = 'vgcp_stations';
            $orderColumn = 'elevation_order';
            break;
        case 'horizontal':
            $table = 'hgcp_stations';
            $orderColumn = 'horizontal_order';
            break;
        case 'gravity':
            $table = 'grav_stations';
            $orderColumn = '`order`';
            break;
        default:
            throw new Exception('Invalid station type');
    }
    
    $conditions = [];
    $values = [];
    $types = '';
    
    if (!empty($params['order'])) {
        $conditions[] = "$orderColumn = ?";
        $values[] = $params['order'];
        $types .= 's';
    }
    
    if ($type === 'vertical' && !empty($params['accuracy'])) {
        $conditions[] = "accuracy_class = ?";
        $values[] = $params['accuracy'];
        $types .= 's';
    }
    
    if (!empty($params['region'])) {
        $conditions[] = "region = ?";
        $values[] = $params['region'];
        $types .= 's';
    }
    
    if (!empty($params['province'])) {
        $conditions[] = "province = ?";
        $values[] = $params['province'];
        $types .= 's';
    }
    
    if (!empty($params['city'])) {
        $conditions[] = "city = ?";
        $values[] = $params['city'];
        $types .= 's';
    }
    
    if (!empty($params['barangay'])) {
        $conditions[] = "barangay = ?";
        $values[] = $params['barangay'];
        $types .= 's';
    }
    
    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    $sql = "SELECT * FROM $table $whereClause";
    
    if (!empty($values)) {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $db->error);
        }
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            throw new Exception('Database error: ' . $stmt->error);
        }
        $result = $stmt->get_result();
    } else {
        $result = $db->query($sql);
        if (!$result) {
            throw new Exception('Database error: ' . $db->error);
        }
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Search by radius
function searchByRadius($params) {
    global $db;
    
    if (empty($params['lat']) || empty($params['lng']) || empty($params['radius'])) {
        throw new Exception('Missing parameters');
    }
    
    $lat = floatval($params['lat']);
    $lng = floatval($params['lng']);
    $radius = floatval($params['radius']);
    
    // Haversine formula
    $sql = "SELECT *, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance 
            FROM (
                SELECT * FROM vgcp_stations
                UNION ALL
                SELECT * FROM hgcp_stations
                UNION ALL
                SELECT * FROM grav_stations
            ) as all_stations
            HAVING distance <= ?
            ORDER BY distance";
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $stmt->bind_param('dddd', $lat, $lng, $lat, $radius);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

$db->close(); 