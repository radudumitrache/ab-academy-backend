# Teacher Profile

Teachers can view and update their own account details, upload a profile picture, and change their password.

---

## Get Profile

- **URL**: `GET /api/teacher/profile`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Profile retrieved successfully",
    "profile": {
      "id": 4,
      "username": "teacher1",
      "email": "teacher1@example.com",
      "telephone": "+31 6 12345678",
      "address": "Main Street 1",
      "street": "Main Street",
      "house_number": "1",
      "city": "Amsterdam",
      "county": "Noord-Holland",
      "country": "Netherlands",
      "occupation": "Mathematics Teacher",
      "languages_taught": ["English", "Dutch"],
      "timezone": "Europe/Bucharest",
      "role": "teacher",
      "profile_picture_url": "https://storage.googleapis.com/..."
    }
  }
  ```
- **Notes**: `profile_picture_url` is a signed GCS URL valid for 60 minutes. It is `null` if no picture has been uploaded.

---

## Update Profile

- **URL**: `PUT /api/teacher/profile`
- **Auth Required**: Yes
- **Body** (all fields optional — send only those to change):
  ```json
  {
    "username": "new_username",
    "email": "new@example.com",
    "telephone": "+31 6 99999999",
    "address": "New Street 5",
    "street": "New Street",
    "house_number": "5",
    "city": "Rotterdam",
    "county": "Zuid-Holland",
    "country": "Netherlands",
    "occupation": "Physics Teacher",
    "languages_taught": ["English", "Romanian"],
    "timezone": "Europe/Bucharest"
  }
  ```
- **Validation**:
  - `username`: unique across all users (excluding self)
  - `email`: valid email, unique across all users (excluding self)
  - `telephone`: max 20 chars
  - `address`, `street`, `occupation`: max 255 chars
  - `house_number`: max 50 chars
  - `city`, `county`, `country`: max 100 chars
  - `languages_taught`: array of strings
  - `timezone`: valid IANA timezone string (e.g. `Europe/Bucharest`, `America/New_York`). `null` clears the setting and defaults to `Europe/Bucharest`.
- **Success Response**:
  ```json
  {
    "message": "Profile updated successfully",
    "profile": {
      "id": 4,
      "username": "new_username",
      "email": "new@example.com",
      ...
    }
  }
  ```

---

## Change Password

- **URL**: `POST /api/teacher/profile/change-password`
- **Auth Required**: Yes
- **Body**:
  ```json
  {
    "current_password": "old_password",
    "new_password": "new_secure_password"
  }
  ```
- **Validation**: `new_password` min 6 characters
- **Success Response**:
  ```json
  { "message": "Password changed successfully" }
  ```
- **Error Response** `422` — wrong current password:
  ```json
  { "message": "Current password is incorrect" }
  ```

---

## Upload Profile Picture

- **URL**: `POST /api/teacher/profile/picture`
- **Auth Required**: Yes
- **Body**: `multipart/form-data`
  - `image` (required): jpeg, jpg, png, or webp — max 5 MB
- **Success Response**:
  ```json
  {
    "message": "Profile picture uploaded successfully",
    "profile_picture_url": "https://storage.googleapis.com/..."
  }
  ```
- **Notes**: Replaces any existing profile picture. Stored at `teachers/{username}/profile/profile_picture.{ext}`.

---

## Get Profile Picture URL

- **URL**: `GET /api/teacher/profile/picture`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Profile picture retrieved successfully",
    "profile_picture_url": "https://storage.googleapis.com/..."
  }
  ```
- **Error Response** `404` — no picture uploaded yet.

---

## Setup Storage

Creates the GCS folder structure for the teacher. Safe to call multiple times.

- **URL**: `POST /api/teacher/profile/setup`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Storage setup completed",
    "username": "teacher1",
    "folders_created": [
      "teachers/teacher1/private/.keep",
      "teachers/teacher1/profile/.keep"
    ],
    "structure": [
      "teachers/teacher1/private/",
      "teachers/teacher1/profile/"
    ]
  }
  ```
