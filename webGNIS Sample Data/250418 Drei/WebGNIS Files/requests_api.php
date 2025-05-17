<?php
require_once 'users_config.php';

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

$response = [];
$db = connectDB();

if (!$db) {
    returnResponse(500, "Database connection error", null);
    exit;
}

// Set up automatic expiry check
checkExpiredRequests($db);

// Route the request to the appropriate function
try {
    switch ($mainEndpoint) {
        case 'create':
            if ($method === 'POST') {
                createRequest($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'list':
            if ($method === 'GET') {
                if (isset($_GET['user'])) {
                    getUserRequests($db, intval($_GET['user']));
                } else if (isset($_GET['status'])) {
                    getRequestsByStatus($db, intval($_GET['status']));
                } else {
                    getAllRequests($db);
                }
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'view':
            if ($method === 'GET') {
                if ($id) {
                    getRequestById($db, $id);
                } else if (isset($_GET['reference'])) {
                    getRequestByReference($db, $_GET['reference']);
                } else {
                    returnResponse(400, "Request ID or reference number required", null);
                }
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'update-status':
            if ($method === 'PUT' || $method === 'POST') {
                updateRequestStatus($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
        
        case 'statuses':
            if ($method === 'GET') {
                getRequestStatuses($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case '':
            // No endpoint specified - show API info
            returnResponse(200, "WebGNIS Requests API", [
                'version' => '1.0',
                'endpoints' => [
                    'create', 'list', 'view', 'update-status', 'statuses'
                ]
            ]);
            break;
            
        default:
            returnResponse(404, "Endpoint not found", null);
            break;
    }
} catch (Exception $e) {
    returnResponse(500, "Server error: " . $e->getMessage(), null);
}

/**
 * Create a new request from cart items
 */
function createRequest($db) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    $userId = $token->user_id;
    
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->items) || !is_array($data->items) || count($data->items) == 0) {
        returnResponse(400, "No items provided for request", null);
        return;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Get status ID for "Not Paid"
        $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Not Paid'";
        $statusStmt = $db->prepare($statusSql);
        $statusStmt->execute();
        $statusResult = $statusStmt->fetch();
        
        if (!$statusResult) {
            $db->rollBack();
            returnResponse(500, "Failed to get status ID", null);
            return;
        }
        
        $statusId = $statusResult['status_id'];
        
        // Generate unique reference number
        $referenceNumber = 'REQ-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        // Create new request
        $totalAmount = 0; // Will be calculated based on items
        
        $sql = "INSERT INTO requests (reference_number, user_id, status_id, total_amount, expiry_date) 
                VALUES (:reference_number, :user_id, :status_id, :total_amount, DATE_ADD(NOW(), INTERVAL 15 DAY))";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':reference_number', $referenceNumber);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':status_id', $statusId);
        $stmt->bindParam(':total_amount', $totalAmount);
        $stmt->execute();
        
        $requestId = $db->lastInsertId();
        
        // Add items to request
        $itemSql = "INSERT INTO request_items (request_id, station_id, station_type, price) 
                   VALUES (:request_id, :station_id, :station_type, :price)";
        $itemStmt = $db->prepare($itemSql);
        
        foreach ($data->items as $item) {
            // Calculate price based on station type (placeholder logic - replace with actual pricing)
            $price = getPriceForStationType($item->station_type);
            $totalAmount += $price;
            
            $itemStmt->bindParam(':request_id', $requestId);
            $itemStmt->bindParam(':station_id', $item->station_id);
            $itemStmt->bindParam(':station_type', $item->station_type);
            $itemStmt->bindParam(':price', $price);
            $itemStmt->execute();
        }
        
        // Update total amount
        $updateSql = "UPDATE requests SET total_amount = :total_amount WHERE request_id = :request_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':total_amount', $totalAmount);
        $updateStmt->bindParam(':request_id', $requestId);
        $updateStmt->execute();
        
        // Remove items from cart if they were cart items
        if (isset($data->clear_cart) && $data->clear_cart) {
            $cartSql = "DELETE FROM cart_items WHERE user_id = :user_id";
            $cartStmt = $db->prepare($cartSql);
            $cartStmt->bindParam(':user_id', $userId);
            $cartStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(201, "Request created successfully", [
            'request_id' => $requestId,
            'reference_number' => $referenceNumber,
            'total_amount' => $totalAmount,
            'expiry_date' => date('Y-m-d H:i:s', strtotime('+15 days'))
        ]);
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->rollBack();
        returnResponse(500, "Failed to create request: " . $e->getMessage(), null);
    }
}

/**
 * Get all requests (admin only)
 */
function getAllRequests($db) {
    // Verify user is admin
    $token = verifyToken('admin', true);
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;
    
    // Get requests with status name and user info
    $sql = "SELECT r.*, rs.status_name, rs.color_code, 
            u.username, u.email, u.contact_number, 
            COUNT(ri.item_id) as item_count
            FROM requests r
            JOIN request_statuses rs ON r.status_id = rs.status_id
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN request_items ri ON r.request_id = ri.request_id
            GROUP BY r.request_id
            ORDER BY r.request_date DESC
            LIMIT :offset, :per_page";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $requests = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM requests";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $totalCount = $countResult['count'];
    
    $totalPages = ceil($totalCount / $perPage);
    
    returnResponse(200, "Requests retrieved successfully", [
        'requests' => $requests,
        'pagination' => [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages
        ]
    ]);
}

/**
 * Get requests for a specific user
 */
function getUserRequests($db, $userId) {
    // Verify user is logged in and is either admin or the user themselves
    $token = verifyToken(null, true);
    
    if ($token->user_type !== 'admin' && $token->user_id !== $userId) {
        returnResponse(403, "Access denied", null);
        return;
    }
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 10;
    $offset = ($page - 1) * $perPage;
    
    // Get requests with status name
    $sql = "SELECT r.*, rs.status_name, rs.color_code, COUNT(ri.item_id) as item_count
            FROM requests r
            JOIN request_statuses rs ON r.status_id = rs.status_id
            LEFT JOIN request_items ri ON r.request_id = ri.request_id
            WHERE r.user_id = :user_id
            GROUP BY r.request_id
            ORDER BY r.request_date DESC
            LIMIT :offset, :per_page";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $requests = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM requests WHERE user_id = :user_id";
    $countStmt = $db->prepare($countSql);
    $countStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $totalCount = $countResult['count'];
    
    $totalPages = ceil($totalCount / $perPage);
    
    returnResponse(200, "User requests retrieved successfully", [
        'requests' => $requests,
        'pagination' => [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages
        ]
    ]);
}

/**
 * Get requests by status
 */
function getRequestsByStatus($db, $statusId) {
    // Verify user is admin
    $token = verifyToken('admin', true);
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;
    
    // Get requests with status name and user info
    $sql = "SELECT r.*, rs.status_name, rs.color_code, 
            u.username, u.email, u.contact_number, 
            COUNT(ri.item_id) as item_count
            FROM requests r
            JOIN request_statuses rs ON r.status_id = rs.status_id
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN request_items ri ON r.request_id = ri.request_id
            WHERE r.status_id = :status_id
            GROUP BY r.request_id
            ORDER BY r.request_date DESC
            LIMIT :offset, :per_page";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status_id', $statusId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $requests = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM requests WHERE status_id = :status_id";
    $countStmt = $db->prepare($countSql);
    $countStmt->bindParam(':status_id', $statusId, PDO::PARAM_INT);
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $totalCount = $countResult['count'];
    
    $totalPages = ceil($totalCount / $perPage);
    
    returnResponse(200, "Requests retrieved successfully", [
        'requests' => $requests,
        'pagination' => [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages
        ]
    ]);
}

/**
 * Get request by ID with items
 */
function getRequestById($db, $requestId) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    
    // Get request details
    $sql = "SELECT r.*, rs.status_name, rs.color_code, 
            u.username, u.email, u.contact_number
            FROM requests r
            JOIN request_statuses rs ON r.status_id = rs.status_id
            JOIN users u ON r.user_id = u.user_id
            WHERE r.request_id = :request_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        returnResponse(404, "Request not found", null);
        return;
    }
    
    $request = $stmt->fetch();
    
    // Check if user has access to this request (must be owner or admin)
    if ($token->user_type !== 'admin' && $token->user_id !== $request['user_id']) {
        returnResponse(403, "Access denied", null);
        return;
    }
    
    // Get request items
    $itemSql = "SELECT * FROM request_items WHERE request_id = :request_id";
    $itemStmt = $db->prepare($itemSql);
    $itemStmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
    $itemStmt->execute();
    $items = $itemStmt->fetchAll();
    
    // Get payment transactions
    $transactionSql = "SELECT t.*, pm.method_name 
                       FROM transactions t
                       JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
                       WHERE t.request_id = :request_id
                       ORDER BY t.transaction_date DESC";
    $transactionStmt = $db->prepare($transactionSql);
    $transactionStmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
    $transactionStmt->execute();
    $transactions = $transactionStmt->fetchAll();
    
    // Add items and transactions to request data
    $request['items'] = $items;
    $request['transactions'] = $transactions;
    
    returnResponse(200, "Request details retrieved", $request);
}

/**
 * Get request by reference number
 */
function getRequestByReference($db, $referenceNumber) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    
    // Get request details by reference number
    $sql = "SELECT r.request_id FROM requests r WHERE r.reference_number = :reference_number";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':reference_number', $referenceNumber);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        returnResponse(404, "Request not found", null);
        return;
    }
    
    $result = $stmt->fetch();
    getRequestById($db, $result['request_id']); // Reuse existing function
}

/**
 * Update request status
 */
function updateRequestStatus($db) {
    // Verify user is admin
    $token = verifyToken('admin', true);
    
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->request_id) || !isset($data->status_id)) {
        returnResponse(400, "Missing required fields: request_id, status_id", null);
        return;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Check if request exists
        $checkSql = "SELECT r.*, u.email, u.username, rs.status_name as current_status 
                    FROM requests r 
                    JOIN users u ON r.user_id = u.user_id
                    JOIN request_statuses rs ON r.status_id = rs.status_id
                    WHERE r.request_id = :request_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':request_id', $data->request_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            $db->rollBack();
            returnResponse(404, "Request not found", null);
            return;
        }
        
        $request = $checkStmt->fetch();
        
        // Check if status exists
        $statusSql = "SELECT * FROM request_statuses WHERE status_id = :status_id";
        $statusStmt = $db->prepare($statusSql);
        $statusStmt->bindParam(':status_id', $data->status_id);
        $statusStmt->execute();
        
        if ($statusStmt->rowCount() == 0) {
            $db->rollBack();
            returnResponse(400, "Invalid status ID", null);
            return;
        }
        
        $newStatus = $statusStmt->fetch();
        
        // Only update if the status is actually changing
        if ($request['status_id'] == $data->status_id) {
            $db->rollBack();
            returnResponse(200, "Request status unchanged", [
                'request_id' => $data->request_id,
                'status' => $newStatus['status_name']
            ]);
            return;
        }
        
        // Update request status
        $sql = "UPDATE requests SET status_id = :status_id";
        
        // Add remarks if provided
        if (isset($data->remarks)) {
            $sql .= ", remarks = :remarks";
        }
        
        $sql .= " WHERE request_id = :request_id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':status_id', $data->status_id);
        $stmt->bindParam(':request_id', $data->request_id);
        
        if (isset($data->remarks)) {
            $stmt->bindParam(':remarks', $data->remarks);
        }
        
        $stmt->execute();
        
        // Send email notification for approved or not approved requests
        if ($newStatus['status_name'] == 'Approved' || $newStatus['status_name'] == 'Not Approved') {
            $userEmail = $request['email'];
            $userName = $request['username'];
            $requestRef = $request['reference_number'];
            $statusMessage = $newStatus['status_name'];
            $remarks = isset($data->remarks) ? $data->remarks : "No additional information provided.";
            
            // Compose email content
            $subject = "WebGNIS Request {$requestRef} Status Update: {$statusMessage}";
            
            $message = "Dear {$userName},\n\n";
            $message .= "Your request with reference number {$requestRef} has been {$statusMessage}.\n\n";
            
            if ($newStatus['status_name'] == 'Approved') {
                $message .= "Your requested data is now ready for download. Please log in to your account to access it.\n\n";
            } else if ($newStatus['status_name'] == 'Not Approved') {
                $message .= "Unfortunately, your request could not be approved. Please contact NAMRIA support for more information.\n\n";
            }
            
            $message .= "Remarks: {$remarks}\n\n";
            $message .= "If you have any questions, please contact our support team.\n\n";
            $message .= "Regards,\nNAMRIA WebGNIS Team";
            
            // Send email
            if (sendEmail($userEmail, $subject, $message)) {
                // Log the email sent
                error_log("Notification email sent to {$userEmail} for request {$requestRef} status change to {$statusMessage}");
            } else {
                // Log the email failure but don't fail the transaction
                error_log("Failed to send notification email to {$userEmail} for request {$requestRef}");
            }
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(200, "Request status updated to " . $newStatus['status_name'], [
            'request_id' => $data->request_id,
            'previous_status' => $request['current_status'],
            'new_status' => $newStatus['status_name'],
            'reference_number' => $request['reference_number'],
            'notification_sent' => ($newStatus['status_name'] == 'Approved' || $newStatus['status_name'] == 'Not Approved')
        ]);
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->rollBack();
        returnResponse(500, "Failed to update request status: " . $e->getMessage(), null);
    }
}

