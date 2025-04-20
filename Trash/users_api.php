<?php
require_once 'users_config.php';

// Set headers to allow cross-origin requests and specify content type
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Get request method and handle accordingly
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$endpoint = array_shift($request);

$response = [];
$db = connectDB();

if (!$db) {
    returnResponse(500, "Database connection error", null);
    exit;
}

// Route the request to the appropriate function
try {
    switch ($endpoint) {
        case 'login':
            if ($method === 'POST') {
                login($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'users':
            switch ($method) {
                case 'GET':
                    if (!empty($request[0]) && is_numeric($request[0])) {
                        getUserById($db, $request[0]);
                    } else {
                        getAllUsers($db);
                    }
                    break;
                case 'POST':
                    createUser($db);
                    break;
                case 'PUT':
                    if (!empty($request[0]) && is_numeric($request[0])) {
                        updateUser($db, $request[0]);
                    } else {
                        returnResponse(400, "User ID required", null);
                    }
                    break;
                case 'DELETE':
                    if (!empty($request[0]) && is_numeric($request[0])) {
                        deleteUser($db, $request[0]);
                    } else {
                        returnResponse(400, "User ID required", null);
                    }
                    break;
                default:
                    returnResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'company':
            switch ($method) {
                case 'GET':
                    if (!empty($request[0]) && is_numeric($request[0])) {
                        getCompanyById($db, $request[0]);
                    } else {
                        getAllCompanies($db);
                    }
                    break;
                default:
                    returnResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'individual':
            switch ($method) {
                case 'GET':
                    if (!empty($request[0]) && is_numeric($request[0])) {
                        getIndividualById($db, $request[0]);
                    } else {
                        getAllIndividuals($db);
                    }
                    break;
                default:
                    returnResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'sectors':
            if ($method === 'GET') {
                getSectors($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'sexes':
            if ($method === 'GET') {
                getSexes($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        default:
            returnResponse(404, "Endpoint not found", null);
            break;
    }
} catch (Exception $e) {
    returnResponse(500, "Server error: " . $e->getMessage(), null);
}

// Authentication function
function login($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->username) || !isset($data->password)) {
        returnResponse(400, "Username and password required", null);
        return;
    }
    
    $sql = "SELECT user_id, username, password, user_type FROM users WHERE username = :username AND is_active = TRUE";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        if (password_verify($data->password, $user['password'])) {
            // Update last login timestamp
            $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':user_id', $user['user_id']);
            $updateStmt->execute();
            
            // Generate JWT token
            $token = generateJWT($user);
            
            returnResponse(200, "Login successful", [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'user_type' => $user['user_type'],
                'token' => $token
            ]);
        } else {
            returnResponse(401, "Invalid credentials", null);
        }
    } else {
        returnResponse(401, "Invalid credentials", null);
    }
}

// User CRUD functions
function getAllUsers($db) {
    verifyToken();
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, u.user_type, 
            u.name_on_certificate, u.created_at, u.is_active, s.sex_name
            FROM users u
            LEFT JOIN sexes s ON u.sex_id = s.id
            ORDER BY u.user_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $users = $stmt->fetchAll();
        returnResponse(200, "Users retrieved successfully", $users);
    } else {
        returnResponse(404, "No users found", null);
    }
}

function getUserById($db, $id) {
    verifyToken();
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, u.user_type, 
            u.name_on_certificate, u.created_at, u.is_active, s.sex_name
            FROM users u
            LEFT JOIN sexes s ON u.sex_id = s.id
            WHERE u.user_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Get additional details based on user type
        if ($user['user_type'] === 'company') {
            $companySql = "SELECT c.*, s.sector_name 
                          FROM company_details c
                          JOIN sectors s ON c.sector_id = s.id
                          WHERE c.user_id = :user_id";
            $companyStmt = $db->prepare($companySql);
            $companyStmt->bindParam(':user_id', $id);
            $companyStmt->execute();
            
            if ($companyStmt->rowCount() > 0) {
                $user['company_details'] = $companyStmt->fetch();
            }
        } elseif ($user['user_type'] === 'individual') {
            $individualSql = "SELECT * FROM individual_details WHERE user_id = :user_id";
            $individualStmt = $db->prepare($individualSql);
            $individualStmt->bindParam(':user_id', $id);
            $individualStmt->execute();
            
            if ($individualStmt->rowCount() > 0) {
                $user['individual_details'] = $individualStmt->fetch();
            }
        }
        
        returnResponse(200, "User retrieved successfully", $user);
    } else {
        returnResponse(404, "User not found", null);
    }
}

function createUser($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!validateUserData($data)) {
        returnResponse(400, "Missing required fields", null);
        return;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if username or email already exists
        $checkSql = "SELECT COUNT(*) as count FROM users WHERE username = :username OR email = :email";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':username', $data->username);
        $checkStmt->bindParam(':email', $data->email);
        $checkStmt->execute();
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            returnResponse(409, "Username or email already exists", null);
            $db->rollBack();
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);
        
        // Insert into users table
        $userSql = "INSERT INTO users (username, password, email, contact_number, user_type, 
                    sex_id, name_on_certificate, is_active) 
                    VALUES (:username, :password, :email, :contact_number, :user_type, 
                    :sex_id, :name_on_certificate, :is_active)";
        
        $userStmt = $db->prepare($userSql);
        $userStmt->bindParam(':username', $data->username);
        $userStmt->bindParam(':password', $hashedPassword);
        $userStmt->bindParam(':email', $data->email);
        $userStmt->bindParam(':contact_number', $data->contact_number);
        $userStmt->bindParam(':user_type', $data->user_type);
        $userStmt->bindParam(':sex_id', $data->sex_id);
        $userStmt->bindParam(':name_on_certificate', $data->name_on_certificate);
        $isActive = isset($data->is_active) ? $data->is_active : true;
        $userStmt->bindParam(':is_active', $isActive);
        $userStmt->execute();
        
        $userId = $db->lastInsertId();
        
        // Insert additional details based on user type
        if ($data->user_type === 'company') {
            if (!isset($data->company_name) || !isset($data->company_address) || 
                !isset($data->sector_id) || !isset($data->authorized_representative)) {
                $db->rollBack();
                returnResponse(400, "Missing company details", null);
                return;
            }
            
            $companySql = "INSERT INTO company_details (user_id, company_name, company_address, 
                          sector_id, authorized_representative) 
                          VALUES (:user_id, :company_name, :company_address, 
                          :sector_id, :authorized_representative)";
            
            $companyStmt = $db->prepare($companySql);
            $companyStmt->bindParam(':user_id', $userId);
            $companyStmt->bindParam(':company_name', $data->company_name);
            $companyStmt->bindParam(':company_address', $data->company_address);
            $companyStmt->bindParam(':sector_id', $data->sector_id);
            $companyStmt->bindParam(':authorized_representative', $data->authorized_representative);
            $companyStmt->execute();
            
        } elseif ($data->user_type === 'individual') {
            if (!isset($data->full_name) || !isset($data->address)) {
                $db->rollBack();
                returnResponse(400, "Missing individual details", null);
                return;
            }
            
            $individualSql = "INSERT INTO individual_details (user_id, full_name, address) 
                             VALUES (:user_id, :full_name, :address)";
            
            $individualStmt = $db->prepare($individualSql);
            $individualStmt->bindParam(':user_id', $userId);
            $individualStmt->bindParam(':full_name', $data->full_name);
            $individualStmt->bindParam(':address', $data->address);
            $individualStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(201, "User created successfully", ['user_id' => $userId]);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnResponse(500, "Error creating user: " . $e->getMessage(), null);
    }
}

