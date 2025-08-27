<?php
// stations_viewer.php
header('Content-Type: application/json');
require_once 'config.php'; // assumes DB credentials are here

$allowed_tables = ['grav_stations', 'hgcp_stations', 'vgcp_stations'];

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
        return [0, 0, 0.0];
    }
    
    $degrees = intval($decimal);
    $minutes = intval(($decimal - $degrees) * 60);
    $seconds = round((($decimal - $degrees) * 60 - $minutes) * 60, 6);
    
    return [$degrees, $minutes, $seconds];
}

/**
 * Transform station data from database format to frontend format
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
    
    // Add all other fields from the original row
    foreach ($row as $key => $value) {
        if (!isset($transformed[$key])) {
            $transformed[$key] = $value;
        }
    }
    
    return $transformed;
}

/**
 * Transform frontend data back to database format
 */
function transformDataForDatabase($row, $type) {
    $transformed = [];
    
    // Copy all fields
    foreach ($row as $key => $value) {
        $transformed[$key] = $value;
    }
    
    // Convert decimal coordinates back to DMS if needed
    if (isset($row['latitude']) && is_numeric($row['latitude'])) {
        $latDMS = convertDecimalToDMS($row['latitude']);
        $transformed['latitude_degrees'] = $latDMS[0];
        $transformed['latitude_minutes'] = $latDMS[1];
        $transformed['latitude_seconds'] = $latDMS[2];
    }
    
    if (isset($row['longitude']) && is_numeric($row['longitude'])) {
        $lonDMS = convertDecimalToDMS($row['longitude']);
        $transformed['longitude_degrees'] = $lonDMS[0];
        $transformed['longitude_minutes'] = $lonDMS[1];
        $transformed['longitude_seconds'] = $lonDMS[2];
    }
    
    return $transformed;
}

