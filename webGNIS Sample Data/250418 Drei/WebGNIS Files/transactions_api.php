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

// Extract ID if present in the endpoint (e.g., transactions/123)
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

// Route the request to the appropriate function
try {
    switch ($mainEndpoint) {
        case 'payment-methods':
            if ($method === 'GET') {
                getPaymentMethods($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'submit':
            if ($method === 'POST') {
                submitPayment($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'verify':
            if ($method === 'PUT' || $method === 'POST') {
                verifyPayment($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'list':
            if ($method === 'GET') {
                if (isset($_GET['request'])) {
                    getPaymentsByRequest($db, intval($_GET['request']));
                } else if (isset($_GET['user'])) {
                    getUserPayments($db, intval($_GET['user']));
                } else {
                    getAllPayments($db);
                }
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'view':
            if ($method === 'GET') {
                if ($id) {
                    getTransactionById($db, $id);
                } else {
                    returnResponse(400, "Transaction ID required", null);
                }
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'upload-proof':
            if ($method === 'POST') {
                uploadPaymentProof($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case '':
            // No endpoint specified - show API info
            returnResponse(200, "WebGNIS Transactions API", [
                'version' => '1.0',
                'endpoints' => [
                    'payment-methods', 'submit', 'verify', 'list', 'view', 'upload-proof'
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
 * Get all available payment methods
 */
function getPaymentMethods($db) {
    $sql = "SELECT * FROM payment_methods WHERE is_active = TRUE ORDER BY display_order";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $methods = $stmt->fetchAll();
    
    returnResponse(200, "Payment methods retrieved", $methods);
}

/**
 * Submit a payment for a request
 */
function submitPayment($db) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    $userId = $token->user_id;
    
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->request_id) || !isset($data->payment_method_id) || !isset($data->amount)) {
        returnResponse(400, "Missing required fields: request_id, payment_method_id, amount", null);
        return;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Check if request exists and belongs to user
        $requestSql = "SELECT r.*, rs.status_name 
                      FROM requests r
                      JOIN request_statuses rs ON r.status_id = rs.status_id
                      WHERE r.request_id = :request_id";
        $requestStmt = $db->prepare($requestSql);
        $requestStmt->bindParam(':request_id', $data->request_id);
        $requestStmt->execute();
        
        if ($requestStmt->rowCount() == 0) {
            $db->rollBack();
            returnResponse(404, "Request not found", null);
            return;
        }
        
        $request = $requestStmt->fetch();
        
        // Verify request belongs to user
        if ($token->user_type !== 'admin' && $request['user_id'] != $userId) {
            $db->rollBack();
            returnResponse(403, "Access denied", null);
            return;
        }
        
        // Verify payment method exists
        $methodSql = "SELECT * FROM payment_methods WHERE payment_method_id = :id AND is_active = TRUE";
        $methodStmt = $db->prepare($methodSql);
        $methodStmt->bindParam(':id', $data->payment_method_id);
        $methodStmt->execute();
        
        if ($methodStmt->rowCount() == 0) {
            $db->rollBack();
            returnResponse(400, "Invalid payment method", null);
            return;
        }
        
        // Generate transaction code in the format CSUMGB-YYYYMMDD-<userid>-001
        $dateString = date('Ymd');
        
        // Find the latest transaction code for this user on this day to determine the sequence number
        $seqSql = "SELECT payment_reference FROM transactions t
                  JOIN requests r ON t.request_id = r.request_id
                  WHERE r.user_id = :user_id 
                  AND payment_reference LIKE :ref_pattern
                  ORDER BY transaction_id DESC LIMIT 1";
        $seqStmt = $db->prepare($seqSql);
        $refPattern = "CSUMGB-$dateString-$userId-%";
        $seqStmt->bindParam(':user_id', $userId);
        $seqStmt->bindParam(':ref_pattern', $refPattern);
        $seqStmt->execute();
        
        if ($seqStmt->rowCount() > 0) {
            $lastRef = $seqStmt->fetch()['payment_reference'];
            $lastSeq = intval(substr($lastRef, -3)); // Extract last 3 digits
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }
        
        // Format sequence number with leading zeros
        $seqFormatted = sprintf('%03d', $newSeq);
        $paymentReference = "CSUMGB-$dateString-$userId-$seqFormatted";
        
        // Use provided remarks if any
        $remarks = isset($data->remarks) ? $data->remarks : null;
        
        // Create payment transaction
        $sql = "INSERT INTO transactions (request_id, payment_method_id, amount, payment_reference, remarks) 
                VALUES (:request_id, :payment_method_id, :amount, :payment_reference, :remarks)";
                
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':request_id', $data->request_id);
        $stmt->bindParam(':payment_method_id', $data->payment_method_id);
        $stmt->bindParam(':amount', $data->amount);
        $stmt->bindParam(':payment_reference', $paymentReference);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->execute();
        
        $transactionId = $db->lastInsertId();
        
        // Update request status to Paid if not already
        if ($request['status_name'] == 'Not Paid') {
            // Get status ID for "Paid"
            $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Paid'";
            $statusStmt = $db->prepare($statusSql);
            $statusStmt->execute();
            $statusResult = $statusStmt->fetch();
            
            if ($statusResult) {
                $statusId = $statusResult['status_id'];
                
                // Update request status
                $updateSql = "UPDATE requests SET status_id = :status_id WHERE request_id = :request_id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindParam(':status_id', $statusId);
                $updateStmt->bindParam(':request_id', $data->request_id);
                $updateStmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(201, "Payment submitted successfully", [
            'transaction_id' => $transactionId,
            'payment_reference' => $paymentReference
        ]);
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->rollBack();
        returnResponse(500, "Failed to submit payment: " . $e->getMessage(), null);
    }
}

/**
 * Upload payment proof file
 */
function uploadPaymentProof($db) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    
    if (!isset($_POST['transaction_id'])) {
        returnResponse(400, "Missing transaction ID", null);
        return;
    }
    
    $transactionId = intval($_POST['transaction_id']);
    
    // Check if transaction exists and user has access
    $checkSql = "SELECT t.*, r.user_id 
                FROM transactions t 
                JOIN requests r ON t.request_id = r.request_id 
                WHERE t.transaction_id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':id', $transactionId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        returnResponse(404, "Transaction not found", null);
        return;
    }
    
    $transaction = $checkStmt->fetch();
    
    // Verify transaction belongs to user
    if ($token->user_type !== 'admin' && $transaction['user_id'] != $token->user_id) {
        returnResponse(403, "Access denied", null);
        return;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = isset($_FILES['proof_file']) ? getUploadErrorMessage($_FILES['proof_file']['error']) : "No file uploaded";
        returnResponse(400, $errorMessage, null);
        return;
    }
    
    // Validate file
    $file = $_FILES['proof_file'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        returnResponse(400, "Invalid file type. Allowed types: JPEG, PNG, GIF, PDF", null);
        return;
    }
    
    if ($file['size'] > $maxFileSize) {
        returnResponse(400, "File too large. Maximum size: 5MB", null);
        return;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/payment_proofs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'proof_' . $transactionId . '_' . date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
    $targetFilePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        returnResponse(500, "Failed to upload file", null);
        return;
    }
    
    // Update transaction with file path
    $sql = "UPDATE transactions SET payment_proof_file = :file_path WHERE transaction_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_path', $fileName);
    $stmt->bindParam(':id', $transactionId);
    
    if ($stmt->execute()) {
        returnResponse(200, "Payment proof uploaded successfully", [
            'file_path' => $fileName
        ]);
    } else {
        // Delete uploaded file if update fails
        unlink($targetFilePath);
        returnResponse(500, "Failed to update transaction", null);
    }
}

/**
 * Get upload error message
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload";
        default:
            return "Unknown upload error";
    }
}

/**
 * Verify a payment (admin only)
 */
function verifyPayment($db) {
    // Verify user is admin
    $token = verifyToken('admin', true);
    
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->transaction_id)) {
        returnResponse(400, "Missing transaction ID", null);
        return;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Check if transaction exists
        $transactionSql = "SELECT t.*, r.request_id, r.status_id as current_status_id
                          FROM transactions t
                          JOIN requests r ON t.request_id = r.request_id
                          JOIN request_statuses rs ON r.status_id = rs.status_id
                          WHERE t.transaction_id = :id";
        $transactionStmt = $db->prepare($transactionSql);
        $transactionStmt->bindParam(':id', $data->transaction_id);
        $transactionStmt->execute();
        
        if ($transactionStmt->rowCount() == 0) {
            $db->rollBack();
            returnResponse(404, "Transaction not found", null);
            return;
        }
        
        $transaction = $transactionStmt->fetch();
        
        // Update transaction as verified
        $sql = "UPDATE transactions 
                SET verified = TRUE, 
                    verified_by = :admin_id, 
                    verified_date = NOW()";
                    
        // Add remarks if provided
        if (isset($data->remarks)) {
            $sql .= ", remarks = :remarks";
        }
        
        $sql .= " WHERE transaction_id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':admin_id', $token->user_id);
        $stmt->bindParam(':id', $data->transaction_id);
        
        if (isset($data->remarks)) {
            $stmt->bindParam(':remarks', $data->remarks);
        }
        
        $stmt->execute();
        
        // Determine which status to set for the request
        $targetStatus = 'Pending'; // Default to Pending if not specified
        
        if (isset($data->status)) {
            // Allow admin to specify the target status
            $targetStatus = $data->status;
        }
        
        // Get status ID for the target status
        $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = :status_name";
        $statusStmt = $db->prepare($statusSql);
        $statusStmt->bindParam(':status_name', $targetStatus);
        $statusStmt->execute();
        $statusResult = $statusStmt->fetch();
        
        if ($statusResult) {
            $statusId = $statusResult['status_id'];
            
            // Only update status if different from current status
            if ($statusId != $transaction['current_status_id']) {
                $updateSql = "UPDATE requests SET status_id = :status_id";
                
                // Add status update reason if provided
                if (isset($data->status_remarks)) {
                    $updateSql .= ", remarks = :status_remarks";
                }
                
                $updateSql .= " WHERE request_id = :request_id";
                
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindParam(':status_id', $statusId);
                $updateStmt->bindParam(':request_id', $transaction['request_id']);
                
                if (isset($data->status_remarks)) {
                    $updateStmt->bindParam(':status_remarks', $data->status_remarks);
                }
                
                $updateStmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(200, "Payment verified and request status updated to $targetStatus", [
            'transaction_id' => $data->transaction_id,
            'new_status' => $targetStatus
        ]);
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->rollBack();
        returnResponse(500, "Failed to verify payment: " . $e->getMessage(), null);
    }
}

/**
 * Get all payments (admin only)
 */
function getAllPayments($db) {
    // Verify user is admin
    $token = verifyToken('admin', true);
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;
    
    // Get transactions with payment method and request info
    $sql = "SELECT t.*, pm.method_name, 
            r.reference_number, r.user_id,
            u.username, u.email
            FROM transactions t
            JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
            JOIN requests r ON t.request_id = r.request_id
            JOIN users u ON r.user_id = u.user_id
            ORDER BY t.transaction_date DESC
            LIMIT :offset, :per_page";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM transactions";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $totalCount = $countResult['count'];
    
    $totalPages = ceil($totalCount / $perPage);
    
    returnResponse(200, "Payments retrieved successfully", [
        'transactions' => $transactions,
        'pagination' => [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages
        ]
    ]);
}

/**
 * Get payments by request ID
 */
function getPaymentsByRequest($db, $requestId) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    
    // Check if request exists and user has access
    $requestSql = "SELECT user_id FROM requests WHERE request_id = :id";
    $requestStmt = $db->prepare($requestSql);
    $requestStmt->bindParam(':id', $requestId);
    $requestStmt->execute();
    
    if ($requestStmt->rowCount() == 0) {
        returnResponse(404, "Request not found", null);
        return;
    }
    
    $request = $requestStmt->fetch();
    
    // Verify user has access to this request (must be owner or admin)
    if ($token->user_type !== 'admin' && $token->user_id !== $request['user_id']) {
        returnResponse(403, "Access denied", null);
        return;
    }
    
    // Get transactions for the request
    $sql = "SELECT t.*, pm.method_name 
            FROM transactions t
            JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
            WHERE t.request_id = :request_id
            ORDER BY t.transaction_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':request_id', $requestId);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll();
    
    returnResponse(200, "Request payments retrieved", $transactions);
}

/**
 * Get payments by user ID
 */
function getUserPayments($db, $userId) {
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
    
    // Get transactions with payment method and request info
    $sql = "SELECT t.*, pm.method_name, r.reference_number
            FROM transactions t
            JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
            JOIN requests r ON t.request_id = r.request_id
            WHERE r.user_id = :user_id
            ORDER BY t.transaction_date DESC
            LIMIT :offset, :per_page";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count 
                FROM transactions t
                JOIN requests r ON t.request_id = r.request_id
                WHERE r.user_id = :user_id";
    $countStmt = $db->prepare($countSql);
    $countStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $totalCount = $countResult['count'];
    
    $totalPages = ceil($totalCount / $perPage);
    
    returnResponse(200, "User payments retrieved", [
        'transactions' => $transactions,
        'pagination' => [
            'total' => $totalCount,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages
        ]
    ]);
}

/**
 * Get transaction by ID
 */
function getTransactionById($db, $transactionId) {
    // Verify user is logged in
    $token = verifyToken(null, true);
    
    // Get transaction details
    $sql = "SELECT t.*, pm.method_name, 
            r.reference_number, r.request_id, r.user_id,
            u.username, u.email
            FROM transactions t
            JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
            JOIN requests r ON t.request_id = r.request_id
            JOIN users u ON r.user_id = u.user_id
            WHERE t.transaction_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $transactionId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        returnResponse(404, "Transaction not found", null);
        return;
    }
    
    $transaction = $stmt->fetch();
    
    // Verify user has access to this transaction (must be owner or admin)
    if ($token->user_type !== 'admin' && $token->user_id !== $transaction['user_id']) {
        returnResponse(403, "Access denied", null);
        return;
    }
    
    returnResponse(200, "Transaction details retrieved", $transaction);
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