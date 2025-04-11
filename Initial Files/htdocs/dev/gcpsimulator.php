<!DOCTYPE html>
<html>
<head>
    <title>GNIS | Simulator</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>

    /* Input fields and other styles */
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

    #sidebar {
        width: calc(100vw / 3);
        height: 100vh;
        background-color: #f2f2f2;
        float: left;
        overflow: auto;
    }

    #sidebar-content {
        padding: 20px;
        box-sizing: border-box;
        color: #4caf50;
    }

    .input-container {
        background-color: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .input-group {
        margin-bottom: 15px;
    }

    .input-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: normal;
    }

    .input-group input {
        width: calc(100% - 16px);
        padding: 8px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
        transition: border-color 0.3s ease;
    }

    .input-group input:focus {
        border-color: #66afe9;
        outline: none;
    }

    .slider-container {
        margin-top: 30px;
        margin-bottom: 15px;
        margin-right: 15px;
        display: flex;
        align-items: center;
    }

    .slider-container label {
        display: block;
        margin-bottom: 0px;
        font-weight: bold;
        flex: 1;
    }

    #radius {
        flex: 2;
    }

    #map {
        height: calc(100vh);
        width: calc(100% - calc(100vw / 3));
        float: right;
    }

    h2 {
        color: #2c3e50;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 24px;
        font-weight: bold;
        margin-top: 0;
        margin-bottom: 20px;
    }

    .instruction {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
    }

    /* Table styles */
    #table-container {
        padding: 0px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #ddd;
    }

    th, td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #f2f2f2;
        color: #333;
    }

    th:first-child, td:first-child {
        border-left: none;
    }

    th:last-child, td:last-child {
        border-right: none;
    }

    tr:hover {
        background-color: #f2f2f2;
    }

    
    </style>

</head>
<body>

<div class="header">
    <h1><img src="gnis_logo.png" alt="GNIS Logo" width="60"> Geodetic Network Information System (GNIS)</h1>
</div>

<div class="navbar">
    <a href="index.html"><i class="fas fa-home"></i> GNIS Home</a>
    <a href="mapDatabase.php"><i class="fas fa-map"></i> Explorer</a>
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

<div id="sidebar">
    <div id="sidebar-content">
        <div class="input-container">
            <h2>Custom Point</h2>
            <div class="input-group">
                <label for="latitude">Latitude:</label>
                <input type="text" id="latitude" name="latitude" placeholder="Enter latitude" value="14.6513">
            </div>
            <div class="input-group">
                <label for="longitude">Longitude:</label>
                <input type="text" id="longitude" name="longitude" placeholder="Enter longitude" value="121.049">
            </div>
            <div class="slider-container">
                <label for="radius">Distance (km):</label>
                <input type="range" id="radius" name="radius" min="0.5" max="6" step="0.1" value="3">
                <input type="number" id="distance" name="distance" min="0.5" max="6" step="0.1" value="3" style="width: 50px; margin-left: 10px;">
                <div id="instruction" class="instruction">Maximum distance is 6km</div> <!-- Added instruction -->
            </div>
        </div>

        
        <div id="table-container">
            <h2>Available GCPs</h2>
            <div class="input-container" style="max-height: 45vh; overflow-y: auto; padding: 20px; box-sizing: border-box; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 0px;">
                <table>
                    <thead>
                        <tr>
                        <th >Station</th>
                        <th >Latitude</th>
                        <th >Longitude</th>
                        <th >Distance (KM)</th>

                        </tr>
                    </thead>
                    <tbody id="station-table-body">
                        <!-- Table body will be populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>


    </div>
</div>

<div id="map"></div>


<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script> <!-- For making HTTP requests -->

