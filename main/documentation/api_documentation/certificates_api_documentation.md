# Certificates API Documentation (certificates_api.php)

## Overview
Generate, download, and upload processed certificates linked to transactions.

- Base: `/certificates_api.php?action=`
- Auth: JWT Bearer (owner or admin)

## Endpoints

### Generate (admin)
- Path: `generate`
- Method: POST
- Headers: `Authorization: Bearer <token>` (admin)
- Body: `{ "transaction_code": "string" }`
- Response: `{ status, message, data: { certificate_id } }`

### Download
- Path: `download`
- Method: GET
- Headers: `Authorization: Bearer <token>`
- Query: `transaction_code`
- Response: file stream (processed or preprocessed)

### Upload Processed (admin)
- Path: `upload_processed`
- Method: POST (multipart/form-data)
- Headers: `Authorization: Bearer <token>` (admin)
- Fields: `transaction_code`, `processed_certificate_file`
- Response: `{ status, message }`

## Notes
- Uses `certificate_generator.php` under the hood; files stored under `assets/processed_certs` or `assets/preprocessed_certs`.
