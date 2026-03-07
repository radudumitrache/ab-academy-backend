# Authentication

## Register

`POST /api/student/register`

Creates a new student account. No authentication required.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `username` | string | Yes | Must be unique |
| `email` | string | Yes | Must be a valid, unique email |
| `telephone` | string | No | Max 20 characters |
| `password` | string | Yes | Min 6 characters |

**Response** `201`:
```json
{
  "message": "Registration successful",
  "user": {
    "id": 12,
    "username": "john_doe",
    "email": "john@example.com",
    "role": "student"
  },
  "access_token": "eyJ0eXAiOiJKV1Q...",
  "token_type": "Bearer"
}
```

**Errors**: `422` if username or email already taken.

---

## Login

`POST /api/student/login`

| Field | Type | Required |
|-------|------|----------|
| `username` | string | Yes |
| `password` | string | Yes |

**Response** `200`:
```json
{
  "message": "Student login successful",
  "user": {
    "id": 12,
    "username": "john_doe",
    "role": "student"
  },
  "access_token": "eyJ0eXAiOiJKV1Q...",
  "token_type": "Bearer"
}
```

**Errors**: `401` if credentials are incorrect.

---

## Logout

`POST /api/student/logout` — requires `Authorization: Bearer {token}`

Revokes the current access token.

**Response** `200`:
```json
{ "message": "Student logout successful" }
```
