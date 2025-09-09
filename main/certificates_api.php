<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// certificates_api.php

require_once 'users_config.php';
require_once 'certificate_generator.php';
require_once 'gcs_helper.php'; // Include the GCS helper
// require_once 'jwt_utils.php'; // REMOVED: jwt_utils.php is missing, JWT validation will be handled locally

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// --- Debug helper ---
if (!function_exists('cert_api_log')) {
    function cert_api_log($message, $context = null) {
        try {
            if ($context !== null) {
                error_log('[CERT_API] ' . $message . ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                error_log('[CERT_API] ' . $message);
            }
        } catch (Throwable $e) {
            error_log('[CERT_API] log_error: ' . $e->getMessage());
        }
    }
}

// Handle preflight CORS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function returnError($message, $statusCode = 400) {
    cert_api_log('returnError', ['status' => $statusCode, 'message' => $message]);
    http_response_code($statusCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Copied and adapted verifyToken from transactions_api.php
function verifyToken($requiredRole = null, $exitOnFail = true) {
    $headers = getallheaders();
    cert_api_log('verifyToken:headers_present', ['keys' => array_keys($headers)]);
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');

    if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
        if ($exitOnFail) {
            returnError("Authentication token not provided or invalid format.", 401);
        }
        return null;
    }

    $jwt = substr($authHeader, 7);
    cert_api_log('verifyToken:jwt_prefix', ['prefix' => substr($jwt, 0, 12) . '...']);
    if (empty($jwt)) {
        if ($exitOnFail) {
            returnError("Authentication token not provided.", 401);
        }
        return null;
    }

    try {
        // JWT_SECRET and TOKEN_EXPIRY should be defined (e.g., in users_config.php or globally)
        if (!defined('JWT_SECRET')) {
            // This should not happen if users_config.php is loaded correctly
            error_log("[ERROR] JWT_SECRET is not defined in certificates_api.php context.");
            if ($exitOnFail) returnError("Server configuration error regarding JWT secret.", 500);
            return null;
        }

        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) {
            throw new Exception('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureProvided) = $tokenParts;

        // Build signature
        $dataToSign = $headerEncoded . "." . $payloadEncoded;
        $expectedSignature = base64_encode(hash_hmac('sha256', $dataToSign, JWT_SECRET, true));

        if (!hash_equals($expectedSignature, $signatureProvided)) {
            throw new Exception('Invalid token signature');
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadEncoded)));
        if (!$payload) {
            throw new Exception('Invalid token payload');
        }

        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token expired');
        }

        if ($requiredRole && (!isset($payload->user_type) || $payload->user_type !== $requiredRole)) {
             error_log("[AUTH_FAIL] Required role: {$requiredRole}, User type: {".($payload->user_type ?? 'N/A')."}");
            if ($exitOnFail) {
                returnError("Access denied. User does not have the required role: {$requiredRole}", 403);
            }
            return null;
        }
        
        // Ensure user_id is an integer if it exists
        if (isset($payload->user_id) && is_numeric($payload->user_id)) {
            $payload->user_id = intval($payload->user_id);
        }

        cert_api_log('verifyToken:ok', ['user_id' => $payload->user_id ?? null, 'user_type' => $payload->user_type ?? null]);
        return $payload; // Contains user_id, user_type, etc.
    } catch (Exception $e) {
        error_log("[AUTH_EXCEPTION] JWT Validation Error: " . $e->getMessage() . " for token: " . $jwt);
        if ($exitOnFail) {
            returnError("Authentication failed: " . $e->getMessage(), 401);
        }
        return null;
    }
}

$action = $_GET['action'] ?? null;
cert_api_log('request:start', ['action' => $action, 'method' => $_SERVER['REQUEST_METHOD']]);
$db = connectDB(); // From users_config.php

if (!$db) {
    returnError('Database connection failed.', 500);
}

// All actions in this API currently require admin privileges.
$tokenData = verifyToken(); // This will exit with 401/403 if validation fails. Role checks are done per action.
cert_api_log('auth:ok', ['user_id' => $tokenData->user_id ?? null, 'user_type' => $tokenData->user_type ?? null]);

