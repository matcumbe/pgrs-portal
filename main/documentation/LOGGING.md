# Logging and Diagnostics

## PHP Error Log
- Central log file: `main/php_errors.log`.
- APIs use `error_log()` for key actions and errors.

## Typical Entries
- Request routing errors (unknown `action`/`path`)
- Auth failures (invalid/expired tokens)
- DB exceptions (connection/query errors)
- File upload issues (size/type/path)

## Where to Look
- `main/php_errors.log` for application issues
- Webserver error logs (Apache/Nginx) for runtime/PHP-FPM issues

## Recommendations
- Rotate logs in production.
- Sanitize sensitive data in log statements.
- Include correlation IDs in responses and logs if feasible.
