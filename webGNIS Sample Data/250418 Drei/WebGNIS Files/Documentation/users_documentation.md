# WebGNIS Users Database and API Documentation

This document provides comprehensive information about the WebGNIS Users database structure and API endpoints. This documentation serves as a reference for developers working with the WebGNIS user management system.

## Table of Contents

1. [Database Schema](#database-schema)
   - [Database Overview](#database-overview)
   - [Tables Structure](#tables-structure)
2. [API Reference](#api-reference)
   - [Authentication](#authentication)
   - [Users Endpoints](#users-endpoints)
   - [Company Endpoints](#company-endpoints)
   - [Individual Endpoints](#individual-endpoints)
   - [Reference Data Endpoints](#reference-data-endpoints)
3. [Client Usage](#client-usage)
   - [JavaScript API Client](#javascript-api-client)
   - [Example Usage](#example-usage)

## Database Schema

### Database Overview

The WebGNIS Users database is a MySQL database that stores user information for the WebGNIS portal. The database is designed to support multiple user types, including individual users, company users, and administrators.

The database uses a normalized structure with separate tables for different types of users, allowing for efficient storage and retrieval of user information.

### Tables Structure

#### `users` Table

The `users` table is the main table that stores common information for all user types.

| Column                | Type                             | Description                                           |
|-----------------------|----------------------------------|-------------------------------------------------------|
| user_id               | INT AUTO_INCREMENT PRIMARY KEY   | Unique identifier for the user                        |
| username              | VARCHAR(50) NOT NULL UNIQUE      | Username for login                                    |
| password              | VARCHAR(255) NOT NULL            | Hashed password                                       |
| email                 | VARCHAR(100) NOT NULL UNIQUE     | User's email address                                  |
| contact_number        | VARCHAR(20)                      | User's contact number                                 |
| user_type             | ENUM('individual', 'company', 'admin') NOT NULL | Type of user account                   |
| sex_id                | INT                              | Foreign key to sexes table                            |
| name_on_certificate   | VARCHAR(100)                     | Name to appear on certificates                        |
| created_at            | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Account creation timestamp                         |
| updated_at            | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last update timestamp |
| is_active             | BOOLEAN DEFAULT TRUE             | Whether the account is active                         |
| last_login            | TIMESTAMP NULL                   | Timestamp of the last login                           |

#### `company_details` Table

The `company_details` table stores additional information for company users.

| Column                   | Type                           | Description                                         |
|--------------------------|--------------------------------|-----------------------------------------------------|
| company_id               | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier for the company details           |
| user_id                  | INT NOT NULL UNIQUE            | Foreign key to users table                          |
| company_name             | VARCHAR(100) NOT NULL          | Name of the company                                 |
| company_address          | TEXT NOT NULL                  | Address of the company                              |
| sector_id                | INT NOT NULL                   | Foreign key to sectors table                        |
| authorized_representative| VARCHAR(100) NOT NULL          | Name of the authorized representative               |

#### `individual_details` Table

The `individual_details` table stores additional information for individual users.

| Column                | Type                           | Description                                     |
|-----------------------|--------------------------------|-------------------------------------------------|
| individual_id         | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier for the individual details    |
| user_id               | INT NOT NULL UNIQUE            | Foreign key to users table                      |
| full_name             | VARCHAR(100) NOT NULL          | Full name of the individual                     |
| address               | TEXT NOT NULL                  | Address of the individual                       |

#### `sexes` Table

The `sexes` table stores valid options for sex/gender.

| Column                | Type                           | Description                                     |
|-----------------------|--------------------------------|-------------------------------------------------|
| id                    | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier for the sex                   |
| sex_name              | VARCHAR(50) NOT NULL           | Name of the sex (e.g., Male, Female)            |

#### `sectors` Table

The `sectors` table stores valid options for company sectors.

| Column                | Type                           | Description                                     |
|-----------------------|--------------------------------|-------------------------------------------------|
| id                    | INT AUTO_INCREMENT PRIMARY KEY | Unique identifier for the sector                |
| sector_name           | VARCHAR(100) NOT NULL          | Name of the sector                              |

### Indexes

The database includes the following indexes for improved performance:

- `users`: Indexes on `username`, `email`, and `user_type`
- `company_details`: Indexes on `user_id` and `sector_id`
- `individual_details`: Index on `user_id`

### Relationships

- A user can be of type 'individual', 'company', or 'admin'
- Each user of type 'company' has exactly one record in the `company_details` table
- Each user of type 'individual' has exactly one record in the `individual_details` table
- Foreign key constraints ensure referential integrity between tables

## API Reference

The WebGNIS Users API is a RESTful API that provides endpoints for managing users, companies, and individuals. The API uses JSON for data exchange and JWT for authentication.

### Base URL

The base URL for all API endpoints is `/users_api.php`.

### Authentication

#### Login

Authenticates a user and returns a JWT token.

- **URL**: `/login`
- **Method**: `POST`
- **Auth required**: No
- **Request body**:
  ```json
  {
    "username": "string",
    "password": "string"
  }
  ```
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Login successful",
      "data": {
        "user_id": "integer",
        "username": "string",
        "user_type": "string",
        "token": "string"
      }
    }
    ```
- **Error Responses**:
  - **Code**: 400
    - **Content**: `{ "status": 400, "message": "Username and password required" }`
  - **Code**: 401
    - **Content**: `{ "status": 401, "message": "Invalid credentials" }`

### Users Endpoints

#### Get All Users

Retrieves a list of all users.

- **URL**: `/users`
- **Method**: `GET`
- **Auth required**: Yes
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Users retrieved successfully",
      "data": [
        {
          "user_id": "integer",
          "username": "string",
          "email": "string",
          "contact_number": "string",
          "user_type": "string",
          "name_on_certificate": "string",
          "created_at": "string",
          "is_active": "boolean",
          "sex_name": "string"
        }
      ]
    }
    ```
- **Error Responses**:
  - **Code**: 401
    - **Content**: `{ "status": 401, "message": "Unauthorized" }`
  - **Code**: 404
    - **Content**: `{ "status": 404, "message": "No users found" }`

#### Get User by ID

Retrieves a specific user by ID.

- **URL**: `/users/{id}`
- **Method**: `GET`
- **Auth required**: Yes
- **URL Parameters**: `id=[integer]` where `id` is the user ID
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "User retrieved successfully",
      "data": {
        "user_id": "integer",
        "username": "string",
        "email": "string",
        "contact_number": "string",
        "user_type": "string",
        "name_on_certificate": "string",
        "created_at": "string",
        "is_active": "boolean",
        "sex_name": "string",
        "company_details": {
          // Only for company users
        },
        "individual_details": {
          // Only for individual users
        }
      }
    }
    ```
- **Error Responses**:
  - **Code**: 401
    - **Content**: `{ "status": 401, "message": "Unauthorized" }`
  - **Code**: 404
    - **Content**: `{ "status": 404, "message": "User not found" }`

#### Create User

Creates a new user.

- **URL**: `/users`
- **Method**: `POST`
- **Auth required**: No
- **Request body**:
  ```json
  {
    "username": "string",
    "password": "string",
    "email": "string",
    "contact_number": "string",
    "user_type": "string",
    "sex_id": "integer",
    "name_on_certificate": "string",
    "is_active": "boolean",
    // For company users
    "company_name": "string",
    "company_address": "string",
    "sector_id": "integer",
    "authorized_representative": "string",
    // For individual users
    "full_name": "string",
    "address": "string"
  }
  ```
- **Success Response**:
  - **Code**: 201
  - **Content**:
    ```json
    {
      "status": 201,
      "message": "User created successfully",
      "data": {
        "user_id": "integer"
      }
    }
    ```
- **Error Responses**:
  - **Code**: 400
    - **Content**: `{ "status": 400, "message": "Missing required fields" }`
  - **Code**: 409
    - **Content**: `{ "status": 409, "message": "Username or email already exists" }`

#### Update User

Updates an existing user.

- **URL**: `/users/{id}`
- **Method**: `PUT`
- **Auth required**: Yes
- **URL Parameters**: `id=[integer]` where `id` is the user ID
- **Request body**:
  ```json
  {
    "email": "string",
    "contact_number": "string",
    "sex_id": "integer",
    "name_on_certificate": "string",
    "is_active": "boolean",
    "password": "string",
    "company_details": {
      "company_name": "string",
      "company_address": "string",
      "sector_id": "integer",
      "authorized_representative": "string"
    },
    "individual_details": {
      "full_name": "string",
      "address": "string"
    }
  }
  ```
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "User updated successfully"
    }
    ```
- **Error Responses**:
  - **Code**: 400
    - **Content**: `{ "status": 400, "message": "No data provided" }`
  - **Code**: 401
    - **Content**: `{ "status": 401, "message": "Unauthorized" }`
  - **Code**: 404
    - **Content**: `{ "status": 404, "message": "User not found" }`

#### Delete User

Deletes a user.

- **URL**: `/users/{id}`
- **Method**: `DELETE`
- **Auth required**: Yes (admin only)
- **URL Parameters**: `id=[integer]` where `id` is the user ID
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "User deleted successfully"
    }
    ```
- **Error Responses**:
  - **Code**: 401
    - **Content**: `{ "status": 401, "message": "Unauthorized" }`
  - **Code**: 403
    - **Content**: `{ "status": 403, "message": "Insufficient permissions" }`
  - **Code**: 404
    - **Content**: `{ "status": 404, "message": "User not found" }`

### Company Endpoints

#### Get All Companies

Retrieves a list of all company users.

- **URL**: `/company`
- **Method**: `GET`
- **Auth required**: Yes
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Companies retrieved successfully",
      "data": [
        {
          "user_id": "integer",
          "username": "string",
          "email": "string",
          "contact_number": "string",
          "sex_name": "string",
          "name_on_certificate": "string",
          "company_name": "string",
          "company_address": "string",
          "sector_name": "string",
          "authorized_representative": "string"
        }
      ]
    }
    ```

#### Get Company by ID

Retrieves a specific company user by ID.

- **URL**: `/company/{id}`
- **Method**: `GET`
- **Auth required**: Yes
- **URL Parameters**: `id=[integer]` where `id` is the user ID
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Company retrieved successfully",
      "data": {
        "user_id": "integer",
        "username": "string",
        "email": "string",
        "contact_number": "string",
        "sex_name": "string",
        "name_on_certificate": "string",
        "company_name": "string",
        "company_address": "string",
        "sector_name": "string",
        "authorized_representative": "string"
      }
    }
    ```

### Individual Endpoints

#### Get All Individuals

Retrieves a list of all individual users.

- **URL**: `/individual`
- **Method**: `GET`
- **Auth required**: Yes
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Individuals retrieved successfully",
      "data": [
        {
          "user_id": "integer",
          "username": "string",
          "email": "string",
          "contact_number": "string",
          "sex_name": "string",
          "name_on_certificate": "string",
          "full_name": "string",
          "address": "string"
        }
      ]
    }
    ```

#### Get Individual by ID

Retrieves a specific individual user by ID.

- **URL**: `/individual/{id}`
- **Method**: `GET`
- **Auth required**: Yes
- **URL Parameters**: `id=[integer]` where `id` is the user ID
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Individual retrieved successfully",
      "data": {
        "user_id": "integer",
        "username": "string",
        "email": "string",
        "contact_number": "string",
        "sex_name": "string",
        "name_on_certificate": "string",
        "full_name": "string",
        "address": "string"
      }
    }
    ```

### Reference Data Endpoints

#### Get Sectors

Retrieves a list of all available sectors.

- **URL**: `/sectors`
- **Method**: `GET`
- **Auth required**: No
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Sectors retrieved successfully",
      "data": [
        {
          "id": "integer",
          "sector_name": "string"
        }
      ]
    }
    ```

#### Get Sexes

Retrieves a list of all available sex options.

- **URL**: `/sexes`
- **Method**: `GET`
- **Auth required**: No
- **Success Response**:
  - **Code**: 200
  - **Content**:
    ```json
    {
      "status": 200,
      "message": "Sexes retrieved successfully",
      "data": [
        {
          "id": "integer",
          "sex_name": "string"
        }
      ]
    }
    ```

## Client Usage

### JavaScript API Client

The WebGNIS Users API can be accessed using the provided JavaScript API client (`users.js`). The client provides methods for all API endpoints and handles authentication automatically.

#### Initialization

```javascript
const usersApi = new UsersAPI('/users_api.php');
```

#### Authentication

```javascript
// Login
const loginResult = await usersApi.login('username', 'password');

// Check if authenticated
const isAuthenticated = usersApi.isAuthenticated();

// Logout
usersApi.logout();
```

#### User Management

```javascript
// Get all users
const users = await usersApi.getAllUsers();

// Get user by ID
const user = await usersApi.getUserById(1);

// Create user
const userData = {
    username: 'newuser',
    password: 'password123',
    email: 'newuser@example.com',
    contact_number: '09123456789',
    user_type: 'individual',
    sex_id: 1,
    name_on_certificate: 'John Doe',
    full_name: 'John Doe',
    address: '123 Main St'
};
const createResult = await usersApi.createUser(userData);

// Update user
const updateData = {
    contact_number: '09987654321',
    individual_details: {
        address: '456 New St'
    }
};
const updateResult = await usersApi.updateUser(1, updateData);

// Delete user
const deleteResult = await usersApi.deleteUser(1);
```

#### Company and Individual Methods

```javascript
// Get all companies
const companies = await usersApi.getAllCompanies();

// Get company by ID
const company = await usersApi.getCompanyById(1);

// Get all individuals
const individuals = await usersApi.getAllIndividuals();

// Get individual by ID
const individual = await usersApi.getIndividualById(1);
```

#### Reference Data

```javascript
// Get sectors
const sectors = await usersApi.getSectors();

// Get sexes
const sexes = await usersApi.getSexes();
```

### Example Usage

Here's a complete example of user registration and login:

```javascript
// Initialize API client
const usersApi = new UsersAPI('/users_api.php');

// Register a new user
async function registerUser() {
    const userData = {
        username: 'newcompany',
        password: 'securepass',
        email: 'company@example.com',
        contact_number: '09123456789',
        user_type: 'company',
        sex_id: 1,
        name_on_certificate: 'XYZ Corporation',
        company_name: 'XYZ Corporation',
        company_address: '123 Business Ave',
        sector_id: 5, // Private (Company)
        authorized_representative: 'Jane Doe'
    };
    
    try {
        const result = await usersApi.createUser(userData);
        console.log('User registered successfully:', result);
        return result.data.user_id;
    } catch (error) {
        console.error('Registration failed:', error);
        return null;
    }
}

// Login with the new user
async function loginUser(username, password) {
    try {
        const result = await usersApi.login(username, 'securepass');
        console.log('Login successful:', result);
        
        // Get user details after login
        const userDetails = await usersApi.getUserById(result.data.user_id);
        console.log('User details:', userDetails);
        
        return userDetails;
    } catch (error) {
        console.error('Login failed:', error);
        return null;
    }
}

// Example workflow
async function userWorkflow() {
    // Register a new company
    const userId = await registerUser();
    
    if (userId) {
        // Login with the new user
        const userDetails = await loginUser('newcompany', 'securepass');
        
        if (userDetails) {
            // Update company details
            const updateData = {
                company_details: {
                    company_address: '456 Corporate Blvd'
                }
            };
            
            try {
                const updateResult = await usersApi.updateUser(userId, updateData);
                console.log('User updated:', updateResult);
                
                // Get updated user details
                const updatedUser = await usersApi.getUserById(userId);
                console.log('Updated user details:', updatedUser);
            } catch (error) {
                console.error('Update failed:', error);
            }
        }
    }
} 