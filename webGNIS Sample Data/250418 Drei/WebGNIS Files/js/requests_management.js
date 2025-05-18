import { usersApi } from './users/api-client.js';
import { checkAuthStatus } from './users/auth.js';

const API_BASE_URL = '.'; // Adjust if your API files are in a different directory

document.addEventListener('DOMContentLoaded', () => {
    const requestsTableBody = document.querySelector('#requestsTable tbody');
    const loadingMessage = document.getElementById('loadingMessage');
    const noRequestsMessage = document.getElementById('noRequestsMessage');
    const statusFilterContainer = document.getElementById('statusFilterContainer');
    const accessDenied = document.getElementById('accessDenied');
    const adminInterface = document.getElementById('adminInterface');
    
    let currentStatusFilter = null;
    let statusList = [];
    let allTransactions = [];
    let currentPage = 1;
    let itemsPerPage = 10;
    
    // Initialize Bootstrap modals
    let previewModal = null;
    try {
        const previewModalEl = document.getElementById('previewModal');
        if (previewModalEl) {
            previewModal = new bootstrap.Modal(previewModalEl);
        }
    } catch (e) {
        console.error("Failed to initialize Bootstrap modal for preview:", e);
    }

    let currentTransactionId = null;

    // --- Authentication Checks ---
    async function getCurrentUserInfo() {
        try {
            const userData = await usersApi.getCurrentUser();
            if (userData && userData.data) {
                console.log('Current user data from API:', userData.data);
                
                // Store user data in localStorage for later use
                localStorage.setItem('userData', JSON.stringify(userData.data));
                
                return userData.data;
            }
        } catch (error) {
            console.error('Error getting current user from API:', error);
        }
        
        // If we couldn't get fresh data, try to use cached data
        const cachedUserData = localStorage.getItem('userData');
        if (cachedUserData) {
            try {
                return JSON.parse(cachedUserData);
            } catch (e) {
                console.error('Error parsing cached user data:', e);
            }
        }
        
        return null;
    }

    function getToken() {
        return localStorage.getItem('webgnis_token');
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
                return result.data || [];
            }
            console.error('Failed to fetch request statuses:', result.message);
            return [];
        } catch (error) {
            console.error('Error fetching request statuses:', error);
            return [];
        }
    }

    async function fetchAllTransactions(statusId = null, page = 1) {
        const token = getToken();
        if (!token) {
            console.error('No authentication token available');
            return { transactions: [], pagination: { total: 0, per_page: 10, current_page: 1, last_page: 1 } };
        }
        
        // Build URL with parameters
        let url = `${API_BASE_URL}/transactions_api.php?action=list&page=${page}`;
        if (statusId) {
            url += `&status=${statusId}`;
        }
        
        console.log('Fetching transactions from URL:', url);
        
        try {
            const response = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            // For debugging, log the entire response
            const responseText = await response.text();
            console.log('API Response:', responseText);
            
            // Re-parse the response
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse API response as JSON:', e);
                throw new Error('Invalid JSON response from API');
            }
            
            if (result.status === 'success') {
                return {
                    transactions: result.data.transactions || [],
                    pagination: result.data.pagination || { 
                        total: 0, 
                        per_page: 10, 
                        current_page: 1, 
                        last_page: 1 
                    }
                };
            }
            console.error('Failed to fetch all transactions:', result.message);
            return { transactions: [], pagination: { total: 0, per_page: 10, current_page: 1, last_page: 1 } };
        } catch (error) {
            console.error('Error fetching all transactions:', error);
            return { transactions: [], pagination: { total: 0, per_page: 10, current_page: 1, last_page: 1 } };
        }
    }

    async function fetchTransactionDetails(transactionId) {
        const token = getToken();
        try {
            console.log(`Fetching transaction details for ID: ${transactionId}`);
            
            // Fix the URL format - use the proper query parameter format
            const url = `${API_BASE_URL}/transactions_api.php?action=view&id=${transactionId}`;
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
            console.log('Transaction details response:', result);
            
            if (result.status === 'success') {
                return result.data;
            }
            
            console.error('Failed to fetch transaction details:', result.message);
            return null;
        } catch (error) {
            console.error('Error fetching transaction details:', error);
            return null;
        }
    }

    async function fetchRequestItems(requestId) {
        if (!requestId) {
            console.error('No request ID provided to fetchRequestItems');
            return [];
        }
        
        const token = getToken();
        try {
            console.log(`Fetching request items for request ID: ${requestId}`);
            
            // Fix the URL format - use the proper query parameter format
            const url = `${API_BASE_URL}/requests_api.php?action=view&id=${requestId}`;
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
            console.log('Request items response:', result);
            
            if (result.status === 'success' && result.data && result.data.items) {
                return result.data.items || [];
            }
            
            console.error('Failed to fetch request items:', result.message);
            return [];
        } catch (error) {
            console.error('Error fetching request items:', error);
            return [];
        }
    }

    // --- UI Handlers ---
    function populateStatusFilters(statuses) {
        if (!statusFilterContainer) return;
        
        // Save the list for later use
        statusList = statuses;
        
        // Create radio buttons for each status
        let html = `
            <div class="form-check">
                <input class="form-check-input" type="radio" name="statusFilter" id="filterAll" value="all" checked>
                <label class="form-check-label" for="filterAll">All</label>
            </div>
        `;
        
        statuses.forEach(status => {
            const value = status.status_id;
            const label = status.status_name;
            const id = `filter${label.replace(/\s+/g, '')}`;
            
            html += `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="statusFilter" id="${id}" value="${value}">
                    <label class="form-check-label" for="${id}">${label}</label>
                </div>
            `;
        });
        
        statusFilterContainer.innerHTML = html;
        
        // Add event listeners to the filter controls
        document.querySelectorAll('input[name="statusFilter"]').forEach(radio => {
            radio.addEventListener('change', handleStatusFilterChange);
        });
    }

    async function handleStatusFilterChange(event) {
        currentPage = 1; // Reset to first page when filter changes
        const statusValue = event.target.value;
        currentStatusFilter = statusValue === 'all' ? null : parseInt(statusValue);
        
        loadingMessage.style.display = 'block';
        noRequestsMessage.style.display = 'none';
        
        const result = await fetchAllTransactions(currentStatusFilter, currentPage);
        allTransactions = result.transactions;
        
        if (allTransactions.length === 0) {
            noRequestsMessage.style.display = 'block';
            requestsTableBody.innerHTML = '';
        } else {
            noRequestsMessage.style.display = 'none';
            renderTransactions(allTransactions);
        }
        
        renderPagination(result.pagination);
        loadingMessage.style.display = 'none';
    }

    function renderTransactions(transactions) {
        if (!requestsTableBody) return;
        
        requestsTableBody.innerHTML = '';
        
        transactions.forEach(transaction => {
            const statusName = transaction.status_name || 'Unknown';
            const statusBadge = getStatusBadge(statusName);
            const row = document.createElement('tr');
            
            // Format the date
            const transactionDate = new Date(transaction.transaction_date || transaction.payment_date);
            const formattedDate = transactionDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            // Get transaction code - try different possible field names
            const transactionCode = transaction.transaction_code || (transaction.request_id ? `REQ-${transaction.request_id}` : 'N/A');
            
            // Format payment date and amount
            const paymentDate = transaction.payment_date ? new Date(transaction.payment_date) : null;
            const formattedPaymentDate = paymentDate ? paymentDate.toLocaleDateString() : 'N/A';
            const formattedPaymentAmount = transaction.payment_amount ? `₱${parseFloat(transaction.payment_amount).toFixed(2)}` : 'N/A';
            
            // Determine if transaction is approved for enabling buttons
            const isApproved = transaction.status_name && transaction.status_name.toLowerCase() === 'approved';
            const isPaid = transaction.status_name && transaction.status_name.toLowerCase() === 'paid';
            
            // Create action buttons with appropriate state
            const previewButton = `<button class="btn btn-sm btn-info action-btn preview-btn" data-id="${transaction.transaction_id}" title="Preview"><i class="fas fa-eye"></i></button>`;
            
            const approveButton = isPaid ? 
                `<button class="btn btn-sm btn-success action-btn approve-btn" data-id="${transaction.transaction_id}" title="Approve"><i class="fas fa-check"></i></button>` :
                `<button class="btn btn-sm btn-success action-btn approve-btn btn-disabled" title="Approve"><i class="fas fa-check"></i></button>`;
                
            const disapproveButton = isPaid ? 
                `<button class="btn btn-sm btn-danger action-btn disapprove-btn" data-id="${transaction.transaction_id}" title="Disapprove"><i class="fas fa-times"></i></button>` :
                `<button class="btn btn-sm btn-danger action-btn disapprove-btn btn-disabled" title="Disapprove"><i class="fas fa-times"></i></button>`;
                
            const downloadButton = isApproved ? 
                `<button class="btn btn-sm btn-secondary action-btn download-btn" data-code="${transaction.transaction_code}" title="Download Certificate"><i class="fas fa-download"></i></button>` :
                `<button class="btn btn-sm btn-secondary action-btn download-btn btn-disabled" title="Download Certificate"><i class="fas fa-download"></i></button>`;
            
            row.innerHTML = `
                <td>${transaction.username || 'Unknown'}</td>
                <td>${transactionCode}</td>
                <td>${formattedDate}</td>
                <td>${formattedPaymentAmount}</td>
                <td>${statusBadge}</td>
                <td class="text-center">
                    ${previewButton}
                    ${approveButton}
                    ${disapproveButton}
                    ${downloadButton}
                </td>
            `;
            
            requestsTableBody.appendChild(row);
        });
        
        // Add event listeners for preview buttons
        document.querySelectorAll('.preview-btn').forEach(button => {
            button.addEventListener('click', () => {
                const transactionId = button.getAttribute('data-id');
                showTransactionPreview(transactionId);
            });
        });
        
        document.querySelectorAll('.approve-btn:not(.btn-disabled)').forEach(button => {
            button.addEventListener('click', () => {
                const transactionId = button.getAttribute('data-id');
                const transaction = transactions.find(t => t.transaction_id == transactionId);
                handleApproveTransaction(transactionId, transaction.transaction_code);
            });
        });
        
        document.querySelectorAll('.disapprove-btn:not(.btn-disabled)').forEach(button => {
            button.addEventListener('click', () => {
                const transactionId = button.getAttribute('data-id');
                const transaction = transactions.find(t => t.transaction_id == transactionId);
                handleDisapproveTransaction(transactionId, transaction.transaction_code);
            });
        });
        
        document.querySelectorAll('.download-btn:not(.btn-disabled)').forEach(button => {
            button.addEventListener('click', () => {
                const transactionCode = button.getAttribute('data-code');
                handleDownloadCertificate(transactionCode);
            });
        });
    }

    function getStatusBadge(statusName) {
        let badgeClass = 'bg-secondary';
        
        switch (statusName.toLowerCase()) {
            case 'not paid':
                badgeClass = 'bg-danger';
                break;
            case 'paid':
                badgeClass = 'bg-warning text-dark';
                break;
            case 'pending':
                badgeClass = 'bg-info text-dark';
                break;
            case 'approved':
                badgeClass = 'bg-success';
                break;
            case 'not approved':
                badgeClass = 'bg-danger';
                break;
            case 'expired':
                badgeClass = 'bg-secondary';
                break;
        }
        
        return `<span class="badge ${badgeClass}">${statusName}</span>`;
    }

    function renderPagination(pagination) {
        const paginationControls = document.getElementById('paginationControls');
        if (!paginationControls) return;
        
        const { current_page, last_page } = pagination;
        let html = '';
        
        // Previous button
        html += `
            <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${current_page - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= last_page; i++) {
            html += `
                <li class="page-item ${i === current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Next button
        html += `
            <li class="page-item ${current_page === last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${current_page + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        paginationControls.innerHTML = html;
        
        // Add event listeners to pagination controls
        document.querySelectorAll('#paginationControls .page-link').forEach(link => {
            link.addEventListener('click', handlePaginationClick);
        });
    }

    async function handlePaginationClick(event) {
        event.preventDefault();
        const page = parseInt(event.currentTarget.getAttribute('data-page'));
        
        if (isNaN(page) || page < 1 || page === currentPage) {
            return;
        }
        
        currentPage = page;
        loadingMessage.style.display = 'block';
        
        const result = await fetchAllTransactions(currentStatusFilter, currentPage);
        allTransactions = result.transactions;
        
        if (allTransactions.length === 0) {
            noRequestsMessage.style.display = 'block';
            requestsTableBody.innerHTML = '';
        } else {
            noRequestsMessage.style.display = 'none';
            renderTransactions(allTransactions);
        }
        
        renderPagination(result.pagination);
        loadingMessage.style.display = 'none';
    }

    async function showTransactionPreview(transactionId) {
        console.log('Showing preview for transaction:', transactionId);
        
        if (!transactionId) {
            console.error('Cannot show preview: transaction ID is undefined or null');
            alert('Unable to preview transaction: Invalid transaction ID');
            return;
        }
        
        // Store current transaction ID for approve/disapprove actions
        currentTransactionId = transactionId;
        
        // Fetch the transaction details
        const transactionDetails = await fetchTransactionDetails(transactionId);
        if (!transactionDetails) {
            console.error('Failed to fetch transaction details');
            alert('Unable to load transaction details. Please try again later.');
            return;
        }
        
        // Fetch request items for this transaction
        let requestItems = [];
        if (transactionDetails.request_id) {
            requestItems = await fetchRequestItems(transactionDetails.request_id);
        } else {
            console.warn('No request_id found in transaction details');
        }
        
        // Get transaction code - try different possible field names
        const transactionCode = transactionDetails.transaction_code || 
                               (transactionDetails.request_id ? `REQ-${transactionDetails.request_id}` : 'N/A');
        
        // Populate the modal with transaction data
        document.getElementById('modalTransactionId').textContent = transactionCode;
        document.getElementById('modalRequesterName').textContent = transactionDetails.username || 'Unknown';
        
        // Format and display the transaction date
        const transactionDate = new Date(transactionDetails.transaction_date || transactionDetails.payment_date || new Date());
        const formattedDate = transactionDate.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        document.getElementById('modalRequestDate').textContent = formattedDate;
        
        // Set status badge
        const statusName = transactionDetails.status_name || 'Unknown';
        document.getElementById('modalStatus').innerHTML = getStatusBadge(statusName);
        
        // Enable/disable approve/disapprove buttons based on status
        const approveBtn = document.querySelector('#previewModal .modal-footer .btn-success');
        const disapproveBtn = document.querySelector('#previewModal .modal-footer .btn-danger');
        
        if (approveBtn && disapproveBtn) {
            // Enable buttons for 'paid' or 'pending' status
            if (statusName.toLowerCase() === 'paid' || statusName.toLowerCase() === 'pending') {
                approveBtn.classList.remove('btn-disabled');
                disapproveBtn.classList.remove('btn-disabled');
                
                // Add event listeners
                approveBtn.onclick = () => handleApproveTransaction(transactionId, transactionCode);
                disapproveBtn.onclick = () => handleDisapproveTransaction(transactionId, transactionCode);
            } else {
                approveBtn.classList.add('btn-disabled');
                disapproveBtn.classList.add('btn-disabled');
                
                // Remove event listeners
                approveBtn.onclick = null;
                disapproveBtn.onclick = null;
            }
        }
        
        // Payment details
        if (document.getElementById('modalPaymentAmount')) {
            document.getElementById('modalPaymentAmount').textContent = transactionDetails.payment_amount 
                ? '₱' + parseFloat(transactionDetails.payment_amount).toFixed(2) 
                : 'Not paid yet';
        }
        
        if (document.getElementById('modalPaymentMethod')) {
            document.getElementById('modalPaymentMethod').textContent = transactionDetails.method_name || 'N/A';
        }
        
        if (document.getElementById('modalReferenceNumber')) {
            document.getElementById('modalReferenceNumber').textContent = transactionDetails.payment_reference || 'N/A';
        }
        
        // Payment proof image
        const proofContainer = document.getElementById('paymentProofContainer');
        if (proofContainer) {
            if (transactionDetails.payment_proof_file) {
                const proofFileUrl = transactionDetails.payment_proof_file;
                
                // If it's a PDF, show a PDF icon instead
                if (proofFileUrl.toLowerCase().endsWith('.pdf')) {
                    proofContainer.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-file-pdf fa-5x text-danger mb-2"></i>
                            <p>PDF Document</p>
                            <a href="Assets/payment_proofs/${proofFileUrl}" target="_blank" class="btn btn-sm btn-primary">View PDF</a>
                        </div>
                    `;
                } else {
                    // Otherwise, show the image
                    proofContainer.innerHTML = `
                        <div class="text-center">
                            <img src="Assets/payment_proofs/${proofFileUrl}" alt="Payment Proof" class="img-fluid payment-proof-img">
                        </div>
                    `;
                }
            } else {
                proofContainer.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                        <p>No payment proof uploaded yet</p>
                    </div>
                `;
            }
        }
        
        // Populate items table
        const itemsTableBody = document.getElementById('modalRequestItems');
        if (itemsTableBody) {
            itemsTableBody.innerHTML = '';
            
            if (requestItems && requestItems.length > 0) {
                let totalPrice = 0;
                
                requestItems.forEach(item => {
                    const row = document.createElement('tr');
                    const price = parseFloat(item.price || 0);
                    totalPrice += price;
                    
                    row.innerHTML = `
                        <td>${item.station_id || 'N/A'}</td>
                        <td>${item.station_name || 'N/A'}</td>
                        <td>${item.station_type ? (item.station_type.charAt(0).toUpperCase() + item.station_type.slice(1)) : 'N/A'}</td>
                        <td>₱${price.toFixed(2)}</td>
                    `;
                    
                    itemsTableBody.appendChild(row);
                });
                
                // Update totals
                if (document.getElementById('modalItemsCount')) {
                    document.getElementById('modalItemsCount').textContent = requestItems.length;
                }
                if (document.getElementById('modalTotalAmount')) {
                    document.getElementById('modalTotalAmount').textContent = '₱' + totalPrice.toFixed(2);
                }
            } else {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="4" class="text-center">No items found</td>`;
                itemsTableBody.appendChild(row);
                
                if (document.getElementById('modalItemsCount')) {
                    document.getElementById('modalItemsCount').textContent = '0';
                }
                if (document.getElementById('modalTotalAmount')) {
                    document.getElementById('modalTotalAmount').textContent = '₱0.00';
                }
            }
        }
        
        // Show the modal
        previewModal.show();
    }

    // Function to handle transaction approval
    async function handleApproveTransaction(transactionId, transactionCode) {
        if (!confirm(`Are you sure you want to approve transaction ${transactionCode}?`)) {
            return;
        }
        
        try {
            const token = getToken();
            const currentUser = getCurrentUser();
            
            // Make sure we have a user ID, fallback to 1 (admin) if not available
            const userId = currentUser?.user_id || 1;
            
            // Update transaction status
            const updateResponse = await fetch(`${API_BASE_URL}/transactions_api.php?action=update/${transactionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    status: 'approved',
                    verified: 1,
                    verified_by: userId,
                    verified_date: new Date().toISOString().slice(0, 19).replace('T', ' ')
                })
            });
            
            if (!updateResponse.ok) {
                throw new Error(`Failed to update transaction status: ${updateResponse.status}`);
            }
            
            const updateResult = await updateResponse.json();
            
            if (updateResult.status !== 'success') {
                throw new Error(updateResult.message || 'Failed to update transaction status');
            }
            
            // Generate certificate
            const certificateResponse = await fetch(`${API_BASE_URL}/certificates_api.php?action=generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    transaction_code: transactionCode
                })
            });
            
            if (!certificateResponse.ok) {
                console.warn(`Certificate generation warning: ${certificateResponse.status}`);
                // Continue even if certificate generation fails
            } else {
                const certificateResult = await certificateResponse.json();
                console.log('Certificate generation result:', certificateResult);
            }
            
            // Close modal and refresh transactions
            const modal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
            modal.hide();
            
            // Refresh transactions list
            await fetchAllTransactions(currentStatusFilter, currentPage);
            alert(`Transaction ${transactionCode} has been approved successfully.`);
            
        } catch (error) {
            console.error('Error approving transaction:', error);
            alert(`Failed to approve transaction: ${error.message}`);
        }
    }

    // Function to handle transaction disapproval
    async function handleDisapproveTransaction(transactionId, transactionCode) {
        const reason = prompt(`Please provide a reason for disapproving transaction ${transactionCode}:`);
        
        if (reason === null) {
            // User canceled the prompt
            return;
        }
        
        try {
            const token = getToken();
            const currentUser = getCurrentUser();
            
            // Make sure we have a user ID, fallback to 1 (admin) if not available
            const userId = currentUser?.user_id || 1;
            
            // Update transaction status
            const updateResponse = await fetch(`${API_BASE_URL}/transactions_api.php?action=update/${transactionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    status: 'not approved',
                    verified: 0,
                    verified_by: userId,
                    verified_date: new Date().toISOString().slice(0, 19).replace('T', ' '),
                    remarks: reason
                })
            });
            
            if (!updateResponse.ok) {
                throw new Error(`Failed to update transaction status: ${updateResponse.status}`);
            }
            
            const updateResult = await updateResponse.json();
            
            if (updateResult.status !== 'success') {
                throw new Error(updateResult.message || 'Failed to update transaction status');
            }
            
            // Close modal and refresh transactions
            const modal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
            modal.hide();
            
            // Refresh transactions list
            await fetchAllTransactions(currentStatusFilter, currentPage);
            alert(`Transaction ${transactionCode} has been disapproved.`);
            
        } catch (error) {
            console.error('Error disapproving transaction:', error);
            alert(`Failed to disapprove transaction: ${error.message}`);
        }
    }

    // Function to get current user data
    function getCurrentUser() {
        const userData = localStorage.getItem('userData');
        if (!userData) {
            console.warn('No user data found in localStorage');
            return null;
        }
        
        try {
            return JSON.parse(userData);
        } catch (error) {
            console.error('Error parsing user data:', error);
            return null;
        }
    }

    // Function to handle certificate download
    async function handleDownloadCertificate(transactionCode) {
        if (!transactionCode) {
            console.error('Cannot download certificate: transaction code is undefined or null');
            alert('Unable to download certificate: Invalid transaction code');
            return;
        }
        
        try {
            const token = getToken();
            
            // First check if certificate exists
            const checkResponse = await fetch(`${API_BASE_URL}/certificates_api.php?action=download&transaction_code=${transactionCode}`, {
                method: 'HEAD',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            if (checkResponse.status === 404) {
                // Certificate doesn't exist yet, need to generate it
                console.log(`Certificate for ${transactionCode} not found, generating...`);
                
                const generateResponse = await fetch(`${API_BASE_URL}/certificates_api.php?action=generate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        transaction_code: transactionCode
                    })
                });
                
                if (!generateResponse.ok) {
                    throw new Error(`Failed to generate certificate: ${generateResponse.status}`);
                }
                
                const generateResult = await generateResponse.json();
                
                if (generateResult.status !== 'success') {
                    throw new Error(generateResult.message || 'Failed to generate certificate');
                }
            }
            
            // Open download in new window/tab
            window.open(`${API_BASE_URL}/certificates_api.php?action=download&transaction_code=${transactionCode}`, '_blank');
            
        } catch (error) {
            console.error('Error downloading certificate:', error);
            alert(`Failed to download certificate: ${error.message}`);
        }
    }

    // --- Initialization ---
    async function initializeRequestsManagement() {
        // Check if user is authenticated and is an admin
        const userData = await getCurrentUserInfo();
        
        // Store user data for later use if we got it
        if (userData) {
            localStorage.setItem('userData', JSON.stringify(userData));
        }
        
        const isAdmin = userData && userData.user_type === 'admin';
        if (!isAdmin) {
            console.log('User is not an admin. Access denied.');
            if (accessDenied) accessDenied.style.display = 'block';
            if (adminInterface) adminInterface.classList.add('hidden');
            return;
        }
        
        // User is admin, show admin interface
        if (accessDenied) accessDenied.style.display = 'none';
        if (adminInterface) adminInterface.classList.remove('hidden');
        
        // Fetch request statuses and populate filters
        const statuses = await fetchRequestStatuses();
        populateStatusFilters(statuses);
        
        // Fetch initial set of transactions
        loadingMessage.style.display = 'block';
        const result = await fetchAllTransactions(null, 1);
        allTransactions = result.transactions;
        
        if (allTransactions.length === 0) {
            noRequestsMessage.style.display = 'block';
        } else {
            renderTransactions(allTransactions);
            noRequestsMessage.style.display = 'none';
        }
        
        renderPagination(result.pagination);
        loadingMessage.style.display = 'none';
    }

    // Initialize the page
    checkAuthStatus().then(() => {
        initializeRequestsManagement();
    }).catch(error => {
        console.error('Authentication check failed:', error);
        if (accessDenied) accessDenied.style.display = 'block';
        if (adminInterface) adminInterface.classList.add('hidden');
    });
}); 