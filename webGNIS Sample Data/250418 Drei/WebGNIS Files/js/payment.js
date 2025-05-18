/**
 * Payment Module - Handles payment submission and UI interaction
 */

// API endpoints
const API_URL = {
    PAYMENT_METHODS: 'transactions_api.php?action=payment-methods',
    SUBMIT_PAYMENT: 'transactions_api.php?action=submit',
    UPLOAD_PROOF: 'transactions_api.php?action=upload-proof',
    CREATE_REQUEST: 'requests_api.php?action=create',
    CONFIG: 'config-api.php'
};

// Station prices - will be updated from config
let STATION_PRICES = {
    'horizontal': 300,
    'vertical': 300,
    'gravity': 300
};

// Modal HTML template
const PAYMENT_MODAL_HTML = `
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">Payment Process</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-3">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Station ID</th>
                                <th>Station Name</th>
                                <th>Station Type</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="paymentStationsList">
                            <!-- Will be populated dynamically -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2">Total</td>
                                <td id="stationsCount">0</td>
                                <td colspan="2" id="paymentTotalAmount">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <form id="paymentForm">
                    <input type="hidden" id="requestId" name="request_id">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="paidAmount" class="form-label">Paid Amount:</label>
                        </div>
                        <div class="col-md-9">
                            <input type="number" class="form-control" id="paidAmount" name="paid_amount" required step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Payment Method:</label>
                        </div>
                        <div class="col-md-9">
                            <div id="paymentMethodsContainer" class="d-flex gap-3">
                                <!-- Payment methods will be populated here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3" style="display: none;">
                        <div class="col-md-3">
                            <label for="transactionCode" class="form-label">Transaction Code:</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="transactionCode" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="referenceNumber" class="form-label">Reference No.:</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="referenceNumber" name="reference_number" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="proofFile" class="form-label">Upload Photo:</label>
                        </div>
                        <div class="col-md-9">
                            <div class="upload-box" id="uploadBox">
                                <div class="upload-icon">
                                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                                </div>
                                <p class="mb-0">Click here to upload payment proof</p>
                                <input type="file" class="form-control d-none" id="proofFile" name="proof_file" accept="image/*,application/pdf">
                            </div>
                        </div>
                    </div>
                    
                    <div id="paymentAlert" class="alert alert-danger d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitPaymentBtn" class="btn btn-success">Submit</button>
                <button type="button" id="payLaterBtn" class="btn btn-warning">Pay Later</button>
            </div>
        </div>
    </div>
</div>
`;

