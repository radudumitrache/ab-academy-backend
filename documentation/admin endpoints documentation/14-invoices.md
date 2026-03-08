# Invoices

Invoices are created by the admin and assigned to students. They are paid by students through the EuPlatesc hosted checkout. Each payment attempt is tracked in the `invoice_payments` table.

## Invoice Statuses

| Status | Meaning |
|--------|---------|
| `draft` | Not yet issued — students cannot pay |
| `issued` | Issued and awaiting payment |
| `overdue` | Past due date, still unpaid |
| `paid` | Automatically set when EuPlatesc confirms payment |
| `cancelled` | Cancelled — students cannot pay |

---

## List All Invoices

- **URL**: `GET /api/admin/invoices`
- **Auth Required**: Yes

**Response** `200`:
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
    }
  ]
}
```

---

## Create Invoice

- **URL**: `POST /api/admin/invoices`
- **Auth Required**: Yes

**Request Body**:
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

| Field | Required | Notes |
|-------|----------|-------|
| `title` | Yes | string |
| `series` | Yes | string, max 10 chars |
| `student_id` | Yes | must be a valid student user ID |
| `value` | Yes | numeric, min 0 |
| `currency` | Yes | `EUR` or `RON` |
| `due_date` | Yes | `YYYY-MM-DD` |
| `status` | No | defaults to `draft` |

The `number` is auto-generated (zero-padded sequence per series).

**Response** `201`:
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

---

## Get Invoice Details

- **URL**: `GET /api/admin/invoices/{id}`
- **Auth Required**: Yes

**Response** `200`:
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

---

## Update Invoice

- **URL**: `PUT /api/admin/invoices/{id}`
- **Auth Required**: Yes

All fields optional. `series` and `number` are immutable.

**Request Body**:
```json
{
  "title": "Updated Course Payment",
  "value": 525.00,
  "currency": "EUR",
  "due_date": "2026-03-20",
  "status": "issued"
}
```

**Response** `200` with updated invoice object.

---

## Delete Invoice

- **URL**: `DELETE /api/admin/invoices/{id}`
- **Auth Required**: Yes

Soft-deletes the invoice.

**Response** `200`:
```json
{ "message": "Invoice deleted successfully" }
```

---

## Update Invoice Status

- **URL**: `PUT /api/admin/invoices/{id}/status`
- **Auth Required**: Yes

**Request Body**:
```json
{ "status": "paid" }
```

Valid values: `draft`, `issued`, `paid`, `overdue`, `cancelled`.

> Note: Status is also set automatically to `paid` when EuPlatesc confirms a successful payment via the IPN webhook.

**Response** `200`:
```json
{
  "message": "Invoice status updated successfully",
  "invoice": { ... }
}
```

---

## Get Student Invoices

- **URL**: `GET /api/admin/students/{id}/invoices`
- **Auth Required**: Yes

Returns all invoices for a specific student.

**Response** `200`:
```json
{
  "message": "Student invoices retrieved successfully",
  "student_id": 5,
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
      "status": "paid",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "updated_at": "2026-02-21T14:00:00.000000Z",
      "deleted_at": null
    }
  ]
}
```

---

## Payment Tracking (`invoice_payments`)

Each time a student initiates checkout via `POST /api/student/invoices/{id}/pay`, a record is created in `invoice_payments`.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int | Primary key |
| `invoice_id` | int | FK to `invoices` |
| `student_id` | int | FK to `users` |
| `order_key` | string | Unique 7-char ID sent to EuPlatesc as `orderId` |
| `amount` | decimal | Amount charged |
| `currency` | string | `EUR` or `RON` |
| `status` | string | `pending` / `approved` / `failed` |
| `status_code` | int | EuPlatesc `action` field (`0` = approved) |
| `status_message` | string | EuPlatesc `message` field (e.g. `"Approved"`) |
| `ep_id` | string | EuPlatesc internal transaction ID |
| `paid_at` | timestamp | Set when EuPlatesc confirms approval |
| `created_at` | timestamp | When the checkout was initiated |

When `status = approved`, the linked invoice is automatically set to `paid`.
