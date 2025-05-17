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

// Extract ID if present in the endpoint (e.g., cart/123)
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
        case 'add':
            if ($method === 'POST') {
                addToCart($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'remove':
            if ($method === 'POST' || $method === 'DELETE') {
                removeFromCart($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'list':
            if ($method === 'GET') {
                getCartItems($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'clear':
            if ($method === 'POST' || $method === 'DELETE') {
                clearCart($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'count':
            if ($method === 'GET') {
                getCartCount($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'sync':
            if ($method === 'POST') {
                syncCart($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case '':
            // No endpoint specified - show API info
            returnResponse(200, "WebGNIS Cart API", [
                'version' => '1.0',
                'endpoints' => [
                    'add', 'remove', 'list', 'clear', 'count', 'sync'
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
 * Add an item to cart
 */
function addToCart($db) {
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    // Debug log
    error_log("addToCart received data: " . print_r($data, true));
    
    if (!isset($data->station_id) || !isset($data->station_type)) {
        returnResponse(400, "Missing required fields: station_id, station_type", null);
        return;
    }
    
    // Use station ID as name if not provided
    $stationName = isset($data->station_name) && !empty($data->station_name) ? $data->station_name : $data->station_id;
    
    // Get user information if logged in
    $token = verifyToken(null, false);
    $userId = $token ? $token->user_id : null;
    
    // Get session ID for tracking
    $sessionId = isset($data->session_id) ? $data->session_id : session_id();
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // First, get or create a cart for this user/session
        $cartId = getOrCreateCart($db, $userId, $sessionId);
        
        // Check if item already exists in cart
        $sql = "SELECT item_id FROM cart_items WHERE 
                cart_id = :cart_id 
                AND station_id = :station_id 
                AND station_type = :station_type";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':cart_id', $cartId);
        $stmt->bindParam(':station_id', $data->station_id);
        $stmt->bindParam(':station_type', $data->station_type);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $db->commit();
            returnResponse(200, "Item already in cart", null);
            return;
        }
        
        // Add new item to cart
        $sql = "INSERT INTO cart_items (cart_id, station_id, station_name, station_type) 
                VALUES (:cart_id, :station_id, :station_name, :station_type)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':cart_id', $cartId);
        $stmt->bindParam(':station_id', $data->station_id);
        $stmt->bindParam(':station_name', $stationName);
        $stmt->bindParam(':station_type', $data->station_type);
        
        if ($stmt->execute()) {
            // Update cart's updated_at timestamp
            $sql = "UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE cart_id = :cart_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            $db->commit();
            returnResponse(201, "Item added to cart", ["item_id" => $db->lastInsertId()]);
        } else {
            $db->rollBack();
            returnResponse(500, "Failed to add item to cart", null);
        }
    } catch (Exception $e) {
        $db->rollBack();
        returnResponse(500, "Error adding item to cart: " . $e->getMessage(), null);
    }
}

/**
 * Helper function to get or create a cart for a user/session
 */
function getOrCreateCart($db, $userId, $sessionId) {
    try {
        error_log("Getting cart for user_id: " . ($userId ? $userId : "NULL") . ", session_id: $sessionId");
        
        // If user is logged in, try to find their cart first
        if ($userId) {
            // Look for cart by user ID
            $sql = "SELECT cart_id FROM carts WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                error_log("Found existing user cart ID: " . $row['cart_id']);
                
                // Update the session ID to current one if different
                $sql = "UPDATE carts SET session_id = :session_id, updated_at = CURRENT_TIMESTAMP WHERE cart_id = :cart_id";
                $updateStmt = $db->prepare($sql);
                $updateStmt->bindParam(':session_id', $sessionId);
                $updateStmt->bindParam(':cart_id', $row['cart_id']);
                $updateStmt->execute();
                
                return $row['cart_id'];
            }
            
            // No cart found for user, create one
            error_log("Creating new cart for user_id: $userId");
            $sql = "INSERT INTO carts (user_id, session_id) VALUES (:user_id, :session_id)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':session_id', $sessionId);
            
            if ($stmt->execute()) {
                $cartId = $db->lastInsertId();
                error_log("Created new user cart ID: " . $cartId);
                return $cartId;
            } else {
                throw new Exception("Failed to create a new cart: " . implode(", ", $stmt->errorInfo()));
            }
        } else {
            // For guest users - look up by session ID only
            $sql = "SELECT cart_id FROM carts WHERE session_id = :session_id AND user_id IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                error_log("Found existing guest cart ID: " . $row['cart_id']);
                return $row['cart_id'];
            }
            
            // Create a new cart for guest
            error_log("Creating new guest cart for session: $sessionId");
            $sql = "INSERT INTO carts (user_id, session_id) VALUES (NULL, :session_id)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId);
            
            if ($stmt->execute()) {
                $cartId = $db->lastInsertId();
                error_log("Created new guest cart ID: " . $cartId);
                return $cartId;
            } else {
                throw new Exception("Failed to create a new guest cart: " . implode(", ", $stmt->errorInfo()));
            }
        }
    } catch (Exception $e) {
        error_log("Error in getOrCreateCart: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Remove an item from cart
 */
function removeFromCart($db) {
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->cart_id) && !isset($data->item_id) && (!isset($data->station_id) || !isset($data->station_type))) {
        returnResponse(400, "Missing required fields: item_id or (station_id and station_type)", null);
        return;
    }
    
    // Get user information if logged in
    $token = verifyToken(null, false);
    $userId = $token ? $token->user_id : null;
    
    // If not logged in, use session ID for tracking
    $sessionId = $userId ? session_id() : (isset($data->session_id) ? $data->session_id : session_id());
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Get the cart ID
        $cartId = null;
        if (isset($data->cart_id)) {
            // Use provided cart_id
            $cartId = $data->cart_id;
        } else {
            // Find the cart for this user/session
            $sql = "SELECT cart_id FROM carts WHERE ";
            $sql .= $userId ? "user_id = :user_id" : "session_id = :session_id AND user_id IS NULL";
            
            $stmt = $db->prepare($sql);
            if ($userId) {
                $stmt->bindParam(':user_id', $userId);
            } else {
                $stmt->bindParam(':session_id', $sessionId);
            }
            $stmt->execute();
            
            if ($row = $stmt->fetch()) {
                $cartId = $row['cart_id'];
            } else {
                $db->commit();
                returnResponse(404, "Cart not found", null);
                return;
            }
        }
        
        // Remove item from cart
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND ";
        
        if (isset($data->item_id)) {
            $sql .= "item_id = :item_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->bindParam(':item_id', $data->item_id);
        } else {
            $sql .= "station_id = :station_id AND station_type = :station_type";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->bindParam(':station_id', $data->station_id);
            $stmt->bindParam(':station_type', $data->station_type);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update cart's updated_at timestamp
            $sql = "UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE cart_id = :cart_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            $db->commit();
            returnResponse(200, "Item removed from cart", null);
        } else {
            $db->commit();
            returnResponse(404, "Item not found in cart", null);
        }
    } catch (Exception $e) {
        $db->rollBack();
        returnResponse(500, "Error removing item from cart: " . $e->getMessage(), null);
    }
}

/**
 * Get all items in cart
 */
function getCartItems($db) {
    // Get user information if logged in
    $token = verifyToken(null, false);
    $userId = $token ? $token->user_id : null;
    
    // If not logged in, use session ID for tracking
    $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : session_id();
    
    try {
        // Find the cart for this user/session
        $sql = "SELECT cart_id FROM carts WHERE ";
        $sql .= $userId ? "user_id = :user_id" : "session_id = :session_id AND user_id IS NULL";
        
        $stmt = $db->prepare($sql);
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        } else {
            $stmt->bindParam(':session_id', $sessionId);
        }
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cartId = $row['cart_id'];
            
            // Get cart items
            $sql = "SELECT ci.item_id, ci.station_id, ci.station_name, ci.station_type, ci.added_at 
                    FROM cart_items ci 
                    WHERE ci.cart_id = :cart_id 
                    ORDER BY ci.added_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process items to ensure all have station_name
            foreach ($items as &$item) {
                // If station_name is NULL or empty, use station_id as fallback
                if (empty($item['station_name'])) {
                    $item['station_name'] = $item['station_id'];
                }
            }
            
            returnResponse(200, "Cart items retrieved", $items);
        } else {
            // No cart found, return empty array
            returnResponse(200, "Cart items retrieved", []);
        }
    } catch (Exception $e) {
        returnResponse(500, "Error retrieving cart items: " . $e->getMessage(), null);
    }
}

/**
 * Clear all items in cart
 */
function clearCart($db) {
    // Get user information if logged in
    $token = verifyToken(null, false);
    $userId = $token ? $token->user_id : null;
    
    // If not logged in, use session ID for tracking
    $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : session_id();
    
    try {
        // Find the cart for this user/session
        $sql = "SELECT cart_id FROM carts WHERE ";
        $sql .= $userId ? "user_id = :user_id" : "session_id = :session_id AND user_id IS NULL";
        
        $stmt = $db->prepare($sql);
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        } else {
            $stmt->bindParam(':session_id', $sessionId);
        }
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $cartId = $row['cart_id'];
            
            // Begin transaction
            $db->beginTransaction();
            
            // Delete all items from cart
            $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            // Update cart's updated_at timestamp
            $sql = "UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE cart_id = :cart_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            $db->commit();
            returnResponse(200, "Cart cleared", null);
        } else {
            returnResponse(200, "No cart to clear", null);
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        returnResponse(500, "Error clearing cart: " . $e->getMessage(), null);
    }
}

/**
 * Get cart count
 */
function getCartCount($db) {
    // Get user information if logged in
    $token = verifyToken(null, false);
    $userId = $token ? $token->user_id : null;
    
    // If not logged in, use session ID for tracking
    $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : session_id();
    
    try {
        // Find the cart for this user/session
        $sql = "SELECT cart_id FROM carts WHERE ";
        $sql .= $userId ? "user_id = :user_id" : "session_id = :session_id AND user_id IS NULL";
        
        $stmt = $db->prepare($sql);
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        } else {
            $stmt->bindParam(':session_id', $sessionId);
        }
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $cartId = $row['cart_id'];
            
            // Get cart count
            $sql = "SELECT COUNT(*) as count FROM cart_items WHERE cart_id = :cart_id";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $cartId);
            $stmt->execute();
            
            $result = $stmt->fetch();
            returnResponse(200, "Cart count retrieved", ["count" => (int)$result['count']]);
        } else {
            // No cart found, return zero count
            returnResponse(200, "Cart count retrieved", ["count" => 0]);
        }
    } catch (Exception $e) {
        returnResponse(500, "Error retrieving cart count: " . $e->getMessage(), null);
    }
}

/**
 * Sync cart items from guest session to user account after login
 */
function syncCart($db) {
    // Try to verify the token
    try {
        $token = verifyToken(null, false); // Don't exit on failure
        
        // Check if we have a valid token with user_id
        if (!$token || !isset($token->user_id)) {
            error_log("Sync cart failed: No valid token with user_id");
            returnResponse(401, "Authentication required for syncing cart", null);
            return;
        }
        
        $userId = $token->user_id;
        error_log("Starting cart sync for user_id: $userId");
    } catch (Exception $e) {
        error_log("Token verification failed in syncCart: " . $e->getMessage());
        returnResponse(401, "Authentication failed: " . $e->getMessage(), null);
        return;
    }
    
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->session_id)) {
        error_log("Missing session_id in sync cart request");
        returnResponse(400, "Missing required field: session_id", null);
        return;
    }
    
    error_log("Syncing from guest session: " . $data->session_id . " to user: $userId");
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Get the guest cart ID
        $sql = "SELECT cart_id FROM carts WHERE session_id = :session_id AND user_id IS NULL";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':session_id', $data->session_id);
        $stmt->execute();
        $guestCartRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$guestCartRow) {
            error_log("No guest cart found for session_id: " . $data->session_id);
            $db->commit();
            returnResponse(200, "No guest cart found to sync", ["items_added" => 0]);
            return;
        }
        $guestCartId = $guestCartRow['cart_id'];
        error_log("Found guest cart ID: $guestCartId");
        
        // Get or create a user cart
        $sql = "SELECT cart_id FROM carts WHERE user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $userCartRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userCartRow) {
            $userCartId = $userCartRow['cart_id'];
            error_log("Found existing user cart ID: $userCartId");
        } else {
            // Create a new cart for the user
            $sql = "INSERT INTO carts (user_id, session_id) VALUES (:user_id, :session_id)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $newSessionId = session_id(); // Use current PHP session
            $stmt->bindParam(':session_id', $newSessionId);
            $stmt->execute();
            $userCartId = $db->lastInsertId();
            error_log("Created new user cart ID: $userCartId");
        }
        
        // First, get all items from guest cart that don't exist in user cart
        $sql = "SELECT g.station_id, g.station_name, g.station_type FROM cart_items g 
                WHERE g.cart_id = :guest_cart_id 
                AND NOT EXISTS (
                    SELECT 1 FROM cart_items u 
                    WHERE u.cart_id = :user_cart_id 
                    AND u.station_id = g.station_id 
                    AND u.station_type = g.station_type
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':guest_cart_id', $guestCartId);
        $stmt->bindParam(':user_cart_id', $userCartId);
        $stmt->execute();
        
        $itemsToAdd = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Found " . count($itemsToAdd) . " items to sync from guest cart to user cart");
        
        // Add session items to user cart
        if (count($itemsToAdd) > 0) {
            $insertSql = "INSERT INTO cart_items (cart_id, station_id, station_name, station_type) 
                        VALUES (:cart_id, :station_id, :station_name, :station_type)";
            $insertStmt = $db->prepare($insertSql);
            
            foreach ($itemsToAdd as $item) {
                // Use station_id as station_name if it's empty or null
                $stationName = !empty($item['station_name']) ? $item['station_name'] : $item['station_id'];
                
                $insertStmt->bindParam(':cart_id', $userCartId);
                $insertStmt->bindParam(':station_id', $item['station_id']);
                $insertStmt->bindParam(':station_name', $stationName);
                $insertStmt->bindParam(':station_type', $item['station_type']);
                $insertStmt->execute();
                error_log("Added item: " . $item['station_id'] . " (type: " . $item['station_type'] . ") to user cart");
            }
            
            // Update user cart's updated_at timestamp
            $sql = "UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE cart_id = :cart_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':cart_id', $userCartId);
            $stmt->execute();
        }
        
        // Delete guest cart items
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':cart_id', $guestCartId);
        $stmt->execute();
        error_log("Deleted items from guest cart ID: $guestCartId");
        
        // Delete guest cart
        $sql = "DELETE FROM carts WHERE cart_id = :cart_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':cart_id', $guestCartId);
        $stmt->execute();
        error_log("Deleted guest cart ID: $guestCartId");
        
        // Commit transaction
        $db->commit();
        
        returnResponse(200, "Cart synchronized successfully", ["items_added" => count($itemsToAdd)]);
    } catch (Exception $e) {
        // Roll back transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error syncing cart: " . $e->getMessage());
        returnResponse(500, "Failed to synchronize cart: " . $e->getMessage(), null);
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
    // Get authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    // Check if auth header exists and starts with Bearer
    if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
        error_log("No authentication token provided in request");
        if ($exitOnFail) {
            returnResponse(401, "No authentication token provided", null);
        }
        return null;
    }
    
    // Extract token
    $jwt = substr($authHeader, 7);
    error_log("Verifying token: " . substr($jwt, 0, 10) . "...");
    
    try {
        // Simple decode for debugging
        list($headerB64, $payloadB64, $signatureB64) = explode('.', $jwt);
        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')));
        
        // If we have a valid payload with user_id, return it without complex verification
        if (!$payload) {
            error_log("Invalid token payload: could not decode JSON");
            throw new Exception('Invalid token payload');
        }
        
        // Check token expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            error_log("Token expired: expiry = " . date('Y-m-d H:i:s', $payload->exp) . ", current = " . date('Y-m-d H:i:s'));
            throw new Exception('Token expired');
        }
        
        // Check required role if specified
        if ($requiredRole && (!isset($payload->user_type) || $payload->user_type !== $requiredRole)) {
            error_log("Access denied: user has role " . (isset($payload->user_type) ? $payload->user_type : "none") . " but required role is $requiredRole");
            if ($exitOnFail) {
                returnResponse(403, "Access denied: Insufficient permissions", null);
            }
            return null;
        }
        
        // Log user info
        if (isset($payload->user_id)) {
            error_log("Token belongs to user_id: " . $payload->user_id);
        } else {
            error_log("Warning: Token has no user_id");
        }
        
        return $payload;
    } catch (Exception $e) {
        error_log("Authentication failed: " . $e->getMessage());
        if ($exitOnFail) {
            returnResponse(401, "Authentication failed: " . $e->getMessage(), null);
        }
        return null;
    }
} 