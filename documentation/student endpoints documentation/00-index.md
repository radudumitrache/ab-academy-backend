# AB Academy Student API Documentation

This documentation covers all student-facing API endpoints for the AB Academy platform.

## Table of Contents

1. [Materials](01-materials.md)
2. [Groups](02-groups.md)

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
| POST | `/api/student/login` | No | Log in and receive an access token |
| POST | `/api/student/logout` | Yes | Revoke the current token |
| POST | `/api/student/groups/join` | Yes | Join a group by class code |
| GET | `/api/student/groups/hours` | Yes | Get total course hours |
| GET | `/api/student/materials` | Yes | List materials shared with the student |
| GET | `/api/student/materials/{id}` | Yes | Get material details + signed download URL |

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
| `422` | Validation failed |