<script>

    // Custom icon for blue marker
    var blueIcon = L.icon({
        iconUrl: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -16]
    });

    
    var map = L.map('map').setView([14.5353, 121.0410], 15); // Default coordinates and zoom level
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
    }).addTo(map);

    var greenMarker = null; // Variable to store the green marker
    var circle = null; // Variable to store the circle

    // Function to fetch data from MySQL database and plot points
    function plotPoints() {
        <?php
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

        // Fetch data from the database
        $sql = "SELECT * FROM gcp_table WHERE status <> 5";
        $result = $conn->query($sql);
        $data = array();

        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        } else {
            echo "console.error('0 results')";
        }

        // Close connection
        $conn->close();

        // Output data as JSON
        echo "var data = " . json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE) . ";";
        ?>

        // Loop through the data and add markers to the map
        // Loop through the data and add markers to the map
        data.forEach(function(row) {
            var marker = L.marker([row.N84dd, row.E84dd], { icon: blueIcon }).addTo(map);
            marker.bindPopup("<b>"+ row.stat_name + "</b><br>" + "<br>Province: " + row.province + "<br>Municipal: " + row.municipal + "<br>Barangay: " + row.barangay + "<br>Descriptions: " + row.descripts);

            // Append row data to the table dynamically
            var tableRow = document.createElement('tr');
            tableRow.innerHTML = "<td>" + row.stat_name + "</td><td>" + row.N84dd + "</td><td>" + row.E84dd + "</td>";
            document.getElementById('station-table-body').appendChild(tableRow);
        });

    }

    // Call the function to plot points when the page loads
    plotPoints();

    // Function to plot marker based on latitude and longitude input
    function plotMarker() {
        var latitude = parseFloat(document.getElementById('latitude').value);
        var longitude = parseFloat(document.getElementById('longitude').value);

        if (!isNaN(latitude) && !isNaN(longitude) && latitude !== "" && longitude !== "") {
            // Remove the existing green marker if it exists
            if (greenMarker !== null) {
                map.removeLayer(greenMarker);
            }

            // Remove the existing circle
            if (circle !== null) {
                map.removeLayer(circle);
            }

            // Plot green marker at the specified coordinates
            greenMarker = L.marker([latitude, longitude], { icon: greenIcon, zIndexOffset: 1000 }).addTo(map);

            // Plot circle with given radius around the marker
            var radius = parseFloat(document.getElementById('radius').value) * 1000; // Convert km to meters
            circle = L.circle([latitude, longitude], {
                radius: radius,
                color: '#4CAF50', // Light green color
                fillColor: '#4CAF50', // Light green color
                fillOpacity: 0.3, // Semi-transparent
                zIndex: 999, // Ensure the circle is above other elements
            }).addTo(map);

            // Update map extent to fit the circle
            map.fitBounds(circle.getBounds());
        }
    }

    // Custom icon for green marker
    var greenIcon = L.icon({
        iconUrl: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -16]
    });

    // Plot green marker and circle for default coordinates when the page loads
    plotMarker();

// Add event listener to the radius input slider to update the table rows
document.getElementById('radius').addEventListener('input', function() {
    var radiusValue = parseFloat(this.value);
    // Check if the entered value is greater than 6
    if (radiusValue > 6) {
        // If the value is greater than 6, set it to 6
        this.value = 6;
        radiusValue = 6;
    }
    document.getElementById('distance').value = radiusValue;
    plotMarker(); // Plot marker with updated radius
    hideRowsGreaterThanDistance(radiusValue); // Hide rows greater than the updated radius
});

