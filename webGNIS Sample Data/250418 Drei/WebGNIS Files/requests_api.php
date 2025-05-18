<?php
// Set timezone for all date/time operations
date_default_timezone_set('Asia/Manila');

// Include config and authentication
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
                // Check for ID in different locations
                $requestId = null;
                
                // Check if ID is in the URL path (view/123)
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    $requestId = intval($parts[1]);
                } 
                // Check if ID is a direct query parameter (?action=view&id=123)
                else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                    $requestId = intval($_GET['id']);
                }
                // Check for reference parameter
                else if (isset($_GET['reference'])) {
                    getRequestByReference($db, $_GET['reference']);
                    return;
                }
                
                if ($requestId) {
                    error_log("[" . date('Y-m-d H:i:s') . "] Fetching request ID: " . $requestId . " from action: " . $endpoint);
                    getRequestById($db, $requestId);
                } else {
                    error_log("[" . date('Y-m-d H:i:s') . "] Missing request ID in request. Action: " . $endpoint . ", GET params: " . print_r($_GET, true));
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
    
    // Debug log for token and user ID verification
    error_log("createRequest called with token user_id: " . var_export($userId, true) . " (type: " . gettype($userId) . ")");
    error_log("Full token object: " . var_export($token, true));
    
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    error_log("Request data: " . print_r($data, true));
    
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
        
        // Create new request
        $totalAmount = 0; // Will be calculated based on items
        
        // Generate transaction code
        $transactionCode = generateTransactionCode($db, $userId);
        
        // Log the generated transaction code and user ID for debugging
        error_log("Generated transaction code: $transactionCode for user_id: $userId");
        
        $sql = "INSERT INTO requests (user_id, status_id, total_amount, transaction_code, exp_date) 
                VALUES (:user_id, :status_id, :total_amount, :transaction_code, DATE_ADD(NOW(), INTERVAL 15 DAY))";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':status_id', $statusId);
        $stmt->bindParam(':total_amount', $totalAmount);
        $stmt->bindParam(':transaction_code', $transactionCode);
        $stmt->execute();
        
        // Verify the insert worked
        $requestId = $db->lastInsertId();
        error_log("Inserted request with ID: $requestId, user_id: $userId, transaction_code: $transactionCode");
        
        // Double-check the inserted values
        $checkSql = "SELECT user_id, transaction_code FROM requests WHERE request_id = :request_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':request_id', $requestId);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch();
        error_log("Verification of request record - user_id: " . $checkResult['user_id'] . ", transaction_code: " . $checkResult['transaction_code']);
        
        // Add items to request
        $itemSql = "INSERT INTO request_items (request_id, station_id, station_name, station_type, price) 
                   VALUES (:request_id, :station_id, :station_name, :station_type, :price)";
        $itemStmt = $db->prepare($itemSql);
        
        foreach ($data->items as $item) {
            // Log the received item data for debugging
            error_log("[createRequest] Processing item: " . print_r($item, true));

            // Calculate price based on station type
            // Ensure station_type is present and valid
            $stationType = $item->station_type ?? 'horizontal'; // Default if null, though it shouldn\'t be
            $price = getPriceForStationType($stationType);
            $totalAmount += $price;
            
            // Use the properties as sent by the corrected payment.js
            // station_id from client should be the actual ID (e.g., from item.id)
            // station_name from client should be the name (e.g., from item.name)
            // station_type from client should be the type (e.g., from item.type)

            $clientStationId = $item->station_id ?? null;
            $clientStationName = $item->station_name ?? null;
            $clientStationType = $item->station_type ?? 'unknown'; // Default if not provided

            if ($clientStationId === null) {
                error_log("[createRequest] CRITICAL: clientStationId is null. Item: " . print_r($item, true));
                // Potentially skip this item or throw an error, as a null ID is problematic
                // For now, we\'ll try to use station_name as a fallback for ID if name is not null.
                // This is not ideal and suggests client-side data issues.
                $clientStationId = $clientStationName ?? ('ERR-' . substr(md5(time() . rand()), 0, 8));
            }
            
            if ($clientStationName === null) {
                // If name is null, try to use the ID as name, or a placeholder
                $clientStationName = $clientStationId ?? 'Unknown Station';
            }

            error_log("[createRequest] DB Insert Values: ID='{$clientStationId}', Name='{$clientStationName}', Type='{$clientStationType}', Price='{$price}'");

            $itemStmt->bindParam(':request_id', $requestId);
            $itemStmt->bindParam(':station_id', $clientStationId); // Use client-provided ID
            $itemStmt->bindParam(':station_name', $clientStationName); // Use client-provided Name
            $itemStmt->bindParam(':station_type', $clientStationType); // Use client-provided Type
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
            // Corrected SQL: Delete from cart_items by joining with carts table or using a subquery
            // Using a subquery to get cart_id(s) for the user
            $cartSql = "DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM carts WHERE user_id = :user_id)";
            error_log("Attempting to clear cart items for user_id: $userId using query: $cartSql"); // Debug log
            $cartStmt = $db->prepare($cartSql);
            $cartStmt->bindParam(':user_id', $userId);
            $cartStmt->execute();
            error_log("Cart items cleared for user_id: $userId. Rows affected: " . $cartStmt->rowCount()); // Debug log
        
            // Optionally, if you also want to delete the cart record itself from 'carts' table (not just items):
            // $cartMasterSql = "DELETE FROM carts WHERE user_id = :user_id";
            // $cartMasterStmt = $db->prepare($cartMasterSql);
            // $cartMasterStmt->bindParam(':user_id', $userId);
            // $cartMasterStmt->execute();
            // error_log("Cart master record deleted for user_id: $userId. Rows affected: " . $cartMasterStmt->rowCount());
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(201, "Request created successfully", [
            'request_id' => $requestId,
            'transaction_code' => $transactionCode,
            'total_amount' => $totalAmount,
            'exp_date' => date('Y-m-d H:i:s', strtotime('+15 days'))
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
    // Debug: Log the requested userId and token information
    error_log("getUserRequests called with userId: " . var_export($userId, true));
    
    // Verify user is logged in
    $token = verifyToken(null, true);
    error_log("Token user_id: " . var_export($token->user_id, true) . ", type: " . gettype($token->user_id));
    error_log("Requested user_id: " . var_export($userId, true) . ", type: " . gettype($userId));
    
    // Compare user_id values properly, ensuring type conversion
    $tokenUserId = intval($token->user_id);
    $requestedUserId = intval($userId);
    
    // Check permissions
    if ($token->user_type !== 'admin' && $tokenUserId !== $requestedUserId) {
        error_log("Access denied: Token user_id ($tokenUserId) does not match requested user_id ($requestedUserId) and not admin");
        returnResponse(403, "Access denied", null);
        return;
    }
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 10;
    $offset = ($page - 1) * $perPage;
    
    try {
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
        $stmt->bindParam(':user_id', $requestedUserId, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $requests = $stmt->fetchAll();
        error_log("Found " . count($requests) . " requests for user $requestedUserId");
        
        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM requests WHERE user_id = :user_id";
        $countStmt = $db->prepare($countSql);
        $countStmt->bindParam(':user_id', $requestedUserId, PDO::PARAM_INT);
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
    } catch (Exception $e) {
        error_log("Error in getUserRequests: " . $e->getMessage());
        returnResponse(500, "Error retrieving requests: " . $e->getMessage(), null);
    }
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
 * Get a request by ID with its items
 */
function getRequestById($db, $requestId) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    
    // Get the request with status name
    $sql = "SELECT r.*, rs.status_name, rs.color_code 
            FROM requests r
            JOIN request_statuses rs ON r.status_id = rs.status_id
            WHERE r.request_id = :request_id";
            
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':request_id', $requestId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        returnResponse(404, "Request not found", null);
        return;
    }
    
    $request = $stmt->fetch();
    
    // Verify user has access to this request
    if ($token->user_type !== 'admin' && $request['user_id'] != $token->user_id) {
        returnResponse(403, "Access denied", null);
        return;
    }
    
    // Get request items
    $itemsSql = "SELECT * FROM request_items WHERE request_id = :request_id";
    $itemsStmt = $db->prepare($itemsSql);
    $itemsStmt->bindParam(':request_id', $requestId);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll();
    
    // Get user info - Fixed query to avoid non-existent columns
    $userSql = "SELECT user_id, username, email, contact_number 
                FROM users WHERE user_id = :user_id";
    $userStmt = $db->prepare($userSql);
    $userStmt->bindParam(':user_id', $request['user_id']);
    $userStmt->execute();
    $userInfo = $userStmt->fetch();
    
    // Format response
    $response = [
        'request_id' => $request['request_id'],
        'user_id' => $request['user_id'],
        'status' => [
            'id' => $request['status_id'],
            'name' => $request['status_name'],
            'color' => $request['color_code']
        ],
        'request_date' => $request['request_date'],
        'exp_date' => $request['exp_date'],
        'total_amount' => $request['total_amount'],
        'transaction_code' => $request['transaction_code'],
        'items' => $items,
        'user' => [
            'user_id' => $userInfo['user_id'],
            'username' => $userInfo['username'],
            'email' => $userInfo['email'],
            'contact_number' => $userInfo['contact_number']
        ]
    ];
    
    returnResponse(200, "Request details retrieved", $response);
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
    try {
        // Get status IDs
        $notPaidSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Not Paid'";
        $notPaidStmt = $db->prepare($notPaidSql);
        $notPaidStmt->execute();
        $notPaidResult = $notPaidStmt->fetch();
        
        if (!$notPaidResult) {
            return; // No Not Paid status found
        }
        
        $notPaidStatusId = $notPaidResult['status_id'];
        
        $expiredSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Expired'";
        $expiredStmt = $db->prepare($expiredSql);
        $expiredStmt->execute();
        $expiredResult = $expiredStmt->fetch();
        
        if (!$expiredResult) {
            return; // No Expired status found
        }
        
        $expiredStatusId = $expiredResult['status_id'];
        
        // Find and update expired requests
        $sql = "UPDATE requests 
                SET status_id = :expired_status_id 
                WHERE status_id = :not_paid_status_id 
                AND exp_date < NOW()";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':expired_status_id', $expiredStatusId);
        $stmt->bindParam(':not_paid_status_id', $notPaidStatusId);
        $stmt->execute();
        
        // Log the number of updated requests
        $count = $stmt->rowCount();
        if ($count > 0) {
            error_log("Marked {$count} expired requests");
        }
    } catch (Exception $e) {
        error_log("Error checking expired requests: " . $e->getMessage());
    }
}

/**
 * Get price for station type (placeholder - replace with actual pricing logic)
 */
function getPriceForStationType($stationType) {
    // Use defined constants from config.php
    switch ($stationType) {
        case 'horizontal':
            return defined('PRICE_HORIZONTAL') ? PRICE_HORIZONTAL : 300.00;
        case 'vertical':
            return defined('PRICE_VERTICAL') ? PRICE_VERTICAL : 300.00;
        case 'gravity':
            return defined('PRICE_GRAVITY') ? PRICE_GRAVITY : 300.00;
        default:
            return defined('PRICE_HORIZONTAL') ? PRICE_HORIZONTAL : 300.00;
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
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
    
    error_log("verifyToken called with requiredRole: " . ($requiredRole ?? 'null'));
    error_log("Authorization header present: " . ($authHeader ? 'yes (' . substr($authHeader, 0, 15) . '...)' : 'no'));
    
    // Properly handle the authorization header
    if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
        error_log("Invalid auth header format or missing Bearer: " . substr($authHeader, 0, 20) . "...");
        if ($exitOnFail) {
            returnResponse(401, "No or invalid authentication token provided", null);
        }
        return null;
    }
    
    $jwt = substr($authHeader, 7);
    error_log("JWT token for verification (length: " . strlen($jwt) . "): " . substr($jwt, 0, 20) . "...");
    
    // DISABLED: Special case for admin authorization - use only for testing and only if the token matches exactly
    // This was causing issues with normal users as their requests were being assigned to admin (user_id 1)
    /*
    if ($jwt === 'admin_token' && (!$requiredRole || $requiredRole === 'admin')) {
        error_log("Admin token used for testing purposes");
        // Return admin user object for admin_token
        $adminUser = new stdClass();
        $adminUser->user_id = 1;  // Default admin ID
        $adminUser->username = 'admin';
        $adminUser->user_type = 'admin';
        return $adminUser;
    }
    */
    
    try {
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) {
            error_log("Invalid token format: " . count($tokenParts) . " parts instead of 3");
            throw new Exception('Invalid token format');
        }
        
        // Get token parts (these are already Base64 encoded as per JWT standard)
        $headerEncoded = $tokenParts[0];
        $payloadEncoded = $tokenParts[1];
        $signatureProvided = $tokenParts[2];
        
        // Verify signature (aligning with users_api.php's verifyToken logic)
        // The data to sign is the original Base64 encoded header and payload, concatenated with a dot.
        $dataToSign = $headerEncoded . "." . $payloadEncoded;
        $expectedSignature = base64_encode(hash_hmac('sha256', $dataToSign, JWT_SECRET, true));

        // Important: JWT signatures are Base64URL encoded. 
        // The generateJWT in users_api.php uses base64_encode, not base64url_encode.
        // So we need to ensure we are comparing apples to apples.
        // If generateJWT uses standard base64_encode for signature, then we compare with that.
        // If it were using base64url, we'd convert $expectedSignature to base64url.
        // Given users_api.php generateJWT: $signature = base64_encode(hash_hmac(...));
        // We compare directly.

        if ($signatureProvided !== $expectedSignature) {
            error_log("Invalid token signature. Provided: " . $signatureProvided . " Expected: " . $expectedSignature);
            error_log("Data that was signed: " . $dataToSign);
            throw new Exception('Invalid token signature');
        }
        
        // Decode payload (standard base64_decode is fine here as json_decode handles it)
        $payload = json_decode(base64_decode($payloadEncoded));
        if (!$payload) {
            error_log("Failed to parse payload JSON from: " . $payloadEncoded);
            throw new Exception('Invalid token payload');
        }
        
        // Log token information for debugging
        error_log("Token payload decoded successfully. User ID: " . ($payload->user_id ?? 'N/A') . ", Type: " . ($payload->user_type ?? 'N/A'));

        if (isset($payload->exp) && $payload->exp < time()) {
            error_log("Token expired: " . date('Y-m-d H:i:s', $payload->exp));
            throw new Exception('Token expired');
        }
        
        if ($requiredRole && (!isset($payload->user_type) || $payload->user_type !== $requiredRole)) {
            error_log("User lacks required role: {$requiredRole}, user has: " . ($payload->user_type ?? 'none'));
            if ($exitOnFail) {
                returnResponse(403, "Access denied: Insufficient permissions", null);
            }
            return null;
        }
        
        // Ensure user_id is an integer
        if (isset($payload->user_id) && is_numeric($payload->user_id)) {
            $payload->user_id = intval($payload->user_id);
        }
        
        return $payload;
    } catch (Exception $e) {
        error_log("Token verification failed: " . $e->getMessage());
        if ($exitOnFail) {
            returnResponse(401, "Authentication failed: " . $e->getMessage(), null);
        }
        return null;
    }
}

// Generate transaction code with Manila timezone
function generateTransactionCode($db, $userId) {
    // Use Manila timezone for date
    $date = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $dateStr = $date->format('Ymd');
    
    // Find the highest existing transaction number for this user and date
    $sql = "SELECT transaction_code FROM requests 
            WHERE user_id = :user_id 
            AND transaction_code LIKE :pattern
            ORDER BY transaction_code DESC
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $pattern = "CSUMGB-{$dateStr}-{$userId}-%";
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':pattern', $pattern);
    $stmt->execute();
    
    $nextNum = 1;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Extract the sequence number
        $parts = explode('-', $row['transaction_code']);
        $lastPart = end($parts);
        $lastNum = intval($lastPart);
        $nextNum = $lastNum + 1;
    }
    
    // Format with leading zeros
    $formattedNum = str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    return "CSUMGB-{$dateStr}-{$userId}-{$formattedNum}";
} 