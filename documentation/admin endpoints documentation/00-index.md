# AB Academy Admin API Documentation

This documentation provides comprehensive details about the AB Academy admin API endpoints for frontend integration.

## Table of Contents

1. [Authentication](01-authentication.md)
2. [Events](02-events.md)
3. [Groups](03-groups.md)
4. [User Management](04-user-management.md)
5. [Dashboard](05-dashboard.md)
6. [Courses](06-courses.md)
7. [Exams](07-exams.md)
8. [Archive](08-archive.md)
9. [Student Details](09-student-details.md)
10. [Chat System](10-chat-system.md)
11. [Admin Chat System](11-admin-chat-system.md)
12. [Homework System (legacy model docs)](12-homework-system.md)
13. [Notifications](13-notifications.md)
14. [Invoices](14-invoices.md)
15. [Materials](15-materials.md)
16. [Homework (Admin CRUD + Submissions)](16-homework.md)
17. [Tests (Admin CRUD + Submissions)](17-tests.md)
18. [Chat](18-chat.md)
19. [Meeting Accounts](19-meeting-accounts.md)
20. [Profile](20-profile.md)
21. [Real-time Broadcasting](21-broadcasting.md)
22. [Products & Acquisitions](22-products.md) — manage product catalogue, grant access after payment
23. [Payment Profiles (Admin)](23-payment-profiles.md) — view all student profiles, set invoice_text, confirm profiles
24. [Acquisitions & Invoicing Flow](24-acquisitions-invoicing.md) — full admin flow: grant access → create SmartBill invoice → mark paid
25. [EuPlatesc Transactions](25-euplatesc-transactions.md) — view all EuPlatesc payments, check live status

## Base URL

All API endpoints are prefixed with `/api/admin`.

```
https://backend.andreeaberkhout.com/api/admin
```

## Authentication

All protected endpoints require a Bearer token obtained from the login endpoint:

```
Authorization: Bearer {access_token}
```

