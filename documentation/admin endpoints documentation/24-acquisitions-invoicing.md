# Admin — Acquisitions & Invoicing Flow

This document describes the full admin workflow from a student paying for a product through to the SmartBill invoice being generated and marked as paid.

---

## Full Admin Flow

```
[Option A — EuPlatesc online payment]
Student pays  →  Acquisition status: "paid"
      │
[Option B — Cash / bank transfer]
Admin creates manually  →  POST /api/admin/acquisitions  (set status: "paid")
      │
      ▼
Admin reviews payment  →  GET /api/admin/acquisitions?status=paid
      │
      ▼
Admin grants access  →  POST /api/admin/acquisitions/{id}/grant-access
(adds student to groups / assigns tests; remaining_courses set from product)
      │  Acquisition status: "active"
      ▼
Admin checks payment profile for billing mentions
      │
      ├─ Profile has observations/invoice_text AND not yet confirmed?
      │     └─ POST /api/admin/payment-profiles/{id}/set-invoice-text  (first time only)
      │
      ▼
Admin creates SmartBill invoice  →  POST /api/admin/acquisitions/create-invoice
      │
      ▼
Admin marks invoice paid in SmartBill  →  POST /api/admin/acquisitions/{id}/mark-invoice-paid
      │
      ▼
Student attends sessions → remaining_courses decrements on each presence/unmotivated absence
      │  When remaining_courses = 0: admins notified, student removed from group(s)
      │  Admin can adjust at any time  →  PATCH /api/admin/acquisitions/{id}/remaining-courses
      ▼
Admin marks acquisition completed  →  PUT /api/admin/acquisitions/{id}/status
      │  acquisition_status: "completed"
      ▼
Student has 1 week to renew before being removed from group
      │
      ├─ Student renews  →  POST /api/student/acquisitions/{id}/renew
      │     └─ New acquisition created → same flow repeats from "paid"
      └─ No renewal  →  Admin sets status "expired"
```

---

## Acquisition Endpoints

### Create Acquisition (Manual)

`POST /api/admin/acquisitions`

Creates an acquisition directly on behalf of a student — used for cash payments, bank transfers, or any flow that bypasses EuPlatesc. You can create it in any starting status (e.g. `paid` if payment is already confirmed).

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `student_id` | integer | Yes | ID of the student |
| `product_id` | integer | Yes | ID of the product being purchased |
| `payment_profile_id` | integer | No | Student's payment profile to use for invoicing |
| `amount_paid` | numeric | Yes | Amount paid (≥ 0) |
| `currency` | string | Yes | `RON` or `EUR` |
| `acquisition_status` | string | Yes | Starting status: `pending_payment`, `paid`, `active`, `completed`, `cancelled`, `expired` |
| `acquisition_date` | date | No | Date of acquisition (`YYYY-MM-DD`). Defaults to `null` |
| `acquisition_notes` | string | No | Internal admin notes |
| `groups_access` | array of integers | No | Group IDs to grant immediately (for course products) |
| `tests_access` | array of integers | No | Test IDs to grant immediately (for single products) |

**Response** `201`:
```json
{
  "message": "Acquisition created successfully",
  "acquisition": {
    "id": 12,
    "student": { "id": 7, "username": "maria.ionescu", "email": "maria@example.com" },
    "product": { "id": 1, "name": "IELTS Preparation — 20 sessions", "type": "course" },
    "payment_profile": null,
    "amount_paid": "985.05",
    "currency": "RON",
    "acquisition_status": "paid",
    ...
  }
}
```

**Errors**: `422` if validation fails (e.g. unknown student/product/group/test ID).

---

### List Acquisitions

`GET /api/admin/acquisitions`

**Query Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `student_id` | integer | Filter by student |
| `status` | string | Filter by `acquisition_status` |
| `product_id` | integer | Filter by product |
| `needs_groups` | boolean | If `1`, shows active acquisitions with no groups assigned yet |

