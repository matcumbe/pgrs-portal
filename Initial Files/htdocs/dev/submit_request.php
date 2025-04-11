<?php


$host = 'sql105.infinityfree.com';
$dbname = 'if0_36589195_webgnisdb';
$username = 'if0_36589195';
$password = 'Himpapawid11';

$items = array();

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $client = $_POST['client'];
        $email = $_POST['email'];
        $stat_names = $_POST['stat_names'];

        $items['client'] = $client;
        $items['email'] = $email;

        // Generate a unique ticketID
        $ticketID = generateTicketID();
        $items['ticketID'] = $ticketID;

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
    $randomString = 'H';
    $length = 9; // Adjust the length of the ticketID as needed

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Concatenate timestamp to ensure uniqueness
    $ticketID = $randomString . time();
    

    return $ticketID;
}

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
    $mail->Username = 'service@web-gnis.42web.io'; // Your SMTP username
    $mail->Password = '6UJhZWiR6WU7'; // Your SMTP password
    $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, 'ssl' also accepted
    $mail->Port = 465; // TCP port to connect to

    // Set sender and recipient
    $mail->setFrom('service@web-gnis.42web.io', 'WebGNIS Service Team');
    $mail->addAddress($items['email'], $items['client']);

    // Set email subject and body
    $mail->Subject = 'Your Request (Ticket ID: '.$items['ticketID'].') is submitted!';

    // Set email body as HTML
    $mail->isHTML(true);
    $body = 'Greetings, '.$items['client'].'!<br><br>Your certificate request (Ticket ID: '.$items['ticketID'].' has been submitted! You can check the progress of your certificate request at this address below:<br><br>https://web-gnis.42web.io/certTrack.php<br>https://web-gnis.42web.io/certTrack.php<br>https://web-gnis.42web.io/certTrack.php<br><br>Use your credentials below to check on the request progress.<br><br>Ticket ID: <b>'.$items['ticketID'].'</b><br>Email Address: <b>'.$items['email'].'</b><br><br>Thank you for availing our services!<br><br>Ensuring accuracy and precision,<br><b>WebGNIS Service Team</b>';
    $mail->Body = $body;

    // Send the email
    $mail->send();
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}

/* -------------------------------------------------------------------------- */

?>
