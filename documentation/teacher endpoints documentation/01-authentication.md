# Authentication

Teacher authentication uses Laravel Passport. A successful login returns a Bearer token
that must be included in the `Authorization` header of all protected requests.

Teacher accounts are users with `role = "teacher"` in the `users` table.
They are created and managed by an admin via the admin user management endpoints.

---

## Login

- **URL**: `/api/teacher/login`
- **Method**: `POST`
- **Auth Required**: No
- **Request Body**:
  ```json
  {
    "username": "teacher1",
    "password": "yourpassword"
  }
  ```
- **Field Notes**:
  - `username` (required): the teacher's unique username
  - `password` (required): the teacher's password
- **Success Response** `200`:
  ```json
  {
    "message": "Teacher login successful",
    "user": {
      "id": 4,
      "username": "teacher1",
      "role": "teacher"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
  }
  ```
- **Error Responses**:
  - **401** — wrong username:
    ```json
    {
      "message": "Invalid credentials",
      "errors": {
        "username": ["The provided credentials are incorrect."]
      }
    }
    ```
  - **401** — wrong password:
    ```json
    {
      "message": "Invalid credentials",
      "errors": {
        "password": ["The provided credentials are incorrect."]
      }
    }
    ```
  - **422** — missing fields:
    ```json
    {
      "message": "Validation failed",
      "errors": {
        "username": ["The username field is required."],
        "password": ["The password field is required."]
      }
    }
    ```
  - **500** — server error:
    ```json
    {
      "message": "An error occurred during login",
      "error": "Error details"
    }
    ```

---

## Logout

Revokes the current Bearer token. The token is immediately invalidated and cannot
be used for further requests.

- **URL**: `/api/teacher/logout`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**: none
- **Success Response** `200`:
  ```json
  {
    "message": "Teacher logout successful"
  }
  ```
- **Error Responses**:
  - **500** — server error:
    ```json
    {
      "message": "An error occurred during logout",
      "error": "Error details"
    }
    ```

---

## How to Use the Token

Store the `access_token` returned on login and include it in every subsequent request:

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

If the token is missing or expired, the API returns:

```json
{ "message": "Unauthenticated." }
```

HTTP Status: `401`
