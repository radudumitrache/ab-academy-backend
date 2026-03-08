# Invoices & Payments

Invoices are created by the admin via SmartBill and assigned to students. Students can view their invoices and pay outstanding ones through the EuPlatesc hosted checkout page.

> **Billing details** — EuPlatesc uses the student's profile fields (`street`, `house_number`, `city`, `county`, `country`, `telephone`, `email`) as billing information. Keep these up to date via `PUT /api/student/profile` before initiating a payment.

---

## Invoice Statuses

| Status | Meaning | Can be paid? |
|--------|---------|-------------|
| `draft` | Not yet issued | No |
| `issued` | Awaiting payment | Yes |
| `overdue` | Past due date, still unpaid | Yes |
| `paid` | Payment confirmed by EuPlatesc | No |
| `cancelled` | Cancelled by admin | No |

---

## Payment Attempt Statuses

Each call to `POST /invoices/{id}/pay` creates a new `invoice_payment` record.

| Status | Meaning |
|--------|---------|
| `pending` | Checkout initiated, awaiting EuPlatesc response |
| `approved` | EuPlatesc confirmed payment (`action = 0`) |
| `failed` | EuPlatesc returned an error or decline |

---

## List Invoices

`GET /api/student/invoices`

Returns all invoices for the authenticated student, ordered by due date descending.

**Response** `200`:
```json
{
  "message": "Invoices retrieved successfully",
  "count": 2,
  "invoices": [
    {
      "id": 1,
      "title": "Curs engleza - luna martie",
      "series": "AB",
      "number": "000001",
      "value": "150.00",
      "currency": "RON",
      "due_date": "2026-03-31",
      "status": "issued"
    },
    {
      "id": 2,
      "title": "Curs engleza - luna februarie",
      "series": "AB",
      "number": "000002",
      "value": "150.00",
      "currency": "RON",
      "due_date": "2026-02-28",
      "status": "paid"
    }
  ]
}
```

---

## Get Invoice Detail

`GET /api/student/invoices/{id}`

Returns full invoice details including all payment attempts.

**Response** `200`:
```json
{
  "message": "Invoice retrieved successfully",
  "invoice": {
    "id": 1,
    "title": "Curs engleza - luna martie",
    "series": "AB",
    "number": "000001",
    "value": "150.00",
    "currency": "RON",
    "due_date": "2026-03-31",
    "status": "issued",
    "payments": [
      {
        "id": 3,
        "order_key": "AB1XY7Z",
        "amount": "150.00",
        "currency": "RON",
        "status": "failed",
        "status_message": "Insufficient funds",
        "paid_at": null,
        "created_at": "2026-03-08T10:00:00.000000Z"
      }
    ]
  }
}
```

**Errors**: `404` if invoice not found or not owned by the student.

---

## Pay Invoice

`POST /api/student/invoices/{id}/pay`

Initiates an EuPlatesc payment for the invoice. Returns an HTML page with an auto-submitting `<form>` that redirects the browser to the EuPlatesc hosted checkout page.

**The frontend must render this HTML response directly** — open it in the same tab or a new window so the browser submits the form to EuPlatesc.

**Response** `200` — `Content-Type: text/html`:
```html
<html>
  <body>
    <form method="POST" action="https://secure.euplatesc.ro/tdsprocess/tranzactd.php">
      <input type="hidden" name="amount" value="150.00" />
      <input type="hidden" name="curr" value="RON" />
      <input type="hidden" name="invoice_id" value="AB1XY7Z" />
      <input type="hidden" name="fp_hash" value="..." />
      <!-- ... other HMAC-signed fields ... -->
    </form>
    <script>document.forms[0].submit();</script>
  </body>
</html>
```

**What happens next:**

1. Student enters card details on the EuPlatesc hosted page.
2. EuPlatesc sends a **server-to-server IPN** to `POST /api/euplatesc/notify` — the invoice is automatically marked `paid` on approval.
3. EuPlatesc **redirects the student's browser** back via `POST /api/euplatesc/return`.
4. The return endpoint redirects the student to the frontend:
   - Success: `{FRONTEND_URL}/payment/success?invoice_id={id}`
   - Failure: `{FRONTEND_URL}/payment/failed?invoice_id={id}`

**Error Responses**:
```json
{ "message": "Invoice not found" }
```
```json
{ "message": "Invoice is already paid" }
```
```json
{ "message": "Invoice cannot be paid in its current status" }
```

---

## Billing Details

EuPlatesc passes the student's profile data to the checkout page as pre-filled billing info. To ensure the checkout is pre-populated correctly, the student should keep these profile fields up to date via `PUT /api/student/profile`:

| Profile field | Used as |
|---------------|---------|
| `email` | Billing email |
| `telephone` | Billing phone |
| `street` + `house_number` | Billing address line |
| `city` | Billing city |
| `county` | Billing region/county |
| `country` | Billing country |

See [02-profile.md](02-profile.md) for how to update these fields.

---

## EuPlatesc Webhook Endpoints

These are called by EuPlatesc servers directly — **not** by the frontend or the student app.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/euplatesc/notify` | Server-to-server IPN — updates `invoice_payments` status and marks invoice `paid` on approval |
| `POST` | `/api/euplatesc/return` | User browser return after checkout — redirects to frontend success/failed page |

**IPN fields received from EuPlatesc:**

| Field | Description |
|-------|-------------|
| `invoice_id` | Matches `order_key` in `invoice_payments` |
| `ep_id` | EuPlatesc internal transaction ID |
| `action` | `0` = approved, non-zero = error/decline |
| `message` | Human-readable status (e.g. `"Approved"`) |
| `amount` | Amount charged |
| `currency` | Currency charged |
| `timestamp` | Payment datetime in `YmdHis` format |
| `fp_hash` | HMAC signature |

---

## Required `.env` Variables

```env
EU_PLATESC_MID=your_merchant_id
EU_PLATESC_KEY=your_api_key
EU_PLATESC_TEST_MODE=true
APP_FRONTEND_URL=https://frontend.andreeaberkhout.com
```

Set `EU_PLATESC_TEST_MODE=false` in production.
