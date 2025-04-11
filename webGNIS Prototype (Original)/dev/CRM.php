<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNIS | Requests Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> 
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


        .hoverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 0px 10px 0px rgba(0, 0, 0, 0.1);
        }
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 200px;
            height: 10px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-box input[type="button"] {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        .login-box input[type="button"]:hover {
            background-color: #45a049;
        }
        .login-button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 25px;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .login-button:hover {
            background-color: #45a049;
        }

        .container {
            max-width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h3 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 8px;
        }

        .button:hover {
            background-color: #45a049;
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 999;
            max-height: 80%; /* Maximum height of the popup */
            overflow-y: auto; /* Enable vertical scroll if content exceeds the max-height */
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        .selected-table {
            margin-top: 0px;
        }

        .search-container {
            text-align: right;
            margin-bottom: 10px;
            margin-top: 10px;
        }

        .search-container input[type=text] {
            padding: 5px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .show-entries {
            margin-top: 10px;
            float: left;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 20px;
            color: #aaa;
        }

        .close-btn:hover {
            color: #333;
        }

        .disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .notification {
            color: green;
            font-size: 14px;
            margin-left: 10px;
        }

        /* Scrollable table body */
        .scrollable-table tbody {
            display: block;
            max-height: 300px; /* Adjust the height as needed */
            overflow-y: scroll;
        }

        .scrollable-table thead, .scrollable-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
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

<div id="hoverlay" class="hoverlay">
    <div class="login-box">
        <h3>Login</h3>
        <form>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" maxlength="20"><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" maxlength="20"><br><br>
            <button type="button" onclick="submitLogin()" class="login-button">Login</button>
        </form>
    </div>
</div>

<div class="container">

    <h1>Certificate Request Management</h1>
    <h2 style="margin-bottom:-37px">Pending Requests</h2>
    
    <div class="search-container">
        <input type="text" id="searchInput" onkeyup="searchTable('requestsTable')" placeholder="Search for requests...">
    </div>

    <table id="requestsTable" class="scrollable-table">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Request Date</th>
                <th>Client Name</th>
                <th>Email</th>
                <th>Requested Stations</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // MySQL server configuration
        $servername = "sql105.infinityfree.com"; // Your MySQL server name (usually localhost)
        $username = "if0_36589195"; // Your MySQL username
        $password = "Himpapawid11"; // Your MySQL password
        $dbname = "if0_36589195_webgnisdb"; // Your database name
        $table = "gcp_request"; // Your table name

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Select data from table
        $sql = "SELECT * FROM $table";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Output data of each row
            while($row = $result->fetch_assoc()) {
                echo "<tr ondblclick='addToSelected()'>
                        <td>" . $row["ticketID"] . "</td>
                        <td>" . $row["request_date"] . "</td>
                        <td>" . $row["client"] . "</td>
                        <td>" . $row["email"] . "</td>
                        <td>" . $row["stat_names"] . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>0 results</td></tr>";
        }
        $conn->close();
        ?>
        </tbody>
    </table>
    
    <h3></h3>
    <hr>

    <div class="selected-table">
        <h2 style="margin-bottom:-37px">Selected Requests</h2>

        <div class="search-container">
            <input type="text" id="searchInput2" onkeyup="searchTable('selectedTable')" placeholder="Search for selected requests...">
        </div>

        <table id="selectedTable" class="scrollable-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Request Date</th>
                    <th>Client Name</th>
                    <th>Email</th>
                    <th>Requested Stations</th>
                </tr>
            </thead>
            <tbody>
            <!-- Populate selected requests dynamically -->
            </tbody>
        </table>
    </div>

    <div class="button-container">
        <button class="button" onclick="processRequests()">Process Request</button>
    </div>

    <div id="overlay" class="overlay"></div>

    <div id="popup" class="popup">
        <span class="close-btn" onclick="togglePopup()">&#10006;</span>
        <h2>Selected Request Details</h2>
        <div id="popup-content"></div>
    </div>
</div>

<script>
window.onload = function() {
    document.getElementById('hoverlay').style.display = 'flex';
};

function submitLogin() {
    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;
    if (username === 'admin' && password === '12345') {
        alert('Login successful!');
        // You can redirect or perform further actions upon successful login
        document.getElementById('hoverlay').style.display = 'none';
    } else {
        alert('Invalid username or password. Please try again.');
    }
}

var selectedRequests = []; // Array to store selected request IDs

function togglePopup() {
    var popup = document.getElementById("popup");
    var overlay = document.getElementById("overlay");
    popup.style.display = popup.style.display === "block" ? "none" : "block";
    overlay.style.display = overlay.style.display === "block" ? "none" : "block";
}

function searchTable(tableId) {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById(tableId === 'requestsTable' ? "searchInput" : "searchInput2");
    filter = input.value.toUpperCase();
    table = document.getElementById(tableId);
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td");
        if (td.length > 0) {
            let found = false;
            for (let j = 0; j < td.length; j++) {
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? "" : "none";
        }
    }
}

function addToSelected(ticketID) {
    if (!selectedRequests.includes(ticketID)) {
        selectedRequests.push(ticketID);
        var table = document.getElementById("requestsTable");
        var rows = table.getElementsByTagName("tr");
        for (var i = 0; i < rows.length; i++) {z
            var cols = rows[i].getElementsByTagName("td");
            if (cols.length > 0 && cols[0].innerText === ticketID) {
                var newRow = rows[i].cloneNode(true);
                newRow.ondblclick = function() { removeFromSelected(ticketID); };
                document.getElementById("selectedTable").getElementsByTagName("tbody")[0].appendChild(newRow);
                break;
            }
        }
    }
}

function removeFromSelected(ticketID) {
    var table = document.getElementById("selectedTable");
    var rows = table.getElementsByTagName("tr");
    for (var i = 0; i < rows.length; i++) {
        var cols = rows[i].getElementsByTagName("td");
        if (cols.length > 0 && cols[0].innerText === ticketID) {
            rows[i].parentNode.removeChild(rows[i]);
            break;
        }
    }
    selectedRequests = selectedRequests.filter(id => id !== ticketID);
}

function updateSelectedTable() {
    var table = document.getElementById("selectedTable").getElementsByTagName('tbody')[0];
    table.innerHTML = ""; // Clear the table

    // Re-populate the selected table
    for (var i = 0; i < selectedRequests.length; i++) {
        var request = getRequestById(selectedRequests[i]);
        if (request) {
            var row = table.insertRow();
            row.insertCell().textContent = request.ticketID;
            row.insertCell().textContent = request.request_date;
            row.insertCell().textContent = request.client;
            row.insertCell().textContent = request.email;
            row.insertCell().textContent = request.stat_names;
            row.ondblclick = function() {
                removeFromSelected(this.cells[0].textContent);
            };
        }
    }
}

function getRequestById(id) {
    var table = document.getElementById("requestsTable");
    var rows = table.getElementsByTagName("tr");
    for (var i = 1; i < rows.length; i++) {
        var rowData = rows[i].getElementsByTagName("td");
        if (rowData[0].textContent === id) {
            return {
                ticketID: rowData[0].textContent,
                client: rowData[2].textContent,
                email: rowData[3].textContent,
                stat_names: rowData[4].textContent
            };
        }
    }
    return null;
}

function processRequests() {
    var popupContent = document.getElementById("popup-content");
    popupContent.innerHTML = ""; // Clear previous content

    for (var i = 0; i < selectedRequests.length; i++) {
        var request = getRequestById(selectedRequests[i]);
        if (request) {
            var clientName = request.client;
            var emailAdd = request.email;
            var ticketID = request.ticketID;
            var statNames = request.stat_names.split(', '); // Assuming station names are separated by comma and space

            // Create a table for each client
            var clientTable = document.createElement('table');
            clientTable.innerHTML = "<thead><tr><th>Province</th><th>Station Name</th><th>Municipal</th><th>Barangay</th><th>Latitude</th><th>Longitude</th></thead>";
            var clientTbody = document.createElement('tbody');

            // Fetch station details for each station name
            statNames.forEach(function(statName) {
                var stations = getStationDetails(statName);
                stations.forEach(function(station) {
                    var row = clientTbody.insertRow();
                    row.insertCell().textContent = station.province;
                    row.insertCell().textContent = station.stat_name;
                    row.insertCell().textContent = station.municipal;
                    row.insertCell().textContent = station.barangay;
                    row.insertCell().textContent = station.N84dd;
                    row.insertCell().textContent = station.E84dd;
                });
            });

            clientTable.appendChild(clientTbody);

            // Add the table to popup content
            popupContent.appendChild(document.createElement('hr'));
            popupContent.appendChild(document.createElement('br'));
            popupContent.appendChild(document.createTextNode("Client: " + clientName));
            popupContent.appendChild(clientTable);

            // Create and append the "Generate PDF" button for each client
            var generateButton = document.createElement('button');
            generateButton.className = 'button';
            generateButton.textContent = 'Generate PDF';
            generateButton.onclick = (function(clientName, ticketID, emailAdd, button) {
                return function() {
                    generatePDF(clientName, ticketID, emailAdd, button);
                };
            })(clientName, ticketID, emailAdd, generateButton);
            popupContent.appendChild(generateButton);
        }
    }

    togglePopup(); // Show the popup
}

document.addEventListener('DOMContentLoaded', function() {
    changeEntries('requestsTable');
    changeEntries('selectedTable');
});

function getStationDetails(statName) {
    var xhttp = new XMLHttpRequest();
    var stations = [];
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText);
            stations = response;
        }
    };
    xhttp.open("GET", "getStationDetails.php?stat_name=" + statName, false);
    xhttp.send();
    return stations;
}

