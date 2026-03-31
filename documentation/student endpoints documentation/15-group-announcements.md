# Group Announcements (Student)

Students can read announcements posted to their groups. Attempting to access announcements for a group the student does not belong to returns `403`.

---

## Announcement Object

```json
{
  "announcement_id": 1,
  "title": "Class cancelled this Friday",
  "group_id": 3,
  "message": "Due to a public holiday, Friday's session is cancelled. We'll resume next week.",
  "attached_files": [12, 15],
  "time_created": "2026-03-31T10:00:00.000000Z",
  "created_at": "2026-03-31T10:00:00.000000Z",
  "updated_at": "2026-03-31T10:00:00.000000Z"
}
```

| Field | Description |
|---|---|
| `announcement_id` | Unique identifier |
| `title` | Short title |
| `group_id` | ID of the group this announcement belongs to |
| `message` | Full announcement text |
| `attached_files` | Array of `material_id` values; `null` if no attachments. Use `GET /api/student/materials/{id}` to retrieve each file. |
| `time_created` | When the announcement was posted |

---

## Get Group Announcements

```
GET /api/student/groups/{groupId}/announcements
```

Returns all announcements for the specified group, ordered by `time_created` descending. The student must be a member of the group.

**URL Parameters**

| Parameter | Description |
|---|---|
| `groupId` | The ID of the group |

**Response `200`**
```json
{
  "message": "Announcements retrieved successfully",
  "count": 2,
  "announcements": [
    {
      "announcement_id": 2,
      "title": "New reading material uploaded",
      "group_id": 3,
      "message": "Please read chapters 6 and 7 before Thursday's session.",
      "attached_files": [18],
      "time_created": "2026-03-30T08:00:00.000000Z",
      "created_at": "2026-03-30T08:00:00.000000Z",
      "updated_at": "2026-03-30T08:00:00.000000Z"
    },
    {
      "announcement_id": 1,
      "title": "Class cancelled this Friday",
      "group_id": 3,
      "message": "Due to a public holiday, Friday's session is cancelled.",
      "attached_files": null,
      "time_created": "2026-03-28T10:00:00.000000Z",
      "created_at": "2026-03-28T10:00:00.000000Z",
      "updated_at": "2026-03-28T10:00:00.000000Z"
    }
  ]
}
```

**Response `403`** — student is not a member of this group
```json
{ "message": "You are not a member of this group" }
```
