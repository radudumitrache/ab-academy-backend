# AB Academy Teacher API Documentation

This documentation covers all teacher-facing API endpoints for the AB Academy platform.

## Table of Contents

1. [Authentication](01-authentication.md)
2. [Dashboard](02-dashboard.md)
3. [AI Assistant](03-ai-assistant.md)

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
| GET | `/api/teacher/dashboard` | Yes | Teacher dashboard placeholder |
| POST | `/api/teacher/ai-assistant/translate` | Yes | Translate text via Claude AI |

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
