<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_users');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}

$sql = "SELECT station_id, admin_user, action, details, timestamp FROM station_activity_log ORDER BY timestamp DESC LIMIT 100";
$result = $conn->query($sql);
if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . $conn->error]);
    exit;
}

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$conn->close();
echo json_encode(['success' => true, 'logs' => $logs]);
?>



