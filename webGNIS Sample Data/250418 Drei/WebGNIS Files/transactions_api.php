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
                // Check for ID in different locations: direct query param, path segment, or JSON in request body
                $transactionId = null;
                
                // Check if ID is in the URL path (view/123)
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    $transactionId = intval($parts[1]);
                } 
                // Check if ID is a direct query parameter (?action=view&id=123)
                else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                    $transactionId = intval($_GET['id']);
                }
                
                if ($transactionId) {
                    error_log("[" . date('Y-m-d H:i:s') . "] Fetching transaction ID: " . $transactionId . " from action: " . $endpoint);
                    getTransactionById($db, $transactionId);
                } else {
                    error_log("[" . date('Y-m-d H:i:s') . "] Missing transaction ID in request. Action: " . $endpoint . ", GET params: " . print_r($_GET, true));
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
            
        case 'update':
            if ($method !== 'POST') {
                returnResponse(405, "Method not allowed. Use POST for updates.", null);
            }
            
            // Check authorization
            $token = verifyToken(null, true);
            if (!$token || $token->user_type !== 'admin') {
                returnResponse(401, "Unauthorized access. Only admins can update transaction status.", null);
            }
            
            // Get transaction ID from URL path
            $transactionId = null;
            if (count($parts) > 1 && is_numeric($parts[1])) {
                $transactionId = intval($parts[1]);
            } else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $transactionId = intval($_GET['id']);
            }
            
            if (!$transactionId) {
                returnResponse(400, "Transaction ID is required", null);
            }
            
            // Get transaction data
            try {
                // Get request data
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                if (!$data) {
                    returnResponse(400, "Invalid JSON data", null);
                }
                
                error_log("[" . date('Y-m-d H:i:s') . "] Updating transaction ID: $transactionId with data: " . print_r($data, true));
                
                // Verify transaction exists
                $checkSql = "SELECT t.*, r.request_id FROM transactions t 
                             JOIN requests r ON t.request_id = r.request_id 
                             WHERE t.transaction_id = :transaction_id";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->bindParam(':transaction_id', $transactionId);
                $checkStmt->execute();
                
                $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if (!$transaction) {
                    returnResponse(404, "Transaction not found", null);
                }
                
                // Start transaction
                $db->beginTransaction();
                
                // Build update SQL based on provided fields
                $updateFields = [];
                $params = [':transaction_id' => $transactionId];
                
                // Handle status changes
                if (isset($data['status'])) {
                    $statusName = $data['status'];
                    
                    // Get status ID from name
                    $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = :status_name LIMIT 1";
                    $statusStmt = $db->prepare($statusSql);
                    $statusStmt->bindParam(':status_name', $statusName);
                    $statusStmt->execute();
                    
                    $statusResult = $statusStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$statusResult) {
                        $db->rollBack();
                        returnResponse(400, "Invalid status: $statusName", null);
                    }
                    
                    $statusId = $statusResult['status_id'];
                    $updateFields[] = "status_id = :status_id";
                    $params[':status_id'] = $statusId;
                    
                    // Also update request status
                    $updateRequestSql = "UPDATE requests SET status_id = :status_id 
                                        WHERE request_id = :request_id";
                    $updateRequestStmt = $db->prepare($updateRequestSql);
                    $updateRequestStmt->bindParam(':status_id', $statusId);
                    $updateRequestStmt->bindParam(':request_id', $transaction['request_id']);
                    $updateRequestStmt->execute();
                }
                
                // Handle verification
                if (isset($data['verified'])) {
                    $updateFields[] = "verified = :verified";
                    $params[':verified'] = $data['verified'] ? 1 : 0;
                }
                
                if (isset($data['verified_by'])) {
                    $updateFields[] = "verified_by = :verified_by";
                    $params[':verified_by'] = $data['verified_by'];
                }
                
                if (isset($data['verified_date'])) {
                    $updateFields[] = "verified_date = :verified_date";
                    $params[':verified_date'] = $data['verified_date'];
                }
                
                if (isset($data['remarks'])) {
                    $updateFields[] = "remarks = :remarks";
                    $params[':remarks'] = $data['remarks'];
                }
                
                // If no fields to update, return error
                if (empty($updateFields)) {
                    $db->rollBack();
                    returnResponse(400, "No fields to update", null);
                }
                
                // Build and execute update query
                $updateSql = "UPDATE transactions SET " . implode(", ", $updateFields) . " 
                              WHERE transaction_id = :transaction_id";
                $updateStmt = $db->prepare($updateSql);
                
                foreach ($params as $key => $value) {
                    $updateStmt->bindValue($key, $value);
                }
                
                $updateStmt->execute();
                
                // Commit transaction
                $db->commit();
                
                // Return success
                returnResponse(200, "Transaction updated successfully", [
                    'transaction_id' => $transactionId
                ]);
                
            } catch (Exception $e) {
                // Rollback transaction on error
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("[" . date('Y-m-d H:i:s') . "] Error updating transaction: " . $e->getMessage());
                returnResponse(500, "Failed to update transaction: " . $e->getMessage(), null);
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
    
    if (!isset($data->request_id) || !isset($data->payment_method_id) || !isset($data->paid_amount)) {
        returnResponse(400, "Missing required fields: request_id, payment_method_id, paid_amount", null);
        return;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Check if request exists and belongs to user
        $requestSql = "SELECT r.*, rs.status_name, rs.status_id
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
        
        // Get transaction code from the request, or generate if it doesn't exist
        $transactionCode = $request['transaction_code'];
        
        if (!$transactionCode) {
            // Use our new function to generate a transaction code with Manila timezone
            $transactionCode = generateTransactionCode($db, $userId);
            
            // Update the request with this transaction code
            $updateCodeSql = "UPDATE requests SET transaction_code = :transaction_code WHERE request_id = :request_id";
            $updateCodeStmt = $db->prepare($updateCodeSql);
            $updateCodeStmt->bindParam(':transaction_code', $transactionCode);
            $updateCodeStmt->bindParam(':request_id', $data->request_id);
            $updateCodeStmt->execute();
        }
        
        // Use provided reference number if any
        $referenceNumber = isset($data->reference_number) ? $data->reference_number : null;
        
        // Use provided remarks if any
        $remarks = isset($data->remarks) ? $data->remarks : null;
        
        // Get payment amount from request
        $paymentAmount = $request['total_amount'];
        
        // Determine appropriate status ID
        $statusId = null;
        if (isset($data->is_pay_later) && $data->is_pay_later === true) {
            // For pay later transactions, use "Pending Payment" status
            $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Pending Payment'";
            $remarks = isset($remarks) ? $remarks : "Payment pending - created via Pay Later option";
        } else {
            // For normal transactions, use "Paid" status
            $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Paid'";
        }
        
        $statusStmt = $db->prepare($statusSql);
        $statusStmt->execute();
        $statusResult = $statusStmt->fetch();
        
        if (!$statusResult) {
            $db->rollBack();
            returnResponse(500, "Failed to get status ID", null);
            return;
        }
        
        $statusId = $statusResult['status_id'];
        
        // Create payment transaction
        $sql = "INSERT INTO transactions (
                    transaction_code, request_id, user_id, status_id, 
                    payment_method_id, payment_amount, paid_amount, 
                    payment_reference, remarks
                ) VALUES (
                    :transaction_code, :request_id, :user_id, :status_id,
                    :payment_method_id, :payment_amount, :paid_amount,
                    :payment_reference, :remarks
                )";
                
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':transaction_code', $transactionCode);
        $stmt->bindParam(':request_id', $data->request_id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':status_id', $statusId);
        $stmt->bindParam(':payment_method_id', $data->payment_method_id);
        $stmt->bindParam(':payment_amount', $paymentAmount);
        $stmt->bindParam(':paid_amount', $data->paid_amount);
        $stmt->bindParam(':payment_reference', $referenceNumber);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->execute();
        
        $transactionId = $db->lastInsertId();
        
        // Update request status if not already set appropriately
        if ($request['status_name'] == 'Not Paid') {
            // Update request status
            $updateSql = "UPDATE requests SET status_id = :status_id WHERE request_id = :request_id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':status_id', $statusId);
            $updateStmt->bindParam(':request_id', $data->request_id);
            $updateStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        // Determine status name for response
        $statusName = (isset($data->is_pay_later) && $data->is_pay_later) ? "Pending Payment" : "Paid";
        
        returnResponse(201, (isset($data->is_pay_later) && $data->is_pay_later) ? 
            "Payment request created successfully" : "Payment submitted successfully", [
            'transaction_id' => $transactionId,
            'transaction_code' => $transactionCode,
            'request_id' => $data->request_id,
            'payment_amount' => $paymentAmount,
            'paid_amount' => $data->paid_amount,
            'status' => $statusName,
            'is_pay_later' => isset($data->is_pay_later) && $data->is_pay_later
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
    $userId = $token->user_id;
    
    // For debugging
    error_log("uploadPaymentProof called with POST: " . print_r($_POST, true));
    error_log("FILES array: " . print_r($_FILES, true));
    
    // Get request_id directly from POST for new implementation
    if (isset($_POST['request_id']) && isset($_FILES['proof_file'])) {
        // Handle new implementation with direct file upload
        $requestId = intval($_POST['request_id']);
        $paidAmount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : null;
        $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;
        $referenceNumber = isset($_POST['reference_number']) ? $_POST['reference_number'] : null;
        
        // Validate required fields
        if (!$paidAmount || !$paymentMethod || !$referenceNumber) {
            returnResponse(400, "Missing required fields: paid_amount, payment_method, reference_number", null);
            return;
        }
        
        // Check if file was properly uploaded
        if ($_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
            returnResponse(400, "File upload error: " . getUploadErrorMessage($_FILES['proof_file']['error']), null);
            return;
        }
        
        // Define allowed file types and maximum file size (5MB)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB in bytes
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $_FILES['proof_file']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($fileType, $allowedTypes)) {
            returnResponse(400, "Invalid file type. Allowed types: JPG, PNG, GIF, PDF", null);
            return;
        }
        
        // Check file size
        if ($_FILES['proof_file']['size'] > $maxFileSize) {
            returnResponse(400, "File too large. Maximum size: 5MB", null);
            return;
        }
        
        // Get payment method ID - try both "method_name" and direct lookup
        $methodSql = "SELECT payment_method_id FROM payment_methods WHERE (method_name = :method_name OR payment_method_id = :method_id) AND is_active = TRUE";
        $methodStmt = $db->prepare($methodSql);
        $methodStmt->bindParam(':method_name', $paymentMethod);
        $methodId = intval($paymentMethod);
        $methodStmt->bindParam(':method_id', $methodId);
        $methodStmt->execute();
        
        if ($methodStmt->rowCount() == 0) {
            // Fallback for common payment methods
            $paymentMethodId = null;
            if ($paymentMethod == 'linkbiz') {
                $paymentMethodId = 1;
            } else if ($paymentMethod == 'bank_transfer') {
                $paymentMethodId = 2;
            } else if ($paymentMethod == 'cash_deposit') {
                $paymentMethodId = 3;
            }
            
            // Try matching with the exact method names in the database
            if ($paymentMethodId === null) {
                if (strtolower($paymentMethod) == 'link biz' || strtolower($paymentMethod) == 'linkbiz') {
                    $paymentMethodId = 1;
                } else if (strtolower($paymentMethod) == 'bank transfer') {
                    $paymentMethodId = 2;
                } else if (strtolower($paymentMethod) == 'cash deposit') {
                    $paymentMethodId = 3;
                }
            }
            
            if ($paymentMethodId === null) {
                returnResponse(400, "Invalid payment method: $paymentMethod", null);
                return;
            }
            
            error_log("Using fallback payment method ID: $paymentMethodId for method: $paymentMethod");
        } else {
            $paymentMethodId = $methodStmt->fetch()['payment_method_id'];
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Get request details
            $requestSql = "SELECT r.*, rs.status_name, rs.status_id
                          FROM requests r
                          JOIN request_statuses rs ON r.status_id = rs.status_id
                          WHERE r.request_id = :request_id";
            $requestStmt = $db->prepare($requestSql);
            $requestStmt->bindParam(':request_id', $requestId);
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
            
            // Generate transaction code
            $transactionCode = generateTransactionCode($db, $userId);
            
            // Get payment amount from request
            $paymentAmount = $request['total_amount'];
            
            // Get status ID for "Paid"
            $paidStatusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Paid'";
            $paidStatusStmt = $db->prepare($paidStatusSql);
            $paidStatusStmt->execute();
            $paidStatusResult = $paidStatusStmt->fetch();
            
            if (!$paidStatusResult) {
                $db->rollBack();
                returnResponse(500, "Failed to get paid status ID", null);
                return;
            }
            
            $paidStatusId = $paidStatusResult['status_id'];
            
            // Create payment_proofs directory if it doesn't exist
            $uploadsDir = dirname(__FILE__) . '/Assets/payment_proofs';
            if (!is_dir($uploadsDir)) {
                if (!mkdir($uploadsDir, 0755, true)) {
                    error_log("Failed to create directory: $uploadsDir");
                    $db->rollBack();
                    returnResponse(500, "Failed to create upload directory", null);
                    return;
                } else {
                    error_log("Created payment_proofs directory: $uploadsDir");
                }
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
            $filename = 'payment_proof_' . $requestId . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadsDir . '/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $filePath)) {
                error_log("Failed to move uploaded file to: $filePath");
                $db->rollBack();
                returnResponse(500, "Failed to save uploaded file", null);
                return;
            }
            
            // Create payment transaction
            $sql = "INSERT INTO transactions (
                        transaction_code, request_id, user_id, status_id, 
                        payment_method_id, payment_amount, paid_amount, 
                        payment_reference, payment_date, payment_proof_file
                    ) VALUES (
                        :transaction_code, :request_id, :user_id, :status_id,
                        :payment_method_id, :payment_amount, :paid_amount,
                        :payment_reference, NOW(), :proof_filename
                    )";
                    
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':transaction_code', $transactionCode);
            $stmt->bindParam(':request_id', $requestId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':status_id', $paidStatusId);
            $stmt->bindParam(':payment_method_id', $paymentMethodId);
            $stmt->bindParam(':payment_amount', $paymentAmount);
            $stmt->bindParam(':paid_amount', $paidAmount);
            $stmt->bindParam(':payment_reference', $referenceNumber);
            $stmt->bindParam(':proof_filename', $filename);
            $stmt->execute();
            
            $transactionId = $db->lastInsertId();
            
            // Update request status to Paid if not already
            if ($request['status_name'] == 'Not Paid') {
                $updateSql = "UPDATE requests SET status_id = :status_id, transaction_code = :transaction_code WHERE request_id = :request_id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindParam(':status_id', $paidStatusId);
                $updateStmt->bindParam(':transaction_code', $transactionCode);
                $updateStmt->bindParam(':request_id', $requestId);
                $updateStmt->execute();
            }
            
            // Commit the transaction
            $db->commit();
            
            returnResponse(200, "Payment submitted successfully", [
                'transaction_id' => $transactionId,
                'transaction_code' => $transactionCode,
                'request_id' => $requestId,
                'proof_filename' => $filename
            ]);
            
        } catch (Exception $e) {
            error_log("Exception in uploadPaymentProof: " . $e->getMessage());
            $db->rollBack();
            returnResponse(500, "Failed to process payment: " . $e->getMessage(), null);
            return;
        }
    } else if (!isset($_POST['transaction_id']) || !isset($_FILES['proof_file'])) {
        returnResponse(400, "Missing required fields: transaction_id or request_id, and proof_file", null);
        return;
    } else {
        // Handle legacy implementation with existing transaction
        $transactionId = intval($_POST['transaction_id']);
        
        // Check if transaction exists and belongs to the user
        $checkSql = "SELECT t.*, r.user_id 
                    FROM transactions t 
                    JOIN requests r ON t.request_id = r.request_id 
                    WHERE t.transaction_id = :transaction_id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':transaction_id', $transactionId);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            returnResponse(404, "Transaction not found", null);
            return;
        }
        
        $transaction = $checkStmt->fetch();
        
        // Verify transaction belongs to user
        if ($token->user_type !== 'admin' && $transaction['user_id'] != $userId) {
            returnResponse(403, "Access denied", null);
            return;
        }
        
        // Check if file was properly uploaded
        if ($_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
            returnResponse(400, "File upload error: " . getUploadErrorMessage($_FILES['proof_file']['error']), null);
            return;
        }
        
        // Define allowed file types and maximum file size (5MB)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB in bytes
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $_FILES['proof_file']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($fileType, $allowedTypes)) {
            returnResponse(400, "Invalid file type. Allowed types: JPG, PNG, GIF, PDF", null);
            return;
        }
        
        // Check file size
        if ($_FILES['proof_file']['size'] > $maxFileSize) {
            returnResponse(400, "File too large. Maximum size: 5MB", null);
            return;
        }
        
        // Create payment_proofs directory if it doesn't exist
        $uploadsDir = dirname(__FILE__) . '/Assets/payment_proofs';
        if (!is_dir($uploadsDir)) {
            if (!mkdir($uploadsDir, 0755, true)) {
                error_log("Failed to create directory: $uploadsDir");
                returnResponse(500, "Failed to create upload directory", null);
                return;
            } else {
                error_log("Created payment_proofs directory: $uploadsDir");
            }
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        $filename = 'payment_proof_' . $transactionId . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadsDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $filePath)) {
            returnResponse(500, "Failed to save uploaded file", null);
            return;
        }
        
        // Update transaction record with proof file
        $sql = "UPDATE transactions SET payment_proof_file = :proof_filename WHERE transaction_id = :transaction_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':proof_filename', $filename);
        $stmt->bindParam(':transaction_id', $transactionId);
        
        try {
            $stmt->execute();
            
            // If we're in a transaction (from the new implementation), commit it
            if (isset($requestId)) {
                $db->commit();
                returnResponse(200, "Payment submitted successfully", [
                    'transaction_id' => $transactionId,
                    'transaction_code' => isset($transactionCode) ? $transactionCode : $transaction['transaction_code'],
                    'request_id' => isset($requestId) ? $requestId : $transaction['request_id'],
                    'proof_filename' => $filename
                ]);
            } else {
                returnResponse(200, "Payment proof uploaded successfully", [
                    'transaction_id' => $transactionId,
                    'proof_filename' => $filename
                ]);
            }
        } catch (Exception $e) {
            // Roll back if we're in a transaction
            if (isset($requestId)) {
                $db->rollBack();
            }
            
            // Delete the uploaded file if database update fails
            @unlink($filePath);
            returnResponse(500, "Failed to update transaction record: " . $e->getMessage(), null);
        }
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
 * Verify payment (admin only)
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
        // Get transaction details
        $transactionSql = "SELECT t.*, r.status_id as request_status_id, r.user_id
                          FROM transactions t
                          JOIN requests r ON t.request_id = r.request_id
                          WHERE t.transaction_id = :transaction_id";
        $transactionStmt = $db->prepare($transactionSql);
        $transactionStmt->bindParam(':transaction_id', $data->transaction_id);
        $transactionStmt->execute();
        
        if ($transactionStmt->rowCount() == 0) {
            $db->rollBack();
            returnResponse(404, "Transaction not found", null);
            return;
        }
        
        $transaction = $transactionStmt->fetch();
        
        // Update transaction
        $sql = "UPDATE transactions 
                SET verified = TRUE, 
                    verified_by = :verified_by, 
                    verified_date = NOW()";
        
        // Add remarks if provided
        if (isset($data->remarks) && !empty($data->remarks)) {
            $sql .= ", remarks = :remarks";
        }
        
        $sql .= " WHERE transaction_id = :transaction_id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':verified_by', $token->user_id);
        $stmt->bindParam(':transaction_id', $data->transaction_id);
        
        if (isset($data->remarks) && !empty($data->remarks)) {
            $stmt->bindParam(':remarks', $data->remarks);
        }
        
        $stmt->execute();
        
        // Update request status to Pending
        $pendingStatusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Pending'";
        $pendingStatusStmt = $db->prepare($pendingStatusSql);
        $pendingStatusStmt->execute();
        $pendingStatusResult = $pendingStatusStmt->fetch();
        
        if ($pendingStatusResult) {
            $pendingStatusId = $pendingStatusResult['status_id'];
            
            // Only update if current status is Paid
            if ($transaction['request_status_id'] != $pendingStatusId) {
                $updateRequestSql = "UPDATE requests SET status_id = :status_id WHERE request_id = :request_id";
                $updateRequestStmt = $db->prepare($updateRequestSql);
                $updateRequestStmt->bindParam(':status_id', $pendingStatusId);
                $updateRequestStmt->bindParam(':request_id', $transaction['request_id']);
                $updateRequestStmt->execute();
            }
        }
        
        // Send email notification to user
        $userSql = "SELECT email, username FROM users WHERE user_id = :user_id";
        $userStmt = $db->prepare($userSql);
        $userStmt->bindParam(':user_id', $transaction['user_id']);
        $userStmt->execute();
        $user = $userStmt->fetch();
        
        if ($user) {
            $subject = "Payment Verification - WebGNIS";
            $message = "Dear {$user['username']},\n\n";
            $message .= "Your payment for transaction code {$transaction['transaction_code']} has been verified. ";
            $message .= "Your request is now in 'Pending' status and will be processed soon.\n\n";
            $message .= "Thank you for using our service!\n\n";
            $message .= "Best regards,\nThe WebGNIS Team";
            
            sendEmail($user['email'], $subject, $message);
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(200, "Payment verified successfully", [
            'transaction_id' => $data->transaction_id,
            'request_id' => $transaction['request_id'],
            'verified_by' => $token->user_id,
            'verified_date' => date('Y-m-d H:i:s')
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
    try {
        // Verify user is admin
        $token = verifyToken('admin', true);
        
        // Debug info - log for troubleshooting
        error_log("[" . date('Y-m-d H:i:s') . "] getAllPayments called by user_id: " . $token->user_id);
        
        // Check if any transactions exist
        $checkSql = "SELECT COUNT(*) as count FROM transactions";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // If no transactions exist, create a test transaction for demonstration
        if ($checkResult['count'] == 0) {
            error_log("[" . date('Y-m-d H:i:s') . "] No transactions found, creating test data");
            createTestTransaction($db);
        }
        
        // Pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
        $offset = ($page - 1) * $perPage;
        
        // Get transactions with payment method and user info
        // Using LEFT JOIN to ensure we get transactions even if some joins fail
        $sql = "SELECT t.*,
                pm.method_name, 
                r.user_id, r.transaction_code,
                u.username, u.email,
                rs.status_name
                FROM transactions t
                LEFT JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
                LEFT JOIN requests r ON t.request_id = r.request_id
                LEFT JOIN users u ON r.user_id = u.user_id
                LEFT JOIN request_statuses rs ON t.status_id = rs.status_id
                ORDER BY t.payment_date DESC
                LIMIT :offset, :per_page";
        
        error_log("[" . date('Y-m-d H:i:s') . "] Running query: " . $sql);
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("[" . date('Y-m-d H:i:s') . "] Found " . count($transactions) . " transactions");
        
        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM transactions";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute();
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $totalCount = $countResult['count'] ?? 0;
        
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
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("[" . date('Y-m-d H:i:s') . "] Error in getAllPayments: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        returnResponse(500, "Failed to retrieve payments: " . $e->getMessage(), null);
    }
}

/**
 * Create a test transaction for development purposes
 */
function createTestTransaction($db) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Check if admin user exists
        $userSql = "SELECT user_id FROM users WHERE username = 'admin' LIMIT 1";
        $userStmt = $db->prepare($userSql);
        $userStmt->execute();
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            error_log("[" . date('Y-m-d H:i:s') . "] Admin user not found, cannot create test transaction");
            $db->rollBack();
            return;
        }
        
        $userId = $userData['user_id'];
        
        // Check for request statuses
        $statusSql = "SELECT status_id FROM request_statuses WHERE status_name = 'Paid' LIMIT 1";
        $statusStmt = $db->prepare($statusSql);
        $statusStmt->execute();
        $statusData = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$statusData) {
            error_log("[" . date('Y-m-d H:i:s') . "] Status 'Paid' not found, cannot create test transaction");
            $db->rollBack();
            return;
        }
        
        $statusId = $statusData['status_id'];
        
        // Check for payment methods
        $methodSql = "SELECT payment_method_id FROM payment_methods WHERE method_name = 'LinkBiz' OR method_name = 'Link Biz' LIMIT 1";
        $methodStmt = $db->prepare($methodSql);
        $methodStmt->execute();
        $methodData = $methodStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$methodData) {
            error_log("[" . date('Y-m-d H:i:s') . "] Payment method not found, using default ID 1");
            $paymentMethodId = 1;
        } else {
            $paymentMethodId = $methodData['payment_method_id'];
        }
        
        // Create a test request if needed
        $requestSql = "SELECT request_id FROM requests WHERE user_id = :user_id LIMIT 1";
        $requestStmt = $db->prepare($requestSql);
        $requestStmt->bindParam(':user_id', $userId);
        $requestStmt->execute();
        $requestData = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        $requestId = null;
        $transactionCode = "CSUMGB-" . date('Ymd') . "-" . $userId . "-001";
        
        if (!$requestData) {
            // Create a new request
            $createRequestSql = "INSERT INTO requests (user_id, request_date, status_id, total_amount, transaction_code) 
                               VALUES (:user_id, NOW(), :status_id, 600.00, :transaction_code)";
            $createRequestStmt = $db->prepare($createRequestSql);
            $createRequestStmt->bindParam(':user_id', $userId);
            $createRequestStmt->bindParam(':status_id', $statusId);
            $createRequestStmt->bindParam(':transaction_code', $transactionCode);
            $createRequestStmt->execute();
            
            $requestId = $db->lastInsertId();
            
            // Create example request items
            $itemsSql = "INSERT INTO request_items (request_id, station_id, station_name, station_type, price) VALUES
                       (:request_id, '01', 'MMA-39', 'horizontal', 300.00),
                       (:request_id, '03', 'MMA-36', 'vertical', 300.00)";
            $itemsStmt = $db->prepare($itemsSql);
            $itemsStmt->bindParam(':request_id', $requestId);
            $itemsStmt->execute();
        } else {
            $requestId = $requestData['request_id'];
        }
        
        // Create test transaction
        $transactionSql = "INSERT INTO transactions (
                        transaction_code, request_id, user_id, status_id,
                        payment_method_id, payment_amount, paid_amount,
                        payment_reference, payment_date
                    ) VALUES (
                        :transaction_code, :request_id, :user_id, :status_id,
                        :payment_method_id, 600.00, 600.00,
                        'REF123456789', NOW()
                    )";
                    
        $transactionStmt = $db->prepare($transactionSql);
        $transactionStmt->bindParam(':transaction_code', $transactionCode);
        $transactionStmt->bindParam(':request_id', $requestId);
        $transactionStmt->bindParam(':user_id', $userId);
        $transactionStmt->bindParam(':status_id', $statusId);
        $transactionStmt->bindParam(':payment_method_id', $paymentMethodId);
        $transactionStmt->execute();
        
        // Commit transaction
        $db->commit();
        
        error_log("[" . date('Y-m-d H:i:s') . "] Created test transaction with ID: " . $db->lastInsertId());
    } catch (Exception $e) {
        $db->rollBack();
        error_log("[" . date('Y-m-d H:i:s') . "] Error creating test transaction: " . $e->getMessage());
    }
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
    try {
        error_log("[" . date('Y-m-d H:i:s') . "] getTransactionById called with ID: " . $transactionId);
        
        // Verify user is logged in
        $token = verifyToken(null, true);
        error_log("[" . date('Y-m-d H:i:s') . "] User verified: user_id=" . $token->user_id . ", user_type=" . $token->user_type);
        
        // Get transaction details using LEFT JOINs for better resilience
        $sql = "SELECT t.*, pm.method_name, 
                r.request_id, r.user_id, r.transaction_code,
                u.username, u.email, 
                rs.status_name
                FROM transactions t
                LEFT JOIN payment_methods pm ON t.payment_method_id = pm.payment_method_id
                LEFT JOIN requests r ON t.request_id = r.request_id
                LEFT JOIN users u ON r.user_id = u.user_id
                LEFT JOIN request_statuses rs ON t.status_id = rs.status_id
                WHERE t.transaction_id = :id";
        
        error_log("[" . date('Y-m-d H:i:s') . "] Executing query for transaction_id: " . $transactionId);
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $transactionId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            error_log("[" . date('Y-m-d H:i:s') . "] Transaction not found: " . $transactionId);
            returnResponse(404, "Transaction not found", null);
            return;
        }
        
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("[" . date('Y-m-d H:i:s') . "] Found transaction: " . $transaction['transaction_id'] . 
                 ", request_id: " . $transaction['request_id'] . 
                 ", user_id: " . $transaction['user_id']);
        
        // Verify user has access to this transaction (must be owner or admin)
        if ($token->user_type !== 'admin' && $token->user_id !== $transaction['user_id']) {
            error_log("[" . date('Y-m-d H:i:s') . "] Access denied: user_id=" . $token->user_id . 
                     " trying to access transaction for user_id=" . $transaction['user_id']);
            returnResponse(403, "Access denied", null);
            return;
        }
        
        returnResponse(200, "Transaction details retrieved", $transaction);
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("[" . date('Y-m-d H:i:s') . "] Error in getTransactionById: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        returnResponse(500, "Failed to retrieve transaction details: " . $e->getMessage(), null);
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
    
    // Properly handle the authorization header
    if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
        if ($exitOnFail) {
            returnResponse(401, "No or invalid authentication token provided", null);
        }
        return null;
    }
    
    $jwt = substr($authHeader, 7);
    
    // DISABLED: Special case for admin authorization - use only for testing
    // This was causing issues with normal users as their requests were being assigned to admin (user_id 1)
    /*
    if ($jwt === 'admin_token' && (!$requiredRole || $requiredRole === 'admin')) {
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
            throw new Exception('Invalid token format');
        }
        
        // Get token parts
        $headerEncoded = $tokenParts[0];
        $payloadEncoded = $tokenParts[1];
        $signatureProvided = $tokenParts[2];
        
        // Verify signature
        $dataToSign = $headerEncoded . "." . $payloadEncoded;
        $expectedSignature = base64_encode(hash_hmac('sha256', $dataToSign, JWT_SECRET, true));

        if ($signatureProvided !== $expectedSignature) {
            throw new Exception('Invalid token signature');
        }
        
        // Decode payload
        $payload = json_decode(base64_decode($payloadEncoded));
        if (!$payload) {
            throw new Exception('Invalid token payload');
        }

        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token expired');
        }
        
        if ($requiredRole && (!isset($payload->user_type) || $payload->user_type !== $requiredRole)) {
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
        if ($exitOnFail) {
            returnResponse(401, "Authentication failed: " . $e->getMessage(), null);
        }
        return null;
    }
}

// Server-side function to generate transaction code
function generateTransactionCode($db, $userId) {
    // Use Manila timezone for date
    $date = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $dateStr = $date->format('Ymd');
    
    // Find the highest existing transaction number for this user and date
    $sql = "SELECT transaction_code FROM transactions 
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