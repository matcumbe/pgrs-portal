<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GNIS | Explorer (Horizontal)</title>

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



    .table-container table thead th {
        background-color: #000;
        color: #fff;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .filter-container {
        margin-top: 10px;
        margin-bottom: 2px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .filter-container label {
        margin-right: 10px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    th, td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .container {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        background-color: #fff;
    }
    .container h2 {
        margin: 0;
        font-size: 18px;
        background-color: #4CAF50;
        color: #fff;
        padding: 10px;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-container {
        max-height: 200px;
        overflow-y: auto;
    }
    #map-container {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        background-color: #fff;
    }
    #map-container h2 {
        margin: 0;
        font-size: 18px;
        background-color: #4CAF50;
        color: #fff;
        padding: 10px;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #map {
        width: 100%;
        height: 400px;
    }

    .search-container {
        display: flex;
        justify-content: center;
        margin-bottom: 10px;
    }
    .search-container input[type="text"] {
        padding: 8px;
        width: 80%;
        font-size: 16px;
    }
    .search-bar {
        width: 20%;
        padding: 6px;
        margin-right: 10px;
        font-size: 12px;
        border-radius: 5px; /* Add this line to round the corners */
        border: 1px solid #ccc; /* Optional: Add a border to make the rounded corners more visible */
    }

    #submit-request {
        background-color: green;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }

    /* Button container styles */
    .button-container {
        margin: 20px 10px;
        display: flex;
        justify-content: flex-end; /* Distribute space between the buttons */
        margin-top: 10px;
    }

    /* Button styles */
    .button-container button {
        margin-right: 10px;
        background-color: green;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    #request-certificates-btn {
        margin-right: 10px;
        background-color: green;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }


    .popup-content {
        font-size: 14px;
        line-height: 1.4;
    }
    .popup-header {
        font-weight: bold;
        color: #006400; /* Dark green */
    }
    .popup-desc {
        margin-top: 5px;
        padding: 5px;
        background-color: #f0f0f0;
        border-radius: 5px;
        max-height: 25vh; /* 25% of the viewport height */
        overflow-y: auto;
    }
    
/* CSS for the Request Certificates popup window */
.request-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(255, 255, 255);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    border-radius: 5px;
    padding: 15px;
    font-size: 16px;
    z-index: 9999;
    width: 300px; /* Adjust the width as needed */
}

.request-popup .close {
    position: absolute;
    top: 5px;
    right: 5px;
    cursor: pointer;
    font-size: 20px;
}

.request-popup .popup-content {
    margin-bottom: 10px;
}

.request-popup .popup-content label {
    display: block;
    margin-bottom: 5px;
}

