# AB Academy Student API Documentation

This documentation covers all student-facing API endpoints for the AB Academy platform.

## Table of Contents

1. [Authentication](01-authentication.md) — register, login, logout
2. [Profile](02-profile.md) — view and update personal details, change password
3. [Groups](03-groups.md) — list groups, view group detail with homework, join by code, course hours
4. [Schedule](04-schedule.md) — weekly schedule overview across all groups
5. [Events](05-events.md) — meetings and events the student is invited to
6. [Homework](06-homework.md) — view assigned homework, save answers, submit
7. [Tests](07-tests.md) — view assigned tests, save answers, submit
8. [Exams](08-exams.md) — view enrolled exams, create personal exams, record grades
9. [Materials](09-materials.md) — browse and download files shared with the student
10. [Chat](10-chat.md) — messaging with teachers and admin
11. [Notifications](11-notifications.md) — view, mark as seen, delete notifications
12. [Invoices & Payments](12-invoices.md) — view invoices, pay via EuPlatesc
13. [Dashboard & Achievements](13-dashboard.md) — dashboard overview, streak, achievements

## Base URL

All student API endpoints are prefixed with `/api/student`.

```
https://backend.andreeaberkhout.com/api/student
```

## Authentication

All protected endpoints require a Bearer token obtained from the login endpoint:

```
Authorization: Bearer {access_token}
```

## Quick Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/student/register` | No | Register a new student account |
| POST | `/api/student/login` | No | Log in and receive an access token |
| POST | `/api/student/logout` | Yes | Revoke the current token |
| GET | `/api/student/profile` | Yes | Get own profile |
| PUT | `/api/student/profile` | Yes | Update own profile |
| POST | `/api/student/profile/change-password` | Yes | Change password |
| GET | `/api/student/groups` | Yes | List all groups the student belongs to |
| GET | `/api/student/groups/{id}` | Yes | Group detail with assigned homework |
| POST | `/api/student/groups/join` | Yes | Join a group by class code |
| GET | `/api/student/groups/hours` | Yes | Get attendance and course hours |
| GET | `/api/student/schedule` | Yes | Weekly schedule overview |
| GET | `/api/student/events` | Yes | List events the student is invited to |
| GET | `/api/student/events/{id}` | Yes | Get a single event |
| GET | `/api/student/homework` | Yes | List assigned homework |
| GET | `/api/student/homework/{id}` | Yes | Get homework with questions |
| POST | `/api/student/homework/{id}/answers` | Yes | Save answers (draft) |
| POST | `/api/student/homework/{id}/submit` | Yes | Submit homework |
| GET | `/api/student/tests` | Yes | List assigned tests |
| GET | `/api/student/tests/{id}` | Yes | Get test with questions |
| POST | `/api/student/tests/{id}/answers` | Yes | Save answers (draft) |
| POST | `/api/student/tests/{id}/submit` | Yes | Submit test |
| GET | `/api/student/exams` | Yes | List enrolled exams |
| POST | `/api/student/exams` | Yes | Create a personal exam |
| GET | `/api/student/exams/{id}` | Yes | Get exam details and result |
| PATCH | `/api/student/exams/{id}/score` | Yes | Record own score/notes |
| DELETE | `/api/student/exams/{id}` | Yes | Delete a self-created exam |
| GET | `/api/student/materials` | Yes | List materials shared with the student |
| GET | `/api/student/materials/{id}` | Yes | Get material details + signed download URL |
| GET | `/api/student/chats` | Yes | List chats |
| GET | `/api/student/chats/{id}` | Yes | Get chat with messages |
| POST | `/api/student/chats/{id}/messages` | Yes | Send a message |
| GET | `/api/student/chats/unread/count` | Yes | Unread message count |
| POST | `/api/student/chats/admin` | Yes | Start a chat with the admin |
| GET | `/api/student/notifications` | Yes | List notifications (filterable) |
| PUT | `/api/student/notifications/seen-all` | Yes | Mark all as seen |
| PUT | `/api/student/notifications/{id}/seen` | Yes | Mark one as seen |
| DELETE | `/api/student/notifications/{id}` | Yes | Delete a notification |
| GET | `/api/student/invoices` | Yes | List all invoices |
| GET | `/api/student/invoices/{id}` | Yes | Get invoice detail + payment history |
| POST | `/api/student/invoices/{id}/pay` | Yes | Initiate EuPlatesc checkout (returns HTML) |
| GET | `/api/student/dashboard` | Yes | Full dashboard overview |
| GET | `/api/student/achievements` | Yes | Streak + achievement list |

## Error Format

```json
{
  "message": "Human-readable error description"
}
```

| HTTP Status | Meaning |
|-------------|---------|
| `401` | Unauthenticated |
| `403` | Authenticated but not authorized for this resource |
| `404` | Resource not found |
| `409` | Conflict (e.g. already submitted, already in group) |
| `422` | Validation failed |
