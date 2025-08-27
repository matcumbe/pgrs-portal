<?php
require_once 'config.php';

echo "Testing Stations Viewer Functionality\n";
echo "=====================================\n\n";

// Test 1: Check if we can fetch data
echo "1. Testing data fetch...\n";
$url = "http://localhost/webgnis/stations_viewer.php?table=hgcp_stations";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['columns']) && isset($data['data'])) {
        echo "✓ Data fetch successful\n";
        echo "  - Columns: " . count($data['columns']) . "\n";
        echo "  - Records: " . count($data['data']) . "\n";
        
        // Check if coordinates are transformed
        if (count($data['data']) > 0) {
            $firstRecord = $data['data'][0];
            if (isset($firstRecord['latitude']) && isset($firstRecord['longitude'])) {
                echo "  - Coordinates transformed: ✓\n";
                echo "    Latitude: " . $firstRecord['latitude'] . "\n";
                echo "    Longitude: " . $firstRecord['longitude'] . "\n";
            } else {
                echo "  - Coordinates NOT transformed: ✗\n";
            }
        }
    } else {
        echo "✗ Data fetch failed - invalid response format\n";
    }
} else {
    echo "✗ Data fetch failed - could not connect\n";
}

// Test 2: Check if we can save data (simulate a simple update)
echo "\n2. Testing data save...\n";
$testData = [
    'table' => 'hgcp_stations',
    'data' => [
        [
            'station_id' => '2041',
            'station_name' => 'MMA-72 (BH-3) - TEST',
            'latitude' => 14.675670,
            'longitude' => 121.100111,
            'region' => 'NCR',
            'province' => 'NCR - MANILA, FIRST DISTRICT',
            'city' => 'QUEZON CITY',
            'barangay' => 'BATASAN',
            'description' => 'Test update from stations_viewer'
        ]
    ]
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData),
        'timeout' => 10
    ]
]);

$response = @file_get_contents('http://localhost/webgnis/stations_viewer.php', false, $context);
if ($response !== false) {
    $result = json_decode($response, true);
    if ($result && isset($result['success'])) {
        echo "✓ Data save test completed\n";
        echo "  - Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ Data save failed - invalid response format\n";
    }
} else {
    echo "✗ Data save failed - could not connect\n";
}

echo "\nTest completed.\n";
?>
