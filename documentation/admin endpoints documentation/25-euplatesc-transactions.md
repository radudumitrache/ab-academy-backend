# Admin — EuPlatesc Transactions

Admins can view all payments made through EuPlatesc for product acquisitions. Each record corresponds to a `ProductAcquisition` that went through the checkout flow, showing who paid, how much, for which product, and the current status.

> Routes are registered in `routes/admin/products.php` under the `/api/admin` prefix with admin auth middleware.

---

## Payment Flow Overview

1. Student initiates checkout → `EuPlatescService::initiateProductPayment()` generates a unique `order_key`, stores it on the `ProductAcquisition`, and returns an HTML auto-submit form redirecting the browser to EuPlatesc.
2. EuPlatesc processes the card and POSTs back to both:
   - `POST /api/euplatesc/notify` — server-to-server IPN (no auth, no CSRF)
   - `POST /api/euplatesc/return` — user browser return (no auth, no CSRF)
3. Both callbacks are handled by `EuPlatescController::handleCallback()`, which resolves the `order_key` to a `ProductAcquisition`, updates `ep_id`, `payment_status_message`, `paid_at`, and sets `acquisition_status` to `paid` (action=0) or leaves it as `pending_payment`.
4. The browser is then redirected to `{FRONTEND_URL}/payment/success?acquisition_id={id}` or `.../payment/failed?acquisition_id={id}`.

---

## List Transactions

`GET /api/admin/euplatesc-transactions`

Returns all `ProductAcquisition` records that have a non-null `order_key` (i.e. went through EuPlatesc), ordered by `paid_at` descending, then `created_at` descending.

**Query Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `student_id` | integer | Filter by student |
| `status` | string | Filter by `acquisition_status` (`pending_payment`, `paid`, `active`, `completed`, `cancelled`, `expired`) |
| `currency` | string | `EUR` or `RON` (case-insensitive, stored uppercase) |
| `from` | date | Filter by `created_at` >= date, format `YYYY-MM-DD` |
| `to` | date | Filter by `created_at` <= date, format `YYYY-MM-DD` |

> **Note on `total_paid`**: Computed from the same filtered query but further restricted to records where `acquisition_status != 'pending_payment'` AND `paid_at IS NOT NULL`. It reflects the actual collected amount within the applied filters.

**Response** `200`:
```json
{
  "message": "Transactions retrieved successfully",
  "count": 3,
  "total_paid": "2955.15",
  "transactions": [
    {
      "acquisition_id": 5,
      "order_key": "AB7XY2Z",
      "ep_id": "EP123456",
      "student": {
        "id": 7,
        "username": "maria.ionescu",
        "email": "maria@example.com"
      },
      "product": {
        "id": 1,
        "name": "IELTS Preparation — 20 sessions",
        "type": "course"
      },
      "payment_profile": {
        "id": 3,
        "nickname": "Firma mea",
        "type": "company"
      },
      "amount_paid": "985.05",
      "currency": "RON",
      "acquisition_status": "active",
      "payment_status_message": "Approved",
      "paid_at": "2026-03-15T10:05:22.000000Z",
      "invoice_series": "AB",
      "invoice_number": "INV-000042",
      "created_at": "2026-03-15T10:00:00.000000Z"
    }
  ]
}
```

**Field notes**:
- `ep_id` — EuPlatesc's internal transaction ID; populated after the IPN/return callback fires. `null` if payment not yet processed.
- `payment_status_message` — Raw message from EuPlatesc (e.g. `"Approved"`, `"Card declined"`).
- `acquisition_status` — Lifecycle: `pending_payment` → `paid` → `active` → `completed` (or `cancelled`/`expired`). EuPlatesc only moves it from `pending_payment` to `paid`; subsequent transitions are done by admin.
- `student`, `product`, `payment_profile` — `null` if the related record has been deleted.

---

## Check Live Status from EuPlatesc

`GET /api/admin/euplatesc-transactions/{id}/check-status`

Queries the EuPlatesc back-end API (`POST https://manager.euplatesc.ro/v3/index.php?action=ws`) for the live status of a specific transaction using the `ep_id` stored on the acquisition.

> `{id}` is the **acquisition ID** (`ProductAcquisition.id`), not the `ep_id`.

The request to EuPlatesc is HMAC-signed (`method=check_status`, `mid`, `epid`, `timestamp`, `nonce`, `fp_hash`).

**Response** `200`:
```json
{
  "message": "Status retrieved from EuPlatesc",
  "ep_id": "EP123456",
  "ep_status": [
    {
      "action": "0",
      "message": "Approved",
      "amount": "985.05",
      "currency": "RON"
    }
  ],
  "acquisition": {
    "acquisition_id": 5,
    "order_key": "AB7XY2Z",
    "ep_id": "EP123456",
    "student": { ... },
    "product": { ... },
    "payment_profile": { ... },
    "amount_paid": "985.05",
    "currency": "RON",
    "acquisition_status": "active",
    "payment_status_message": "Approved",
    "paid_at": "2026-03-15T10:05:22.000000Z",
    "invoice_series": "AB",
    "invoice_number": "INV-000042",
    "created_at": "2026-03-15T10:00:00.000000Z"
  }
}
```

> `ep_status` is an array decoded from EuPlatesc's `success` JSON field. `action = "0"` means approved; any other value indicates failure.

**Errors**:

| Status | Condition |
|--------|-----------|
| `404` | Acquisition not found |
| `422` | Acquisition has no `ep_id` yet (EuPlatesc callback not received) |
| `502` | EuPlatesc API unreachable, returned no output, or returned a response without a `success` field |

---

## Key Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/EuPlatescTransactionController.php` | `index` and `checkStatus` actions |
| `app/Services/EuPlatescService.php` | `initiateProductPayment()`, `checkStatus()`, `generateHmac()` |
| `app/Http/Controllers/EuPlatescController.php` | IPN (`/api/euplatesc/notify`) and return (`/api/euplatesc/return`) callbacks |
| `routes/admin/products.php` | Route registration for admin transaction endpoints |
| `routes/euplatesc.php` | Route registration for IPN/return webhook endpoints |
| `app/Models/ProductAcquisition.php` | Primary model for these records |