.request-popup .popup-content input[type="text"],
.request-popup .popup-content input[type="email"] {
    width: calc(100% - 16px); /* Adjust input width */
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.request-popup .popup-content button {
    background-color: green;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.request-popup .popup-content button:hover {
    background-color: darkgreen;
}

        /* Overlay styles */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
            z-index: 999; /* Ensure it covers everything */
        }

/* Adjust Leaflet map controls and credits */
.leaflet-control {
    z-index: 998; /* Position controls below overlay */
}

.leaflet-control-attribution {
    z-index: 997; /* Position credits below overlay */
}




</style>
</head>

<body>
<div class="overlay"></div>
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

<div id="map-container" class="container">
    <h2>Map</h2>
    <div id="map"></div>
        <div class="filter-container">
            <label><input type="checkbox" id="order1" checked> 1st Order</label>
            <label><input type="checkbox" id="order2" checked> 2nd Order</label>
            <label><input type="checkbox" id="order3" checked> 3rd Order</label>
            <label><input type="checkbox" id="order4" checked> 4th Order</label>
        </div>

</div>

<!-- Popup window for client name and email address -->
<div id="popup" class="request-popup">
    <div class="popup-content">
        <span class="close">&times;</span>
        <h2>Request Certificates</h2>
        <label for="client-name">Client Name:</label>
        <input type="text" id="client-name" name="client-name">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <div id="selected-points-list"></div> <!-- Add a div to display selected points -->
        <button id="submit-request">Submit</button>
    </div>
</div>

<?php
    $host = 'sql105.infinityfree.com';
    $dbname = 'if0_36589195_webgnisdb';
    $username = 'if0_36589195';
    $password = 'Himpapawid11';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch all columns from the gcp_table
    $stmt = $pdo->query("SELECT ID, stat_name, status, island, region, province, municipal, barangay, N84dd, E84dd, order_acc, N92d, N92m, N92s, E92d, E92m, E92s, H92, N92ptm, E92ptm, Z92, N84d, N84m, N84s, E84d, E84m, E84s, H84, descripts, E92utm, N92utm, Z92utm, E84utm, N84utm, Z84utm FROM gcp_table");

    $completeInformation = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $completeInformation[] = $row;
    
    }   

    // Pass the PHP array to JavaScript
    echo "<script>
        var completeInformation = " . json_encode($completeInformation, JSON_INVALID_UTF8_SUBSTITUTE) . ";
    </script>";

    // Updated query to exclude rows where status = 5
    $stmt_available = $pdo->query("SELECT stat_name, region, province, municipal, barangay, H84, order_acc, N84d, N84m, N84s, E84d, E84m, E84s FROM gcp_table WHERE status <> 5");
    
    // Constructing the Available Points table
    $available_points_table = '<div class="container"><h2>Available Points (double click a row to select)<input type="text" id="search-available" class="search-bar" placeholder="Search Available Points"></h2><div class="table-container"><table id="available-table"><thead><tr><th>Station</th><th>Region</th><th>Province</th><th>Municipality/City</th><th>Barangay</th><th>Latitude</th><th>Longitude</th><th>Elevation (M)</th><th>Order of Accuracy</th></tr></thead><tbody>';

    while ($row = $stmt_available->fetch(PDO::FETCH_ASSOC)) {
        $available_points_table .= '<tr>';
        $available_points_table .= '<td>' . htmlspecialchars($row['stat_name']) . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['region']) . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['province']) . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['municipal']) . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['barangay']) . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['N84d'] . '° ' . $row['N84m'] . '\' ' . $row['N84s'] . '"') . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['E84d'] . '° ' . $row['E84m'] . '\' ' . $row['E84s'] . '"') . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['H84']) . '</td>';
        $available_points_table .= '<td>' . htmlspecialchars($row['order_acc']) . '</td>';
        $available_points_table .= '</tr>';
    }

    $available_points_table .= '</tbody></table></div></div>';

    // Constructing the Selected Points table (initially empty)
    $selected_points_table = '<div class="container"><h2>Selected Points (Double click a row to deselect)<input type="text" id="search-selected" class="search-bar" placeholder="Search Selected Points"></h2><div class="table-container"><table id="selected-table"><thead><tr><th>Station</th><th>Region</th><th>Province</th><th>Municipality/City</th><th>Barangay</th><th>Latitude</th><th>Longitude</th><th>Elevation (M)</th><th>Order of Accuracy</th></tr></thead><tbody>';
    // Add placeholder rows if needed

    $selected_points_table .= '</tbody></table></div></div>';

    // Outputting both tables
    echo $available_points_table;
    echo $selected_points_table;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>



<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
var map = L.map('map').setView([14.6552, 121.06], 13); // Center of the Philippines

L.tileLayer('https://basemapserver.geoportal.gov.ph/tiles/v2/PGP/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="https://www.geoportal.gov.ph/">NAMRIA</a> contributors',
}).addTo(map);

var markers = L.layerGroup().addTo(map);

