# Materials (Google Cloud Storage)

This section covers the API endpoints for students to browse and download course materials shared with them.

> **Note**: Students can only see materials where their user ID appears in the `allowed_users` field. A teacher or admin must explicitly grant access.

> **Note**: Downloads are served as **signed URLs** valid for 60 minutes. The file is never proxied through the server.

---

## List Materials

Returns all materials the student has been granted access to.

- **URL**: `/api/student/materials`
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
        "gcs_path": "teachers/teacher1/private/lecture_notes_week1.pdf",
        "uploader_id": 2,
        "folder": "private",
        "created_at": "2026-03-06T10:00:00.000000Z",
        "updated_at": "2026-03-06T10:00:00.000000Z"
      }
    ]
  }
  ```

---

## Get Material + Download URL

Returns material details and a time-limited signed download URL.

- **URL**: `/api/student/materials/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Notes**: Student must be in the `allowed_users` list of the material.
- **Success Response**:
  ```json
  {
    "message": "Material retrieved successfully",
    "material": {
      "material_id": 1,
      "material_name": "Lecture Notes - Week 1",
      "file_type": "application/pdf",
      "date_created": "2026-03-06T10:00:00.000000Z",
      "authors": [2],
      "allowed_users": [3, 4, 5],
      "gcs_path": "teachers/teacher1/private/lecture_notes_week1.pdf",
      "uploader_id": 2,
      "folder": "private"
    },
    "download_url": "https://storage.googleapis.com/your-bucket/teachers/teacher1/private/lecture_notes_week1.pdf?X-Goog-Signature=..."
  }
  ```
- **Error Response** (`403`):
  ```json
  { "message": "Forbidden" }
  ```
