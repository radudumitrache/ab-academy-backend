# Materials (Google Cloud Storage)

This section covers the admin API endpoints for full management of course materials stored in Google Cloud Storage.

> **Note**: Admins have unrestricted access to all materials regardless of folder or uploader.

> **Note**: Files are stored in three locations:
> - `private` — under `teachers/{username}/private/` in the bucket (attributed to a specific teacher via `uploader_id`)
> - `common` — under `common/` in the bucket, visible to all teachers
> - `admin` — under `admin/files/` in the bucket, for admin-owned files (GCS folder auto-created on first upload)

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
  | `folder` | string | Yes | `private`, `common`, or `admin` |
  | `material_name` | string | No | Defaults to original filename |
  | `uploader_id` | integer | No | User ID to attribute upload to; defaults to the admin's own ID. Ignored when `folder = "admin"` |
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

---

## Storage Folder Management

Admins can inspect and manage the raw folder structure across the entire bucket — including teacher folders, common, and admin areas.

> **Path format**: Use forward-slash-separated paths without a leading slash, e.g. `teachers/teacher1/private/` or `admin/files/`.

### List All Objects Under a Prefix

- **URL**: `GET /api/admin/storage/list?prefix={prefix}`
- **Auth Required**: Yes
- **Query Params**: `prefix` — bucket path prefix (defaults to root if omitted)
- **Success Response**:
  ```json
  {
    "message": "Objects retrieved successfully",
    "prefix": "teachers/teacher1/private/",
    "objects": [
      "teachers/teacher1/private/.keep",
      "teachers/teacher1/private/notes.pdf",
      "teachers/teacher1/private/homework/sheet1.docx"
    ]
  }
  ```

### List Immediate Subfolders Under a Prefix

- **URL**: `GET /api/admin/storage/folders?prefix={prefix}`
- **Auth Required**: Yes
- **Query Params**: `prefix` — bucket path prefix (defaults to root if omitted)
- **Success Response**:
  ```json
  {
    "message": "Folders retrieved successfully",
    "prefix": "teachers/teacher1/private/",
    "folders": ["homework", "corrections", "exams"]
  }
  ```

### Create a Folder

- **URL**: `POST /api/admin/storage/folders`
- **Auth Required**: Yes
- **Body**:
  ```json
  { "path": "teachers/teacher1/private/new-folder" }
  ```
- **Validation**: `path` — alphanumeric, hyphens, underscores, dots, and forward slashes only; max 500 chars
- **Success Response** `201`:
  ```json
  {
    "message": "Folder created successfully",
    "path": "teachers/teacher1/private/new-folder/"
  }
  ```
- **Error Response** `409` — folder already exists.

### Delete a Folder

Deletes the folder placeholder and **all objects** inside it.

- **URL**: `DELETE /api/admin/storage/folders`
- **Auth Required**: Yes
- **Body**:
  ```json
  { "path": "teachers/teacher1/private/old-folder" }
  ```
- **Validation**: same as create
- **Success Response**:
  ```json
  {
    "message": "Folder deleted successfully",
    "objects_deleted": 5
  }
  ```
- **Error Response** `404` — folder not found or already empty.
