# Group Announcements (Teacher)

Teachers can create, view, update, and delete announcements for groups they **own or assist**. Attempting to post to a group the teacher is not associated with returns `403`.

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
  "updated_at": "2026-03-31T10:00:00.000000Z",
  "group": {
    "group_id": 3,
    "group_name": "English A1"
  }
}
```

| Field | Description |
|---|---|
| `announcement_id` | Unique identifier |
| `title` | Short title (max 255 characters) |
| `group_id` | ID of the group this announcement belongs to |
| `message` | Full announcement text |
| `attached_files` | Array of `material_id` values; `null` if no attachments |
| `time_created` | Timestamp when the announcement was created (auto-set) |
| `group` | Eager-loaded group name and ID |

---

## List Announcements

```
GET /api/teacher/group-announcements
```

Returns all announcements belonging to groups the authenticated teacher can manage, ordered by `time_created` descending.

**Response `200`**
```json
{
  "message": "Announcements retrieved successfully",
  "count": 2,
  "announcements": [ { ...announcement object... } ]
}
```

---

## Get Single Announcement

```
GET /api/teacher/group-announcements/{id}
```

Returns `404` if the announcement does not exist or belongs to a group the teacher cannot manage.

**Response `200`**
```json
{
  "message": "Announcement retrieved successfully",
  "announcement": { ...announcement object... }
}
```

---

## Create Announcement

```
POST /api/teacher/group-announcements
```

**Request Body**

| Field | Type | Required | Description |
|---|---|---|---|
| `title` | string | Yes | Max 255 characters |
| `group_id` | integer | Yes | Must be a group the teacher can manage |
| `message` | string | Yes | Full announcement body |
| `attached_files` | integer[] | No | Array of `material_id` values |

**Example**
```json
{
  "title": "Homework deadline extended",
  "group_id": 3,
  "message": "The deadline for Chapter 5 homework has been moved to next Monday.",
  "attached_files": []
}
```

**Response `201`**
```json
{
  "message": "Announcement created successfully",
  "announcement": { ...announcement object... }
}
```

**Response `403`** — teacher is not associated with the specified group
```json
{ "message": "You are not authorised to post announcements in this group" }
```

---

## Update Announcement

```
PUT /api/teacher/group-announcements/{id}
```

All fields are optional. Returns `404` if the announcement does not exist or belongs to an unmanaged group. Returns `403` if `group_id` is changed to a group the teacher cannot manage.

**Request Body**

| Field | Type | Required | Description |
|---|---|---|---|
| `title` | string | No | Max 255 characters |
| `group_id` | integer | No | Must be a group the teacher can manage |
| `message` | string | No | Full announcement body |
| `attached_files` | integer[] | No | Replaces existing list; send `null` to clear |

**Response `200`**
```json
{
  "message": "Announcement updated successfully",
  "announcement": { ...announcement object... }
}
```

---

## Delete Announcement

```
DELETE /api/teacher/group-announcements/{id}
```

Returns `404` if the announcement does not exist or belongs to an unmanaged group.

**Response `200`**
```json
{ "message": "Announcement deleted successfully" }
```
