<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNIS | Points Management</title>
    <link rel="icon" href="gnis_logo.png" type="image/x-icon">
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
            margin: 0;
        }
        .header h1 img {
            margin-right: 10px;
        }
        .navbar {
            display: flex;
            justify-content: center;
            background-color: #333;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            position: absolute;
            margin-top: 10px;
            margin-left: 10px;
            width: 65%; /* Container is now two-thirds the width of the screen */
            height: 71%;
            overflow-x: auto; /* Add horizontal scroll bar if needed */
            background-color: #fff; /* Background color for the container */
            border: 1px solid #ccc; /* Add a border for visual distinction */
            border-radius: 5px; /* Rounded corners for the container */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Add a subtle shadow effect */
        }
        .container-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #1e1e1e; /* Darker header color */
            color: #ddd; /* Light text color */
            padding: 10px; /* Add padding to the header */
            border-top-left-radius: 5px; /* Rounded corners for the top-left and top-right */
            border-top-right-radius: 5px;
        }
        .search-bar {
            width: 290px; /* Set a fixed width for the search bar */
            padding: 6px;
            border-radius: 3px;
            border: 1px solid #ccc;
            font-size: 12px;
        }
        .table-container {
            width: 100%;
            overflow-y: auto; /* Add vertical scroll bar for the table */
            max-height: 100%; /* Adjusted maximum height to account for header and padding */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Fix table layout */
        }
        th, td {
            font-size: 12px; /* Adjust the font size as needed */
            border: 1px solid #dddddd;
            padding: 8px; /* Increase padding */
            text-align: left;
            word-wrap: break-word; /* Ensure long words break and wrap to fit the cell */
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            position: sticky; /* Make the header sticky */
            top: -1px; /* Set top to 0 to keep it at the top */
            //z-index: 10; /* Ensure the header is above other elements */
        }
        tr:nth-child(even) {
            background-color: #f9f9f9; /* Zebra striping for rows */
        }
        tr:hover {
            background-color: #e9e9e9; /* Highlight row on hover */
        }
        .action-links a {
            margin-right: 10px; /* Space between Edit and Delete links */
            text-decoration: none; /* Remove underline from links */
        }
        .add-button {
            padding: 6px 10px;
            border-radius: 3px;
            background-color: #45a049; /* Green color */
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            margin-right: 10px; /* Add some space between the search bar and the button */
            border: 1px outset #013220; /* Stronger outline */
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2); /* Stronger shadow */
        }

        .add-button:hover {
            background-color: #45a049;
        }

        .side-tab {

            position: absolute;
            top: 30%;
            right: 0%;
            margin-top: 10px;
            width: 30%;
            height: 71%;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);

            padding: 20px;

            overflow-x: auto;
            overflow-y: auto;
            border-left: 2px solid #ccc; /* Add border to the left */
            transform: translateY(-50%); /* Center the side-tab vertically */
            //z-index: 1; /* Adjust the z-index to make sure it's above other content */
        }

        .container2 {
            height: 65.5%;
            right: 0%;
            position: absolute;
            margin-top: 10px;
            /* margin-left: 10px; */
            width: 30%;
            overflow-x: auto;
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .input-group {
            margin-bottom: 15px;
            border: 1px solid #ddd; /* Add border around each input group */
            border-radius: 5px; /* Add border radius for a rounded appearance */
            padding: 10px; /* Add padding inside each input group */
            background-color: #f9f9f9; /* Light gray background color */
        }

        .input-group h3 {
            text-align: center;
            margin-top: -10px;
            padding: 5px;
        }

        .input-group h4 {
            text-align: center;
        }

        .input-group label {
            font-size: 12px; /* Adjust the font size as needed */
            display: inline-block;
            width: 120px;
            font-weight: bold;
        }

        .input-group input[type="text"],
        .input-group textarea {
            width: calc(100% - 130px - 2px); /* Adjust input width to accommodate label width and borders */
            padding: 4px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 14px;
            box-sizing: border-box; /* Include padding and border in width calculation */
        }

        .input-group textarea {
            resize: vertical; /* Allow vertical resizing */
        }

        .input-group button {
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            background-color: #45a049; /* Green color */
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }

        .input-group button:hover {
            background-color: #3c8942; /* Darker green color on hover */
        }

        /* Add a margin between input groups */
        .input-group:not(:last-child) {
            margin-bottom: 20px;
        }

        .input-group inline-form {
            display: flex;
            align-items: center;
            gap: 10px; /* Adjust the gap as needed */
        }

        .side-tab-header {
            display: flex;
            justify-content: space-between; /* Align items horizontally with space between them */
            margin-bottom: 20px; /* Add some space between the title and the form */
            position: sticky; /* Make the header sticky */
            top: -20px; /* Stick it to the top */
            background-color: #fff; /* Background color for the header */
            padding: 15px 20px; /* Add padding to the header */
            z-index: 10; /* Ensure the header is above other elements */
        }

        .side-tab-title {
            font-size: 18px;
            font-weight: bold;
        }

        .save-button {
            position: sticky; /* Make the save button sticky */
            top: 10px; /* Stick it to the top */
            right: 10px; /* Align it to the right */
            padding: 8px 15px; /* Add padding to the button */
            border: none; /* Remove border */
            border-radius: 3px; /* Add border radius */
            background-color: #45a049; /* Green color */
            color: #fff; /* Text color */
            font-size: 14px; /* Font size */
            cursor: pointer; /* Cursor style */
            z-index: 10; /* Ensure the button is above other elements */
        }

        .emphasized {
        border: 5px solid #4CAF50; /* Add a green border for emphasis */
        box-shadow: 0 0 10px rgba(76, 175, 80, 0.8); /* Increase the size and intensity of the glow effect */
        transition: border 0.3s, box-shadow 0.3s; /* Add transition for smooth effect */

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
            <a href="explorer_horizontal.php">Horizontal Control Points</a>
            <a href="explorer_vertical.php">Vertical Control Points</a>
        </div>
    </div>
    <a href="tracker_certificates.php"><i class="fas fa-chart-line"></i> Tracker</a>
    <a href="simulator_gcps.php"><i class="fas fa-cogs"></i> Simulator</a>
    <div class="dropdown">
        <a href="#" id="managementLink"><i class="fas fa-tasks"></i> Management <i class="fas fa-caret-down"></i></a>
        <div class="dropdown-content">
            <a href="management_certificates.php">Certificates</a>
            <a href="management_gcps.php">Control Points</a>
        </div>
    </div>
    <a href="about.html"><i class="fas fa-info-circle"></i> About Us</a>
</div>



    <div class="container">
        <div class="container-header">
            <div style="color: #fff;">Points Database</div>
            <div style="display: flex; align-items: center;">
                <a href="#" class="add-button">&#43; Add</a>
                <input type="text" class="search-bar" id="searchInput" onkeyup="searchTable()" placeholder="Search...">
            </div>
        </div> <!-- Header -->
        <div class="table-container">
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

            // SQL query to select specific columns from gcp_table
            $sql = "SELECT stat_name, N84dd, E84dd, order_acc, island, region, province, municipal, barangay FROM gcp_table";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo '<table id="dataTable">';
                echo '<tr>';
                echo '<th style="width : 30%;">Action</th>'; // Action header
                echo '<th style="width: 12%;">Station</th>'; // Renamed from stat_name
                echo '<th style="width: 15%;">Latitude</th>'; // Renamed from N84dd
                echo '<th style="width: 16%;">Longitude</th>'; // Renamed from E84dd
                echo '<th style="width: 10%;">Order</th>'; // Renamed from order_acc
                echo '<th style="width: 10%;">Island</th>'; // Unchanged
                echo '<th style="width: 12%;">Region</th>'; // Unchanged
                echo '<th style="width: 22%;">Municipality</th>'; // Renamed from municipal
                echo '<th style="width: 12%;">Barangay</th>'; // Unchanged
                echo '</tr>';

                // Output data of each row
                while($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td class="action-links"><a href="#" onclick="editRow(\'' . htmlspecialchars($row['stat_name']) . '\')">✏️ Edit</a><a href="#" onclick="deleteRow(\'' . htmlspecialchars($row['stat_name']) . '\')">⛔ Delete</a></td>';
                    echo '<td>' . htmlspecialchars($row['stat_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['N84dd']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['E84dd']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['order_acc']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['island']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['region']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['municipal']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['barangay']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo "0 results";
            }

            $conn->close();
            ?>
        </div>
    </div>

    <div class="container2">
        <div class="side-tab-header">
        <div class="side-tab-title">GCP Information</div>
        <button class="save-button">Save</button>
    </div>

    <form>
        <!-- First Three Lines -->
        <div class="input-group">
            <h3>Station Information</h3>
            <label for="id">ID:</label>
            <input type="text" id="id" name="id" disabled>
            <label for="stat_name">Station Name:</label>
            <input type="text" id="stat_name" name="stat_name" disabled>
            <label for="status">Status:</label>
            <input type="text" id="status" name="status" disabled>
            <label for="order_acc">Order of Accuracy:</label>
            <input type="text" id="order_acc" name="order_acc" disabled>
            <label for="accuracy_class">Accuracy Class (CM):</label>
            <input type="text" id="accuracy_class" name="accuracy_class" disabled>
        </div>

        <!-- Address -->
        <div class="input-group">
            <h3>Address</h3>
            <label for="island">Island:</label>
            <input type="text" id="island" name="island" disabled>
            <label for="region">Region:</label>
            <input type="text" id="region" name="region" disabled>
            <label for="province">Province:</label>
            <input type="text" id="province" name="province" disabled>
            <label for="municipal">Municipal:</label>
            <input type="text" id="municipal" name="municipal" disabled>
            <label for="barangay">Barangay:</label>
            <input type="text" id="barangay" name="barangay" disabled>
        </div>

        <!-- PRS92 -->
        <div class="input-group">
            <h3>PRS92 Information</h3>
            <h4>DMS Coordinates</h4>
            <form class="inline-form">
                <label for="n92d">Latitude:</label>
                <input type="text" id="n92d" name="n92d" style="width: 40px" disabled><a>° </a>
                <input type="text" id="n92m" name="n92m" style="width: 40px" disabled><a>' </a>
                <input type="text" id="n92s" name="n92s" style="width: 60px" disabled><a>" </a>
            </form>

            <form class="inline-form">
                <label for="e92d">Longitude:</label>
                <input type="text" id="e92d" name="e92d" style="width: 40px" disabled><a>° </a>
                <input type="text" id="e92m" name="e92m" style="width: 40px" disabled><a>' </a>
                <input type="text" id="e92s" name="e92s" style="width: 60px" disabled><a>" </a>
            </form>
            
            <label for="h92">Ellipsoidal Height:</label>
            <input type="text" id="h92" name="h92" disabled>

            <br><hr>

            <h4>PTM Coordinates</h4>
            <label for="n92ptm">Northings:</label>
            <input type="text" id="n92ptm" name="n92ptm" disabled>
            <label for="e92ptm">Eastings:</label>
            <input type="text" id="e92ptm" name="e92ptm" disabled>
            <label for="z92">Zone:</label>
            <input type="text" id="z92" name="z92" disabled>

            <br><hr>

            <h4>UTM Coordinates</h4>
            <label for="n92utm">Northings:</label>
            <input type="text" id="n92utm" name="n92utm" disabled>
            <label for="e92utm">Eastings:</label>
            <input type="text" id="e92utm" name="e92utm" disabled>
            <label for="z92utm">Zone:</label>
            <input type="text" id="z92utm" name="z92utm" disabled>
        </div>

        <!-- WGS84 -->
        <div class="input-group">
            <h3>WGS84 Information</h3>
            <label for="n84dd">Decimal Latitude:</label>
            <!--<p type="text" id="n84dd" name="n84dd"></p>-->
            <input type="text" id="n84dd" name="n84dd" disabled>
            <label for="e84dd">Decimal Longitude:</label>
            <!--<p type="text" id="e84dd" name="e84dd"></p>-->
            <input type="text" id="e84dd" name="e84dd" disabled>

            <form class="inline-form">
                <label for="n84d">Latitude:</label>
                <input type="text" id="n84d" name="n84d" style="width: 40px" disabled><a>° </a>
                <input type="text" id="n84m" name="n84m" style="width: 40px" disabled><a>' </a>
                <input type="text" id="n84s" name="n84s" style="width: 60px" disabled><a>" </a>
            </form>

            <form class="inline-form">
                <label for="e84d">Longitude:</label>
                <input type="text" id="e84d" name="e84d" style="width: 40px" disabled><a>° </a>
                <input type="text" id="e84m" name="e84m" style="width: 40px" disabled><a>' </a>
                <input type="text" id="e84s" name="e84s" style="width: 60px" disabled><a>" </a>
            </form>
            
            <label for="h84">Ellipsoidal Height:</label>
            <input type="text" id="h84" name="h84" disabled>

            <br><hr>

            <h4>UTM Coordinates</h4>
            <label for="n84utm">Northings:</label>
            <input type="text" id="n84utm" name="n84utm" disabled>
            <label for="e84utm">Eastings:</label>
            <input type="text" id="e84utm" name="e84utm" disabled>
            <label for="z84utm">Zone:</label>
            <input type="text" id="z84utm" name="z84utm" disabled>
        </div>

        <!-- Description -->
        <div class="input-group">
            <label for="descripts">Description:</label>
            <!-- Adjusted input box for descripts -->
            <textarea id="descripts" name="descripts" rows="4" style="resize: vertical;" disabled></textarea>
        </div>

    </form>


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

        function dmsToDecimal(degrees, minutes, seconds) {
            return degrees + (minutes / 60) + (seconds / 3600);
        }

        function updateDecimalValues() {
            const nd = parseFloat(document.getElementById('n84d').value) || 0;
            const nm = parseFloat(document.getElementById('n84m').value) || 0;
            const ns = parseFloat(document.getElementById('n84s').value) || 0;
            const ed = parseFloat(document.getElementById('e84d').value) || 0;
            const em = parseFloat(document.getElementById('e84m').value) || 0;
            const es = parseFloat(document.getElementById('e84s').value) || 0;

            const decimalLatitude = dmsToDecimal(nd, nm, ns).toFixed(6);
            const decimalLongitude = dmsToDecimal(ed, em, es).toFixed(6);

            document.getElementById('n84dd').textContent = decimalLatitude;
            document.getElementById('e84dd').textContent = decimalLongitude;
        }

        /*document.querySelectorAll('#n84d, #n84m, #n84s, #e84d, #e84m, #e84s').forEach(input => {
            input.addEventListener('input', updateDecimalValues);
        });*/

        function searchTable() {
            // Declare variables
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("dataTable");
            tr = table.getElementsByTagName("tr");

            // Loop through all table rows, and hide those who don't match the search query
            for (i = 0; i < tr.length; i++) {
                // Start loop from the second column (index 1) to exclude the action column
                for (var j = 1; j < tr[i].cells.length; j++) {
                    td = tr[i].getElementsByTagName("td")[j]; // Get the cell of the current column
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break; // Break the loop if a match is found in any column
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            }
        }


        function editRow(stationName) {
    // Create an AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "management_g_fetchdata.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Define what happens on successful data submission
    xhr.onload = function() {
        if (xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            if (data.error) {
                alert(data.error);
            } else {
                // Populate the form with the fetched data
                document.getElementById('id').value = data.id;
                document.getElementById('stat_name').value = data.stat_name;
                document.getElementById('status').value = data.status;
                document.getElementById('island').value = data.island;
                document.getElementById('region').value = data.region;
                document.getElementById('province').value = data.province;
                document.getElementById('municipal').value = data.municipal;
                document.getElementById('barangay').value = data.barangay;
                document.getElementById('n84dd').value = data.N84dd;
                document.getElementById('e84dd').value = data.E84dd;
                document.getElementById('order_acc').value = data.order_acc;
                document.getElementById('accuracy_class').value = data.accuracy_class;
                document.getElementById('n92d').value = data.N92d;
                document.getElementById('n92m').value = data.N92m;
                document.getElementById('n92s').value = data.N92s;
                document.getElementById('e92d').value = data.E92d;
                document.getElementById('e92m').value = data.E92m;
                document.getElementById('e92s').value = data.E92s;
                document.getElementById('h92').value = data.H92;
                document.getElementById('n92ptm').value = data.N92ptm;
                document.getElementById('e92ptm').value = data.E92ptm;
                document.getElementById('z92').value = data.Z92;
                document.getElementById('n84d').value = data.N84d;
                document.getElementById('n84m').value = data.N84m;
                document.getElementById('n84s').value = data.N84s;
                document.getElementById('e84d').value = data.E84d;
                document.getElementById('e84m').value = data.E84m;
                document.getElementById('e84s').value = data.E84s;
                document.getElementById('h84').value = data.H84;
                document.getElementById('descripts').value = data.descripts;
                document.getElementById('e92utm').value = data.E92utm;
                document.getElementById('n92utm').value = data.N92utm;
                document.getElementById('z92utm').value = data.Z92utm;
                document.getElementById('e84utm').value = data.E84utm;
                document.getElementById('n84utm').value = data.N84utm;
                document.getElementById('z84utm').value = data.Z84utm;

                // Enable all input fields except ID
                var inputFields = document.querySelectorAll('.input-group input, .input-group textarea');
                inputFields.forEach(function(field) {
                    if (field.id !== 'id') {
                        field.disabled = false;
                        field.classList.add('emphasized'); // Add a class for emphasis
                    }
                });
            }
        } else {
            alert("Error fetching data");
        }
    };

    // Send the request with the station name
    xhr.send("station_name=" + encodeURIComponent(stationName));
}


function addRow() {
    // Enable all input fields except ID and clear their values
    var inputFields = document.querySelectorAll('.input-group input, .input-group textarea');
    inputFields.forEach(function(field) {
        if (field.id !== 'id') {
            field.disabled = false;
            field.value = ""; // Clear the value
            field.classList.add('emphasized'); // Add a class for emphasis
        }
    });

    // Set ID field as blank and disable it
    document.getElementById("id").value = "";
    document.getElementById("id").disabled = true;
}



function deleteRow(stationName) {
    // Confirm deletion
    if (confirm("Are you sure you want to delete the row for station: " + stationName + "?")) {
        // Create an AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "management_g_deletedata.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Define what happens on successful data submission
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert(xhr.responseText); // Show response from server
                // Reload the page after successful deletion
                location.reload();
            } else {
                alert("Error deleting data");
            }
        };

        // Send the request with the station name to be deleted
        xhr.send("station_name=" + encodeURIComponent(stationName));
    }
}


// Bind the addRow function to the Add button
document.querySelector('.add-button').addEventListener('click', addRow);


    document.querySelector('.save-button').addEventListener('click', function() {
    // Get the data from input fields
    var id = document.getElementById('id').value; // Assuming you already have this populated
    var stat_name = document.getElementById('stat_name').value;
    var status = document.getElementById('status').value;
    var island = document.getElementById('island').value;
    var region = document.getElementById('region').value;
    var province = document.getElementById('province').value;
    var municipal = document.getElementById('municipal').value;
    var barangay = document.getElementById('barangay').value;
    var n84dd = document.getElementById('n84dd').value;
    var e84dd = document.getElementById('e84dd').value;
    var order_acc = document.getElementById('order_acc').value;
    var accuracy_class = document.getElementById('accuracy_class').value;
    var n92d = document.getElementById('n92d').value;
    var n92m = document.getElementById('n92m').value;
    var n92s = document.getElementById('n92s').value;
    var e92d = document.getElementById('e92d').value;
    var e92m = document.getElementById('e92m').value;
    var e92s = document.getElementById('e92s').value;
    var h92 = document.getElementById('h92').value;
    var n92ptm = document.getElementById('n92ptm').value;
    var e92ptm = document.getElementById('e92ptm').value;
    var z92 = document.getElementById('z92').value;
    var n84d = document.getElementById('n84d').value;
    var n84m = document.getElementById('n84m').value;
    var n84s = document.getElementById('n84s').value;
    var e84d = document.getElementById('e84d').value;
    var e84m = document.getElementById('e84m').value;
    var e84s = document.getElementById('e84s').value;
    var h84 = document.getElementById('h84').value;
    var descripts = document.getElementById('descripts').value;
    var e92utm = document.getElementById('e92utm').value;
    var n92utm = document.getElementById('n92utm').value;
    var z92utm = document.getElementById('z92utm').value;
    var e84utm = document.getElementById('e84utm').value;
    var n84utm = document.getElementById('n84utm').value;
    var z84utm = document.getElementById('z84utm').value;

    // Create an AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "management_g_updatedata.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Define what happens on successful data submission
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert(xhr.responseText); // Show response from server
            // Reload the page after successful update
            location.reload();
        } else {
            alert("Error updating data");
        }
    };

    // Send the request with the data to be updated
    xhr.send("id=" + encodeURIComponent(id) + "&stat_name=" + encodeURIComponent(stat_name) + "&status=" + encodeURIComponent(status) + "&island=" + encodeURIComponent(island) + "&region=" + encodeURIComponent(region) + "&province=" + encodeURIComponent(province) + "&municipal=" + encodeURIComponent(municipal) + "&barangay=" + encodeURIComponent(barangay) + "&n84dd=" + encodeURIComponent(n84dd) + "&e84dd=" + encodeURIComponent(e84dd) + "&order_acc=" + encodeURIComponent(order_acc) + "&accuracy_class=" + encodeURIComponent(accuracy_class) + "&n92d=" + encodeURIComponent(n92d) + "&n92m=" + encodeURIComponent(n92m) + "&n92s=" + encodeURIComponent(n92s) + "&e92d=" + encodeURIComponent(e92d) + "&e92m=" + encodeURIComponent(e92m) + "&e92s=" + encodeURIComponent(e92s) + "&h92=" + encodeURIComponent(h92) + "&n92ptm=" + encodeURIComponent(n92ptm) + "&e92ptm=" + encodeURIComponent(e92ptm) + "&z92=" + encodeURIComponent(z92) + "&n84d=" + encodeURIComponent(n84d) + "&n84m=" + encodeURIComponent(n84m) + "&n84s=" + encodeURIComponent(n84s) + "&e84d=" + encodeURIComponent(e84d) + "&e84m=" + encodeURIComponent(e84m) + "&e84s=" + encodeURIComponent(e84s) + "&h84=" + encodeURIComponent(h84) + "&descripts=" + encodeURIComponent(descripts) + "&e92utm=" + encodeURIComponent(e92utm) + "&n92utm=" + encodeURIComponent(n92utm) + "&z92utm=" + encodeURIComponent(z92utm) + "&e84utm=" + encodeURIComponent(e84utm) + "&n84utm=" + encodeURIComponent(n84utm) + "&z84utm=" + encodeURIComponent(z84utm));
});

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