function fetchUpdatedData() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText);
            updateRequestsTable(response);
        }
    };
    xhttp.open("GET", "fetchRequests.php", true);
    xhttp.send();
}

function updateRequestsTable(data) {
    var table = document.getElementById("requestsTable").getElementsByTagName('tbody')[0];
    table.innerHTML = ""; // Clear the table

    // Populate the table with the updated data
    for (var i = 0; i < data.length; i++) {
        var row = table.insertRow();
        row.ondblclick = function() {
            addToSelected(this.cells[0].textContent);
        };
        row.insertCell().textContent = data[i].ticketID;
        row.insertCell().textContent = data[i].request_date;
        row.insertCell().textContent = data[i].client;
        row.insertCell().textContent = data[i].email;
        row.insertCell().textContent = data[i].stat_names;
    }
}

// Call fetchUpdatedData function to update the table in real-time
setInterval(fetchUpdatedData, 5000); // Fetch updated data every 5 seconds

function generatePDF(clientName, ticketID, emailAdd, button) {
    // Get data for each station
    var stationsData = [];
    var selectedRequest = getRequestById(ticketID);

    if (selectedRequest) {
        var statNames = selectedRequest.stat_names.split(', '); // Assuming station names are separated by comma and space

        statNames.forEach(function(statName) {
            var stations = getStationDetails(statName);
            stations.forEach(function(station) {
                stationsData.push({
                    clientName: clientName,
                    emailAdd: emailAdd,
                    ticketID: ticketID,
                    statName: station.stat_name,
                });
            });
        });

        // Send data to PHP script to insert into completed_request table
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText); // Log response for debugging

                // Disable the button and show the notification
                button.classList.add('disabled');
                button.disabled = true;
                button.textContent = 'Request Completed';

                var notification = document.createElement('span');
                notification.className = 'notification';
                notification.textContent = 'Request completed';
                button.parentNode.insertBefore(notification, button.nextSibling);

                // Fetch the updated data to reflect the changes
                fetchUpdatedData();

                // Remove the processed request from the Selected Table
                removeFromSelected(ticketID);
            }
        };
        xhttp.open("POST", "generatePDF.php", true);
        xhttp.setRequestHeader("Content-type", "application/json");
        xhttp.send(JSON.stringify(stationsData));
    }
}

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

