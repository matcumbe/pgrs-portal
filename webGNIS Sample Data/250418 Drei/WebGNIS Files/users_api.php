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

// Extract ID if present in the endpoint (e.g., users/123)
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
        case 'login':
            if ($method === 'POST') {
                handleLogin();
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'logout':
            if ($method === 'POST') {
                // Just return success, actual logout is handled client-side
                returnResponse(200, "Logout successful", null);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'users':
            switch ($method) {
                case 'GET':
                    if ($id) {
                        getUserById($db, $id);
                    } else if ($parts[1] === 'me') {
                        getCurrentUser($db);
                    } else {
                        getAllUsers($db);
                    }
                    break;
                case 'POST':
                    createUser($db);
                    break;
                case 'PUT':
                    if ($id) {
                        updateUser($db, $id);
                    } else {
                        returnResponse(400, "User ID required", null);
                    }
                    break;
                case 'DELETE':
                    if ($id) {
                        deleteUser($db, $id);
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
                    if ($id) {
                        getCompanyById($db, $id);
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
                    if ($id) {
                        getIndividualById($db, $id);
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
            
        case 'certificates':
            if ($parts[1] === 'request' && $method === 'POST') {
                // Handle certificate request - simplified mock implementation
                $data = json_decode(file_get_contents("php://input"));
                returnResponse(200, "Certificate request submitted", [
                    'reference_id' => 'CERT-' . date('YmdHis') . '-' . rand(1000, 9999)
                ]);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'change_password':
            if ($method === 'POST') {
                changePassword($db);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case '':
            // No endpoint specified - show API info
            returnResponse(200, "WebGNIS Users API", [
                'version' => '1.0',
                'endpoints' => [
                    'login', 'logout', 'users', 'company', 'individual', 'sectors', 'sexes', 'certificates/request'
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

// Current user function
function getCurrentUser($db) {
    $token = verifyToken(null, false);
    
    if (!$token) {
        returnResponse(401, "Unauthorized", null);
        return;
    }
    
    $userId = $token->user_id;
    
    $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, u.user_type, 
            u.name_on_certificate, u.created_at, u.is_active, s.sex_name
            FROM users u
            LEFT JOIN sexes s ON u.sex_id = s.id
            WHERE u.user_id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $userId);
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
            $companyStmt->bindParam(':user_id', $userId);
            $companyStmt->execute();
            
            if ($companyStmt->rowCount() > 0) {
                $user['company_details'] = $companyStmt->fetch();
            }
        } elseif ($user['user_type'] === 'individual') {
            $individualSql = "SELECT * FROM individual_details WHERE user_id = :user_id";
            $individualStmt = $db->prepare($individualSql);
            $individualStmt->bindParam(':user_id', $userId);
            $individualStmt->execute();
            
            if ($individualStmt->rowCount() > 0) {
                $user['individual_details'] = $individualStmt->fetch();
            }
        }
        
        returnResponse(200, "Current user retrieved successfully", $user);
    } else {
        returnResponse(404, "User not found", null);
    }
}

// Authentication function
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnResponse(405, "Method not allowed", null);
        return;
    }

    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($data['username']) || !isset($data['password'])) {
        returnResponse(400, "Username and password are required", null);
        return;
    }
    
    $username = $data['username'];
    $password = $data['password'];
    
    // Connect to database
    $db = connectDB();
    if (!$db) {
        returnResponse(500, "Database connection failed", null);
        return;
    }
    
    try {
        // Query user
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            returnResponse(401, "Invalid username or password", null);
            return;
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            returnResponse(403, "Account is not active", null);
            return;
        }
        
        // Get additional user details based on user type
        $details = null;
        if ($user['user_type'] === 'company') {
            $detailsStmt = $db->prepare("SELECT c.*, s.sector_name 
                                        FROM company_details c
                                        JOIN sectors s ON c.sector_id = s.id
                                        WHERE c.user_id = :user_id");
            $detailsStmt->bindParam(':user_id', $user['user_id']);
            $detailsStmt->execute();
            $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
        } else if ($user['user_type'] === 'individual') {
            $detailsStmt = $db->prepare("SELECT * FROM individual_details 
                                        WHERE user_id = :user_id");
            $detailsStmt->bindParam(':user_id', $user['user_id']);
            $detailsStmt->execute();
            $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get sex information
        $sexName = null;
        if ($user['sex_id']) {
            $sexStmt = $db->prepare("SELECT sex_name FROM sexes WHERE id = :sex_id");
            $sexStmt->bindParam(':sex_id', $user['sex_id']);
            $sexStmt->execute();
            $sex = $sexStmt->fetch(PDO::FETCH_ASSOC);
            if ($sex) {
                $sexName = $sex['sex_name'];
            }
        }
        
        // Create token
        $token = generateJWT($user);
        
        // Create response with user data
        $userData = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'contact_number' => $user['contact_number'],
            'user_type' => $user['user_type'],
            'name_on_certificate' => $user['name_on_certificate'],
            'sex_name' => $sexName,
            'details' => $details
        ];
        
        returnResponse(200, "Login successful", [
            'token' => $token,
            'user' => $userData
        ]);
        
    } catch (PDOException $e) {
        returnResponse(500, "Database error: " . $e->getMessage(), null);
    }
}

// User CRUD functions
function getAllUsers($db) {
    verifyToken(null, false);
    
    try {
        $sql = "SELECT u.user_id, u.username, u.email, u.contact_number, u.user_type, 
                u.name_on_certificate, u.created_at, u.is_active, s.sex_name
                FROM users u
                LEFT JOIN sexes s ON u.sex_id = s.id
                ORDER BY u.user_id
                LIMIT 50"; // Limit to prevent large responses
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $users = $stmt->fetchAll();
            returnResponse(200, "Users retrieved successfully", $users);
        } else {
            returnResponse(404, "No users found", null);
        }
    } catch (PDOException $e) {
        error_log("getAllUsers error: " . $e->getMessage());
        returnResponse(500, "Database error retrieving users", null);
    }
}

function getUserById($db, $id) {
    verifyToken(null, false);
    
    try {
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
    } catch (PDOException $e) {
        error_log("getUserById error: " . $e->getMessage());
        returnResponse(500, "Database error retrieving user", null);
    }
}

function createUser($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnResponse(405, "Method not allowed", null);
        return;
    }

    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $validationResult = validateUserData($data);
    if ($validationResult !== true) {
        returnResponse(400, $validationResult, null);
        return;
    }
    
    // Check if username or email already exists
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            returnResponse(409, "Username or email already exists", null);
            return;
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create base user
        $userStmt = $db->prepare("
            INSERT INTO users (username, password, email, contact_number, user_type, name_on_certificate, sex_id) 
            VALUES (:username, :password, :email, :contact_number, :user_type, :name_on_certificate, :sex_id)
        ");
        
        $userStmt->bindParam(':username', $data['username']);
        $userStmt->bindParam(':password', $passwordHash);
        $userStmt->bindParam(':email', $data['email']);
        $userStmt->bindParam(':contact_number', $data['contact_number']);
        $userStmt->bindParam(':user_type', $data['user_type']);
        $userStmt->bindParam(':name_on_certificate', $data['name_on_certificate']);
        $userStmt->bindParam(':sex_id', $data['sex_id']);
        $userStmt->execute();
        
        // Get new user ID
        $userId = $db->lastInsertId();
        
        // Create profile based on user type
        if ($data['user_type'] === 'company') {
            $companyStmt = $db->prepare("
                INSERT INTO company_details (
                    user_id, company_name, sector_id, company_address, authorized_representative
                ) VALUES (
                    :user_id, :company_name, :sector_id, :company_address, :authorized_representative
                )
            ");
            
            $companyStmt->bindParam(':user_id', $userId);
            $companyStmt->bindParam(':company_name', $data['company_name']);
            $companyStmt->bindParam(':sector_id', $data['sector_id']);
            $companyStmt->bindParam(':company_address', $data['company_address']);
            $companyStmt->bindParam(':authorized_representative', $data['authorized_representative']);
            $companyStmt->execute();
        } 
        else if ($data['user_type'] === 'individual') {
            $individualStmt = $db->prepare("
                INSERT INTO individual_details (
                    user_id, full_name, address
                ) VALUES (
                    :user_id, :full_name, :address
                )
            ");
            
            $individualStmt->bindParam(':user_id', $userId);
            $individualStmt->bindParam(':full_name', $data['full_name']);
            $individualStmt->bindParam(':address', $data['address']);
            $individualStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        // Get created user
        $userObj = getUserById($db, $userId);
        
        returnResponse(201, "User created successfully", $userObj);
        
    } catch (PDOException $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        returnResponse(500, "Database error: " . $e->getMessage(), null);
    }
}

function updateUser($db, $id) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data)) {
        returnResponse(400, "No data provided", null);
        return;
    }
    
    // Mock successful update
    returnResponse(200, "User updated successfully", null);
}

function deleteUser($db, $id) {
    // Mock successful deletion
    returnResponse(200, "User deleted successfully", null);
}

// Company functions
function getAllCompanies($db) {
    // Mock company data
    $companies = [
        [
            'user_id' => 2001,
            'username' => 'acme_corp',
            'email' => 'info@acme.com',
            'contact_number' => '555-123-4567',
            'sex_name' => null,
            'name_on_certificate' => 'ACME Corporation',
            'company_name' => 'ACME Corporation',
            'company_address' => '123 Business Ave',
            'sector_name' => 'Private (Company)',
            'authorized_representative' => 'John Manager'
        ],
        [
            'user_id' => 2002,
            'username' => 'global_inc',
            'email' => 'contact@global.com',
            'contact_number' => '555-987-6543',
            'sex_name' => null,
            'name_on_certificate' => 'Global Inc.',
            'company_name' => 'Global Inc.',
            'company_address' => '456 Corporate Blvd',
            'sector_name' => 'Private (Company)',
            'authorized_representative' => 'Jane Executive'
        ]
    ];
    
    returnResponse(200, "Companies retrieved successfully", $companies);
}

function getCompanyById($db, $id) {
    // Mock company data
    $company = [
        'user_id' => $id,
        'username' => 'company_' . $id,
        'email' => 'company' . $id . '@example.com',
        'contact_number' => '555-' . rand(100, 999) . '-' . rand(1000, 9999),
        'sex_name' => null,
        'name_on_certificate' => 'Company ' . $id,
        'company_name' => 'Company ' . $id,
        'company_address' => rand(100, 999) . ' Business Ave',
        'sector_name' => 'Private (Company)',
        'authorized_representative' => 'Representative ' . $id
    ];
    
    returnResponse(200, "Company retrieved successfully", $company);
}

// Individual functions
function getAllIndividuals($db) {
    // Mock individual data
    $individuals = [
        [
            'user_id' => 3001,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'contact_number' => '123-456-7890',
            'sex_name' => 'Male',
            'name_on_certificate' => 'John Doe',
            'full_name' => 'John Doe',
            'address' => '123 Main St'
        ],
        [
            'user_id' => 3002,
            'username' => 'jane_smith',
            'email' => 'jane@example.com',
            'contact_number' => '123-456-7891',
            'sex_name' => 'Female',
            'name_on_certificate' => 'Jane Smith',
            'full_name' => 'Jane Smith',
            'address' => '456 Oak Ave'
        ]
    ];
    
    returnResponse(200, "Individuals retrieved successfully", $individuals);
}

function getIndividualById($db, $id) {
    // Mock individual data
    $individual = [
        'user_id' => $id,
        'username' => 'individual_' . $id,
        'email' => 'individual' . $id . '@example.com',
        'contact_number' => '123-' . rand(100, 999) . '-' . rand(1000, 9999),
        'sex_name' => (rand(0, 1) == 0) ? 'Male' : 'Female',
        'name_on_certificate' => 'Individual ' . $id,
        'full_name' => 'Full Name ' . $id,
        'address' => rand(100, 999) . ' Street Name'
    ];
    
    returnResponse(200, "Individual retrieved successfully", $individual);
}

// Get reference data
function getSectors($db) {
    try {
        $sql = "SELECT id, sector_name FROM sectors ORDER BY id";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $sectors = $stmt->fetchAll();
            returnResponse(200, "Sectors retrieved successfully", $sectors);
        } else {
            // Fall back to hardcoded values if no database records
            $sectors = [
                ['id' => 1, 'sector_name' => 'National Government (ENR)'],
                ['id' => 2, 'sector_name' => 'National Government (Others)'],
                ['id' => 3, 'sector_name' => 'Local Government'],
                ['id' => 4, 'sector_name' => 'Government Controlled Corp.'],
                ['id' => 5, 'sector_name' => 'Private (Company)'],
                ['id' => 6, 'sector_name' => 'Private (Individual)'],
                ['id' => 7, 'sector_name' => 'Foreign'],
                ['id' => 8, 'sector_name' => 'N.G.O.'],
                ['id' => 9, 'sector_name' => 'Academia'],
                ['id' => 10, 'sector_name' => 'Legislative'],
                ['id' => 11, 'sector_name' => 'Judiciary']
            ];
            returnResponse(200, "Sectors retrieved from fallback", $sectors);
        }
    } catch (PDOException $e) {
        error_log("getSectors error: " . $e->getMessage());
        // Fall back to hardcoded values if database error
        $sectors = [
            ['id' => 1, 'sector_name' => 'National Government (ENR)'],
            ['id' => 2, 'sector_name' => 'National Government (Others)'],
            ['id' => 3, 'sector_name' => 'Local Government'],
            ['id' => 4, 'sector_name' => 'Government Controlled Corp.'],
            ['id' => 5, 'sector_name' => 'Private (Company)'],
            ['id' => 6, 'sector_name' => 'Private (Individual)'],
            ['id' => 7, 'sector_name' => 'Foreign'],
            ['id' => 8, 'sector_name' => 'N.G.O.'],
            ['id' => 9, 'sector_name' => 'Academia'],
            ['id' => 10, 'sector_name' => 'Legislative'],
            ['id' => 11, 'sector_name' => 'Judiciary']
        ];
        returnResponse(200, "Sectors retrieved from fallback", $sectors);
    }
}

function getSexes($db) {
    try {
        $sql = "SELECT id, sex_name FROM sexes ORDER BY id";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $sexes = $stmt->fetchAll();
            returnResponse(200, "Sexes retrieved successfully", $sexes);
        } else {
            // Fall back to hardcoded values if no database records
            $sexes = [
                ['id' => 1, 'sex_name' => 'Male'],
                ['id' => 2, 'sex_name' => 'Female'],
                ['id' => 3, 'sex_name' => 'Prefer not to say']
            ];
            returnResponse(200, "Sexes retrieved from fallback", $sexes);
        }
    } catch (PDOException $e) {
        error_log("getSexes error: " . $e->getMessage());
        // Fall back to hardcoded values if database error
        $sexes = [
            ['id' => 1, 'sex_name' => 'Male'],
            ['id' => 2, 'sex_name' => 'Female'],
            ['id' => 3, 'sex_name' => 'Prefer not to say']
        ];
        returnResponse(200, "Sexes retrieved from fallback", $sexes);
    }
}

// Helper functions
function validateUserData($data) {
    // Check required fields for all users
    if (!isset($data['username']) || empty($data['username'])) {
        return "Username is required";
    }
    
    if (!isset($data['password']) || strlen($data['password']) < 6) {
        return "Password must be at least 6 characters";
    }
    
    if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return "Valid email is required";
    }
    
    if (!isset($data['contact_number']) || empty($data['contact_number'])) {
        return "Contact number is required";
    }
    
    if (!isset($data['user_type']) || !in_array($data['user_type'], ['company', 'individual'])) {
        return "Valid user type (company or individual) is required";
    }
    
    if (!isset($data['name_on_certificate']) || empty($data['name_on_certificate'])) {
        return "Name on certificate is required";
    }
    
    if (!isset($data['sex_id']) || !is_numeric($data['sex_id'])) {
        return "Valid sex ID is required";
    }
    
    // User type specific validation
    if ($data['user_type'] === 'company') {
        if (!isset($data['company_name']) || empty($data['company_name'])) {
            return "Company name is required";
        }
        
        if (!isset($data['sector_id']) || !is_numeric($data['sector_id'])) {
            return "Valid sector ID is required";
        }
        
        if (!isset($data['company_address']) || empty($data['company_address'])) {
            return "Company address is required";
        }
    } 
    else if ($data['user_type'] === 'individual') {
        if (!isset($data['full_name']) || empty($data['full_name'])) {
            return "Full name is required";
        }
        
        if (!isset($data['address']) || empty($data['address'])) {
            return "Address is required";
        }
        
        if (isset($data['birth_date'])) {
            // Validate date format (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birth_date'])) {
                return "Birth date must be in YYYY-MM-DD format";
            }
        }
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

function verifyToken($requiredRole = null, $exitOnFail = true) {
    $headers = getallheaders();
    
    if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
        if ($exitOnFail) {
            returnResponse(401, "Authorization header missing", null);
            exit;
        } else {
            return false;
        }
    }
    
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    $token = str_replace('Bearer ', '', $authHeader);
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        if ($exitOnFail) {
            returnResponse(401, "Invalid token format", null);
            exit;
        } else {
            return false;
        }
    }
    
    list($header, $payload, $signature) = $tokenParts;
    
    $verifySignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    
    if ($signature !== $verifySignature) {
        if ($exitOnFail) {
            returnResponse(401, "Invalid token signature", null);
            exit;
        } else {
            return false;
        }
    }
    
    $payload = json_decode(base64_decode($payload));
    
    if ($payload->exp < time()) {
        if ($exitOnFail) {
            returnResponse(401, "Token expired", null);
            exit;
        } else {
            return false;
        }
    }
    
    if ($requiredRole && $payload->user_type !== $requiredRole) {
        if ($exitOnFail) {
            returnResponse(403, "Insufficient permissions", null);
            exit;
        } else {
            return false;
        }
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

// Add the changePassword function after other existing functions
function changePassword($db) {
    // Verify user is authenticated first
    $token = verifyToken(null, false);
    
    if (!$token) {
        returnResponse(401, "Unauthorized", null);
        return;
    }
    
    // Get request body data
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate input
    if (!isset($data->current_password) || !isset($data->new_password)) {
        returnResponse(400, "Current password and new password are required", null);
        return;
    }
    
    if (strlen($data->new_password) < 6) {
        returnResponse(400, "New password must be at least 6 characters", null);
        return;
    }
    
    // Get user ID from token or request
    $userId = $token->user_id;
    if (isset($data->user_id) && $data->user_id != $userId) {
        // If user is trying to change someone else's password, check if they're an admin
        if ($token->user_type !== 'admin') {
            returnResponse(403, "You are not authorized to change another user's password", null);
            return;
        }
        $userId = $data->user_id;
    }
    
    try {
        // Get current user password
        $sql = "SELECT password FROM users WHERE user_id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            returnResponse(404, "User not found", null);
            return;
        }
        
        $user = $stmt->fetch();
        
        // Mock verification for demo purposes
        // In a real environment, you would use: password_verify($data->current_password, $user['password'])
        // For this implementation, we're just checking if a current password was provided
        // if (isset($data->current_password) && !empty($data->current_password)) {
        if (password_verify($data->current_password, $user['password'])) {
            // Hash the new password
            // Again, in a real environment you would use: $newPasswordHash = password_hash($data->new_password, PASSWORD_DEFAULT);
            // $newPasswordHash = $data->new_password;
            $newPasswordHash = password_hash($data->new_password, PASSWORD_DEFAULT);
            
            // Update the user's password
            $updateSql = "UPDATE users SET password = :password WHERE user_id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':password', $newPasswordHash);
            $updateStmt->bindParam(':id', $userId);
            if ($updateStmt->execute()) {
                returnResponse(200, "Password updated successfully", null);
            } else {
                returnResponse(500, "Failed to update password", null);
            }
        } else {
            returnResponse(400, "Current password is incorrect", null);
        }
    } catch (PDOException $e) {
        error_log("Change password error: " . $e->getMessage());
        returnResponse(500, "Failed to update password", null);
    }
} 