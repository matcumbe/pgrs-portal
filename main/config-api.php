<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Turn off all error reporting to ensure clean JSON output
// error_reporting(0); // This line is now commented out for debugging

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Station price configuration
$config = [
    'status' => 'success',
    'data' => [
        'station_prices' => [
            'horizontal' => 360,
            'vertical' => 360,
            'gravity' => 360,
            'caap' => 720
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