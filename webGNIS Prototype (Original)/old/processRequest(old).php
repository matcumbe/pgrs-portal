<?php
// Database credentials
$host = 'sql105.infinityfree.com';
$dbname = 'if0_36589195_webgnisdb';
$username = 'if0_36589195';
$password = 'Himpapawid11';

// Connect to the database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Process form data
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve data from form submission
        $clientName = $_POST["clientName"];
        $clientEmail = $_POST["clientEmail"];
        $selectedStats = $_POST["selectedStats"];

        // Generate a random, unique ticket ID
        $ticketID = uniqid();

                // Prepare and execute SQL statement to insert data into the 'gcp_request' table
                $stmt = $conn->prepare("INSERT INTO gcp_request (ID, client, email, stat_names) VALUES (:ID, :client, :email, :stat_names)");
        
                // Bind parameters
                $stmt->bindParam(':ID', $ticketID);
                $stmt->bindParam(':client', $clientName);
                $stmt->bindParam(':email', $clientEmail);
                $stmt->bindParam(':stat_names', $statNames);
        
                // Implode selected stats into a comma-separated string
                $statNames = implode(", ", $selectedStats);
        
                // Execute the statement
                $stmt->execute();
        
                // Check if the data was inserted successfully
                if ($stmt->rowCount() > 0) {
                    echo $ticketID;
                } else {
                    echo "Failed to submit request.";
                }
            }
        } catch(PDOException $e) {
            // Handle database connection error
            echo "Connection failed: " . $e->getMessage();
        }
        ?>
        
