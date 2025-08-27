<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// Include database and application configuration
require_once 'config.php';

// Configure error handling for this script
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set common headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request path
$path = $_GET['path'] ?? '';
$path_segments = explode('/', trim($path, '/'));

// Main routing logic
try {
    if ($path_segments[0] === 'api') {
        error_log("API Route: " . implode('/', $path_segments)); // Debugging line
        // Route: /api/search/stations
        if (isset($path_segments[1]) && $path_segments[1] === 'search' && isset($path_segments[2]) && $path_segments[2] === 'stations') {
            if (!isset($_GET['type'])) {
                returnResponse('error', 'Station type parameter is required', null, 400);
            }
            $type = strtolower($_GET['type']);
            getStations($type, $_GET);
        }
        // Route: /api/provinces
        elseif (isset($path_segments[1]) && $path_segments[1] === 'provinces') {
            getProvinces();
        }
        // Route: /api/locations
        elseif (isset($path_segments[1]) && $path_segments[1] === 'locations') {
            if (isset($_GET['view']) && $_GET['view'] === 'tree') {
                getLocationsTree();
            } else {
                getLocations($_GET);
            }
        }
        // Fallback for invalid API routes
        else {
            returnResponse('error', 'Invalid API endpoint', null, 404);
        }
    } else {
        returnResponse('error', 'Invalid request path', null, 400);
    }
} catch (Exception $e) {
    error_log("Unhandled Exception in API router: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    returnResponse('error', 'An unexpected error occurred.', null, 500);
}


/**
 * Fetches a paginated, filtered, and sorted list of stations.
 *
 * @param string $type The station type ('horizontal', 'vertical', 'gravity').
 * @param array $params The GET request parameters for filtering, sorting, and pagination.
 */
function getStations($type, $params) {
    $db = connectDB();

    $tableMap = [
        'horizontal' => 'hgcp_stations_new',
        'vertical' => 'vgcp_stations_new',
        'gravity' => 'grav_stations_new'
    ];

    if (!isset($tableMap[$type])) {
        returnResponse('error', 'Invalid station type provided', null, 400);
    }
    $tableName = $tableMap[$type];

    // Whitelist for sortable columns to prevent SQL injection
    $sortableColumns = ['station_name', 'province', 'city_or_municipality', 'date_last_updated'];
    
    // Pagination parameters
    $page = isset($params['page']) && is_numeric($params['page']) ? (int)$params['page'] : 1;
    $limit = isset($params['limit']) && is_numeric($params['limit']) ? (int)$params['limit'] : 100;
    $offset = ($page - 1) * $limit;

    // Sorting parameters
    $sortBy = isset($params['sortBy']) && in_array($params['sortBy'], $sortableColumns) ? $params['sortBy'] : 'station_name';
    $sortOrder = isset($params['sortOrder']) && in_array(strtoupper($params['sortOrder']), ['ASC', 'DESC']) ? strtoupper($params['sortOrder']) : 'ASC';

    // Filtering parameters
    $filters = [];
    $queryParams = [];
    $queryTypes = '';

    if (!empty($params['province'])) {
        $filters[] = 'province = ?';
        $queryParams[] = $params['province'];
        $queryTypes .= 's';
    }
    // Support both 'city_or_municipality' and 'city' query params and columns
    $cityParam = null;
    if (isset($params['city_or_municipality']) && $params['city_or_municipality'] !== '') {
        $cityParam = $params['city_or_municipality'];
    } elseif (isset($params['city']) && $params['city'] !== '') {
        $cityParam = $params['city'];
    }
    if ($cityParam !== null) {
        // Detect which city column(s) exist on the chosen table
        $cityColumns = [];
        $colCheck1 = $db->query("SHOW COLUMNS FROM `$tableName` LIKE 'city_or_municipality'");
        if ($colCheck1 && $colCheck1->num_rows > 0) {
            $cityColumns[] = 'city_or_municipality';
        }
        if ($colCheck1) { $colCheck1->free(); }
        $colCheck2 = $db->query("SHOW COLUMNS FROM `$tableName` LIKE 'city'");
        if ($colCheck2 && $colCheck2->num_rows > 0) {
            $cityColumns[] = 'city';
        }
        if ($colCheck2) { $colCheck2->free(); }

        if (count($cityColumns) === 0) {
            // Fallback: assume 'city_or_municipality'
            $filters[] = 'city_or_municipality = ?';
            $queryParams[] = $cityParam;
            $queryTypes .= 's';
        } elseif (count($cityColumns) === 1) {
            $filters[] = "$cityColumns[0] = ?";
            $queryParams[] = $cityParam;
            $queryTypes .= 's';
        } else {
            // Both columns exist; allow match on either
            $filters[] = '(city_or_municipality = ? OR city = ?)';
            $queryParams[] = $cityParam;
            $queryParams[] = $cityParam;
            $queryTypes .= 'ss';
        }
    }
    if (!empty($params['barangay'])) {
        $filters[] = 'barangay = ?';
        $queryParams[] = $params['barangay'];
        $queryTypes .= 's';
    }
    if (!empty($params['search'])) {
        $filters[] = 'station_name LIKE ?';
        $queryParams[] = '%' . $params['search'] . '%';
        $queryTypes .= 's';
    }
    
    // --- Build queries ---
    
    // Query for total records (for pagination)
    $countSql = "SELECT COUNT(*) as total FROM `$tableName`";
    if (!empty($filters)) {
        $countSql .= " WHERE " . implode(' AND ', $filters);
    }

    // Query for data
    $dataSql = "SELECT * FROM `$tableName`";
    if (!empty($filters)) {
        $dataSql .= " WHERE " . implode(' AND ', $filters);
    }
    $dataSql .= " ORDER BY `$sortBy` $sortOrder LIMIT ? OFFSET ?";
    
    try {
        // --- Get total count ---
        $stmt = $db->prepare($countSql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare count statement: " . $db->error);
        }
        if (!empty($queryParams)) {
            $stmt->bind_param($queryTypes, ...$queryParams);
        }
        $stmt->execute();
        $countResult = $stmt->get_result()->fetch_assoc();
        $totalRecords = $countResult['total'];
        $totalPages = ceil($totalRecords / $limit);

        // --- Get paginated data ---
        $dataStmt = $db->prepare($dataSql);
        if ($dataStmt === false) {
            throw new Exception("Failed to prepare data statement: " . $db->error);
        }
        $dataParams = $queryParams;
        $dataTypes = $queryTypes;
        $dataParams[] = $limit;
        $dataTypes .= 'i';
        $dataParams[] = $offset;
        $dataTypes .= 'i';
        if (!empty($dataParams)) {
            $dataStmt->bind_param($dataTypes, ...$dataParams);
        }
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        $stations = $result->fetch_all(MYSQLI_ASSOC);
        
        $stmt->close();
        $dataStmt->close();
        $db->close();

        // Prepare response payload
        $response_data = [
            'pagination' => [
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'limit' => $limit
            ],
            'stations' => $stations
        ];
        
        returnResponse('success', 'Stations retrieved successfully', $response_data);

    } catch (Exception $e) {
        error_log("Error fetching stations: " . $e->getMessage());
        if (isset($db) && $db->ping()) $db->close();
        returnResponse('error', 'Failed to fetch station data.', null, 500);
    }
}

/**
 * Fetches a unique list of provinces from all station tables.
 */
function getProvinces() {
    $db = connectDB();
    
    try {
        $sql = "
            SELECT DISTINCT province FROM (
                SELECT province FROM hgcp_stations_new WHERE province IS NOT NULL AND province != ''
                UNION
                SELECT province FROM vgcp_stations_new WHERE province IS NOT NULL AND province != ''
                UNION  
                SELECT province FROM grav_stations_new WHERE province IS NOT NULL AND province != ''
            ) AS all_provinces
            ORDER BY province ASC
        ";
        
        $result = $db->query($sql);
        if ($result === false) {
            throw new Exception("Failed to execute provinces query: " . $db->error);
        }
        $provinces = $result->fetch_all(MYSQLI_ASSOC);
        
        $db->close();
        
        returnResponse('success', 'Provinces retrieved successfully', $provinces);
    } catch (Exception $e) {
        error_log("Error fetching provinces: " . $e->getMessage());
        if (isset($db) && $db->ping()) $db->close();
        returnResponse('error', 'Failed to fetch provinces.', null, 500);
    }
}

/**
 * Fetches unique cities or barangays based on a given province or city_or_municipality.
 *
 * @param array $params The GET request parameters. Expects 'province' and optionally 'city_or_municipality'.
 */
function getLocations($params) {
    $db = connectDB();
    
    $province = $params['province'] ?? null;
    $city_or_municipality = $params['city_or_municipality'] ?? null;
    
    if (!$province) {
        returnResponse('error', 'Province parameter is required to fetch locations.', null, 400);
    }

    try {
        $locations = [];
        if ($city_or_municipality) {
            // Fetch barangays for a given province and city_or_municipality
            $sql = "
                (SELECT DISTINCT barangay FROM hgcp_stations_new WHERE province = ? AND city_or_municipality = ? AND barangay IS NOT NULL AND barangay != '')
                UNION
                (SELECT DISTINCT barangay FROM vgcp_stations_new WHERE province = ? AND city_or_municipality = ? AND barangay IS NOT NULL AND barangay != '')
                UNION
                (SELECT DISTINCT barangay FROM grav_stations_new WHERE province = ? AND city_or_municipality = ? AND barangay IS NOT NULL AND barangay != '')
                ORDER BY barangay ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ssssss', $province, $city_or_municipality, $province, $city_or_municipality, $province, $city_or_municipality);
            $stmt->execute();
            $result = $stmt->get_result();
            $locations = $result->fetch_all(MYSQLI_ASSOC);
            $message = 'Barangays retrieved successfully';

        } else {
            // Fetch cities for a given province
            $sql = "
                (SELECT DISTINCT city_or_municipality as city_or_municipality FROM hgcp_stations_new WHERE province = ? AND city_or_municipality IS NOT NULL AND city_or_municipality != '')
                UNION
                (SELECT DISTINCT city_or_municipality as city_or_municipality FROM vgcp_stations_new WHERE province = ? AND city_or_municipality IS NOT NULL AND city_or_municipality != '')
                UNION
                (SELECT DISTINCT city_or_municipality FROM grav_stations_new WHERE province = ? AND city_or_municipality IS NOT NULL AND city_or_municipality != '')
                ORDER BY city_or_municipality ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('sss', $province, $province, $province);
            $stmt->execute();
            $result = $stmt->get_result();
            $locations = $result->fetch_all(MYSQLI_ASSOC);
            $message = 'Cities retrieved successfully';
        }
        
        $stmt->close();
        $db->close();
        
        returnResponse('success', $message, $locations);

    } catch (Exception $e) {
        error_log("Error fetching locations: " . $e->getMessage());
        if (isset($db) && $db->ping()) $db->close();
        returnResponse('error', 'Failed to fetch location data.', null, 500);
    }
}

/**
 * Fetches a hierarchical tree of locations (regions, provinces, cities, barangays)
 * from all station tables.
 */
function getLocationsTree() {
    error_log("Executing getLocationsTree");
    $db = connectDB();
    $type = isset($_GET['type']) ? strtolower($_GET['type']) : null;

    $sql_parts = [];
    $base_query = "SELECT DISTINCT region, province, %s as city_or_municipality, barangay FROM %s WHERE region IS NOT NULL AND region != '' AND province IS NOT NULL AND province != ''";

    if ($type === 'horizontal' || $type === null) {
        $sql_parts[] = sprintf($base_query, 'city_or_municipality', 'hgcp_stations_new');
    }
    if ($type === 'vertical' || $type === null) {
        $sql_parts[] = sprintf($base_query, 'city_or_municipality', 'vgcp_stations_new');
    }
    if ($type === 'gravity' || $type === null) {
        $sql_parts[] = sprintf($base_query, 'city_or_municipality', 'grav_stations_new');
    }

    if (empty($sql_parts)) {
        returnResponse('success', 'No locations for this type', []);
        return;
    }

    $sql = implode(" UNION ", $sql_parts);

    try {
        error_log("Executing SQL: " . $sql);
        $result = $db->query($sql);
        if ($result === false) {
            throw new Exception("Database query failed: " . $db->error);
        }
        $locations = $result->fetch_all(MYSQLI_ASSOC);
        $db->close();

        $tree = [];
        foreach ($locations as $location) {
            $region = $location['region'];
            $province = $location['province'];
            $city_or_municipality = $location['city_or_municipality'];
            $barangay = $location['barangay'];

            if (empty($region) || empty($province)) continue;

            if (!isset($tree[$region])) {
                $tree[$region] = [];
            }
            if (!isset($tree[$region][$province])) {
                $tree[$region][$province] = [];
            }
            if (!empty($city_or_municipality) && !isset($tree[$region][$province][$city_or_municipality])) {
                $tree[$region][$province][$city_or_municipality] = [];
            }
            if (!empty($barangay) && !in_array($barangay, $tree[$region][$province][$city_or_municipality])) {
                $tree[$region][$province][$city_or_municipality][] = $barangay;
            }
        }
        
        returnResponse('success', 'Locations tree retrieved successfully', $tree);

    } catch (Exception $e) {
        error_log("Error in getLocationsTree: " . $e->getMessage() . " | SQL: " . $sql);
        if (isset($db) && $db->ping()) $db->close();
        returnResponse('error', 'An error occurred while fetching the locations tree.', null, 500);
    }
}