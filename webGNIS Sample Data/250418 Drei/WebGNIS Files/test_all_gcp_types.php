<?php
// Comprehensive Test Script for All GCP Types

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
    <title>GCP Types Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        pre { white-space: pre-wrap; overflow-x: auto; background-color: #f5f5f5; padding: 10px; }
        .test-group { margin-bottom: 30px; border-bottom: 1px solid #ccc; padding-bottom: 20px; }
        h1 { color: #333; }
        h2 { color: #555; }
        h3 { color: #777; }
        button { padding: 10px; margin: 5px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0069d9; }
        .station-id { font-family: monospace; font-weight: bold; }
    </style>
</head>
<body>
    <h1>GCP Types Test - Comprehensive</h1>
    
    <div class="test-group">
        <h2>Get All Station Types</h2>
        <?php
        $types = ['vertical', 'horizontal', 'gravity'];
        foreach ($types as $type) {
            $result = makeRequest("/api/admin/stations/{$type}");
            $class = $result['status'] === 200 ? 'success' : 'error';
            $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
            
            echo "<div class='result {$class}'>";
            echo "<h3>Response for {$type} stations:</h3>";
            echo "<p>Status: {$result['status']}</p>";
            echo "<p>Count: {$count} stations</p>";
            echo "</div>";
        }
        ?>
    </div>
    
    <div class="test-group">
        <h2>Create/Update/Delete Test</h2>
        <form action="test_all_gcp_types.php" method="post">
            <button type="submit" name="action" value="create_vertical">Create Vertical Station</button>
            <button type="submit" name="action" value="create_horizontal">Create Horizontal Station</button>
            <button type="submit" name="action" value="create_gravity">Create Gravity Station</button>
        </form>
        
        <?php
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            $createdId = null;
            
            // Define test data for each type
            if ($action == 'create_vertical') {
                $stationData = [
                    'type' => 'vertical',
                    'station_name' => 'Test Vertical Station ' . date('YmdHis'),
                    'station_code' => 'TVS-' . mt_rand(1000, 9999),
                    'latitude' => 14.6760,
                    'longitude' => 121.0437,
                    'region' => 'NCR',
                    'province' => 'Metro Manila',
                    'city' => 'Quezon City',
                    'barangay' => 'UP Campus',
                    'elevation' => 25.123,
                    'bm_plus' => 1.25,
                    'accuracy_class' => '2CM',
                    'elevation_order' => '1',
                    'encoder' => 'Test Script',
                    'date_last_updated' => date('Y-m-d')
                ];
                
                $result = makeRequest('/api/admin/station', 'POST', $stationData);
                $class = $result['status'] === 200 ? 'success' : 'error';
                
                echo "<div class='result {$class}'>";
                echo "<h3>Response for vertical station creation:</h3>";
                echo "<p>Status: {$result['status']}</p>";
                echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
                echo "</div>";
                
                if ($result['status'] === 200 && isset($result['response']['data']['id'])) {
                    $createdId = $result['response']['data']['id'];
                    echo "<div><p>Created station with ID: <span class='station-id'>{$createdId}</span></p>";
                    echo "<a href='test_all_gcp_types.php?action=view&type=vertical&id={$createdId}'>View this station</a> | ";
                    echo "<a href='test_all_gcp_types.php?action=delete&id={$createdId}'>Delete this station</a></div>";
                }
            } 
            else if ($action == 'create_horizontal') {
                $stationData = [
                    'type' => 'horizontal',
                    'station_name' => 'Test Horizontal Station ' . date('YmdHis'),
                    'station_code' => 'THS-' . mt_rand(1000, 9999),
                    'latitude' => 14.6590,
                    'longitude' => 121.0640,
                    'region' => 'NCR',
                    'province' => 'Metro Manila',
                    'city' => 'Quezon City',
                    'barangay' => 'Diliman',
                    'ellipsoidal_height' => 20.456,
                    'horizontal_order' => '2',
                    'utm_northing' => 1625000.123,
                    'utm_easting' => 275000.456,
                    'utm_zone' => '51N',
                    'encoder' => 'Test Script',
                    'date_last_updated' => date('Y-m-d')
                ];
                
                $result = makeRequest('/api/admin/station', 'POST', $stationData);
                $class = $result['status'] === 200 ? 'success' : 'error';
                
                echo "<div class='result {$class}'>";
                echo "<h3>Response for horizontal station creation:</h3>";
                echo "<p>Status: {$result['status']}</p>";
                echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
                echo "</div>";
                
                if ($result['status'] === 200 && isset($result['response']['data']['id'])) {
                    $createdId = $result['response']['data']['id'];
                    echo "<div><p>Created station with ID: <span class='station-id'>{$createdId}</span></p>";
                    echo "<a href='test_all_gcp_types.php?action=view&type=horizontal&id={$createdId}'>View this station</a> | ";
                    echo "<a href='test_all_gcp_types.php?action=delete&id={$createdId}'>Delete this station</a></div>";
                }
            }
            else if ($action == 'create_gravity') {
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
                    'gravity_value' => 978125.456,
                    'standard_deviation' => 0.003,
                    'gravity_order' => '2',
                    'encoder' => 'Test Script',
                    'date_last_updated' => date('Y-m-d')
                ];
                
                $result = makeRequest('/api/admin/station', 'POST', $stationData);
                $class = $result['status'] === 200 ? 'success' : 'error';
                
                echo "<div class='result {$class}'>";
                echo "<h3>Response for gravity station creation:</h3>";
                echo "<p>Status: {$result['status']}</p>";
                echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
                echo "</div>";
                
                if ($result['status'] === 200 && isset($result['response']['data']['id'])) {
                    $createdId = $result['response']['data']['id'];
                    echo "<div><p>Created station with ID: <span class='station-id'>{$createdId}</span></p>";
                    echo "<a href='test_all_gcp_types.php?action=view&type=gravity&id={$createdId}'>View this station</a> | ";
                    echo "<a href='test_all_gcp_types.php?action=delete&id={$createdId}'>Delete this station</a></div>";
                }
            }
        }
        
        // Handle view action
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $id = $_GET['id'];
            $type = $_GET['type'] ?? '';
            
            $result = makeRequest("/api/admin/station/{$id}");
            $class = $result['status'] === 200 ? 'success' : 'error';
            
            echo "<div class='result {$class}'>";
            echo "<h3>Retrieved {$type} station details:</h3>";
            echo "<p>Status: {$result['status']}</p>";
            echo "<pre>" . json_encode($result['response'], JSON_PRETTY_PRINT) . "</pre>";
            echo "</div>";
            
            echo "<div><a href='test_all_gcp_types.php?action=delete&id={$id}'>Delete this station</a></div>";
        }
        
        // Handle delete action
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
    </div>
    
    <div class="test-group">
        <h2>Location Data Tests</h2>
        <?php
        $locationEndpoints = [
            'regions' => '/api/admin/regions',
            'provinces' => '/api/admin/provinces',
            'cities' => '/api/admin/cities', 
            'barangays' => '/api/admin/barangays'
        ];
        
        foreach ($locationEndpoints as $name => $endpoint) {
            $result = makeRequest($endpoint);
            $class = $result['status'] === 200 ? 'success' : 'error';
            $count = isset($result['response']['data']) ? count($result['response']['data']) : 0;
            
            echo "<div class='result {$class}'>";
            echo "<h3>Response for {$name}:</h3>";
            echo "<p>Status: {$result['status']}</p>";
            echo "<p>Count: {$count} items</p>";
            echo "</div>";
        }
        ?>
    </div>
    
    <div>
        <a href="test_all_gcp_types.php">Back to Tests</a>
    </div>
</body>
</html> 