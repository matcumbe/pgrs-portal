<?php
require_once 'config.php';

echo "Updating user types to include moderator...\n";

try {
    // Connect to webgnis_users database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'webgnis_users');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "Connected to webgnis_users database\n";
    
    // Check current user_type column definition
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'");
    $column = $result->fetch_assoc();
    
    echo "Current user_type definition: " . $column['Type'] . "\n";
    
    // Update the ENUM to include 'moderator'
    $sql = "ALTER TABLE users MODIFY COLUMN user_type ENUM('individual', 'company', 'moderator', 'admin') NOT NULL";
    
    if ($conn->query($sql)) {
        echo "✓ Successfully updated user_type column to include 'moderator'\n";
    } else {
        echo "✗ Error updating user_type column: " . $conn->error . "\n";
    }
    
    // Check if moderator user already exists
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'moderator'");
    $count = $result->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Create moderator user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, contact_number, user_type, is_active) 
                VALUES ('moderator', '$password', 'moderator@webgnis.gov.ph', '09123456788', 'moderator', TRUE)";
        
        if ($conn->query($sql)) {
            echo "✓ Successfully created moderator user\n";
            echo "  Username: moderator\n";
            echo "  Password: admin123\n";
        } else {
            echo "✗ Error creating moderator user: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Moderator user already exists\n";
    }
    
    // Verify the update
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'");
    $column = $result->fetch_assoc();
    echo "Updated user_type definition: " . $column['Type'] . "\n";
    
    $conn->close();
    echo "\nUpdate completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
