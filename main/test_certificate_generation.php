<?php
// test_certificate_generation.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Certificate Generation Test</h1>";

require_once 'users_config.php'; // For connectDB() to webgnis_users
require_once 'certificate_generator.php'; // The script to test

$db = connectDB(); // Connection to webgnis_users

if (!$db) {
    die("<p style='color:red;'>Failed to connect to webgnis_users database.</p>");
}
echo "<p>Successfully connected to webgnis_users database.</p>";

// Test Data based on screenshots
$test_request_id = 74;
// Using a potentially unique transaction code for each test run to avoid filename clashes if old files aren't cleaned up
$test_transaction_code = 'TESTCERT-' . date('YmdHis'); 
$test_user_id = 2; // From requests screenshot
$test_request_date = '2025-06-03 15:31:20'; // From requests screenshot

// User data for user_id = 2
$test_user_data = [
    'user_id' => $test_user_id,
    'username' => 'sample', // Matching username from provided webgnis_users.sql for user_id 2
    'password' => '$2y$10$l4MkyBztTrYXY.xVCx2rAeo8hSZkqC7enS0sgi3TTBeLzydcjhFHe', // Matching hash
    'email' => 'sample@email.com', // Matching email
    'user_type' => 'individual',
    'sex_id' => 1,
    'name_on_certificate' => 'Sample User Name for Certificate Test' // For getRequestingPartyDetails
];

// Request data for request_id = 74
$test_request_data = [
    'request_id' => $test_request_id,
    'user_id' => $test_user_id,
    'request_date' => $test_request_date,
    'status_id' => 4, // 'Approved' status
    'total_amount' => 1800.00,
    'transaction_code' => $test_transaction_code 
];

// Request items for request_id = 74
$test_request_items_data = [
    ['item_id_pk' => 217, 'request_id' => $test_request_id, 'station_id' => '356', 'station_name' => 'MMA-3202', 'station_type' => 'horizontal', 'price' => 360.00],
    ['item_id_pk' => 218, 'request_id' => $test_request_id, 'station_id' => '356', 'station_name' => 'MMA-3202', 'station_type' => 'caap', 'price' => 720.00],
    ['item_id_pk' => 219, 'request_id' => $test_request_id, 'station_id' => '1', 'station_name' => 'MMA-4269 (GM-3HA)', 'station_type' => 'vertical', 'price' => 360.00],
    ['item_id_pk' => 220, 'request_id' => $test_request_id, 'station_id' => '10001', 'station_name' => 'MMA-115', 'station_type' => 'gravity', 'price' => 360.00]
];

// Individual details for user_id = 2 (as a fallback if name_on_certificate is not set)
// From your webgnis_users.sql, user_id 2 already has an entry in individual_details
$test_individual_details_data = [
    'user_id' => $test_user_id,
    'full_name' => 'Sample Name', // Matching full_name from dump
    'address' => 'Sample Address' // Matching address from dump
];


