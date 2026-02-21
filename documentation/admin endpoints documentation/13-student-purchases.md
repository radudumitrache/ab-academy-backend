# Student Purchases

This section covers the API endpoints for managing student purchases in the AB Academy platform.

## Get Student Purchases

Retrieves all products purchased by a specific student.

- **URL**: `/api/admin/students/{id}/purchases`
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
    "message": "Student purchases retrieved successfully",
    "student_id": 5,
    "purchases": [
      {
        "id": 1,
        "title": "Mathematics Textbook",
        "description": "Comprehensive textbook covering algebra, calculus, and statistics",
        "price": "49.99",
        "created_at": "2026-02-21T10:00:00.000000Z",
        "updated_at": "2026-02-21T10:00:00.000000Z",
        "deleted_at": null,
        "pivot": {
          "student_id": 5,
          "product_id": 1,
          "purchased_at": "2026-02-21T14:30:00.000000Z",
          "purchase_price": "49.99",
          "created_at": "2026-02-21T14:30:00.000000Z",
          "updated_at": "2026-02-21T14:30:00.000000Z"
        }
      },
      {
        "id": 2,
        "title": "Physics Lab Kit",
        "description": "Hands-on physics experiments kit for students",
        "price": "129.99",
        "created_at": "2026-02-21T11:00:00.000000Z",
        "updated_at": "2026-02-21T11:00:00.000000Z",
        "deleted_at": null,
        "pivot": {
          "student_id": 5,
          "product_id": 2,
          "purchased_at": "2026-02-21T15:45:00.000000Z",
          "purchase_price": "129.99",
          "created_at": "2026-02-21T15:45:00.000000Z",
          "updated_at": "2026-02-21T15:45:00.000000Z"
        }
      }
    ]
  }
  ```

## Record Student Purchase

Records a new product purchase for a student.

- **URL**: `/api/admin/students/{id}/purchases`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `id`: The ID of the student
- **Request Body**:
  ```json
  {
    "product_id": 3,
    "purchased_at": "2026-02-21T16:30:00.000000Z"
  }
  ```
  Note: The `purchased_at` field is optional. If not provided, the current time will be used.

- **Success Response**:
  ```json
  {
    "message": "Purchase recorded successfully",
    "purchase": {
      "student_id": 5,
      "product_id": 3,
      "product_title": "Chemistry Textbook",
      "purchased_at": "2026-02-21T16:30:00.000000Z",
      "purchase_price": "59.99"
    }
  }
  ```

## Remove Student Purchase

Removes a purchase record for a student.

- **URL**: `/api/admin/students/{studentId}/purchases/{productId}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **URL Parameters**:
  - `studentId`: The ID of the student
  - `productId`: The ID of the product

- **Success Response**:
  ```json
  {
    "message": "Purchase record removed successfully"
  }
  ```
