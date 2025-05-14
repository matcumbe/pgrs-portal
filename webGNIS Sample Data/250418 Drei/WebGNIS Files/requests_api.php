<?php
require_once 'requests_config.php';
require_once 'users_config.php'; // Include user config for JWT verification

// Set headers to allow cross-origin requests and specify content type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Check for preflight CORS request
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the endpoint from the action parameter instead of path segments
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';
$parts = explode('/', $endpoint);
$mainEndpoint = $parts[0];

// Extract ID if present in the endpoint (e.g., requests/123)
$id = null;
if (count($parts) > 1 && is_numeric($parts[1])) {
    $id = intval($parts[1]);
}

// Connect to requests database
$db = connectRequestsDB();

if (!$db) {
    returnRequestResponse(500, "Database connection error", null);
    exit;
}

// Route the request to the appropriate function
try {
    switch ($mainEndpoint) {
        case 'requests':
            switch ($method) {
                case 'GET':
                    if ($id) {
                        getRequestById($db, $id);
                    } else if (isset($parts[1]) && $parts[1] === 'user') {
                        getUserRequests($db);
                    } else if (isset($parts[1]) && $parts[1] === 'admin') {
                        getAdminRequests($db);
                    } else {
                        returnRequestResponse(400, "Invalid request endpoint", null);
                    }
                    break;
                case 'POST':
                    createRequest($db);
                    break;
                case 'PUT':
                    if ($id) {
                        updateRequest($db, $id);
                    } else {
                        returnRequestResponse(400, "Request ID required", null);
                    }
                    break;
                default:
                    returnRequestResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'cart':
            switch ($method) {
                case 'GET':
                    getUserCart($db);
                    break;
                case 'POST':
                    addToCart($db);
                    break;
                case 'DELETE':
                    if ($id) {
                        removeFromCart($db, $id);
                    } else if (isset($parts[1]) && $parts[1] === 'clear') {
                        clearCart($db);
                    } else {
                        returnRequestResponse(400, "Invalid cart delete operation", null);
                    }
                    break;
                default:
                    returnRequestResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'payments':
            switch ($method) {
                case 'POST':
                    submitPayment($db);
                    break;
                case 'PUT':
                    if ($id) {
                        updatePaymentStatus($db, $id);
                    } else {
                        returnRequestResponse(400, "Transaction ID required", null);
                    }
                    break;
                default:
                    returnRequestResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'status':
            if ($method === 'PUT' && $id) {
                updateRequestStatus($db, $id);
            } else {
                returnRequestResponse(400, "Invalid status update request", null);
            }
            break;
            
        case '':
            // No endpoint specified - show API info
            returnRequestResponse(200, "WebGNIS Requests API", [
                'version' => '1.0',
                'endpoints' => [
                    'requests', 'requests/user', 'requests/admin', 'payments', 'status', 'cart'
                ]
            ]);
            break;
            
        default:
            returnRequestResponse(404, "Endpoint not found", null);
            break;
    }
} catch (Exception $e) {
    returnRequestResponse(500, "Server error: " . $e->getMessage(), null);
}

// Verify user authentication
function verifyUserAuth($requiredRole = null) {
    require_once 'users_api.php'; // Include user API for verifyToken function
    return verifyToken($requiredRole, false);
}

// Get requests for current user
function getUserRequests($db) {
    $token = verifyUserAuth();
    
    if (!$token) {
        returnRequestResponse(401, "Unauthorized", null);
        return;
    }
    
    $userId = $token->user_id;
    
    $sql = "SELECT r.request_id, r.request_date, r.expiry_date, r.status, 
            r.request_reference, r.total_amount, r.admin_notes, r.last_updated
            FROM requests r
            WHERE r.user_id = :user_id
            ORDER BY r.request_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $requests = $stmt->fetchAll();
    
    // Get items for each request
    foreach ($requests as &$request) {
        $itemsSql = "SELECT item_id, station_id, station_name, station_type
                    FROM request_items
                    WHERE request_id = :request_id";
        
        $itemsStmt = $db->prepare($itemsSql);
        $itemsStmt->bindParam(':request_id', $request['request_id']);
        $itemsStmt->execute();
        
        $request['items'] = $itemsStmt->fetchAll();
        
        // Get transaction information
        $transactionSql = "SELECT transaction_id, payment_method, payment_date, 
                          payment_amount, payment_reference, proof_document, status
                          FROM transactions
                          WHERE request_id = :request_id
                          ORDER BY payment_date DESC";
        
        $transactionStmt = $db->prepare($transactionSql);
        $transactionStmt->bindParam(':request_id', $request['request_id']);
        $transactionStmt->execute();
        
        $request['transactions'] = $transactionStmt->fetchAll();
    }
    
    returnRequestResponse(200, "User requests retrieved successfully", $requests);
}

// Get admin view of requests
function getAdminRequests($db) {
    $token = verifyUserAuth('admin');
    
    if (!$token || $token->role !== 'admin') {
        returnRequestResponse(403, "Forbidden: Admin access required", null);
        return;
    }
    
    // Optional filtering
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    
    $sql = "SELECT r.request_id, r.user_id, r.request_date, r.expiry_date, r.status, 
            r.request_reference, r.total_amount, r.admin_notes, r.last_updated,
            u.username, u.email, u.contact_number
            FROM requests r
            JOIN webgnis_users.users u ON r.user_id = u.user_id
            WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND r.status = :status";
        $params[':status'] = $status;
    }
    
    if ($dateFrom) {
        $sql .= " AND r.request_date >= :date_from";
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }
    
    if ($dateTo) {
        $sql .= " AND r.request_date <= :date_to";
        $params[':date_to'] = $dateTo . ' 23:59:59';
    }
    
    $sql .= " ORDER BY r.request_date DESC";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->execute();
    
    $requests = $stmt->fetchAll();
    
    returnRequestResponse(200, "Admin requests retrieved successfully", $requests);
}

// Get request by ID
function getRequestById($db, $id) {
    $token = verifyUserAuth();
    
    if (!$token) {
        returnRequestResponse(401, "Unauthorized", null);
        return;
    }
    
    $userId = $token->user_id;
    $isAdmin = isset($token->role) && $token->role === 'admin';
    
    $sql = "SELECT r.request_id, r.user_id, r.request_date, r.expiry_date, r.status, 
            r.request_reference, r.total_amount, r.admin_notes, r.last_updated,
            u.username, u.email, u.contact_number
            FROM requests r
            JOIN webgnis_users.users u ON r.user_id = u.user_id
            WHERE r.request_id = :id";
    
    // Add user restriction for non-admins
    if (!$isAdmin) {
        $sql .= " AND r.user_id = :user_id";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    
    if (!$isAdmin) {
        $stmt->bindParam(':user_id', $userId);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        returnRequestResponse(404, "Request not found", null);
        return;
    }
    
    $request = $stmt->fetch();
    
    // Get request items
    $itemsSql = "SELECT item_id, station_id, station_name, station_type
                FROM request_items
                WHERE request_id = :request_id";
    
    $itemsStmt = $db->prepare($itemsSql);
    $itemsStmt->bindParam(':request_id', $id);
    $itemsStmt->execute();
    
    $request['items'] = $itemsStmt->fetchAll();
    
    // Get transaction information
    $transactionSql = "SELECT transaction_id, payment_method, payment_date, 
                      payment_amount, payment_reference, proof_document, status,
                      verification_date, verified_by
                      FROM transactions
                      WHERE request_id = :request_id
                      ORDER BY payment_date DESC";
    
    $transactionStmt = $db->prepare($transactionSql);
    $transactionStmt->bindParam(':request_id', $id);
    $transactionStmt->execute();
    
    $request['transactions'] = $transactionStmt->fetchAll();
    
    returnRequestResponse(200, "Request retrieved successfully", $request);
}

// Create a new request
function createRequest($db) {
    $token = verifyUserAuth();
    
    if (!$token) {
        returnRequestResponse(401, "Unauthorized", null);
        return;
    }
    
    $userId = $token->user_id;
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate request data
    if (!isset($data['stations']) || !is_array($data['stations']) || count($data['stations']) === 0) {
        returnRequestResponse(400, "No stations selected for request", null);
        return;
    }
    
    // Verify each station has required fields
    foreach ($data['stations'] as $station) {
        if (!isset($station['station_id']) || !isset($station['station_name']) || !isset($station['station_type'])) {
            returnRequestResponse(400, "Invalid station data", null);
            return;
        }
        
        // Validate station type
        if (!in_array($station['station_type'], ['horizontal', 'benchmark', 'gravity'])) {
            returnRequestResponse(400, "Invalid station type: " . $station['station_type'], null);
            return;
        }
    }
    
    // Calculate total amount based on station types
    $totalAmount = calculateRequestTotal($data['stations']);
    
    // Generate unique reference
    $requestReference = generateRequestReference();
    
    // Set expiry date (15 days from now)
    $expiryDate = date('Y-m-d H:i:s', strtotime('+' . REQUEST_EXPIRY_DAYS . ' days'));
    
    $db->beginTransaction();
    
    try {
        // Create request record
        $sql = "INSERT INTO requests (user_id, request_reference, total_amount, expiry_date, status) 
                VALUES (:user_id, :reference, :total, :expiry, 'Not Paid')";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':reference', $requestReference);
        $stmt->bindParam(':total', $totalAmount);
        $stmt->bindParam(':expiry', $expiryDate);
        $stmt->execute();
        
        $requestId = $db->lastInsertId();
        
        // Add request items
        $itemSql = "INSERT INTO request_items (request_id, station_id, station_name, station_type) 
                    VALUES (:request_id, :station_id, :station_name, :station_type)";
        
        $itemStmt = $db->prepare($itemSql);
        
        foreach ($data['stations'] as $station) {
            $itemStmt->bindParam(':request_id', $requestId);
            $itemStmt->bindParam(':station_id', $station['station_id']);
            $itemStmt->bindParam(':station_name', $station['station_name']);
            $itemStmt->bindParam(':station_type', $station['station_type']);
            $itemStmt->execute();
        }
        
        $db->commit();
        
        // Return the created request
        returnRequestResponse(201, "Request created successfully", [
            'request_id' => $requestId,
            'reference' => $requestReference,
            'total_amount' => $totalAmount,
            'expiry_date' => $expiryDate,
            'status' => 'Not Paid'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnRequestResponse(500, "Failed to create request: " . $e->getMessage(), null);
    }
}

// Update request status
function updateRequestStatus($db, $id) {
    $token = verifyUserAuth('admin');
    
    if (!$token || $token->role !== 'admin') {
        returnRequestResponse(403, "Forbidden: Admin access required", null);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate status
    if (!isset($data['status'])) {
        returnRequestResponse(400, "Status is required", null);
        return;
    }
    
    $validStatuses = ['Not Paid', 'Paid', 'Pending', 'Expired', 'Approved', 'Not Approved'];
    if (!in_array($data['status'], $validStatuses)) {
        returnRequestResponse(400, "Invalid status value", null);
        return;
    }
    
    $status = $data['status'];
    $adminNotes = isset($data['admin_notes']) ? $data['admin_notes'] : null;
    
    try {
        // Update request status
        $sql = "UPDATE requests SET status = :status";
        
        // Add admin notes if provided
        if ($adminNotes !== null) {
            $sql .= ", admin_notes = :notes";
        }
        
        $sql .= " WHERE request_id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if ($adminNotes !== null) {
            $stmt->bindParam(':notes', $adminNotes);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            returnRequestResponse(404, "Request not found", null);
            return;
        }
        
        returnRequestResponse(200, "Request status updated successfully", [
            'request_id' => $id,
            'status' => $status
        ]);
        
    } catch (Exception $e) {
        returnRequestResponse(500, "Failed to update request status: " . $e->getMessage(), null);
    }
}

// Submit payment for a request
function submitPayment($db) {
    $token = verifyUserAuth();
    
    if (!$token) {
        returnRequestResponse(401, "Unauthorized", null);
        return;
    }
    
    $userId = $token->user_id;
    
    // Check if using multipart form data (for file upload)
    if (!isset($_POST['request_id']) || !isset($_POST['payment_method']) || !isset($_POST['payment_amount'])) {
        returnRequestResponse(400, "Missing required payment information", null);
        return;
    }
    
    $requestId = intval($_POST['request_id']);
    $paymentMethod = $_POST['payment_method'];
    $paymentAmount = floatval($_POST['payment_amount']);
    $paymentReference = isset($_POST['payment_reference']) ? $_POST['payment_reference'] : null;
    
    // Validate payment method
    $validMethods = ['Cash Deposit', 'Link Biz', 'Bank Transfer'];
    if (!in_array($paymentMethod, $validMethods)) {
        returnRequestResponse(400, "Invalid payment method", null);
        return;
    }
    
    // Verify request exists and belongs to user
    $sql = "SELECT request_id, user_id, total_amount, status 
            FROM requests 
            WHERE request_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $requestId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        returnRequestResponse(404, "Request not found", null);
        return;
    }
    
    $request = $stmt->fetch();
    
    // Verify request belongs to the user
    if ($request['user_id'] !== $userId) {
        returnRequestResponse(403, "You are not authorized to submit payment for this request", null);
        return;
    }
    
    // Check if request is already paid
    if (in_array($request['status'], ['Paid', 'Pending', 'Approved'])) {
        returnRequestResponse(400, "This request has already been paid", null);
        return;
    }
    
    // Handle file upload
    $proofDocument = null;
    if (isset($_FILES['proof_document']) && $_FILES['proof_document']['error'] === UPLOAD_ERR_OK) {
        $tempFile = $_FILES['proof_document']['tmp_name'];
        $fileName = time() . '_' . $_FILES['proof_document']['name'];
        $targetPath = UPLOAD_DIR . $fileName;
        
        // Create directory if it doesn't exist
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        
        if (move_uploaded_file($tempFile, $targetPath)) {
            $proofDocument = $targetPath;
        } else {
            returnRequestResponse(500, "Failed to upload payment proof", null);
            return;
        }
    }
    
    try {
        $db->beginTransaction();
        
        // Create transaction record
        $transactionSql = "INSERT INTO transactions 
                          (request_id, payment_method, payment_amount, payment_reference, proof_document, status) 
                          VALUES (:request_id, :method, :amount, :reference, :proof, 'Pending')";
        
        $transactionStmt = $db->prepare($transactionSql);
        $transactionStmt->bindParam(':request_id', $requestId);
        $transactionStmt->bindParam(':method', $paymentMethod);
        $transactionStmt->bindParam(':amount', $paymentAmount);
        $transactionStmt->bindParam(':reference', $paymentReference);
        $transactionStmt->bindParam(':proof', $proofDocument);
        $transactionStmt->execute();
        
        $transactionId = $db->lastInsertId();
        
        // Update request status to Pending
        $updateSql = "UPDATE requests SET status = 'Pending' WHERE request_id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':id', $requestId);
        $updateStmt->execute();
        
        $db->commit();
        
        returnRequestResponse(201, "Payment submitted successfully", [
            'transaction_id' => $transactionId,
            'request_id' => $requestId,
            'status' => 'Pending'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnRequestResponse(500, "Failed to submit payment: " . $e->getMessage(), null);
    }
}

// Update payment status (admin only)
function updatePaymentStatus($db, $id) {
    $token = verifyUserAuth('admin');
    
    if (!$token || $token->role !== 'admin') {
        returnRequestResponse(403, "Forbidden: Admin access required", null);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate status
    if (!isset($data['status'])) {
        returnRequestResponse(400, "Status is required", null);
        return;
    }
    
    $validStatuses = ['Pending', 'Approved', 'Not Approved'];
    if (!in_array($data['status'], $validStatuses)) {
        returnRequestResponse(400, "Invalid payment status value", null);
        return;
    }
    
    $status = $data['status'];
    $adminNotes = isset($data['admin_notes']) ? $data['admin_notes'] : null;
    
    try {
        $db->beginTransaction();
        
        // Get transaction info and corresponding request
        $sql = "SELECT t.request_id, t.status AS transaction_status, r.status AS request_status
                FROM transactions t
                JOIN requests r ON t.request_id = r.request_id
                WHERE t.transaction_id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            returnRequestResponse(404, "Transaction not found", null);
            return;
        }
        
        $transaction = $stmt->fetch();
        $requestId = $transaction['request_id'];
        
        // Update transaction status
        $updateTransactionSql = "UPDATE transactions 
                                SET status = :status, 
                                    verification_date = NOW(),
                                    verified_by = :admin_id
                                WHERE transaction_id = :id";
        
        $updateTransactionStmt = $db->prepare($updateTransactionSql);
        $updateTransactionStmt->bindParam(':status', $status);
        $updateTransactionStmt->bindParam(':admin_id', $token->user_id);
        $updateTransactionStmt->bindParam(':id', $id);
        $updateTransactionStmt->execute();
        
        // Update request status based on payment status
        $requestStatus = $status;
        if ($status === 'Pending') {
            $requestStatus = 'Pending';
        } else if ($status === 'Approved') {
            $requestStatus = 'Paid';
        } else if ($status === 'Not Approved') {
            $requestStatus = 'Not Paid';
        }
        
        $updateRequestSql = "UPDATE requests 
                            SET status = :status";
        
        if ($adminNotes !== null) {
            $updateRequestSql .= ", admin_notes = :notes";
        }
        
        $updateRequestSql .= " WHERE request_id = :id";
        
        $updateRequestStmt = $db->prepare($updateRequestSql);
        $updateRequestStmt->bindParam(':status', $requestStatus);
        $updateRequestStmt->bindParam(':id', $requestId);
        
        if ($adminNotes !== null) {
            $updateRequestStmt->bindParam(':notes', $adminNotes);
        }
        
        $updateRequestStmt->execute();
        
        $db->commit();
        
        returnRequestResponse(200, "Payment status updated successfully", [
            'transaction_id' => $id,
            'request_id' => $requestId,
            'transaction_status' => $status,
            'request_status' => $requestStatus
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnRequestResponse(500, "Failed to update payment status: " . $e->getMessage(), null);
    }
}

// Update request (for admin edits)
function updateRequest($db, $id) {
    $token = verifyUserAuth('admin');
    
    if (!$token || $token->role !== 'admin') {
        returnRequestResponse(403, "Forbidden: Admin access required", null);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check which fields to update
    $updateFields = [];
    $params = [':id' => $id];
    
    if (isset($data['status'])) {
        $validStatuses = ['Not Paid', 'Paid', 'Pending', 'Expired', 'Approved', 'Not Approved'];
        if (!in_array($data['status'], $validStatuses)) {
            returnRequestResponse(400, "Invalid status value", null);
            return;
        }
        $updateFields[] = "status = :status";
        $params[':status'] = $data['status'];
    }
    
    if (isset($data['admin_notes'])) {
        $updateFields[] = "admin_notes = :notes";
        $params[':notes'] = $data['admin_notes'];
    }
    
    if (isset($data['expiry_date'])) {
        $updateFields[] = "expiry_date = :expiry";
        $params[':expiry'] = $data['expiry_date'];
    }
    
    if (empty($updateFields)) {
        returnRequestResponse(400, "No fields to update", null);
        return;
    }
    
    try {
        $sql = "UPDATE requests SET " . implode(", ", $updateFields) . " WHERE request_id = :id";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            returnRequestResponse(404, "Request not found", null);
            return;
        }
        
        returnRequestResponse(200, "Request updated successfully", [
            'request_id' => $id
        ]);
        
    } catch (Exception $e) {
        returnRequestResponse(500, "Failed to update request: " . $e->getMessage(), null);
    }
}

// Get user's cart items
function getUserCart($db) {
    // First try to get user from authentication
    $token = verifyUserAuth(null, false);
    $userId = null;
    $sessionId = null;
    
    if ($token) {
        // User is logged in
        $userId = $token->user_id;
    } else {
        // No authenticated user, check for session ID
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
        
        if (!$sessionId) {
            // Generate a new session ID if none provided
            $sessionId = generateSessionId();
            
            returnRequestResponse(200, "New cart created", [
                'session_id' => $sessionId,
                'items' => []
            ]);
            return;
        }
    }
    
    try {
        // Build query based on whether we have a user ID or session ID
        $sql = "SELECT cart_id, station_id, station_name, station_type, added_date
                FROM cart_items
                WHERE ";
        
        $params = [];
        
        if ($userId) {
            $sql .= "user_id = :user_id";
            $params[':user_id'] = $userId;
        } else {
            $sql .= "session_id = :session_id";
            $params[':session_id'] = $sessionId;
        }
        
        $sql .= " ORDER BY added_date DESC";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        $items = $stmt->fetchAll();
        
        // Calculate total price for informational purposes
        $totalPrice = 0;
        foreach ($items as $item) {
            switch ($item['station_type']) {
                case 'horizontal':
                    $totalPrice += HORIZONTAL_STATION_PRICE;
                    break;
                case 'benchmark':
                    $totalPrice += BENCHMARK_STATION_PRICE;
                    break;
                case 'gravity':
                    $totalPrice += GRAVITY_STATION_PRICE;
                    break;
            }
        }
        
        returnRequestResponse(200, "Cart retrieved successfully", [
            'session_id' => $sessionId,
            'items' => $items,
            'total_price' => $totalPrice,
            'item_count' => count($items)
        ]);
        
    } catch (Exception $e) {
        returnRequestResponse(500, "Failed to retrieve cart: " . $e->getMessage(), null);
    }
}

// Add item to cart
function addToCart($db) {
    // First try to get user from authentication
    $token = verifyUserAuth(null, false);
    $userId = null;
    
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($data['station_id']) || !isset($data['station_name']) || !isset($data['station_type'])) {
        returnRequestResponse(400, "Missing required station information", null);
        return;
    }
    
    // Validate station type
    if (!in_array($data['station_type'], ['horizontal', 'benchmark', 'gravity'])) {
        returnRequestResponse(400, "Invalid station type", null);
        return;
    }
    
    $stationId = $data['station_id'];
    $stationName = $data['station_name'];
    $stationType = $data['station_type'];
    
    // Check for session ID if user is not logged in
    $sessionId = isset($data['session_id']) ? $data['session_id'] : null;
    
    if ($token) {
        // User is logged in
        $userId = $token->user_id;
    } else if (!$sessionId) {
        returnRequestResponse(400, "No authenticated user and no session ID provided", null);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // If user is logged in, check if this item already exists in their cart
        if ($userId) {
            $checkSql = "SELECT cart_id FROM cart_items 
                        WHERE user_id = :user_id AND station_id = :station_id";
            
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':station_id', $stationId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Item already in cart
                $db->rollBack();
                returnRequestResponse(200, "Item already in cart", null);
                return;
            }
            
            // Add to cart for authenticated user
            $sql = "INSERT INTO cart_items (user_id, station_id, station_name, station_type) 
                    VALUES (:user_id, :station_id, :station_name, :station_type)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':station_id', $stationId);
            $stmt->bindParam(':station_name', $stationName);
            $stmt->bindParam(':station_type', $stationType);
            $stmt->execute();
            
        } else {
            // Add to cart for session-based user
            $checkSql = "SELECT cart_id FROM cart_items 
                        WHERE session_id = :session_id AND station_id = :station_id";
            
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->bindParam(':session_id', $sessionId);
            $checkStmt->bindParam(':station_id', $stationId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Item already in cart
                $db->rollBack();
                returnRequestResponse(200, "Item already in cart", null);
                return;
            }
            
            // We need a placeholder user_id for the foreign key constraint
            // In a production system, this would need a different approach
            // For now, we'll use a dedicated guest user ID
            $guestUserId = 1; // Assuming ID 1 is reserved for "Guest" account
            
            $sql = "INSERT INTO cart_items (user_id, station_id, station_name, station_type, session_id) 
                    VALUES (:user_id, :station_id, :station_name, :station_type, :session_id)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $guestUserId);
            $stmt->bindParam(':station_id', $stationId);
            $stmt->bindParam(':station_name', $stationName);
            $stmt->bindParam(':station_type', $stationType);
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->execute();
        }
        
        $db->commit();
        
        returnRequestResponse(201, "Item added to cart successfully", [
            'station_id' => $stationId,
            'station_name' => $stationName,
            'station_type' => $stationType
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnRequestResponse(500, "Failed to add item to cart: " . $e->getMessage(), null);
    }
}

// Remove item from cart
function removeFromCart($db, $cartItemId) {
    // First try to get user from authentication
    $token = verifyUserAuth(null, false);
    $userId = null;
    
    // Get query parameters
    $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
    
    if ($token) {
        // User is logged in
        $userId = $token->user_id;
    } else if (!$sessionId) {
        returnRequestResponse(400, "No authenticated user and no session ID provided", null);
        return;
    }
    
    try {
        // Build delete query based on whether we have a user ID or session ID
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND ";
        
        $params = [':cart_id' => $cartItemId];
        
        if ($userId) {
            $sql .= "user_id = :user_id";
            $params[':user_id'] = $userId;
        } else {
            $sql .= "session_id = :session_id";
            $params[':session_id'] = $sessionId;
        }
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            returnRequestResponse(404, "Cart item not found or not authorized to remove", null);
            return;
        }
        
        returnRequestResponse(200, "Item removed from cart successfully", null);
        
    } catch (Exception $e) {
        returnRequestResponse(500, "Failed to remove item from cart: " . $e->getMessage(), null);
    }
}

// Clear cart (remove all items)
function clearCart($db) {
    // First try to get user from authentication
    $token = verifyUserAuth(null, false);
    $userId = null;
    
    // Get query parameters
    $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
    
    if ($token) {
        // User is logged in
        $userId = $token->user_id;
    } else if (!$sessionId) {
        returnRequestResponse(400, "No authenticated user and no session ID provided", null);
        return;
    }
    
    try {
        // Build delete query based on whether we have a user ID or session ID
        $sql = "DELETE FROM cart_items WHERE ";
        
        $params = [];
        
        if ($userId) {
            $sql .= "user_id = :user_id";
            $params[':user_id'] = $userId;
        } else {
            $sql .= "session_id = :session_id";
            $params[':session_id'] = $sessionId;
        }
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        
        returnRequestResponse(200, "Cart cleared successfully", [
            'items_removed' => $stmt->rowCount()
        ]);
        
    } catch (Exception $e) {
        returnRequestResponse(500, "Failed to clear cart: " . $e->getMessage(), null);
    }
}

// Merge anonymous cart with user cart after login
function mergeCartsAfterLogin($db, $userId, $sessionId) {
    if (!$userId || !$sessionId) {
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        // Get all items from session cart
        $sql = "SELECT station_id, station_name, station_type 
                FROM cart_items 
                WHERE session_id = :session_id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->execute();
        
        $sessionItems = $stmt->fetchAll();
        
        // Get existing user cart items to avoid duplicates
        $userSql = "SELECT station_id FROM cart_items WHERE user_id = :user_id";
        $userStmt = $db->prepare($userSql);
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();
        
        $userItems = $userStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Insert session items that don't already exist in user's cart
        if (count($sessionItems) > 0) {
            $insertSql = "INSERT INTO cart_items (user_id, station_id, station_name, station_type) 
                        VALUES (:user_id, :station_id, :station_name, :station_type)";
            
            $insertStmt = $db->prepare($insertSql);
            
            foreach ($sessionItems as $item) {
                // Skip if item already exists in user's cart
                if (in_array($item['station_id'], $userItems)) {
                    continue;
                }
                
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':station_id', $item['station_id']);
                $insertStmt->bindParam(':station_name', $item['station_name']);
                $insertStmt->bindParam(':station_type', $item['station_type']);
                $insertStmt->execute();
            }
        }
        
        // Delete session cart items
        $deleteSql = "DELETE FROM cart_items WHERE session_id = :session_id";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->bindParam(':session_id', $sessionId);
        $deleteStmt->execute();
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Failed to merge carts: " . $e->getMessage());
        return false;
    }
}

// Generate a session ID for anonymous users
function generateSessionId() {
    return bin2hex(random_bytes(16));
} 