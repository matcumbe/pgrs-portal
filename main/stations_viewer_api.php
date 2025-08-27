<?php
// stations_viewer_api.php (ported)
header('Content-Type: application/json');
require_once 'config.php';

$allowed_tables = ['grav_stations', 'hgcp_stations', 'vgcp_stations'];

function convertDMSToDecimal($degrees, $minutes, $seconds) {
    if (!is_numeric($degrees) || !is_numeric($minutes) || !is_numeric($seconds)) {
        return 0.0;
    }
    $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
    return round($decimal, 6);
}

function convertDecimalToDMS($decimal) {
    if (!is_numeric($decimal)) {
        return [0, 0, 0.0];
    }
    $degrees = intval($decimal);
    $minutes = intval(($decimal - $degrees) * 60);
    $seconds = round((($decimal - $degrees) * 60 - $minutes) * 60, 6);
    return [$degrees, $minutes, $seconds];
}

function transformStationData($row, $type) {
    $transformed = [];
    $transformed['station_id'] = $row['station_id'] ?? $row['station_code'] ?? '';
    $transformed['station_name'] = $row['station_name'] ?? '';
    $transformed['region'] = $row['region'] ?? '';
    $transformed['province'] = $row['province'] ?? '';
    $transformed['city'] = $row['city'] ?? '';
    $transformed['barangay'] = $row['barangay'] ?? '';
    $transformed['description'] = $row['description'] ?? '';
    $transformed['island_group'] = $row['island_group'] ?? '';
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
    if (isset($row['latitude']) && is_numeric($row['latitude'])) {
        $transformed['latitude'] = floatval($row['latitude']);
    }
    if (isset($row['longitude']) && is_numeric($row['longitude'])) {
        $transformed['longitude'] = floatval($row['longitude']);
    }
    // Back-compat for new schemas: fill station_id from id if missing
    if ((empty($transformed['station_id']) || $transformed['station_id'] === '') && isset($row['id'])) {
        $transformed['station_id'] = (string)$row['id'];
    }
    // Back-compat for city name
    if ((empty($transformed['city']) || $transformed['city'] === '') && isset($row['city_or_municipality'])) {
        $transformed['city'] = $row['city_or_municipality'];
    }
    foreach ($row as $key => $value) {
        if (!isset($transformed[$key])) {
            $transformed[$key] = $value;
        }
    }
    return $transformed;
}

