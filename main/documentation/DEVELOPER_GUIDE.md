# WebGNIS Developer Guide (PHP)

## Table of Contents

1. Introduction
2. Project Structure (main/)
3. Tech Stack
4. Local Development Setup
5. Databases and Tables
6. APIs and Routing
7. Frontend Overview
8. Authentication and Authorization
9. Coding Standards and Conventions
10. Debugging and Logs
11. Deployment Notes

## Introduction

This guide describes the WebGNIS application as implemented in this repository: a PHP/MySQL web app that exposes multiple PHP endpoint files and a browser-based frontend for public exploration and administrative management of geodetic control points.

## Project Structure (main/)

```
main/
  api.php                   # Public API (stations/locations/radius-search)
  stations-api.php          # Public stations by type
  gcp_admin_api.php         # Admin CRUD for stations and locations
  users_api.php             # Users: auth, users/companies/individuals, reference data
  cart_api.php              # Guest/user cart for selected stations
  requests_api.php          # Request lifecycle (create/list/view/update-status)
  transactions_api.php      # Payments: methods/submit/upload-proof/view/list/verify
  certificates_api.php      # Certificates: generate/download/upload_processed
  stations_viewer_api.php   # Admin/mod ops (import/export, bulk ops)

  config.php                # Stations DB (mysqli), constants, helpers
  users_config.php          # Users/requests DB (PDO), JWT utilities
  config-api.php            # Public JSON config (prices, upload limits, base URL)

  certificate_generator.php # PDF certificate generation (FPDF/FPDI)
  gcs_helper.php            # Upload to GCS or local fallback

  assets/
    payment_proofs/
    processed_certs/
    preprocessed_certs/
    qrcodes/
    Provinces.json

  php_errors.log            # Central PHP error log

  index.php, home.php, admin.php, requests_management.php, stations_viewer.php, account.php
  js/ (map/search/stations/admin/payment/cart users/*)
```

See also:
- `documentation/code-map.md` for a deeper mapping of flows
- `documentation/API.md` (index) and `documentation/api_documentation/*` for endpoint details

## Tech Stack

- Backend: PHP 7.4+ (mysqli + PDO), FPDF/FPDI for PDF
- Databases: MySQL (separate logical DBs for stations and users/requests)
- Auth: JWT (HMAC-SHA256) with shared secret; Bearer tokens in `Authorization`
- Frontend: HTML, Bootstrap 5, JavaScript (ES6), Leaflet 1.9.x

## Local Development Setup

- PHP with webserver (Apache/Nginx) and MySQL
- Configure virtual host to serve `main/` as document root
- Update DB credentials and constants in `main/config.php` and `main/users_config.php`
- Ensure write permissions for:
  - `main/assets/payment_proofs`
  - `main/assets/processed_certs`
  - `main/assets/preprocessed_certs`
  - `main/php_errors.log`

Quick checklist:
- Confirm mysqli and PDO MySQL extensions enabled
- Set `JWT_SECRET` in `config.php`/`users_config.php`
- Verify `config-api.php` values used by the frontend (prices, upload limits)

## Databases and Tables

Stations-side (read/admin on stations DB):
- `vgcp_stations[_new]`, `hgcp_stations[_new]`, `grav_stations[_new]`

Users/Requests side (users DB via PDO):
- `users`, `company_details`, `individual_details`, `sectors`, `sexes`
- `carts`, `cart_items`
- `requests`, `request_items`, `request_statuses`
- `transactions`, `payment_methods`
- `certificates`

See `documentation/DATABASE.md` for schemas and notes (accuracy class, ITRF fields, etc.).

## APIs and Routing

- Public: `api.php`, `stations-api.php`
- Admin: `gcp_admin_api.php`
- Users: `users_api.php?action=...`
- Cart: `cart_api.php?action=...`
- Requests: `requests_api.php?action=...`
- Transactions: `transactions_api.php?action=...`
- Certificates: `certificates_api.php?action=...`

Request format:
- Query parameter `action` (or `path` for some) with REST-like segments
- JSON body for POST/PUT; CORS and OPTIONS preflight supported

Standard responses:
- `{ status: number|string, message: string, data?: any }`

See `documentation/api_documentation/*` for endpoint specifics and examples.

## Frontend Overview

- Explorer map (Leaflet) with filters and results table
- Client-side quick filtering in results table
- Admin panel with tabbed station form (visibility toggled by station type)
- DMS/decimal conversion utilities; dynamic accuracy-class dropdown entries

Key JS modules under `main/js/`:
- `map.js`, `stations.js`, `search.js`
- `admin.js`, `requests_management.js`
- `cart-api.js`, `cart.js`, `payment.js`
- `users/` (auth state, API client)

## Authentication and Authorization

- JWT Bearer tokens required for protected endpoints
- Owner-or-admin checks for user-owned resources (requests, transactions, certificates)
- Admin endpoints in `gcp_admin_api.php` and admin actions in transactions/requests/certificates

## Coding Standards and Conventions

- PHP: 
  - Prefer prepared statements (mysqli/PDO) for all queries
  - Early returns for error handling; do not suppress errors
  - Use centralized `returnResponse()`/helpers for JSON responses
  - Keep response shapes consistent across APIs
- JavaScript:
  - ES6 modules; avoid global state except where legacy requires
  - Debounce frequent UI handlers; avoid unnecessary DOM thrash
- Naming:
  - Descriptive function/variable names; avoid abbreviations
  - Extract complex conditions into named variables

## Debugging and Logs

- Central log: `main/php_errors.log`
- Most APIs call `error_log()` on key actions and failures
- Use browser devtools for frontend; network tab to inspect API calls

See `documentation/LOGGING.md` for more.

## Deployment Notes

- Ensure `assets/*` writable by webserver
- Configure proper PHP timeouts and upload limits (for receipt/certificate files)
- Serve over HTTPS; set appropriate CORS in production
- Enable log rotation for `php_errors.log` 