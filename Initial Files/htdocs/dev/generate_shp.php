<?php
// generate_shp.php

require 'Shapefile/ShapefileAutoloader.php';
Shapefile\ShapefileAutoloader::register();

use Shapefile\Shapefile;
//use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;

$data = json_decode(file_get_contents('php://input'), true);
print_r($data);
$stationNames = $data['stationNames'];
print_r($stationNames);

// Database connection (adjust credentials as needed)
$servername = 'sql105.infinityfree.com';
$dbname = 'if0_36589195_webgnisdb';
$username = 'if0_36589195';
$password = 'Himpapawid11';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch station data
$stationNamesList = "'" . implode("','", $stationNames) . "'";

$sql = "SELECT * FROM gcp_table WHERE stat_name IN ($stationNamesList)";
$result = $conn->query($sql);


// Creating shapefile
$timestamp = date('ymd_His');

try {
    $shpFile = new ShapefileWriter('shp/stations.shp',[Shapefile::OPTION_EXISTING_FILES_MODE => Shapefile::MODE_OVERWRITE]);

    $shpFile->setShapeType(Shapefile::SHAPE_TYPE_POINT);
    $shpFile->addCharField('STN', 100);
    $shpFile->addCharField('ISL', 20);
    $shpFile->addCharField('REG', 100);
    $shpFile->addCharField('PRO', 50);
    $shpFile->addCharField('MUN', 100);
    $shpFile->addCharField('BGY', 100);
    $shpFile->addNumericField('OOA', 2);
    $shpFile->addNumericField('ACL', 2);

    foreach ($result as $row) {
        $point = new Point($row['E84dd'],$row['N84dd']);

        $point->setData('STN', $row['stat_name']);
        $point->setData('ISL', $row['island']);
        $point->setData('REG', $row['region']);
        $point->setData('PRO', $row['province']);
        $point->setData('MUN', $row['municipal']);
        $point->setData('BGY', $row['barangay']);
        $point->setData('OOA', $row['order_acc']);
        $point->setData('ACL', $row['accuracy_class']);

        $shpFile->writeRecord($point);
    }

    $shpFile = null;

} catch (ShapefileException $e) {
    // Print detailed error information
    echo "Error Type: " . $e->getErrorType()
        . "\nMessage: " . $e->getMessage()
        . "\nDetails: " . $e->getDetails();
}

$conn->close();

// Generate the filename with the format 'GNISOnline_YYMMDD_HHMMSS.zip'
$timestamp = date('Ymd_His');
$filename = "GNISOnline_$timestamp.zip";

// Create a new ZIP file in memory
$zip = new ZipArchive();
$tempFile = tempnam(sys_get_temp_dir(), 'zip');

if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
    die("Failed to create ZIP file");
}

// Define the shapefile paths
$shpDir = 'shp/';
$shpFiles = ['stations.shp', 'stations.shx', 'stations.dbf'];

// Add the shapefile components to the ZIP archive
foreach ($shpFiles as $file) {
    $filePath = $shpDir . $file;
    if (file_exists($filePath)) {
        $zip->addFile($filePath, $file);
    } else {
        die("File not found: $filePath");
    }
}

// Close the ZIP file
$zip->close();

// Ensure the file is completely written
if (!file_exists($tempFile) || filesize($tempFile) === 0) {
    die("Failed to create ZIP file or file is empty");
}

// Clean the output buffer
if (ob_get_length()) {
    ob_clean();
}

// Send the file headers
header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Content-Length: ' . filesize($tempFile));

// Flush the system output buffer
flush();

// Output the zip file for download
readfile($tempFile);

// Clean up temporary file
unlink($tempFile);

exit();
?>
