# Materials

Students can browse and download course materials. Files are served as **60-minute signed GCS URLs** — never proxied through the server.

> A student can see a material only if **explicitly granted access**:
> - Their user ID appears in `allowed_users`, **or**
> - They belong to a group listed in `allowed_groups`
>
> Students do **not** have automatic access to the `common` folder. Access to common-folder files must be granted individually or via a group, same as private files.
>
> Students have **no edit or delete** endpoints — all access is read-only.
>
> Stale materials (file deleted from GCS but DB record still present) are automatically cleaned up when listed or accessed.

---

## List Materials

`GET /api/student/materials`

Returns all materials the student has been explicitly granted access to via `allowed_users` or `allowed_groups`. Stale DB records (file no longer in GCS) are removed automatically.

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

Returns material details and a time-limited signed download URL. Student must have been explicitly granted access via `allowed_users` or `allowed_groups`.

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

**Errors**: `403` if the student is not in `allowed_users` and does not belong to any group in `allowed_groups`. `404` if the file no longer exists in GCS (record is also removed).