/**
 * Send an email - Helper function
 */
function sendEmail($to, $subject, $message) {
    // Set headers
    $headers = 'From: webgnis@namria.gov.ph' . "\r\n" .
               'Reply-To: webgnis@namria.gov.ph' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    // Try to send email
    try {
        return mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all request statuses
 */
function getRequestStatuses($db) {
    $sql = "SELECT * FROM request_statuses ORDER BY status_id";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $statuses = $stmt->fetchAll();
    
    returnResponse(200, "Request statuses retrieved", $statuses);
}

/**
 * Check for expired requests and update their status
 */
function checkExpiredRequests($db) {
    // Get status IDs
    $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = :status_name";
    $statusStmt = $db->prepare($statusSql);
    
    // Get "Not Paid" status ID
    $statusStmt->bindValue(':status_name', 'Not Paid');
    $statusStmt->execute();
    $notPaidResult = $statusStmt->fetch();
    
    if (!$notPaidResult) {
        return; // Can't proceed if status not found
    }
    $notPaidStatusId = $notPaidResult['status_id'];
    
    // Get "Expired" status ID
    $statusStmt->bindValue(':status_name', 'Expired');
    $statusStmt->execute();
    $expiredResult = $statusStmt->fetch();
    
    if (!$expiredResult) {
        return; // Can't proceed if status not found
    }
    $expiredStatusId = $expiredResult['status_id'];
    
    // Update expired requests (not paid and past expiry date)
    $sql = "UPDATE requests 
            SET status_id = :expired_status_id 
            WHERE status_id = :not_paid_status_id 
            AND expiry_date < NOW()";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':expired_status_id', $expiredStatusId);
    $stmt->bindParam(':not_paid_status_id', $notPaidStatusId);
    $stmt->execute();
}

/**
 * Get price for station type (placeholder - replace with actual pricing logic)
 */
function getPriceForStationType($stationType) {
    // Simple placeholder pricing
    switch ($stationType) {
        case 'horizontal':
            return 500.00;
        case 'vertical':
            return 450.00;
        case 'gravity':
            return 600.00;
        default:
            return 500.00;
    }
}

/**
 * Standard response function (reusing from users_api.php)
 */
function returnResponse($statusCode, $message, $data) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => $statusCode < 400 ? 'success' : 'error',
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Verify JWT token (reusing from users_api.php)
 */
function verifyToken($requiredRole = null, $exitOnFail = true) {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
        if ($exitOnFail) {
            returnResponse(401, "No authentication token provided", null);
        }
        return null;
    }
    
    $jwt = substr($authHeader, 7);
    
    try {
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) {
            throw new Exception('Invalid token format');
        }
        
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];
        
        // Check if token is expired
        $payloadObj = json_decode($payload);
        if (!$payloadObj) {
            throw new Exception('Invalid token payload');
        }
        
        if (isset($payloadObj->exp) && $payloadObj->exp < time()) {
            throw new Exception('Token expired');
        }
        
        // Verify signature
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64UrlSignature !== $signatureProvided) {
            throw new Exception('Invalid token signature');
        }
        
        // Check if user has required role
        if ($requiredRole && (!isset($payloadObj->user_type) || $payloadObj->user_type !== $requiredRole)) {
            if ($exitOnFail) {
                returnResponse(403, "Access denied: Insufficient permissions", null);
            }
            return null;
        }
        
        return $payloadObj;
    } catch (Exception $e) {
        if ($exitOnFail) {
            returnResponse(401, "Authentication failed: " . $e->getMessage(), null);
        }
        return null;
    }
} 