## WebGNIS Code Map (main/)

### Core Config and Conventions
- **Config**: `config.php` (stations DB via mysqli) and `users_config.php` (users DB via PDO)
  - DB: `connectDB()` returns mysqli (config.php) or PDO (users_config.php)
  - Constants: `JWT_SECRET`, `TOKEN_EXPIRY`, station pricing, upload limits, BASE_URL
  - Responses: `returnResponse(status, message, data?, code?)` in `config.php` (JSON: {status, message, data?})
  - CORS/Headers: Set per API, OPTIONS preflight supported
- **Auth/JWT**: Simple HMAC-SHA256 JWT signed with `JWT_SECRET`
  - `users_api.php`: `generateJWT(user)`, `verifyToken(requiredRole?, exitOnFail=true)`
  - Other APIs re-implement compatible `verifyToken`
- **Error logging**: `php_errors.log`; APIs use try/catch and http_response_code

### Public API Routers
- `api.php` (GET only)
  - `/api/search/stations?type={horizontal|vertical|gravity}&filters...` → stations list (mysqli)
  - `/api/provinces` → distinct provinces
  - `/api/locations?province=...&city_or_municipality?` → cities/barangays
  - `/api/locations?view=tree&type?` → hierarchical locations
- `stations-api.php` (GET)
  - `/api/stations/{vertical|horizontal|gravity}` via `path` query; returns table content

### Users API (users_api.php; action=query param)
- Headers: JSON, CORS, methods: POST, GET, PUT, DELETE
- Endpoints (action):
  - `login` [POST]: {username,password} → {token, user}
  - `logout` [POST]
  - `users` [GET]: list (auth); [GET users/{id}]; [GET users/me]; [POST] create; [PUT users/{id}] update; [DELETE users/{id}] delete
  - `company` [GET], `company/{id}` [GET]
  - `individual` [GET], `individual/{id}` [GET]
  - `sectors` [GET]
  - `sexes` [GET]
  - `change_password` [POST] current/new password (auth)
  - `certificates/request` [POST] mock submit
- Auth: Bearer token in `Authorization`
- DB: PDO (`users_config.php`)

### Cart API (cart_api.php; action=query param)
- Endpoints:
  - `add` [POST] {station_id, station_type, station_name?, session_id}
  - `remove` [DELETE|POST] {item_id? or station_id+station_type, session_id?}
  - `list` [GET] (auth or session_id)
  - `clear` [DELETE|POST]
  - `count` [GET] (auth or session_id)
  - `sync` [POST] {session_id} (auth)
- Auth optional; supports guest via `session_id`
- DB: carts, cart_items

### Requests API (requests_api.php; action=query param)
- Auto-expiry updater `checkExpiredRequests`
- Endpoints (auth unless noted):
  - `create` [POST] {items:[{station_id,station_name,station_type}], clear_cart?} → creates request, items, transaction seed
  - `list` [GET]: all (admin), by `user` (auth owner/admin), by `status`
  - `view/{id}` or `view?id=` or `view&reference=` [GET]: request details + items + user (auth owner/admin)
  - `update-status` [PUT|POST admin]: status change (+ optional remarks) with email notify
  - `statuses` [GET]: list statuses
- Pricing via constants (`PRICE_*`)

### Transactions API (transactions_api.php; action=query param)
- Endpoints:
  - `payment-methods` [GET]
  - `submit` [POST auth]: create payment transaction; updates request status
  - `upload-proof` [POST auth Multipart]: {request_id, paid_amount, payment_method, reference_number, proof_file}
  - `view/{id}` or `view?id=` [GET auth owner/admin]
  - `list` [GET]: all (admin) | by `request` | by `user` (owner/admin)
  - `update/{id}` [POST admin]: update fields; if status Approved → generate certificate and record
  - `verify` [PUT|POST admin]: mark verified and set request Pending; email notify
- File uploads: `gcs_helper.php` to GCS or local `assets/payment_proofs`

### Certificates API (certificates_api.php; action=query param, auth required)
- `generate` [POST admin]: {transaction_code} → generate via `certificate_generator.php`, upsert `certificates`
- `download` [GET auth owner/admin]: {transaction_code} → serves processed or preprocessed file
- `upload_processed` [POST admin Multipart]: {transaction_code, processed_certificate_file}

### Admin API (gcp_admin_api.php; path=query param)
- CRUD and lookups for stations using mysqli (tables: vgcp_stations, hgcp_stations, grav_stations)
- Endpoints (path):
  - GET `/api/admin/stations/{type}`
  - GET `/api/admin/station/{id}`
  - POST `/api/admin/station`
  - PUT `/api/admin/station/{id}`
  - DELETE `/api/admin/station/{id}`
  - GET `/api/admin/regions|provinces|cities|barangays` (optional filters via query)

### Client-Side Usage (JS)
- `users.js` + `js/users/*`
  - `js/users/api-client.js`: wraps `users_api.php` with `?action=...` endpoints; stores token in `localStorage(webgnis_token)`
  - `auth.js` manages auth state/UI; `user-ui.js` handles forms
- `cart-api.js`: wraps `cart_api.php` with `?action=...`; manages guest `session_id`, listens to auth events for sync
- `payment.js`: orchestrates request creation (`requests_api.php?action=create`), payment proof upload to `transactions_api.php?action=upload-proof`, reads config from `config-api.php`, payment methods, and uses station price constants
- `admin.js`: uses `gcp_admin_api.php` with `path=/api/admin/...` for admin CRUD and lookups
- `stations.js/map.js/search.js`: integrate with public APIs for data and UI

### Utilities
- `gcs_helper.php`: `uploadToGCS(bucket, tmp_path, dest)` with local fallback at `assets/payment_proofs` and certs helper used by certificates API
- `certificate_generator.php`: generates PDFs (FPDF/FPDI), integrates station and requesting party data; used by Transactions/Certificates API

### Response Shapes
- Success: `{ status: 'success', message: string, data?: any }` or `{ statusCode:int, message, data }` in some scripts; client code handles both
- Error: HTTP 4xx/5xx with `{ status: 'error', message }`

### Auth Patterns
- Bearer token in `Authorization`
- Owner-or-admin checks across requests/transactions/certificates

### Notable Tables (by usage)
- users, company_details, individual_details, sexes, sectors
- carts, cart_items
- requests, request_items, request_statuses
- transactions, payment_methods
- certificates
- hgcp_stations_new/vgcp_stations_new/grav_stations_new (public) and hgcp_stations/vgcp_stations/grav_stations (admin)

### Logs and Debug
- Central: `php_errors.log`
- Many APIs write detailed `error_log()` entries for tracing

### Notes
- Two DB configs: stations/public APIs use `config.php` (mysqli); users/transactions use `users_config.php` (PDO)
- JWT generation uses base64, not base64url; verification implementations match this
- File uploads rely on env `GCS_BUCKET_NAME`; fallback to local paths when credentials missing