// Function to log station activity
function logStationActivity($conn, $table, $station_id, $admin_user, $action, $details = '') {
    try {
        // Connect to webgnis_users database for logging
        $logConn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_users');
        if ($logConn->connect_error) {
            error_log("Failed to connect to webgnis_users for logging: " . $logConn->connect_error);
            return false;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $stmt = $logConn->prepare("INSERT INTO station_activity_log (timestamp, station_id, admin_user, action, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $timestamp, $station_id, $admin_user, $action, $details);
        $result = $stmt->execute();
        $stmt->close();
        $logConn->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error logging station activity: " . $e->getMessage());
        return false;
    }
}

// Function to get current user from Authorization header
function getCurrentUser() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                 (isset($headers['authorization']) ? $headers['authorization'] : '');
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }
    
    $token = str_replace('Bearer ', '', $authHeader);
    $tokenParts = explode('.', $token);
    
    if (count($tokenParts) !== 3) {
        return null;
    }
    
    list($header, $payload, $signature) = $tokenParts;
    
    // Verify signature (using the same secret as in users_config.php)
    $verifySignature = base64_encode(hash_hmac('sha256', "$header.$payload", 'webgnis_secret_key', true));
    
    if ($signature !== $verifySignature) {
        return null;
    }
    
    $payload = json_decode(base64_decode($payload));
    
    if ($payload->exp < time()) {
        return null;
    }
    
    return $payload;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $table = $input['table'] ?? '';
    $data = $input['data'] ?? [];
    $append = isset($input['append']) ? (bool)$input['append'] : false;
    
    if (!in_array($table, $allowed_tables)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid table']);
        exit;
    }
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_db');
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }
    
    // Get current user for logging
    $currentUser = getCurrentUser();
    $adminUser = $currentUser ? $currentUser->username : 'unknown';
    
    // Get columns
    $colres = $conn->query("SHOW COLUMNS FROM `$table`");
    $columns = [];
    while ($col = $colres->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    
    if (!$append) {
        // For overwrite mode: fetch existing data before deletion for comparison
        $existingData = [];
        $result = $conn->query("SELECT * FROM `$table`");
        while ($row = $result->fetch_assoc()) {
            if (isset($row['station_id'])) {
                $existingData[trim((string)$row['station_id'])] = $row;
            }
        }
        // Build new data map by station_id
        $newDataMap = [];
        foreach ($data as $newRow) {
            if (isset($newRow['station_id'])) {
                $newDataMap[trim((string)$newRow['station_id'])] = $newRow;
            }
        }
        // Log updates and adds
        foreach ($newDataMap as $stationId => $newRow) {
            if (!isset($existingData[$stationId])) {
                // New row
                error_log("LOGGING ADD: $stationId");
                logStationActivity($conn, $table, $stationId, $adminUser, 'add', json_encode(['table' => $table, 'data' => $newRow]));
            } else {
                // Possible update
                $oldRow = $existingData[$stationId];
                $changed = false;
                $changes = [];
                foreach ($columns as $col) {
                    if ($col === 'station_id') continue;
                    $oldVal = array_key_exists($col, $oldRow) ? (string)$oldRow[$col] : '';
                    $newVal = array_key_exists($col, $newRow) ? (string)$newRow[$col] : '';
                    if ($oldVal !== $newVal) {
                        $changed = true;
                        $changes[] = "$col: \"$oldVal\" → \"$newVal\"";
                    }
                }
                if ($changed) {
                    error_log("LOGGING UPDATE: $stationId");
                    logStationActivity($conn, $table, $stationId, $adminUser, 'update', json_encode([
                        'table' => $table,
                        'changes' => $changes,
                        'before' => $oldRow,
                        'after' => $newRow
                    ]));
                } else {
                    error_log("UNCHANGED: $stationId");
                }
            }
        }
        // Log deletions (in old, not in new)
        foreach ($existingData as $stationId => $oldRow) {
            if (!isset($newDataMap[$stationId])) {
                error_log("LOGGING DELETE: $stationId");
                logStationActivity($conn, $table, $stationId, $adminUser, 'delete', json_encode(['table' => $table, 'data' => $oldRow]));
            }
        }
        // Overwrite: Delete all rows first
        $conn->query("DELETE FROM `$table`");
    }
    
    // Insert new data (append or overwrite)
    if (count($data) > 0) {
        if ($append) {
            // For append mode, handle updates and inserts separately
            foreach ($data as $row) {
                // Transform data back to database format
                $type = str_replace('_stations', '', $table);
                $dbRow = transformDataForDatabase($row, $type);
                $stationId = $row['station_id'] ?? null;
                if ($stationId) {
                    // Check if record exists
                    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM `$table` WHERE station_id = ?");
                    $checkStmt->bind_param('s', $stationId);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $exists = $result->fetch_assoc()['count'] > 0;
                    $checkStmt->close();
                    
                    if ($exists) {
                        // Get existing data for comparison
                        $getStmt = $conn->prepare("SELECT * FROM `$table` WHERE station_id = ?");
                        $getStmt->bind_param('s', $stationId);
                        $getStmt->execute();
                        $existingRow = $getStmt->get_result()->fetch_assoc();
                        $getStmt->close();
                        
                        // Update existing record
                        $updateFields = [];
                        $updateValues = [];
                        foreach ($columns as $col) {
                            if ($col !== 'station_id') {
                                $updateFields[] = "`$col` = ?";
                                $updateValues[] = $dbRow[$col] ?? null;
                            }
                        }
                        $updateValues[] = $stationId; // for WHERE clause
                        
                        $updateSql = "UPDATE `$table` SET " . implode(', ', $updateFields) . " WHERE station_id = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->bind_param(str_repeat('s', count($updateValues)), ...$updateValues);
                        $updateStmt->execute();
                        $updateStmt->close();
                        
                        // Log update with changes
                        $changes = [];
                        foreach ($columns as $col) {
                            if ($col !== 'station_id' && $existingRow[$col] !== $dbRow[$col]) {
                                $changes[] = "$col: \"{$existingRow[$col]}\" → \"{$dbRow[$col]}\"";
                            }
                        }
                        if (!empty($changes)) {
                            logStationActivity($conn, $table, $stationId, $adminUser, 'update', json_encode([
                                'table' => $table,
                                'changes' => $changes,
                                'before' => $existingRow,
                                'after' => $dbRow
                            ]));
                        }
                    } else {
                        // Insert new record
                        $colList = '`' . implode('`,`', $columns) . '`';
                        $insertStmt = $conn->prepare("INSERT INTO `$table` ($colList) VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")");
                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $dbRow[$col] ?? null;
                        }
                        $insertStmt->bind_param(str_repeat('s', count($values)), ...$values);
                        $insertStmt->execute();
                        $insertStmt->close();
                        
                        // Log addition
                        logStationActivity($conn, $table, $stationId, $adminUser, 'add', json_encode(['table' => $table, 'data' => $dbRow]));
                    }
                } else {
                    // Insert record without station_id
                    $colList = '`' . implode('`,`', $columns) . '`';
                    $insertStmt = $conn->prepare("INSERT INTO `$table` ($colList) VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")");
                    $values = [];
                    foreach ($columns as $col) {
                        $values[] = $dbRow[$col] ?? null;
                    }
                    $insertStmt->bind_param(str_repeat('s', count($values)), ...$values);
                    $insertStmt->execute();
                    $insertStmt->close();
                    
                    // Log addition (without station_id)
                    logStationActivity($conn, $table, 'N/A', $adminUser, 'add', json_encode(['table' => $table, 'data' => $dbRow]));
                }
            }
        } else {
            // For overwrite mode, use simple insert and log additions
            $colList = '`' . implode('`,`', $columns) . '`';
            $stmt = $conn->prepare("INSERT INTO `$table` ($colList) VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")");
            foreach ($data as $row) {
                // Transform data back to database format
                $type = str_replace('_stations', '', $table);
                $dbRow = transformDataForDatabase($row, $type);
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $dbRow[$col] ?? null;
                }
                $stmt->bind_param(str_repeat('s', count($values)), ...$values);
                $stmt->execute();
                
                // Log addition for overwrite mode
                $stationId = $row['station_id'] ?? 'N/A';
                logStationActivity($conn, $table, $stationId, $adminUser, 'add', json_encode(['table' => $table, 'data' => $dbRow]));
            }
            $stmt->close();
        }
    }
    $conn->close();
    echo json_encode(['success' => true]);
    exit;
}

// GET: fetch table data
$table = isset($_GET['table']) ? $_GET['table'] : 'hgcp_stations';
if (!in_array($table, $allowed_tables)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$sql = "SELECT * FROM `$table`";
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    // Transform the data for frontend consumption
    $type = str_replace('_stations', '', $table);
    $transformedRow = transformStationData($row, $type);
    $rows[] = $transformedRow;
}

// Get column names
$columns = [];
if (count($rows) > 0) {
    $columns = array_keys($rows[0]);
} else {
    // fallback: get columns from information_schema
    $colres = $conn->query("SHOW COLUMNS FROM `$table`");
    while ($col = $colres->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}

$conn->close();

echo json_encode([
    'columns' => $columns,
    'data' => $rows
]); 