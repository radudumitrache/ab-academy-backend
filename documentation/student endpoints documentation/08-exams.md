# Exams

Exams are formal assessments. Students are enrolled in exams by the admin. This section lets students view their enrolled exams, scheduled dates, statuses, and any scores or feedback given after correction.

---

## List Enrolled Exams

`GET /api/student/exams`

Returns all exams the student is enrolled in, ordered by date.

**Response** `200`:
```json
{
  "message": "Exams retrieved successfully",
  "count": 2,
  "exams": [
    {
      "id": 1,
      "name": "Cambridge B2 Mock Exam",
      "date": "2026-04-15",
      "status": "upcoming",
      "score": null,
      "feedback": null
    },
    {
      "id": 2,
      "name": "Internal Grammar Test",
      "date": "2026-03-01",
      "status": "passed",
      "score": 87,
      "feedback": "Excellent work on the reading section."
    }
  ]
}
```

### Exam Status Values

| Status | Meaning |
|--------|---------|
| `upcoming` | Scheduled, not yet taken |
| `to_be_corrected` | Taken, awaiting correction |
| `passed` | Corrected and passed |
| `failed` | Corrected and failed |

---

## Get Single Exam

`GET /api/student/exams/{id}`

Returns details for a single exam the student is enrolled in.

**Response** `200`:
```json
{
  "message": "Exam retrieved successfully",
  "exam": {
    "id": 1,
    "name": "Cambridge B2 Mock Exam",
    "date": "2026-04-15",
    "status": "upcoming",
    "score": null,
    "feedback": null
  }
}
```

**Errors**: `404` if not found or student is not enrolled.
