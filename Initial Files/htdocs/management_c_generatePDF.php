<?php
// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

// Retrieve JSON data from the request body
$jsonData = file_get_contents('php://input');

// Decode the JSON data
$stationsData = json_decode($jsonData, true);

// Check if JSON decoding was successful
if ($stationsData === null) {
    // Log JSON decoding errors
    error_log('JSON decoding failed: ' . json_last_error_msg());
    exit('JSON decoding failed');
}

// Process the received data and generate PDFs
// Your code for generating PDFs from the $stationsData goes here...

// Example of logging a success message
error_log('PDF generation successful');

// Send a response back to the client
echo 'PDF generation successful';


error_reporting(E_ALL);
ini_set('display_errors', 1);

require('fpdf/fpdf.php');

// Get data sent from JavaScript
$data = json_decode(file_get_contents('php://input'), true);

// Database configuration
$servername = "sql105.infinityfree.com";
$username = "if0_36589195";
$password = "Himpapawid11";
$dbname = "if0_36589195_webgnisdb";

// ------------------------------------------------------------------------------------------------------

// Create connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get column names from the specified table
$table_name = 'gcp_table';
$columns_query = "SELECT COLUMN_NAME 
                  FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = ? 
                  AND TABLE_NAME = ?";
$stmt = $conn->prepare($columns_query);
$stmt->bind_param('ss', $dbname, $table_name);
$stmt->execute();
$result = $stmt->get_result();

// Fetch column names and store them in an array
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['COLUMN_NAME'];
}

// Close the statement for fetching column names
$stmt->close();

// Initialize the result array that will store the final output
global $result_array;
$result_array = [];


foreach ($data as $dict) {
    // Check if the current dictionary has a 'statName' key
    if (isset($dict['statName'])) {
        $statName = $dict['statName'];

        // Prepare and execute the SQL query to get rows matching the statName
        $stmt = $conn->prepare("SELECT * FROM gcp_table WHERE stat_name = ?");
        $stmt->bind_param("s", $statName);
        $stmt->execute();
        $result = $stmt->get_result();

        // If rows are found, add them to the result array
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Create a dictionary for the current row with column names as keys
                $entry = [];
                foreach ($columns as $column) {
                    $entry[$column] = $row[$column];
                }

                // Add the current row's dictionary to the result array with statName as the key
                $result_array[] = $entry + $dict;
            }
        } else {
            // If no rows are found, add an entry with null values for each column
            $result_array[] = [
                $statName => array_fill_keys($columns, null)
            ];
        }

        // Close the statement for fetching rows
        $stmt->close();
    }
}

// Close the database connection
$conn->close();

// ------------------------------------------------------------------------------------------------------


$dataTickID = (string)$data[0]['ticketID'];
$ticketIDtype = $dataTickID[0];

class PDF extends FPDF {

    // Function to center mixed text
    function provinceMixedText($regularText, $boldText, $y) {
        // Set fonts
        $this->SetFont('Arial', '', 10);
        $regularTextWidth = $this->GetStringWidth($regularText);
        
        $this->SetFont('Arial', 'B', 10);
        $boldTextWidth = $this->GetStringWidth($boldText);

        // Calculate total width
        $totalWidth = $regularTextWidth + $boldTextWidth;

        // Calculate the x position to center the text
        $x = ($this->w - $totalWidth) / 2;

        // Set position and print the regular text
        $this->SetXY($x, $y);
        $this->SetFont('Arial', '', 10);
        $this->Cell($regularTextWidth, 2.8, $regularText, 0, 0, 'C');

        // Print the bold text right after the regular text
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($boldTextWidth, 2.8, $boldText, 0, 0, 'C');
    }

    function CenteredMixedText($regularText, $boldText, $y) {
        // Set fonts
        $this->SetFont('Arial', '', 10);
        $regularTextWidth = $this->GetStringWidth($regularText);
        
        $this->SetFont('Arial', 'B', 10);
        $boldTextWidth = $this->GetStringWidth($boldText);

        // Calculate total width
        $totalWidth = $regularTextWidth + $boldTextWidth;

        $x = (192 - $totalWidth)/2;

        // Set position and print the regular text
        $this->SetXY($x, $y);
        $this->SetFont('Arial', '', 10);
        $this->Cell($regularTextWidth, 2.8, $regularText, 0, 0, 'C');

        // Print the bold text right after the regular text
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($boldTextWidth, 2.8, $boldText, 0, 0, 'C');
    }

