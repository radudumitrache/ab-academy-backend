# Materials

Students can browse and download course materials that a teacher or admin has explicitly shared with them. Files are served as **60-minute signed GCS URLs** — never proxied through the server.

> Students can only see materials where their user ID appears in the `allowed_users` field.

---

## List Materials

`GET /api/student/materials`

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
      "folder": "private",
      "uploader_id": 4
    }
  ]
}
```

---

## Get Material + Download URL

`GET /api/student/materials/{id}`

Returns material details and a time-limited signed download URL. Student must be in `allowed_users`.

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
    "folder": "private"
  },
  "download_url": "https://storage.googleapis.com/bucket/teachers/4/lecture_notes.pdf?X-Goog-Signature=..."
}
```

**Errors**: `403` if student is not in `allowed_users`.