function transformDataForDatabase($row, $type) {
    $transformed = [];
    foreach ($row as $key => $value) {
        $transformed[$key] = $value;
    }
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

function logStationActivity($conn, $table, $station_id, $admin_user, $action, $details = '') {
    try {
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

function getCurrentUser() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }
    $token = str_replace('Bearer ', '', $authHeader);
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return null;
    }
    list($header, $payload, $signature) = $tokenParts;
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
    $deleteIds = $input['deleteIds'] ?? [];
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
    $currentUser = getCurrentUser();
    $adminUser = $currentUser ? $currentUser->username : 'unknown';
    // Map view/table alias to physical table for writes
    $tableMap = [
        'hgcp_stations' => 'hgcp_stations_new',
        'vgcp_stations' => 'vgcp_stations_new',
        'grav_stations' => 'grav_stations_new'
    ];
    $targetTable = isset($tableMap[$table]) ? $tableMap[$table] : $table;
    $colres = $conn->query("SHOW COLUMNS FROM `$targetTable`");
    $columns = [];
    while ($col = $colres->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    // Determine primary key field present in target table
    $keyCol = in_array('station_id', $columns, true) ? 'station_id' : (in_array('id', $columns, true) ? 'id' : null);
    if (!$append) {
        $existingData = [];
        $result = $conn->query("SELECT * FROM `$targetTable`");
        while ($row = $result->fetch_assoc()) {
            if ($keyCol && isset($row[$keyCol])) {
                $existingData[trim((string)$row[$keyCol])] = $row;
            }
        }
        $newDataMap = [];
        foreach ($data as $newRow) {
            if ($keyCol) {
                $incomingKey = $newRow[$keyCol] ?? ($keyCol === 'id' ? ($newRow['station_id'] ?? null) : ($newRow['id'] ?? null));
                if ($incomingKey !== null && $incomingKey !== '') {
                    $newDataMap[trim((string)$incomingKey)] = $newRow;
                }
            }
        }
        foreach ($newDataMap as $keyValue => $newRow) {
            if (!isset($existingData[$keyValue])) {
                logStationActivity($conn, $table, $keyValue, $adminUser, 'add', json_encode(['table' => $table, 'data' => $newRow]));
            } else {
                $oldRow = $existingData[$keyValue];
                $changed = false;
                $changes = [];
                foreach ($columns as $col) {
                    if ($col === $keyCol) continue;
                    $oldVal = array_key_exists($col, $oldRow) ? (string)$oldRow[$col] : '';
                    $newVal = array_key_exists($col, $newRow) ? (string)$newRow[$col] : '';
                    if ($oldVal !== $newVal) {
                        $changed = true;
                        $changes[] = "$col: \"$oldVal\" → \"$newVal\"";
                    }
                }
                if ($changed) {
                    logStationActivity($conn, $table, $keyValue, $adminUser, 'update', json_encode([
                        'table' => $table,
                        'changes' => $changes,
                        'before' => $oldRow,
                        'after' => $newRow
                    ]));
                }
            }
        }
        foreach ($existingData as $keyValue => $oldRow) {
            if (!isset($newDataMap[$keyValue])) {
                logStationActivity($conn, $table, $keyValue, $adminUser, 'delete', json_encode(['table' => $table, 'data' => $oldRow]));
            }
        }
        $conn->query("DELETE FROM `$targetTable`");
    }
    // When append=true, optionally process deletions first
    if ($append && !empty($deleteIds) && $keyCol) {
        $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
        $types = str_repeat('s', count($deleteIds));
        $sql = "DELETE FROM `$targetTable` WHERE `$keyCol` IN ($placeholders)";
        $stmtDel = $conn->prepare($sql);
        $stmtDel->bind_param($types, ...array_map('strval', $deleteIds));
        $stmtDel->execute();
        $stmtDel->close();
        // Log deletions
        foreach ($deleteIds as $did) {
            logStationActivity($conn, $table, (string)$did, $adminUser, 'delete', json_encode(['table' => $table, 'data' => ['id' => $did]]));
        }
    }
    if (count($data) > 0) {
        if ($append) {
            foreach ($data as $row) {
                $type = str_replace('_stations', '', $table);
                $dbRow = transformDataForDatabase($row, $type);
                // Map synonyms to match DB columns
                if (in_array('id', $columns, true) && (!isset($dbRow['id']) || $dbRow['id'] === null || $dbRow['id'] === '')) {
                    if (isset($row['station_id']) && $row['station_id'] !== '') {
                        $dbRow['id'] = $row['station_id'];
                    }
                }
                if (in_array('station_id', $columns, true) && (!isset($dbRow['station_id']) || $dbRow['station_id'] === null || $dbRow['station_id'] === '')) {
                    if (isset($row['id']) && $row['id'] !== '') {
                        $dbRow['station_id'] = $row['id'];
                    }
                }
                if (in_array('city_or_municipality', $columns, true) && (!isset($dbRow['city_or_municipality']) || $dbRow['city_or_municipality'] === null || $dbRow['city_or_municipality'] === '')) {
                    if (isset($row['city']) && $row['city'] !== '') {
                        $dbRow['city_or_municipality'] = $row['city'];
                    }
                }
                if (in_array('city', $columns, true) && (!isset($dbRow['city']) || $dbRow['city'] === null || $dbRow['city'] === '')) {
                    if (isset($row['city_or_municipality']) && $row['city_or_municipality'] !== '') {
                        $dbRow['city'] = $row['city_or_municipality'];
                    }
                }
                $incomingKey = $row[$keyCol] ?? ($keyCol === 'id' ? ($row['station_id'] ?? null) : ($row['id'] ?? null));
                if ($incomingKey) {
                    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM `$targetTable` WHERE `$keyCol` = ?");
                    $checkStmt->bind_param('s', $incomingKey);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    $exists = $result->fetch_assoc()['count'] > 0;
                    $checkStmt->close();
                    if ($exists) {
                        $getStmt = $conn->prepare("SELECT * FROM `$targetTable` WHERE `$keyCol` = ?");
                        $getStmt->bind_param('s', $incomingKey);
                        $getStmt->execute();
                        $existingRow = $getStmt->get_result()->fetch_assoc();
                        $getStmt->close();
                        $updateFields = [];
                        $updateValues = [];
                        foreach ($columns as $col) {
                            if ($col !== $keyCol) {
                                $updateFields[] = "`$col` = ?";
                                $updateValues[] = $dbRow[$col] ?? null;
                            }
                        }
                        $updateValues[] = $incomingKey;
                        $updateSql = "UPDATE `$targetTable` SET " . implode(', ', $updateFields) . " WHERE `$keyCol` = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->bind_param(str_repeat('s', count($updateValues)), ...$updateValues);
                        $updateStmt->execute();
                        $updateStmt->close();
                        $changes = [];
                        foreach ($columns as $col) {
                            if ($col !== $keyCol && $existingRow[$col] !== $dbRow[$col]) {
                                $changes[] = "$col: \"{$existingRow[$col]}\" → \"{$dbRow[$col]}\"";
                            }
                        }
                        if (!empty($changes)) {
                            logStationActivity($conn, $table, $incomingKey, $adminUser, 'update', json_encode([
                                'table' => $table,
                                'changes' => $changes,
                                'before' => $existingRow,
                                'after' => $dbRow
                            ]));
                        }
                    } else {
                        $colList = '`' . implode('`,`', $columns) . '`';
                        $insertStmt = $conn->prepare("INSERT INTO `$targetTable` ($colList) VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")");
                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $dbRow[$col] ?? null;
                        }
                        $insertStmt->bind_param(str_repeat('s', count($values)), ...$values);
                        $insertStmt->execute();
                        $insertStmt->close();
                        logStationActivity($conn, $table, $incomingKey, $adminUser, 'add', json_encode(['table' => $table, 'data' => $dbRow]));
                    }
                } else {
                    $colList = '`' . implode('`,`', $columns) . '`';
                    $insertStmt = $conn->prepare("INSERT INTO `$targetTable` ($colList) VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")");
                    $values = [];
                    foreach ($columns as $col) {
                        $values[] = $dbRow[$col] ?? null;
                    }
                    $insertStmt->bind_param(str_repeat('s', count($values)), ...$values);
                    $insertStmt->execute();
                    $insertStmt->close();
                    logStationActivity($conn, $table, 'N/A', $adminUser, 'add', json_encode(['table' => $table, 'data' => $dbRow]));
                }
            }
        } else {
            $colList = '`' . implode('`,`', $columns) . '`';
            $stmt = $conn->prepare("INSERT INTO `$targetTable` ($colList) VALUES (" . rtrim(str_repeat('?,', count($columns)), ',') . ")");
            foreach ($data as $row) {
                $type = str_replace('_stations', '', $table);
                $dbRow = transformDataForDatabase($row, $type);
                // Map synonyms to match DB columns
                if (in_array('id', $columns, true) && (!isset($dbRow['id']) || $dbRow['id'] === null || $dbRow['id'] === '')) {
                    if (isset($row['station_id']) && $row['station_id'] !== '') {
                        $dbRow['id'] = $row['station_id'];
                    }
                }
                if (in_array('station_id', $columns, true) && (!isset($dbRow['station_id']) || $dbRow['station_id'] === null || $dbRow['station_id'] === '')) {
                    if (isset($row['id']) && $row['id'] !== '') {
                        $dbRow['station_id'] = $row['id'];
                    }
                }
                if (in_array('city_or_municipality', $columns, true) && (!isset($dbRow['city_or_municipality']) || $dbRow['city_or_municipality'] === null || $dbRow['city_or_municipality'] === '')) {
                    if (isset($row['city']) && $row['city'] !== '') {
                        $dbRow['city_or_municipality'] = $row['city'];
                    }
                }
                if (in_array('city', $columns, true) && (!isset($dbRow['city']) || $dbRow['city'] === null || $dbRow['city'] === '')) {
                    if (isset($row['city_or_municipality']) && $row['city_or_municipality'] !== '') {
                        $dbRow['city'] = $row['city_or_municipality'];
                    }
                }
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $dbRow[$col] ?? null;
                }
                $stmt->bind_param(str_repeat('s', count($values)), ...$values);
                $stmt->execute();
                $keyValue = ($keyCol && isset($row[$keyCol])) ? $row[$keyCol] : (($keyCol === 'id') ? ($row['station_id'] ?? 'N/A') : ($row['id'] ?? 'N/A'));
                logStationActivity($conn, $table, (string)$keyValue, $adminUser, 'add', json_encode(['table' => $table, 'data' => $dbRow]));
            }
            $stmt->close();
        }
    }
    $conn->close();
    echo json_encode(['success' => true]);
    exit;
}

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
    $type = str_replace('_stations', '', $table);
    $transformedRow = transformStationData($row, $type);
    $rows[] = $transformedRow;
}
$columns = [];
if (count($rows) > 0) {
    $columns = array_keys($rows[0]);
} else {
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
?>


