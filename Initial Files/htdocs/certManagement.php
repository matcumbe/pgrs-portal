<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-image: linear-gradient(to bottom, #ffffff, #f0f0f0);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            color: #555;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        button {
            padding: 12px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: block;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background-color: #45a049;
        }

        /* Popup styling */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            z-index: 999;
        }

        .popup h2 {
            margin-top: 0;
            color: #333;
        }

        .popup p {
            color: #555;
        }

        .popup button {
            padding: 10px 20px;
            font-size: 14px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: block;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .popup button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pending Requests</h1>
        <table id="pendingTable">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Request Date</th>
                    <th>Client Name</th>
                    <th>Email Address</th>
                    <th>Stations</th>
                </tr>
            </thead>
            <tbody>
            <!-- PHP code to fetch and display data from MySQL table "gcp_request" -->
            <?php
            // Database configuration
            $servername = "sql105.infinityfree.com";
            $username = "if0_36589195";
            $password = "Himpapawid11";
            $dbname = "if0_36589195_webgnisdb";

            // Connect to MySQL database
            $conn = mysqli_connect($servername, $username, $password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Query to fetch data from "gcp_request" table
            $sql = "SELECT ticketID, request_date, client, email, stat_names FROM gcp_request";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["ticketID"] . "</td>";
                    echo "<td>" . $row["request_date"] . "</td>";
                    echo "<td>" . $row["client"] . "</td>";
                    echo "<td>" . $row["email"] . "</td>";
                    echo "<td>" . $row["stat_names"] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "0 results";
            }
            $conn->close();
            ?>
            <!-- End of PHP code -->
            </tbody>
        </table>

        <h1>Selected Requests</h1>
        <table id="selectedTable">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Request Date</th>
                    <th>Client Name</th>
                    <th>Email Address</th>
                    <th>Stations</th>
                </tr>
            </thead>
            <tbody id="selectedTableBody">
                <!-- Selected requests will be displayed here -->
            </tbody>
        </table>

        <button onclick="openPopup()">Process Request</button>
    </div>

    <!-- Popup -->
    <div id="popup" class="popup">
        <h2>Process Request</h2>
        <p>This is where you would process the selected requests.</p>
        <button onclick="closePopup()">Close</button>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listener to the container div
            document.querySelector('.container').addEventListener('click', function(event) {
                // Check if the click occurred on a row
                if (event.target.tagName === 'TD') {
                    // Check if the row belongs to the selected table
                    if (event.target.parentNode.parentNode.id === 'selectedTableBody') {
                        deselectRow(event.target.parentNode);
                    } else {
                        selectRow(event.target.parentNode);
                    }
                }
            });
        });

        function selectRow(row) {
            var selectedTable = document.getElementById("selectedTableBody");
            var clonedRow = row.cloneNode(true);
            selectedTable.appendChild(clonedRow);
            // Attach double-click listener to the newly added row
            clonedRow.addEventListener('dblclick', function() {
                deselectRow(clonedRow);
            });
        }

        function deselectRow(row) {
            var pendingTable = document.getElementById("pendingTableBody");
            var clonedRow = row.cloneNode(true);
            pendingTable.appendChild(clonedRow);
            row.parentNode.removeChild(row); // Remove the row from the selected table
        }

        function openPopup() {
            // Get selected rows from the selected table
            var selectedRows = document.getElementById("selectedTable").getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            // Get the popup element
            var popup = document.getElementById('popup');

            // Clear previous content in the popup
            popup.innerHTML = '';

            // Loop through selected rows and create a table for each
            for (var i = 0; i < selectedRows.length; i++) {
                var clientName = selectedRows[i].getElementsByTagName('td')[2].innerText; // Get Client Name
                var stations = selectedRows[i].getElementsByTagName('td')[4].innerText.split(","); // Get Stations as an array

                // Create a table for the selected request
                var tableHTML = '<h2>' + clientName + '</h2><table><thead><tr><th>Station</th><th>Order of Accuracy</th><th>Province</th><th>Municipality</th><th>Barangay</th><th>Latitude</th><th>Longitude</th></tr></thead><tbody>';

                // Fetch station information from the database and populate the table
                stations.forEach(function(station) {
                    // Query to fetch station information from "gcp_table" table
                    var sql = "SELECT * FROM gcp_table WHERE stat_name = '" + station.trim() + "'";
                    var result = <?php echo json_encode($conn->query($sql)->fetch_all(MYSQLI_ASSOC)); ?>;

                    if (result.length > 0) {
                        var stationInfo = result[0]; // Assuming station names are unique
                        tableHTML += '<tr>';
                        tableHTML += '<td>' + stationInfo['stat_name'] + '</td>';
                        tableHTML += '<td>' + stationInfo['order_acc'] + '</td>';
                        tableHTML += '<td>' + stationInfo['province'] + '</td>';
                        tableHTML += '<td>' + stationInfo['municipal'] + '</td>';
                        tableHTML += '<td>' + stationInfo['barangay'] + '</td>';
                        tableHTML += '<td>' + stationInfo['N84dd'] + '</td>';
                        tableHTML += '<td>' + stationInfo['E84dd'] + '</td>';
                        tableHTML += '</tr>';
                    }
                });

                tableHTML += '</tbody></table><button>Process</button>'; // Placeholder button
                popup.innerHTML += tableHTML; // Append the table to the popup
            }

            // Display the popup
            popup.style.display = 'block';
        }

        function closePopup() {
            // Hide the popup
            document.getElementById('popup').style.display = 'none';
        }
    </script>
</body>
</html>

