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
  | `folder` | string | Yes | `private`, `admin`, `common`, or `common/subfolder-name` |
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

Admins can inspect and manage the raw folder structure across the entire bucket — including teacher folders, common, admin, and student areas.

> **Path format**: Use forward-slash-separated paths without a leading slash, e.g. `teachers/teacher1/private/` or `common/`. Omit `prefix` entirely to browse from the bucket root.

> **Empty folders**: Both listing endpoints use GCS delimiter-based listing, which surfaces all virtual directories — including those with no files in them.

### List Contents at a Prefix (folders + files)

Returns immediate subfolders and direct files at the given prefix. Does not recurse.

- **URL**: `GET /api/admin/storage/list?prefix={prefix}`
- **Auth Required**: Yes
- **Query Params**: `prefix` — bucket path prefix (optional, defaults to root)
- **Success Response**:
  ```json
  {
    "message": "Contents retrieved successfully",
    "prefix": "common/",
    "folders": ["common/worksheets/", "common/exams/"],
    "files": ["common/syllabus.pdf", "common/schedule.xlsx"]
  }
  ```

### List Immediate Subfolders Under a Prefix

- **URL**: `GET /api/admin/storage/folders?prefix={prefix}`
- **Auth Required**: Yes
- **Query Params**: `prefix` — bucket path prefix (optional, defaults to root)
- **Success Response**:
  ```json
  {
    "message": "Folders retrieved successfully",
    "prefix": "teachers/teacher1/private/",
    "folders": ["homework", "corrections", "exams"]
  }
  ```

### Create a Folder

Works anywhere in the bucket — teacher areas, common, admin, etc.

- **URL**: `POST /api/admin/storage/folders`
- **Auth Required**: Yes
- **Body**:
  ```json
  { "path": "common/worksheets" }
  ```
  Other examples: `"teachers/teacher1/private/essays"`, `"admin/files/reports"`
- **Validation**: `path` — alphanumeric, hyphens, underscores, dots, and forward slashes only; max 500 chars
- **Success Response** `201`:
  ```json
  {
    "message": "Folder created successfully",
    "path": "common/worksheets/"
  }
  ```
- **Error Response** `409` — folder already exists.

### Delete a Folder

Deletes the folder placeholder and **all objects** inside it. Works anywhere in the bucket.

- **URL**: `DELETE /api/admin/storage/folders`
- **Auth Required**: Yes
- **Body**:
  ```json
  { "path": "common/old-worksheets" }
  ```
  Other examples: `"teachers/teacher1/private/old-essays"`, `"admin/files/archive"`
- **Validation**: same as create
- **Success Response**:
  ```json
  {
    "message": "Folder deleted successfully",
    "objects_deleted": 5
  }
  ```
- **Error Response** `404` — folder not found or already empty.
