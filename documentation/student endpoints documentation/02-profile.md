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

All fields are optional. `username` cannot be changed.

| Field | Type | Notes |
|-------|------|-------|
| `email` | string | Must be unique |
| `telephone` | string | Max 20 characters |
| `address` | string | |
| `street` | string | |
| `house_number` | string | |
| `city` | string | |
| `county` | string | |
| `country` | string | |
| `occupation` | string | |

**Response** `200` with updated profile object.

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
