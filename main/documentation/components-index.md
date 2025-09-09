## Components Index (WebGNIS)

### Config and Core
- `main/config.php`: App constants, mysqli DB (stations), response helper, JWT constants.
- `main/users_config.php`: PDO DB (users/tickets), helpers for users-related APIs.
- `main/config-api.php`: Public JSON config (prices, upload limits, base_url).

### Public and Admin APIs (PHP)
- `main/api.php`: Public API router
  - `/api/search/stations` → filtered, paginated stations from `*_stations_new`
  - `/api/provinces`, `/api/locations`, `/api/locations?view=tree&type?`
- `main/stations-api.php`: Public stations data endpoint by type (vertical|horizontal|gravity); returns full table rows.
- `main/gcp_admin_api.php`: Admin CRUD and lookups for stations (HGCP/VGCP/GRAV) via mysqli (see README/API docs).
- `main/stations_viewer_api.php`: Admin/mod import/export and bulk table operations; logs to `webgnis_users.station_activity_log`.
- `main/users_api.php`: Authentication and users service (JWT), users/company/individual/sectors/sexes, change_password.
- `main/cart_api.php`: Cart operations (add/remove/list/clear/count/sync) supporting guest `session_id` and JWT users.
- `main/requests_api.php`: Request lifecycle (create, list, view, update-status, statuses), auto-expiry, transaction seed creation.
- `main/transactions_api.php`: Payment methods, submit, upload-proof, view/list/update/verify (integrates with certificates).
- `main/certificates_api.php`: Generate/download/upload processed certificates (uses `certificate_generator.php`).
- `main/gcp_admin_api.php`: Admin endpoints `/api/admin/...` for stations and locations.

### Auth
- JWT HMAC-SHA256 using `JWT_SECRET` from `config.php`.
- Tokens are base64-based (not URL-safe); verification aligns with that across APIs.
- Clients send `Authorization: Bearer <token>`.

### Frontend (selected)
- Pages: `main/index.php` (Explorer), `main/home.php`, `main/admin.php`, `main/requests_management.php`, `main/stations_viewer.php`, `main/account.php`.
- JS modules (`main/js/`):
  - `users/` auth modules (token storage in `localStorage` as `webgnis_token`), API client wrappers.
  - `cart-api.js`, `cart.js` (cart UI + API integration).
  - `payment.js` (requests + transactions orchestration; reads `config-api.php`).
  - `map.js`, `stations.js`, `search.js` (Explorer map, filters, UI binding).
  - `admin.js`, `requests_management.js` (admin/mod tooling calling admin and management APIs).

### Utilities
- `main/gcs_helper.php`: Uploads to GCS bucket or local fallback under `assets/payment_proofs`.
- `main/certificate_generator.php`: PDF generation for certificates; used by transactions/certificates APIs.

### Data and Assets
- `main/assets/sqls/`: SQL helpers and schema snippets; `assets/data/` for CSV inputs.
- `main/assets/processed_certs`, `preprocessed_certs`, `qrcodes`, `payment_proofs` directories.
- `main/assets/Provinces.json` for location data.

### Logging and Errors
- Central log file: `main/php_errors.log`.
- Many APIs `error_log()` key actions; `stations_viewer_api.php` logs station updates to DB log table.

### Notable Tables (by usage)
- Users-side (PDO): `users`, `company_details`, `individual_details`, `sexes`, `sectors`, `carts`, `cart_items`, `requests`, `request_items`, `request_statuses`, `transactions`, `payment_methods`, `certificates`.
- Stations-side (mysqli): `hgcp_stations_new`, `vgcp_stations_new`, `grav_stations_new`; admin uses aliases/views `hgcp_stations`, `vgcp_stations`, `grav_stations`.

### Cross-file Conventions
- Responses: `{ status, message, data? }` from helpers.
- CORS headers and OPTIONS handling present across APIs.
- Owner-or-admin checks on protected resources.

### See Also
- `main/documentation/API.md` for detailed endpoints.
- `main/documentation/ARCHITECTURE.md` for system overview.
- `main/documentation/code-map.md` for a deeper map of flows.
