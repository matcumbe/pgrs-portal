# users.js Documentation

## Overview
The `users.js` file is a JavaScript module that provides user management functionality for the WebGNIS application. It includes a class-based API client for interacting with the users API, as well as event handlers for user authentication, registration, and profile management.

## UsersAPI Class

### Constructor
```javascript
constructor(baseUrl = '')
```
- **Purpose**: Initializes the UsersAPI client
- **Parameters**: `baseUrl` - Base URL for API requests (default: empty string)
- **Functionality**: Sets up the base URL and retrieves authentication token from localStorage

### Authentication Methods

#### `setToken(token)`
- **Purpose**: Sets the authentication token
- **Parameters**: `token` - JWT token string
- **Functionality**: Stores token in memory and localStorage

#### `clearToken()`
- **Purpose**: Clears the authentication token
- **Functionality**: Removes token from memory and localStorage

#### `isAuthenticated()`
- **Purpose**: Checks if user is authenticated
- **Returns**: Boolean indicating authentication status

#### `request(endpoint, method = 'GET', data = null)`
- **Purpose**: Makes API requests with authentication
- **Parameters**:
  - `endpoint` - API endpoint to call
  - `method` - HTTP method (default: 'GET')
  - `data` - Request body for POST/PUT requests (default: null)
- **Returns**: Promise resolving to API response data
- **Error Handling**: Includes comprehensive error handling

#### `login(username, password)`
- **Purpose**: Authenticates user and obtains token
- **Parameters**:
  - `username` - User's username
  - `password` - User's password
- **Returns**: Promise resolving to login response
- **Functionality**: Stores token upon successful login

#### `logout()`
- **Purpose**: Logs out the user
- **Functionality**: Calls logout endpoint and clears token

### User Management Methods

#### `getAllUsers()`
- **Purpose**: Retrieves all users
- **Returns**: Promise resolving to users data

#### `getUserById(userId)`
- **Purpose**: Retrieves a specific user by ID
- **Parameters**: `userId` - ID of the user to retrieve
- **Returns**: Promise resolving to user data

#### `createUser(userData)`
- **Purpose**: Creates a new user
- **Parameters**: `userData` - Object containing user data
- **Returns**: Promise resolving to creation result

#### `updateUser(userId, userData)`
- **Purpose**: Updates an existing user
- **Parameters**:
  - `userId` - ID of the user to update
  - `userData` - Updated user data
- **Returns**: Promise resolving to update result

#### `deleteUser(userId)`
- **Purpose**: Deletes a user
- **Parameters**: `userId` - ID of the user to delete
- **Returns**: Promise resolving to deletion result

### Company Methods

#### `getAllCompanies()`
- **Purpose**: Retrieves all companies
- **Returns**: Promise resolving to companies data

#### `getCompanyById(companyId)`
- **Purpose**: Retrieves a specific company by ID
- **Parameters**: `companyId` - ID of the company to retrieve
- **Returns**: Promise resolving to company data

### Individual Methods

#### `getAllIndividuals()`
- **Purpose**: Retrieves all individuals
- **Returns**: Promise resolving to individuals data

#### `getIndividualById(individualId)`
- **Purpose**: Retrieves a specific individual by ID
- **Parameters**: `individualId` - ID of the individual to retrieve
- **Returns**: Promise resolving to individual data

### Reference Data Methods

#### `getSectors()`
- **Purpose**: Retrieves all sectors
- **Returns**: Promise resolving to sectors data

#### `getSexes()`
- **Purpose**: Retrieves all sexes
- **Returns**: Promise resolving to sexes data

#### `getCurrentUser()`
- **Purpose**: Retrieves the current user's profile
- **Returns**: Promise resolving to current user data
- **Functionality**: Returns null if not authenticated

#### `requestCertificate(pointIds, requestDetails)`
- **Purpose**: Requests a certificate for selected points
- **Parameters**:
  - `pointIds` - Array of point IDs
  - `requestDetails` - Object containing request details
- **Returns**: Promise resolving to certificate request result

## User Authentication Module

### DOM Elements
- `requestCertBtn` - Button to request certificate
- `userInfoSection` - Section displaying user information
- `userDisplayName` - Element displaying user's name
- `userType` - Element displaying user's account type
- `logoutBtn` - Button to log out
- `requestCertificateBtn` - Button to request certificate
- `loginForm` - Login form
- `registerForm` - Registration form
- `loginAlert` - Alert for login errors
- `registerAlert` - Alert for registration errors
- `individualType` - Radio button for individual account type
- `companyType` - Radio button for company account type
- `individualFields` - Fields specific to individual accounts
- `companyFields` - Fields specific to company accounts

### Loading State Management

#### `showLoading(form)`
- **Purpose**: Shows loading state for a form
- **Parameters**: `form` - Form element
- **Functionality**: Disables submit button and shows loading spinner

#### `hideLoading(form)`
- **Purpose**: Hides loading state for a form
- **Parameters**: `form` - Form element
- **Functionality**: Enables submit button and hides loading spinner

### Reference Data Loading

#### `loadReferenceData()`
- **Purpose**: Loads reference data for dropdowns
- **Functionality**: Fetches and populates sexes and sectors dropdowns
- **Error Handling**: Includes fallback to hardcoded values if API fails

### Authentication State Management

#### `checkLoginStatus()`
- **Purpose**: Checks if user is logged in
- **Functionality**: Verifies token and retrieves user data if authenticated
- **Error Handling**: Clears token and hides user info if authentication fails

#### `showUserInfo(userData)`
- **Purpose**: Displays user information
- **Parameters**: `userData` - User data object
- **Functionality**: Updates UI with user's name and account type

#### `hideUserInfo()`
- **Purpose**: Hides user information
- **Functionality**: Updates UI for logged out state

### Form Handlers

#### `handleLogin(event)`
- **Purpose**: Handles login form submission
- **Parameters**: `event` - Form submission event
- **Functionality**: Authenticates user and updates UI
- **Error Handling**: Displays error message if login fails

#### `handleRegister(event)`
- **Purpose**: Handles registration form submission
- **Parameters**: `event` - Form submission event
- **Functionality**: Creates new user account and logs in
- **Error Handling**: Displays error message if registration fails

#### `handleLogout()`
- **Purpose**: Handles logout
- **Functionality**: Logs out user and updates UI
- **Error Handling**: Logs error but proceeds with local logout

#### `handleCertificateRequest()`
- **Purpose**: Handles certificate request
- **Functionality**: Submits certificate request for selected points
- **Error Handling**: Displays error message if request fails

### UI Helpers

#### `toggleUserTypeFields()`
- **Purpose**: Toggles between individual and company fields
- **Functionality**: Shows/hides fields based on selected account type

#### `showAlert(alertElement, message)`
- **Purpose**: Shows alert message
- **Parameters**:
  - `alertElement` - Alert element to update
  - `message` - Message to display
- **Functionality**: Displays message and hides after 5 seconds

## Global Instance
- `usersApi` - Global instance of the UsersAPI class

## Event Listeners
- Login form submit event
- Register form submit event
- Logout button click event
- Request certificate button click event
- Individual/Company type radio button change events 