<?php
// Database connection parameters
        $servername = "sql105.infinityfree.com";
        $username = "if0_36589195";
        $password = "Himpapawid11";
        $dbname = "if0_36589195_webgnisdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the station name to be deleted from the AJAX request
$stationName = $_POST['station_name'];

// SQL query to delete the entry from gcp_table
$sql = "DELETE FROM gcp_table WHERE stat_name = '$stationName'";

if ($conn->query($sql) === TRUE) {
    echo "Entry for station '$stationName' deleted successfully";
} else {
    echo "Error deleting entry: " . $conn->error;
}

$conn->close();
?>
