# Meeting Accounts

Meeting accounts store Zoom OAuth credentials used to automatically create Zoom meetings for events. Credentials (`client_id`, `client_secret`) are encrypted at rest using Laravel's `encrypted` cast (AES-256-CBC keyed by `APP_KEY`). They are **never** returned in API responses.

---

## List All Meeting Accounts

- **URL**: `/api/admin/meeting-accounts`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Meeting accounts retrieved successfully",
    "count": 2,
    "accounts": [
      {
        "id": 1,
        "name": "Main Zoom Account",
        "provider": "zoom",
        "account_id": "abc123xyz",
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-03-09T10:00:00.000000Z",
        "updated_at": "2026-03-09T10:00:00.000000Z",
        "creator": { "id": 1, "username": "admin", "email": "admin@example.com" }
      }
    ]
  }
  ```

---

## Create Meeting Account

- **URL**: `/api/admin/meeting-accounts`
- **Method**: `POST`
- **Auth Required**: Yes
- **Body**:
  ```json
  {
    "name": "Main Zoom Account",
    "provider": "zoom",
    "account_id": "abc123xyz",
    "client_id": "your_client_id",
    "client_secret": "your_client_secret",
    "is_active": true
  }
  ```
- **Validation**:
  - `name`: required, string, max 255
  - `provider`: required, must be `"zoom"`
  - `account_id`: required, string, max 255
  - `client_id`: required, string, max 255
  - `client_secret`: required, string, max 255
  - `is_active`: optional, boolean
- **Success Response** `201`:
  ```json
  {
    "message": "Meeting account created successfully",
    "account": { "id": 1, "name": "Main Zoom Account", ... }
  }
  ```

---

## Get Meeting Account

- **URL**: `/api/admin/meeting-accounts/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Meeting account retrieved successfully",
    "account": { "id": 1, "name": "Main Zoom Account", "provider": "zoom", ... }
  }
  ```
- **Note**: `client_id` and `client_secret` are **never** included in responses.

---

## Update Meeting Account

- **URL**: `/api/admin/meeting-accounts/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Body** (all fields optional):
  ```json
  {
    "name": "Updated Name",
    "is_active": false
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Meeting account updated successfully",
    "account": { ... }
  }
  ```

---

## Delete Meeting Account

- **URL**: `/api/admin/meeting-accounts/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  { "message": "Meeting account deleted successfully" }
  ```

---

## Test Credentials

Verifies that the stored credentials can obtain a Zoom access token.

- **URL**: `/api/admin/meeting-accounts/{id}/test`
- **Method**: `POST`
- **Auth Required**: Yes
- **Success Response** `200`:
  ```json
  { "message": "Credentials are valid — access token obtained successfully" }
  ```
- **Error Response** `502`:
  ```json
  { "message": "Credentials test failed: ..." }
  ```

---

## Create Zoom Meeting for an Event

See [02-events.md](02-events.md) — **Create Zoom Meeting** section.
