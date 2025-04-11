<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GNIS | Tracker</title>
  <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
 <style>
       body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header h1 img {
            margin-right: 10px;
        }
        .navbar {
            display: flex;
            justify-content: center;
            background-color: #333;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar a {
            padding: 15px 20px;
            display: block;
            color: white;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
        }
        .navbar a:hover {
            background-color: #ddd;
            color: black;
        }

            /* Add CSS for dropdown */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 168px;
            z-index: 9999;
            box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #ddd;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }


    .container {
      max-width: 600px;
      margin: 50px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .status-container {
      max-width: 600px;
      margin: 20px auto;
      padding: 10px 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    h1 {
      text-align: center;
      margin-bottom: 20px;
    }
    form {
      text-align: center;
    }
    input[type="text"],
    input[type="email"] {
      width: calc(100% - 22px);
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-sizing: border-box;
    }
    input[type="submit"] {
      width: 100%;
      background-color: #4caf50;
      color: #fff;
      padding: 10px 0;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
    input[type="submit"]:hover {
      background-color: #45a049;
    }
    a {
      color: #4caf50; /* Green hyperlink color */
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<div class="header">
    <h1><img src="gnis_logo.png" alt="GNIS Logo" width="60"> Geodetic Network Information System (GNIS)</h1>
</div>

<div class="navbar">
    <a href="index.html"><i class="fas fa-home"></i> GNIS Home</a>
    <div class="dropdown">
        <a href="#" id="explorerLink"><i class="fas fa-map"></i> Explorer <i class="fas fa-caret-down"></i></a>
        <div class="dropdown-content">
            <a href="mapDatabase.php">Horizontal Control Points</a>
            <a href="verticalDatabase.php">Vertical Control Points</a>
        </div>
    </div>
    <a href="certTrack.php"><i class="fas fa-chart-line"></i> Tracker</a>
    <a href="gcpsimulator.php"><i class="fas fa-cogs"></i> Simulator</a>
    <div class="dropdown">
        <a href="#" id="managementLink"><i class="fas fa-tasks"></i> Management <i class="fas fa-caret-down"></i></a>
        <div class="dropdown-content">
            <a href="CRM.php">Certificates</a>
            <a href="pointsManagement.php">Control Points</a>
        </div>
    </div>
    <a href="about.html"><i class="fas fa-info-circle"></i> About Us</a>
</div>

  <div class="container">
    <h1>Certificate Request Tracker</h1>
    <form id="certificateForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <input type="text" id="requestId" name="requestId" placeholder="Request ID" required><br>
      <input type="text" id="email" name="email" placeholder="Email" required><br>
      <input type="submit" value="Submit">
    </form>
  </div>

  <?php
  // Database connection parameters
  $servername = "sql105.infinityfree.com";
  $username = "if0_36589195";
  $password = "Himpapawid11";
  $database = "if0_36589195_webgnisdb";

  // Create connection
  $conn = new mysqli($servername, $username, $password, $database);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Function to sanitize input data
  function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
  }

 // Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $requestId = sanitize_input($_POST['requestId']);
    $email = sanitize_input($_POST['email']);

    // Prepare SQL statement to check in completed_request table
    $sql_completed = "SELECT * FROM completed_request WHERE ticketID = '$requestId' AND email = '$email'";
    $result_completed = $conn->query($sql_completed);

    if ($result_completed->num_rows > 0) {
        // Request found in completed_request table
        $row = $result_completed->fetch_assoc(); // Fetch the row data
    
        // Display details and status in the status container
        echo "<div class='status-container' id='statusContainer' style='background-color: #e0f2f1; /* Toned down background color */'>";
        echo "<p style='color: #2e7d32;'>Request is completed.</p>"; /* Dark green text color */
        echo "<p>ID: " . $row['ticketID'] . "</p>";
        echo "<p>Client Name: " . $row['client'] . "</p>";
        echo "<p>Email Address: " . $row['email'] . "</p>";
        echo "<p>Stations:</p>";
        echo "<ul>";
        // Parse and display stat_names as bullet points
        $stat_names = explode(",", $row['stat_names']);
        foreach ($stat_names as $stat_name) {
            echo "<li>$stat_name</li>";
        }
        echo "</ul>";
        
        // Add the hyperlink to download the certificate PDF file
        $certificateFileName = $row['ticketID'] . ".pdf";
        $certificateFilePath = "certificates/$certificateFileName"; // Assuming the certificates folder is under the current working directory
        if (file_exists($certificateFilePath)) {
            $fileSize = filesize($certificateFilePath);
            $fileSizeFormatted = $fileSize < 1024 ? $fileSize . " B" : ($fileSize < 1048576 ? round($fileSize / 1024, 2) . " KB" : round($fileSize / 1048576, 2) . " MB");
            echo "<p><img src='paperclip.png' alt='Attachment' style='vertical-align: middle; height: 20px;'> <a href='$certificateFilePath' download>" . $certificateFileName . "</a> ($fileSizeFormatted)</p>";
        } else {
            echo "<p>Certificate not found.</p>";
        }
        
        echo "</div>";
    } 
    
    
    else {
        // Prepare SQL statement to check in test_request table
        $sql_pending = "SELECT * FROM gcp_request WHERE ticketID = '$requestId' AND email = '$email'";
        $result_pending = $conn->query($sql_pending);

        if ($result_pending->num_rows > 0) {
            // Request found in test_request table
            $row = $result_pending->fetch_assoc(); // Fetch the row data

            // Display details and status in the status container
            echo "<div class='status-container' id='statusContainer' style='background-color: #ffe0b2; /* Toned down background color */'>";
            echo "<p style='color: #e65100;'>Request is pending.</p>"; /* Orange text color */
            echo "<p>ID: " . $row['ticketID'] . "</p>";
            echo "<p>Client Name: " . $row['client'] . "</p>";
            echo "<p>Email Address: " . $row['email'] . "</p>";
            echo "<p>Stations:</p>";
            echo "<ul>";
            // Parse and display stat_names as bullet points
            $stat_names = explode(",", $row['stat_names']);
            foreach ($stat_names as $stat_name) {
                echo "<li>$stat_name</li>";
            }
            echo "</ul>";
            echo "</div>";
        } else {
            // Request not found in either table
            echo "<div class='status-container' id='statusContainer' style='background-color: #ffcdd2; /* Toned down background color */'>";
            echo "<p style='color: #c62828;'>Request not found.</p>"; /* Red text color */
            echo "</div>";
        }
    }
}


  // Close connection
  $conn->close();
  ?>

<script>
    /* JavaScript for dropdown functionality */
    document.querySelector('.dropdown').addEventListener('mouseover', function() {
        document.querySelector('.dropdown-content').style.display = 'block';
    });

    document.querySelector('.dropdown').addEventListener('mouseout', function() {
        document.querySelector('.dropdown-content').style.display = 'none';
    });
</script>

</body>
</html>

