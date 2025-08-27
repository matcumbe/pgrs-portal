<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_db');
if ($conn->connect_error) {
    echo 'Connection failed: ' . $conn->connect_error;
    exit;
}

echo "Station tables found:\n";
$result = $conn->query("SHOW TABLES LIKE '%_stations'");
while ($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}

echo "\nChecking columns for each table:\n";
$tables = ['hgcp_stations', 'vgcp_stations', 'grav_stations'];
foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    $result = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table not found or error\n";
    }
}

// Also check for _new tables
echo "\nChecking for _new tables:\n";
$result = $conn->query("SHOW TABLES LIKE '%_new'");
while ($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}

$conn->close();
?>
