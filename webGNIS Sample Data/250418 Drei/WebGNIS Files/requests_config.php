<?php
// Database configuration for requests
define('REQ_DB_HOST', '127.0.0.1');
define('REQ_DB_USER', 'root');
define('REQ_DB_PASS', '');
define('REQ_DB_NAME', 'webgnis_requests');
define('REQ_DB_CHARSET', 'utf8mb4');

// Station pricing configuration
define('HORIZONTAL_STATION_PRICE', 150.00);
define('BENCHMARK_STATION_PRICE', 200.00);
define('GRAVITY_STATION_PRICE', 250.00);

// Request settings
define('REQUEST_EXPIRY_DAYS', 15); // Days before a request expires if not paid
define('UPLOAD_DIR', 'uploads/payment_proofs/'); // Directory for payment proof uploads

// Connect to requests database
function connectRequestsDB() {
    try {
        $dsn = "mysql:host=" . REQ_DB_HOST . ";dbname=" . REQ_DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, REQ_DB_USER, REQ_DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Requests database connection failed: " . $e->getMessage());
        return null;
    }
}

// Generate a unique request reference
function generateRequestReference() {
    $prefix = 'REQ-';
    $timestamp = date('YmdHis');
    $random = rand(1000, 9999);
    return $prefix . $timestamp . '-' . $random;
}

// Calculate request total amount based on stations
function calculateRequestTotal($stations) {
    $total = 0;
    
    foreach ($stations as $station) {
        switch ($station['station_type']) {
            case 'horizontal':
                $total += HORIZONTAL_STATION_PRICE;
                break;
            case 'benchmark':
                $total += BENCHMARK_STATION_PRICE;
                break;
            case 'gravity':
                $total += GRAVITY_STATION_PRICE;
                break;
        }
    }
    
    return $total;
}

// Format response for API
function returnRequestResponse($statusCode, $message, $data) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => $statusCode,
        'message' => $message,
        'data' => $data
    ]);
    exit;
} 