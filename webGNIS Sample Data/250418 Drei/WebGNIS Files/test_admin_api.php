<?php
// Simple test script for gcp_admin_api.php

// Function to make a request to the API
function makeRequest($path, $method = 'GET', $data = null) {
    $url = 'gcp_admin_api.php?path=' . urlencode($path);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'status' => $info['http_code'],
        'response' => json_decode($response, true)
    ];
}

// HTML output
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        pre { white-space: pre-wrap; overflow-x: auto; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
    <h1>Admin API Tests</h1>
    
    <h2>Get All Station Types</h2>
    <?php
    $types = ['vertical', 'horizontal', 'gravity'];
    foreach ($types as $type) {
        $result = makeRequest("/api/admin/stations/{$type}");
        $class = $result['status'] === 200 ? 'success' : 'error';
        echo "<div class='result {$class}'>";
        echo "<h3>Response for {$type} stations:</h3>";
        echo "<p>Status: {$result['status']}</p>";
        echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
    ?>
    
    <h2>Get Regions</h2>
    <?php
    $result = makeRequest('/api/admin/regions');
    $class = $result['status'] === 200 ? 'success' : 'error';
    echo "<div class='result {$class}'>";
    echo "<h3>Response for regions:</h3>";
    echo "<p>Status: {$result['status']}</p>";
    echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
    echo "</div>";
    ?>
    
    <h2>Create/Update/Delete Station Test</h2>
    <p>These tests would modify the database. Run them manually with specific data.</p>
    
    <h3>Test Create Station:</h3>
    <form action="test_admin_api.php?action=create" method="post">
        <button type="submit">Run Test (Create Gravity Station)</button>
    </form>
    
    <?php
    if (isset($_GET['action']) && $_GET['action'] === 'create') {
        // Sample data for a new gravity station
        $stationData = [
            'type' => 'gravity',
            'station_name' => 'Test Gravity Station ' . date('YmdHis'),
            'station_code' => 'TGS-' . mt_rand(1000, 9999),
            'latitude' => 14.6760,
            'longitude' => 121.0437,
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'city' => 'Quezon City',
            'barangay' => 'UP Campus',
            'gravity_value' => 978000.123,
            'standard_deviation' => 0.005,
            'order' => '2',
            'encoder' => 'Test Script',
            'date_last_updated' => date('Y-m-d')
        ];
        
        $result = makeRequest('/api/admin/station', 'POST', $stationData);
        $class = $result['status'] === 200 ? 'success' : 'error';
        
        echo "<div class='result {$class}'>";
        echo "<h3>Response for station creation:</h3>";
        echo "<p>Status: {$result['status']}</p>";
        echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
        
        // If creation was successful, add a link to delete it
        if ($result['status'] === 200 && isset($result['response']['data']['id'])) {
            $id = $result['response']['data']['id'];
            echo "<div><a href='test_admin_api.php?action=delete&id={$id}'>Delete this station</a></div>";
        }
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $result = makeRequest("/api/admin/station/{$id}", 'DELETE');
        $class = $result['status'] === 200 ? 'success' : 'error';
        
        echo "<div class='result {$class}'>";
        echo "<h3>Response for station deletion:</h3>";
        echo "<p>Status: {$result['status']}</p>";
        echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
    ?>
</body>
</html> 