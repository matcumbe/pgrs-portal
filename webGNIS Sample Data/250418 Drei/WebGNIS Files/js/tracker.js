import PaymentController from './payment.js';
import { usersApi } from './users/api-client.js';
import { checkAuthStatus } from './users/auth.js';

const API_BASE_URL = '.'; // Adjust if your API files are in a different directory

document.addEventListener('DOMContentLoaded', () => {
    const requestsTableBody = document.querySelector('#requestsTable tbody');
    const loadingMessage = document.getElementById('loadingMessage');
    const noRequestsMessage = document.getElementById('noRequestsMessage');
    const statusFilterContainer = document.getElementById('statusFilterContainer');
    let currentStatusFilter = null;
    let statusList = [];
    let bsPaymentModal = null;
    
    // Add this variable to store the cached requests at the top of the file (after existing variables)
    let allFetchedRequests = [];
    const itemsPerPage = 10; // Or your preferred number for the tracker page
    let currentPage = 1; // Keep track of the current page
    
    try {
        const paymentModalEl = document.getElementById('paymentModal');
        if (paymentModalEl) {
            bsPaymentModal = new bootstrap.Modal(paymentModalEl);
        }
    } catch (e) {
        console.error("Failed to initialize Bootstrap modal for payment:", e);
    }

    // --- Authentication and User ID --- 
    async function getCurrentUserInfo() {
        // Try using the API to get current user info
        try {
            const userData = await usersApi.getCurrentUser();
            if (userData && userData.data) {
                console.log('Current user data from API:', userData.data);
                return userData.data;
            }
        } catch (error) {
            console.error('Error getting current user from API:', error);
        }
        
        return null;
    }

    function getUserId() {
        try {
            const user = JSON.parse(localStorage.getItem('gnisUser'));
            // Check for user_id first (API format), then id (possible format from JWT)
            if (user) {
                console.log('User from localStorage:', user);
                if (user.user_id) {
                    return parseInt(user.user_id);
                } else if (user.id) {
                    return parseInt(user.id);
                }
            }
            
            // Fallback: try to get user ID from JWT token payload
            const token = localStorage.getItem('webgnis_token');
            if (token) {
                try {
                    const tokenParts = token.split('.');
                    if (tokenParts.length !== 3) {
                        console.error('Invalid token format');
                        return null;
                    }
                    
                    // Make the base64 string URL safe for decoding
                    let base64Payload = tokenParts[1].replace(/-/g, '+').replace(/_/g, '/');
                    // Add padding if needed
                    while (base64Payload.length % 4 !== 0) {
                        base64Payload += '=';
                    }
                    
                    // Decode base64
                    const decodedPayload = atob(base64Payload);
                    // Parse JSON
                    const tokenData = JSON.parse(decodedPayload);
                    console.log('User data from token:', tokenData);
                    
                    if (tokenData.user_id) {
                        return parseInt(tokenData.user_id);
                    }
                } catch (e) {
                    console.error('Failed to extract user ID from token:', e);
                }
            }
            
            return null;
        } catch (e) {
            console.error('Error getting user ID:', e);
            return null;
        }
    }

    function getToken() {
        return localStorage.getItem('webgnis_token'); 
    }

    function checkAuth() {
        if (!usersApi.isAuthenticated()) {
            console.warn('User not logged in. Displaying limited or no data.');
            if (requestsTableBody) requestsTableBody.innerHTML = '';
            if (noRequestsMessage) {
                noRequestsMessage.innerHTML = '<p>Please <a href="#" data-bs-toggle="modal" data-bs-target="#authModal">login</a> to view your requests.</p>';
                noRequestsMessage.style.display = 'block';
            }
            if (loadingMessage) loadingMessage.style.display = 'none';
            return false;
        }
        return true;
    }

    // --- API Calls ---
    async function fetchRequestStatuses() {
        const token = getToken();
        try {
            const response = await fetch(`${API_BASE_URL}/requests_api.php?action=statuses`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (result.status === 'success') {
                statusList = result.data || []; // Store for badge coloring
                return statusList;
            }
            console.error('Failed to fetch request statuses:', result.message);
            return [];
        } catch (error) {
            console.error('Error fetching request statuses:', error);
            return [];
        }
    }

    // Modified to fetch a single page of user requests - will be used by fetchAllUserRequestPagesSequentially
    async function fetchUserRequestsPage(userId, statusId = null, page = 1, perPage = itemsPerPage) {
        if (!userId) {
            console.error('No userId provided to fetchUserRequestsPage');
            return { requests: [], pagination: { total: 0, per_page: perPage, current_page: page, last_page: 1 } };
        }
        const token = getToken();
        if (!token) {
            console.error('No authentication token available');
            return { requests: [], pagination: { total: 0, per_page: perPage, current_page: page, last_page: 1 } };
        }
        let url = `${API_BASE_URL}/requests_api.php?action=list&user=${userId}&page=${page}&per_page=${perPage}`;
        if (statusId) { // If an initial server-side filter is ever needed for the base set
            url += `&status=${statusId}`;
        }
        console.log('Fetching user requests page from URL:', url);
        try {
            const response = await fetch(url, { headers: { 'Authorization': `Bearer ${token}` } });
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse API response as JSON:', parseError, responseText.substring(0,500));
                return { requests: [], pagination: { total: 0, per_page: perPage, current_page: page, last_page: 1 } };
            }
            if (!response.ok) {
                console.error(`API error! status: ${response.status}, message:`, result.message || 'Unknown error');
                return { requests: [], pagination: { total: 0, per_page: perPage, current_page: page, last_page: 1 } };
            }
            if (result.status === 'success') {
                 return {
                    requests: result.data.requests || [],
                    pagination: result.data.pagination || { total: 0, per_page: perPage, current_page: page, last_page: 1 }
                };
            }
            console.error('Failed to fetch user requests page:', result.message);
            return { requests: [], pagination: { total: 0, per_page: perPage, current_page: page, last_page: 1 } };
        } catch (error) {
            console.error('Error fetching user requests page:', error);
            return { requests: [], pagination: { total: 0, per_page: perPage, current_page: page, last_page: 1 } };
        }
    }

    // New function to fetch all pages of user requests sequentially
    async function fetchAllUserRequestPagesSequentially(userId, initialStatusId = null) {
        let allItems = [];
        let page = 1;
        let hasMorePages = true;
        const queryPerPage = 50; // Fetch in larger chunks

        console.log(`Starting to fetch all request pages for user ${userId}.`);

        while (hasMorePages) {
            const result = await fetchUserRequestsPage(userId, initialStatusId, page, queryPerPage);
            
            if (result.requests && result.requests.length > 0) {
                allItems = allItems.concat(result.requests);
                console.log(`Fetched ${result.requests.length} requests from page ${page}. Total items for user ${userId} so far: ${allItems.length}`);
            }

            if (result.pagination) {
                const { current_page, last_page } = result.pagination;
                if (current_page >= last_page || result.requests.length < queryPerPage) {
                    hasMorePages = false;
                    console.log(`Reached the last page for user ${userId} or fetched less than perPage items.`);
                } else {
                    page++;
                }
            } else {
                hasMorePages = false; // Safety break if pagination info is missing
                console.warn('No pagination info in API response for user requests, assuming all items fetched.');
            }
            
            // Safety break in case of an issue preventing hasMorePages from becoming false
            if (page > (result.pagination?.last_page || 1) + 5 && page > 100) { // Allow a few extra pages, then cap
                 console.error("fetchAllUserRequestPagesSequentially safety break: Exceeded expected page count.");
                 hasMorePages = false;
            }
        }
        console.log(`Finished fetching all request pages for user ${userId}. Total items fetched: ${allItems.length}`);
        return allItems;
    }

    async function fetchRequestDetails(requestId) {
        const token = getToken();
        try {
            console.log(`Fetching request details for ID: ${requestId}`);
            
            // Based on the PHP code, the ID should be in the action parameter like "view/23"
            const url = `${API_BASE_URL}/requests_api.php?action=view/${requestId}`;
            console.log(`Making request to: ${url}`);
            
            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            console.log(`Response status: ${response.status}`);
            if (!response.ok) {
                const errorText = await response.text();
                console.error(`Error response: ${errorText}`);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Request details response:', result);
            
            if (result.status === 'success') {
                return result.data;
            }
            
            console.error('Failed to fetch request details:', result.message);
            return null;
        } catch (error) {
            console.error('Error fetching request details:', error);
            return null;
        }
    }

    // --- UI Population ---
    // populateStatusFilters is now primarily for attaching event listeners
    function setupStatusFiltersEventListeners() {
        if (!statusFilterContainer) {
            console.warn("Status filter container not found. Cannot attach listeners.");
            return;
        }
        // Event listeners for the static filters
        document.querySelectorAll('.status-filter').forEach(filter => {
            filter.addEventListener('change', handleStatusFilterChange);
        });
        console.log("Event listeners attached to static status filters.");
    }

    // Add helper function to filter requests by status (client-side)
    // This function remains similar, ensures status_id comparison is correct
    function filterRequestsByStatusClientSide(requests, statusId) {
        if (statusId === null || statusId === "") { // Empty string for "All"
            return requests; 
        }
        const numericStatusId = parseInt(statusId);
        return requests.filter(request => {
            // Ensure request.status_id is treated as a number for comparison
            return request.status_id !== undefined && parseInt(request.status_id) === numericStatusId;
        });
    }

    // Client-side pagination helper
    function paginateClientSide(items, page, perPage) {
        const startIndex = (page - 1) * perPage;
        return items.slice(startIndex, startIndex + perPage);
    }

    // Client-side pagination info generator
    function generatePaginationInfo(totalItems, page, perPage) {
        const last_page = Math.ceil(totalItems / perPage) || 1;
        return {
            total: totalItems,
            per_page: perPage,
            current_page: page,
            last_page: last_page
        };
    }

    // Modified handleStatusFilterChange for client-side filtering
    async function handleStatusFilterChange(event) {
        if (!event.target.checked) return;
        
        const statusIdValue = event.target.value; // Value from HTML radio button (can be empty string for "All")
        currentStatusFilter = statusIdValue === "" ? null : parseInt(statusIdValue);
        currentPage = 1; // Reset to first page on filter change
        
        console.log('Client-side filter change. Status ID:', currentStatusFilter);
        
        applyFilterAndPaginateTracker();
    }

    function renderRequests(requestsToRender) {
        if (!requestsTableBody) return;
        requestsTableBody.innerHTML = ''; // Clear existing rows

        if (requestsToRender.length === 0) {
            // This is now handled by applyFilterAndPaginateTracker
            // if (noRequestsMessage) noRequestsMessage.style.display = 'block'; 
            return;
        }
        // if (noRequestsMessage) noRequestsMessage.style.display = 'none'; // Handled by applyFilterAndPaginateTracker

        requestsToRender.forEach(request => {
            const row = requestsTableBody.insertRow();
            row.setAttribute('data-request-id', request.request_id);
            row.classList.add('request-summary-row');
            
            // Transaction ID cell
            const txnCell = row.insertCell();
            txnCell.textContent = request.transaction_code || `CSUMGB-${request.request_id}`;
            
            // Date of Request
            const requestDate = new Date(request.request_date);
            row.insertCell().textContent = requestDate.toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
            
            // Status
            const statusCell = row.insertCell();
            const statusBadge = document.createElement('span');
            statusBadge.classList.add('badge');
            statusBadge.textContent = request.status_name;
            
            // Find status in the list to use proper color
            const status = statusList.find(s => s.status_name === request.status_name);
            if (status && status.color_code) {
                statusBadge.style.backgroundColor = status.color_code;
                statusBadge.style.color = getContrastColor(status.color_code);
            } else {
                // Fallback status colors
                if (request.status_name === 'Not Paid') statusBadge.classList.add('bg-danger');
                else if (request.status_name === 'Approved') statusBadge.classList.add('bg-success');
                else if (request.status_name === 'Expired') statusBadge.classList.add('bg-secondary');
                else statusBadge.classList.add('bg-info');
            }
            
            statusCell.appendChild(statusBadge);

            // Remarks cell (new)
            const remarksCell = row.insertCell();
            if (request.status_name === 'Not Approved' && request.transaction_remarks) {
                remarksCell.textContent = request.transaction_remarks;
            } else {
                remarksCell.textContent = 'No remarks.'; // Or some placeholder like '-'
            }

            // Actions
            const actionsCell = row.insertCell();
            actionsCell.classList.add('text-center', 'action-buttons');

            // Add Payment button
            const addPaymentBtn = document.createElement('button');
            addPaymentBtn.classList.add('btn', 'btn-primary', 'btn-sm', 'me-2');
            addPaymentBtn.textContent = 'Add Payment';
            addPaymentBtn.setAttribute('data-request-id', request.request_id);
            addPaymentBtn.onclick = (e) => {
                handleAddPayment(request.request_id);
            };
            if (request.status_name !== 'Not Paid') {
                addPaymentBtn.disabled = true;
            }
            actionsCell.appendChild(addPaymentBtn);

            // Download button
            const downloadBtn = document.createElement('button');
            downloadBtn.classList.add('btn', 'btn-info', 'btn-sm', 'me-2');
            downloadBtn.innerHTML = '<i class="fas fa-download"></i>';
            downloadBtn.setAttribute('data-request-id', request.request_id);
            downloadBtn.title = 'Download';
            if (request.status_name !== 'Approved') {
                downloadBtn.disabled = true;
                downloadBtn.classList.replace('btn-info', 'btn-secondary');
            }
            actionsCell.appendChild(downloadBtn);
            
            // View Details button
            const detailsBtn = document.createElement('button');
            detailsBtn.classList.add('btn', 'btn-secondary', 'btn-sm', 'view-details-btn');
            detailsBtn.innerHTML = '<i class="fas fa-eye"></i>';
            detailsBtn.setAttribute('data-request-id', request.request_id);
            detailsBtn.title = 'View Details';
            detailsBtn.onclick = (e) => {
                toggleRequestDetails(request.request_id);
                
                // Toggle button class
                if (detailsBtn.classList.contains('btn-secondary')) {
                    detailsBtn.classList.replace('btn-secondary', 'btn-success');
                    detailsBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    detailsBtn.title = 'Hide Details';
                } else {
                    detailsBtn.classList.replace('btn-success', 'btn-secondary');
                    detailsBtn.innerHTML = '<i class="fas fa-eye"></i>';
                    detailsBtn.title = 'View Details';
                }
            };
            actionsCell.appendChild(detailsBtn);

            // Create the hidden details row
            const detailsRow = requestsTableBody.insertRow();
            detailsRow.classList.add('request-details-row');
            detailsRow.setAttribute('id', `details-${request.request_id}`);
            detailsRow.style.display = 'none'; // Hidden by default
            detailsRow.style.transition = 'all 0.3s ease';
            const detailsCell = detailsRow.insertCell();
            detailsCell.colSpan = 5; // Adjusted colspan from 4 to 5
            detailsCell.classList.add('request-details-content', 'p-3', 'bg-light');
            detailsCell.innerHTML = `
                <div class="d-flex align-items-center justify-content-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2">Loading details...</span>
                </div>`;
        });
    }

    function renderPagination(pagination) {
        const paginationContainer = document.getElementById('paginationControls');
        if (!paginationContainer) return;
        
        // Clear existing pagination
        paginationContainer.innerHTML = '';
        
        // Skip if only one page
        if (pagination.last_page <= 1) return;
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${pagination.current_page === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>`;
        paginationContainer.appendChild(prevLi);
        
        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${pagination.current_page === i ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            paginationContainer.appendChild(pageLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>`;
        paginationContainer.appendChild(nextLi);
        
        // Add click handlers
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', handlePaginationClick);
        });
    }

    // Modified handlePaginationClick for client-side pagination
    async function handlePaginationClick(event) {
        event.preventDefault();
        const target = event.target;
        const newPage = parseInt(target.dataset.page);

        if (isNaN(newPage) || newPage < 1 || newPage === currentPage) {
            if (target.closest('.page-item.disabled')) { // Check if parent li is disabled
            return;
            }
            // If not disabled but page is same or invalid, also return to avoid re-processing
            if (newPage === currentPage || isNaN(newPage) || newPage < 1) return;
        }
        
        currentPage = newPage;
        console.log('Client-side pagination click. New page:', currentPage);
        applyFilterAndPaginateTracker();
    }

    async function toggleRequestDetails(requestId) {
        const detailsRow = document.getElementById(`details-${requestId}`);
        if (!detailsRow) return;
        
        // Get the summary row that contains this request
        const summaryRow = document.querySelector(`.request-summary-row[data-request-id="${requestId}"]`);

        if (detailsRow.style.display === 'table-row') {
            // Closing the details
            detailsRow.style.display = 'none';
            if (summaryRow) {
                summaryRow.classList.remove('expanded');
            }
        } else {
            // Opening the details
            detailsRow.style.display = 'table-row';
            if (summaryRow) {
                summaryRow.classList.add('expanded');
            }
            
            // Fetch and display details if not already loaded
            const detailsContent = detailsRow.querySelector('.request-details-content');
            if (detailsContent.dataset.loaded !== 'true') {
                try {
                    const requestData = await fetchRequestDetails(requestId);
                    console.log("Received request data:", requestData);
                    
                    if (requestData) {
                        let html = '<div class="card">';
                        html += '<div class="card-header bg-primary text-white"><h5 class="mb-0">Request Details</h5></div>';
                        html += '<div class="card-body">';
                        
                        // Add request info section
                        html += '<div class="row mb-3">';
                        html += '<div class="col-md-6">';
                        html += `<p><strong>Transaction Code:</strong> ${requestData.transaction_code || 'N/A'}</p>`;
                        html += `<p><strong>Total Amount:</strong> ₱${parseFloat(requestData.total_amount || 0).toFixed(2)}</p>`;
                        html += '</div>';
                        html += '<div class="col-md-6">';
                        
                        // Add request dates
                        if (requestData.request_date) {
                            const requestDate = new Date(requestData.request_date);
                            html += `<p><strong>Request Date:</strong> ${requestDate.toLocaleDateString('en-US', {
                                year: 'numeric', month: 'long', day: 'numeric'
                            })}</p>`;
                        }
                        
                        if (requestData.exp_date) {
                            const expDate = new Date(requestData.exp_date);
                            html += `<p><strong>Expiration Date:</strong> ${expDate.toLocaleDateString('en-US', {
                                year: 'numeric', month: 'long', day: 'numeric'
                            })}</p>`;
                        }
                        
                        html += '</div>';
                        html += '</div>';
                        
                        // Add requested items section
                        html += '<h6 class="mb-3">Requested Items:</h6>';
                        
                        if (requestData.items && requestData.items.length > 0) {
                            html += '<div class="table-responsive">';
                            html += '<table class="table table-striped table-bordered">';
                            html += '<thead class="table-light">';
                            html += '<tr><th>Station ID</th><th>Station Name</th><th>Type</th><th>Price</th></tr>';
                            html += '</thead><tbody>';
                            
                            requestData.items.forEach(item => {
                                html += '<tr>';
                                html += `<td>${item.station_id || 'N/A'}</td>`;
                                html += `<td>${item.station_name || 'N/A'}</td>`;
                                html += `<td>${item.station_type === 'caap' ? 'Horizontal (CAAP)' : (item.station_type ? (item.station_type.charAt(0).toUpperCase() + item.station_type.slice(1)) : 'N/A')}</td>`;
                                html += `<td class="text-end">₱${parseFloat(item.price || 0).toFixed(2)}</td>`;
                                html += '</tr>';
                            });
                            
                            // Add total row
                            html += '<tr class="table-light fw-bold">';
                            html += '<td colspan="3" class="text-end">Total:</td>';
                            html += `<td class="text-end">₱${parseFloat(requestData.total_amount || 0).toFixed(2)}</td>`;
                            html += '</tr>';
                            
                            html += '</tbody></table>';
                            html += '</div>';
                        } else {
                            html += '<div class="alert alert-warning">No items found for this request. There may be a data issue.</div>';
                        }
                        
                        html += '</div>'; // Close card-body
                        html += '</div>'; // Close card
                        
                        detailsContent.innerHTML = html;
                        detailsContent.dataset.loaded = 'true';
                    } else {
                        detailsContent.innerHTML = '<div class="alert alert-danger">Could not load request details. The server returned no data.</div>';
                    }
                } catch (error) {
                    console.error("Error loading request details:", error);
                    detailsContent.innerHTML = '<div class="alert alert-danger">Error loading request details: ' + error.message + '</div>';
                }
            }
        }
    }
    
    // --- Payment Modal Handling ---
    async function handleAddPayment(requestId) {
        const requestData = await fetchRequestDetails(requestId);
        if (!requestData || !requestData.items || requestData.items.length === 0) {
            alert('Could not load request details for payment or request has no items.');
            return;
        }

        if (!bsPaymentModal) {
            console.error("Payment modal is not initialized.");
            alert("Payment functionality is currently unavailable.");
            return;
        }

        // Populate payment modal
        document.getElementById('modalRequestId').value = requestId;
        
        // Set the paid amount to the total amount from the request
        const paidAmountField = document.getElementById('trackerPaidAmount');
        if (paidAmountField && requestData.total_amount) {
            paidAmountField.value = parseFloat(requestData.total_amount).toFixed(2);
            paidAmountField.min = parseFloat(requestData.total_amount).toFixed(2);
        }
        
        // Set the transaction code
        const transactionCodeField = document.getElementById('transactionCodeDisplay');
        if (transactionCodeField && requestData.transaction_code) {
            transactionCodeField.value = requestData.transaction_code;
        }
        
        // Clear any previous alert
        const alertElement = document.getElementById('trackerPaymentAlert');
        if (alertElement) {
            alertElement.classList.add('d-none');
            alertElement.textContent = '';
        }
        
        // Clear reference number
        const referenceField = document.getElementById('trackerReferenceNumber');
        if (referenceField) {
            referenceField.value = '';
        }
        
        // Set default payment method selection
        const linkBizOption = document.getElementById('methodLinkBiz');
        if (linkBizOption) {
            linkBizOption.checked = true;
        }
        
        // Reset the file input and upload box
        const fileInput = document.getElementById('trackerProofFile');
        if (fileInput) {
            fileInput.value = '';
        }
        
        // Completely rebuild the upload box to ensure proper event handlers
        const uploadBoxContainer = document.querySelector('.upload-box').parentNode;
        if (uploadBoxContainer) {
            uploadBoxContainer.innerHTML = `
                <div class="upload-box" id="uploadBox">
                    <input type="file" class="form-control d-none" id="trackerProofFile" name="proof_file" accept="image/*,application/pdf">
                    <div class="upload-icon">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                    </div>
                    <p class="mb-0">Click here to upload payment proof</p>
                </div>
            `;
            
            // Set up fresh event listeners
            const newUploadBox = document.getElementById('uploadBox');
            const newFileInput = document.getElementById('trackerProofFile');
            
            if (newUploadBox && newFileInput) {
                newUploadBox.addEventListener('click', function() {
                    newFileInput.click();
                });
                
                newFileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        const fileName = this.files[0].name;
                        const selectedFiles = this.files; // Store the file selection
                        
                        newUploadBox.innerHTML = `
                            <div class="upload-icon">
                                <i class="fas fa-check fa-2x mb-2 text-success"></i>
                            </div>
                            <p class="mb-0">${fileName}</p>
                            <input type="file" class="form-control d-none" id="trackerProofFile" name="proof_file" accept="image/*,application/pdf">
                        `;
                        
                        // Re-add the event listener to the new file input and preserve the selected file
                        const readdedFileInput = document.getElementById('trackerProofFile');
                        if (readdedFileInput) {
                            // Create a new FileList-like object
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(selectedFiles[0]);
                            readdedFileInput.files = dataTransfer.files;
                            
                            // Add click handler to the new input to avoid propagation issues
                            readdedFileInput.addEventListener('click', function(e) {
                                e.stopPropagation();
                            });
                        }
                    }
                });
            }
        }
        
        // Show the modal
        bsPaymentModal.show();
        
        // Set up the submit button handler - completely rebuild to avoid duplicate listeners
        const submitBtnContainer = document.getElementById('submitPaymentBtn').parentNode;
        if (submitBtnContainer) {
            const oldBtn = document.getElementById('submitPaymentBtn');
            const newBtn = document.createElement('button');
            newBtn.id = 'submitPaymentBtn';
            newBtn.className = oldBtn.className;
            newBtn.textContent = 'Submit';
            newBtn.type = 'button';
            
            submitBtnContainer.replaceChild(newBtn, oldBtn);
            
            newBtn.addEventListener('click', function() {
                handleSubmitPayment(requestId);
            });
        }
    }

    // --- Utility Functions ---
    function getContrastColor(hexColor) {
        // If no hex provided, return black
        if (!hexColor) return '#000000';
        
        // Convert hex to RGB
        let r, g, b;
        if (hexColor.length === 4) {
            r = parseInt(hexColor[1] + hexColor[1], 16);
            g = parseInt(hexColor[2] + hexColor[2], 16);
            b = parseInt(hexColor[3] + hexColor[3], 16);
        } else {
            r = parseInt(hexColor.substr(1, 2), 16);
            g = parseInt(hexColor.substr(3, 2), 16);
            b = parseInt(hexColor.substr(5, 2), 16);
        }
        
        // Calculate luminance
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        
        // Return black for bright colors and white for dark colors
        return luminance > 0.5 ? '#000000' : '#FFFFFF';
    }

    // --- Main Initialization ---
    async function initializeTracker() {
        // First check if user is authenticated
        if (!checkAuth()) {
            console.warn("User not authenticated, can't initialize tracker");
            return;
        }

        let currentUser = await getCurrentUserInfo();
        let userId;
        
        if (currentUser && currentUser.user_id) {
            userId = parseInt(currentUser.user_id);
        } else {
            userId = getUserId(); // Fallback to localStorage/token
        }

        if (!userId) {
            console.error('No user ID available. Cannot fetch requests.');
            if (loadingMessage) loadingMessage.style.display = 'none';
            if (noRequestsMessage) {
                noRequestsMessage.innerHTML = '<p>Unable to retrieve your user information. Please try logging out and back in.</p>';
                noRequestsMessage.style.display = 'block';
            }
            return;
        }

        if (loadingMessage) loadingMessage.style.display = 'block';
        if (noRequestsMessage) noRequestsMessage.style.display = 'none';
        if (requestsTableBody) requestsTableBody.innerHTML = '';

        try {
            // Fetch statuses for badge coloring (statusList global variable)
            await fetchRequestStatuses(); 
            
            // Attach event listeners to the static HTML filters
            setupStatusFiltersEventListeners();

            // Fetch ALL user requests for client-side filtering
            console.log('Fetching all requests for user ID for client-side cache:', userId);
            allFetchedRequests = await fetchAllUserRequestPagesSequentially(userId);
            console.log('All requests fetched and cached:', allFetchedRequests.length);
            
            currentPage = 1; // Ensure current page is reset before first render
            applyFilterAndPaginateTracker(); // Initial render

        } catch (error) {
            console.error('Error initializing tracker:', error);
            if (noRequestsMessage) {
                noRequestsMessage.innerHTML = '<p>Error loading requests. Please try again later.</p>';
                noRequestsMessage.style.display = 'block';
            }
            // Render empty pagination or hide it
             renderPagination(generatePaginationInfo(0, 1, itemsPerPage));
        } finally {
            if (loadingMessage) loadingMessage.style.display = 'none';
        }

        // Event delegation for action buttons and row clicks (ensure this is set up once)
        const tbody = document.querySelector('#requestsTable tbody');
        if (tbody && !tbody.dataset.eventListenersAttached) { // Prevent multiple attachments
            tbody.addEventListener('click', function(event) {
            const target = event.target;
                // Add Payment Button Click (check classList contains 'add-payment-btn' which is more robust)
                const addPaymentButton = target.closest('.btn-primary'); // Assuming Add Payment is primary
                if (addPaymentButton && addPaymentButton.textContent === 'Add Payment' && !addPaymentButton.disabled) {
                    const requestId = addPaymentButton.dataset.requestId;
                    if (requestId) handleAddPayment(requestId);
                }
                // Download button click
            else if (target.closest('.download-btn') && !target.closest('.download-btn').disabled) {
                const requestId = target.closest('.download-btn').dataset.requestId;
                alert(`Download functionality for request ID: ${requestId} is not yet implemented.`);
                console.log("Download clicked for request:", requestId);
            }
                // View Details Button Click (use .view-details-btn class)
                else if (target.closest('.view-details-btn')) {
                     const detailsButton = target.closest('.view-details-btn');
                     const requestId = detailsButton.dataset.requestId;
                     if (requestId) {
                toggleRequestDetails(requestId);
                        // Toggle button appearance
                        if (detailsButton.classList.contains('btn-secondary')) {
                            detailsButton.classList.replace('btn-secondary', 'btn-success');
                            detailsButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                            detailsButton.title = 'Hide Details';
                        } else {
                            detailsButton.classList.replace('btn-success', 'btn-secondary');
                            detailsButton.innerHTML = '<i class="fas fa-eye"></i>';
                            detailsButton.title = 'View Details';
                        }
                     }
                }
                // Handle row click for details (but not if an action button within the row was clicked)
                // Check if the click was on the row itself and not on something within .action-buttons
                else if (target.closest('.request-summary-row') && !target.closest('.action-buttons')) {
                    const summaryRow = target.closest('.request-summary-row');
                    const requestId = summaryRow.dataset.requestId;
                    if (requestId) {
                        // Also toggle the button state of the view details button within this row
                        const detailsButton = summaryRow.querySelector('.view-details-btn');
                        toggleRequestDetails(requestId); // This function handles showing/hiding details
                         if (detailsButton) {
                            if (detailsButton.classList.contains('btn-secondary')) {
                                detailsButton.classList.replace('btn-secondary', 'btn-success');
                                detailsButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                                detailsButton.title = 'Hide Details';
                            } else {
                                detailsButton.classList.replace('btn-success', 'btn-secondary');
                                detailsButton.innerHTML = '<i class="fas fa-eye"></i>';
                                detailsButton.title = 'View Details';
                            }
                        }
                    }
                }
            });
            tbody.dataset.eventListenersAttached = 'true';
        }
    }

    // +++ New Client-Side Core Filtering and Pagination Function for Tracker +++
    function applyFilterAndPaginateTracker() {
        if (!allFetchedRequests) {
            console.error("allFetchedRequests is not initialized. Cannot apply filter on tracker.");
            if (loadingMessage) loadingMessage.style.display = 'none';
            if (noRequestsMessage) {
                noRequestsMessage.textContent = 'Request data is not available.';
                noRequestsMessage.style.display = 'block';
            }
            renderPagination(generatePaginationInfo(0, 1, itemsPerPage)); // Show empty pagination
                        return;
                    }

        if (loadingMessage) loadingMessage.style.display = 'block'; // Show loading during processing

        console.log(`Tracker: Applying filter. Status ID: ${currentStatusFilter}, Current page: ${currentPage}`);

        const filteredRequests = filterRequestsByStatusClientSide(allFetchedRequests, currentStatusFilter);
        console.log(`Tracker: Found ${filteredRequests.length} requests after filter (Status ID: ${currentStatusFilter}).`);

        const totalFilteredItems = filteredRequests.length;
        const maxPage = Math.ceil(totalFilteredItems / itemsPerPage) || 1;
        if (currentPage > maxPage) {
            currentPage = maxPage; // Adjust current page if out of bounds
        }

        const paginationInfo = generatePaginationInfo(totalFilteredItems, currentPage, itemsPerPage);
        const displayedRequests = paginateClientSide(filteredRequests, currentPage, itemsPerPage);
        
        console.log(`Tracker: Paginated. Displaying ${displayedRequests.length} requests on page ${currentPage} of ${paginationInfo.last_page}. Total filtered: ${totalFilteredItems}`);

        if (requestsTableBody) requestsTableBody.innerHTML = ''; // Clear before rendering

        if (displayedRequests.length === 0) {
            if (noRequestsMessage) {
                if (totalFilteredItems > 0) {
                    noRequestsMessage.textContent = 'No requests match the current filter on this page.';
                } else if (currentStatusFilter !== null) {
                    noRequestsMessage.textContent = 'No requests match your current filter criteria.';
                } else if (allFetchedRequests.length === 0 ) {
                     noRequestsMessage.innerHTML = 'You have no requests yet. Please <a href="#" data-bs-toggle="modal" data-bs-target="#authModal">login</a> or make a request.';
                }
                 else {
                    noRequestsMessage.textContent = 'No requests to display.';
                }
                noRequestsMessage.style.display = 'block';
            }
        } else {
            if (noRequestsMessage) noRequestsMessage.style.display = 'none';
        }
        
        renderRequests(displayedRequests); // renderRequests now uses the passed 'displayedRequests'
        renderPagination(paginationInfo);
        
        if (loadingMessage) loadingMessage.style.display = 'none'; // Hide loading after processing
    }
    // --- End of New Helper Functions ---

    // Listen for authentication events
    document.addEventListener('webgnis:auth:login', function(event) {
        console.log("Authentication event received:", event.detail);
        initializeTracker();
    });

    document.addEventListener('webgnis:auth:logout', function() {
        console.log("Logout event received");
        if (requestsTableBody) requestsTableBody.innerHTML = '';
        if (noRequestsMessage) {
            noRequestsMessage.innerHTML = '<p>Please <a href="#" data-bs-toggle="modal" data-bs-target="#authModal">login</a> to view your requests.</p>';
            noRequestsMessage.style.display = 'block';
        }
        if (loadingMessage) loadingMessage.style.display = 'none';
    });

    // Initial auth check
    checkAuthStatus().then(status => {
        if (status.authenticated && status.user) {
            initializeTracker();
        } else {
            checkAuth(); // Show login message
        }
    });
    
    // Add click handler for the login link in the message
    document.body.addEventListener('click', function(event) {
        if (event.target.matches('#noRequestsMessage a[data-bs-toggle="modal"]')) {
            event.preventDefault();
            const authModal = new bootstrap.Modal(document.getElementById('authModal'));
            authModal.show();
        }
    });

    // Handle payment form submission
    async function handleSubmitPayment(requestId) {
        const paymentForm = document.getElementById('trackerPaymentForm');
        
        if (!paymentForm) {
            alert("Payment form not found");
            return;
        }
        
        try {
            // Validate the form
            const paidAmount = document.getElementById('trackerPaidAmount')?.value;
            const referenceNumber = document.getElementById('trackerReferenceNumber')?.value;
            
            // Get proof file safely with proper error handling
            const proofFileInput = document.getElementById('trackerProofFile');
            if (!proofFileInput) {
                showPaymentAlert("File input element not found. Please refresh the page and try again.");
                console.error("trackerProofFile element not found in the form");
                return;
            }
            
            const proofFile = proofFileInput.files && proofFileInput.files.length > 0 ? proofFileInput.files[0] : null;
            
            // Get selected payment method with better error handling
            const paymentMethodElements = document.querySelectorAll('input[name="paymentMethod"]');
            let selectedPaymentMethod = null;
            paymentMethodElements.forEach(element => {
                if (element.checked) {
                    selectedPaymentMethod = element.value;
                }
            });
            
            // Validate form inputs
            if (!paidAmount) {
                showPaymentAlert("Please enter the paid amount");
                return;
            }
            
            if (!selectedPaymentMethod) {
                showPaymentAlert("Please select a payment method");
                return;
            }
            
            if (!referenceNumber) {
                showPaymentAlert("Please enter a reference number");
                return;
            }
            
            if (!proofFile) {
                showPaymentAlert("Please upload proof of payment");
                return;
            }
            
            // Prepare form data for submission
            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('paid_amount', paidAmount);
            formData.append('payment_method', selectedPaymentMethod);
            formData.append('reference_number', referenceNumber);
            formData.append('proof_file', proofFile);
            
            console.log("Submitting payment with data:", {
                request_id: requestId,
                paid_amount: paidAmount,
                payment_method: selectedPaymentMethod,
                reference_number: referenceNumber,
                proof_file: proofFile.name
            });
            
            // Show loading state
            const submitBtn = document.getElementById('submitPaymentBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
            }
            
            // Get authentication token
            const token = getToken();
            if (!token) {
                showPaymentAlert("Authentication required. Please login again.");
                if (submitBtn) submitBtn.disabled = false;
                return;
            }
            
            // Submit the payment using the upload-proof endpoint
            const response = await fetch(`${API_BASE_URL}/transactions_api.php?action=upload-proof`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });
            
            // Get the response as text first for debugging
            const responseText = await response.text();
            console.log("Raw payment submission response:", responseText);
            
            // Try to parse the response as JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error("Error parsing response:", parseError);
                showPaymentAlert("Server returned an invalid response. Please try again.");
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
                return;
            }
            
            if (result.status === 'success') {
                // Close the modal
                if (bsPaymentModal) bsPaymentModal.hide();
                
                // Show success message
                alert(`Payment submitted successfully! Your transaction has been recorded.`);
                
                // Reload the requests table to show updated status
                const userId = getUserId();
                const userRequests = await fetchAllUserRequestPagesSequentially(userId, currentStatusFilter);
                renderRequests(userRequests);
                renderPagination(generatePaginationInfo(userRequests.length, currentPage, itemsPerPage));
            } else {
                showPaymentAlert(result.message || "Failed to submit payment. Please try again.");
            }
        } catch (error) {
            console.error('Error submitting payment:', error);
            showPaymentAlert("An error occurred while submitting your payment: " + (error.message || "Unknown error"));
        } finally {
            // Reset button state
            const submitBtn = document.getElementById('submitPaymentBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            }
        }
    }
    
    // Helper to show payment alerts
    function showPaymentAlert(message) {
        const alertElement = document.getElementById('trackerPaymentAlert');
        if (alertElement) {
            alertElement.textContent = message;
            alertElement.classList.remove('d-none');
        } else {
            alert(message);
        }
    }
}); 