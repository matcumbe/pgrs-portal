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

// Initialize variables
$id = isset($_POST['id']) ? $_POST['id'] : null;
$stat_name = $_POST['stat_name'];
$status = $_POST['status'];
$island = $_POST['island'];
$region = $_POST['region'];
$province = $_POST['province'];
$municipal = $_POST['municipal'];
$barangay = $_POST['barangay'];
$n84dd = $_POST['n84dd'];
$e84dd = $_POST['e84dd'];
$order_acc = $_POST['order_acc'];
$accuracy_class = $_POST['accuracy_class'];
$n92d = $_POST['n92d'];
$n92m = $_POST['n92m'];
$n92s = $_POST['n92s'];
$e92d = $_POST['e92d'];
$e92m = $_POST['e92m'];
$e92s = $_POST['e92s'];
$h92 = $_POST['h92'];
$n92ptm = $_POST['n92ptm'];
$e92ptm = $_POST['e92ptm'];
$z92 = $_POST['z92'];
$n84d = $_POST['n84d'];
$n84m = $_POST['n84m'];
$n84s = $_POST['n84s'];
$e84d = $_POST['e84d'];
$e84m = $_POST['e84m'];
$e84s = $_POST['e84s'];
$h84 = $_POST['h84'];
$descripts = $_POST['descripts'];
$e92utm = $_POST['e92utm'];
$n92utm = $_POST['n92utm'];
$z92utm = $_POST['z92utm'];
$e84utm = $_POST['e84utm'];
$n84utm = $_POST['n84utm'];
$z84utm = $_POST['z84utm'];

// Prepare SQL statement based on whether ID is provided or not
if ($id) {
    // Update existing entry
    $sql = "UPDATE gcp_table SET stat_name='$stat_name', status='$status', island='$island', region='$region', province='$province', municipal='$municipal', barangay='$barangay', N84dd='$n84dd', E84dd='$e84dd', order_acc='$order_acc', accuracy_class='$accuracy_class', N92d='$n92d', N92m='$n92m', N92s='$n92s', E92d='$e92d', E92m='$e92m', E92s='$e92s', H92='$h92', N92ptm='$n92ptm', E92ptm='$e92ptm', Z92='$z92', N84d='$n84d', N84m='$n84m', N84s='$n84s', E84d='$e84d', E84m='$e84m', E84s='$e84s', H84='$h84', descripts='$descripts', E92utm='$e92utm', N92utm='$n92utm', Z92utm='$z92utm', E84utm='$e84utm', N84utm='$n84utm', Z84utm='$z84utm' WHERE id='$id'";
} else {
    // Insert new entry (let the database handle auto-increment for the ID)
    $sql = "INSERT INTO gcp_table (stat_name, status, island, region, province, municipal, barangay, N84dd, E84dd, order_acc, accuracy_class, N92d, N92m, N92s, E92d, E92m, E92s, H92, N92ptm, E92ptm, Z92, N84d, N84m, N84s, E84d, E84m, E84s, H84, descripts, E92utm, N92utm, Z92utm, E84utm, N84utm, Z84utm) VALUES ('$stat_name', '$status', '$island', '$region', '$province', '$municipal', '$barangay', '$n84dd', '$e84dd', '$order_acc', '$accuracy_class', '$n92d', '$n92m', '$n92s', '$e92d', '$e92m', '$e92s', '$h92', '$n92ptm', '$e92ptm', '$z92', '$n84d', '$n84m', '$n84s', '$e84d', '$e84m', '$e84s', '$h84', '$descripts', '$e92utm', '$n92utm', '$z92utm', '$e84utm', '$n84utm', '$z84utm')";
}

// Execute the SQL statement
if ($conn->query($sql) === TRUE) {
    echo "Record updated/created successfully";
}
