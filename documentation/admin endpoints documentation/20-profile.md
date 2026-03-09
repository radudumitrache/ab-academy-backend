# Admin Profile

Admins can view and update their own account details, upload a profile picture, change their password, and set up their GCS storage area.

Admin files are stored under the shared `admin/` folder in the bucket:
- `admin/profile/` — profile pictures
- `admin/files/` — uploaded materials (when `folder = "admin"`)

---

## Get Profile

- **URL**: `GET /api/admin/profile`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Profile retrieved successfully",
    "profile": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "telephone": "+31 6 12345678",
      "address": "Main Street 1",
      "street": "Main Street",
      "house_number": "1",
      "city": "Amsterdam",
      "county": "Noord-Holland",
      "country": "Netherlands",
      "occupation": "Platform Administrator",
      "role": "admin",
      "profile_picture_url": "https://storage.googleapis.com/..."
    }
  }
  ```
- **Notes**: `profile_picture_url` is a signed GCS URL valid for 60 minutes. It is `null` if no picture has been uploaded.

---

## Update Profile

- **URL**: `PUT /api/admin/profile`
- **Auth Required**: Yes
- **Body** (all fields optional — send only those to change):
  ```json
  {
    "username": "new_admin",
    "email": "new@example.com",
    "telephone": "+31 6 99999999",
    "address": "New Street 5",
    "street": "New Street",
    "house_number": "5",
    "city": "Rotterdam",
    "county": "Zuid-Holland",
    "country": "Netherlands",
    "occupation": "Head Administrator"
  }
  ```
- **Validation**:
  - `username`: unique across all users (excluding self)
  - `email`: valid email, unique across all users (excluding self)
  - `telephone`: max 20 chars
  - `address`, `street`, `occupation`: max 255 chars
  - `house_number`: max 50 chars
  - `city`, `county`, `country`: max 100 chars
- **Success Response**:
  ```json
  {
    "message": "Profile updated successfully",
    "profile": { ... }
  }
  ```

---

## Change Password

- **URL**: `POST /api/admin/profile/change-password`
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
- **Error Response** `422`:
  ```json
  { "message": "Current password is incorrect" }
  ```

---

## Upload Profile Picture

- **URL**: `POST /api/admin/profile/picture`
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
- **Notes**: Replaces any existing picture. Stored at `admin/profile/profile_picture.{ext}`.

---

## Get Profile Picture URL

- **URL**: `GET /api/admin/profile/picture`
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

Creates the GCS folder structure for the admin area. Safe to call multiple times.

- **URL**: `POST /api/admin/profile/setup`
- **Auth Required**: Yes
- **Success Response**:
  ```json
  {
    "message": "Storage setup completed",
    "folders_created": [
      "admin/profile/.keep",
      "admin/files/.keep"
    ],
    "structure": [
      "admin/profile/",
      "admin/files/"
    ]
  }
  ```
