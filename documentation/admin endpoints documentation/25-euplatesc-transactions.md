# Admin — EuPlatesc Transactions

Admins can view all payments made through EuPlatesc for product acquisitions. Each record corresponds to a `product_acquisition` that went through the checkout flow, showing who paid, how much, for which product, and the current status.

---

## List Transactions

`GET /api/admin/euplatesc-transactions`

Returns all product acquisition records that have an EuPlatesc `order_key`, ordered by payment date descending.

**Query Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `student_id` | integer | Filter by student |
| `status` | string | Filter by `acquisition_status` (`paid`, `active`, `pending_payment`, etc.) |
| `currency` | string | `EUR` or `RON` |
| `from` | date | Start date (inclusive), format `YYYY-MM-DD` |
| `to` | date | End date (inclusive), format `YYYY-MM-DD` |

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

> `total_paid` is the sum of `amount_paid` for all transactions with a `paid_at` timestamp, regardless of filters applied to the `transactions` list. Use the date/currency filters to scope the total.

---

## Check Live Status from EuPlatesc

`GET /api/admin/euplatesc-transactions/{id}/check-status`

Queries the EuPlatesc back-end API for the live status of a specific transaction using the `ep_id` stored on the acquisition.

> The `{id}` here is the **acquisition ID**, not the `ep_id`.

**Response** `200`:
```json
{
  "message": "Status retrieved from EuPlatesc",
  "ep_id": "EP123456",
  "ep_status": {
    "action": "0",
    "message": "Approved",
    "amount": "985.05",
    "currency": "RON"
  },
  "acquisition": { ... }
}
```

**Errors**:
- `404` — acquisition not found
- `422` — acquisition has no `ep_id` yet (payment not yet processed by EuPlatesc)
- `502` — EuPlatesc API unreachable or returned an error
