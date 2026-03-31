# AB Academy Student API Documentation

This documentation covers all student-facing API endpoints for the AB Academy platform.

## Table of Contents

1. [Authentication](01-authentication.md) — register, login, logout
2. [Profile](02-profile.md) — view and update personal details, change password, profile picture
3. [Groups](03-groups.md) — list groups, view group detail with homework, join by code, course hours
4. [Schedule](04-schedule.md) — weekly schedule overview across all groups
5. [Events](05-events.md) — meetings and events the student is invited to
6. [Homework](06-homework.md) — view assigned homework, save answers, submit
7. [Tests](07-tests.md) — view assigned tests, save answers, submit
8. [Exams](08-exams.md) — browse available exams, register/unregister, record personal scores
9. [Materials](09-materials.md) — browse and download files shared with the student
10. [Chat](10-chat.md) — messaging with teachers and admin
11. [Notifications](11-notifications.md) — view, mark as seen, delete notifications
12. [Invoices & Payments](12-invoices.md) — view invoices, pay via EuPlatesc
13. [Dashboard & Achievements](13-dashboard.md) — dashboard overview, streak, achievements
14. [Payment Profiles](13-payment-profiles.md) — manage billing profiles (physical person or company)
15. [Products & Purchases](14-products.md) — browse products, purchase via EuPlatesc, renew subscriptions
16. [Group Announcements](15-group-announcements.md) — read announcements posted to your groups

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
| POST | `/api/student/profile/setup` | Yes | Create GCS folder structure for the student |
| POST | `/api/student/profile/change-password` | Yes | Change password |
| POST | `/api/student/profile/picture` | Yes | Upload or replace profile picture |
| GET | `/api/student/profile/picture` | Yes | Get signed profile picture URL |
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
| GET | `/api/student/exams` | Yes | List all enrolled exams |
| GET | `/api/student/exams/available` | Yes | List upcoming exams available to register for |
| GET | `/api/student/exams/{id}` | Yes | Get a single enrolled exam |
| POST | `/api/student/exams/{id}/register` | Yes | Register for an exam (no body needed) |
| PATCH | `/api/student/exams/{id}/score` | Yes | Record own score/notes |
| DELETE | `/api/student/exams/{id}/unregister` | Yes | Unregister from an exam |
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
| GET | `/api/student/payment-profiles` | Yes | List payment profiles |
| GET | `/api/student/payment-profiles/{id}` | Yes | Get a single payment profile |
| POST | `/api/student/payment-profiles` | Yes | Create a payment profile |
| PUT | `/api/student/payment-profiles/{id}` | Yes | Update a payment profile |
| DELETE | `/api/student/payment-profiles/{id}` | Yes | Delete a payment profile |
| GET | `/api/student/products` | Yes | List active products |
| GET | `/api/student/products/{id}` | Yes | Get a single product |
| POST | `/api/student/products/{id}/purchase` | Yes | Initiate EuPlatesc checkout for a product (returns HTML) |
| GET | `/api/student/acquisitions` | Yes | List own product acquisitions |
| GET | `/api/student/acquisitions/{id}` | Yes | Get a single acquisition |
| POST | `/api/student/acquisitions/{id}/renew` | Yes | Renew a completed/expired acquisition |
| GET | `/api/student/dashboard` | Yes | Full dashboard overview |
| GET | `/api/student/achievements` | Yes | Streak + achievement list |
| GET | `/api/student/groups/{groupId}/announcements` | Yes | List announcements for a group (student must be a member) |
| POST | `/broadcasting/auth` | Yes | Pusher channel authorization (called by Laravel Echo) |

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
