# Correction Content Viewer (Student)

Students can fetch the raw text content of a correction file the teacher attached to a graded response. This is used to display `.md` (Markdown) correction documents inline in the frontend rather than as a download.

---

## Get Correction File Content

`GET /api/student/submissions/{submissionId}/responses/{responseId}/correction-content`

Returns the raw text content of the correction file for a specific response. The student must own the submission — attempting to access another student's submission returns `404`.

**Path parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `submissionId` | integer | The homework submission ID (`submission.id`) |
| `responseId` | integer | The individual question response ID (`response.response_id`) |

**Response** `200`:

Returns the raw file content as plain text (`Content-Type: text/plain; charset=utf-8`). For `.md` correction files the body is a Markdown string that can be rendered directly by the frontend.

```
Content-Type: text/plain; charset=utf-8

✅ Well-structured response with clear organization. The student effectively
summarises the main features of the data and makes meaningful comparisons...
```

**Errors:**

| Status | Meaning |
|--------|---------|
| `401` | Unauthenticated |
| `404` | Submission not found, does not belong to this student, response not found, or no correction file attached |

---

## Usage Notes

- The `correction_file_url` field returned by the homework results endpoint (`GET /api/student/homework/{id}/results`) is a signed GCS URL that expires after **60 minutes** and triggers a file download. Use this endpoint instead when you want to render the correction inline (e.g. in a Markdown viewer component).
- This endpoint streams the GCS object directly — there is no expiry window.
- Only responses that have a non-null `correction_file_path` will return content. Check `correction_file_url !== null` in the results response before calling this endpoint.

---

## Example Flow

```
GET /api/student/homework/125/results
→ responses[0].response_id = 1248
→ responses[0].correction_file_url = "https://storage.googleapis.com/..." (not null)

GET /api/student/submissions/153/responses/1248/correction-content
→ 200 OK — raw Markdown text of the correction
```
