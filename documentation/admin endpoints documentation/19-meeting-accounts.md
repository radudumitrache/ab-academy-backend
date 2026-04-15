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

## Today's Meetings (All Accounts)

Returns today's scheduled meetings from the Zoom API for **every** meeting account, grouped by account. "Today" is the current calendar day in the requesting admin's effective timezone. Meetings are sorted in ascending start-time order per account.

If one account's Zoom API call fails, that account's entry will contain an `error` key and no `meetings` array — all other accounts are still returned.

- **URL**: `GET /api/admin/meeting-accounts/today-meetings`
- **Auth Required**: Yes
- **Query Parameters**: none

- **Success Response** `200`:
  ```json
  {
    "message": "Today's meetings retrieved successfully",
    "date": "2026-04-15",
    "timezone": "Europe/Bucharest",
    "account_count": 2,
    "suggested_account_now": {
      "id": 2,
      "name": "Backup Zoom Account"
    },
    "accounts": [
      {
        "account_id": 1,
        "account_name": "Main Zoom Account",
        "is_active": true,
        "meeting_count": 1,
        "meetings": [
          {
            "zoom_meeting_id": 12345678901,
            "topic": "Math Class",
            "start_time_utc": "2026-04-15 09:00:00",
            "start_date": "2026-04-15",
            "start_time": "12:00",
            "duration": 60,
            "join_url": "https://us05web.zoom.us/j/12345678?pwd=..."
          }
        ]
      },
      {
        "account_id": 2,
        "account_name": "Backup Zoom Account",
        "is_active": true,
        "meeting_count": 0,
        "meetings": []
      }
    ]
  }
  ```

  `suggested_account_now` — the first active account with no meeting overlapping the current moment (60-minute window). `null` when all accounts are busy or errored.

  If an account's Zoom API call fails entirely, its entry has an `error` key instead of `meetings`.

- **Errors**:
  - `401` — unauthenticated

---

## Suggest an Account for a Future Meeting

Returns which account would be automatically selected if a new meeting were scheduled at a given date and time. Uses the same first-available selection logic as the event Zoom meeting creator: iterates active accounts in database order and picks the first one with no overlapping scheduled meeting.

- **URL**: `GET /api/admin/meeting-accounts/suggest-account`
- **Auth Required**: Yes
- **Query Parameters**:

  | Param | Type | Required | Description |
  |-------|------|----------|-------------|
  | `date` | string (Y-m-d) | Yes | Meeting date, in the admin's timezone |
  | `time` | string (H:i) | Yes | Meeting start time, in the admin's timezone |
  | `duration` | integer (minutes) | No | Meeting length (default: 60) |

- **Success Response** `200` (account available):
  ```json
  {
    "message": "Account available for this time slot",
    "requested_start": "2026-04-20 14:00",
    "requested_end": "2026-04-20 15:00",
    "duration_minutes": 60,
    "timezone": "Europe/Bucharest",
    "suggested_account": {
      "id": 1,
      "name": "Main Zoom Account"
    },
    "busy_accounts": [],
    "error_accounts": []
  }
  ```

- **Success Response** `200` (all busy):
  ```json
  {
    "message": "No accounts available for this time slot",
    "requested_start": "2026-04-20 14:00",
    "requested_end": "2026-04-20 15:00",
    "duration_minutes": 60,
    "timezone": "Europe/Bucharest",
    "suggested_account": null,
    "busy_accounts": [
      {
        "id": 1,
        "name": "Main Zoom Account",
        "conflicting_topic": "Grammar Class",
        "conflicting_start": "2026-04-20T11:00:00Z"
      }
    ],
    "error_accounts": []
  }
  ```

  `busy_accounts` — active accounts that have an overlapping meeting at the requested time.
  `error_accounts` — active accounts whose Zoom API call failed (treated as unavailable).

- **Errors**:
  - `401` — unauthenticated
  - `422` — validation failed (missing/invalid date, time, or duration)

---

## Check Meetings on a Zoom Account

Queries the Zoom API directly to find any scheduled meetings on this account that overlap a given time window. This does **not** consult the local database — it hits the Zoom API and returns live data.

- **URL**: `GET /api/admin/meeting-accounts/{id}/check-meetings`
- **Auth Required**: Yes
- **Query Parameters**:

  | Param | Type | Required | Description |
  |-------|------|----------|-------------|
  | `date` | string (Y-m-d) | Yes | Date to check, in the admin's timezone |
  | `time` | string (H:i) | Yes | Time to check, in the admin's timezone |
  | `duration` | integer (minutes) | No | Length of the window to check for overlap (default: 60) |

- **Success Response** `200`:
  ```json
  {
    "message": "Meetings found in this time window",
    "account_id": 1,
    "account_name": "Main Zoom Account",
    "checked_from": "2026-04-10 14:00",
    "checked_until": "2026-04-10 15:00",
    "timezone": "Europe/Bucharest",
    "meeting_count": 1,
    "meetings": [
      {
        "zoom_meeting_id": 12345678901,
        "topic": "Math Class",
        "start_time": "2026-04-10T11:00:00Z",
        "duration": 60,
        "join_url": "https://us05web.zoom.us/j/12345678?pwd=..."
      }
    ]
  }
  ```
  When no meetings overlap, `message` is `"No meetings found in this time window"` and `meetings` is an empty array.

- **Errors**:
  - `404` — meeting account not found
  - `422` — validation failed (missing/invalid date, time, or duration)
  - `502` — Zoom API call failed (message included)

---

## Create Zoom Meeting for an Event

See [02-events.md](02-events.md) — **Create Zoom Meeting** section.
