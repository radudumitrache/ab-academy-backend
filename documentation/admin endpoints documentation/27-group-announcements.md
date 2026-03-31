# Group Announcements (Admin)

Admins have full CRUD access to all group announcements — no ownership filter applies.

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

## List All Announcements

```
GET /api/admin/group-announcements
```

Returns all announcements across all groups, ordered by `time_created` descending.

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
GET /api/admin/group-announcements/{id}
```

**Response `200`**
```json
{
  "message": "Announcement retrieved successfully",
  "announcement": { ...announcement object... }
}
```

**Response `404`**
```json
{ "message": "Announcement not found" }
```

---

## Create Announcement

```
POST /api/admin/group-announcements
```

**Request Body**

| Field | Type | Required | Description |
|---|---|---|---|
| `title` | string | Yes | Max 255 characters |
| `group_id` | integer | Yes | Must exist in `groups` |
| `message` | string | Yes | Full announcement body |
| `attached_files` | integer[] | No | Array of `material_id` values |

**Example**
```json
{
  "title": "Class cancelled this Friday",
  "group_id": 3,
  "message": "Due to a public holiday, Friday's session is cancelled.",
  "attached_files": [12, 15]
}
```

**Response `201`**
```json
{
  "message": "Announcement created successfully",
  "announcement": { ...announcement object... }
}
```

---

## Update Announcement

```
PUT /api/admin/group-announcements/{id}
```

All fields are optional — only include what you want to change.

**Request Body**

| Field | Type | Required | Description |
|---|---|---|---|
| `title` | string | No | Max 255 characters |
| `group_id` | integer | No | Must exist in `groups` |
| `message` | string | No | Full announcement body |
| `attached_files` | integer[] | No | Replaces existing list; send `null` to clear |

**Response `200`**
```json
{
  "message": "Announcement updated successfully",
  "announcement": { ...announcement object... }
}
```

**Response `404`**
```json
{ "message": "Announcement not found" }
```

---

## Delete Announcement

```
DELETE /api/admin/group-announcements/{id}
```

**Response `200`**
```json
{ "message": "Announcement deleted successfully" }
```

**Response `404`**
```json
{ "message": "Announcement not found" }
```
