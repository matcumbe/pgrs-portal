# WebGNIS User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
   - [System Requirements](#system-requirements)
   - [Accessing WebGNIS](#accessing-webgnis)
   - [User Interface Overview](#user-interface-overview)
3. [Explorer Interface](#explorer-interface)
   - [Map Navigation](#map-navigation)
   - [Searching for Stations](#searching-for-stations)
   - [Viewing Station Details](#viewing-station-details)
   - [Exporting Data](#exporting-data)
4. [Tracker Interface](#tracker-interface)
   - [Tracking Changes](#tracking-changes)
   - [Generating Reports](#generating-reports)
5. [Data Request System](#data-request-system)
   - [Selecting Points of Interest](#selecting-points-of-interest)
   - [Creating a Data Request](#creating-a-data-request)
   - [Making Payment](#making-payment)
   - [Uploading Payment Proof](#uploading-payment-proof)
   - [Tracking Request Status](#tracking-request-status)
6. [Admin Panel](#admin-panel)
   - [User Management](#user-management)
   - [Station Management](#station-management)
   - [Adding a New Station](#adding-a-new-station)
   - [Editing Station Information](#editing-station-information)
   - [Deleting Stations](#deleting-stations)
   - [Managing Data Requests](#managing-data-requests)
   - [Verifying Payments](#verifying-payments)
7. [Account Management](#account-management)
   - [Updating Your Profile](#updating-your-profile)
   - [Changing Your Password](#changing-your-password)
8. [Frequently Asked Questions](#frequently-asked-questions)
9. [Troubleshooting](#troubleshooting)
10. [Glossary](#glossary)

## Introduction

WebGNIS (Web-based Geodetic Network Information System) is a comprehensive platform for managing, exploring, and monitoring geodetic control points and stations. This user guide provides detailed instructions on how to use the various features of the WebGNIS system.

The system is designed to help surveyors, GIS professionals, and administrators manage geodetic network information efficiently through an intuitive web interface. WebGNIS supports various types of stations including:

- Vertical control stations (benchmarks)
- Horizontal control stations (triangulation points)
- Gravity stations
- Combined stations with multiple measurement types

## Getting Started

### System Requirements

To access and use WebGNIS, you need:

- A modern web browser (Google Chrome, Mozilla Firefox, Microsoft Edge, or Safari)
- Internet connection
- Screen resolution of at least 1280 x 800
- JavaScript enabled in your browser

### Accessing WebGNIS

1. Open your web browser
2. Navigate to the WebGNIS URL provided by your administrator (typically https://webgnis.yourdomain.com)
3. You will be directed to the WebGNIS home page

### User Interface Overview

The WebGNIS interface consists of several main sections:

- **Navigation Bar**: Located at the top of every page, providing access to all major sections
- **Explorer**: The main map interface for viewing and searching stations
- **Tracker**: For monitoring changes and generating reports
- **Admin Panel**: For administrative tasks (requires admin privileges)
- **User Profile**: For managing your account settings

## Explorer Interface

The Explorer is the primary interface for viewing and searching geodetic stations on an interactive map.

### Map Navigation

1. **Zoom Controls**:
   - Use the `+` and `-` buttons in the top-left corner of the map
   - Use your mouse scroll wheel to zoom in and out
   - Double-click to zoom in on a specific location

2. **Pan Controls**:
   - Click and drag the map to pan in any direction
   - Use the arrow keys on your keyboard for precise movements

3. **Base Map Selection**:
   - Click the layers icon in the top-right corner of the map
   - Select from available base maps (Topographic, Satellite, Street)

4. **Legend**:
   - Click the legend icon to view the map legend
   - Different station types are represented by unique symbols

### Searching for Stations

1. **Quick Search**:
   - Enter a station ID, name, or coordinates in the search box
   - Press Enter or click the search icon
   - Matching stations will be highlighted on the map

2. **Advanced Search**:
   - Click the "Advanced Search" button below the search box
   - Specify search criteria:
     - Station Type (Vertical, Horizontal, Gravity)
     - Administrative Region
     - Date Range
     - Accuracy Class
     - Mark Type
   - Click "Search" to execute the query

3. **Spatial Search**:
   - Click the "Draw" button in the map toolbar
   - Select a drawing tool (rectangle, circle, polygon)
   - Draw the area of interest on the map
   - Click "Search in Area" to find stations within the drawn area

### Viewing Station Details

1. **Selecting a Station**:
   - Click on a station marker on the map
   - A popup will display basic information about the station
   - Click "View Details" for complete information

2. **Station Information Panel**:
   - The panel shows all available information organized in tabs:
     - **General**: ID, name, type, status, and location
     - **Vertical**: Elevation, datum, adjustment details (for vertical stations)
     - **Horizontal**: Coordinates, datum, adjustment details (for horizontal stations)
     - **Gravity**: Gravity values and related information (for gravity stations)
     - **History**: Record of changes made to the station
     - **Documents**: Access to attached documents and photos

3. **Measurement History**:
   - Navigate to the "History" tab in the station details panel
   - View a chronological list of measurements taken at the station
   - Toggle between tabular and graph views using the buttons at the top

### Exporting Data

1. **Export Search Results**:
   - After performing a search, click the "Export" button
   - Select the export format (CSV, Excel, PDF, GeoJSON)
   - Choose which fields to include in the export
   - Click "Download" to generate and download the file

2. **Export Station Details**:
   - While viewing station details, click the "Export" button
   - Select the export format
   - Choose to export only the current station or include nearby stations
   - Click "Download" to generate and download the file

3. **Batch Export**:
   - From the advanced search results, select multiple stations using checkboxes
   - Click "Export Selected" to export only selected stations
   - Follow the export format selection process

## Tracker Interface

The Tracker interface allows you to monitor changes made to the geodetic network and generate reports based on this information.

### Tracking Changes

1. **Viewing Recent Changes**:
   - Navigate to the Tracker interface from the navigation bar
   - The dashboard displays recent changes by default
   - Each entry shows:
     - Date and time of the change
     - User who made the change
     - Type of change (Add, Edit, Delete)
     - Affected station(s)

2. **Filtering Changes**:
   - Use the filter panel on the left side to narrow down results:
     - By date range
     - By user
     - By action type
     - By station type
     - By administrative region
   - Click "Apply Filters" to update the displayed changes

3. **Change Details**:
   - Click on any change entry to view detailed information
   - The details panel shows:
     - Before and after values for edited fields
     - Complete details for added or deleted stations
     - Comments provided by the user who made the change

### Generating Reports

1. **Standard Reports**:
   - Click the "Reports" tab in the Tracker interface
   - Select from available report templates:
     - Monthly Activity Summary
     - User Activity Report
     - Station Type Distribution
     - Data Quality Report

2. **Custom Reports**:
   - Click "Create Custom Report"
   - Select data fields to include in the report
   - Specify filtering criteria
   - Choose grouping and sorting options
   - Select the output format (PDF, Excel, HTML)

3. **Scheduled Reports**:
   - Click "Schedule Report" when creating or viewing a report
   - Set the frequency (daily, weekly, monthly)
   - Specify delivery method (email, download link)
   - Click "Save Schedule" to automate the report generation

## Data Request System

The WebGNIS Data Request System allows you to request access to specific geodetic control points for your projects. This section guides you through the process of selecting points, creating a request, making payment, and tracking your request status.

### Selecting Points of Interest

1. **Navigate to the Explorer Interface**:
   - Access the map interface where you can view all geodetic control points
   - Use the search and filter tools to find points relevant to your project

2. **Select Points for Request**:
   - Click on a station marker to view its details
   - In the station details panel, click "Add to Request" to select this point
   - The point will be added to your selected points list in the sidebar
   - A counter will show how many points you've selected

3. **Review Selected Points**:
   - Open the "Selected Points" panel by clicking on the counter or the "Selected Points" button
   - Review the list of points you've selected
   - You can remove individual points by clicking the "Remove" button next to each point
   - To clear all selections, click "Clear All"

### Creating a Data Request

1. **Initiate Request Creation**:
   - Once you've selected all the points you need, click "Create Request" in the Selected Points panel
   - You must be logged in to create a request; if not logged in, you will be prompted to do so

2. **Complete Request Form**:
   - Enter the purpose of your request (e.g., "Survey for Municipal Project")
   - Review the list of selected points and their associated costs
   - The system will calculate and display the total cost of your request
   - Read and accept the terms and conditions

3. **Submit Request**:
   - Click "Submit Request" to create your data access ticket
   - The system will generate a unique ticket ID for your request
   - You will receive an email confirmation with your request details
   - You will be redirected to the payment page

### Making Payment

1. **Review Payment Information**:
   - On the payment page, verify your request details
   - Note the total amount due
   - Read the payment instructions carefully

2. **Process Payment via LinkBiz**:
   - The system will display the necessary information for processing payment via LinkBiz
   - Note the merchant account information and the amount
   - Access the LinkBiz portal or mobile app
   - Complete the payment transaction
   - Save the reference number provided by LinkBiz
   - Take a screenshot or save the payment receipt

### Uploading Payment Proof

1. **Return to the Payment Page**:
   - After completing your payment, return to the WebGNIS payment page
   - If you closed the page, you can access it again by going to "My Requests" in your user menu

2. **Upload Payment Evidence**:
   - Enter the reference number from your LinkBiz transaction
   - Click "Choose File" to select the screenshot or receipt image
   - The accepted file formats are JPG, PNG, and PDF, with a maximum size of 2MB
   - Optionally, add notes regarding your payment
   - Click "Submit Payment Proof"

3. **Confirmation**:
   - The system will confirm receipt of your payment proof
   - The status of your request will change to "Payment Uploaded"
   - You'll receive an email confirmation of the payment upload

### Tracking Request Status

1. **Access My Requests**:
   - Click on your username in the top navigation bar
   - Select "My Requests" from the dropdown menu
   - This will display a list of all your submitted requests

2. **Request Status Information**:
   - Each request will show:
     - Ticket ID
     - Submission date
     - Number of points requested
     - Total amount
     - Current status
     - Last update date

3. **Understanding Status Codes**:
   - **Pending**: Initial status when request is first created
   - **Awaiting Payment**: Request created but payment not yet uploaded
   - **Payment Uploaded**: Payment proof submitted, awaiting verification
   - **Verified**: Payment has been verified by an administrator
   - **Processing**: Request is being processed and data is being prepared
   - **Completed**: Request has been fulfilled and data is ready for download
   - **Rejected**: Request was rejected (check notes for reason)

4. **Viewing Request Details**:
   - Click on any request to view its complete details
   - The details page shows:
     - All requested points with coordinates and types
     - Payment information and verification status
     - History of status changes
     - Administrator notes
     - Download links (if status is "Completed")

5. **Downloading Data**:
   - When a request reaches "Completed" status, download links will appear
   - Click "Download Data" to receive your requested geodetic control point information
   - The data will be provided in standardized formats with complete metadata

## Admin Panel

The Admin Panel provides tools for managing users, stations, and system settings. This section is only accessible to users with administrative privileges.

### User Management

1. **Viewing Users**:
   - Navigate to the Admin Panel from the navigation bar
   - Click the "Users" tab
   - View a list of all users with basic information

2. **Adding a New User**:
   - Click the "Add User" button
   - Fill in the required information:
     - Username
     - Email address
     - Full name
     - Role (User, Manager, Administrator)
     - Department/Organization
   - Click "Create User" to add the new account
   - A temporary password will be generated and sent to the user's email

3. **Editing User Information**:
   - In the users list, click the "Edit" button for the desired user
   - Update the user information as needed
   - Click "Save Changes" to apply the updates

4. **Deactivating a User**:
   - In the users list, click the "Deactivate" button for the desired user
   - Confirm the action in the dialog box
   - The user account will be deactivated but not deleted

### Station Management

1. **Browsing Stations**:
   - In the Admin Panel, click the "Stations" tab
   - View a paginated list of all stations
   - Use the search box to find specific stations
   - Use the filter options to narrow down the list

2. **Station List Features**:
   - Sort columns by clicking on column headers
   - Adjust columns visibility using the "Columns" button
   - Export the current view using the "Export" button
   - Click on a station row to view its detailed information

### Adding a New Station

1. **Initiate Station Creation**:
   - In the Stations tab of the Admin Panel, click "Add Station"
   - Select the station type (Vertical, Horizontal, Gravity, or Combined)

2. **Fill in Station Details**:
   - **Common Information**:
     - Station ID (must be unique)
     - Station name
     - Description
     - Status (Active, Inactive, Destroyed)
     - Mark type
     - Installation date
   
   - **Location Information**:
     - Enter coordinates manually or click "Pick on Map" to select a location
     - Specify the coordinate system and datum
     - Enter address information (if applicable)
     - Select administrative region
   
   - **Type-Specific Information**:
     - For Vertical stations: elevation, vertical datum, accuracy class
     - For Horizontal stations: latitude/longitude, horizontal datum, accuracy class
     - For Gravity stations: gravity value, reference system, instrument
     - For Combined stations: information for all applicable types

3. **Upload Supporting Documents**:
   - Click "Add Document" in the Documents section
   - Select the document type (Photo, Field Notes, Recovery Form)
   - Choose the file to upload
   - Add a description for the document
   - Click "Upload" to attach the document

4. **Save the Station**:
   - Review all entered information
   - Click "Save Station" to create the new record
   - The system will validate the information and display any validation errors
   - Once successfully saved, you will be redirected to the station details page

### Editing Station Information

1. **Access Station Edit Form**:
   - From the stations list, click the "Edit" button for the desired station
   - Alternatively, while viewing station details, click "Edit Station"

2. **Make Necessary Changes**:
   - Update the station information as needed
   - Fields are organized in tabs similar to the station creation form
   - Required fields are marked with an asterisk (*)

3. **Add Change Notes**:
   - In the "Notes" field at the bottom of the form, describe the changes being made
   - This information will be recorded in the station history

4. **Save Changes**:
   - Click "Save Changes" to update the station record
   - The system will validate the information and display any validation errors
   - Once successfully saved, you will be redirected to the station details page

### Deleting Stations

1. **Delete a Station**:
   - From the stations list, click the "Delete" button for the desired station
   - Alternatively, while viewing or editing station details, click "Delete Station"
   - A confirmation dialog will appear

2. **Confirm Deletion**:
   - Enter your reason for deleting the station
   - Type "DELETE" in the confirmation field
   - Click "Confirm Delete" to proceed

3. **Records and History**:
   - Deleted stations are not permanently removed from the database
   - They are marked as deleted and no longer appear in regular searches
   - Administrators can view deleted stations by enabling the "Show Deleted" filter

### Managing Data Requests

1. **Access Data Request Management**:
   - Navigate to the Admin Panel from the navigation bar
   - Click the "Data Requests" tab
   - View a list of all submitted data requests

2. **Request Details**:
   - Click on any request to view its complete details
   - The details page shows:
     - Ticket ID
     - Submission date
     - Number of points requested
     - Total amount
     - Current status
     - Last update date

3. **Verifying Payments**:
   - Click the "Verify Payment" button for a request to verify the payment
   - Enter the payment verification details
   - Click "Confirm Verification" to complete the process

## Account Management

### Updating Your Profile

1. **Access Your Profile**:
   - Click on your username in the top-right corner of any page
   - Select "My Profile" from the dropdown menu

2. **Edit Profile Information**:
   - Click the "Edit Profile" button
   - Update your information:
     - Full name
     - Email address
     - Department/Organization
     - Contact phone number
     - Preferred language
   - Click "Save Changes" to update your profile

3. **Profile Picture**:
   - In the profile page, hover over your profile picture
   - Click "Change Picture"
   - Select a new image file (JPG, PNG, or GIF format)
   - Crop and resize the image as needed
   - Click "Upload" to set your new profile picture

### Changing Your Password

1. **Access Password Settings**:
   - Click on your username in the top-right corner of any page
   - Select "Change Password" from the dropdown menu

2. **Update Password**:
   - Enter your current password
   - Enter and confirm your new password
   - Passwords must be at least 8 characters long and include:
     - At least one uppercase letter
     - At least one lowercase letter
     - At least one number
     - At least one special character
   - Click "Change Password" to save the new password

## Frequently Asked Questions

**Q: How do I reset my password if I forget it?**
A: On the login page, click the "Forgot Password" link and follow the instructions to reset your password. A reset link will be sent to your registered email address.

**Q: Can I access WebGNIS on my mobile device?**
A: Yes, WebGNIS is responsive and works on mobile devices. However, for complex operations like adding or editing stations, a desktop or laptop computer is recommended.

**Q: How do I export data in a specific coordinate system?**
A: When exporting data, click the "Advanced Options" button in the export dialog. There you can select your desired coordinate system and datum for the exported data.

**Q: Can I upload multiple documents at once?**
A: Yes, when adding documents to a station, you can select multiple files at once in the file selection dialog by holding the Ctrl key (or Command key on Mac) while selecting files.

**Q: How can I report a problem with a station record?**
A: While viewing a station's details, click the "Report Issue" button in the top-right corner of the page. Fill out the issue report form with details about the problem.

## Troubleshooting

### Map Display Issues

**Problem**: Map is not loading or displaying properly.
**Solution**:
- Check your internet connection
- Clear your browser cache
- Ensure JavaScript is enabled in your browser
- Try using a different browser

### Search Problems

**Problem**: Search results are not showing expected stations.
**Solution**:
- Check your search criteria for typos
- Try using fewer filters at once
- Ensure your search area is correct if using spatial search
- Contact an administrator if you believe the station should be in the database

### Export Failures

**Problem**: Unable to export data or download files.
**Solution**:
- Check if your browser is blocking downloads
- Ensure you have selected at least one station to export
- Try a different export format
- For large datasets, try exporting in smaller batches

### Login Issues

**Problem**: Unable to log in to your account.
**Solution**:
- Verify your username and password
- Check if Caps Lock is enabled
- Clear browser cookies
- Use the "Forgot Password" feature if necessary
- Contact your administrator if the problem persists

## Glossary

**Benchmark**: A permanently marked point of known elevation.

**Control Point**: A point on the earth with known coordinates, used as a reference for surveys.

**Datum**: A reference system for geodetic measurements, defining the origin and orientation of coordinate systems.

**Ellipsoidal Height**: Height above or below a reference ellipsoid, typically used for GPS measurements.

**Gravity Station**: A location where the Earth's gravitational acceleration has been precisely measured.

**Horizontal Control**: A survey control point with known horizontal coordinates (latitude and longitude).

**ITRF**: International Terrestrial Reference Frame, a precise global reference frame for positions on Earth.

**Mark Type**: The physical form of a survey marker (e.g., brass disk, concrete monument, steel rod).

**Orthometric Height**: Height above the geoid, approximately mean sea level.

**Vertical Control**: A survey control point with known elevation.

---

*For additional assistance, please contact support@webgnis.org*

*Last updated: April 25, 2025*