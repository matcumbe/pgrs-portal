<?php
require_once 'config.php';
require_once 'requests_config.php';

class TransactionsAPI {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Create new transaction
    public function createTransaction($requestId, $paymentMethod, $paymentAmount, $paymentReference, $proofDocument = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO transactions (
                    request_id, payment_method, payment_amount, 
                    payment_reference, proof_document, status
                ) VALUES (?, ?, ?, ?, ?, 'Pending')
            ");
            
            $stmt->bind_param("isdss", 
                $requestId, 
                $paymentMethod, 
                $paymentAmount, 
                $paymentReference, 
                $proofDocument
            );
            
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating transaction: " . $e->getMessage());
            return false;
        }
    }
    
    // Get transaction details
    public function getTransaction($transactionId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT t.*, r.request_reference, u.username as verified_by_username
                FROM transactions t
                LEFT JOIN requests r ON t.request_id = r.request_id
                LEFT JOIN webgnis_users.users u ON t.verified_by = u.user_id
                WHERE t.transaction_id = ?
            ");
            
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting transaction: " . $e->getMessage());
            return null;
        }
    }
    
    // Get transactions by request
    public function getTransactionsByRequest($requestId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT t.*, u.username as verified_by_username
                FROM transactions t
                LEFT JOIN webgnis_users.users u ON t.verified_by = u.user_id
                WHERE t.request_id = ?
                ORDER BY t.payment_date DESC
            ");
            
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting transactions by request: " . $e->getMessage());
            return [];
        }
    }
    
    // Update transaction status
    public function updateTransactionStatus($transactionId, $status, $verifiedBy = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE transactions 
                SET status = ?, 
                    verification_date = IF(? IS NOT NULL, CURRENT_TIMESTAMP, verification_date),
                    verified_by = ?
                WHERE transaction_id = ?
            ");
            
            $stmt->bind_param("siii", $status, $verifiedBy, $verifiedBy, $transactionId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating transaction status: " . $e->getMessage());
            return false;
        }
    }
    
    // Get transactions by user
    public function getTransactionsByUser($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT t.*, r.request_reference, u.username as verified_by_username
                FROM transactions t
                JOIN requests r ON t.request_id = r.request_id
                LEFT JOIN webgnis_users.users u ON t.verified_by = u.user_id
                WHERE r.user_id = ?
                ORDER BY t.payment_date DESC
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting transactions by user: " . $e->getMessage());
            return [];
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionsAPI = new TransactionsAPI($conn);
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['action'] ?? '') {
        case 'create':
            $response = $transactionsAPI->createTransaction(
                $data['requestId'],
                $data['paymentMethod'],
                $data['paymentAmount'],
                $data['paymentReference'],
                $data['proofDocument'] ?? null
            );
            break;
            
        case 'update_status':
            $response = $transactionsAPI->updateTransactionStatus(
                $data['transactionId'],
                $data['status'],
                $data['verifiedBy'] ?? null
            );
            break;
            
        default:
            http_response_code(400);
            $response = ['error' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $transactionsAPI = new TransactionsAPI($conn);
    
    if (isset($_GET['transactionId'])) {
        $response = $transactionsAPI->getTransaction($_GET['transactionId']);
    } elseif (isset($_GET['requestId'])) {
        $response = $transactionsAPI->getTransactionsByRequest($_GET['requestId']);
    } elseif (isset($_GET['userId'])) {
        $response = $transactionsAPI->getTransactionsByUser($_GET['userId']);
    } else {
        http_response_code(400);
        $response = ['error' => 'Missing required parameters'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
}
?> 