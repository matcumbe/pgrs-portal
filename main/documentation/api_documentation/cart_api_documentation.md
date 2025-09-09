# Cart API Documentation (cart_api.php)

## Overview
Guest cart for selected stations with optional auth sync.

- Base: `/cart_api.php?action=`
- Auth: optional (`session_id` supports guests); Bearer for user carts

## Endpoints

### Add
- Path: `add`
- Method: POST
- Body:
```json
{ "station_id":"string", "station_type":"vertical|horizontal|gravity", "station_name":"optional", "session_id":"optional" }
```

### Remove
- Path: `remove`
- Method: DELETE or POST
- Body: `{ item_id }` or `{ station_id, station_type }` (+ `session_id` if guest)

### List
- Path: `list`
- Method: GET
- Query: `session_id` (guest)
- Headers: `Authorization: Bearer <token>` (user)

### Clear
- Path: `clear`
- Method: DELETE or POST

### Count
- Path: `count`
- Method: GET

### Sync (guest → user)
- Path: `sync`
- Method: POST
- Body: `{ session_id }` (requires Bearer token)

## Notes
- Price computation occurs later during request creation.
