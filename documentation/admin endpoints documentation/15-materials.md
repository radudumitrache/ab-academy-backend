# Materials (Google Cloud Storage)

This section covers the admin API endpoints for full management of course materials stored in Google Cloud Storage.

> **Note**: Admins have unrestricted access to all materials regardless of folder or uploader.

> **Note**: Files are stored in two locations:
> - `private` — under `teachers/{username}/private/` in the bucket
> - `common` — under `common/` in the bucket, visible to all teachers

> **Note**: Downloads are served as **signed URLs** valid for 60 minutes.

---

## List All Materials

- **URL**: `/api/admin/materials`
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
        "uploader": {
          "id": 2,
          "username": "teacher1"
        }
      }
    ]
  }
  ```

---

## Upload Material

- **URL**: `/api/admin/materials/upload`
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
  | `folder` | string | Yes | `private` or `common` |
  | `material_name` | string | No | Defaults to original filename |
  | `uploader_id` | integer | No | User ID to attribute upload to; defaults to the admin's own ID |
  | `allowed_users` | array of integers | No | User IDs that can access this file |

- **Success Response** (`201`):
  ```json
  {
    "message": "Material uploaded successfully",
    "material": {
      "material_id": 2,
      "material_name": "Course Syllabus",
      "file_type": "application/pdf",
      "date_created": "2026-03-06T11:00:00.000000Z",
      "authors": [3],
      "allowed_users": [],
      "gcs_path": "common/course_syllabus.pdf",
      "uploader_id": 3,
      "folder": "common"
    }
  }
  ```

---

## Get Material + Download URL

- **URL**: `/api/admin/materials/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Material retrieved successfully",
    "material": { ... },
    "download_url": "https://storage.googleapis.com/your-bucket/common/course_syllabus.pdf?X-Goog-Signature=..."
  }
  ```

---

## Update Access (allowed_users)

- **URL**: `/api/admin/materials/{id}/access`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "allowed_users": [3, 4, 5, 6, 7]
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

- **URL**: `/api/admin/materials/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Notes**: Removes the file from GCS and deletes the database record permanently.
- **Success Response**:
  ```json
  { "message": "Material deleted successfully" }
  ```
