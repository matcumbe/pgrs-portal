<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_db');
if ($conn->connect_error) {
    echo 'Connection failed: ' . $conn->connect_error;
    exit;
}

$tables = ['hgcp_stations', 'vgcp_stations', 'grav_stations'];

foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    $result = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($result) {
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
            $count++;
        }
        echo "Total columns: $count\n";
    } else {
        echo "Table not found or error\n";
    }
}

$conn->close();
?>

