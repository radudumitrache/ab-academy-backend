# Invoices

This section covers the API endpoints for managing invoices in the AB Academy platform.

## List All Invoices

- **URL**: `/api/admin/invoices`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Invoices retrieved successfully",
    "invoices": [
      {
        "id": 1,
        "title": "Course Payment",
        "series": "INV",
        "number": "000001",
        "student_id": 5,
        "value": "499.99",
        "currency": "EUR",
        "due_date": "2026-03-15",
        "status": "issued",
        "created_at": "2026-02-21T10:00:00.000000Z",
        "updated_at": "2026-02-21T10:00:00.000000Z",
        "deleted_at": null,
        "student": {
          "id": 5,
          "username": "student1",
          "email": "student1@example.com",
          "role": "student"
        }
      },
      {
        "id": 2,
        "title": "Exam Fee",
        "series": "INV",
        "number": "000002",
        "student_id": 6,
        "value": "50.00",
        "currency": "RON",
        "due_date": "2026-03-01",
        "status": "paid",
        "created_at": "2026-02-21T11:00:00.000000Z",
        "updated_at": "2026-02-21T11:30:00.000000Z",
        "deleted_at": null,
        "student": {
          "id": 6,
          "username": "student2",
          "email": "student2@example.com",
          "role": "student"
        }
      }
    ]
  }
  ```

## Create Invoice

- **URL**: `/api/admin/invoices`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Course Materials",
    "series": "INV",
    "student_id": 5,
    "value": 75.50,
    "currency": "EUR",
    "due_date": "2026-04-01",
    "status": "draft"
  }
  ```
  Note: The `number` field is automatically generated based on the series.

- **Success Response**:
  ```json
  {
    "message": "Invoice created successfully",
    "invoice": {
      "id": 3,
      "title": "Course Materials",
      "series": "INV",
      "number": "000003",
      "student_id": 5,
      "value": "75.50",
      "currency": "EUR",
      "due_date": "2026-04-01",
      "status": "draft",
      "created_at": "2026-02-21T12:00:00.000000Z",
      "updated_at": "2026-02-21T12:00:00.000000Z",
      "deleted_at": null,
      "student": {
        "id": 5,
        "username": "student1",
        "email": "student1@example.com",
        "role": "student"
      }
    }
  }
  ```

## Get Invoice Details

- **URL**: `/api/admin/invoices/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Invoice retrieved successfully",
    "invoice": {
      "id": 1,
      "title": "Course Payment",
      "series": "INV",
      "number": "000001",
      "student_id": 5,
      "value": "499.99",
      "currency": "EUR",
      "due_date": "2026-03-15",
      "status": "issued",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "updated_at": "2026-02-21T10:00:00.000000Z",
      "deleted_at": null,
      "student": {
        "id": 5,
        "username": "student1",
        "email": "student1@example.com",
        "role": "student"
      }
    }
  }
  ```

## Update Invoice

- **URL**: `/api/admin/invoices/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Course Payment",
    "value": 525.00,
    "due_date": "2026-03-20",
    "status": "issued"
  }
  ```
  Note: The `series` and `number` fields cannot be updated.

- **Success Response**:
  ```json
  {
    "message": "Invoice updated successfully",
    "invoice": {
      "id": 1,
      "title": "Updated Course Payment",
      "series": "INV",
      "number": "000001",
      "student_id": 5,
      "value": "525.00",
      "currency": "EUR",
      "due_date": "2026-03-20",
      "status": "issued",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "updated_at": "2026-02-21T13:00:00.000000Z",
      "deleted_at": null,
      "student": {
        "id": 5,
        "username": "student1",
        "email": "student1@example.com",
        "role": "student"
      }
    }
  }
  ```

## Delete Invoice

- **URL**: `/api/admin/invoices/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Invoice deleted successfully"
  }
  ```

## Update Invoice Status

- **URL**: `/api/admin/invoices/{id}/status`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "status": "paid"
  }
  ```
  Note: Valid status values are: `draft`, `issued`, `paid`, `overdue`, `cancelled`.

- **Success Response**:
  ```json
  {
    "message": "Invoice status updated successfully",
    "invoice": {
      "id": 1,
      "title": "Updated Course Payment",
      "series": "INV",
      "number": "000001",
      "student_id": 5,
      "value": "525.00",
      "currency": "EUR",
      "due_date": "2026-03-20",
      "status": "paid",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "updated_at": "2026-02-21T14:00:00.000000Z",
      "deleted_at": null
    }
  }
  ```

## Get Student Invoices

- **URL**: `/api/admin/students/{id}/invoices`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the student

- **Success Response**:
  ```json
  {
    "message": "Student invoices retrieved successfully",
    "student_id": 5,
    "invoices": [
      {
        "id": 1,
        "title": "Updated Course Payment",
        "series": "INV",
        "number": "000001",
        "student_id": 5,
        "value": "525.00",
        "currency": "EUR",
        "due_date": "2026-03-20",
        "status": "paid",
        "created_at": "2026-02-21T10:00:00.000000Z",
        "updated_at": "2026-02-21T14:00:00.000000Z",
        "deleted_at": null
      },
      {
        "id": 3,
        "title": "Course Materials",
        "series": "INV",
        "number": "000003",
        "student_id": 5,
        "value": "75.50",
        "currency": "EUR",
        "due_date": "2026-04-01",
        "status": "draft",
        "created_at": "2026-02-21T12:00:00.000000Z",
        "updated_at": "2026-02-21T12:00:00.000000Z",
        "deleted_at": null
      }
    ]
  }
  ```
