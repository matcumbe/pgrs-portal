<?php
require_once 'config.php';
require_once 'requests_config.php';

class CartAPI {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Add item to cart
    public function addToCart($userId, $stationId, $stationName, $stationType, $sessionId = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO cart_items (user_id, station_id, station_name, station_type, session_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE added_date = CURRENT_TIMESTAMP
            ");
            
            $stmt->bind_param("issss", $userId, $stationId, $stationName, $stationType, $sessionId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            return false;
        }
    }
    
    // Get cart items
    public function getCartItems($userId, $sessionId = null) {
        try {
            $query = "SELECT * FROM cart_items WHERE ";
            if ($userId) {
                $query .= "user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $userId);
            } else {
                $query .= "session_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s", $sessionId);
            }
            
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting cart items: " . $e->getMessage());
            return [];
        }
    }
    
    // Remove item from cart
    public function removeFromCart($cartId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->bind_param("i", $cartId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error removing from cart: " . $e->getMessage());
            return false;
        }
    }
    
    // Clear cart
    public function clearCart($userId, $sessionId = null) {
        try {
            $query = "DELETE FROM cart_items WHERE ";
            if ($userId) {
                $query .= "user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $userId);
            } else {
                $query .= "session_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s", $sessionId);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }
    
    // Transfer cart from session to user
    public function transferCartToUser($sessionId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE cart_items 
                SET user_id = ?, session_id = NULL 
                WHERE session_id = ?
            ");
            $stmt->bind_param("is", $userId, $sessionId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error transferring cart: " . $e->getMessage());
            return false;
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartAPI = new CartAPI($conn);
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($_GET['action'] ?? '') {
        case 'add':
            $response = $cartAPI->addToCart(
                $data['userId'] ?? null,
                $data['stationId'],
                $data['stationName'],
                $data['stationType'],
                $data['sessionId'] ?? null
            );
            break;
            
        case 'remove':
            $response = $cartAPI->removeFromCart($data['cartId']);
            break;
            
        case 'clear':
            $response = $cartAPI->clearCart(
                $data['userId'] ?? null,
                $data['sessionId'] ?? null
            );
            break;
            
        case 'transfer':
            $response = $cartAPI->transferCartToUser(
                $data['sessionId'],
                $data['userId']
            );
            break;
            
        default:
            http_response_code(400);
            $response = ['error' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cartAPI = new CartAPI($conn);
    $response = $cartAPI->getCartItems(
        $_GET['userId'] ?? null,
        $_GET['sessionId'] ?? null
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
}
?> 