function updateUser($db, $id) {
    verifyToken();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data)) {
        returnResponse(400, "No data provided", null);
        return;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if user exists
        $checkSql = "SELECT user_type FROM users WHERE user_id = :id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            returnResponse(404, "User not found", null);
            return;
        }
        
        $user = $checkStmt->fetch();
        
        // Update user table
        $updateFields = [];
        $params = [':id' => $id];
        
        if (isset($data->email)) {
            $updateFields[] = "email = :email";
            $params[':email'] = $data->email;
        }
        
        if (isset($data->contact_number)) {
            $updateFields[] = "contact_number = :contact_number";
            $params[':contact_number'] = $data->contact_number;
        }
        
        if (isset($data->sex_id)) {
            $updateFields[] = "sex_id = :sex_id";
            $params[':sex_id'] = $data->sex_id;
        }
        
        if (isset($data->name_on_certificate)) {
            $updateFields[] = "name_on_certificate = :name_on_certificate";
            $params[':name_on_certificate'] = $data->name_on_certificate;
        }
        
        if (isset($data->is_active)) {
            $updateFields[] = "is_active = :is_active";
            $params[':is_active'] = $data->is_active;
        }
        
        if (isset($data->password)) {
            $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);
            $updateFields[] = "password = :password";
            $params[':password'] = $hashedPassword;
        }
        
        if (!empty($updateFields)) {
            $userSql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = :id";
            $userStmt = $db->prepare($userSql);
            
            foreach ($params as $key => $value) {
                $userStmt->bindValue($key, $value);
            }
            
            $userStmt->execute();
        }
        
        // Update additional details based on user type
        if ($user['user_type'] === 'company' && isset($data->company_details)) {
            $companyDetails = $data->company_details;
            $companyUpdateFields = [];
            $companyParams = [':user_id' => $id];
            
            if (isset($companyDetails->company_name)) {
                $companyUpdateFields[] = "company_name = :company_name";
                $companyParams[':company_name'] = $companyDetails->company_name;
            }
            
            if (isset($companyDetails->company_address)) {
                $companyUpdateFields[] = "company_address = :company_address";
                $companyParams[':company_address'] = $companyDetails->company_address;
            }
            
            if (isset($companyDetails->sector_id)) {
                $companyUpdateFields[] = "sector_id = :sector_id";
                $companyParams[':sector_id'] = $companyDetails->sector_id;
            }
            
            if (isset($companyDetails->authorized_representative)) {
                $companyUpdateFields[] = "authorized_representative = :authorized_representative";
                $companyParams[':authorized_representative'] = $companyDetails->authorized_representative;
            }
            
            if (!empty($companyUpdateFields)) {
                $companySql = "UPDATE company_details SET " . implode(", ", $companyUpdateFields) . " WHERE user_id = :user_id";
                $companyStmt = $db->prepare($companySql);
                
                foreach ($companyParams as $key => $value) {
                    $companyStmt->bindValue($key, $value);
                }
                
                $companyStmt->execute();
            }
        } elseif ($user['user_type'] === 'individual' && isset($data->individual_details)) {
            $individualDetails = $data->individual_details;
            $individualUpdateFields = [];
            $individualParams = [':user_id' => $id];
            
            if (isset($individualDetails->full_name)) {
                $individualUpdateFields[] = "full_name = :full_name";
                $individualParams[':full_name'] = $individualDetails->full_name;
            }
            
            if (isset($individualDetails->address)) {
                $individualUpdateFields[] = "address = :address";
                $individualParams[':address'] = $individualDetails->address;
            }
            
            if (!empty($individualUpdateFields)) {
                $individualSql = "UPDATE individual_details SET " . implode(", ", $individualUpdateFields) . " WHERE user_id = :user_id";
                $individualStmt = $db->prepare($individualSql);
                
                foreach ($individualParams as $key => $value) {
                    $individualStmt->bindValue($key, $value);
                }
                
                $individualStmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        returnResponse(200, "User updated successfully", null);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnResponse(500, "Error updating user: " . $e->getMessage(), null);
    }
}

