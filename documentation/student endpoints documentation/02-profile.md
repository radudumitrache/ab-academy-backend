# Profile

## Get Profile

`GET /api/student/profile`

Returns the authenticated student's profile.

**Response** `200`:
```json
{
  "message": "Profile retrieved successfully",
  "profile": {
    "id": 12,
    "username": "john_doe",
    "email": "john@example.com",
    "telephone": "+40712345678",
    "address": null,
    "street": "Main St",
    "house_number": "12A",
    "city": "Bucharest",
    "county": "Ilfov",
    "country": "Romania",
    "occupation": "Student",
    "role": "student"
  }
}
```

---

## Update Profile

`PUT /api/student/profile`

All fields are optional. Send only the fields you want to change.

| Field | Type | Notes |
|-------|------|-------|
| `username` | string | Must be unique |
| `email` | string | Must be unique |
| `telephone` | string | Max 20 chars |
| `address` | string | General address / PO box |
| `street` | string | Street name — used as billing address on invoices |
| `house_number` | string | House / apartment number — used on invoices |
| `city` | string | Used as billing city on invoices |
| `county` | string | Used as billing region/county on invoices |
| `country` | string | Used as billing country on invoices |
| `occupation` | string | |

> **Invoicing note** — `street`, `house_number`, `city`, `county`, `country`, `telephone`, and `email` are passed to EuPlatesc as billing details when a payment is initiated. Keep them accurate before paying an invoice.

**Response** `200`:
```json
{
  "message": "Profile updated successfully",
  "profile": {
    "id": 12,
    "username": "john_doe",
    "email": "john@example.com",
    "telephone": "+40712345678",
    "address": null,
    "street": "Main St",
    "house_number": "12A",
    "city": "Bucharest",
    "county": "Ilfov",
    "country": "Romania",
    "occupation": "Student"
  }
}
```

---

## Change Password

`POST /api/student/profile/change-password`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `current_password` | string | Yes | Must match existing password |
| `new_password` | string | Yes | Min 6 characters |

**Response** `200`:
```json
{ "message": "Password changed successfully" }
```

**Errors**: `422` if `current_password` is incorrect.
