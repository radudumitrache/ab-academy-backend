# Admin — Products & Acquisitions

Admins manage the product catalogue and handle everything after a student pays: granting access, creating SmartBill invoices, and managing the subscription lifecycle.

---

## Full Admin Flow

```
1. Admin creates product  →  POST /api/admin/products
         │
         ▼
2. Student browses & pays  (automatic — EuPlatesc IPN sets status to "paid")
         │
         ▼
3. Admin sees paid acquisitions  →  GET /api/admin/acquisitions?status=paid
         │
         ▼
4. Admin checks payment profile
   ├─ Has observations/invoice_text AND invoice_confirmed = false?
   │     └─ Admin sets invoice_text  →  POST /api/admin/payment-profiles/{id}/set-invoice-text
   └─ No special mentions  →  proceed
         │
         ▼
5. Admin grants access  →  POST /api/admin/acquisitions/{id}/grant-access
   ├─ course product: provide groups_access (student added to groups)
   └─ single product: provide tests_access
         │  acquisition_status → "active"
         ▼
6. Admin creates SmartBill invoice  →  POST /api/admin/acquisitions/{id}/create-invoice
         │
         ▼
7. Admin marks invoice paid  →  POST /api/admin/acquisitions/{id}/mark-invoice-paid
         │
         ▼
8. Student attends sessions, admin monitors attendance
         │
         ▼
9. Admin marks acquisition completed  →  PUT /api/admin/acquisitions/{id}/status
         │  acquisition_status → "completed"
         ▼
10. Student has 1 week to renew. If no renewal:
    Admin sets status "expired"  →  PUT /api/admin/acquisitions/{id}/status
```

---

## Product Management

### List Products

`GET /api/admin/products`

Returns all products including inactive and soft-deleted ones.

**Response** `200`:
```json
{
  "message": "Products retrieved successfully",
  "count": 2,
  "products": [
    {
      "id": 1,
      "type": "course",
      "name": "IELTS Preparation — 20 sessions",
      "description": "Full IELTS Academic prep course.",
      "price_eur": "199.00",
      "is_active": true,
      "details": {
        "number_of_courses": 20
      }
    },
    {
      "id": 2,
      "type": "single",
      "name": "IELTS Mock Test",
      "description": "Full mock exam with teacher feedback.",
      "price_eur": "29.00",
      "is_active": true,
      "details": {
        "teacher_assistance": true,
        "test": { "id": 5, "name": "IELTS Academic Mock — March 2026" }
      }
    }
  ]
}
```

---

### Get Single Product

`GET /api/admin/products/{id}`

**Errors**: `404` if not found.

---

### Create Product

`POST /api/admin/products`

**Common fields**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | Yes | `single` or `course` |
| `name` | string | Yes | |
| `description` | string | No | |
| `price` | decimal | Yes | Always in EUR |
| `is_active` | boolean | No | Defaults to `true` |

**Additional for `single`**:

| Field | Type | Required |
|-------|------|----------|
| `teacher_assistance` | boolean | No (default `false`) |
| `test_id` | integer | No |

**Additional for `course`**:

| Field | Type | Required |
|-------|------|----------|
| `number_of_courses` | integer | Yes |

**Response** `201`: `{ "message": "Product created successfully", "product": { ... } }`

---

### Update Product

`PUT /api/admin/products/{id}`

All fields are optional. `type` cannot be changed.

**Response** `200`: `{ "message": "Product updated successfully", "product": { ... } }`

---

### Delete Product

`DELETE /api/admin/products/{id}`

Soft-deletes the product. Existing acquisitions are unaffected.

**Response** `200`: `{ "message": "Product deleted successfully" }`

---

## Acquisition Management

### List Acquisitions

`GET /api/admin/acquisitions`

Returns all acquisitions across all students. Supports query filters.

**Query Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `student_id` | integer | Filter by student |
| `status` | string | Filter by `acquisition_status` |
| `product_id` | integer | Filter by product |
| `needs_groups` | boolean (`1`) | Active acquisitions with no groups assigned yet — quick view for unassigned course purchases |

