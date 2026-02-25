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
| GET | `/api/teacher/groups/schedule/options` | Yes | Get allowed schedule days and times |
| GET | `/api/teacher/groups` | Yes | List all groups owned by the teacher |
| POST | `/api/teacher/groups` | Yes | Create a new group |
| GET | `/api/teacher/groups/{id}` | Yes | Get a single group |
| PUT | `/api/teacher/groups/{id}` | Yes | Update a group |
| DELETE | `/api/teacher/groups/{id}` | Yes | Delete a group (soft delete) |
| POST | `/api/teacher/groups/{id}/students` | Yes | Add a student to a group by ID |
| POST | `/api/teacher/groups/{id}/students/by-username` | Yes | Add a student to a group by username |
| DELETE | `/api/teacher/groups/{groupId}/students/{studentId}` | Yes | Remove a student from a group |
| GET | `/api/teacher/exams` | Yes | List all exams |
| POST | `/api/teacher/exams` | Yes | Create a new exam |
| GET | `/api/teacher/exams/{id}` | Yes | Get a single exam |
| POST | `/api/teacher/exams/{id}/students` | Yes | Enroll students in an exam |
| DELETE | `/api/teacher/exams/{examId}/students/{studentId}` | Yes | Remove a student from an exam |
| GET | `/api/teacher/events` | Yes | List events where teacher is organizer or invited |
| POST | `/api/teacher/events` | Yes | Create a new event |
| GET | `/api/teacher/events/{id}` | Yes | Get a single event |
| PUT | `/api/teacher/events/{id}` | Yes | Update an event (organizer only) |
| DELETE | `/api/teacher/events/{id}` | Yes | Delete an event (organizer only) |
| PUT | `/api/teacher/events/{id}/attendance` | Yes | Record guest attendance (organizer only) |
| POST | `/api/teacher/events/{id}/guests/by-username` | Yes | Add guests to an event by username (organizer only) |
| GET | `/api/teacher/notifications` | Yes | List own notifications (filterable) |
| PUT | `/api/teacher/notifications/seen-all` | Yes | Mark all notifications as seen |
| PUT | `/api/teacher/notifications/{id}/seen` | Yes | Mark a single notification as seen |
| DELETE | `/api/teacher/notifications/{id}` | Yes | Delete a notification |

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
