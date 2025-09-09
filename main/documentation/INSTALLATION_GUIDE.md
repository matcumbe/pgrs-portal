# WebGNIS Installation Guide (PHP/MySQL)

## System Requirements

- CPU: Dual-core 2.0 GHz+
- RAM: 4 GB+ (8 GB recommended)
- Storage: 10 GB+
- OS: Linux (Ubuntu 20.04+), Windows 10/11, or macOS 11+
- Web Server: Apache or Nginx
- PHP: 7.4+ with mysqli and PDO MySQL extensions
- MySQL: 5.7+/8.0+

## Prerequisites

- Apache or Nginx configured to serve `main/` as document root
- PHP configured and enabled for the webserver
- MySQL server with credentials

## Database Setup

Create the databases/tables per `documentation/DATABASE.md` (stations DB and users/requests DB). Ensure users DB contains:
- `users`, `company_details`, `individual_details`, `sectors`, `sexes`
- `carts`, `cart_items`, `requests`, `request_items`, `request_statuses`
- `transactions`, `payment_methods`, `certificates`

Create stations tables:
- `vgcp_stations[_new]`, `hgcp_stations[_new]`, `grav_stations[_new]`

## Configuration

Edit PHP config files:
- `main/config.php` (stations DB, constants)
- `main/users_config.php` (users/requests DB, JWT)
- `main/config-api.php` (public config consumed by frontend)

Set at minimum:
- DB host, name, user, password (both files)
- `JWT_SECRET`
- Pricing and upload limits
- `BASE_URL` for public links

## File Permissions

Grant write access to the webserver user for:
- `main/assets/payment_proofs`
- `main/assets/processed_certs`
- `main/assets/preprocessed_certs`
- `main/php_errors.log`

## Running Locally

1. Point your web server vhost to `.../pgrs/main/`
2. Restart web server
3. Open the site in browser (e.g., http://localhost)
4. Verify public explorer loads and admin login works

## Verifying Installation

- Call `GET main/config-api.php` and confirm JSON loads
- Test public API (e.g., `api.php?path=/api/regions`)
- Login via `users_api.php?action=login` and test a protected route
- Create a request via `requests_api.php?action=create`
- Upload proof via `transactions_api.php?action=upload-proof`

## Troubleshooting

- Check `main/php_errors.log` for application errors
- Verify DB connectivity (credentials and host)
- Ensure PHP extensions enabled (mysqli, pdo_mysql)
- Confirm writable permissions for `assets/*` and log file
- Inspect network requests in browser devtools

## Backup and Restore

- MySQL backup: `mysqldump -u <user> -p <db> > backup.sql`
- Restore: `mysql -u <user> -p <db> < backup.sql`
- Backup uploaded files: archive `main/assets/`

## Security Notes

- Serve over HTTPS
- Keep `JWT_SECRET` private
- Validate upload size/types; enforce limits in PHP config and code
- Limit CORS to known origins in production 