if (!$tokenData || !isset($tokenData->user_id)) { // Should not be reached if verifyToken exits on fail
    returnError('Authentication failed or admin user ID not found in token.', 401);
}
$adminUserId = $tokenData->user_id; // user_id from the validated token


if ($action === 'generate') {
    if ($tokenData->user_type !== 'admin') {
        returnError('Access denied. Admin privileges required for this action.', 403);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnError('Invalid request method for generate. POST required.', 405);
    }
    $data = json_decode(file_get_contents('php://input'), true);
    cert_api_log('generate:payload', $data);
    $transaction_code = $data['transaction_code'] ?? null;

    if (!$transaction_code) {
        returnError('Transaction code is required.');
    }

    try {
        // 1. Get request_id and transaction_id (integer PK) from transactions table
        $stmt_trans = $db->prepare("SELECT transaction_id, request_id FROM transactions WHERE transaction_code = :transaction_code");
        $stmt_trans->bindParam(':transaction_code', $transaction_code);
        $stmt_trans->execute();
        $transactionDetails = $stmt_trans->fetch(PDO::FETCH_ASSOC);
        cert_api_log('generate:transaction_lookup', $transactionDetails);

        if (!$transactionDetails || !isset($transactionDetails['request_id']) || !isset($transactionDetails['transaction_id'])) {
            returnError('Transaction not found or missing key details for the given transaction code.', 404);
        }
        $request_id = $transactionDetails['request_id'];
        $db_transaction_id = $transactionDetails['transaction_id']; // The integer PK

        // 2. Call the certificate generator from certificate_generator.php
        cert_api_log('generate:call_generator');
        $generationResult = generateAndSaveCertificate($db, $transaction_code, $request_id);
        cert_api_log('generate:generator_result', $generationResult);

        if ($generationResult['status'] === 'success' && isset($generationResult['filepath'])) {
            // 3. Save certificate details to the 'certificates' table
            $generated_filename = basename($generationResult['filepath']);
            // $filepath_to_save = $generationResult['filepath']; // Actual path, not directly stored if schema lacks 'file_path'

            // Check if a certificate already exists for this transaction_code
            $stmt_check_cert = $db->prepare("SELECT certificate_id FROM certificates WHERE transaction_code = :transaction_code");
            $stmt_check_cert->bindParam(':transaction_code', $transaction_code);
            $stmt_check_cert->execute();
            $existing_cert = $stmt_check_cert->fetch(PDO::FETCH_ASSOC);

            $log_action = "";
            $stmt_cert_execute = false;

            if ($existing_cert) {
                $stmt_cert_update = $db->prepare("UPDATE certificates 
                                                 SET preprocessed_filename = :preprocessed_filename, status = 'preprocessed', updated_at = CURRENT_TIMESTAMP
                                                 WHERE transaction_code = :transaction_code");
                // Removed generated_by, file_path from DB update to match schema.
                // Using preprocessed_filename for the generated file's name.
                $stmt_cert_update->bindParam(':preprocessed_filename', $generated_filename);
                $stmt_cert_update->bindParam(':transaction_code', $transaction_code);
                $stmt_cert_execute = $stmt_cert_update->execute();
                cert_api_log('generate:cert_update', ['ok' => $stmt_cert_execute]);
                $log_action = "updated";
            } else {
                $stmt_cert_insert = $db->prepare("INSERT INTO certificates (transaction_code, request_id, preprocessed_filename, status)
                                                  VALUES (:transaction_code, :request_id, :preprocessed_filename, 'preprocessed')");
                // Removed generated_by, file_path from DB insert. Added request_id.
                $stmt_cert_insert->bindParam(':transaction_code', $transaction_code);
                $stmt_cert_insert->bindParam(':request_id', $request_id, PDO::PARAM_INT);
                $stmt_cert_insert->bindParam(':preprocessed_filename', $generated_filename);
                $stmt_cert_execute = $stmt_cert_insert->execute();
                cert_api_log('generate:cert_insert', ['ok' => $stmt_cert_execute]);
                $log_action = "inserted";
            }
            
            if ($stmt_cert_execute) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Certificate generated and DB record ' . $log_action . ' successfully.',
                    // Provide the filepath in response if useful for client, even if not stored in DB exactly as is.
                    'filepath' => $generationResult['filepath']
                ]);
            } else {
                error_log("Failed to $log_action certificate record for transaction_code {$transaction_code}. DB Error: " . print_r($db->errorInfo(), true));
                returnError('Certificate generated but failed to record in database.', 500);
            }
        } else {
            returnError('Certificate generation failed: ' . ($generationResult['message'] ?? 'Unknown error from generator'), 500);
        }
    } catch (Exception $e) {
        error_log("Error in generate certificate action for transaction_code {$transaction_code}: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        returnError('An internal error occurred during certificate generation: ' . $e->getMessage(), 500);
    }

} elseif ($action === 'download') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        returnError('Invalid request method for download. GET or HEAD required.', 405);
    }
    $transaction_code = $_GET['transaction_code'] ?? null;

    if (!$transaction_code) {
        returnError('Transaction code is required for download.');
    }

    try {
        // Authorize: Admin can download anything. User can only download their own.
        $stmt_owner = $db->prepare("
            SELECT r.user_id 
            FROM requests r
            JOIN transactions t ON r.request_id = t.request_id
            WHERE t.transaction_code = :transaction_code
        ");
        $stmt_owner->bindParam(':transaction_code', $transaction_code);
        $stmt_owner->execute();
        $request_owner = $stmt_owner->fetch(PDO::FETCH_ASSOC);

        if (!$request_owner) {
            returnError('Transaction not found.', 404);
        }

        if ($tokenData->user_type !== 'admin' && $tokenData->user_id != $request_owner['user_id']) {
            returnError('Access denied. You are not authorized to download this certificate.', 403);
        }
        
        // Fetch certificate filenames
        $stmt_get_cert = $db->prepare("SELECT preprocessed_filename, processed_filename FROM certificates WHERE transaction_code = :transaction_code ORDER BY updated_at DESC LIMIT 1");
        $stmt_get_cert->bindParam(':transaction_code', $transaction_code);
        $stmt_get_cert->execute();
        $certificate_info = $stmt_get_cert->fetch(PDO::FETCH_ASSOC);

        $certificate_file_to_serve = null;
        $certificate_actual_filename = null;

        if ($certificate_info) {
            // Prioritize processed certificate
            if (!empty($certificate_info['processed_filename'])) {
                $potential_path = __DIR__ . '/assets/processed_certs/' . $certificate_info['processed_filename'];
                if (file_exists($potential_path)) {
                    $certificate_file_to_serve = $potential_path;
                    $certificate_actual_filename = $certificate_info['processed_filename'];
                } else {
                    error_log("File {$potential_path} (processed) from DB record not found for transaction {$transaction_code}.");
                }
            }

            // Fallback to preprocessed certificate if processed one not found
            if (!$certificate_file_to_serve && !empty($certificate_info['preprocessed_filename'])) {
                $potential_path = __DIR__ . '/assets/preprocessed_certs/' . $certificate_info['preprocessed_filename'];
                if (file_exists($potential_path)) {
                    $certificate_file_to_serve = $potential_path;
                    $certificate_actual_filename = $certificate_info['preprocessed_filename'];
                } else {
                    error_log("File {$potential_path} (preprocessed) from DB record not found for transaction {$transaction_code}.");
                }
            }
        }

        if ($certificate_file_to_serve) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($certificate_actual_filename) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($certificate_file_to_serve));
            flush(); 
            readfile($certificate_file_to_serve);
            exit;
        } else {
            error_log("No valid certificate file found for transaction {$transaction_code}. DB info: " . print_r($certificate_info, true));
            http_response_code(404);
            // Output a user-friendly HTML page for 404 if not an API client expecting JSON strictly
            echo "<html><body><h1>Certificate Not Found</h1><p>The certificate for transaction code {$transaction_code} could not be found. It may not have been generated yet or an error occurred.</p></body></html>";
            exit;
        }
    } catch (Exception $e) {
        error_log("Error in download certificate action for transaction_code {$transaction_code}: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        returnError('An internal error occurred during certificate download: ' . $e->getMessage(), 500);
    }

} elseif ($action === 'upload_processed') {
    if ($tokenData->user_type !== 'admin') {
        returnError('Access denied. Admin privileges required for this action.', 403);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnError('Invalid request method. POST required.', 405);
    }

    $transaction_code = $_POST['transaction_code'] ?? null;
    if (empty($transaction_code)) {
        returnError('Transaction code is required.', 400);
    }

    if (!isset($_FILES['processed_certificate_file']) || $_FILES['processed_certificate_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['processed_certificate_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        returnError('File upload error: ' . getUploadErrorMessage($uploadError), 400);
    }

    $file = $_FILES['processed_certificate_file'];

    if ($file['type'] !== 'application/pdf') {
        returnError('Invalid file type. Only PDF files are allowed.', 400);
    }

    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxFileSize) {
        returnError('File is too large. Maximum size is 5MB.', 400);
    }

    try {
        $publicUrl = _handleGCSUpload_Certs($file, $transaction_code);

        $stmt_update = $db->prepare(
            "UPDATE certificates SET processed_filename = :processed_filename, status = 'processed', uploaded_by = :uploaded_by, uploaded_at = CURRENT_TIMESTAMP 
             WHERE transaction_code = :transaction_code"
        );
        $stmt_update->bindParam(':processed_filename', $publicUrl);
        $stmt_update->bindParam(':uploaded_by', $adminUserId, PDO::PARAM_INT);
        $stmt_update->bindParam(':transaction_code', $transaction_code);

        if ($stmt_update->execute() && $stmt_update->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Processed certificate uploaded and record updated successfully.',
                'filepath' => $publicUrl
            ]);
        } else {
            // Optional: If the DB fails, try to delete the uploaded file from GCS.
            error_log("[ERROR] Failed to update certificate record for {$transaction_code} after upload. DB Error: " . print_r($stmt_update->errorInfo(), true));
            returnError('Database error: Could not update certificate record after upload.', 500);
        }
    } catch (Exception $e) {
        error_log("[ERROR] Exception during processed certificate upload for {$transaction_code}: " . $e->getMessage());
        returnError('An internal server error occurred during upload: ' . $e->getMessage(), 500);
    }

} else {
    returnError('Invalid action specified.');
}

