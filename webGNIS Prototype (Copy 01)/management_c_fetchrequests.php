<?php
// MySQL server configuration
$servername = "sql105.infinityfree.com"; // Your MySQL server name (usually localhost)
$username = "if0_36589195"; // Your MySQL username
$password = "Himpapawid11"; // Your MySQL password
$dbname = "if0_36589195_webgnisdb"; // Your database name
$table = "gcp_request"; // Your table name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Select data from table
$sql = "SELECT ticketID, request_date, client, email, stat_names FROM $table";
$result = $conn->query($sql);

$requests = array();
if ($result->num_rows > 0) {
    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$conn->close();

echo json_encode($requests);
?>
