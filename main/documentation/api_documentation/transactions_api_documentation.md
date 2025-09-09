# Transactions API Documentation (transactions_api.php)

## Overview
Handles payment operations and verification for requests.

- Base: `/transactions_api.php?action=`
- Auth: JWT Bearer (admin for admin actions)

## Endpoints

### Get Payment Methods
- Path: `payment-methods`
- Method: GET
- Response: `{ status, message, data: [ { id, name } ] }`

### Submit Payment (create transaction)
- Path: `submit`
- Method: POST
- Headers: `Authorization: Bearer <token>`
- Body:
```json
{ "request_id": 123, "payment_method": "LinkBiz", "paid_amount": 1000 }
```
- Response: `{ status, message, data: { transaction_id, request_id, status } }`

### Upload Payment Proof
- Path: `upload-proof`
- Method: POST (multipart/form-data)
- Headers: `Authorization: Bearer <token>`
- Fields: `request_id`, `paid_amount`, `payment_method`, `reference_number`, `proof_file`
- Response: `{ status, message, data: { transaction_id } }`

### View Transaction
- Path: `view/{id}` or `view?id=`
- Method: GET
- Headers: `Authorization: Bearer <token>`
- Response: transaction + request summary

### List Transactions
- Path: `list`
- Method: GET
- Headers: `Authorization: Bearer <token>` (admin for all)
- Query: `request`, `user`

### Update Transaction (admin)
- Path: `update/{id}`
- Method: POST
- Headers: `Authorization: Bearer <token>` (admin)
- Body: arbitrary updatable fields; if status Approved, certificate is generated

### Verify (admin)
- Path: `verify`
- Method: PUT or POST
- Headers: `Authorization: Bearer <token>` (admin)
- Body:
```json
{ "transaction_id": 1, "verification_status": "verified|rejected", "notes": "optional" }
```

## Notes
- File uploads use `gcs_helper.php` with local fallback to `assets/payment_proofs`.
- Email notifications are sent on key status transitions when configured.