// Payment controller
const PaymentController = {
    // Helper function to normalize a cart item from various sources
    normalizeCartItem: function(rawItem) {
        if (!rawItem) return null;

        let id, name, type;

        id = rawItem.id || rawItem.station_id || rawItem.stationId;
        name = rawItem.name || rawItem.station_name || rawItem.stationName;
        type = rawItem.type || rawItem.station_type || rawItem.stationType;

        if (!id && name) id = name; 
        if (!name && id) name = id; 
        if (!type) type = 'horizontal'; 

        if (!id || !name) {
            console.warn('Could not normalize cart item (missing id or name after trying common fields):', rawItem);
            return null; 
        }

        return {
            id: String(id),
            name: String(name),
            type: String(type).toLowerCase()
        };
    },

    // Initialize the payment module
    init: function() {
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', PAYMENT_MODAL_HTML);
        
        // Get elements
        this.modal = document.getElementById('paymentModal');
        this.bsModal = new bootstrap.Modal(this.modal);
        this.stationsList = document.getElementById('paymentStationsList');
        this.totalAmount = document.getElementById('paymentTotalAmount');
        this.form = document.getElementById('paymentForm');
        this.alertElement = document.getElementById('paymentAlert');
        this.transactionCode = document.getElementById('transactionCode');
        this.methodsContainer = document.getElementById('paymentMethodsContainer');
        this.requestIdInput = document.getElementById('requestId');
        
        // Store selected file in the controller
        this.selectedFile = null;
        
        // Set up event listeners
        const submitPaymentBtn = document.getElementById('submitPaymentBtn');
        if (submitPaymentBtn) {
            submitPaymentBtn.addEventListener('click', this.handleSubmit.bind(this));
        }
        
        const payLaterBtn = document.getElementById('payLaterBtn');
        if (payLaterBtn) {
            payLaterBtn.addEventListener('click', this.handlePayLater.bind(this));
        }
        
        const requestCertBtn = document.getElementById('requestCertBtn');
        if (requestCertBtn) {
            requestCertBtn.addEventListener('click', this.handleRequestCertificates.bind(this));
        }
        
        // Set up file upload interaction globally
        this.setupFileUpload();
        
        // Fetch config for station prices
        this.fetchConfig();
        
        // Fetch payment methods
        this.fetchPaymentMethods();
    },
    
    // Set up file upload functionality
    setupFileUpload: function() {
        const uploadBox = document.getElementById('uploadBox');
        const proofFileInput = document.getElementById('proofFile') || document.getElementById('trackerProofFile');
        
        if (uploadBox && proofFileInput) {
            // Handle click on the upload box
            uploadBox.addEventListener('click', () => {
                proofFileInput.click();
            });
            
            // Show file name after selection and store the file
            proofFileInput.addEventListener('change', (e) => {
                if (proofFileInput.files && proofFileInput.files.length > 0) {
                    const file = proofFileInput.files[0];
                    const fileName = file.name;
                    
                    // Store the file in the controller
                    this.selectedFile = file;
                    
                    // Update the UI to show the file is selected
                    const fileDisplay = uploadBox.querySelector('p') || document.createElement('p');
                    fileDisplay.className = 'mb-0';
                    fileDisplay.textContent = fileName;
                    
                    const iconElement = uploadBox.querySelector('.upload-icon') || document.createElement('div');
                    iconElement.className = 'upload-icon';
                    iconElement.innerHTML = '<i class="fas fa-check-circle fa-2x mb-2 text-success"></i>';
                    
                    // Clear upload box and rebuild it
                    uploadBox.innerHTML = '';
                    uploadBox.appendChild(iconElement);
                    uploadBox.appendChild(fileDisplay);
                    
                    // Re-add the file input which was cleared
                    uploadBox.appendChild(proofFileInput);
                    proofFileInput.className = 'form-control d-none';
                    
                    console.log('File selected and stored:', fileName);
                } else {
                    // Reset the file selection UI
                    uploadBox.innerHTML = `
                        <div class="upload-icon">
                            <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        </div>
                        <p class="mb-0">Click here to upload payment proof</p>
                    `;
                    
                    // Re-add the file input
                    uploadBox.appendChild(proofFileInput);
                    proofFileInput.className = 'form-control d-none';
                    
                    // Clear stored file
                    this.selectedFile = null;
                }
            });
        } else {
            console.warn('Could not set up file upload: uploadBox or proofFileInput not found');
        }
    },
    
    // Fetch configuration including station prices
    fetchConfig: async function() {
        try {
            console.log('Fetching configuration from:', API_URL.CONFIG);
            const response = await fetch(API_URL.CONFIG);
            
            // Check if the response is OK first
            if (!response.ok) {
                console.warn(`Config API returned status: ${response.status}`);
                return; // Use defaults
            }
            
            // Get the raw text first to check it
            const rawText = await response.text();
            
            // Check if we got an empty response
            if (!rawText || rawText.trim() === '') {
                console.warn('Empty response from config API');
                return; // Use defaults
            }
            
            // Check for HTML error responses
            if (rawText.includes('<!DOCTYPE html>') || rawText.includes('<html>')) {
                console.warn('Received HTML instead of JSON from config API');
                return; // Use defaults
            }
            
            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(rawText);
            } catch (jsonError) {
                console.error('Invalid JSON received from config API:', 
                    rawText.length > 100 ? rawText.substring(0, 100) + '...' : rawText);
                return; // Use defaults
            }
            
            // Now process the valid JSON
            if (result.status === 'success' && result.data && result.data.station_prices) {
                // Update station prices from config
                STATION_PRICES = {
                    ...STATION_PRICES, // Keep defaults as fallback
                    ...result.data.station_prices // Override with config values
                };
                console.log('Station prices loaded from config:', STATION_PRICES);
            }
        } catch (error) {
            console.error('Error fetching configuration - using defaults:', error);
            // Continue with default values
        }
    },
    
    // Fetch and display payment methods
    fetchPaymentMethods: async function() {
        try {
            const response = await fetch(API_URL.PAYMENT_METHODS);
            const result = await response.json();
            
            if (result.status !== 'success') {
                console.error('Failed to fetch payment methods:', result.message);
                return;
            }
            
            this.renderPaymentMethods(result.data);
        } catch (error) {
            console.error('Error fetching payment methods:', error);
        }
    },
    
    // Render payment methods as radio buttons
    renderPaymentMethods: function(methods) {
        this.methodsContainer.innerHTML = '';
        
        methods.forEach((method, index) => {
            const methodId = `paymentMethod-${method.payment_method_id}`;
            const label = document.createElement('div');
            label.className = 'form-check form-check-inline';
            
            label.innerHTML = `
                <input class="form-check-input" type="radio" name="payment_method_id" 
                       id="${methodId}" value="${method.payment_method_id}" 
                       ${index === 0 ? 'checked' : ''}>
                <label class="form-check-label" for="${methodId}">
                    ${method.method_name}
                </label>
            `;
            
            this.methodsContainer.appendChild(label);
        });
    },
    
    // Get cart items from localStorage or sessionStorage or DOM
    getCartItems: function(purpose = 'unknown') {
        let items = [];
        let source = 'unknown';

        // PRIMARY AND PREFERRED SOURCE: window.selectedPointsList from cart.js
        if (typeof window.selectedPointsList !== 'undefined' && Array.isArray(window.selectedPointsList)) {
            try {
                console.log(`[getCartItems for ${purpose}] Attempting to use window.selectedPointsList.`);
                const cartJsItems = window.selectedPointsList;
                if (cartJsItems.length > 0) {
                    items = cartJsItems.map(item => this.normalizeCartItem(item)).filter(item => item !== null); 
                    source = 'window.selectedPointsList';
                } else {
                    console.warn(`[getCartItems for ${purpose}] window.selectedPointsList is empty.`);
                }
            } catch (e) {
                console.error(`[getCartItems for ${purpose}] Error processing window.selectedPointsList:`, e);
            }
        } else {
            console.warn(`[getCartItems for ${purpose}] window.selectedPointsList is not available or not an array.`);
        }

        // Fallback sources only if window.selectedPointsList didn't yield results or was unavailable
        if (!items || items.length === 0) {
            console.log(`[getCartItems for ${purpose}] Falling back from window.selectedPointsList.`);
            let rawCartItemsFallback = [];
            // 1. Try to get from localStorage for logged-in users
            const userId = this.getUserId();
            if (userId) {
                const localStorageCart = localStorage.getItem(`cart_${userId}`);
                if (localStorageCart) {
                    try { rawCartItemsFallback = JSON.parse(localStorageCart); source = `localStorage (user: ${userId})`; }
                    catch (e) { console.error('Error parsing cart from localStorage:', e); }
                }
                if ((!rawCartItemsFallback || rawCartItemsFallback.length === 0)) {
                    const genericCart = localStorage.getItem('cart');
                    if (genericCart) {
                        try { rawCartItemsFallback = JSON.parse(genericCart); source = 'localStorage (generic)'; }
                        catch (e) { console.error('Error parsing generic cart from localStorage:', e); }
                    }
                }
            }
            // 2. Try sessionStorage (if still no items)
            if (!rawCartItemsFallback || rawCartItemsFallback.length === 0) {
                const sessionId = sessionStorage.getItem('session_id');
                if (sessionId) {
                    const sessionCart = sessionStorage.getItem(`cart_${sessionId}`);
                    if (sessionCart) {
                        try { rawCartItemsFallback = JSON.parse(sessionCart); source = `sessionStorage (session: ${sessionId})`; }
                        catch (e) { console.error('Error parsing cart from sessionStorage:', e); }
                    }
                }
            }
            // 3. Try DOM (#selectedPoints) (if still no items) - THIS WAS A PROBLEM SOURCE
            // We should be careful if this is ever reached for critical data.
            if (!rawCartItemsFallback || rawCartItemsFallback.length === 0) {
                const selectedPointsElement = document.getElementById('selectedPoints');
                if (selectedPointsElement && selectedPointsElement.children.length > 0) {
                    console.warn(`[getCartItems for ${purpose}] Falling back to scraping #selectedPoints. Data from this source has been problematic.`);
                    const stationRows = Array.from(selectedPointsElement.children);
                    rawCartItemsFallback = stationRows.map(row => ({
                        id: row.dataset.stationId || row.querySelector('td:first-child')?.textContent?.trim(),
                        name: row.querySelector('td:first-child')?.textContent?.trim(),
                        type: row.dataset.stationType || 'horizontal'
                    }));
                    if(rawCartItemsFallback.length > 0) source = 'DOM (#selectedPoints)';
                }
            }
             // 4. window.cartData (if still no items)
            if ((!rawCartItemsFallback || rawCartItemsFallback.length === 0) && typeof window.cartData !== 'undefined' && Array.isArray(window.cartData) && window.cartData.length > 0) {
                rawCartItemsFallback = window.cartData;
                source = 'window.cartData';
            }
            
            if (Array.isArray(rawCartItemsFallback)) {
                 items = rawCartItemsFallback.map(this.normalizeCartItem, this).filter(item => item !== null);
            } else {
                console.warn(`[getCartItems for ${purpose}] Fallback rawCartItemsFallback was not an array. Source: ${source}`);
            }
        }
        
        // 5. Development sample data as absolute last resort (if still no items)
        if ((!items || items.length === 0) && this.isDevelopmentMode()) {
            items = [
                { id: 'DEV_H01', name: 'Dev MMA-39', type: 'horizontal' },
                { id: 'DEV_V01', name: 'Dev MMA-36', type: 'vertical' }
            ].map(this.normalizeCartItem, this).filter(item => item !== null); // Normalize samples too
            source = 'Development Sample Data';
        }
        
        console.log(`[getCartItems for ${purpose}] Final items from source "${source}":`, JSON.parse(JSON.stringify(items)));
        return items; 
    },
    
    // Check if we're in development/demo mode
    isDevelopmentMode: function() {
        // Check for development mode indicators
        // This could be a URL parameter, localStorage flag, or hostname check
        return window.location.hostname === 'localhost' || 
               window.location.hostname === '127.0.0.1' ||
               localStorage.getItem('dev_mode') === 'true';
    },
    
    // Calculate total price
    calculateTotal: function(items) {
        return items.reduce((total, item) => {
            const price = this.getPriceForStationType(item.type);
            return total + price;
        }, 0);
    },
    
    // Get price based on station type
    getPriceForStationType: function(stationType) {
        // Use prices from config
        return STATION_PRICES[stationType] || 300;
    },
    
    // Format price as PHP currency
    formatPrice: function(price) {
        return '₱' + price.toFixed(2);
    },
    
    // Show the payment modal
    showModal: function() {
        // Get cart items - strongly prefer window.selectedPointsList via the updated getCartItems
        let items = this.getCartItems('modal population'); 
        
        const isLoggedIn = this.checkIfUserIsLoggedIn();
        
        if (!isLoggedIn) {
            alert('You must be logged in to request certificates.');
            // Assuming Auth.showLoginModal() or similar exists and is preferred
            if (typeof Auth !== 'undefined' && Auth.showLoginModal) {
                 Auth.showLoginModal();
            } else {
                const authModalEl = document.getElementById('authModal');
                if (authModalEl) {
                    const authModal = new bootstrap.Modal(authModalEl);
                    authModal.show();
                } else {
                    console.warn("Auth modal not found and Auth.showLoginModal not available.");
                }
            }
            return;
        }
        
        // If cart is still empty after all checks, use sample data (getCartItems handles this if in dev mode)
        if (items.length === 0 && !this.isDevelopmentMode()) {
             alert('Your cart is empty. Please add stations to make a request.');
             return; // Don't show modal for empty cart unless in dev mode with samples
        }
        if (items.length === 0 && this.isDevelopmentMode()) {
            console.log('Cart appears to be empty. Using sample data for demonstration (dev mode).');
            // getCartItems should already provide sample data if in dev mode and cart is empty.
            // If it didn't, we re-fetch them here to ensure samples are shown.
            items = this.getCartItems(); 
        }

        this.stationsList.innerHTML = '';
        if (this.alertElement) {
            this.alertElement.classList.add('d-none');
        }
        this.requestIdInput.value = '';
        this.form.reset();
        
        // Reset file selection
        this.selectedFile = null;
        
        items.forEach(item => { // item is now {id, name, type}
            const price = this.getPriceForStationType(item.type);
            const row = document.createElement('tr');
            row.dataset.stationId = item.id;
            row.dataset.stationName = item.name;
            row.dataset.stationType = item.type;
            
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${item.name}</td>
                <td>${this.formatStationType(item.type)}</td>
                <td>₱${price.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-item" 
                            data-station-id="${item.id}">X</button>
                </td>
            `;
            this.stationsList.appendChild(row);
        });
        
        // Set total price and count
        const total = this.calculateTotal(items);
        this.totalAmount.textContent = this.formatPrice(total);
        document.getElementById('stationsCount').textContent = items.length;
        
        // Set suggested paid amount
        document.getElementById('paidAmount').value = total.toFixed(2);
        
        // Format the transaction code display with actual user ID
        const userId = this.getUserId();
        const dateStr = this.formatDate();
        this.transactionCode.value = this.generateTransactionCode();
        
        // Show the modal
        this.bsModal.show();
        
        // Add event listeners to remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const stationId = e.currentTarget.dataset.stationId;
                this.removeItemFromCart(stationId);
                e.currentTarget.closest('tr').remove();
                
                // Update totals after item removal
                this.updateTotalsAfterRemoval();
            });
        });
        
        // Make sure the upload box is set up
        this.setupFileUpload();
    },
    
    // Update totals after item removal
    updateTotalsAfterRemoval: function() {
        // Get current items from the table
        const rows = Array.from(this.stationsList.querySelectorAll('tr'));
        const total = rows.reduce((sum, row) => {
            const priceText = row.querySelector('td:nth-child(4)').textContent;
            const price = parseFloat(priceText.replace('₱', '').trim());
            return sum + (isNaN(price) ? 0 : price);
        }, 0);
        
        // Update the count
        document.getElementById('stationsCount').textContent = rows.length;
        
        // Update the total
        this.totalAmount.textContent = this.formatPrice(total);
        
        // Update the paid amount field
        document.getElementById('paidAmount').value = total.toFixed(2);
        
        // If no items left, close modal
        if (rows.length === 0) {
            this.bsModal.hide();
            alert('Your cart is now empty.');
        }
    },
    
    // Helper function to check if user is logged in using multiple indicators
    checkIfUserIsLoggedIn: function() {
        // Check multiple sources to determine login state
        
        // 1. Check token in localStorage
        const hasToken = !!localStorage.getItem('token');
        
        // 2. Check if user_id exists in localStorage
        const hasUserId = !!localStorage.getItem('user_id');
        
        // 3. Check DOM for logged-in indicators
        const logoutButton = document.getElementById('logoutBtn');
        const hasLogoutButton = logoutButton && !logoutButton.classList.contains('d-none');
        
        // 4. Check account details in header
        const accountDetails = document.getElementById('headerAccountDetails');
        const hasAccountDetails = accountDetails && !accountDetails.classList.contains('d-none');
        
        // 5. Check for admin indicator
        const adminElement = document.querySelector('.admin');
        const hasAdminElement = !!adminElement;
        
        // Return true if ANY of the above indicators suggest the user is logged in
        return hasToken || hasUserId || hasLogoutButton || hasAccountDetails || hasAdminElement;
    },
    
    // Format current date as YYYYMMDD using Asia/Manila timezone
    formatDate: function() {
        // Create date object using Manila timezone (UTC+8)
        const options = { timeZone: 'Asia/Manila' };
        const now = new Date();
        
        // Get Manila date components
        const manilaDate = new Date(now.toLocaleString('en-US', options));
        
        // Format as YYYYMMDD
        const year = manilaDate.getFullYear();
        const month = String(manilaDate.getMonth() + 1).padStart(2, '0');
        const day = String(manilaDate.getDate()).padStart(2, '0');
        
        // Log the timezone adjustment for debugging
        console.log('Local date:', now.toISOString());
        console.log('Manila date:', manilaDate.toISOString());
        console.log('Using date for transaction code:', `${year}${month}${day}`);
        
        return year + month + day;
    },
    
    // Format station type for display
    formatStationType: function(type) {
        const types = {
            'horizontal': 'Horizontal GCP',
            'vertical': 'Vertical Benchmark',
            'gravity': 'Gravity Station'
        };
        return types[type] || type;
    },
    
    // Show error alert
    showAlert: function(message) {
        if (!this.alertElement) {
            // If alertElement doesn't exist, fallback to alert()
            alert(message);
            return;
        }
        this.alertElement.textContent = message;
        this.alertElement.classList.remove('d-none');
    },
    
    // Hide error alert
    hideAlert: function() {
        if (!this.alertElement) return;
        this.alertElement.textContent = '';
        this.alertElement.classList.add('d-none');
    },
    
    // Handle request certificates button click
    handleRequestCertificates: function(e) {
        e.preventDefault();
        this.showModal();
    },
    
    // Handle form submission
    handleSubmit: async function(e) {
        e.preventDefault();
        
        try {
            // Basic form validation
            const paidAmount = document.getElementById('paidAmount').value;
            if (!paidAmount || isNaN(parseFloat(paidAmount)) || parseFloat(paidAmount) <= 0) {
                this.showAlert('Please enter a valid paid amount.');
                return;
            }
            
            const paymentMethodInput = document.querySelector('input[name="payment_method_id"]:checked');
            if (!paymentMethodInput) {
                this.showAlert('Please select a payment method.');
                return;
            }
            
            const referenceNumber = document.getElementById('referenceNumber').value;
            if (!referenceNumber) {
                this.showAlert('Please enter a reference number.');
                return;
            }
            
            // Check for proof file using our stored file
            const proofFile = this.selectedFile;
            
            if (!proofFile) {
                this.showAlert('Please upload a payment proof.');
                return;
            }
            
            // Hide any previous errors
            this.hideAlert();
            
            // Create request first
            const requestId = await this.createRequest();
            if (!requestId) return;
            
            // Get authentication token
            let token = localStorage.getItem('webgnis_token');
            if (!token && this.checkIfUserIsLoggedIn()) {
                // Try alternative sources if token is missing
                token = localStorage.getItem('token');
                
                if (!token) {
                    alert('Authentication token not found. Please log in again.');
                    return null;
                }
            }
            
            if (!token) {
                alert('Session expired. Please log in again.');
                window.location.reload();
                return;
            }
            
            try {
                // Create FormData and append necessary fields
                const formData = new FormData();
                formData.append('request_id', requestId);
                formData.append('paid_amount', paidAmount);
                formData.append('payment_method', paymentMethodInput.value);
                formData.append('reference_number', referenceNumber);
                formData.append('proof_file', proofFile);
                
                const uploadResponse = await fetch(API_URL.UPLOAD_PROOF, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    },
                    body: formData
                });
                
                const uploadResult = await uploadResponse.json();
                
                if (uploadResult.status !== 'success') {
                    this.showAlert(`Payment failed: ${uploadResult.message}`);
                    return;
                }
                
                // Clear cart
                this.clearCart();
                
                // Close modal
                this.bsModal.hide();
                
                // Show success message
                alert(`Payment submitted successfully! Your transaction code is ${uploadResult.data.transaction_code}`);
                
                // Redirect to tracker page
                window.location.href = 'tracker.html';
                
            } catch (error) {
                console.error('Error submitting payment proof:', error);
                this.showAlert('An error occurred while submitting payment proof. Please try again.');
            }
        } catch (error) {
            console.error('Error in handleSubmit:', error);
            this.showAlert('An error occurred. Please try again.');
        }
    },
    
    // Get the current user ID from available sources
    getUserId: function() {
        // Always use the direct user_id from localStorage if available
        const userId = localStorage.getItem('user_id');
        if (userId) {
            return userId;
        }
        
        // Try to extract from DOM elements if available
        const userDisplayName = document.getElementById('headerUserDisplayName');
        if (userDisplayName && userDisplayName.dataset.userId) {
            return userDisplayName.dataset.userId;
        }
        
        // Check URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('user_id')) {
            return urlParams.get('user_id');
        }
        
        // For admin users, check if admin ID is available
        if (document.querySelector('.admin')) {
            // Default admin ID
            return '1';
        }
        
        // Default to a placeholder
        return 'user';
    },

    // Create a new request
    createRequest: async function() {
        let items = [];
        let itemsSource = 'unknown';

        // PRIMARY AND PREFERRED SOURCE: window.selectedPointsList from cart.js
        if (typeof window.selectedPointsList !== 'undefined' && Array.isArray(window.selectedPointsList)) {
            try {
                console.log('[createRequest] Attempting to use window.selectedPointsList.');
                const cartJsItems = window.selectedPointsList;
                if (cartJsItems.length > 0) {
                    items = cartJsItems.map(item => this.normalizeCartItem(item)).filter(item => item !== null);
                    itemsSource = 'window.selectedPointsList';
                    if (items.length !== cartJsItems.length) {
                        console.warn('[createRequest] Some items from window.selectedPointsList were filtered out by normalization.');
                    }
                } else {
                     console.warn('[createRequest] window.selectedPointsList is empty.');
                }
            } catch (e) {
                console.error('[createRequest] Error processing window.selectedPointsList:', e);
            }
        } else {
            console.warn('[createRequest] window.selectedPointsList is not available or not an array.');
        }

        // Fallback: If window.selectedPointsList failed, try scraping the payment modal table
        if (!items || items.length === 0) {
            console.warn('[createRequest] Failed to get items from window.selectedPointsList. Falling back to Payment Modal Table.');
            const rows = document.querySelectorAll('#paymentStationsList tr');
            if (rows && rows.length > 0) {
                items = Array.from(rows).map(row => {
                    const id = row.dataset.stationId;
                    const name = row.dataset.stationName || row.querySelector('td:nth-child(2)')?.textContent.trim();
                    const type = row.dataset.stationType;
                    
                    if (!id || !name || !type) {
                        console.warn('[createRequest] Skipping row in payment modal (fallback) due to missing data attributes:', {id, name, type}, row);
                        return null;
                    }
                    return { id: id, name: name, type: type }; 
                }).filter(item => item !== null);
                if (items.length > 0) itemsSource = 'Payment Modal Table (fallback)';
            }
        }
        
        if (!items || items.length === 0) {
            console.error('[createRequest] CRITICAL: No items from window.selectedPointsList or Payment Modal table. Attempting PaymentController.getCartItems() as last resort.');
            items = this.getCartItems('createRequest last resort'); 
            if (items.length > 0) itemsSource = 'PaymentController.getCartItems() (last resort)';
        }
        
        if (!items || items.length === 0) {
            alert('No items found to create a request. Please add stations to your cart.');
            return null;
        }

        console.log(`[createRequest] Items for API request (source: ${itemsSource}):`, JSON.parse(JSON.stringify(items)));
        
        const validatedItems = items.filter(item => item && typeof item.id !== 'undefined' && typeof item.name !== 'undefined' && typeof item.type !== 'undefined');

        if (validatedItems.length !== items.length) {
            console.error('[createRequest] Some items were filtered out due to missing id, name, or type:', items, validatedItems);
             if (validatedItems.length === 0) {
                alert('Request creation failed: No valid items to process after final validation.');
                return null;
            }
            // Potentially inform user, but proceed with validated items
        }

        let token = localStorage.getItem('webgnis_token');
        if (!token && this.checkIfUserIsLoggedIn()) {
            // Try alternative sources if token is missing
            token = localStorage.getItem('token');
            
            if (!token) {
                alert('Authentication token not found. Please log in again.');
                return null;
            }
        }
        
        if (!token) {
            alert('Authentication token not found. Please log in again.');
            return null;
        }
        
        try {
            // Make sure we're using the actual user token and not admin_token
            console.log('Using token:', token);
            
            const response = await fetch(API_URL.CREATE_REQUEST, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    items: validatedItems.map(item => ({ // item here *must* be {id, name, type}
                        station_id: String(item.id),    // Ensure string, use true ID
                        station_name: String(item.name),  // Ensure string, use true name
                        station_type: String(item.type)   // Ensure string, use true type
                    })),
                    clear_cart: true 
                })
            });
            
            const result = await response.json();
            
            if (result.status !== 'success') {
                alert(`Request creation failed: ${result.message}`);
                return null;
            }
            
            return result.data.request_id;
        } catch (error) {
            console.error('Error creating request:', error);
            alert('Failed to create request. Please try again.');
            return null;
        }
    },
    
    // Upload proof file
    uploadProofFile: async function(transactionId, token) {
        if (!token) {
            token = localStorage.getItem('webgnis_token');
            if (!token && this.checkIfUserIsLoggedIn()) {
                token = localStorage.getItem('token');
                
                if (!token) {
                    alert('Authentication token not found. Please log in again.');
                    return false;
                }
            }
        }
        
        // Use the file from our central storage
        const proofFile = this.selectedFile;
        
        if (!proofFile) {
            alert('No payment proof file selected. Please select a file first.');
            return false;
        }
        
        const formData = new FormData();
        formData.append('transaction_id', transactionId);
        formData.append('proof_file', proofFile);
        
        try {
            const response = await fetch(API_URL.UPLOAD_PROOF, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.status !== 'success') {
                console.error('Failed to upload proof:', result.message);
                alert('Payment submitted but proof upload failed. You can upload it later from your account.');
                return false;
            }
            
            return true;
        } catch (error) {
            console.error('Error uploading proof:', error);
            alert('Payment submitted but proof upload failed. You can upload it later from your account.');
            return false;
        }
    },
    
    // Clear the cart after successful submission
    clearCart: function() {
        try {
            console.log('Clearing cart...');
            const userId = localStorage.getItem('user_id');
            
            // Clear all possible cart storage locations
            if (userId) {
                // User-specific cart storage
                console.log(`Clearing user-specific cart for user_id: ${userId}`);
                localStorage.removeItem(`cart_${userId}`);
                localStorage.removeItem('cart'); // Also clear generic cart
            }
            
            // Clear session-based cart
            const sessionId = sessionStorage.getItem('session_id');
            if (sessionId) {
                console.log(`Clearing session-based cart for session_id: ${sessionId}`);
                sessionStorage.removeItem(`cart_${sessionId}`);
            }
            
            // Clear generic storage as well
            localStorage.removeItem('cart');
            sessionStorage.removeItem('cart');
            
            // Try to use cart API if available
            if (typeof window.CartAPI !== 'undefined' && typeof window.CartAPI.clearCart === 'function') {
                console.log('Using CartAPI to clear cart');
                window.CartAPI.clearCart();
            }
            
            // Update UI elements
            
            // Update cart badge count if it exists
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                cartBadge.textContent = '0';
            }
            
            // Update selected points table
            const selectedPoints = document.getElementById('selectedPoints');
            if (selectedPoints) {
                selectedPoints.innerHTML = '';
            }
            
            // Update cart count display
            const cartCountDisplay = document.getElementById('cartCount');
            if (cartCountDisplay) {
                cartCountDisplay.textContent = '0';
            }
            
            // If there's a global updateCartDisplay function, call it
            if (typeof window.updateCartDisplay === 'function') {
                window.updateCartDisplay();
            }
            
            console.log('Cart cleared successfully');
        } catch (error) {
            console.error('Error clearing cart:', error);
        }
    },
    
    // Handle "Pay Later" button click
    handlePayLater: async function(e) {
        e.preventDefault();
        
        try {
            // Only create the request
            const requestId = await this.createRequest();
            if (!requestId) return;
            
            // Clear cart
            this.clearCart();
            
            // Close modal
            this.bsModal.hide();
            
            // Show message
            alert('Your request has been saved. Please make payment within 15 days to avoid expiration.');
            
        } catch (error) {
            console.error('Error creating payment request:', error);
            alert('An error occurred. Please try again.');
        }
    },
    
    // Remove item from cart
    removeItemFromCart: function(stationIdToRemove) { // stationIdToRemove is the true ID
        const userId = localStorage.getItem('user_id');
        if (userId) {
            const cartKey = `cart_${userId}`;
            // Cart items in localStorage might not be normalized yet.
            // So we fetch, normalize, filter, then save normalized back.
            // Or, more simply, assume CartAPI or other mechanisms add items with a consistent 'id' field.
            let cartItems = JSON.parse(localStorage.getItem(cartKey) || '[]');
            
            // Filter based on a property that is consistently the true ID in storage.
            // This assumes items in storage have an 'id' or 'station_id' that is the true ID.
            const updatedCart = cartItems.filter(item => {
                const itemId = item.id || item.station_id; // Adapt based on how items are stored
                return String(itemId) !== String(stationIdToRemove);
            });
            localStorage.setItem(cartKey, JSON.stringify(updatedCart));

        } else {
            const sessionId = sessionStorage.getItem('session_id');
            if (sessionId) {
                const cartKey = `cart_${sessionId}`;
                let cartItems = JSON.parse(sessionStorage.getItem(cartKey) || '[]');
                const updatedCart = cartItems.filter(item => {
                     const itemId = item.id || item.station_id;
                     return String(itemId) !== String(stationIdToRemove);
                });
                sessionStorage.setItem(cartKey, JSON.stringify(updatedCart));
            }
        }
        
        // Update selected points table in the main UI if it exists
        const selectedPoints = document.getElementById('selectedPoints');
        if (selectedPoints) {
            // The rows in #selectedPoints might also need to be identified by true ID.
            // This assumes rows in #selectedPoints have a `data-station-id` attribute with the true ID.
            const itemRow = selectedPoints.querySelector(`tr[data-station-id="${stationIdToRemove}"]`);
            if (itemRow) {
                itemRow.remove();
            }
        }
        // Also update cart badge/count if Cart.removeItem or similar external API is not used.
        if (typeof window.updateCartDisplay === 'function') {
            window.updateCartDisplay(); // Or specific logic to update count
        }
    },

    // Generate transaction code with proper sequential numbering
    generateTransactionCode: function() {
        const userId = this.getUserId();
        const dateStr = this.formatDate();
        
        // Check if there are existing transaction codes for this user and date
        let nextNum = 1;
        
        // If we're on the tracker page, we can try to extract existing codes
        const requestTable = document.querySelector('.table-requests');
        if (requestTable) {
            const transactionCodes = Array.from(requestTable.querySelectorAll('tr'))
                .map(row => {
                    const codeCell = row.querySelector('td:nth-child(3)');
                    return codeCell ? codeCell.textContent.trim() : '';
                })
                .filter(code => code.includes(`CSUMGB-${dateStr}-${userId}`));
            
            if (transactionCodes.length > 0) {
                // Extract the highest number
                const numbers = transactionCodes.map(code => {
                    const parts = code.split('-');
                    const numPart = parts[parts.length - 1];
                    return parseInt(numPart, 10) || 0;
                });
                
                nextNum = Math.max(...numbers) + 1;
            }
        }
        
        // Format the transaction number with leading zeros
        const formattedNum = String(nextNum).padStart(3, '0');
        return `CSUMGB-${dateStr}-${userId}-${formattedNum}`;
    }
};

// Initialize the payment module when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    PaymentController.init();
});

export default PaymentController;