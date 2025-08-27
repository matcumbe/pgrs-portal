<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test script started\n";

// Check if config file exists
if (file_exists('config.php')) {
    echo "Config file exists\n";
    require_once 'config.php';
    echo "Config loaded\n";
} else {
    echo "Config file not found\n";
    exit;
}

// Check if schema file exists
$schemaFile = 'updated schema.sql';
if (file_exists($schemaFile)) {
    echo "Schema file exists: $schemaFile\n";
    echo "File size: " . filesize($schemaFile) . " bytes\n";
} else {
    echo "Schema file not found: $schemaFile\n";
    exit;
}

// Test database connection
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        echo "Database connection failed: " . $db->connect_error . "\n";
    } else {
        echo "Database connection successful\n";
        $db->close();
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "Test script finished\n";
?>