function deleteUser($db, $id) {
    verifyToken('admin'); // Only admins can delete users
    
    // Check if user exists
    $checkSql = "SELECT user_id, user_type FROM users WHERE user_id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        returnResponse(404, "User not found", null);
        return;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Delete user (cascades to details tables via foreign key constraints)
        $userSql = "DELETE FROM users WHERE user_id = :id";
        $userStmt = $db->prepare($userSql);
        $userStmt->bindParam(':id', $id);
        $userStmt->execute();
        
        // Commit transaction
        $db->commit();
        
        returnResponse(200, "User deleted successfully", null);
        
    } catch (Exception $e) {
        $db->rollBack();
        returnResponse(500, "Error deleting user: " . $e->getMessage(), null);
    }
}

// Company functions
function getAllCompanies($db) {
    verifyToken();
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, s.sex_name, 
            u.name_on_certificate, c.company_name, c.company_address, 
            sec.sector_name, c.authorized_representative
            FROM users u
            JOIN company_details c ON u.user_id = c.user_id
            LEFT JOIN sexes s ON u.sex_id = s.id
            JOIN sectors sec ON c.sector_id = sec.id
            WHERE u.user_type = 'company'
            ORDER BY u.user_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $companies = $stmt->fetchAll();
        returnResponse(200, "Companies retrieved successfully", $companies);
    } else {
        returnResponse(404, "No companies found", null);
    }
}

