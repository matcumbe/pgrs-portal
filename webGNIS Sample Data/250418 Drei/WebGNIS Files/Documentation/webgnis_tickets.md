# WebGNIS Tickets Implementation Plan

## Overview
This document outlines the implementation plan for the WebGNIS ticket system that will handle user requests for GCP data. The system will:
1. Allow users to select points from the index.html page
2. Store these selections in a database
3. Track payment information through manual receipt uploads
4. Maintain a record of all requests and their statuses

## Database Design

### 1. Database Creation
We will create a new database called `webgnis_tickets` to separate ticket concerns from user management.

```sql
CREATE DATABASE IF NOT EXISTS webgnis_tickets;
USE webgnis_tickets;
```

### 2. Tables Structure

#### a. Tickets Table
```sql
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'awaiting_payment', 'payment_uploaded', 'verified', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    purpose VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES webgnis_users.users(user_id) ON DELETE CASCADE
);
```

#### b. Ticket_Items Table (Selected Points)
```sql
CREATE TABLE ticket_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    gcp_id VARCHAR(50) NOT NULL,
    gcp_type VARCHAR(50) NOT NULL,
    coordinates VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) DEFAULT 0.00,
    CONSTRAINT fk_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);
```

#### c. Payments Table
```sql
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    reference_number VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT 'LinkBiz',
    amount DECIMAL(10, 2) NOT NULL,
    receipt_image VARCHAR(255), /* Path to uploaded receipt image */
    payment_date DATETIME,
    verification_status ENUM('unverified', 'verified', 'rejected') DEFAULT 'unverified',
    verified_by VARCHAR(100),
    verification_date DATETIME,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);
```

#### d. Ticket_History Table (For Audit)
```sql
CREATE TABLE ticket_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    changed_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_ticket_id FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
);
```

## Implementation Process

### 1. Frontend Implementation (index.html and script.js)

1. Modify the map interface to allow for selecting multiple points of interest
2. Add a "Create Request" button that collects selected points
3. Create a request form that includes:
   - Purpose of request
   - Review of selected points
   - Terms and conditions acceptance
4. Implement client-side validation

#### Code snippet for collecting selected points:
```javascript
// In script.js
let selectedPoints = [];

function addPointToSelection(gcpId, gcpType, coordinates, price) {
    selectedPoints.push({
        gcp_id: gcpId,
        gcp_type: gcpType,
        coordinates: coordinates,
        price: price
    });
    updateSelectionUI();
}

function submitTicketRequest() {
    if (selectedPoints.length === 0) {
        showError("Please select at least one point");
        return;
    }
    
    const purpose = document.getElementById('request-purpose').value;
    if (!purpose) {
        showError("Please specify the purpose of your request");
        return;
    }
    
    // Create the request payload
    const requestData = {
        purpose: purpose,
        points: selectedPoints
    };
    
    // Send the request to the API
    fetch('tickets_api.php?action=create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + getAuthToken()
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 201) {
            // Success - redirect to payment page
            window.location.href = 'payment.html?ticket_id=' + data.data.ticket_id;
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError("An error occurred while creating your request");
    });
}
```

### 2. Backend API (tickets_api.php)

Create a new API file to handle ticket-related operations:

```php
<?php
require_once 'users_config.php';
require_once 'config.php'; // For GCP pricing information

// Set headers similar to users_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Check for preflight CORS request
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the endpoint from the action parameter
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';
$parts = explode('/', $endpoint);
$mainEndpoint = $parts[0];

// Extract ID if present in the endpoint
$id = null;
if (count($parts) > 1 && is_numeric($parts[1])) {
    $id = intval($parts[1]);
}

// Connect to databases
$usersDb = connectDB(); // Connect to webgnis_users
$ticketsDb = connectTicketsDB(); // Create this function in config

if (!$usersDb || !$ticketsDb) {
    returnResponse(500, "Database connection error", null);
    exit;
}

// Route the request
try {
    switch ($mainEndpoint) {
        case 'create':
            if ($method === 'POST') {
                createTicket($usersDb, $ticketsDb);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        case 'tickets':
            switch ($method) {
                case 'GET':
                    if ($id) {
                        getTicketById($usersDb, $ticketsDb, $id);
                    } else {
                        getUserTickets($usersDb, $ticketsDb);
                    }
                    break;
                default:
                    returnResponse(405, "Method not allowed", null);
                    break;
            }
            break;
            
        case 'payment':
            if ($method === 'POST' && $id) {
                uploadPayment($usersDb, $ticketsDb, $id);
            } else {
                returnResponse(405, "Method not allowed", null);
            }
            break;
            
        default:
            returnResponse(404, "Endpoint not found", null);
            break;
    }
} catch (Exception $e) {
    returnResponse(500, "Server error: " . $e->getMessage(), null);
}

// Implementation of the API functions would follow here
// ...
```

### 3. Payment Upload Page (payment.html)

