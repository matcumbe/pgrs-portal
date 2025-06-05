# WebGNIS Users API Documentation

## Overview
The `users_api.php` file provides user management functionality for the WebGNIS application. It handles user authentication, registration, profile management, and access to user-related data. The API supports both individual and company user types with different data requirements for each.

## Configuration and Setup
- Requires `users_config.php` for database connection details and constants
- Sets CORS headers to allow cross-origin requests
- Configures content type as JSON with UTF-8 encoding
- Supports multiple HTTP methods (POST, GET, PUT, DELETE)

## Authentication
- Uses JWT (JSON Web Token) for authentication
- Implements token generation, validation, and verification
- Supports role-based access control

## Request Handling
- Uses the `action` query parameter to determine the endpoint
- Supports path segments for resource identification (e.g., users/123)
- Handles preflight CORS requests

## Endpoints

### 1. Login
- **Path**: `/login`
- **Method**: POST
- **Request Body**: 
  - `username`: User's username
  - `password`: User's password
- **Response**: JSON object containing token and user data
- **Function**: `handleLogin()`

### 2. Logout
- **Path**: `/logout`
- **Method**: POST
- **Response**: Success message (client-side logout)
- **Function**: Handled directly in the router

### 3. Get Current User
- **Path**: `/users/me`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Response**: JSON object containing current user data
- **Function**: `getCurrentUser($db)`

### 4. Get All Users
- **Path**: `/users`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Response**: JSON array of users
- **Function**: `getAllUsers($db)`

### 5. Get User by ID
- **Path**: `/users/{id}`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Parameters**: 
  - `id`: User ID
- **Response**: JSON object containing user data
- **Function**: `getUserById($db, $id)`

### 6. Create User
- **Path**: `/users`
- **Method**: POST
- **Headers**: Authorization: Bearer {token}
- **Request Body**: User data (varies by user type)
- **Response**: JSON object containing created user data
- **Function**: `createUser($db)`

### 7. Update User
- **Path**: `/users/{id}`
- **Method**: PUT
- **Headers**: Authorization: Bearer {token}
- **Parameters**: 
  - `id`: User ID
- **Request Body**: User data to update
- **Response**: Success message
- **Function**: `updateUser($db, $id)`

### 8. Delete User
- **Path**: `/users/{id}`
- **Method**: DELETE
- **Headers**: Authorization: Bearer {token}
- **Parameters**: 
  - `id`: User ID
- **Response**: Success message
- **Function**: `deleteUser($db, $id)`

### 9. Get All Companies
- **Path**: `/company`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Response**: JSON array of company users
- **Function**: `getAllCompanies($db)`

### 10. Get Company by ID
- **Path**: `/company/{id}`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Parameters**: 
  - `id`: Company ID
- **Response**: JSON object containing company data
- **Function**: `getCompanyById($db, $id)`

### 11. Get All Individuals
- **Path**: `/individual`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Response**: JSON array of individual users
- **Function**: `getAllIndividuals($db)`

### 12. Get Individual by ID
- **Path**: `/individual/{id}`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Parameters**: 
  - `id`: Individual ID
- **Response**: JSON object containing individual data
- **Function**: `getIndividualById($db, $id)`

### 13. Get Sectors
- **Path**: `/sectors`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Response**: JSON array of sectors
- **Function**: `getSectors($db)`

### 14. Get Sexes
- **Path**: `/sexes`
- **Method**: GET
- **Headers**: Authorization: Bearer {token}
- **Response**: JSON array of sexes
- **Function**: `getSexes($db)`

### 15. Request Certificate
- **Path**: `/certificates/request`
- **Method**: POST
- **Headers**: Authorization: Bearer {token}
- **Request Body**: Certificate request data
- **Response**: JSON object containing reference ID
- **Function**: Handled directly in the router

## Helper Functions

### Authentication Functions
- `generateJWT($user)`: Generates a JWT token for a user
- `verifyToken($requiredRole = null, $exitOnFail = true)`: Verifies a JWT token and optionally checks for required role

### User Management Functions
- `getCurrentUser($db)`: Retrieves the current authenticated user
- `getAllUsers($db)`: Retrieves all users
- `getUserById($db, $id)`: Retrieves a specific user by ID
- `createUser($db)`: Creates a new user
- `updateUser($db, $id)`: Updates an existing user
- `deleteUser($db, $id)`: Deletes a user

### Company Functions
- `getAllCompanies($db)`: Retrieves all company users
- `getCompanyById($db, $id)`: Retrieves a specific company by ID

### Individual Functions
- `getAllIndividuals($db)`: Retrieves all individual users
- `getIndividualById($db, $id)`: Retrieves a specific individual by ID

### Reference Data Functions
- `getSectors($db)`: Retrieves all sectors
- `getSexes($db)`: Retrieves all sexes

### Validation Functions
- `validateUserData($data)`: Validates user data before creation or update

### Response Functions
- `returnResponse($statusCode, $message, $data)`: Returns a standardized JSON response

## Database Interaction
- Uses PDO for database operations
- Implements prepared statements for secure queries
- Handles multiple tables: users, company_details, individual_details, sectors, sexes
- Supports transactions for complex operations

## Error Handling
- Validates input data before processing
- Returns appropriate HTTP status codes for different scenarios
- Provides detailed error messages for debugging
- Implements fallback mechanisms for reference data

## Security Considerations
- Uses password hashing for secure storage
- Implements JWT for stateless authentication
- Validates user input to prevent injection attacks
- Enforces role-based access control
- Uses prepared statements to prevent SQL injection 