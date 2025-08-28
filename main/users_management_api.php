<?php
header('Content-Type: application/json');
require_once 'config.php';

// --- Access Control: Only allow admins (not moderators) ---
session_start();
function isAdmin() {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') return true;
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        $token = str_replace('Bearer ', '', $authHeader);
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]));
            if ($payload && isset($payload->user_type) && $payload->user_type === 'admin') return true;
        }
    }
    return false;
}
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied: Admins only (Moderators cannot access user management)']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_users');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// --- List users (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['list'])) {
    $search = $conn->real_escape_string($_GET['search'] ?? '');
    $user_type = $conn->real_escape_string($_GET['user_type'] ?? '');
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = max(1, min(50, intval($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    $where = ["is_active = 1"]; // only show active users
    if ($search) {
        $where[] = "(username LIKE '%$search%' OR email LIKE '%$search%' OR name_on_certificate LIKE '%$search%')";
    }
    if ($user_type) {
        $where[] = "user_type = '$user_type'";
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT user_id, username, email, contact_number, user_type, sex_id, name_on_certificate, created_at, updated_at FROM users $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    $result = $conn->query($sql);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $countRes = $conn->query("SELECT COUNT(*) as cnt FROM users $whereSql");
    $total = $countRes->fetch_assoc()['cnt'];
    echo json_encode(['users' => $users, 'total' => intval($total), 'page' => $page, 'per_page' => $perPage]);
    exit;
}

// --- User actions (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $userId = $input['user_id'] ?? null;
    
    // --- Add user ---
    if ($action === 'add') {
        $username = $conn->real_escape_string($input['username'] ?? '');
        $email = $conn->real_escape_string($input['email'] ?? '');
        $contact_number = $conn->real_escape_string($input['contact_number'] ?? '');
        $user_type = $conn->real_escape_string($input['user_type'] ?? 'individual');
        $sex_id = intval($input['sex_id'] ?? 0) ?: null;
        $name_on_certificate = $conn->real_escape_string($input['name_on_certificate'] ?? '');
        $password = password_hash($input['password'] ?? '', PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, contact_number, user_type, sex_id, name_on_certificate, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('sssssisss', $username, $password, $email, $contact_number, $user_type, $sex_id, $name_on_certificate, $now, $now);
        $ok = $stmt->execute();
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();
        echo json_encode(['success' => $ok]);
        exit;
    }
    // --- Edit user ---
    if ($action === 'edit' && $userId) {
        $username = $conn->real_escape_string($input['username'] ?? '');
        $email = $conn->real_escape_string($input['email'] ?? '');
        $contact_number = $conn->real_escape_string($input['contact_number'] ?? '');
        $user_type = $conn->real_escape_string($input['user_type'] ?? '');
        $sex_id = intval($input['sex_id'] ?? 0) ?: null;
        $name_on_certificate = $conn->real_escape_string($input['name_on_certificate'] ?? '');
        $set = [];
        // Allow clearing/changing values; include fields when provided
        if (isset($input['username'])) $set[] = "username='$username'";
        if (isset($input['email'])) $set[] = "email='$email'";
        if (isset($input['contact_number'])) $set[] = "contact_number='$contact_number'";
        if (isset($input['user_type'])) $set[] = "user_type='$user_type'";
        if (array_key_exists('sex_id', $input)) $set[] = (is_null($sex_id) ? "sex_id=NULL" : "sex_id=$sex_id");
        if (isset($input['name_on_certificate'])) $set[] = "name_on_certificate='$name_on_certificate'";
        if ($set) {
            $sql = "UPDATE users SET ".implode(',', $set).", updated_at=NOW() WHERE user_id=$userId";
            $ok = $conn->query($sql);
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['error' => 'Update failed: ' . $conn->error]);
            } else {
                echo json_encode(['success' => true]);
            }
            exit;
        }
    }
    // --- Reset password ---
    if ($action === 'reset_password' && $userId) {
        $newPass = $input['new_password'] ?? bin2hex(random_bytes(4));
        $password = password_hash($newPass, PASSWORD_DEFAULT);
        $ok = $conn->query("UPDATE users SET password='$password', updated_at=NOW() WHERE user_id=$userId");
        echo json_encode(['success' => $ok, 'new_password' => $newPass]);
        exit;
    }
    // --- Delete (soft delete) ---
    if ($action === 'delete' && $userId) {
        $ok = $conn->query("UPDATE users SET is_active=0, updated_at=NOW() WHERE user_id=$userId");
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Delete failed: ' . $conn->error]);
        } else {
            echo json_encode(['success' => true]);
        }
        exit;
    }
    echo json_encode(['error' => 'Invalid action or missing parameters']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']); 