**Response** `200`:
```json
{
  "message": "Acquisitions retrieved successfully",
  "count": 1,
  "acquisitions": [
    {
      "id": 3,
      "student": { "id": 7, "username": "maria.ionescu", "email": "maria@example.com" },
      "product": { "id": 1, "name": "IELTS Preparation — 20 sessions", "type": "course" },
      "payment_profile": {
        "id": 2,
        "nickname": "Firma mea",
        "type": "company",
        "currency": "RON",
        "observations": "Factura pe firma",
        "invoice_text": null,
        "invoice_confirmed": false
      },
      "amount_paid": "985.05",
      "currency": "RON",
      "acquisition_status": "paid",
      "acquisition_date": "2026-03-15",
      "completion_date": null,
      "is_completed": false,
      "invoice_series": null,
      "invoice_number": null,
      "groups_access": null,
      "tests_access": null,
      "acquisition_notes": null,
      "renewed_from_id": null,
      "paid_at": "2026-03-15T10:05:00.000000Z",
      "created_at": "2026-03-15T10:00:00.000000Z"
    }
  ]
}
```

> **Tip**: Check `payment_profile.invoice_confirmed` — if `false` and `observations` or `invoice_text` is set, you must confirm the profile before creating a SmartBill invoice. See [23-payment-profiles.md](23-payment-profiles.md).

---

### Get Single Acquisition

`GET /api/admin/acquisitions/{id}`

Returns the acquisition with full payment profile billing details (physical person or company fields included), useful when preparing the invoice.

**Errors**: `404` if not found.

---

### Grant Access

`POST /api/admin/acquisitions/{id}/grant-access`

Grants the student access to groups or tests and marks the acquisition `active`. Only works on acquisitions with status `paid`.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `groups_access` | array of integers | No | Group IDs (course products) |
| `tests_access` | array of integers | No | Test IDs (single products) |
| `invoice_series` | string | No | Optionally record series at this step |
| `invoice_number` | string | No | Optionally record number at this step |
| `acquisition_notes` | string | No | Internal notes |

**Response** `200`: `{ "message": "Access granted successfully", "acquisition": { ... } }`

**Errors**: `404` if not found, `422` if not in `paid` status.

---

### Create SmartBill Invoice

`POST /api/admin/acquisitions/{id}/create-invoice`

Creates a SmartBill invoice for the acquisition using the payment profile's billing details. The invoice product line uses `invoice_text` from the profile (if set), otherwise falls back to the product name.

**Invoice confirmation gate**: If the payment profile has `observations` or `invoice_text` and `invoice_confirmed = false`, this returns `422` with `needs_confirmation: true`. First call `POST /api/admin/payment-profiles/{id}/set-invoice-text`.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `series` | string | Yes | SmartBill invoice series (e.g. `"AB"`) |

**Response** `200`:
```json
{
  "message": "Invoice created successfully",
  "invoice_series": "AB",
  "invoice_number": "INV-000042",
  "acquisition": { ... }
}
```

**Error — confirmation required** `422`:
```json
{
  "message": "This payment profile has billing mentions that require admin confirmation...",
  "profile_id": 2,
  "needs_confirmation": true
}
```

**Error — already invoiced** `409`:
```json
{
  "message": "An invoice has already been created for this acquisition",
  "invoice_series": "AB",
  "invoice_number": "INV-000042"
}
```

**Errors**: `404` not found, `422` wrong status or confirmation missing, `502` SmartBill API failure.

---

### Mark Invoice Paid in SmartBill

`POST /api/admin/acquisitions/{id}/mark-invoice-paid`

Sends a "mark as paid" (Card payment) to SmartBill for the acquisition's invoice.

**Response** `200`: `{ "message": "Invoice marked as paid in SmartBill" }`

**Errors**: `404` not found, `422` no invoice exists yet, `502` SmartBill failure.

---

### Update Acquisition Status

`PUT /api/admin/acquisitions/{id}/status`

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `acquisition_status` | string | Yes | `pending_payment` / `paid` / `active` / `completed` / `cancelled` / `expired` |
| `completion_date` | date | No | |
| `is_completed` | boolean | No | |
| `acquisition_notes` | string | No | |

**Response** `200`: `{ "message": "Acquisition updated successfully", "acquisition": { ... } }`

**Errors**: `404` not found, `422` validation failure.

---

## Acquisition Status Reference

| Status | Meaning | Admin action |
|--------|---------|--------------|
| `pending_payment` | EuPlatesc checkout started | Wait for IPN |
| `paid` | Payment confirmed | Check profile → grant access → create invoice |
| `active` | Access granted, student attending | Monitor, mark complete when done |
| `completed` | All sessions done | Student has 1 week to renew |
| `expired` | Grace period elapsed, no renewal | Student removed from group |
| `cancelled` | Cancelled manually | — |

---

## Related docs

- [23-payment-profiles.md](23-payment-profiles.md) — invoice_text flow, profile confirmation
- [24-acquisitions-invoicing.md](24-acquisitions-invoicing.md) — detailed flow diagram
- [25-euplatesc-transactions.md](25-euplatesc-transactions.md) — EuPlatesc payment history
