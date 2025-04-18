<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('fpdf/fpdf.php');

// Get data sent from JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// Path to the image version of the PDF form
$pdfFormImage = "certificationForm.jpg"; // Assuming JPG image

class PDF extends FPDF
{
    // Load data and create a new page for each row
    function LoadData($data)
    {
        $this->SetFont('Arial', '', 12);
        foreach ($data as $row) {
            // Add a new page with the background image for each row
            $this->AddPage();
            // Coordinates for each field (example coordinates, adjust as necessary)
            $this->SetXY(62, 98.5);
            $this->Cell(30, 10, $row['clientName'], 0, 1);

            $this->SetXY(52, 104.5);
            $this->Cell(30, 10, $row['requestId'], 0, 1);

            $this->SetXY(111, 65);
            $this->Cell(30, 10, $row['province'], 0, 1);

            $this->SetXY(111, 70.5);
            $this->Cell(30, 10, $row['statName'], 0, 1);

            $this->SetXY(62, 76);
            $this->Cell(30, 10, $row['municipal'], 0, 1);

            $this->SetXY(139, 76);
            $this->Cell(30, 10, $row['barangay'], 0, 1);

            $this->SetXY(62, 87.5);
            $this->Cell(30, 10, $row['N84d'], 0, 1);

            $this->SetXY(139, 87.5);
            $this->Cell(30, 10, $row['longitude'], 0, 1);

            $this->Ln(10);
        }
    }

    // Page header
    function Header()
    {
        // Load the background image
        $this->Image($GLOBALS['pdfFormImage'], 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
    }

    // Page footer
    function Footer()
    {
        // No footer needed
    }
}

// Database configuration
$servername = "sql105.infinityfree.com";
$username = "if0_36589195";
$password = "Himpapawid11";
$dbname = "if0_36589195_webgnisdb";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement to select ID from test_request table
    $stmtSelect = $conn->prepare("SELECT ID, client, email, stat_names FROM gcp_request WHERE ID = :id");

    // Prepare SQL statement to insert data into completed_request table
    $stmtInsert = $conn->prepare("INSERT INTO completed_request (ID, client, email, stat_names) VALUES (:id, :client, :email, :stat_names)");

    // Prepare SQL statement to delete data from test_request table
    $stmtDelete = $conn->prepare("DELETE FROM gcp_request WHERE ID = :id");

    // Bind parameters for insert
    $stmtInsert->bindParam(':id', $id);
    $stmtInsert->bindParam(':client', $client);
    $stmtInsert->bindParam(':email', $email);
    $stmtInsert->bindParam(':stat_names', $stat_names);

    // Array to track processed request IDs
    $processedRequestIds = [];

    // Insert each request
    foreach ($data as $request) {
        if (!in_array($request['requestId'], $processedRequestIds)) {
            // Fetch data from test_request table
            $stmtSelect->execute([':id' => $request['requestId']]);
            $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Extract data
                $id = $row['ID'];
                $client = $row['client'];
                $email = $row['email'];
                $stat_names = $row['stat_names'];

                // Insert data into completed_request table
                $stmtInsert->execute();

                // Delete data from test_request table
                $stmtDelete->execute([':id' => $request['requestId']]);
                
                // Mark this requestId as processed
                $processedRequestIds[] = $request['requestId'];
            }
        }
    }

    // Close connection
    $conn = null;
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Instantiation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->LoadData($data);

// Output PDF
$pdfPath = './certificates/' . $data[0]['requestId'] . '.pdf'; // Path where the PDF will be saved within the "certificates" folder
$pdf->Output($pdfPath, 'F'); // Save PDF to file

echo json_encode(['pdfPath' => $pdfPath]); // Send the path back to JavaScript
?>
