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

// Check if station name is provided
if (isset($_POST['station_name'])) {
    $station_name = $conn->real_escape_string($_POST['station_name']);
    
    // SQL query to select the row for the given station name
    $sql = "SELECT id, stat_name, N84dd, E84dd, order_acc, accuracy_class, island, region, province, municipal, barangay, status, descripts, N92d, N92m, N92s, E92d, E92m, E92s, H92, N92ptm, E92ptm, Z92, N84d, N84m, N84s, E84d, E84m, E84s, H84, E92utm, N92utm, Z92utm, E84utm, N84utm, Z84utm FROM gcp_table WHERE stat_name = '$station_name'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row); // Encode the result as JSON
    } else {
        echo json_encode(["error" => "No data found"]);
    }
} else {
    echo json_encode(["error" => "No station name provided"]);
}

$conn->close();
?>
