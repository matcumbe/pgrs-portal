<?php
/**
 * Database Update Script
 * This script updates the webgnis_db database with the new schema from updated schema.sql
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Script started...\n";

require_once 'config.php';

echo "Config loaded...\n";

try {
    // Connect to the main database
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
    
    echo "Connected to database: " . DB_NAME . "\n";
    
    // Read the updated schema file
    $schemaFile = 'updated schema.sql';
    if (!file_exists($schemaFile)) {
        die("Schema file not found: $schemaFile\n");
    }
    
    echo "Reading schema file: $schemaFile\n";
    echo "File size: " . filesize($schemaFile) . " bytes\n";
    
    // Read the file content
    $sqlContent = file_get_contents($schemaFile);
    
    if ($sqlContent === false) {
        die("Failed to read schema file\n");
    }
    
    echo "File content read successfully\n";
    
    // Split into individual statements
    $statements = explode(';', $sqlContent);
    
    echo "Found " . count($statements) . " statements\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
            continue;
        }
        
        // Skip database creation/use statements
        if (stripos($statement, 'CREATE DATABASE') !== false || 
            stripos($statement, 'USE ') !== false ||
            stripos($statement, 'SET ') !== false) {
            continue;
        }
        
        echo "Processing statement " . ($index + 1) . "...\n";
        
        try {
            if ($db->query($statement)) {
                $successCount++;
                echo "✓ Executed statement successfully\n";
            } else {
                $errorCount++;
                echo "✗ Error executing statement: " . $db->error . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "✗ Exception: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nUpdate completed!\n";
    echo "Successful statements: $successCount\n";
    echo "Failed statements: $errorCount\n";
    
    $db->close();
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}

echo "Script finished.\n";
?>
