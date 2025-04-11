<?php
// MySQL server configuration
$servername = "sql105.infinityfree.com"; // Your MySQL server name (usually localhost)
$username = "if0_36589195"; // Your MySQL username
$password = "Himpapawid11"; // Your MySQL password
$dbname = "if0_36589195_webgnisdb"; // Your database name
$table = "gcp_table"; // Your table name

// Get the station name from the request
$stat_name = $_GET['stat_name'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stations = [];

// Prepare and execute query
$sql = "SELECT * FROM $table WHERE stat_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $stat_name);
$stmt->execute();
$result = $stmt->get_result();

// Fetch data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $station = array(
            'island' => $row['island'],
            'region' => $row['region'],
            'province' => $row['province'],
            'stat_name' => $row['stat_name'],
            'municipal' => $row['municipal'],
            'barangay' => $row['barangay'],
            'order_acc' => $row['order_acc'],
            'N84dd' => $row['N84dd'],
            'E84dd' => $row['E84dd'],
            'N84d' => $row['N84d'],
            'N84m' => $row['N84m'],
            'N84s' => $row['N84s'],
            'E84d' => $row['E84d'],
            'E84m' => $row['E84m'],
            'E84s' => $row['E84s'],
        );
        $stations[] = $station;
    }
}

echo json_encode($stations);

// Close connection
$stmt->close();
$conn->close();
?>
