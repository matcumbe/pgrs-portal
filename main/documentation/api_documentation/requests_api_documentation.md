# Requests API Documentation (requests_api.php)

## Overview
Manages the request lifecycle for selected stations (create, list, view, update status, enumerate statuses).

- Base: `/requests_api.php?action=`
- Auth: JWT Bearer for all protected endpoints
- DB: `requests`, `request_items`, `request_statuses`

## Endpoints

### Create Request
- Path: `create`
- Method: POST
- Headers: `Authorization: Bearer <token>`
- Body:
```json
{
  "items": [
    {"station_id":"string","station_name":"string","station_type":"vertical|horizontal|gravity"}
  ],
  "clear_cart": true
}
```
- Response:
```json
{ "status": 201, "message": "Request created", "data": {"request_id": 123, "total_amount": 0} }
```

### List Requests
- Path: `list`
- Method: GET
- Headers: `Authorization: Bearer <token>`
- Query (optional): `status`, `user`
- Response: `{ status, message, data: [ { request_id, request_date, status, item_count, total_amount } ] }`

### View Request
- Path: `view/{id}` or `view?id=123` or `view&reference=ABC`
- Method: GET
- Headers: `Authorization: Bearer <token>`
- Response: request core fields, items, and user summary.

### Update Status (admin)
- Path: `update-status`
- Method: PUT or POST
- Headers: `Authorization: Bearer <token>` (admin)
- Body:
```json
{ "request_id": 123, "status": "processing", "remarks": "optional" }
```
- Response: `{ status, message }`

### Statuses
- Path: `statuses`
- Method: GET
- Response:
```json
{ "status": 200, "message": "OK", "data": [
  "pending","awaiting_payment","payment_uploaded","verified","processing","completed","rejected"
] }
```

## Notes
- Prices come from constants in `config.php` when created via cart flow.
- Owner-or-admin checks enforced across view/list.
