# Dashboard

Returns summary statistics for the authenticated teacher's activity on the platform.

---

## Get Dashboard Stats

- **URL**: `/api/teacher/dashboard`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**: none
- **Success Response** `200`:
  ```json
  {
    "message": "Teacher dashboard retrieved successfully",
    "stats": {
      "total_students": 24,
      "active_groups": 3,
      "upcoming_homeworks": 5,
      "enrolled_exams": 8
    }
  }
  ```
- **Error Responses**:
  - **401** — missing or invalid token:
    ```json
    { "message": "Unauthenticated." }
    ```

---

## Stats Reference

| Field | Type | Description |
|-------|------|-------------|
| `total_students` | integer | Unique student count across all groups assigned to the teacher |
| `active_groups` | integer | Number of groups where the teacher is the assigned teacher (`group_teacher = teacher.id`), excluding soft-deleted groups |
| `upcoming_homeworks` | integer | Number of homeworks with `due_date` in the future that are assigned to at least one of the teacher's groups (via `groups_assigned`) |
| `enrolled_exams` | integer | Number of distinct exams that students from the teacher's groups have enrolled in (via the `student_exam` pivot) |

---

## How Each Stat Is Calculated

### `total_students`
Counts distinct `student_id` values from the `group_student` pivot table where
`group_id` is in the teacher's assigned groups. A student who belongs to multiple
of the teacher's groups is counted only once.

### `active_groups`
Counts rows in the `groups` table where `group_teacher = teacher.id`.
Soft-deleted groups are automatically excluded.

### `upcoming_homeworks`
Loads all `homework` records with `due_date > today`, then filters those whose
`groups_assigned` JSON array contains at least one of the teacher's group IDs.

### `enrolled_exams`
Collects all student IDs from the teacher's groups, then counts distinct
`exam_id` values in the `student_exam` pivot where those students appear.
