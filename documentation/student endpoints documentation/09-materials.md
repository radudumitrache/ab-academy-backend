# Materials

Students can browse and download course materials that a teacher or admin has explicitly shared with them. Files are served as **60-minute signed GCS URLs** — never proxied through the server.

> A student can see a material if their user ID appears in `allowed_users` **or** they belong to a group listed in `allowed_groups`.

---

## List Materials

`GET /api/student/materials`

Returns all materials the student has access to, either directly (via `allowed_users`) or through group membership (via `allowed_groups`).

**Response** `200`:
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
      "allowed_users": [12, 15],
      "allowed_groups": [3],
      "folder": "private",
      "uploader_id": 4
    }
  ]
}
```

---

## Get Material + Download URL

`GET /api/student/materials/{id}`

Returns material details and a time-limited signed download URL. Student must have access (via `allowed_users` or `allowed_groups`).

**Response** `200`:
```json
{
  "message": "Material retrieved successfully",
  "material": {
    "material_id": 1,
    "material_name": "Lecture Notes - Week 1",
    "file_type": "application/pdf",
    "date_created": "2026-03-06T10:00:00.000000Z",
    "authors": [2],
    "allowed_users": [12, 15],
    "allowed_groups": [3],
    "folder": "private"
  },
  "download_url": "https://storage.googleapis.com/bucket/teachers/4/lecture_notes.pdf?X-Goog-Signature=..."
}
```

**Errors**: `403` if the student is not in `allowed_users` and does not belong to any group in `allowed_groups`.
