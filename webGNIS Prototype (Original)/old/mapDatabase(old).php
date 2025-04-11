<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNIS | Explorer</title>
    <!-- Include Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <!-- Include Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <!-- Include jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">

    <!-- Include DataTables JavaScript -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, Arial;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
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


        .container {
            margin: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .map-container {
            margin-bottom: 20px;
        }

        #map {
            height: 400px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }

        tr.selected {
            background-color: #cce6ff;
        }

        h2 {
            margin-top: 0;
        }

        .hidden {
            display: none;
        }

        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 9999; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            padding-top: 60px;
        }

        

        /* Modal Content/Box */
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            border-radius: 8px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
            position: relative;
        }

        /* The Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        #selectedPointsList {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        #requestCertificatesBtn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            float: right;
            margin-top: 50px;
            transition: background-color 0.3s;
        }

        #requestCertificatesBtn:hover {
            background-color: #45a049;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .form-group input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-group input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php
        // Database credentials
        $host = 'sql105.infinityfree.com';
        $dbname = 'if0_36589195_webgnisdb';
        $username = 'if0_36589195';
        $password = 'Himpapawid11';

        try {
            // Connect to the database
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Fetch data from the database
            $stmt = $conn->query("SELECT * FROM gcp_table");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // Handle database connection error
            echo "Connection failed: " . $e->getMessage();
        }
    ?>

 <div class="header">
    <h1><img src="gnis_logo.png" alt="GNIS Logo" width="60">Geodetic Network Information System (GNIS)</h1>
</div>

<div class="navbar">
    <a href="index.html"><i class="fas fa-home"></i> GNIS Home</a>
    <a href="mapDatabase.php"><i class="fas fa-map"></i> Explorer</a>
    <a href="certTrack.php"><i class="fas fa-chart-line"></i> Tracker</a>
    <a href="gcpsimulator.php"><i class="fas fa-cogs"></i> Simulator</a>
    <a href="CRM.php" id="secretTab"><i class="fas fa-tasks"></i> Management</a>
    <a href="about.html"><i class="fas fa-info-circle"></i> About Us</a>
</div>

    <div class="container map-container">
        <h2>GNIS | GCP Explorer</h2>
        <div id="map"></div>
    </div>

    <div class="container">
        <h2>Available GCPs for Metro Manila</h2>
        <p>Click to pan to the point. Double-click to select the point/s.</p>
        <table id="dataTable" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Stat Name</th>
                    <th>Region</th>
                    <th>Province</th>
                    <th>Municipal</th>
                    <th>Barangay</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr data-lat="<?php echo $row['latitude']; ?>" data-lng="<?php echo $row['longitude']; ?>">
                        <td><?php echo $row['ID']; ?></td>
                        <td><?php echo $row['stat_name']; ?></td>
                        <td><?php echo $row['region']; ?></td>
                        <td><?php echo $row['province']; ?></td>
                        <td><?php echo $row['municipal']; ?></td>
                        <td><?php echo $row['barangay']; ?></td>
                        <td><?php echo $row['latitude']; ?></td>
                        <td><?php echo $row['longitude']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="container">
        <h2>Selected GCPs</h2>
        <p>Click to pan to the point. Double-click to deselect the point/s.</p>
        <table id="selectedPointsTable" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Stat Name</th>
                    <th>Region</th>
                    <th>Province</th>
                    <th>Municipal</th>
                    <th>Barangay</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                </tr>
            </thead>
            <tbody class="hide-empty"></tbody>
        </table>
        <button id="requestCertificatesBtn">Request Certificates</button>
    </div>

    <!-- Modal for Request Certificates -->
    <div id="requestCertificatesModal" class="modal hidden">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Request Certificates</h2>
            <form id="certificateForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-group">
        <label for="clientName">Client Name:</label>
        <input type="text" id="clientName" name="clientName" required>
    </div>
    <div class="form-group">
        <label for="clientEmail">Email:</label>
        <input type="email" id="clientEmail" name="clientEmail" required>
    </div>

    <p>You will be requesting certificates for:</p>
    <ul id="selectedPointsList"></ul>

    <div class="form-group">
        <input type="submit" id="submitBtn" value="Submit">
    </div>
</form>

        </div>
    </div>

    <!-- Toggle Checkbox -->
    <div class="container" id="toggleMarkersCheckbox-container">
        <input type="checkbox" id="toggleMarkersCheckbox">
        <label for="toggleMarkersCheckbox">Toggle Selected Points</label>
    </div>



    <script>
        $(document).ready(function() {
            var selectedPointsData = []; // Array to store selected points data
            var selectedMarkers = {}; // Object to store selected markers by coordinates
            var blueMarkers = {}; // Object to store blue markers by coordinates

            $('#dataTable').DataTable({
                "pageLength": 5,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
            });

            $('#selectedPointsTable').DataTable({
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
            });

            var map = L.map('map').setView([14.6760, 121.0437], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            var markersLayer = new L.LayerGroup().addTo(map);

            // Custom marker icons
            var orangeIcon = new L.Icon({
                iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                shadowSize: [41, 41]
            });

            var blueIcon = new L.Icon({
                iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-icon.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                shadowSize: [41, 41]
            });

            // Attach event listener to table rows
            $('#dataTable tbody').on('click', 'tr', function() {
                var lat = parseFloat($(this).data('lat'));
                var lng = parseFloat($(this).data('lng'));
                map.setView([lat, lng], 15);
            });

            // Add double click event listener to add selected row to the Selected Points table
            $('#dataTable tbody').on('dblclick', 'tr', function() {
                var rowData = [];
                $(this).children('td').each(function() {
                    rowData.push($(this).text());
                });

                var exists = $('#selectedPointsTable tbody tr').filter(function() {
                    var selectedRowData = [];
                    $(this).children('td').each(function() {
                        selectedRowData.push($(this).text());
                    });
                    return JSON.stringify(selectedRowData) === JSON.stringify(rowData);
                }).length > 0;

                if (!exists) {
                    $('#selectedPointsTable').DataTable().row.add(rowData).draw(false);
                    selectedPointsData.push(rowData);

                    var lat = parseFloat(rowData[6]);
                    var lng = parseFloat(rowData[7]);
                    var markerKey = `${lat},${lng}`;

                    // Remove existing marker if present
                    if (selectedMarkers[markerKey]) {
                        map.removeLayer(selectedMarkers[markerKey]);
                        delete                     selectedMarkers[markerKey];
                    }

                    // Remove existing blue marker if present
                    if (blueMarkers[markerKey]) {
                        map.removeLayer(blueMarkers[markerKey]);
                        delete blueMarkers[markerKey];
                    }

                    // Add orange marker to map and store it
                    var marker = L.marker([lat, lng], { icon: orangeIcon }).addTo(markersLayer)
                        .bindPopup(rowData[1]);
                    selectedMarkers[markerKey] = marker;
                }
            });

            // Add single click event listener to pan map to the selected point
            $('#selectedPointsTable tbody').on('click', 'tr', function() {
                var lat = parseFloat($(this).find('td').eq(6).text());
                var lng = parseFloat($(this).find('td').eq(7).text());
                map.setView([lat, lng], 15);
            });

            // Add double click event listener to remove selected row from the Selected Points table
            $('#selectedPointsTable tbody').on('dblclick', 'tr', function() {
                var rowData = [];
                $(this).children('td').each(function() {
                    rowData.push($(this).text());
                });

                selectedPointsData = selectedPointsData.filter(function(item) {
                    return JSON.stringify(item) !== JSON.stringify(rowData);
                });

                var lat = parseFloat(rowData[6]);
                var lng = parseFloat(rowData[7]);
                var markerKey = `${lat},${lng}`;

                // Remove the marker from the map
                if (selectedMarkers[markerKey]) {
                    map.removeLayer(selectedMarkers[markerKey]);
                    delete selectedMarkers[markerKey];
                }

                $('#selectedPointsTable').DataTable().row($(this)).remove().draw();

                if ($('#toggleMarkersCheckbox').is(':checked')) {
                    // If toggle checkbox is checked, only unplot the orange marker
                    if (blueMarkers[markerKey]) {
                        map.removeLayer(blueMarkers[markerKey]);
                        delete blueMarkers[markerKey];
                    }
                } else {
                    // If toggle checkbox is not checked, unplot the orange marker and plot a blue marker
                    if (selectedMarkers[markerKey]) {
                        map.removeLayer(selectedMarkers[markerKey]);
                        delete selectedMarkers[markerKey];
                    }
                    var marker = L.marker([lat, lng], { icon: blueIcon }).addTo(markersLayer).bindPopup(rowData[1]);
                    blueMarkers[markerKey] = marker;
                }
            });

            // Plot all initial markers
            <?php foreach ($data as $row): ?>
                var lat = <?php echo $row['latitude']; ?>;
                var lng = <?php echo $row['longitude']; ?>;
                var markerIcon = blueIcon; // Default to blue icon

                // Check if the marker is in selectedPointsData
                var markerKey = `${lat},${lng}`;
                if (selectedMarkers[markerKey]) {
                    // If the marker is in selectedPointsData, set its icon to orange
                    markerIcon = orangeIcon;
                }

                // Add the marker to the map with the appropriate icon
                var marker = L.marker([lat, lng], { icon: markerIcon }).addTo(markersLayer)
                    .bindPopup("<?php echo addslashes($row['stat_name']); ?>");
                blueMarkers[markerKey] = marker;
            <?php endforeach; ?>

            // Toggle selected markers visibility
            $('#toggleMarkersCheckbox').change(function() {
                var isChecked = $(this).is(':checked');
                markersLayer.eachLayer(function(marker) {
                    var lat = marker.getLatLng().lat;
                    var lng = marker.getLatLng().lng;
                    var key = `${lat},${lng}`;
                    if (isChecked) {
                        // Hide unselected markers
                        if (!(key in selectedMarkers)) {
                            map.removeLayer(marker);
                        } else {
                            // Ensure selected markers remain orange
                            marker.setIcon(orangeIcon);
                        }
                    } else {
                        // Show all markers if it's not already an orange marker
                        if (!(key in selectedMarkers)) {
                            // Check if this marker should be blue or orange based on selectedPointsData
                            var shouldBeOrange = selectedPointsData.some(function(data) {
                                return data[6] == lat && data[7] == lng;
                            });
                            if (shouldBeOrange) {
                                // If the marker should be orange, make sure it's orange
                                marker.setIcon(orangeIcon);
                            } else {
                                // Otherwise, make sure it's blue
                                marker.setIcon(blueIcon);
                            }
                            map.addLayer(marker);
                        }
                    }
                });
            });

               // Modal for Request Certificates
    var modal = document.getElementById("requestCertificatesModal");
    var btn = document.getElementById("requestCertificatesBtn");
    var span = document.getElementsByClassName("close")[0];

    btn.onclick = function() {
        fillSelectedPointsList(); // Fill the list of selected points before showing the modal
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Function to fill the list of selected points in the modal
    function fillSelectedPointsList() {
        var selectedPointsList = document.getElementById("selectedPointsList");
        selectedPointsList.innerHTML = ""; // Clear previous content

        selectedPointsData.forEach(function(point) {
            var listItem = document.createElement("li");
            listItem.textContent = point[1];
            selectedPointsList.appendChild(listItem);
        });
    }
// Prevent form submission
$("#certificateForm").submit(function(e) {
    e.preventDefault();

    // Disable the submit button
    $("#submitBtn").prop("disabled", true);
    // Change the color of the submit button to gray
    $("#submitBtn").css("background-color", "#ccc");
    
    // Get form data
    var clientName = $("#clientName").val();
    var clientEmail = $("#clientEmail").val();
    
    // Prepare data for submission
    var selectedStats = [];
    selectedPointsData.forEach(function(point) {
        selectedStats.push(point[1]);
    });

    // Send form data to PHP script
    $.ajax({
        type: "POST",
        url: "processRequest.php",
        data: {
            clientName: clientName,
            clientEmail: clientEmail,
            selectedStats: selectedStats
        },
        success: function(response) {
            // Display response from PHP script
            var successMessage = "<div class='success-message'>";
            successMessage += "<p class='success-text' style='color: green;'>Request submitted successfully!</p>";
            successMessage += "<p class='success-text' style='color: red;'>The Request ID and Email used are needed for tracking. <br>COPY THESE DETAILS DILIGENTLY.</p>";
            successMessage += "<p><strong>Request ID:</strong> " + response + "<br><strong>Client Name:</strong> " + clientName + "<br><strong>Email:</strong> " + clientEmail + "<br><strong>Requested Stations:</strong> " + selectedStats.join(", ") + " </p>";
            successMessage += "</div>";
            $("#requestCertificatesModal .modal-content").append(successMessage);
        }
    });
});
});
    </script>
</body>
</html>