var greenIcon = new L.Icon({
    iconUrl: 'images/greenpin.png',
    iconSize: [50, 50],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

var yellowIcon = new L.Icon({
    iconUrl: 'images/yellowpin.png',
    iconSize: [50, 50],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
});

var orangeIcon = new L.Icon({
    iconUrl: 'images/orangepin.png',
    iconSize: [50, 50],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
});

var redIcon = new L.Icon({
    iconUrl: 'images/redpin.png',
    iconSize: [50, 50],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
});

var popupMap = new Map();

document.addEventListener('DOMContentLoaded', function() {
    const updateMarkers = () => {
        markers.clearLayers(); // Clear existing markers
        const order1Checked = document.getElementById('order1').checked;
        const order2Checked = document.getElementById('order2').checked;
        const order3Checked = document.getElementById('order3').checked;
        const order4Checked = document.getElementById('order4').checked;

        completeInformation.forEach(point => {
            const lat = parseFloat(point.N84dd);
            const lng = parseFloat(point.E84dd);
            const order = parseInt(point.order_acc);
            let icon;

            switch (order) {
                case 1: icon = greenIcon; break;
                case 2: icon = yellowIcon; break;
                case 3: icon = orangeIcon; break;
                case 4: icon = redIcon; break;
                default: icon = new L.Icon.Default(); break;
            }

            if (!isNaN(lat) && !isNaN(lng)) {
                if ((order === 1 && order1Checked) ||
                    (order === 2 && order2Checked) ||
                    (order === 3 && order3Checked) ||
                    (order === 4 && order4Checked)) {
                    const marker = L.marker([lat, lng], { icon: icon })
                        .bindPopup(`<div class="popup-content"><div class="popup-header">${point.stat_name}</div><div class="popup-desc">${point.descripts}</div></div>`)
                        .addTo(markers);

                    popupMap.set(point.stat_name, marker);
                }
            }
        });
    };

    document.querySelectorAll('.filter-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateMarkers);
    });

    updateMarkers(); // Initial marker update

    const availableTable = document.getElementById('available-table');
    const selectedTable = document.getElementById('selected-table');
    const order1Checkbox = document.getElementById('order1');
    const order2Checkbox = document.getElementById('order2');
    const order3Checkbox = document.getElementById('order3');
    const order4Checkbox = document.getElementById('order4');

    // Function to filter rows based on checkbox state
    function filterRows() {
        const rows = availableTable.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
            const order = parseInt(row.cells[5].textContent); // Assuming order_acc is in the 6th column
            if ((order === 1 && !order1Checkbox.checked) ||
                (order === 2 && !order2Checkbox.checked) ||
                (order === 3 && !order3Checkbox.checked) ||
                (order === 4 && !order4Checkbox.checked)) {
                row.style.display = 'none';
            } else {
                row.style.display = '';
            }
        });
    }

    // Initial filtering based on checkbox state
    filterRows();

    // Event listeners for checkbox changes
    order1Checkbox.addEventListener('change', filterRows);
    order2Checkbox.addEventListener('change', filterRows);
    order3Checkbox.addEventListener('change', filterRows);
    order4Checkbox.addEventListener('change', filterRows);

    // Function to pan the map and show the popup for a row
    function panToRow(targetRow) {
        if (targetRow) {
            const statName = targetRow.cells[0].textContent;
            const marker = popupMap.get(statName);
            if (marker) {
                map.setView(marker.getLatLng(), 16); // Zoom level 16
                marker.openPopup();
            }
        }
    }

    // Event listener for clicking a row in "Available Points" table to pan the map and show the popup
    availableTable.addEventListener('click', function(event) {
        const targetRow = event.target.closest('tr');
        panToRow(targetRow);
    });

    // Event listener for clicking a row in "Selected Points" table to pan the map and show the popup
    selectedTable.addEventListener('click', function(event) {
        const targetRow = event.target.closest('tr');
        panToRow(targetRow);
    });

    // Event listener for adding a row to the "Selected Points" table
    availableTable.addEventListener('dblclick', function(event) {
        const targetRow = event.target.closest('tr');
        if (targetRow) {
            const cloneRow = targetRow.cloneNode(true);
            const selectedRows = selectedTable.querySelectorAll('tbody tr');
            let exists = false;

            selectedRows.forEach(function(row) {
                if (row.cells[0].textContent === cloneRow.cells[0].textContent) {
                    exists = true;
                }
            });

            if (!exists) {
                selectedTable.querySelector('tbody').appendChild(cloneRow);
            } else {
                alert('Selected point already exists.');
            }
        }
    });

    // Event listener for deleting a row from the "Selected Points" table
    selectedTable.addEventListener('dblclick', function(event) {
        const targetRow = event.target.closest('tr');
        if (targetRow) {
            targetRow.remove();
        }
    });

    // Function to filter table based on search input
    function searchTable(searchInput, table) {
        const filter = searchInput.value.toLowerCase();
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let match = false;
            for (let j = 0; j < cells.length; j++) {
                if (cells[j]) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
            }
            if (match) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    // Adding search functionality to the Available Points table
    const searchAvailableInput = document.getElementById('search-available');
    searchAvailableInput.addEventListener('keyup', function() {
        searchTable(searchAvailableInput, availableTable);
    });

    // Adding search functionality to the Selected Points table
    const searchSelectedInput = document.getElementById('search-selected');
    searchSelectedInput.addEventListener('keyup', function() {
        searchTable(searchSelectedInput, selectedTable);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const requestBtn = document.getElementById('request-certificates-btn');
    const popup = document.getElementById('popup');
    const closeBtn = document.querySelector('.close');
    const overlay = document.querySelector('.overlay');
    const leafletControls = document.querySelectorAll('.leaflet-control');
    const leafletAttribution = document.querySelector('.leaflet-control-attribution');

    requestBtn.addEventListener('click', function() {
        popup.style.display = 'block';
        overlay.style.display = 'block';
        hideLeafletControls(true); // Hide Leaflet controls
    });

    closeBtn.addEventListener('click', function() {
        popup.style.display = 'none';
        overlay.style.display = 'none';
        hideLeafletControls(false); // Show Leaflet controls
    });

    // Function to hide or show Leaflet controls
    function hideLeafletControls(hide) {
        if (hide) {
            leafletControls.forEach(control => {
                control.style.display = 'none';
            });
            if (leafletAttribution) {
                leafletAttribution.style.display = 'none';
            }
        } else {
            leafletControls.forEach(control => {
                control.style.display = '';
            });
            if (leafletAttribution) {
                leafletAttribution.style.display = '';
            }
        }
    }
});

// JavaScript code to populate the selected points list with enumeration
document.addEventListener('DOMContentLoaded', function() {
    const selectedTable = document.getElementById('selected-table');
    const selectedPointsList = document.getElementById('selected-points-list');
    const submitBtn = document.getElementById('submit-request');

    function updateSelectedPointsList() {
        selectedPointsList.innerHTML = ''; // Clear existing content
        const rows = selectedTable.querySelectorAll('tbody tr');
        if (rows.length === 0) {
            selectedPointsList.textContent = 'You have not selected any points.';
            selectedPointsList.style.color = 'red'; // Apply red color to the text
            submitBtn.disabled = true; // Disable submit button if no points are selected
        } else {
            selectedPointsList.textContent = 'You are requesting certificates for:'
            selectedPointsList.style.color = 'black'; // Apply red color to the text
            const list = document.createElement('ul');
            rows.forEach(row => {
                const stationName = row.cells[0].textContent; // Assuming station name is in the first column
                const listItem = document.createElement('li');
                listItem.textContent = stationName; // Display station name only
                list.appendChild(listItem);
            });
            selectedPointsList.appendChild(list);
            submitBtn.disabled = false; // Enable submit button when points are selected
        }
    }

    // Update the selected points list whenever the table changes
    selectedTable.addEventListener('DOMSubtreeModified', updateSelectedPointsList);
    updateSelectedPointsList(); // Initial update
});


// Function to format selected points into a comma-separated list
function formatSelectedPoints() {
    const selectedRows = document.querySelectorAll('#selected-table tbody tr');
    const selectedPoints = Array.from(selectedRows).map(row => row.cells[0].textContent);
    return selectedPoints.join(', ');
}

document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submit-request');
    submitBtn.addEventListener('click', function() {
        const clientName = document.getElementById('client-name').value;
        const email = document.getElementById('email').value;
        const selectedPointsList = [];
        const selectedTableRows = document.querySelectorAll('#selected-table tbody tr');

        selectedTableRows.forEach(row => {
            selectedPointsList.push(row.cells[0].textContent); // Assuming station name is in the first column
        });

        const statNames = selectedPointsList.join(', '); // Join the selected points with commas

        // Disable and gray out the submit button
        submitBtn.disabled = true;
        submitBtn.style.backgroundColor = '#cccccc'; // Gray out the button

        // AJAX request to send data to the server
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'submit_request.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = xhr.responseText;
                const ticketID = response.trim(); // Assuming the response contains the ticket ID
                updateSelectedPointsList(ticketID, clientName, email, statNames); // Update the selected points list with the summary
            }
        };
        xhr.send(`client=${encodeURIComponent(clientName)}&email=${encodeURIComponent(email)}&stat_names=${encodeURIComponent(statNames)}`);
    });
});

