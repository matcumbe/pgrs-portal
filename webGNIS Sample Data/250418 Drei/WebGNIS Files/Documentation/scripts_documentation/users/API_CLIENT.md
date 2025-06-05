# API Client Documentation

## Overview
The `api-client.js` module provides a comprehensive client-side interface for interacting with the WebGNIS Users API. It handles authentication, user management, and various API operations.

## Class: UsersAPI

### Constructor
```javascript
constructor(baseUrl = '')
```
- **Parameters:**
  - `baseUrl` (string): Base URL for API endpoints. Defaults to empty string.

### Authentication Methods

#### setToken(token)
Sets the authentication token and stores it in localStorage.
- **Parameters:**
  - `token` (string): JWT token for authentication

#### clearToken()
Clears the authentication token from memory and localStorage.

#### isAuthenticated()
Checks if a user is currently authenticated.
- **Returns:** (boolean) True if authenticated, false otherwise

### Core API Methods

#### request(endpoint, method, data)
Internal method for making API requests.
- **Parameters:**
  - `endpoint` (string): API endpoint
  - `method` (string): HTTP method (GET, POST, PUT, DELETE)
  - `data` (object): Request payload for POST/PUT requests
- **Returns:** (Promise) API response

### Authentication Operations

#### login(username, password)
Authenticates a user and stores the token.
- **Parameters:**
  - `username` (string): User's username
  - `password` (string): User's password
- **Returns:** (Promise) Login response with token

#### logout()
Logs out the current user and clears the token.
- **Returns:** (Promise) Logout response

### User Management

#### getAllUsers()
Retrieves all users.
- **Returns:** (Promise) List of users

#### getUserById(userId)
Retrieves a specific user by ID.
- **Parameters:**
  - `userId` (number): User ID
- **Returns:** (Promise) User details

#### createUser(userData)
Creates a new user.
- **Parameters:**
  - `userData` (object): User information
- **Returns:** (Promise) Created user details

#### updateUser(userId, userData)
Updates an existing user.
- **Parameters:**
  - `userId` (number): User ID
  - `userData` (object): Updated user information
- **Returns:** (Promise) Updated user details

#### deleteUser(userId)
Deletes a user.
- **Parameters:**
  - `userId` (number): User ID
- **Returns:** (Promise) Deletion response

### Company Operations

#### getAllCompanies()
Retrieves all companies.
- **Returns:** (Promise) List of companies

#### getCompanyById(companyId)
Retrieves a specific company by ID.
- **Parameters:**
  - `companyId` (number): Company ID
- **Returns:** (Promise) Company details

### Individual Operations

#### getAllIndividuals()
Retrieves all individual users.
- **Returns:** (Promise) List of individuals

#### getIndividualById(individualId)
Retrieves a specific individual by ID.
- **Parameters:**
  - `individualId` (number): Individual ID
- **Returns:** (Promise) Individual details

### Reference Data

#### getSectors()
Retrieves all business sectors.
- **Returns:** (Promise) List of sectors

#### getSexes()
Retrieves all sex options.
- **Returns:** (Promise) List of sex options

### Profile Operations

#### getCurrentUser()
Retrieves the current user's profile.
- **Returns:** (Promise) Current user details

### Certificate Operations

#### requestCertificate(pointIds, requestDetails)
Requests certificates for specific points.
- **Parameters:**
  - `pointIds` (array): Array of point IDs
  - `requestDetails` (object): Certificate request details
- **Returns:** (Promise) Certificate request response

## Global Instance
The module exports a pre-configured instance of the API client:
```javascript
const usersApi = new UsersAPI('users_api.php');
```

## Usage Example
```javascript
import { usersApi } from './api-client.js';

// Login
await usersApi.login('username', 'password');

// Get current user
const user = await usersApi.getCurrentUser();

// Request certificate
await usersApi.requestCertificate([1, 2, 3], {
    purpose: 'Survey',
    additionalNotes: 'Required for project documentation'
});
``` 