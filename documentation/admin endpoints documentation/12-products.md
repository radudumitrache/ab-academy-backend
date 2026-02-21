# Products

This section covers the API endpoints for managing products in the AB Academy platform.

## List All Products

- **URL**: `/api/admin/products`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Products retrieved successfully",
    "products": [
      {
        "id": 1,
        "title": "Mathematics Textbook",
        "description": "Comprehensive textbook covering algebra, calculus, and statistics",
        "price": "49.99",
        "created_at": "2026-02-21T10:00:00.000000Z",
        "updated_at": "2026-02-21T10:00:00.000000Z",
        "deleted_at": null
      },
      {
        "id": 2,
        "title": "Physics Lab Kit",
        "description": "Hands-on physics experiments kit for students",
        "price": "129.99",
        "created_at": "2026-02-21T11:00:00.000000Z",
        "updated_at": "2026-02-21T11:00:00.000000Z",
        "deleted_at": null
      }
    ]
  }
  ```

## Create Product

- **URL**: `/api/admin/products`
- **Method**: `POST`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Chemistry Textbook",
    "description": "Comprehensive chemistry textbook for high school students",
    "price": 59.99
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Product created successfully",
    "product": {
      "id": 3,
      "title": "Chemistry Textbook",
      "description": "Comprehensive chemistry textbook for high school students",
      "price": "59.99",
      "created_at": "2026-02-21T12:00:00.000000Z",
      "updated_at": "2026-02-21T12:00:00.000000Z",
      "deleted_at": null
    }
  }
  ```

## Get Product Details

- **URL**: `/api/admin/products/{id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Product retrieved successfully",
    "product": {
      "id": 1,
      "title": "Mathematics Textbook",
      "description": "Comprehensive textbook covering algebra, calculus, and statistics",
      "price": "49.99",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "updated_at": "2026-02-21T10:00:00.000000Z",
      "deleted_at": null
    }
  }
  ```

## Update Product

- **URL**: `/api/admin/products/{id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Request Body**:
  ```json
  {
    "title": "Updated Mathematics Textbook",
    "description": "Updated comprehensive textbook covering algebra, calculus, and statistics",
    "price": 54.99
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Product updated successfully",
    "product": {
      "id": 1,
      "title": "Updated Mathematics Textbook",
      "description": "Updated comprehensive textbook covering algebra, calculus, and statistics",
      "price": "54.99",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "updated_at": "2026-02-21T13:00:00.000000Z",
      "deleted_at": null
    }
  }
  ```

## Delete Product

- **URL**: `/api/admin/products/{id}`
- **Method**: `DELETE`
- **Auth Required**: Yes
- **Headers**:
  ```
  Authorization: Bearer {token}
  ```
- **Success Response**:
  ```json
  {
    "message": "Product deleted successfully"
  }
  ```
