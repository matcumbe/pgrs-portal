<?php


$host = 'sql105.infinityfree.com';
$dbname = 'if0_36589195_webgnisdb';
$username = 'if0_36589195';
$password = 'Himpapawid11';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $client = $_POST['client'];
        $email = $_POST['email'];
        $stat_names = $_POST['stat_names'];

        // Generate a unique ticketID
        $ticketID = generateTicketID();

        // Prepare the SQL statement with placeholders including ticketID
        $stmt = $pdo->prepare("INSERT INTO gcp_request (request_date, client, email, stat_names, ticketID) VALUES (NOW(), :client, :email, :stat_names, :ticketID)");

        // Bind the values to the placeholders
        $stmt->bindParam(':client', $client);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':stat_names', $stat_names);
        $stmt->bindParam(':ticketID', $ticketID);

        // Execute the statement
        if ($stmt->execute()) {
            echo $ticketID;
        } else {
            echo "Error submitting request.";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Function to generate a unique ticketID
function generateTicketID() {
    // Generate a random string
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = 'V';
    $length = 9; // Adjust the length of the ticketID as needed

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Concatenate timestamp to ensure uniqueness
    $ticketID = $randomString . time();

    return $ticketID;
}
?>
