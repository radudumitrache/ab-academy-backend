# Dashboard & Achievements

## Dashboard

`GET /api/student/dashboard`

Returns a full overview for the student's home screen: groups, upcoming events, pending work, unpaid invoices, streak, and achievements.

**Response** `200`:
```json
{
  "message": "Dashboard retrieved successfully",
  "dashboard": {
    "groups": [
      {
        "group_id": 3,
        "group_name": "English B2",
        "teacher": "ms_popescu"
      }
    ],
    "upcoming_events": [
      {
        "id": 7,
        "title": "Speaking Practice",
        "start_time": "2026-03-10T15:00:00.000000Z",
        "end_time": "2026-03-10T16:00:00.000000Z"
      }
    ],
    "pending_homework": [
      {
        "id": 4,
        "homework_title": "Unit 5 Reading",
        "due_date": "2026-03-12",
        "overdue": false
      }
    ],
    "pending_tests": [
      {
        "id": 2,
        "test_title": "Grammar Test 3",
        "due_date": "2026-03-09",
        "overdue": true
      }
    ],
    "unpaid_invoices": [
      {
        "id": 1,
        "title": "Curs engleza - luna martie",
        "number": "AB-000001",
        "value": "150.00",
        "currency": "RON",
        "due_date": "2026-03-31",
        "status": "issued"
      }
    ],
    "streak": {
      "current_streak": 5,
      "longest_streak": 12,
      "last_submission_at": "2026-03-07"
    },
    "achievements": {
      "unlocked_count": 3,
      "total": 7,
      "list": [
        {
          "key": "early_bird",
          "name": "Early Bird",
          "description": "Submit homework 2+ days before the deadline",
          "unlocked": true,
          "unlocked_at": "2026-01-10"
        },
        {
          "key": "diamond_student",
          "name": "Diamond Student",
          "description": "Maintain a 30-day streak",
          "unlocked": false,
          "unlocked_at": null
        }
      ]
    }
  }
}
```

---

## Achievements

`GET /api/student/achievements`

Returns only the streak and full achievement list, without the rest of the dashboard data.

**Response** `200`:
```json
{
  "message": "Achievements retrieved successfully",
  "streak": {
    "current_streak": 5,
    "longest_streak": 12,
    "last_submission_at": "2026-03-07"
  },
  "achievements": {
    "unlocked_count": 3,
    "total": 7,
    "list": [ ... ]
  }
}
```

---

## Achievement List

| Key | Name | How to unlock |
|-----|------|---------------|
| `early_bird` | Early Bird | Submit a homework 2+ days before its deadline |
| `on_fire` | On Fire | Reach a 3-day submission streak |
| `perfect_week` | Perfect Week | Submit all homework assigned to you in a single calendar week |
| `first_of_class` | First of Class | Be the first student to submit a specific assignment |
| `bookworm` | Bookworm | Submit 10 assignments in total (homework + tests combined) |
| `diamond_student` | Diamond Student | Reach a 30-day streak |
| `rocket_launch` | Rocket Launch | Submit 3 assignments in a single day |

Each achievement is unlocked once and never revoked, even if the streak later resets.

---

## Streak Rules

- The streak **increments by 1** each day a student submits at least one homework or test.
- Submitting multiple times on the same day counts as one streak day.
- If **7 or more days** pass without any submission, the streak **resets to 0**.
- The `longest_streak` tracks the all-time best streak and never decreases.
- The current streak is checked and auto-reset on every `GET /dashboard` and `GET /achievements` request.

---

## New Achievements on Submit

When a student submits homework (`POST /homework/{id}/submit`) or a test (`POST /tests/{id}/submit`), the response includes a `new_achievements` array listing any achievement keys unlocked by that submission:

```json
{
  "message": "Homework submitted successfully",
  "submission": { ... },
  "new_achievements": ["early_bird", "on_fire"]
}
```

An empty array means no new achievements were earned.
