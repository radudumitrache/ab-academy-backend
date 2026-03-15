# Products & Purchases

Students can browse available products and purchase them using a payment profile. The payment is processed through EuPlatesc. After a successful payment, the admin grants access to the associated groups (course products) or tests (single products).

---

## Product Types

| Type | Description |
|------|-------------|
| `single` | A one-time purchase — optionally includes a specific test and/or teacher assistance |
| `course` | A bundle of N course sessions — admin assigns the student to the relevant group(s) after payment |

---

## Purchase Flow

```
1. Student creates a payment profile  →  POST /api/student/payment-profiles
   (required before purchasing — see 13-payment-profiles.md)
         │
         ▼
2. Student browses products  →  GET /api/student/products
         │
         ▼
3. Student initiates checkout  →  POST /api/student/products/{id}/purchase
   { "payment_profile_id": 1 }
         │
         ▼
4. Backend creates a pending acquisition, returns EuPlatesc HTML form
         │
         ▼
5. Frontend renders/submits the form → student pays on EuPlatesc
         │
         ▼
6. EuPlatesc calls POST /api/euplatesc/notify (server-to-server)
   → acquisition_status → "paid"
         │
         ▼
7. Student is redirected to:
   Success: {FRONTEND_URL}/payment/success?acquisition_id={id}
   Failure: {FRONTEND_URL}/payment/failed?acquisition_id={id}
         │
         ▼
8. Admin reviews profile billing mentions (if any), grants access
   → course products: student added to group(s)
   → single products: test access assigned
   → acquisition_status → "active"
         │
         ▼
9. Admin creates SmartBill invoice — visible on acquisition as invoice_series / invoice_number
         │
         ▼
10. Student attends sessions
         │
         ▼
11. Admin marks acquisition "completed"
         │
         ▼
12. Student has 1 week to renew → POST /api/student/acquisitions/{id}/renew
    No renewal → status set to "expired", student removed from group
```

> **Note on payment profile observations**: If you filled in the `observations` field on your payment profile (e.g. special invoice instructions), the admin must review and confirm it before the invoice is generated. This only happens once per profile.

---

## List Products

`GET /api/student/products`

Returns all active products available for purchase.

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
      "details": {
        "teacher_assistance": true,
        "test": {
          "id": 5,
          "name": "IELTS Academic Mock — March 2026"
        }
      }
    }
  ]
}
```

---

## Get Single Product

`GET /api/student/products/{id}`

**Errors**: `404` if not found or inactive.

---

## Purchase a Product

`POST /api/student/products/{id}/purchase`

Initiates an EuPlatesc payment for the selected product. Returns an HTML page with an auto-submitting `<form>` that redirects the browser to the EuPlatesc hosted checkout page.

**The frontend must render this HTML response directly.**

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `payment_profile_id` | integer | Yes | ID of the payment profile to use for billing |

**Price conversion**: The product price is always stored in EUR. If the selected payment profile's currency is `RON`, the price is automatically converted using the configured EUR→RON rate (`EUR_TO_RON_RATE` env var, default `4.95`).

**Response** `200` — `Content-Type: text/html`:
```html
<html>
  <body>
    <form method="POST" action="https://secure.euplatesc.ro/tdsprocess/tranzactd.php">
      <!-- EuPlatesc HMAC-signed fields -->
    </form>
    <script>document.forms[0].submit();</script>
  </body>
</html>
```

**After checkout:**
- EuPlatesc sends IPN to `POST /api/euplatesc/notify` → acquisition marked `paid`
- EuPlatesc redirects browser to:
  - Success: `{FRONTEND_URL}/payment/success?acquisition_id={id}`
  - Failure: `{FRONTEND_URL}/payment/failed?acquisition_id={id}`

**Errors**: `404` if product not found/inactive or payment profile not found, `500` on EuPlatesc gateway failure.

---

## List My Acquisitions

`GET /api/student/acquisitions`

Returns all product acquisitions for the authenticated student, newest first.

**Response** `200`:
```json
{
  "message": "Acquisitions retrieved successfully",
  "count": 1,
  "acquisitions": [
    {
      "id": 3,
      "product": {
        "id": 1,
        "name": "IELTS Preparation — 20 sessions",
        "type": "course"
      },
      "payment_profile": {
        "id": 1,
        "nickname": "Personal card",
        "type": "physical_person"
      },
      "amount_paid": "985.05",
      "currency": "RON",
      "acquisition_status": "active",
      "acquisition_date": "2026-03-15",
      "completion_date": null,
      "is_completed": false,
      "invoice_series": "AB",
      "invoice_number": "000042",
      "groups_access": [20],
      "tests_access": null,
      "renewed_from_id": null,
      "created_at": "2026-03-15T10:00:00.000000Z"
    }
  ]
}
```

---

## Get Single Acquisition

`GET /api/student/acquisitions/{id}`

**Errors**: `404` if not found or not owned by the student.

---

## Acquisition Statuses

| Status | Meaning |
|--------|---------|
| `pending_payment` | Checkout initiated, awaiting EuPlatesc confirmation |
| `paid` | Payment confirmed — awaiting admin to grant access |
| `active` | Admin granted access — student is attending |
| `completed` | All sessions attended — subscription fulfilled |
| `expired` | Completed and grace period elapsed without renewal |
| `cancelled` | Cancelled by admin |

---

## Renew a Subscription

`POST /api/student/acquisitions/{id}/renew`

Creates a new acquisition linked to the original one and immediately initiates a new EuPlatesc checkout. Only acquisitions with status `completed` or `expired` can be renewed.

The renewed acquisition inherits the same `groups_access` and `tests_access` as the original.

**Optional request body**:

| Field | Type | Description |
|-------|------|-------------|
| `payment_profile_id` | integer | Use a different payment profile for this renewal. Defaults to the original profile. |

**Response** `200` — `Content-Type: text/html` (same EuPlatesc checkout form as purchase)

**Errors**: `404` if acquisition not found, `422` if not in a renewable status or product is no longer active, `500` on gateway failure.