// Function to update the selected points list with the summary
function updateSelectedPointsList(ticketID, clientName, email, requestedPoints) {
    const selectedPointsList = document.getElementById('selected-points-list');
    selectedPointsList.innerHTML = ''; // Clear existing content

    // Create the elements for the ticket ID line
    const ticketIDLine = document.createElement('div');
    ticketIDLine.innerHTML = `<strong style="color: black;">Ticket ID:</strong> ${ticketID}`;
    ticketIDLine.style.display = 'flex'; // Set display to flex for alignment
    ticketIDLine.style.justifyContent = 'space-between'; // Align items to the right
    ticketIDLine.style.marginBottom = '10px'; // Add margin bottom for spacing
    ticketIDLine.style.marginRight = '20px'; // Add margin right for spacing
    selectedPointsList.appendChild(ticketIDLine);

    // Create the copy icon
    const copyIcon = document.createElement('i');
    copyIcon.className = 'far fa-copy';
    copyIcon.style.color = 'darkgreen';
    copyIcon.style.cursor = 'pointer';
    copyIcon.addEventListener('click', function() {
        navigator.clipboard.writeText(ticketID);
        alert('Ticket ID copied to clipboard!');
    });
    ticketIDLine.appendChild(copyIcon); // Append copy icon to the ticket ID line

    // Create the elements for the rest of the summary
    const summaryLines = [
        `<strong style="color: black;">Client Name:</strong> ${clientName}`,
        `<strong style="color: black;">Email:</strong> ${email}`,
        `<strong style="color: black;">Requested Points:</strong><br>`
        
    ];

    // Add the rest of the summary lines
    summaryLines.forEach(line => {
        const lineDiv = document.createElement('div');
        lineDiv.innerHTML = line;
        selectedPointsList.appendChild(lineDiv);
    });

    // Split the requested points by comma and add each point on a separate line
    const pointsArray = requestedPoints.split(', ');
    pointsArray.forEach(point => {
        const pointDiv = document.createElement('div');
        pointDiv.textContent = point;
        selectedPointsList.appendChild(pointDiv);
    });

    // Add the message after the enumeration of requested points
    const messageDiv = document.createElement('div');
    messageDiv.innerHTML = `<br><span style="color: green;">Your request is being processed. Please use the Tracker module for the status of your request.</span>`;
    selectedPointsList.appendChild(messageDiv);
}

