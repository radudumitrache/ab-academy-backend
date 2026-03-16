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
(adds student to groups / assigns tests)
      │  Acquisition status: "active"
      ▼
Admin checks payment profile for billing mentions
      │
      ├─ Profile has observations/invoice_text AND not yet confirmed?
      │     └─ POST /api/admin/payment-profiles/{id}/set-invoice-text  (first time only)
      │
      ▼
Admin creates SmartBill invoice  →  POST /api/admin/acquisitions/{id}/create-invoice
      │
      ▼
Admin marks invoice paid in SmartBill  →  POST /api/admin/acquisitions/{id}/mark-invoice-paid
      │
      ▼
Student attends sessions → Admin monitors attendance
      │
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

---

### Get Single Acquisition

`GET /api/admin/acquisitions/{id}`

Returns the acquisition with full payment profile billing details (useful when preparing the invoice).

**Errors**: `404` if not found.

---

### Grant Access

`POST /api/admin/acquisitions/{id}/grant-access`

Assigns groups or tests to the student and marks the acquisition `active`. Only works on acquisitions with status `paid`.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `groups_access` | array of integers | No | Group IDs (for course products) |
| `tests_access` | array of integers | No | Test IDs (for single products) |
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

`POST /api/admin/acquisitions/{id}/create-invoice`

Creates a SmartBill invoice for the acquisition. The invoice line description is taken from the payment profile's `invoice_text` (if set), otherwise falls back to the product name.

**Pre-condition**: If the payment profile has `observations` or `invoice_text` and `invoice_confirmed = false`, this endpoint returns `422` with `needs_confirmation: true`. The admin must set `invoice_text` and confirm the profile first.

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

### Mark Invoice as Paid in SmartBill

`POST /api/admin/acquisitions/{id}/mark-invoice-paid`

Sends a "mark as paid" request to SmartBill for the acquisition's invoice. Should be called after EuPlatesc confirms payment.

**Response** `200`:
```json
{
  "message": "Invoice marked as paid in SmartBill"
}
```

**Errors**: `404` if not found, `422` if no invoice exists yet, `502` if SmartBill API fails.

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
