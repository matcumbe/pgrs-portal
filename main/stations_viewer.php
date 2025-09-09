<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNIS | Stations Viewer</title>
    <link rel="icon" href="assets/gnis_logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css" rel="stylesheet">
    <style>
        .filter-panel {
            overflow-y: auto;
            padding: 15px;
            height: calc(100vh - 110px);
        }
        
        .content-panel {
            overflow-y: auto;
            padding: 15px;
            height: calc(100vh - 110px);
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .hidden {
            display: none;
        }
        
        #stations-table { 
            margin-top: 1em; 
            height: 70vh; 
            max-height: 70vh; 
            overflow: auto; 
            background: #fff; 
            position: relative; 
        }
        
        #edit-toggle, #upload-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5em;
            vertical-align: middle;
            margin-left: 1em;
        }
        #edit-toggle.active {
            color: #007bff;
        }
        #search-bar {
            margin-bottom: 1em;
            width: 300px;
            padding: 0.5em;
        }
        .tabulator-row .delete-row-btn {
            color: #d9534f;
            cursor: pointer;
            font-size: 1.2em;
            margin-left: 0.5em;
            margin-right: 0.5em;
        }
        .action-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            margin: 4px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .action-link:hover {
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .action-link.edit {
            color: #0d6efd;
            background-color: #f8f9fa;
            border-color: #0d6efd;
        }
        .action-link.edit:hover {
            background-color: #0d6efd;
            color: white;
        }
        .action-link.save {
            color: #198754;
            background-color: #f8f9fa;
            border-color: #198754;
        }
        .action-link.save:hover {
            background-color: #198754;
            color: white;
        }
        .action-link.import {
            color: #6f42c1;
            background-color: #f8f9fa;
            border-color: #6f42c1;
        }
        .action-link.import:hover {
            background-color: #6f42c1;
            color: white;
        }
        .action-link.import.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        .action-link.import.disabled:hover {
            background-color: #f8f9fa;
            color: #6f42c1;
            transform: none;
            box-shadow: none;
        }
        .action-link i {
            font-size: 14px;
            margin-right: 6px;
        }
        .cell-glow {
            background: #fffbe6 !important;
            box-shadow: 0 0 8px 2px #ffe066 inset;
            transition: background 0.2s;
        }
        .row-glow td, .row-glow .tabulator-cell {
            background: #fffde7 !important;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 2em;
            border-radius: 8px;
            min-width: 350px;
            max-width: 90vw;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 2px 16px rgba(0,0,0,0.2);
        }
        .modal-content table {
            border-collapse: collapse;
            width: 100%;
        }
        .modal-content th, .modal-content td {
            border: 1px solid #ccc;
            padding: 4px 8px;
        }
        .modal-content .modal-actions {
            margin-top: 1em;
            text-align: right;
        }
        .modal-content .modal-actions button {
            margin-left: 1em;
            padding: 0.5em 1.2em;
            font-size: 1em;
        }
        .modal-content .warning {
            color: #d9534f;
            font-weight: bold;
            margin-bottom: 1em;
        }

    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-success text-white p-3">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <img src="assets/gnis_logo.png" alt="GNIS Logo" class="me-4" style="height: 40px;">
                    <h1 class="mb-0 h3">Geodetic Network Information System (GNIS)</h1>
                </div>
                <div class="d-flex align-items-center accnt">
                    <div id="headerAccountDetails" class="me-3 d-none">
                        <a href="account.php" id="headerUserDisplayName" class="fw-bold ms-2 text-decoration-underline text-dark" style="cursor:pointer"></a>
                        <span id="headerUserType" class="ms-2"></span>
                    </div>
                    <button id="loginBtn" class="btn btn-outline-dark d-none"><i class="fas fa-sign-in-alt"></i> Login</button>
                    <button id="logoutBtn" class="btn btn-outline-dark"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-md navbar-dark bg-dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav mx-auto">
                    <a class="nav-link" href="home.php"><i class="fas fa-home"></i> GNIS Home</a>
                    <a class="nav-link" href="index.php"><i class="fas fa-search"></i> Explorer</a>
                    <a class="nav-link" href="tracker.php"><i class="fas fa-map-marker"></i> Tracker</a>
                    <a class="nav-link admin-only d-none active" href="stations_viewer.php"><i class="fas fa-cog"></i> Stations Viewer</a>
                    <a class="nav-link admin-only d-none" href="requests_management.php"><i class="fas fa-tasks"></i> Requests Management</a>
                    <a class="nav-link admin-only d-none" href="users_management.php"><i class="fas fa-users"></i> Users Management</a>
                    <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About Us</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-3" style="padding: 0">
        <!-- Access Denied Message -->
        <div id="accessDenied" class="alert alert-danger text-center">
            <h4 class="alert-heading">Access Denied</h4>
            <p>You do not have permission to access this page. Only administrators can access the stations viewer.</p>
            <hr>
            <p class="mb-0">Please contact your system administrator if you believe this is an error.</p>
        </div>

        <!-- Stations Viewer Interface -->
        <div id="stationsViewerInterface" class="hidden">
            <div class="container-fluid mt-3 mb-3">
                <div class="row">
                    <!-- Left Column: Controls Panel -->
                    <div class="col-md-3" style="padding-right: 0px;">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Stations Viewer Controls</h5>
                            </div>
                            <div class="filter-panel">
                                <div id="errorMessages" class="alert alert-danger hidden"></div>
                                
                                <!-- Table Selection -->
                                <div class="mb-3">
                                    <label for="table-select" class="form-label">Select Table</label>
                                    <select id="table-select" class="form-select">
                                        <option value="hgcp_stations">HGCP Stations</option>
                                        <option value="grav_stations">Grav Stations</option>
                                        <option value="vgcp_stations">VGCP Stations</option>
                                    </select>
                                </div>

                                <!-- Search Bar -->
                                <div class="mb-3">
                                    <label for="search-bar" class="form-label">Search</label>
                                    <input type="text" id="search-bar" class="form-control" placeholder="Search..." />
                                </div>

                                <!-- Action Buttons -->
                                <div class="mb-3">
                                    <a href="#" id="edit-toggle" class="action-link edit" title="Toggle Edit Mode">
                                        <i class="fas fa-edit" id="edit-icon"></i> <span id="edit-label">Edit</span>
                                    </a>
                                    <a href="#" id="upload-btn" class="action-link import disabled" title="Import Data (Edit Mode Required)">
                                        <i class="fas fa-upload"></i> Import
                                    </a>
                                </div>

                                <!-- Instructions -->
                                <div class="alert alert-info">
                                    <h6>Instructions:</h6>
                                    <ul class="mb-0 small">
                                        <li>Select a table to view its data</li>
                                        <li>Use search to filter rows</li>
                                        <li>Toggle edit mode to modify data</li>
                                        <li>Import Excel/CSV files with matching columns</li>
                                        <li>Changes are highlighted in yellow</li>
                                    </ul>
                                </div>
                                <div class="mt-2 mb-3">
                                    <a href="#" id="view-activity-log" class="small text-primary" style="text-decoration:underline; cursor:pointer;">View Activity Log</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Data Table -->
                    <div class="col-md-9" style="padding-left: 0px;">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Stations Data</h5>
                            </div>
                            <div class="content-panel">
                                <div id="stations-table"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden file input for upload -->
    <input type="file" id="file-input" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" style="display:none;" />

    <!-- Modal for preview/warning -->
    <div id="modal" class="modal">
        <div class="modal-content" id="modal-content">
            <!-- Content injected by JS -->
        </div>
    </div>

    <!-- Modal for Activity Log -->
    <div id="activityLogModal" class="modal">
        <div class="modal-content" id="activity-log-content" style="min-width:600px; max-width:90vw; max-height:80vh; overflow:auto;">
            <h5>Station Activity Log</h5>
            <div id="activity-log-table">Loading...</div>
            <div class="modal-actions" style="text-align:right; margin-top:1em;">
                <button onclick="closeActivityLogModal()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script type="module">
        // Import authentication module
        import { checkAuthStatus, hasRole, getCurrentUser, initializeAuth } from './js/users/auth.js';

        // Global Variables
        let tabulatorTable = null;
        let editMode = false;
        let lastLoadedData = [];
        let changedData = [];
        let currentTable = 'hgcp_stations';
        let currentColumns = [];
        let importData = null;

        // Initialize application when DOM is fully loaded
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize authentication
            const authStatus = await initializeAuth();
            const currentUser = getCurrentUser();
            
            console.log('Auth Status:', authStatus);
            console.log('Current User:', currentUser);
            
            if (!authStatus || !authStatus.authenticated) {
                console.log('User not authenticated, redirecting to index.php');
                window.location.href = 'index.php';
                return;
            }
            
            if (!currentUser || (currentUser.user_type !== 'admin' && currentUser.user_type !== 'moderator')) {
                console.log('User is not an admin or moderator, showing access denied');
                document.getElementById('accessDenied').classList.remove('hidden');
                document.getElementById('stationsViewerInterface').classList.add('hidden');
                return;
            }
            
            // User is authenticated and is an admin, show stations viewer interface
            console.log('User is authenticated admin, showing stations viewer interface');
            document.getElementById('accessDenied').classList.add('hidden');
            document.getElementById('stationsViewerInterface').classList.remove('hidden');
            
            // Initialize stations viewer interface
            initializeStationsViewer();
        });

        // Initialize Stations Viewer Interface
        function initializeStationsViewer() {
            // Fetch initial data
            fetchAndRender(currentTable);
            
            // Initialize event listeners
            initializeEventListeners();
        }

        // Initialize Event Listeners
        function initializeEventListeners() {
            const tableSelect = document.getElementById('table-select');
            const editToggle = document.getElementById('edit-toggle');
            const searchBar = document.getElementById('search-bar');
            const fileInput = document.getElementById('file-input');
            const uploadBtn = document.getElementById('upload-btn');

            // Table selection
            tableSelect.addEventListener('change', e => {
                currentTable = e.target.value;
                fetchAndRender(currentTable);
            });

            // Edit toggle
            editToggle.addEventListener('click', () => {
                editMode = !editMode;
                const editIcon = document.getElementById('edit-icon');
                const editLabel = document.getElementById('edit-label');
                if (editMode) {
                    editIcon.className = 'fas fa-save';
                    editLabel.textContent = ' Save';
                    editToggle.className = 'action-link save';
                    uploadBtn.classList.remove('disabled');
                    uploadBtn.title = 'Import Data';
                } else {
                    editIcon.className = 'fas fa-edit';
                    editLabel.textContent = ' Edit';
                    editToggle.className = 'action-link edit';
                    uploadBtn.classList.add('disabled');
                    uploadBtn.title = 'Import Data (Edit Mode Required)';
                }
                fetchAndRender(currentTable); // reload columns to add/remove delete button
                if (!editMode) {
                    // Leaving edit mode, ask to save
                    if (JSON.stringify(tabulatorTable.getData().map(r => {let c = {...r}; delete c.__delete; return c;})) !== JSON.stringify(lastLoadedData)) {
                        const changesSummary = getDetailedChangesSummary();
                        if (confirm(changesSummary)) {
                            saveChanges();
                        } else {
                            // Revert data
                            tabulatorTable.replaceData(lastLoadedData);
                        }
                    }
                }
            });

            // Search bar logic
            searchBar.addEventListener('input', function() {
                const val = this.value;
                if (tabulatorTable) {
                    if (!val) {
                        tabulatorTable.clearFilter();
                    } else {
                        tabulatorTable.setFilter(function(data, filterParams) {
                            return Object.values(data).some(v => v && v.toString().toLowerCase().includes(val.toLowerCase()));
                        });
                    }
                }
            });

            // Upload/Import logic
            uploadBtn.addEventListener('click', function() {
                if (!editMode) {
                    alert('Please enable Edit Mode before importing data.');
                    return;
                }
                fileInput.value = '';
                fileInput.click();
            });

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(evt) {
                    let data = evt.target.result;
                    let workbook = XLSX.read(data, {type: 'binary'});
                    let firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    let json = XLSX.utils.sheet_to_json(firstSheet, {defval: ''});
                    // Validate columns
                    const fileCols = Object.keys(json[0] || {});
                    // Normalize expected columns to handle synonyms (station_id/id, city/city_or_municipality)
                    const normalize = (name) => {
                        if (name === 'station_id') return 'id';
                        if (name === 'city') return 'city_or_municipality';
                        return name;
                    };
                    const expectedCols = currentColumns.map(normalize);
                    const normalizedFileCols = fileCols.map(normalize);
                    const missing = expectedCols.filter(col => !normalizedFileCols.includes(col));
                    const extra = normalizedFileCols.filter(col => !expectedCols.includes(col));
                    if (missing.length > 0 || extra.length > 0) {
                        showModal(`<div class='warning'>Column mismatch detected!</div>
                            <div>The file you are trying to import does not match the columns of the <b>${currentTable}</b> table.</div>
                            <div><b>Missing columns:</b> ${missing.length ? missing.join(', ') : 'None'}</div>
                            <div><b>Extra columns:</b> ${extra.length ? extra.join(', ') : 'None'}</div>
                            <div style='margin-top:1em;'>
                                <b>Instructions:</b><br>
                                - You can only import files for the currently selected table.<br>
                                - For example, you cannot import a <b>grav_stations</b> dataset into <b>hgcp_stations</b> or <b>vgcp_stations</b>, and vice versa.<br>
                                - The file must have <b>the required columns</b> for the current table, in any order.<br>
                                - Accepted header synonyms: <code>station_id</code> or <code>id</code>; <code>city</code> or <code>city_or_municipality</code>.<br>
                                - Save your data as Excel (.xlsx) or CSV (.csv) with the correct column headers.
                            </div>
                            <div class='modal-actions'><button onclick='closeModal()'>Close</button></div>`);
                        importData = null;
                        return;
                    }
                    // Show preview in modal
                    showImportPreview(json, fileCols);
                };
                reader.readAsBinaryString(file);
            });
        }

        function fetchAndRender(tableName) {
            const token = localStorage.getItem('webgnis_token');
            fetch(`stations_viewer_api.php?table=${tableName}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })
                .then(res => res.json())
                .then(json => {
                    if (json.error) {
                        document.getElementById('stations-table').innerHTML = `<div style='color:red'>${json.error}</div>`;
                        return;
                    }
                    currentColumns = json.columns;
                    const columns = [];
                    // Add delete button column in edit mode (at the start)
                    if (editMode) {
                        columns.push({
                            title: '',
                            field: '__delete',
                            formatter: function() { return '<i class="fas fa-trash text-danger" title="Delete Row" style="cursor: pointer;"></i>'; },
                            width: 40,
                            hozAlign: 'center',
                            headerSort: false,
                            cellClick: function(e, cell) {
                                if (editMode && confirm('Delete this row?')) {
                                    cell.getRow().delete();
                                }
                            }
                        });
                    }
                    // Add data columns
                    json.columns.forEach(col => {
                        columns.push({
                            title: col,
                            field: col,
                            editor: editMode && col !== 'station_id' ? 'input' : false,
                            cellEdited: function(cell) {
                                highlightCell(cell);
                            },
                            cellFormatter: function(cell) {
                                // Highlight if changed
                                if (editMode && isCellChanged(cell)) {
                                    cell.getElement().classList.add('cell-glow');
                                } else {
                                    cell.getElement().classList.remove('cell-glow');
                                }
                                return cell.getValue();
                            }
                        });
                    });
                    lastLoadedData = JSON.parse(JSON.stringify(json.data)); // deep copy
                    changedData = [];
                    if (tabulatorTable) {
                        tabulatorTable.setColumns(columns);
                        tabulatorTable.replaceData(json.data);
                    } else {
                        tabulatorTable = new Tabulator("#stations-table", {
                            data: json.data,
                            columns: columns,
                            layout: "fitData",
                            movableColumns: true,
                            height: "70vh",
                            maxHeight: "70vh",
                            placeholder: "No Data Available",
                            cellEdited: function(cell) {
                                if (editMode) {
                                    const row = cell.getRow().getData();
                                    const rowIndex = cell.getRow().getPosition(true);
                                    changedData[rowIndex] = row;
                                    highlightCell(cell);
                                }
                            },
                            rowFormatter: function(row) {
                                // Highlight row if any cell in the row is changed
                                if (editMode && isRowChanged(row)) {
                                    row.getElement().classList.add('row-glow');
                                } else {
                                    row.getElement().classList.remove('row-glow');
                                }
                            },
                            renderVertical: "virtual"
                        });
                    }
                });
        }

        function saveChanges() {
            const token = localStorage.getItem('webgnis_token');
            const currentData = tabulatorTable.getData().map(r => { let c = {...r}; delete c.__delete; return c; });
            // Build maps by key (station_id or id)
            const getKey = (row) => (row.station_id != null && row.station_id !== '') ? String(row.station_id).trim() : (row.id != null ? String(row.id).trim() : null);
            const originalMap = {};
            lastLoadedData.forEach(r => { const k = getKey(r); if (k) originalMap[k] = r; });
            const currentMap = {};
            currentData.forEach(r => { const k = getKey(r); if (k) currentMap[k] = r; });
            // Compute deletes and upserts
            const deleteIds = [];
            Object.keys(originalMap).forEach(k => { if (!(k in currentMap)) deleteIds.push(k); });
            const upserts = [];
            Object.keys(currentMap).forEach(k => {
                const cur = currentMap[k];
                const orig = originalMap[k];
                if (!orig) { upserts.push(cur); return; }
                // Compare using areValuesEquivalent
                let changed = false;
                for (const col of currentColumns) {
                    if (col === '__delete') continue;
                    if (!areValuesEquivalent(orig[col], cur[col])) { changed = true; break; }
                }
                if (changed) upserts.push(cur);
            });
            fetch('stations_viewer_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    table: currentTable,
                    data: upserts,
                    append: true,
                    deleteIds: deleteIds
                })
            })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    alert('Changes saved!');
                    fetchAndRender(currentTable);
                } else {
                    alert('Save failed: ' + (json.error || 'Unknown error'));
                }
            });
        }

        function appendData(newRows) {
            // Remove __delete field if present
            newRows = newRows.map(row => {
                let c = {...row};
                delete c.__delete;
                // Normalize header synonyms from import to match backend expectations
                if (c.id && !c.station_id) c.station_id = c.id;
                if (c.city_or_municipality && !c.city) c.city = c.city_or_municipality;
                return c;
            });
            
            // Process the data: replace existing records with same station_id, add new ones
            const processedData = processImportData(newRows);
            
            const token = localStorage.getItem('webgnis_token');
            fetch('stations_viewer_api.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    table: currentTable,
                    data: processedData,
                    append: true
                })
            })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    alert('Data appended!');
                    fetchAndRender(currentTable);
                } else {
                    alert('Append failed: ' + (json.error || 'Unknown error'));
                }
            });
        }

        function processImportData(newRows) {
            const result = [...lastLoadedData]; // Start with existing data
            
            newRows.forEach(newRow => {
                const stationId = newRow.station_id;
                if (!stationId) {
                    result.push(newRow); // Add new record without station_id
                    return;
                }
                
                // Check if this station_id already exists
                const existingIndex = result.findIndex(existing => existing.station_id === stationId);
                if (existingIndex === -1) {
                    result.push(newRow); // Add new record
                } else {
                    result[existingIndex] = newRow; // Replace existing record
                }
            });
            
            return result;
        }

        // Modal helpers
        function showModal(html) {
            document.getElementById('modal-content').innerHTML = html;
            document.getElementById('modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modal-content').innerHTML = '';
        }
        window.closeModal = closeModal; // for inline onclick

        // Close modal on outside click
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Helper to check if a cell is changed
        function isCellChanged(cell) {
            const rowIndex = cell.getRow().getPosition(true);
            const field = cell.getField();
            const current = cell.getValue();
            const original = lastLoadedData[rowIndex] ? lastLoadedData[rowIndex][field] : undefined;
            return current !== original;
        }
        // Helper to check if a row is changed
        function isRowChanged(row) {
            const rowIndex = row.getPosition(true);
            const current = row.getData();
            const original = lastLoadedData[rowIndex];
            if (!original) return true;
            for (let key in current) {
                if (key === '__delete') continue;
                if (current[key] !== original[key]) return true;
            }
            return false;
        }
        // Highlight a cell that was changed
        function highlightCell(cell) {
            if (isCellChanged(cell)) {
                cell.getElement().classList.add('cell-glow');
            } else {
                cell.getElement().classList.remove('cell-glow');
            }
            // Also update row highlight
            const row = cell.getRow();
            if (isRowChanged(row)) {
                row.getElement().classList.add('row-glow');
            } else {
                row.getElement().classList.remove('row-glow');
            }
        }
        // Get summary of changes
        function getChangesSummary() {
            if (!tabulatorTable) return [];
            const changes = [];
            const currentData = tabulatorTable.getData();
            
            currentData.forEach((row, index) => {
                const original = lastLoadedData[index];
                if (!original) return;
                
                const rowChanges = [];
                Object.keys(row).forEach(key => {
                    if (key === '__delete') return;
                    if (row[key] !== original[key]) {
                        rowChanges.push(`${key}: "${original[key]}" → "${row[key]}"`);
                    }
                });
                
                if (rowChanges.length > 0) {
                    const stationId = row.station_id || row.id || `Row ${index + 1}`;
                    const stationName = row.station_name || row.name || '';
                    const identifier = stationName ? `${stationId} (${stationName})` : stationId;
                    changes.push(`${identifier}:`);
                    rowChanges.forEach(change => {
                        changes.push(`  • ${change}`);
                    });
                }
            });
            
            return changes;
        }

        // Get detailed summary of changes with separate sections for new data, updates, and deletions
        function getDetailedChangesSummary() {
            if (!tabulatorTable) return 'No changes detected.';
            
            const currentData = tabulatorTable.getData();
            // Build maps by station_id for fast lookup
            const originalMap = {};
            lastLoadedData.forEach(row => {
                if (row.station_id != null) originalMap[String(row.station_id).trim()] = row;
            });
            const currentMap = {};
            currentData.forEach(row => {
                if (row.station_id != null) currentMap[String(row.station_id).trim()] = row;
            });
            // Find deletions
            const deletions = [];
            Object.keys(originalMap).forEach(stationId => {
                if (!(stationId in currentMap)) {
                    const row = originalMap[stationId];
                    const stationName = row.station_name || '';
                    const identifier = stationName ? `${stationId} (${stationName})` : stationId;
                    deletions.push(identifier);
                }
            });
            // Find new records
            const newRecords = [];
            Object.keys(currentMap).forEach(stationId => {
                if (!(stationId in originalMap)) {
                    const row = currentMap[stationId];
                    const stationName = row.station_name || '';
                    const identifier = stationName ? `${stationId} (${stationName})` : stationId;
                    newRecords.push({ identifier, data: row });
                }
            });
            // Find updates
            const updates = [];
            Object.keys(currentMap).forEach(stationId => {
                if (stationId in originalMap) {
                    const row = currentMap[stationId];
                    const original = originalMap[stationId];
                    const rowChanges = [];
                    Object.keys(row).forEach(key => {
                        if (key === '__delete') return;
                        if (row[key] !== original[key]) {
                            rowChanges.push(`${key}: "${original[key]}" → "${row[key]}"`);
                        }
                    });
                    if (rowChanges.length > 0) {
                        const stationName = row.station_name || '';
                        const identifier = stationName ? `${stationId} (${stationName})` : stationId;
                        updates.push({ identifier, changes: rowChanges });
                    }
                }
            });
            // Build the summary message
            let summary = 'Do you want to save the following changes?\n\n';
            if (deletions.length > 0) {
                summary += `🗑️ DELETIONS (${deletions.length}):\n`;
                deletions.forEach(identifier => {
                    summary += `  • ${identifier}\n`;
                });
                summary += '\n';
            }
            if (newRecords.length > 0) {
                summary += `📝 NEW RECORDS (${newRecords.length}):\n`;
                newRecords.forEach(record => {
                    summary += `  • ${record.identifier}\n`;
                });
                summary += '\n';
            }
            if (updates.length > 0) {
                summary += `🔄 UPDATES (${updates.length}):\n`;
                updates.forEach(update => {
                    summary += `  • ${update.identifier}:\n`;
                    update.changes.forEach(change => {
                        summary += `    - ${change}\n`;
                    });
                });
                summary += '\n';
            }
            if (deletions.length === 0 && newRecords.length === 0 && updates.length === 0) {
                // If nothing changed, return a neutral message
                summary = 'No changes detected.';
            }
            return summary;
        }

        // Helper: compare two values, treating numerics like 121 and 121.000 as equal
        function areValuesEquivalent(a, b) {
            // If both are null/undefined/empty, treat as equal
            if ((a === null || a === undefined || a === '') && (b === null || b === undefined || b === '')) return true;
            // Treat empty upload as equal to default DB values (0.000, 0.0000, 0.000000, 0, 0000-00-00, etc)
            const defaultValues = ['0.000', '0.0000', '0.000000', '0', '0000-00-00', '0000-00-00 00:00:00'];
            // If one is empty and the other is a default value, treat as equal
            if ((a === null || a === undefined || a === '') && (defaultValues.includes(String(b)) || (!isNaN(b) && Number(b) === 0))) return true;
            if ((b === null || b === undefined || b === '') && (defaultValues.includes(String(a)) || (!isNaN(a) && Number(a) === 0))) return true;
            // If both are numeric (or can be parsed as numbers), compare as numbers
            if (!isNaN(a) && !isNaN(b) && a !== '' && b !== '') {
                return Number(a) === Number(b);
            }
            // Otherwise, compare as strings (trimmed)
            return String(a ?? '').trim() === String(b ?? '').trim();
        }

        function showImportPreview(newData, columns) {
            // Analyze the data: separate new records from updates and no changes
            const analysis = analyzeImportData(newData);
            
            let html = `
                <div style="max-width: 90vw; max-height: 80vh; display: flex; flex-direction: column;">
                    <div style="display:flex; justify-content:flex-end; align-items:center; gap:1em; background:#fff; border-bottom:1px solid #eee; padding: 1em 2em;">
                        <button onclick='closeModal()' class="btn btn-secondary">Cancel</button>
                        <button id='modal-import-btn' class="btn btn-primary">Import</button>
                    </div>
                    <div style="overflow:auto; flex:1 1 auto; background:#fff; padding: 2em;">
                        <h4>Import Preview</h4>
                        <!-- Accordion for collapsible sections -->
                        <div class="accordion" id="importAccordion">
                        <!-- New Records Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingNew">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNew" aria-expanded="true" aria-controls="collapseNew">
                                    New Records (${analysis.newRecords.length})
                                </button>
                            </h2>
                            <div id="collapseNew" class="accordion-collapse collapse show" aria-labelledby="headingNew" data-bs-parent="#importAccordion">
                                <div class="accordion-body">
                                    <input type="text" id="search-new" class="form-control mb-2" placeholder="Search new records...">
                                    <div id="new-records-container" style="max-height: 350px; overflow-y: auto; overflow-x: hidden;">
                                        ${generateEditableVerticalTable(analysis.newRecords, columns, 'new', 1)}
                                    </div>
                                    ${generatePagination('new', analysis.newRecords.length, 1)}
                                </div>
                            </div>
                        </div>
                        <!-- Updates Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingUpdates">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUpdates" aria-expanded="true" aria-controls="collapseUpdates">
                                    Updates (${analysis.updates.length})
                                </button>
                            </h2>
                            <div id="collapseUpdates" class="accordion-collapse collapse show" aria-labelledby="headingUpdates" data-bs-parent="#importAccordion">
                                <div class="accordion-body">
                                    <input type="text" id="search-updates" class="form-control mb-2" placeholder="Search updates...">
                                    <div id="updates-container" style="max-height: 350px; overflow-y: auto; overflow-x: hidden;">
                                        ${generateUpdatesTable(analysis.updates, columns, 1)}
                                    </div>
                                    ${generatePagination('updates', analysis.updates.length, 1)}
                                </div>
                            </div>
                        </div>
                        <!-- No Changes Section -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingNoChanges">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNoChanges" aria-expanded="false" aria-controls="collapseNoChanges">
                                    No Changes (${analysis.noChanges.length})
                                </button>
                            </h2>
                            <div id="collapseNoChanges" class="accordion-collapse collapse" aria-labelledby="headingNoChanges" data-bs-parent="#importAccordion">
                                <div class="accordion-body">
                                    <input type="text" id="search-nochanges" class="form-control mb-2" placeholder="Search no changes...">
                                    <div id="nochanges-container" style="max-height: 350px; overflow-y: auto; overflow-x: hidden;">
                                        ${generateEditableVerticalTable(analysis.noChanges, columns, 'nochanges', 1)}
                                    </div>
                                    ${generatePagination('nochanges', analysis.noChanges.length, 1)}
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>`;
            
            showModal(html);
            importData = newData;
            
            // Store analysis data globally for pagination/search
            window.importAnalysis = analysis;
            window.importColumns = columns;
            window.currentPages = { new: 1, updates: 1, nochanges: 1 };
            window.filteredData = { new: analysis.newRecords, updates: analysis.updates, nochanges: analysis.noChanges };
            
            // Initialize search and pagination
            initializeImportControls();
            
            setTimeout(() => {
                document.getElementById('modal-import-btn').onclick = function() {
                    // Get the current data from the editable tables
                    const finalData = getFinalImportData();
                    appendData(finalData);
                    closeModal();
                };
            }, 0);
        }

        function analyzeImportData(newData) {
            const newRecords = [];
            const updates = [];
            const noChanges = [];
            
            newData.forEach(newRow => {
                const stationId = newRow.station_id;
                if (!stationId) {
                    newRecords.push(newRow);
                    return;
                }
                // Type-agnostic, trimmed comparison for station_id
                const existingIndex = lastLoadedData.findIndex(existing =>
                    existing.station_id != null &&
                    String(existing.station_id).trim() === String(stationId).trim()
                );
                if (existingIndex === -1) {
                    newRecords.push(newRow);
                } else {
                    // Check for changes
                    const existingRow = lastLoadedData[existingIndex];
                    let hasChange = false;
                    for (const col of currentColumns) {
                        if (!areValuesEquivalent(existingRow[col], newRow[col])) {
                            hasChange = true;
                            break;
                        }
                    }
                    if (hasChange) {
                        updates.push({
                            new: newRow,
                            existing: existingRow,
                            index: existingIndex
                        });
                    } else {
                        noChanges.push({
                            new: newRow,
                            existing: existingRow,
                            index: existingIndex
                        });
                    }
                }
            });
            
            return { newRecords, updates, noChanges };
        }

        function generateRecordsTable(records, columns, type, page = 1) {
            if (records.length === 0) {
                return '<p class="text-muted">No records to show.</p>';
            }
            
            const pageSize = 5;
            const startIndex = (page - 1) * pageSize;
            const endIndex = startIndex + pageSize;
            const pageRecords = records.slice(startIndex, endIndex);
            
            let html = `<table class="table table-sm table-striped">
                <thead><tr>`;
            html += `<th></th>`; // Trash icon column
            columns.forEach(col => { html += `<th>${col}</th>`; });
            html += `</tr></thead><tbody>`;
            
            pageRecords.forEach((row, index) => {
                html += '<tr>';
                // Trash icon
                html += `<td><button class="btn btn-link text-danger p-0" title="Remove row" onclick="dropImportRow('new', ${startIndex + index})"><i class="fas fa-trash"></i></button></td>`;
                columns.forEach(col => { 
                    const value = row[col] || '';
                    const isEditable = col !== 'station_id';
                    html += `<td>${isEditable ? 
                        `<input type="text" class="form-control form-control-sm" value="${value}" 
                         data-row="${startIndex + index}" data-col="${col}" onchange="updateImportData('${type}', ${startIndex + index}, '${col}', this.value)">` : 
                        value}`;
                });
                html += '</tr>';
            });
            
            html += `</tbody></table>`;
            return html;
        }

        function generateUpdatesTable(updates, columns, page = 1) {
            if (updates.length === 0) {
                return '<p class="text-muted">No updates to show.</p>';
            }
            
            const pageSize = 5;
            const startIndex = (page - 1) * pageSize;
            const endIndex = startIndex + pageSize;
            const pageUpdates = updates.slice(startIndex, endIndex);
            
            // Known long text fields
            const longTextFields = ['description', 'remarks', 'notes'];
            
            let html = `<table class="table table-sm table-striped">
                <thead><tr><th></th><th>Station ID</th><th>Field</th><th>Current Value</th><th>New Value</th></tr></thead><tbody>`;
            
            pageUpdates.forEach((update, index) => {
                const stationId = update.new.station_id;
                const stationName = update.new.station_name || '';
                const identifier = stationName ? `${stationId} (${stationName})` : stationId;
                
                let firstRow = true;
                columns.forEach(col => {
                    const isChanged = !areValuesEquivalent(update.new[col], update.existing[col]);
                    html += `<tr>`;
                    // Trash icon (only on first field row)
                    if (firstRow) {
                        html += `<td rowspan="${columns.length}"><button class="btn btn-link text-danger p-0" title="Remove row" onclick="dropImportRow('updates', ${startIndex + index})"><i class="fas fa-trash"></i></button></td>`;
                        html += `<td rowspan="${columns.length}">${identifier}</td>`;
                        firstRow = false;
                    }
                    html += `<td><strong>${col}</strong></td>`;
                    html += `<td class="text-muted">${update.existing[col] || ''}</td>`;
                    // Use textarea for long text fields
                    const value = update.new[col] || '';
                    const isLong = longTextFields.includes(col) || String(value).length > 30 || String(update.existing[col] || '').length > 30;
                    if (isLong) {
                        html += `<td class="${isChanged ? '' : 'text-muted'}" style="${isChanged ? 'background-color: #ffeb3b; font-weight: bold; border-left: 5px solid #ff9800;' : ''}">
                            <textarea class="form-control form-control-sm" style="min-width:180px; min-height:2.5em; resize:vertical;" data-row="${startIndex + index}" data-col="${col}" onchange="updateImportData('updates', ${startIndex + index}, '${col}', this.value)">${value}</textarea>
                        </td>`;
                    } else {
                        html += `<td class="${isChanged ? '' : 'text-muted'}" style="${isChanged ? 'background-color: #ffeb3b; font-weight: bold; border-left: 5px solid #ff9800;' : ''}">
                            <input type="text" class="form-control form-control-sm" value="${value}" 
                             data-row="${startIndex + index}" data-col="${col}" onchange="updateImportData('updates', ${startIndex + index}, '${col}', this.value)">
                        </td>`;
                    }
                    html += `</tr>`;
                });
            });
            
            html += `</tbody></table>`;
            return html;
        }

        function generateNoChangesTable(noChanges, columns, page = 1) {
            if (noChanges.length === 0) {
                return '<p class="text-muted">No unchanged records to show.</p>';
            }
            const pageSize = 5;
            const startIndex = (page - 1) * pageSize;
            const endIndex = startIndex + pageSize;
            const pageNoChanges = noChanges.slice(startIndex, endIndex);
            let html = `<table class="table table-sm table-striped">
                <thead><tr><th>Station ID</th>`;
            columns.forEach(col => { html += `<th>${col}</th>`; });
            html += `</tr></thead><tbody>`;
            pageNoChanges.forEach((rowObj) => {
                const row = rowObj.new;
                const stationId = row.station_id || row.id || '';
                html += `<tr><td>${stationId}</td>`;
                columns.forEach(col => {
                    html += `<td>${row[col] || ''}</td>`;
                });
                html += `</tr>`;
            });
            html += `</tbody></table>`;
            return html;
        }

        function getChangedFieldsCount(newRow, existingRow, columns) {
            return columns.filter(col => newRow[col] !== existingRow[col]).length;
        }

        function generatePagination(type, totalRecords, currentPage = 1) {
            const pageSize = 5;
            const totalPages = Math.ceil(totalRecords / pageSize);
            
            if (totalPages <= 1) return '';
            
            let html = `<nav><ul class="pagination pagination-sm justify-content-center">`;
            
            // Previous button
            if (currentPage > 1) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="changePage('${type}', ${currentPage - 1})">Previous</a>
                </li>`;
            }
            
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage('${type}', ${i})">${i}</a>
                </li>`;
            }
            
            // Next button
            if (currentPage < totalPages) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="changePage('${type}', ${currentPage + 1})">Next</a>
                </li>`;
            }
            
            html += `</ul></nav>`;
            return html;
        }

        function initializeImportControls() {
            // Search functionality
            document.getElementById('search-new').addEventListener('input', function() {
                filterRecords('new', this.value);
            });
            
            document.getElementById('search-updates').addEventListener('input', function() {
                filterRecords('updates', this.value);
            });
            // No changes search
            document.getElementById('search-nochanges').addEventListener('input', function() {
                filterRecords('nochanges', this.value);
            });
        }

        function filterRecords(type, searchTerm) {
            if (!window.importAnalysis) return;
            
            const allRecords = type === 'new' ? window.importAnalysis.newRecords :
                               type === 'updates' ? window.importAnalysis.updates :
                               window.importAnalysis.noChanges;
            
            if (!searchTerm) {
                window.filteredData[type] = allRecords;
            } else {
                window.filteredData[type] = allRecords.filter(record => {
                    if (type === 'new') {
                        return Object.values(record).some(value => 
                            value && value.toString().toLowerCase().includes(searchTerm.toLowerCase())
                        );
                    } else if (type === 'updates') {
                        return Object.values(record.new).some(value => 
                            value && value.toString().toLowerCase().includes(searchTerm.toLowerCase())
                        );
                    } else { // nochanges
                        return Object.values(record.new).some(value => 
                            value && value.toString().toLowerCase().includes(searchTerm.toLowerCase())
                        );
                    }
                });
            }
            
            // Reset to first page and update display
            window.currentPages[type] = 1;
            updateTableDisplay(type);
        }

        function changePage(type, page) {
            if (!window.importAnalysis) return;
            
            const totalRecords = window.filteredData[type].length;
            const pageSize = 5;
            const totalPages = Math.ceil(totalRecords / pageSize);
            
            if (page < 1 || page > totalPages) return;
            
            window.currentPages[type] = page;
            updateTableDisplay(type);
        }

        function updateTableDisplay(type) {
            if (!window.importAnalysis || !window.importColumns) return;
            
            let container;
            let records;
            let currentPage;
            if (type === 'new') {
                container = document.getElementById('new-records-container');
                records = window.filteredData.new;
                currentPage = window.currentPages.new;
                container.innerHTML = generateEditableVerticalTable(records, window.importColumns, type, currentPage);
            } else if (type === 'updates') {
                container = document.getElementById('updates-container');
                records = window.filteredData.updates;
                currentPage = window.currentPages.updates;
                container.innerHTML = generateUpdatesTable(records, window.importColumns, currentPage);
            } else if (type === 'nochanges') {
                container = document.getElementById('nochanges-container');
                records = window.filteredData.nochanges;
                currentPage = window.currentPages.nochanges;
                container.innerHTML = generateEditableVerticalTable(records, window.importColumns, type, currentPage);
            }
            // Update pagination
            const cardBody = container.parentElement;
            const existingPagination = cardBody.querySelector('nav');
            if (existingPagination) {
                existingPagination.outerHTML = generatePagination(type, records.length, currentPage);
            }
        }

        function updateImportData(type, rowIndex, column, value) {
            if (!window.importAnalysis) return;
            
            if (type === 'new') {
                const actualIndex = (window.currentPages.new - 1) * 5 + rowIndex;
                if (window.importAnalysis.newRecords[actualIndex]) {
                    window.importAnalysis.newRecords[actualIndex][column] = value;
                }
            } else if (type === 'updates') {
                const actualIndex = (window.currentPages.updates - 1) * 5 + rowIndex;
                if (window.importAnalysis.updates[actualIndex]) {
                    window.importAnalysis.updates[actualIndex].new[column] = value;
                }
            } else if (type === 'nochanges') {
                const actualIndex = (window.currentPages.nochanges - 1) * 5 + rowIndex;
                if (window.importAnalysis.noChanges[actualIndex]) {
                    window.importAnalysis.noChanges[actualIndex].new[column] = value;
                }
            }
        }

        function getFinalImportData() {
            if (!window.importAnalysis) return [];
            
            const finalData = [];
            
            // Add new records
            finalData.push(...window.importAnalysis.newRecords);
            
            // Add updated records (only the new data)
            finalData.push(...window.importAnalysis.updates.map(update => update.new));
            
            return finalData;
        }

        // Editable vertical table for New Records and No Changes (like Updates layout)
        function generateEditableVerticalTable(records, columns, type, page = 1) {
            if (records.length === 0) {
                return '<p class="text-muted">No records to show.</p>';
            }
            const pageSize = 5;
            const startIndex = (page - 1) * pageSize;
            const endIndex = startIndex + pageSize;
            const pageRecords = records.slice(startIndex, endIndex);
            // Known long text fields
            const longTextFields = ['description', 'remarks', 'notes'];
            let html = '';
            pageRecords.forEach((row, recIdx) => {
                const record = row.new ? row.new : row;
                const stationId = record.station_id || record.id || '';
                html += `<table class="table table-sm table-bordered mb-4" style="background:#fff;">
                    <tbody>`;
                let firstRow = true;
                columns.forEach(col => {
                    html += '<tr>';
                    if (firstRow) {
                        html += `<td rowspan="${columns.length}" style="vertical-align:top; text-align:center; width:60px; background:#f8f9fa;">
                            <button class="btn btn-link text-danger p-0 mb-2" title="Remove row" onclick="dropImportRow('${type}', ${startIndex + recIdx})"><i class="fas fa-trash"></i></button><br>
                            <b>${stationId}</b>
                        </td>`;
                        firstRow = false;
                    }
                    html += `<td style="width:160px;"><strong>${col}</strong></td>`;
                    const value = record[col] || '';
                    const isEditable = col !== 'station_id';
                    const isLong = longTextFields.includes(col) || String(value).length > 30;
                    if (isEditable && isLong) {
                        html += `<td><textarea class="form-control form-control-sm" style="min-width:180px; min-height:2.5em; resize:vertical;" data-row="${startIndex + recIdx}" data-col="${col}" onchange="updateImportData('${type}', ${startIndex + recIdx}, '${col}', this.value)">${value}</textarea></td>`;
                    } else if (isEditable) {
                        html += `<td><input type="text" class="form-control form-control-sm" value="${value}" data-row="${startIndex + recIdx}" data-col="${col}" onchange="updateImportData('${type}', ${startIndex + recIdx}, '${col}', this.value)"></td>`;
                    } else {
                        html += `<td>${value}</td>`;
                    }
                    html += '</tr>';
                });
                html += '</tbody></table>';
            });
            return html;
        }

        // Drop row from import preview
        function dropImportRow(type, index) {
            if (!window.importAnalysis) return;
            if (type === 'new') {
                window.importAnalysis.newRecords.splice(index, 1);
                window.filteredData.new = window.importAnalysis.newRecords;
                updateTableDisplay('new');
            } else if (type === 'updates') {
                window.importAnalysis.updates.splice(index, 1);
                window.filteredData.updates = window.importAnalysis.updates;
                updateTableDisplay('updates');
            } else if (type === 'nochanges') {
                window.importAnalysis.noChanges.splice(index, 1);
                window.filteredData.nochanges = window.importAnalysis.noChanges;
                updateTableDisplay('nochanges');
            }
        }
        window.dropImportRow = dropImportRow;

        // Activity Log Modal logic
        document.getElementById('view-activity-log').addEventListener('click', function(e) {
            e.preventDefault();
            showActivityLogModal();
        });

        function showActivityLogModal() {
            document.getElementById('activityLogModal').style.display = 'flex';
            fetchActivityLog();
        }
        function closeActivityLogModal() {
            document.getElementById('activityLogModal').style.display = 'none';
        }
        window.closeActivityLogModal = closeActivityLogModal;

        // Fetch and display activity log
        function fetchActivityLog() {
            fetch('stations_log.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('activity-log-table').innerHTML = '<div class="text-danger">Failed to load activity log.</div>';
                        return;
                    }
                    const logs = data.logs;
                    if (!logs.length) {
                        document.getElementById('activity-log-table').innerHTML = '<div class="text-muted">No activity yet.</div>';
                        return;
                    }
                    let html = '<table class="table table-sm table-striped"><thead><tr>' +
                        '<th>Timestamp</th><th>Table</th><th>Station ID</th><th>Admin</th><th>Action</th><th>Details</th></tr></thead><tbody>';
                    logs.forEach(log => {
                        let tableName = 'Unknown';
                        let details = '';
                        try {
                            const parsedDetails = JSON.parse(log.details);
                            if (parsedDetails.table) {
                                tableName = parsedDetails.table;
                            }
                            if (log.action === 'add' || log.action === 'delete') {
                                const data = parsedDetails.data ? (typeof parsedDetails.data === 'string' ? JSON.parse(parsedDetails.data) : parsedDetails.data) : {};
                                details = `<b>Station Name:</b> ${data.station_name || ''}<br>` +
                                          `<b>Location:</b> ${data.city || ''}, ${data.province || ''}<br>` +
                                          `<b>Description:</b> ${data.description || ''}`;
                            } else if (log.action === 'update') {
                                details = '';
                                if (parsedDetails.changes && parsedDetails.changes.length) {
                                    details += '<b>Changed Fields:</b><ul style="margin-bottom:0">';
                                    parsedDetails.changes.forEach(change => {
                                        details += `<li>${change}</li>`;
                                    });
                                    details += '</ul>';
                                }
                            } else {
                                details = log.details;
                            }
                        } catch (e) {
                            details = log.details;
                        }
                        html += `<tr><td>${log.timestamp}</td><td>${tableName}</td><td>${log.station_id}</td><td>${log.admin_user}</td><td>${log.action}</td><td>${details}</td></tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('activity-log-table').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('activity-log-table').innerHTML = '<div class="text-danger">Failed to load activity log.</div>';
                });
        }

        // Close activity log modal on outside click
        document.getElementById('activityLogModal').addEventListener('click', function(e) {
            if (e.target === this) closeActivityLogModal();
        });
    </script>
</body>
</html> 