    // Text box function
    function TextBox($x, $y, $width, $height, $text, $border = 0, $align = 'L', $fill = false) {
        // Set the position
        $this->SetFont('Arial', '', 8);
        $this->SetXY($x, $y);
        // Create the text box with the specified width and height
        $this->MultiCell($width, $height, $text, $border, $align, $fill);
    }

    function BoldText($x, $y, $text) {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 10);
        $boldTextWidth = $this->GetStringWidth($text);
        $this->Cell($boldTextWidth, 2.8, $text, 0, 1);
        // Move down by line height to avoid overlapping with the next content
        $this->Ln();
    }

    // Load data and create a new page for each row
    function LoadData($data) {
        if ($GLOBALS['ticketIDtype'] === "H") {
            global $result_array;
            $this->SetFont('Arial', '', 10);
            foreach ($result_array as $row) {
                    $N84dms = $row['N84d'] . "\xB0 " . $row['N84m'] . "' " . $row['N84s'] . "''";
                    $E84dms = $row['E84d'] . "\xB0 " . $row['E84m'] . "' " . $row['E84s'] . "''";
                    $N92dms = $row['N92d'] . "\xB0 " . $row['N92m'] . "' " . $row['N92s'] . "''";
                    $E92dms = $row['E92d'] . "\xB0 " . $row['E92m'] . "' " . $row['E92s'] . "''";

                    // Add a new page with the background image for each row1
                    $this->AddPage();
                    // Coordinates for each field (example coordinates, adjust as necessary)
                    $this->provinceMixedText('Province: ', $row['province'], 71.55);
                    $this->provinceMixedText('Station Name: ', $row['stat_name'], 78.45);

                    $this->BoldText(25, 84.8, $row['order_acc']);
                    $this->CenteredMixedText('Accuracy Class: ', $row['accuracy_class'], 84.8);

                    $this->BoldText(25, 91.8, $row['island']);
                    $this->CenteredMixedText('Barangay: ', $row['barangay'], 91.8);
                    $this->BoldText(144.2, 91.8, $row['municipal']);

                    $this->BoldText(29, 105.6, $N92dms);
                    $this->CenteredMixedText('Longitude: ', $E92dms, 105.6);
                    $this->BoldText(154.5, 105.6, $row['H92']." m");

                    $this->BoldText(29, 119.4, $row['N92ptm']);
                    $this->CenteredMixedText('Eastings: ', $row['E92ptm'], 119.4);
                    $this->BoldText(135, 119.4, $row['Z92']);                    

                    $this->BoldText(29, 133.2, $row['N92utm']);
                    $this->CenteredMixedText('Eastings: ', $row['E92utm'], 133.2);
                    $this->BoldText(135, 133.2, $row['Z92utm']);

                    $this->BoldText(29, 147, $N84dms);
                    $this->CenteredMixedText('Longitude: ', $E84dms, 147);
                    $this->BoldText(154.5, 147, $row['H84']." m");

                    $this->BoldText(29, 167.8, $row['N84utm']);
                    $this->CenteredMixedText('Eastings: ', $row['E84utm'], 167.8);
                    $this->BoldText(135, 167.8, $row['Z84utm']);

                    $this->TextBox(14.2, 183.5, 181.5, 3, $row['descripts']);

                    $this->BoldText(43.3, 204.5, $row['clientName']);
                    $this->BoldText(43.3, 214.5, $row['ticketID']);

                    $this->Ln(10);

            }

        } else {
            global $result_array;
            $this->SetFont('Arial', '', 10);
            foreach ($result_array as $row) {
                    $N84dms = $row['N84d'] . "\xB0 " . $row['N84m'] . "' " . $row['N84s'] . "''";
                    $E84dms = $row['E84d'] . "\xB0 " . $row['E84m'] . "' " . $row['E84s'] . "''";
                                      
                    // Add a new page with the background image for each row
                    $this->AddPage();

                    // Coordinates for each field (example coordinates, adjust as necessary)
                    $this->provinceMixedText('Station Name: ', $row['stat_name'], 75);

                    $this->BoldText(25, 81.5, $row['island']);  
                    $this->BoldText(120, 81.5, $row['province']);

                    $this->BoldText(30.5, 88.5, $row['barangay']);
                    $this->BoldText(124.7, 88.5, $row['municipal']);

                    $this->BoldText(30, 95.5, $row['H84'].' m');  
                    $this->BoldText(130.5, 95.5, $row['accuracy_class'].' CM');

                    $this->BoldText(31, 102.2, $E84dms);
                    $this->BoldText(118.9, 102.2, $N84dms);

                    $this->TextBox(17.5, 110, 175, 5, $row['descripts']);

                    $this->BoldText(42.5, 162.5, $row['clientName']);
                    $this->BoldText(42.5, 172.5, $row['ticketID']);

                    $this->Ln(10);

                }

            }


    }


    

    // Page header
    function Header()
    {
        if ($GLOBALS['ticketIDtype'] === "H"){
            // Load the background image
            $this->Image("horiFormNamria.jpg", 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
        } else {
            $this->Image("vertFormNamria.jpg", 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
        }
    }

    // Page footer
    function Footer()
    {
        // No footer needed
    }

}

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement to select ID from test_request table
    $stmtSelect = $conn->prepare("SELECT * FROM gcp_request WHERE ticketID = :id");

    // Prepare SQL statement to insert data into completed_request table
    $stmtInsert = $conn->prepare("INSERT INTO completed_request (ticketID, request_date, client, email, stat_names, comp_date) VALUES (:id, :reqDate, :client, :email, :stat_names, NOW())");

    // Prepare SQL statement to delete data from test_request table
    $stmtDelete = $conn->prepare("DELETE FROM gcp_request WHERE ticketID = :id");

    // Bind parameters for insert
    $stmtInsert->bindParam(':id', $id);
    $stmtInsert->bindParam(':reqDate', $reqDate);
    $stmtInsert->bindParam(':client', $client);
    $stmtInsert->bindParam(':email', $email);
    $stmtInsert->bindParam(':stat_names', $stat_names);

    // Array to track processed request IDs
    $processedRequestIds = [];

    // Insert each request
    foreach ($data as $request) {
        if (!in_array($request['ticketID'], $processedRequestIds)) {
            // Fetch data from test_request table
            $stmtSelect->execute([':id' => $request['ticketID']]);
            $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Extract data
                $id = $row['ticketID'];
                $reqDate = $row['request_date'];
                $client = $row['client'];
                $email = $row['email'];
                $stat_names = $row['stat_names'];

                // Insert data into completed_request table
                $stmtInsert->execute();

                // Delete data from test_request table
                $stmtDelete->execute([':id' => $request['ticketID']]);
                
                // Mark this ticketID as processed
                $processedRequestIds[] = $request['ticketID'];
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
$pdfPath = './certificates/' . $data[0]['ticketID'] . '.pdf'; // Path where the PDF will be saved within the "certificates" folder
$pdf->Output($pdfPath, 'F'); // Save PDF to file

echo json_encode(['pdfPath' => $pdfPath]); // Send the path back to JavaScript

/* -------------------------------------------------------------------------- */

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Set mailer to use SMTP
    $mail->isSMTP();

    // Configure SMTP
    $mail->Host = 'smtp.zoho.com'; // Your SMTP server address
    $mail->SMTPAuth = true;
    $mail->Username = 'service@gnis.online'; // Your SMTP username
    $mail->Password = 'GzWBsQ736y5n'; // Your SMTP password
    $mail->SMTPSecure = 'tls'; // Enable TLS encryption, 'ssl' also accepted
    $mail->Port = 587; // TCP port to connect to

    // Set sender and recipient
    $mail->setFrom('service@gnis.online', 'GNIS Online');
    $mail->addAddress($data[0]['emailAdd'], $data[0]['clientName']);

    // Set email subject and body
    $mail->Subject = 'Certificate Request (Ticket ID: '.$data[0]['ticketID'].') Email Thread';

    // Set email body as HTML
    $mail->isHTML(true);
$body = 'Greetings, '.$data[0]['clientName'].'!<br><br>Your certificate request (Ticket ID: '.$data[0]['ticketID'].') has been processed! You can download your certificate/s at this address below:<br><br>gnis.online/tracker_certificates.php<br>gnis.online/tracker_certificates.php<br>gnis.online/tracker_certificates.php<br><br>Use your credentials below to access the file.<br><br>Ticket ID: <b>'.$data[0]['ticketID'].'</b><br>Email Address: <b>'.$data[0]['emailAdd'].'</b><br><br>Thank you for availing our services!<br><br>Ensuring accuracy and precision,<br><b>WebGNIS Service Team</b>';    
    $mail->Body = $body;

    $mail->addAttachment($pdfPath);

    $mail->addReplyTo('reply.to@gmail.com');

    // Send the email
    $mail->send();
    echo 'Email has been sent successfully!';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}

/* -------------------------------------------------------------------------- */

?>