document.addEventListener('DOMContentLoaded', function() {
    const clientNameInput = document.getElementById('client-name');
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submit-request');

    // Function to check if the input fields are filled out and if points are selected
    function checkInputs() {
        const clientNameValue = clientNameInput.value.trim();
        const emailValue = emailInput.value.trim();
        const selectedPointsRows = document.querySelectorAll('#selected-table tbody tr');

        // Check if both fields are filled out, if email is valid, and if points are selected
        const isValid = clientNameValue !== '' && emailValue !== '' && isValidEmail(emailValue) && selectedPointsRows.length > 0;

        // Enable or disable the Submit button based on validation results
        submitBtn.disabled = !isValid;
        submitBtn.style.backgroundColor = isValid ? 'green' : '#cccccc'; // Change button color based on validation
    }

    // Function to validate email format
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Disable the Submit button by default
    submitBtn.disabled = true;
    submitBtn.style.backgroundColor = '#cccccc'; // Set default button color

    // Add event listeners to input fields for detecting changes
    clientNameInput.addEventListener('input', checkInputs);
    emailInput.addEventListener('input', checkInputs);

    // Add event listener to the "Selected Points" table for detecting changes
    document.getElementById('selected-table').addEventListener('DOMSubtreeModified', checkInputs);
});


document.addEventListener('DOMContentLoaded', function() {
    const downloadBtn = document.getElementById('download-points-btn');

    downloadBtn.addEventListener('click', function() {
        // Get the selected points from the table
        const selectedTableRows = document.querySelectorAll('#selected-table tbody tr');
        const selectedPoints = [];

        // Extract station names of selected points
        selectedTableRows.forEach(row => {
            const stationName = row.cells[0].textContent;
            selectedPoints.push(stationName);
        });

        // Create a popup window
        const popupWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');

        // Write HTML content to the popup window
        popupWindow.document.write('<html><head><title>Selected Points Information</title></head><body>');
        popupWindow.document.write('<style>');
        popupWindow.document.write('@page { size: A4; margin: 20mm; }'); // Set A4 size and margin
        popupWindow.document.write('table { border-collapse: collapse; width: 100%; }');
        popupWindow.document.write('th, td { border: 1px solid #000; padding: 10px; font-size: 16px; }');
        popupWindow.document.write('th { background-color: #f2f2f2; text-align: left; }');
        popupWindow.document.write('.print-btn { position: absolute; top: 10px; right: 10px; padding: 10px 20px; background-color: #007bff; color: #fff; border: none; cursor: pointer; font-size: 16px; }'); // Print button style
        popupWindow.document.write('</style>');
        
        // Print button
        popupWindow.document.write('<button class="print-btn" onclick="window.print()">Print</button>');

        // Loop through each selected point
        selectedPoints.forEach((point, index) => {
            // Get the information for the current point
            const pointInfo = completeInformation.find(info => info.stat_name === point);
            if (pointInfo) {
                // Create a table for the current point
                popupWindow.document.write(`<h2>${point} Information</h2>`);
                popupWindow.document.write('<table>');
                popupWindow.document.write('<tr><th colspan="2">Station Name:</th><td colspan="4">' + point + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="2">Order:</th><td colspan="2">' + pointInfo.order_acc + '</td><th>Island:</th><td>' + pointInfo.island + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="2">Province:</th><td colspan="4">' + pointInfo.province + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="2">Municipality:</th><td colspan="2">' + pointInfo.municipal + '</td><th>Barangay:</th><td>' + pointInfo.barangay + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="6">WGS84 Coordinates</th></tr>');
                popupWindow.document.write('<tr><th colspan="2">Latitude:</th><td colspan="2">' + pointInfo.N84d + '° ' + pointInfo.N84m + '\' ' + pointInfo.N84s + '"</td><th>Longitude:</th><td colspan="2">' + pointInfo.E84d + '° ' + pointInfo.E84m + '\' ' + pointInfo.E84s + '"</td></tr>');
                popupWindow.document.write('<tr><th colspan="2">Ellipsoidal Height (m):</th><td colspan="4">' + pointInfo.H84 + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="6">UTM / WGS84</th></tr>');
                popupWindow.document.write('<tr><th colspan="2">Northing:</th><td colspan="2">' + pointInfo.N84utm + '</td><th>Easting:</th><td colspan="2">' + pointInfo.E84utm + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="2">Zone:</th><td colspan="4">' + pointInfo.Z84utm + '</td></tr>');
                popupWindow.document.write('<tr><th colspan="6">Description:</th></tr>');
                popupWindow.document.write('<tr><td colspan="6">' + pointInfo.descripts + '</td></tr>');
                popupWindow.document.write('</table>');

                // Add page break if it's not the last table
                if (index !== selectedPoints.length - 1) {
                    popupWindow.document.write('<div style="page-break-before: always;"></div>'); // Page break after each table
                }
            }
        });

        popupWindow.document.write('</body></html>');
        popupWindow.document.close(); // Close the document writing

        // Focus on the popup window
        popupWindow.focus();
    });
});

    /* JavaScript for dropdown functionality */
    document.querySelector('.dropdown').addEventListener('mouseover', function() {
        document.querySelector('.dropdown-content').style.display = 'block';
    });

    document.querySelector('.dropdown').addEventListener('mouseout', function() {
        document.querySelector('.dropdown-content').style.display = 'none';
    });

</script>

<!-- "Request Certificates" button -->
<div class="button-container">

    <!-- Download Points button -->
    <button id="download-points-btn">Download Points</button>

    <!-- Request Certificates button -->
    <button id="request-certificates-btn">Request Certificates</button>


</div>
  </body>
</html>