/**
 * Handles the file upload to Google Cloud Storage for certificates.
 *
 * @param array $file The $_FILES['processed_certificate_file'] array.
 * @param string $transaction_code The transaction code to build a unique filename.
 * @return string The public URL of the uploaded file.
 */
function _handleGCSUpload_Certs(array $file, string $transaction_code): string
{
    $bucketName = getenv('GCS_BUCKET_NAME');
    if (!$bucketName) {
        error_log("GCS_BUCKET_NAME environment variable not set.");
        throw new Exception("Server configuration error for file uploads.");
    }
    
    $originalFilename = basename($file['name']);
    $safeFilename = preg_replace("/[^a-zA-Z0-9_.-]/", "", $originalFilename);
    $extension = pathinfo($safeFilename, PATHINFO_EXTENSION);
    $gcsFileName = "processed_certs/{$transaction_code}_" . uniqid() . ".{$extension}";

    return uploadToGCS($bucketName, $file['tmp_name'], $gcsFileName);
}

// Helper function to get upload error messages (if not already globally available)
if (!function_exists('getUploadErrorMessage')) { // Prevent re-declaration if it exists elsewhere
    function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE: return "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
            case UPLOAD_ERR_FORM_SIZE: return "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.";
            case UPLOAD_ERR_PARTIAL: return "The uploaded file was only partially uploaded.";
            case UPLOAD_ERR_NO_FILE: return "No file was uploaded.";
            case UPLOAD_ERR_NO_TMP_DIR: return "Missing a temporary folder on the server.";
            case UPLOAD_ERR_CANT_WRITE: return "Failed to write file to disk on the server.";
            case UPLOAD_ERR_EXTENSION: return "A PHP extension stopped the file upload.";
            default: return "Unknown upload error.";
        }
    }
}

?>