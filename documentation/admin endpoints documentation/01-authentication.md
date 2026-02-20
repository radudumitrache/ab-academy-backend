# Authentication

The API supports two types of admin authentication: regular admin and super admin.

## Login

- **URL**: `/api/admin/login`
- **Method**: `POST`
- **Auth Required**: No
- **Request Body**:
  ```json
  {
    "username": "admin_username",
    "password": "admin_password"
  }
  ```
- **Success Response (Regular Admin)**:
  ```json
  {
    "message": "Admin login successful",
    "user": {
      "id": 1,
      "username": "admin_username",
      "role": "admin"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
  }
  ```
- **Success Response (Super Admin)**:
  ```json
  {
    "message": "Super admin login successful",
    "user": {
      "id": 0,
      "username": "super_admin_username",
      "role": "super_admin"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
  }
  ```
- **Error Responses**:
  - **401 Unauthorized**:
    ```json
    {
      "message": "Invalid credentials",
      "errors": {
        "username": ["The provided credentials are incorrect."]
      }
    }
    ```
  - **422 Validation Error**:
    ```json
    {
      "message": "Validation failed",
      "errors": {
        "username": ["The username field is required."]
      }
    }
    ```
  - **500 Server Error**:
    ```json
    {
      "message": "An error occurred during login",
      "error": "Error message details"
    }
    ```

## Super Admin Authentication

Super Admin credentials are defined in the environment variables:
- `SUPER_ADMIN_USERNAME`: Username for the super admin
- `SUPER_ADMIN_PASSWORD`: Password for the super admin

When these credentials are used, the system bypasses the database check and generates a special token with elevated privileges.

## Admin User Creation

Admin users can be created using the `admin:create` Artisan command:

```bash
# Interactive mode
php artisan admin:create

# With options
php artisan admin:create --username=admin1 --password=securepassword

# Create a super admin
php artisan admin:create --username=superadmin --password=securepassword --super
```

The command validates that:
- Username is at least 3 characters and unique
- Password is at least 8 characters

## Logout

- **URL**: `/api/admin/logout`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Admin logout successful"
  }
  ```
- **Error Response**:
  - **500 Server Error**:
    ```json
    {
      "message": "An error occurred during logout",
      "error": "Error message details"
    }
    ```
