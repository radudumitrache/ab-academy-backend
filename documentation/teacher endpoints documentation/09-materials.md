# Materials (Google Cloud Storage)

This section covers the API endpoints for managing course materials stored in Google Cloud Storage.

> **Note**: Files can be stored in two locations:
> - `private` — stored under `teachers/{teacher_id}/` in the bucket. Only the uploading teacher can manage it.
> - `common` — stored under `common/` in the bucket. Visible to all teachers.

> **Note**: Downloads are served as **signed URLs** valid for 60 minutes. The file is never proxied through the server.

> **Note**: Access to a material is controlled by the `allowed_users` field — an array of user IDs. Students can only see materials where their ID appears in this list.

---

## Setup Storage (Create Folder Structure)

- **URL**: `/api/teacher/materials/setup`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Notes**: Creates the teacher's folder structure in GCS. Safe to call multiple times — skips folders that already exist. Should be called once after the teacher account is created.
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

---

## List Materials

- **URL**: `/api/teacher/materials`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Materials retrieved successfully",
    "materials": [
      {
        "material_id": 1,
        "material_name": "Lecture Notes - Week 1",
        "file_type": "application/pdf",
        "date_created": "2026-03-06T10:00:00.000000Z",
        "authors": [2],
        "allowed_users": [3, 4, 5],
        "gcs_path": "teachers/2/lecture_notes_week1.pdf",
        "uploader_id": 2,
        "folder": "private",
        "created_at": "2026-03-06T10:00:00.000000Z",
        "updated_at": "2026-03-06T10:00:00.000000Z"
      }
    ]
  }
  ```

---

## Upload Material

- **URL**: `/api/teacher/materials/upload`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: multipart/form-data
  ```
- **Request Body** (multipart form):

  | Field | Type | Required | Notes |
  |-------|------|----------|-------|
  | `file` | file | Yes | Max 100 MB |
  | `folder` | string | Yes | `common`, `private`, or `private/{subfolder}`. Subfolder names may only contain letters, numbers, `-`, and `_`. |
  | `material_name` | string | No | Defaults to original filename |
  | `allowed_users` | array of integers | No | User IDs that can access this file |

  **Folder examples:**
  - `common` → stored at `common/{filename}`
  - `private` → stored at `teachers/{username}/private/{filename}`
  - `private/tema7-03-2026` → stored at `teachers/{username}/private/tema7-03-2026/{filename}`

- **Success Response** (`201`):
  ```json
  {
    "message": "Material uploaded successfully",
    "material": {
      "material_id": 1,
      "material_name": "Lecture Notes - Week 1",
      "file_type": "application/pdf",
      "date_created": "2026-03-06T10:00:00.000000Z",
      "authors": [2],
      "allowed_users": [3, 4],
      "gcs_path": "teachers/teacher1/private/tema7-03-2026/lecture_notes.pdf",
      "uploader_id": 2,
      "folder": "private/tema7-03-2026"
    }
  }
  ```

---

## Get Material + Download URL

- **URL**: `/api/teacher/materials/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Notes**: Teacher must own the file or it must be in the `common` folder.
- **Success Response**:
  ```json
  {
    "message": "Material retrieved successfully",
    "material": { ... },
    "download_url": "https://storage.googleapis.com/your-bucket/teachers/2/lecture_notes_week1.pdf?X-Goog-Signature=..."
  }
  ```
- **Error Response** (`403`):
  ```json
  { "message": "Forbidden" }
  ```

---

## Update Access (allowed_users)

- **URL**: `/api/teacher/materials/{id}/access`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Notes**: Teacher must own the material.
- **Request Body**:
  ```json
  {
    "allowed_users": [3, 4, 5, 6]
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Access updated successfully",
    "material": { ... }
  }
  ```

---

## Delete Material

- **URL**: `/api/teacher/materials/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Notes**: Teacher must own the material. Removes the file from GCS and the database record.
- **Success Response**:
  ```json
  { "message": "Material deleted successfully" }
  ```
- **Error Response** (`403`):
  ```json
  { "message": "Forbidden" }
  ```

---

## Upload Profile Picture

Upload or replace the teacher's profile picture. The file is stored at `teachers/{username}/profile/profile_picture.{ext}`. If a previous profile picture exists it is automatically deleted from GCS before the new one is uploaded.

- **URL**: `/api/teacher/profile-picture`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: multipart/form-data
  ```
- **Body** (`form-data`):

  | Field | Type | Required | Description |
  |-------|------|----------|-------------|
  | `file` | File | Yes | Image file (jpeg, jpg, png, webp). Max 5 MB. |

- **Success Response** (`200`):
  ```json
  {
    "message": "Profile picture uploaded successfully",
    "profile_picture_path": "teachers/teacher1/profile/profile_picture.jpg"
  }
  ```
- **Error Response** (`422`):
  ```json
  {
    "message": "Validation failed",
    "errors": { "file": ["The file must be an image (jpeg, jpg, png, webp)."] }
  }
  ```

---

## Get Profile Picture

Returns a signed download URL for the teacher's current profile picture, valid for 60 minutes.

- **URL**: `/api/teacher/profile-picture`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** (`200`):
  ```json
  {
    "message": "Profile picture retrieved successfully",
    "profile_picture_path": "teachers/teacher1/profile/profile_picture.jpg",
    "url": "https://storage.googleapis.com/your-bucket/teachers/teacher1/profile/profile_picture.jpg?X-Goog-Signature=..."
  }
  ```
- **Error Response** (`404`):
  ```json
  { "message": "No profile picture set" }
  ```

---

## List Private Folders

Returns the names of all custom subfolders the teacher has created inside their private area (`teachers/{username}/private/`).

- **URL**: `/api/teacher/folders`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** (`200`):
  ```json
  {
    "message": "Folders retrieved successfully",
    "folders": ["homework-2026", "exercises", "tests"]
  }
  ```

---

## Create Private Folder

Creates a new subfolder inside the teacher's private area. Folder names may only contain letters, numbers, hyphens, and underscores.

- **URL**: `/api/teacher/folders`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Body**:
  ```json
  { "name": "homework-2026" }
  ```
- **Success Response** (`201`):
  ```json
  {
    "message": "Folder created successfully",
    "folder": "homework-2026",
    "path": "teachers/teacher1/private/homework-2026/"
  }
  ```
- **Error Response** (`409`):
  ```json
  { "message": "Folder already exists" }
  ```
- **Error Response** (`422`):
  ```json
  {
    "message": "Validation failed",
    "errors": { "name": ["The name field may only contain letters, numbers, dashes and underscores."] }
  }
  ```

---

## Delete Private Folder

Deletes a subfolder and **all files inside it** from the teacher's private area. This action is irreversible.

- **URL**: `/api/teacher/folders/{name}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response** (`200`):
  ```json
  {
    "message": "Folder deleted successfully",
    "objects_deleted": 5
  }
  ```
- **Error Response** (`404`):
  ```json
  { "message": "Folder not found" }
  ```
