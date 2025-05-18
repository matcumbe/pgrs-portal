<?php
// Turn off all error reporting to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Station price configuration
$config = [
    'status' => 'success',
    'data' => [
        'station_prices' => [
            'horizontal' => 300,
            'vertical' => 300,
            'gravity' => 300
        ],
        'upload_limits' => [
            'max_size' => 5242880,
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']
        ],
        'base_url' => 'http://localhost/webgnis/'
    ]
];

// Return the JSON data
echo json_encode($config);
exit; 