echo "<h2>Preparing Test Data...</h2>";
try {
    $db->beginTransaction();

    // Upsert User: Update if user_id exists, otherwise insert.
    $sql_user = "INSERT INTO users (user_id, username, password, email, user_type, sex_id, name_on_certificate, is_active, created_at, updated_at) 
                 VALUES (:user_id, :username, :password, :email, :user_type, :sex_id, :name_on_certificate, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE 
                    username = VALUES(username), 
                    password = VALUES(password), 
                    email = VALUES(email), 
                    user_type = VALUES(user_type), 
                    sex_id = VALUES(sex_id),
                    name_on_certificate = VALUES(name_on_certificate),
                    updated_at = NOW()";
    $stmt_user = $db->prepare($sql_user);
    $stmt_user->execute([
        ':user_id' => $test_user_data['user_id'],
        ':username' => $test_user_data['username'],
        ':password' => $test_user_data['password'],
        ':email' => $test_user_data['email'],
        ':user_type' => $test_user_data['user_type'],
        ':sex_id' => $test_user_data['sex_id'],
        ':name_on_certificate' => $test_user_data['name_on_certificate']
    ]);
    echo "<p>User data for user_id {$test_user_id} upserted.</p>";

    // Upsert Individual Details
    // user_id is UNIQUE in individual_details
     $sql_individual = "INSERT INTO individual_details (user_id, full_name, address) 
                        VALUES (:user_id, :full_name, :address)
                        ON DUPLICATE KEY UPDATE 
                           full_name = VALUES(full_name), 
                           address = VALUES(address)";
    $stmt_individual = $db->prepare($sql_individual);
    $stmt_individual->execute($test_individual_details_data);
    echo "<p>Individual details for user_id {$test_user_id} upserted.</p>";


    // Upsert Request: Update if request_id exists, otherwise insert.
    $sql_request = "INSERT INTO requests (request_id, user_id, request_date, status_id, total_amount, transaction_code)
                    VALUES (:request_id, :user_id, :request_date, :status_id, :total_amount, :transaction_code)
                    ON DUPLICATE KEY UPDATE 
                       user_id = VALUES(user_id), 
                       request_date = VALUES(request_date), 
                       status_id = VALUES(status_id), 
                       total_amount = VALUES(total_amount),
                       transaction_code = VALUES(transaction_code)";
    $stmt_request = $db->prepare($sql_request);
    $stmt_request->execute($test_request_data);
    echo "<p>Request data for request_id {$test_request_id} upserted.</p>";

    // For request_items, it's often easier to delete and re-insert for testing to ensure a clean state.
    // However, request_items PK is item_id (auto-increment). We can upsert based on a combination
    // of request_id and station_id if they should be unique together per request, or manage by item_id_pk if that's the actual PK.
    // The screenshot shows request_items without its own primary key, implying combination might be key or no strict PK shown.
    // The webgnis_users.sql shows item_id as PK for request_items.
    // For this test, let's ensure the specific items are present by deleting any for the request_id and re-inserting.
    
    $stmt_delete_items = $db->prepare("DELETE FROM request_items WHERE request_id = :request_id");
    $stmt_delete_items->bindParam(':request_id', $test_request_id);
    $stmt_delete_items->execute();
    echo "<p>Existing request_items for request_id {$test_request_id} deleted if any.</p>";

    // Insert Request Items
    $item_sql = "INSERT INTO request_items (request_id, station_id, station_name, station_type, price)
                 VALUES (:request_id, :station_id, :station_name, :station_type, :price)";
    $item_stmt = $db->prepare($item_sql);
    foreach ($test_request_items_data as $item) {
        $item_stmt->execute([
            ':request_id' => $item['request_id'],
            ':station_id' => $item['station_id'],
            ':station_name' => $item['station_name'],
            ':station_type' => $item['station_type'],
            ':price' => $item['price']
        ]);
    }
    echo "<p>Test request_items for request_id {$test_request_id} inserted.</p>";

    $db->commit();
    echo "<p style='color:green;'>Test data prepared/verified in webgnis_users database for request_id: $test_request_id.</p>";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("<p style='color:red;'>Error preparing test data: " . $e->getMessage() . "</p><pre>" . $e->getTraceAsString() . "</pre>");
}

// Now call the certificate generator
echo "<h2>Attempting to generate certificate...</h2>";
$result = generateAndSaveCertificate($db, $test_transaction_code, $test_request_id);

echo "<div style='border:1px solid #ccc; padding:10px; margin-top:10px;'>";
echo "<h3>Generation Result:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['status'] === 'success' && isset($result['filepath'])) {
    echo "<p style='color:green; font-weight:bold;'>Certificate generated successfully!</p>";
    
    // Try to determine a web-accessible path
    $baseDir = basename(__DIR__); // e.g., "WebGNIS Files"
    $filePathRelativeToServerRoot = str_replace(DIRECTORY_SEPARATOR, '/', substr($result['filepath'], strpos($result['filepath'], $baseDir) + strlen($baseDir) +1 ));
    // This is a guess, assumes 'WebGNIS Files' is directly under web root or $baseDir is part of web path
    // A more robust way is to define a WEB_ROOT_URL constant
    $webPath = $filePathRelativeToServerRoot; 
    // Fallback if above logic fails or for simpler local server setups:
    if (strpos($result['filepath'], 'assets'.DIRECTORY_SEPARATOR.'preprocessed_certs'.DIRECTORY_SEPARATOR) !== false){
         $webPath = 'assets/preprocessed_certs/' . basename($result['filepath']);
    }

    echo "<p>View Certificate: <a href='$webPath' target='_blank'>$webPath</a></p>";
    echo "<p>(If the link doesn't work, the PDF is at server path: <code>" . htmlspecialchars($result['filepath']) . "</code>)</p>";

} else {
    echo "<p style='color:red; font-weight:bold;'>Certificate generation failed.</p>";
    echo "<p>Message: " . htmlspecialchars($result['message'] ?? 'No message provided.') . "</p>";
}
echo "</div>";

echo "<p style='margin-top:20px;'>Test finished.</p>";

?> 