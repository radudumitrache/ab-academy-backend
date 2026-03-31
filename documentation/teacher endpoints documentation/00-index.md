# AB Academy Teacher API Documentation

This documentation covers all teacher-facing API endpoints for the AB Academy platform.

## Table of Contents

1. [Authentication](01-authentication.md)
2. [Dashboard](02-dashboard.md)
3. [AI Assistant](03-ai-assistant.md)
4. [Group Management](04-groups.md)
5. [Exam Management](05-exams.md)
6. [Event Management](06-events.md)
7. [Notifications](07-notifications.md)
8. [Homework Management](08-homework.md)
9. [Materials](09-materials.md)
10. [Test Management](10-tests.md)
11. [Profile](11-profile.md)
12. [Attendance](12-attendance.md)
13. [Student Performance](13-student-performance.md)
14. [Group Announcements](14-group-announcements.md)

## Base URL

All teacher API endpoints are prefixed with `/api/teacher`.

```
https://backend.andreeaberkhout.com/api/teacher
```

## Authentication

All protected endpoints require a Bearer token obtained from the login endpoint:

```
Authorization: Bearer {access_token}
```

Tokens are issued via Laravel Passport. Each token is scoped to a teacher account
(users with `role = "teacher"`). Tokens are revoked on logout.

## Quick Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/teacher/login` | No | Log in and receive an access token |
| POST | `/api/teacher/logout` | Yes | Revoke the current token |
| GET | `/api/teacher/dashboard` | Yes | Teacher dashboard stats |
| POST | `/api/teacher/ai-assistant/translate` | Yes | Translate text via Claude AI |
| GET | `/api/teacher/teachers` | Yes | List all other teachers (id + username) for assistant lookup |
| GET | `/api/teacher/groups/schedule/options` | Yes | Get allowed schedule days and times |
| POST | `/api/teacher/groups/join` | Yes | Join a group as assistant teacher via class code |
| GET | `/api/teacher/groups` | Yes | List all groups owned by or assisted by the teacher |
| POST | `/api/teacher/groups` | Yes | Create a new group |
| GET | `/api/teacher/groups/{id}` | Yes | Get a single group |
| PUT | `/api/teacher/groups/{id}` | Yes | Update a group (owner or assistant) |
| DELETE | `/api/teacher/groups/{id}` | Yes | Delete a group (owner or assistant, soft delete) |
| POST | `/api/teacher/groups/{id}/students` | Yes | Add a student to a group by ID (owner or assistant) |
| POST | `/api/teacher/groups/{id}/students/by-username` | Yes | Add a student to a group by username (owner or assistant) |
| DELETE | `/api/teacher/groups/{groupId}/students/{studentId}` | Yes | Remove a student from a group (owner or assistant) |
| POST | `/api/teacher/groups/{id}/generate-code` | Yes | Generate or regenerate a class code (owner or assistant) |
| POST | `/api/teacher/groups/{id}/assistant-teachers` | Yes | Add an assistant teacher to a group (owner only) |
| DELETE | `/api/teacher/groups/{groupId}/assistant-teachers/{teacherId}` | Yes | Remove an assistant teacher from a group (owner only) |
| GET | `/api/teacher/exams` | Yes | List all exams |
| POST | `/api/teacher/exams` | Yes | Create a new exam |
| GET | `/api/teacher/exams/{id}` | Yes | Get a single exam |
| POST | `/api/teacher/exams/{id}/students` | Yes | Enroll students in an exam |
| DELETE | `/api/teacher/exams/{examId}/students/{studentId}` | Yes | Remove a student from an exam |
| GET | `/api/teacher/events` | Yes | List events where teacher is organizer or invited |
| POST | `/api/teacher/events` | Yes | Create a new event |
| GET | `/api/teacher/events/{id}` | Yes | Get a single event |
| PUT | `/api/teacher/events/{id}` | Yes | Update an event (organizer or assistant of invited group) |
| DELETE | `/api/teacher/events/{id}` | Yes | Delete an event (organizer or assistant of invited group) |
| GET | `/api/teacher/events/{id}/attendance` | Yes | View event attendance (organizer or guest) — see [Attendance](12-attendance.md) |
| PUT | `/api/teacher/events/{id}/attendance` | Yes | Record event guest attendance (organizer or assistant of invited group) — see [Attendance](12-attendance.md) |
| GET | `/api/teacher/groups/{id}/attendance` | Yes | View group attendance, filterable by date (owner or assistant teacher) — see [Attendance](12-attendance.md) |
| POST | `/api/teacher/groups/{id}/attendance` | Yes | Record group session attendance (owner or assistant teacher) — see [Attendance](12-attendance.md) |
| POST | `/api/teacher/events/{id}/guests/by-username` | Yes | Add guests to an event by username (organizer or assistant of invited group) |
| POST | `/api/teacher/events/{id}/create-zoom-meeting` | Yes | Auto-create a Zoom meeting for an event (organizer or assistant of invited group) |
| GET | `/api/teacher/notifications` | Yes | List own notifications (filterable) |
| PUT | `/api/teacher/notifications/seen-all` | Yes | Mark all notifications as seen |
| PUT | `/api/teacher/notifications/{id}/seen` | Yes | Mark a single notification as seen |
| DELETE | `/api/teacher/notifications/{id}` | Yes | Delete a notification |
| GET | `/api/teacher/homework` | Yes | List own homework |
| POST | `/api/teacher/homework` | Yes | Create homework |
| GET | `/api/teacher/homework/{id}` | Yes | Get homework with all questions and sections |
| PUT | `/api/teacher/homework/{id}` | Yes | Update homework |
| DELETE | `/api/teacher/homework/{id}` | Yes | Delete homework |
| POST | `/api/teacher/homework/{id}/assign` | Yes | Assign students/groups to homework |
| GET | `/api/teacher/homework/{homeworkId}/sections` | Yes | List sections of a homework |
| POST | `/api/teacher/homework/{homeworkId}/sections` | Yes | Add a section to homework |
| PUT | `/api/teacher/homework/{homeworkId}/sections/{sId}` | Yes | Update a section |
| DELETE | `/api/teacher/homework/{homeworkId}/sections/{sId}` | Yes | Delete a section |
| POST | `/api/teacher/homework/{homeworkId}/questions` | Yes | Add a question to a section |
| PUT | `/api/teacher/homework/{homeworkId}/questions/{qId}` | Yes | Update a question |
| DELETE | `/api/teacher/homework/{homeworkId}/questions/{qId}` | Yes | Delete a question |
| GET | `/api/teacher/homework/{homeworkId}/submissions` | Yes | List submitted submissions for a homework |
| GET | `/api/teacher/homework/{homeworkId}/submissions/{sId}` | Yes | Get a single homework submission with responses |
| PATCH | `/api/teacher/homework/{homeworkId}/submissions/{sId}/grade` | Yes | Grade a homework submission |
| PATCH | `/api/teacher/homework/{homeworkId}/submissions/{sId}/grade-responses` | Yes | Grade individual question responses |
| GET | `/api/teacher/tests` | Yes | List own tests |
| POST | `/api/teacher/tests` | Yes | Create a test |
| GET | `/api/teacher/tests/{id}` | Yes | Get test with all sections and questions |
| PUT | `/api/teacher/tests/{id}` | Yes | Update a test |
| DELETE | `/api/teacher/tests/{id}` | Yes | Delete a test |
| POST | `/api/teacher/tests/{id}/assign` | Yes | Assign students/groups to a test |
| GET | `/api/teacher/tests/{testId}/sections` | Yes | List sections of a test |
| POST | `/api/teacher/tests/{testId}/sections` | Yes | Add a section to a test |
| PUT | `/api/teacher/tests/{testId}/sections/{sId}` | Yes | Update a section |
| DELETE | `/api/teacher/tests/{testId}/sections/{sId}` | Yes | Delete a section |
| POST | `/api/teacher/tests/{testId}/questions` | Yes | Add a question to a section |
| PUT | `/api/teacher/tests/{testId}/questions/{qId}` | Yes | Update a question |
| DELETE | `/api/teacher/tests/{testId}/questions/{qId}` | Yes | Delete a question |
| GET | `/api/teacher/tests/{testId}/submissions` | Yes | List submitted submissions for a test |
| GET | `/api/teacher/tests/{testId}/submissions/{sId}` | Yes | Get a single test submission with responses |
| PATCH | `/api/teacher/tests/{testId}/submissions/{sId}/grade` | Yes | Grade a test submission |
| PATCH | `/api/teacher/tests/{testId}/submissions/{sId}/grade-responses` | Yes | Grade individual question responses |
| POST | `/api/teacher/materials/setup` | Yes | Create GCS folder structure for the teacher |
| GET | `/api/teacher/materials` | Yes | List own materials + all common-folder materials |
| POST | `/api/teacher/materials/upload` | Yes | Upload a file to private or common folder |
| GET | `/api/teacher/materials/{id}` | Yes | Get material details + signed download URL |
| PUT | `/api/teacher/materials/{id}/access` | Yes | Update allowed_users on an owned material |
| DELETE | `/api/teacher/materials/{id}` | Yes | Delete an owned material from GCS and DB |
| POST | `/api/teacher/profile/setup` | Yes | Create GCS folder structure for the teacher |
| GET | `/api/teacher/profile` | Yes | Get own profile (with signed profile picture URL) |
| PUT | `/api/teacher/profile` | Yes | Update profile details (name, email, telephone, etc.) |
| POST | `/api/teacher/profile/change-password` | Yes | Change own password |
| POST | `/api/teacher/profile/picture` | Yes | Upload or replace the teacher's profile picture |
| GET | `/api/teacher/profile/picture` | Yes | Get a signed URL for the teacher's profile picture |
| GET | `/api/teacher/students/performance` | Yes | Get performance data for all students in teacher's groups |
| GET | `/api/teacher/students/{id}/performance` | Yes | Get performance data for a single student (must be in teacher's groups) |
| GET | `/api/teacher/group-announcements` | Yes | List announcements for groups the teacher manages |
| POST | `/api/teacher/group-announcements` | Yes | Create a group announcement |
| GET | `/api/teacher/group-announcements/{id}` | Yes | Get a single announcement |
| PUT | `/api/teacher/group-announcements/{id}` | Yes | Update an announcement |
| DELETE | `/api/teacher/group-announcements/{id}` | Yes | Delete an announcement |

## Teacher User Object

A teacher user has the following shape (returned on login and profile endpoints):

```json
{
  "id": 4,
  "username": "teacher1",
  "email": "teacher1@example.com",
  "telephone": "+31 6 12345678",
  "address": "Main Street 1",
  "street": "Main Street",
  "house_number": "1",
  "city": "Amsterdam",
  "county": "Noord-Holland",
  "country": "Netherlands",
  "occupation": "Mathematics Teacher",
  "languages_taught": "English, Dutch",
  "role": "teacher",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-02-01T00:00:00.000000Z"
}
```

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
| `502` | Upstream service error (e.g. Claude API unreachable) |
