<?php
require_once 'config.php';

echo "Testing database connection and new schema...\n";

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error . "\n");
    }
    
    echo "✓ Database connection successful\n";
    
    // Test if the new tables exist
    $tables = ['grav_stations', 'hgcp_stations', 'vgcp_stations'];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✓ Table '$table' exists\n";
            
            // Count records
            $countResult = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $countResult->fetch_assoc()['count'];
            echo "  - Records: $count\n";
        } else {
            echo "✗ Table '$table' does not exist\n";
        }
    }
    
    // Test API endpoints
    echo "\nTesting API endpoints...\n";
    
    $endpoints = [
        'api.php?path=/api/stations/gravity',
        'api.php?path=/api/stations/horizontal', 
        'api.php?path=/api/stations/vertical'
    ];
    
    foreach ($endpoints as $endpoint) {
        $url = "http://localhost/webgnis/$endpoint";
        echo "Testing: $endpoint\n";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $count = count($data['data']);
                echo "  ✓ Success: $count stations returned\n";
            } else {
                echo "  ✗ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "  ✗ Failed to connect to API\n";
        }
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>

