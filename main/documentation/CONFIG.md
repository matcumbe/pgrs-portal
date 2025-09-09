# Configuration

## PHP Config Files
- `main/config.php`: Stations DB (mysqli), constants (JWT_SECRET, TOKEN_EXPIRY, prices, upload limits, BASE_URL), helpers (`returnResponse`).
- `main/users_config.php`: Users/tickets DB (PDO) and helpers (JWT generation/verification for users API and others).
- `main/config-api.php`: Public JSON config consumed by frontend (prices, upload limits, base_url).

## Environment/Constants
- Database hosts, names, and credentials are defined in the PHP config files.
- JWT: HMAC-SHA256 with shared secret (`JWT_SECRET`). Tokens are base64 (not URL-safe) consistently across APIs.
- Upload paths:
  - `main/assets/payment_proofs`
  - `main/assets/processed_certs`, `main/assets/preprocessed_certs`

## Frontend Keys
- Map and other keys (if any) are read from `config-api.php` on the client.

## Permissions
- Ensure webserver can write to `main/assets/*` directories listed above.