function getCompanyById($db, $id) {
    verifyToken();
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, s.sex_name, 
            u.name_on_certificate, c.company_name, c.company_address, 
            sec.sector_name, c.authorized_representative
            FROM users u
            JOIN company_details c ON u.user_id = c.user_id
            LEFT JOIN sexes s ON u.sex_id = s.id
            JOIN sectors sec ON c.sector_id = sec.id
            WHERE u.user_type = 'company' AND u.user_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $company = $stmt->fetch();
        returnResponse(200, "Company retrieved successfully", $company);
    } else {
        returnResponse(404, "Company not found", null);
    }
}

// Individual functions
function getAllIndividuals($db) {
    verifyToken();
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, s.sex_name, 
            u.name_on_certificate, i.full_name, i.address
            FROM users u
            JOIN individual_details i ON u.user_id = i.user_id
            LEFT JOIN sexes s ON u.sex_id = s.id
            WHERE u.user_type = 'individual'
            ORDER BY u.user_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $individuals = $stmt->fetchAll();
        returnResponse(200, "Individuals retrieved successfully", $individuals);
    } else {
        returnResponse(404, "No individuals found", null);
    }
}

function getIndividualById($db, $id) {
    verifyToken();
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, s.sex_name, 
            u.name_on_certificate, i.full_name, i.address
            FROM users u
            JOIN individual_details i ON u.user_id = i.user_id
            LEFT JOIN sexes s ON u.sex_id = s.id
            WHERE u.user_type = 'individual' AND u.user_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $individual = $stmt->fetch();
        returnResponse(200, "Individual retrieved successfully", $individual);
    } else {
        returnResponse(404, "Individual not found", null);
    }
}

// Get reference data
function getSectors($db) {
    $sql = "SELECT * FROM sectors ORDER BY id";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $sectors = $stmt->fetchAll();
        returnResponse(200, "Sectors retrieved successfully", $sectors);
    } else {
        returnResponse(404, "No sectors found", null);
    }
}

function getSexes($db) {
    $sql = "SELECT * FROM sexes ORDER BY id";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $sexes = $stmt->fetchAll();
        returnResponse(200, "Sexes retrieved successfully", $sexes);
    } else {
        returnResponse(404, "No sexes found", null);
    }
}

// Helper functions
function validateUserData($data) {
    if (!isset($data->username) || !isset($data->password) || !isset($data->email) || 
        !isset($data->contact_number) || !isset($data->user_type)) {
        return false;
    }
    
    return true;
}

function generateJWT($user) {
    $issuedAt = time();
    $expirationTime = $issuedAt + TOKEN_EXPIRY;
    
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'user_type' => $user['user_type']
    ];
    
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode($payload));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    
    return "$header.$payload.$signature";
}

function verifyToken($requiredRole = null) {
    $headers = apache_request_headers();
    
    if (!isset($headers['Authorization'])) {
        returnResponse(401, "Authorization header missing", null);
        exit;
    }
    
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        returnResponse(401, "Invalid token format", null);
        exit;
    }
    
    list($header, $payload, $signature) = $tokenParts;
    
    $verifySignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    
    if ($signature !== $verifySignature) {
        returnResponse(401, "Invalid token signature", null);
        exit;
    }
    
    $payload = json_decode(base64_decode($payload));
    
    if ($payload->exp < time()) {
        returnResponse(401, "Token expired", null);
        exit;
    }
    
    if ($requiredRole && $payload->user_type !== $requiredRole) {
        returnResponse(403, "Insufficient permissions", null);
        exit;
    }
    
    return $payload;
}

function returnResponse($statusCode, $message, $data) {
    http_response_code($statusCode);
    
    $response = [
        'status' => $statusCode,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
} 