**Response** `200`:
```json
{
  "message": "Acquisitions retrieved successfully",
  "count": 1,
  "acquisitions": [
    {
      "id": 5,
      "student": { "id": 7, "username": "maria.ionescu", "email": "maria@example.com" },
      "product": { "id": 1, "name": "IELTS Preparation — 20 sessions", "type": "course" },
      "payment_profile": {
        "id": 3,
        "nickname": "Firma mea",
        "type": "company",
        "currency": "RON",
        "observations": "Factura pe firma",
        "invoice_text": "Servicii educationale — IELTS",
        "invoice_confirmed": true
      },
      "amount_paid": "985.05",
      "currency": "RON",
      "acquisition_status": "paid",
      "acquisition_date": "2026-03-15",
      "completion_date": null,
      "is_completed": false,
      "remaining_courses": null,
      "invoice_series": null,
      "invoice_number": null,
      "group_id": null,
      "marked_courses": null,
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

---

### Get Single Acquisition

`GET /api/admin/acquisitions/{id}`

Returns the acquisition with full payment profile billing details (useful when preparing the invoice).

**Errors**: `404` if not found.

---

### Grant Access

`POST /api/admin/acquisitions/{id}/grant-access`

Assigns groups or tests to the student and marks the acquisition `active`. Only works on acquisitions with status `paid`.

For course products, `remaining_courses` is automatically initialised from `course_products.number_of_courses` on the first grant (only if not already set).

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `groups_access` | array of integers | No | Group IDs (for course products) |
| `tests_access` | array of integers | No | Test IDs (for single products) |
| `group_id` | integer | No | The specific group this acquisition's sessions are tied to. When set, attendance for this group decrements `remaining_courses` from this acquisition directly, with no ambiguity if the student has multiple active acquisitions. |
| `invoice_series` | string | No | Optionally record invoice series at this step |
| `invoice_number` | string | No | Optionally record invoice number at this step |
| `acquisition_notes` | string | No | Internal admin notes |

**Response** `200`:
```json
{
  "message": "Access granted successfully",
  "acquisition": { ... }
}
```

**Errors**: `404` if not found, `422` if not in `paid` status.

---

### Create SmartBill Invoice

`POST /api/admin/acquisitions/create-invoice`

Creates a SmartBill invoice for the acquisition. The invoice line description is taken from the payment profile's `invoice_text` (if set), otherwise falls back to the product name.

**Pre-condition**: If the payment profile has `observations` or `invoice_text` and `invoice_confirmed = false`, this endpoint returns `422` with `needs_confirmation: true`. The admin must set `invoice_text` and confirm the profile first.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | Yes | ID of the acquisition |
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
  "message": "This payment profile has billing mentions that require admin confirmation before the first invoice can be issued. Please set the invoice_text and confirm the profile first.",
  "profile_id": 3,
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

**Errors**: `404` if not found, `422` if status not `paid`/`active`, `502` if SmartBill API fails.

---

### Download Invoice PDF

`GET /api/admin/acquisitions/{id}/download-invoice`

Downloads the SmartBill invoice PDF for the acquisition.

**Pre-condition**: An invoice must already exist (`invoice_series` and `invoice_number` must be set on the acquisition).

**Response** `200`: Binary PDF file with headers:
- `Content-Type: application/pdf`
- `Content-Disposition: attachment; filename="invoice-{series}-{number}.pdf"`

**Errors**: `404` if not found, `422` if no invoice exists yet, `502` if SmartBill API fails.

---

### Send Invoice to SPV (ANAF e-Factura)

`POST /api/admin/acquisitions/{id}/send-invoice-to-spv`

Submits the SmartBill invoice to SPV (ANAF's e-Factura system) for the acquisition.

**Pre-condition**: An invoice must already exist (`invoice_series` and `invoice_number` must be set on the acquisition).

**Response** `200`:
```json
{
  "message": "Invoice sent to SPV successfully"
}
```

**Errors**: `404` if not found, `422` if no invoice exists yet, `502` if SmartBill API fails.

---

### Mark Invoice as Paid in SmartBill

`POST /api/admin/acquisitions/{id}/mark-invoice-paid`

Sends a "mark as paid" request to SmartBill for the acquisition's invoice.

**Response** `200`:
```json
{
  "message": "Invoice marked as paid in SmartBill"
}
```

**Errors**: `404` if not found, `422` if no invoice exists yet, `502` if SmartBill API fails.

---

### Send Invoice by Email

`POST /api/admin/acquisitions/{id}/send-invoice-email`

Sends the SmartBill invoice for the acquisition to an email address. If no email is provided in the request body, it defaults to the student's email on record.

**Pre-condition**: An invoice must already exist (`invoice_series` and `invoice_number` must be set on the acquisition).

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string (email) | No | Recipient email address. Defaults to the student's email if omitted. |

**Response** `200`:
```json
{
  "message": "Invoice sent by email successfully",
  "email": "maria@example.com"
}
```

**Errors**: `404` if not found, `422` if no invoice exists yet or no email is available, `502` if SmartBill API fails.

---

### Update Product

`PATCH /api/admin/acquisitions/{id}/product`

Change the product linked to an acquisition.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | integer | Yes | ID of the new product |

**Response** `200`:
```json
{
  "message": "Product updated successfully",
  "acquisition": { ... }
}
```

**Errors**: `404` if not found, `422` if validation fails.

---

### Update Marked Courses

`PATCH /api/admin/acquisitions/{id}/marked-courses`

Overwrite the `marked_courses` list for an acquisition. This is the log of sessions that counted toward session consumption, automatically appended by the system whenever attendance is recorded as `present` or `absent`. Admins can edit or remove entries here.

Each entry is a string in the format `"present: YYYY-MM-DD"` or `"absent: YYYY-MM-DD"`, where the date is the `session_date` of the attendance record.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `marked_courses` | array of strings | Yes | Full replacement list of marked course strings |

**Response** `200`:
```json
{
  "message": "Marked courses updated successfully",
  "marked_courses": ["present: 2026-04-01", "absent: 2026-04-08"]
}
```

**Errors**: `404` if not found, `422` if validation fails.

---

### Update Remaining Courses

`PATCH /api/admin/acquisitions/{id}/remaining-courses`

Manually override the number of remaining course sessions for an acquisition. Useful for corrections, top-ups, or resetting after a renewal.

For course acquisitions, `remaining_courses` is automatically initialised from the product's `number_of_courses` when access is granted, and decremented each time the student is marked `present` or `absent` (unmotivated) in the attendance table. When it reaches `0`, all admins are notified and the student is removed from the linked groups.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `remaining_courses` | integer | Yes | New value (≥ 0) |

**Response** `200`:
```json
{
  "message": "Remaining courses updated successfully",
  "remaining_courses": 5
}
```

**Errors**: `404` if not found, `422` if validation fails.

---

### Update Acquisition Status

`PUT /api/admin/acquisitions/{id}/status`

Manually update the lifecycle status of an acquisition.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `acquisition_status` | string | Yes | `pending_payment` / `paid` / `active` / `completed` / `cancelled` / `expired` |
| `completion_date` | date | No | Date of completion |
| `is_completed` | boolean | No | |
| `acquisition_notes` | string | No | |

**Response** `200`:
```json
{
  "message": "Acquisition updated successfully",
  "acquisition": { ... }
}
```

---

### Delete Acquisition

`DELETE /api/admin/acquisitions/{id}`

Permanently deletes an acquisition record. Only acquisitions in a terminal or pre-payment status can be deleted to prevent accidental removal of financial records.

**Allowed statuses**: `pending_payment`, `cancelled`, `expired`

**Response** `200`:
```json
{
  "message": "Acquisition deleted successfully"
}
```

**Error — status not deletable** `422`:
```json
{
  "message": "Only acquisitions with status pending_payment, cancelled, or expired can be deleted",
  "current_status": "active"
}
```

**Errors**: `404` if not found.

---

### Delete EuPlatesc Transaction

`DELETE /api/admin/euplatesc-transactions/{id}`

Permanently deletes an EuPlatesc transaction record (a `ProductAcquisition` that went through EuPlatesc checkout). The same status restriction applies.

**Allowed statuses**: `pending_payment`, `cancelled`, `expired`

**Response** `200`:
```json
{
  "message": "Transaction deleted successfully"
}
```

**Error — status not deletable** `422`:
```json
{
  "message": "Only transactions with status pending_payment, cancelled, or expired can be deleted",
  "current_status": "paid"
}
```

**Errors**: `404` if not found or not an EuPlatesc transaction.

---

## Acquisition Status Reference

| Status | Meaning | Admin action |
|--------|---------|--------------|
| `pending_payment` | EuPlatesc checkout started (or manually created, awaiting payment) | Wait for IPN or confirm manually |
| `paid` | Payment confirmed | Grant access + create invoice |
| `active` | Access granted, student attending | Monitor, mark complete when done |
| `completed` | All sessions done | Student has 1 week to renew |
| `expired` | Grace period elapsed | Remove student from group if not renewed |
| `cancelled` | Cancelled manually | — |

> **Note**: When creating an acquisition manually via `POST /api/admin/acquisitions`, you can set any starting status. For a confirmed cash payment, use `paid` directly and optionally include `groups_access`/`tests_access` to skip the separate grant-access step.