// Update the table rows when the input distance changes
document.getElementById('distance').addEventListener('input', function() {
    var distanceValue = parseFloat(this.value);
    document.getElementById('radius').value = distanceValue;
    plotMarker(); // Plot marker with updated distance
    hideRowsGreaterThanDistance(distanceValue); // Hide rows greater than the updated distance
});


    function calculateDistance(lat1, lon1, lat2, lon2) {
        var R = 6371; // Radius of the earth in km
        var dLat = deg2rad(lat2 - lat1);
        var dLon = deg2rad(lon2 - lon1);
        var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var distance = R * c; // Distance in km
        return distance;
    }

    function deg2rad(deg) {
        return deg * (Math.PI / 180);
    }

    // Function to populate the Distance column in the table
    function populateDistanceColumn(latitude, longitude) {
    var tableRows = document.querySelectorAll('#station-table-body tr');
    tableRows.forEach(function(row) {
        var stationLat = parseFloat(row.cells[1].textContent);
        var stationLon = parseFloat(row.cells[2].textContent);
        var distance = calculateDistance(latitude, longitude, stationLat, stationLon);
        // Round distance to two decimal places
        distance = Math.round(distance * 100) / 100;
        
        // Check if the distance cell already contains data
        if (row.cells.length < 4) {
            // If less than 4 cells, insert a new cell and set its content
            var cell = row.insertCell();
            cell.textContent = distance;
        } else {
            // If there's already a cell, update its content
            row.cells[3].textContent = distance;
        }
    });


    
}

    // Call the function to populate the Distance column when the page loads
    populateDistanceColumn(14.6513, 121.049); // Default input point, you can change this to your custom input

    // Update the distance column when the custom input changes
    function updateDistanceColumn() {
    var latitude = parseFloat(document.getElementById('latitude').value);
    var longitude = parseFloat(document.getElementById('longitude').value);

    // Clear the distance column before populating new data
    var tableRows = document.querySelectorAll('#station-table-body tr');
    tableRows.forEach(function(row) {
        row.cells[3].textContent = ""; // Clear distance column
    });

    populateDistanceColumn(latitude, longitude);
}

    // Add event listeners to latitude and longitude input fields to update the distance column
    document.getElementById('latitude').addEventListener('input', updateDistanceColumn);
    document.getElementById('longitude').addEventListener('input', updateDistanceColumn);



    // Function to hide rows in the table where the distance is greater than the input kilometer distance
function hideRowsGreaterThanDistance(distance) {
    var tableRows = document.querySelectorAll('#station-table-body tr');
    tableRows.forEach(function(row) {
        var distanceCell = row.cells[3];
        if (parseFloat(distanceCell.textContent) > distance) {
            row.style.display = 'none';
        } else {
            row.style.display = ''; // Reset to default display property
        }
    });
}

// Call the function to hide rows greater than the input distance when the page loads
hideRowsGreaterThanDistance(parseFloat(document.getElementById('distance').value));

// Update the table rows when the input distance changes
document.getElementById('distance').addEventListener('input', function() {
    var distanceValue = parseFloat(this.value);
    hideRowsGreaterThanDistance(distanceValue);
});

// Add event listeners to latitude and longitude input fields to update the green pin on map
document.getElementById('latitude').addEventListener('input', function() {
    plotMarker(); // Update green pin on map
});

document.getElementById('longitude').addEventListener('input', function() {
    plotMarker(); // Update green pin on map
});

