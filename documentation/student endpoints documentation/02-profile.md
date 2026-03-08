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
    "role": "student",
    "profile_picture_url": "https://storage.googleapis.com/...signed-url..."
  }
}
```

> `profile_picture_url` is a 60-minute signed GCS URL, or `null` if no picture has been uploaded.

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

---

## Setup Storage

`POST /api/student/profile/setup`

Creates the GCS folder structure for the student (`students/{username}/profile/`). Safe to call multiple times — skips folders that already exist. Should be called once after registration before uploading a profile picture.

**Response** `200`:
```json
{
  "message": "Storage setup completed",
  "username": "john_doe",
  "folders_created": [
    "students/john_doe/profile/.keep"
  ],
  "structure": [
    "students/john_doe/profile/"
  ]
}
```

> If the folder already exists, `folders_created` will be an empty array.

---

## Upload Profile Picture

`POST /api/student/profile/picture`

Upload or replace the student's profile picture. Send as `multipart/form-data`.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `image` | file | Yes | jpeg, jpg, png, or webp — max 5 MB |

Stored in GCS at `students/{username}/profile/profile_picture.{ext}`. The student's folder structure is created automatically on first upload. If a previous picture exists, it is deleted before the new one is stored.

**Response** `200`:
```json
{
  "message": "Profile picture uploaded successfully",
  "profile_picture_url": "https://storage.googleapis.com/...signed-url..."
}
```

**Errors**: `422` if the file is missing or fails validation.

---

## Get Profile Picture

`GET /api/student/profile/picture`

Returns a 60-minute signed download URL for the student's profile picture.

**Response** `200`:
```json
{
  "message": "Profile picture retrieved successfully",
  "profile_picture_url": "https://storage.googleapis.com/...signed-url..."
}
```

**Errors**: `404` if no profile picture has been uploaded.