## Quick Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/admin/login` | No | Log in and receive an access token |
| POST | `/api/admin/logout` | Yes | Revoke the current token |
| GET | `/api/admin/dashboard` | Yes | Dashboard KPIs and activity |
| GET | `/api/admin/search` | Yes | Search users, groups, courses |
| GET | `/api/admin/events` | Yes | List all events |
| POST | `/api/admin/events` | Yes | Create an event |
| GET | `/api/admin/events/{id}` | Yes | Get a single event |
| PUT | `/api/admin/events/{id}` | Yes | Update an event |
| DELETE | `/api/admin/events/{id}` | Yes | Delete an event |
| POST | `/api/admin/events/{id}/create-zoom-meeting` | Yes | Auto-create a Zoom meeting for an event |
| GET | `/api/admin/groups` | Yes | List all groups |
| POST | `/api/admin/groups` | Yes | Create a group |
| GET | `/api/admin/groups/{id}` | Yes | Get a single group |
| PUT | `/api/admin/groups/{id}` | Yes | Update a group |
| DELETE | `/api/admin/groups/{id}` | Yes | Delete a group (soft delete) |
| POST | `/api/admin/groups/{id}/students` | Yes | Add a student to a group |
| DELETE | `/api/admin/groups/{groupId}/students/{studentId}` | Yes | Remove a student from a group |
| GET | `/api/admin/users` | Yes | List all users |
| POST | `/api/admin/users/teachers` | Yes | Create a teacher account |
| POST | `/api/admin/users/students` | Yes | Create a student account |
| GET | `/api/admin/users/{id}` | Yes | Get a single user |
| PUT | `/api/admin/users/{id}` | Yes | Update a user |
| DELETE | `/api/admin/users/{id}` | Yes | Delete a user |
| POST | `/api/admin/teachers` | Yes | Create a teacher |
| GET | `/api/admin/teachers` | Yes | List all teachers |
| GET | `/api/admin/teachers/{id}` | Yes | Get a teacher |
| PUT | `/api/admin/teachers/{id}` | Yes | Update a teacher |
| DELETE | `/api/admin/teachers/{id}` | Yes | Delete a teacher |
| POST | `/api/admin/students` | Yes | Create a student |
| GET | `/api/admin/students` | Yes | List all students |
| GET | `/api/admin/students/{id}` | Yes | Get a student |
| PUT | `/api/admin/students/{id}` | Yes | Update a student |
| DELETE | `/api/admin/students/{id}` | Yes | Delete a student |
| GET | `/api/admin/courses` | Yes | List all courses |
| POST | `/api/admin/courses` | Yes | Create a course |
| GET | `/api/admin/courses/{id}` | Yes | Get a course |
| PUT | `/api/admin/courses/{id}` | Yes | Update a course |
| DELETE | `/api/admin/courses/{id}` | Yes | Delete a course |
| GET | `/api/admin/exams` | Yes | List all exams |
| POST | `/api/admin/exams` | Yes | Create an exam |
| GET | `/api/admin/exams/{id}` | Yes | Get an exam |
| PUT | `/api/admin/exams/{id}` | Yes | Update an exam |
| DELETE | `/api/admin/exams/{id}` | Yes | Delete an exam |
| POST | `/api/admin/exams/{id}/students` | Yes | Enroll students in an exam |
| DELETE | `/api/admin/exams/{id}/students/{studentId}` | Yes | Remove a student from an exam |
| GET | `/api/admin/archive` | Yes | List archived courses/groups |
| POST | `/api/admin/archive/{id}/restore` | Yes | Restore an archived item |
| GET | `/api/admin/students/{id}/details` | Yes | Get student groups, exams, payments |
| GET | `/api/admin/students/{id}/groups` | Yes | List groups for a student |
| GET | `/api/admin/students/{id}/exams` | Yes | List exams for a student |
| GET | `/api/admin/students/{id}/payments` | Yes | List payments for a student |
| GET | `/api/admin/users/{id}/notes` | Yes | Get admin notes for a user |
| POST | `/api/admin/users/{id}/notes` | Yes | Save/update admin notes for a user |
| GET | `/api/admin/logs` | Yes | List database logs |
| GET | `/api/admin/logs/{id}` | Yes | Get a single log entry |
| GET | `/api/admin/notifications` | Yes | List all notifications |
| POST | `/api/admin/notifications` | Yes | Create a notification |
| DELETE | `/api/admin/notifications/{id}` | Yes | Delete a notification |
| GET | `/api/admin/invoices` | Yes | List all invoices |
| POST | `/api/admin/invoices` | Yes | Create an invoice (auto-synced to SmartBill) |
| GET | `/api/admin/invoices/{id}` | Yes | Get an invoice |
| PUT | `/api/admin/invoices/{id}` | Yes | Update an invoice |
| DELETE | `/api/admin/invoices/{id}` | Yes | Delete an invoice |
| PUT | `/api/admin/invoices/{id}/status` | Yes | Update status (marks paid in SmartBill if synced) |
| POST | `/api/admin/invoices/{id}/smartbill-sync` | Yes | Manually retry SmartBill sync |
| GET | `/api/admin/materials` | Yes | List all materials |
| POST | `/api/admin/materials/upload` | Yes | Upload a file to any bucket path via `folder_path` field |
| GET | `/api/admin/materials/{id}` | Yes | Get material + signed download URL |
| PUT | `/api/admin/materials/{id}/access` | Yes | Update allowed_users on a material |
| DELETE | `/api/admin/materials/{id}` | Yes | Delete a material |
| GET | `/api/admin/storage/list` | Yes | List immediate subfolders + direct files at a bucket prefix |
| GET | `/api/admin/storage/folders` | Yes | List immediate subfolders under a bucket prefix |
| POST | `/api/admin/storage/folders` | Yes | Create a folder at any bucket path |
| DELETE | `/api/admin/storage/folders` | Yes | Delete a folder and all its contents at any path |
| GET | `/api/admin/homework` | Yes | List all homework |
| POST | `/api/admin/homework` | Yes | Create homework |
| GET | `/api/admin/homework/{id}` | Yes | Get homework with sections and questions |
| PUT | `/api/admin/homework/{id}` | Yes | Update homework |
| DELETE | `/api/admin/homework/{id}` | Yes | Delete homework |
| POST | `/api/admin/homework/{id}/assign` | Yes | Assign students/groups to homework |
| GET | `/api/admin/homework/{homeworkId}/sections` | Yes | List sections of a homework |
| POST | `/api/admin/homework/{homeworkId}/sections` | Yes | Add a section |
| PUT | `/api/admin/homework/{homeworkId}/sections/{sId}` | Yes | Update a section |
| DELETE | `/api/admin/homework/{homeworkId}/sections/{sId}` | Yes | Delete a section |
| POST | `/api/admin/homework/{homeworkId}/questions` | Yes | Add a question |
| PUT | `/api/admin/homework/{homeworkId}/questions/{qId}` | Yes | Update a question |
| DELETE | `/api/admin/homework/{homeworkId}/questions/{qId}` | Yes | Delete a question |
| GET | `/api/admin/homework/{homeworkId}/submissions` | Yes | List submitted homework submissions |
| GET | `/api/admin/homework/{homeworkId}/submissions/{sId}` | Yes | Get a single homework submission |
| PATCH | `/api/admin/homework/{homeworkId}/submissions/{sId}/grade` | Yes | Grade a homework submission |
| PATCH | `/api/admin/homework/{homeworkId}/submissions/{sId}/grade-responses` | Yes | Grade individual question responses |
| GET | `/api/admin/tests` | Yes | List all tests |
| POST | `/api/admin/tests` | Yes | Create a test |
| GET | `/api/admin/tests/{id}` | Yes | Get test with sections and questions |
| PUT | `/api/admin/tests/{id}` | Yes | Update a test |
| DELETE | `/api/admin/tests/{id}` | Yes | Delete a test |
| POST | `/api/admin/tests/{id}/assign` | Yes | Assign students/groups to a test |
| GET | `/api/admin/tests/{testId}/sections` | Yes | List sections of a test |
| POST | `/api/admin/tests/{testId}/sections` | Yes | Add a section |
| PUT | `/api/admin/tests/{testId}/sections/{sId}` | Yes | Update a section |
| DELETE | `/api/admin/tests/{testId}/sections/{sId}` | Yes | Delete a section |
| POST | `/api/admin/tests/{testId}/questions` | Yes | Add a question |
| PUT | `/api/admin/tests/{testId}/questions/{qId}` | Yes | Update a question |
| DELETE | `/api/admin/tests/{testId}/questions/{qId}` | Yes | Delete a question |
| GET | `/api/admin/tests/{testId}/submissions` | Yes | List submitted test submissions |
| GET | `/api/admin/tests/{testId}/submissions/{sId}` | Yes | Get a single test submission |
| PATCH | `/api/admin/tests/{testId}/submissions/{sId}/grade` | Yes | Grade a test submission |
| PATCH | `/api/admin/tests/{testId}/submissions/{sId}/grade-responses` | Yes | Grade individual question responses |
| GET | `/api/admin/meeting-accounts` | Yes | List all Zoom meeting accounts |
| POST | `/api/admin/meeting-accounts` | Yes | Create a meeting account |
| GET | `/api/admin/meeting-accounts/{id}` | Yes | Get a meeting account |
| PUT | `/api/admin/meeting-accounts/{id}` | Yes | Update a meeting account |
| DELETE | `/api/admin/meeting-accounts/{id}` | Yes | Delete a meeting account |
| POST | `/api/admin/meeting-accounts/{id}/test` | Yes | Test Zoom credentials |
| GET | `/api/admin/chats` | Yes | List all admin chats |
| GET | `/api/admin/chats/{id}` | Yes | Get full message history (marks messages read, includes `sender_role`) |
| POST | `/api/admin/chats/{id}/messages` | Yes | Send a message |
| GET | `/api/admin/chats/unread/count` | Yes | Total unread message count |
| POST | `/api/admin/chats/student` | Yes | Open or resume a chat with a student |
| PUT | `/api/admin/chats/{id}/archive` | Yes | Archive a chat |
| POST | `/api/admin/profile/setup` | Yes | Create GCS folder structure for admin |
| GET | `/api/admin/profile` | Yes | Get own profile |
| PUT | `/api/admin/profile` | Yes | Update own profile details |
| POST | `/api/admin/profile/change-password` | Yes | Change own password |
| POST | `/api/admin/profile/picture` | Yes | Upload or replace profile picture |
| GET | `/api/admin/profile/picture` | Yes | Get a signed URL for profile picture |
| GET | `/api/admin/products` | Yes | List all products (including inactive) |
| POST | `/api/admin/products` | Yes | Create a product |
| GET | `/api/admin/products/{id}` | Yes | Get a single product |
| PUT | `/api/admin/products/{id}` | Yes | Update a product |
| DELETE | `/api/admin/products/{id}` | Yes | Soft-delete a product |
| GET | `/api/admin/acquisitions` | Yes | List all acquisitions (filterable, incl. `needs_groups`) |
| GET | `/api/admin/acquisitions/{id}` | Yes | Get a single acquisition with full profile details |
| POST | `/api/admin/acquisitions/{id}/grant-access` | Yes | Grant groups/tests access → marks active |
| POST | `/api/admin/acquisitions/{id}/create-invoice` | Yes | Create SmartBill invoice (requires profile confirmation if observations exist) |
| POST | `/api/admin/acquisitions/{id}/mark-invoice-paid` | Yes | Mark SmartBill invoice as paid |
| PUT | `/api/admin/acquisitions/{id}/status` | Yes | Update acquisition status |
| GET | `/api/admin/payment-profiles` | Yes | List all student payment profiles (filterable, incl. `needs_confirmation`) |
| GET | `/api/admin/payment-profiles/{id}` | Yes | Get profile with acquisitions summary |
| GET | `/api/admin/students/{studentId}/payment-profiles` | Yes | List profiles for a specific student |
| POST | `/api/admin/payment-profiles/{id}/set-invoice-text` | Yes | Set invoice_text and confirm profile |
| POST | `/api/admin/payment-profiles/{id}/confirm` | Yes | Confirm profile without changing invoice_text |
| GET | `/api/admin/euplatesc-transactions` | Yes | List all EuPlatesc payment transactions (filterable) |
| GET | `/api/admin/euplatesc-transactions/{id}/check-status` | Yes | Check live status of a transaction from EuPlatesc API |
| POST | `/broadcasting/auth` | Yes | Pusher channel authorization (called by Laravel Echo) |

## Error Format

All error responses follow the same shape:

```json
{
  "message": "Human-readable error description",
  "errors": {
    "field_name": ["Validation message"]
  }
}
```

| HTTP Status | Meaning |
|-------------|---------|
| `401` | Invalid credentials or unauthenticated |
| `403` | Authenticated but not authorized for this action |
| `404` | Resource not found |
| `422` | Validation failed |
| `500` | Unexpected server error |
| `502` | Upstream service error (e.g. Zoom API unreachable) |