// Function to sort table rows based on the values in the Distance column
function sortTableByDistance() {
    var table = document.querySelector('table');
    var tbody = table.querySelector('tbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));

    // Custom sorting function to sort rows based on the distance column
    rows.sort(function(a, b) {
        var distanceA = parseFloat(a.cells[3].textContent.replace(' km', ''));
        var distanceB = parseFloat(b.cells[3].textContent.replace(' km', ''));
        return distanceA - distanceB;
    });

    // Clear existing table rows and append sorted rows
    tbody.innerHTML = '';
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

// Call the function to sort the table by distance when the page loads
sortTableByDistance();

// Update the table rows and sort by distance when the input distance changes
document.getElementById('distance').addEventListener('input', function() {
    var distanceValue = parseFloat(this.value);
    hideRowsGreaterThanDistance(distanceValue); // Hide rows greater than the input distance
    sortTableByDistance(); // Sort the table rows by distance
});

// Add event listeners to latitude and longitude input fields to update the table rows and sort by distance
document.getElementById('latitude').addEventListener('input', function() {
    plotMarker(); // Update green pin on map
    updateDistanceColumn(); // Update distance column
    sortTableByDistance(); // Sort the table rows by distance
});

document.getElementById('longitude').addEventListener('input', function() {
    plotMarker(); // Update green pin on map
    updateDistanceColumn(); // Update distance column
    sortTableByDistance(); // Sort the table rows by distance
});

// Custom icon for orange marker
var orangeIcon = L.icon({
    iconUrl: 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -16]
});

// Function to plot all visible stations on the map with orange markers
// Function to plot all stations on the map with orange markers
// Function to plot all stations on the map with orange markers
function plotAllStations() {
    // Remove existing orange markers from the map
    map.eachLayer(function(layer) {
        if (layer instanceof L.Marker && layer.options.icon.options.iconUrl === 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png') {
            map.removeLayer(layer);
        }
    });

    var tableRows = document.querySelectorAll('#station-table-body tr');

    // Loop through each table row
    tableRows.forEach(function(row) {
        // Check if the row is visible
        if (row.style.display !== 'none') {
            var latitude = parseFloat(row.cells[1].textContent);
            var longitude = parseFloat(row.cells[2].textContent);

            // Check if latitude and longitude are valid numbers
            if (!isNaN(latitude) && !isNaN(longitude)) {
                // Create orange marker for each visible station and add to the map
                var orangeMarker = L.marker([latitude, longitude], { icon: orangeIcon }).addTo(map);

                // Add popup to the orange marker with station information
                orangeMarker.bindPopup("<b>Station Name:</b> " + row.cells[0].textContent + "<br>" +
                                       "<b>Latitude:</b> " + latitude + "<br>" +
                                       "<b>Longitude:</b> " + longitude);

                // You can add more information to the popup as needed
            }
        }
    });
}

// Call the function to plot all stations when the page loads
plotAllStations();



// Add event listener to the radius input slider to update the map markers
document.getElementById('radius').addEventListener('input', function() {
    var radiusValue = parseFloat(this.value);
    // Check if the entered value is greater than 6
    if (radiusValue > 6) {
        // If the value is greater than 6, set it to 6
        this.value = 6;
        radiusValue = 6;
    }
    document.getElementById('distance').value = radiusValue;
    plotMarker(); // Plot marker with updated radius
    hideRowsGreaterThanDistance(radiusValue); // Hide rows greater than the updated radius
    plotAllStations(); // Update map markers for all visible stations
});

// Update the table rows when the input distance changes
document.getElementById('distance').addEventListener('input', function() {
    var distanceValue = parseFloat(this.value);
    document.getElementById('radius').value = distanceValue;
    plotMarker(); // Plot marker with updated distance
    hideRowsGreaterThanDistance(distanceValue); // Hide rows greater than the updated distance
    plotAllStations(); // Update map markers for all visible stations
});

// Add event listeners to latitude and longitude input fields to simulate slider change
document.getElementById('latitude').addEventListener('input', function() {
    var slider = document.getElementById('radius');
    var sliderValue = parseFloat(slider.value);
    slider.dispatchEvent(new Event('input'));
});

document.getElementById('longitude').addEventListener('input', function() {
    var slider = document.getElementById('radius');
    var sliderValue = parseFloat(slider.value);
    slider.dispatchEvent(new Event('input'));
});


// Add event listener to the map to change input location by clicking
map.on('click', function(e) {
    var latitude = e.latlng.lat.toFixed(4); // Extract latitude
    var longitude = e.latlng.lng.toFixed(4); // Extract longitude

    // Update latitude and longitude input fields
    document.getElementById('latitude').value = latitude;
    document.getElementById('longitude').value = longitude;

    // Trigger input event on latitude and longitude input fields to simulate slider change
    var latitudeInput = document.getElementById('latitude');
    var longitudeInput = document.getElementById('longitude');
    latitudeInput.dispatchEvent(new Event('input'));
    longitudeInput.dispatchEvent(new Event('input'));
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