Create a page for users to upload their payment proof:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGNIS - Payment Upload</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="payment-container">
        <h2>Upload Payment Proof</h2>
        <div id="ticket-details">
            <!-- Ticket details will be populated here -->
        </div>
        
        <div class="payment-instructions">
            <h3>Payment Instructions</h3>
            <p>1. Make a payment via LinkBiz using the details below</p>
            <p>2. Take a screenshot of your payment receipt</p>
            <p>3. Upload the screenshot and provide the reference number below</p>
            
            <div class="linkbiz-details">
                <p><strong>Payment Amount:</strong> <span id="payment-amount">₱0.00</span></p>
                <p><strong>Merchant ID:</strong> WebGNIS-Data-123</p>
            </div>
        </div>
        
        <form id="payment-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="reference-number">Reference Number</label>
                <input type="text" id="reference-number" name="reference_number" required>
            </div>
            
            <div class="form-group">
                <label for="receipt-image">Receipt Screenshot</label>
                <input type="file" id="receipt-image" name="receipt_image" accept="image/*" required>
            </div>
            
            <div class="form-group">
                <label for="payment-notes">Notes (Optional)</label>
                <textarea id="payment-notes" name="notes"></textarea>
            </div>
            
            <button type="submit" class="btn-primary">Submit Payment Proof</button>
        </form>
    </div>
    
    <script src="users.js"></script>
    <script src="tickets.js"></script>
</body>
</html>
```

### 4. Admin Panel Enhancements (admin.html/admin.js)

Add new sections to the admin panel for:
1. Viewing all ticket requests
2. Verifying payments
3. Processing and completing requests
4. Generating reports

## API Endpoints

### 1. Create Ticket
- **Endpoint**: `tickets_api.php?action=create`
- **Method**: POST
- **Auth Required**: Yes
- **Request Body**:
  ```json
  {
    "purpose": "Survey project for Municipality X",
    "points": [
      {
        "gcp_id": "GCP001",
        "gcp_type": "Primary",
        "coordinates": "14.5995,120.9842",
        "price": 150.00
      },
      {
        "gcp_id": "GCP002",
        "gcp_type": "Secondary",
        "coordinates": "14.6005,120.9852",
        "price": 100.00
      }
    ]
  }
  ```
- **Response**:
  ```json
  {
    "status": 201,
    "message": "Ticket created successfully",
    "data": {
      "ticket_id": 123,
      "total_amount": 250.00,
      "status": "awaiting_payment"
    }
  }
  ```

### 2. Get User Tickets
- **Endpoint**: `tickets_api.php?action=tickets`
- **Method**: GET
- **Auth Required**: Yes
- **Response**:
  ```json
  {
    "status": 200,
    "message": "Tickets retrieved successfully",
    "data": [
      {
        "ticket_id": 123,
        "request_date": "2023-06-15 14:30:00",
        "status": "awaiting_payment",
        "purpose": "Survey project",
        "total_amount": 250.00,
        "item_count": 2
      },
      {
        "ticket_id": 124,
        "request_date": "2023-06-16 09:15:00",
        "status": "completed",
        "purpose": "Research",
        "total_amount": 450.00,
        "item_count": 3
      }
    ]
  }
  ```

### 3. Get Ticket Details
- **Endpoint**: `tickets_api.php?action=tickets/123`
- **Method**: GET
- **Auth Required**: Yes
- **Response**:
  ```json
  {
    "status": 200,
    "message": "Ticket retrieved successfully",
    "data": {
      "ticket_id": 123,
      "user": {
        "user_id": 456,
        "username": "john_doe",
        "email": "john@example.com"
      },
      "request_date": "2023-06-15 14:30:00",
      "status": "awaiting_payment",
      "purpose": "Survey project",
      "total_amount": 250.00,
      "items": [
        {
          "item_id": 1,
          "gcp_id": "GCP001",
          "gcp_type": "Primary",
          "coordinates": "14.5995,120.9842",
          "price": 150.00
        },
        {
          "item_id": 2,
          "gcp_id": "GCP002",
          "gcp_type": "Secondary",
          "coordinates": "14.6005,120.9852",
          "price": 100.00
        }
      ],
      "payment": null
    }
  }
  ```

### 4. Upload Payment
- **Endpoint**: `tickets_api.php?action=payment/123`
- **Method**: POST
- **Auth Required**: Yes
- **Request Body**: `multipart/form-data` with fields:
  - reference_number: string
  - receipt_image: file
  - notes: string (optional)
- **Response**:
  ```json
  {
    "status": 200,
    "message": "Payment proof uploaded successfully",
    "data": {
      "ticket_id": 123,
      "status": "payment_uploaded",
      "payment_id": 789
    }
  }
  ```

## Database Connection Function

Add this function to `config.php`:

```php
function connectTicketsDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=webgnis_tickets;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Tickets database connection failed: " . $e->getMessage());
        return null;
    }
}
```

## Workflow

1. **User selects points** on the map in index.html
2. **User submits request** with purpose and selected points
3. **System creates ticket** in pending status and calculates total cost
4. **User is redirected** to payment page
5. **User makes payment** via LinkBiz (external to the system)
6. **User uploads payment proof** with reference number
7. **Admin verifies payment** and changes status to verified
8. **Admin processes request** and prepares data
9. **Admin marks request complete** when data is ready
10. **User can download data** once the request is complete

## Security Considerations

1. Implement proper authentication for all endpoints
2. Validate all user inputs
3. Implement CSRF protection
4. Secure file uploads (limit file types, size)
5. Use prepared statements for all database operations
6. Log all important actions for audit purposes

## Next Steps

1. Create the `webgnis_tickets` database and tables
2. Implement the `tickets_api.php` file
3. Modify the frontend to collect selected points
4. Create the payment upload page
5. Update the admin panel to handle ticket management
6. Test the entire workflow
7. Document the API for future reference 