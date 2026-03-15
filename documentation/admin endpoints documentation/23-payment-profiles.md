# Admin — Payment Profiles

Admins can view all payment profiles created by students across the platform. The key admin responsibility is the **invoice confirmation flow**: before the first invoice can be auto-generated for a payment profile that has special billing mentions (`observations` or `invoice_text`), an admin must review the profile, complete the `invoice_text` field (the text printed on the invoice), and confirm it.

---

## invoice_text vs observations

| Field | Who sets it | Purpose |
|-------|-------------|---------|
| `observations` | Student | Free-text notes visible to admin (e.g. "please put Dept. Marketing on invoice") |
| `invoice_text` | Admin | The exact text that will appear as the product line description on the SmartBill invoice |
| `invoice_confirmed` | Admin | `true` once admin has reviewed the profile and confirmed invoice generation is allowed |

---

## Invoice Confirmation Flow

```
1. Student creates a payment profile with observations
2. Admin sees it in the "needs confirmation" list  →  GET /api/admin/payment-profiles?needs_confirmation=1
3. Admin reviews the profile and sets the invoice text  →  POST /api/admin/payment-profiles/{id}/set-invoice-text
4. Profile is now confirmed (invoice_confirmed = true)
5. Admin can create the first invoice for any acquisition on this profile  →  POST /api/admin/acquisitions/{id}/create-invoice
6. All subsequent invoices for this profile are generated automatically without the confirmation step
```

If the profile has **no observations and no invoice_text**, it is treated as standard — the admin can skip the confirmation step and create invoices immediately. To explicitly confirm a profile without changing invoice_text, use the confirm endpoint.

---

## List All Payment Profiles

`GET /api/admin/payment-profiles`

**Query Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `student_id` | integer | Filter by student |
| `type` | string | `physical_person` or `company` |
| `needs_confirmation` | boolean | If `1`, returns only profiles with observations/invoice_text that are not yet confirmed |

**Response** `200`:
```json
{
  "message": "Payment profiles retrieved successfully",
  "count": 2,
  "profiles": [
    {
      "id": 3,
      "student": {
        "id": 7,
        "username": "maria.ionescu",
        "email": "maria@example.com"
      },
      "type": "company",
      "nickname": "Firma mea",
      "currency": "RON",
      "observations": "Factura pe firma, mentionati proiectul X",
      "invoice_text": null,
      "invoice_confirmed": false,
      "details": {
        "cui": "RO12345678",
        "company_name": "Ionescu SRL",
        "trade_register_number": "J12/456/2021",
        "registration_date": "2021-06-01",
        "legal_address": "Str. Victoriei 1, Bucuresti",
        "billing_address": "Str. Victoriei 1",
        "billing_city": "Bucuresti",
        "billing_state": "Ilfov",
        "billing_zip_code": "010101",
        "billing_country": "Romania"
      }
    }
  ]
}
```

---

## Get Single Payment Profile

`GET /api/admin/payment-profiles/{id}`

Returns the profile with full details plus a summary of all acquisitions made under it.

**Response** `200`:
```json
{
  "message": "Payment profile retrieved successfully",
  "profile": {
    "id": 3,
    "student": { ... },
    "type": "company",
    "nickname": "Firma mea",
    "currency": "RON",
    "observations": "Factura pe firma",
    "invoice_text": "Servicii educationale — IELTS Preparation, 20 sesiuni",
    "invoice_confirmed": true,
    "details": { ... },
    "acquisitions": [
      {
        "id": 5,
        "product": { "id": 1, "name": "IELTS Preparation — 20 sessions" },
        "acquisition_status": "active",
        "acquisition_date": "2026-03-15",
        "amount_paid": "985.05",
        "currency": "RON"
      }
    ]
  }
}
```

---

## Get Payment Profiles for a Specific Student

`GET /api/admin/students/{studentId}/payment-profiles`

**Response** `200`:
```json
{
  "message": "Payment profiles retrieved successfully",
  "student": { "id": 7, "username": "maria.ionescu" },
  "count": 1,
  "profiles": [ ... ]
}
```

**Errors**: `404` if student not found.

---

## Set Invoice Text (and Confirm)

`POST /api/admin/payment-profiles/{id}/set-invoice-text`

Sets the `invoice_text` field and marks the profile as confirmed (`invoice_confirmed = true`). Required before creating the first SmartBill invoice for profiles with billing mentions.

**Request body**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `invoice_text` | string | Yes | Text to print on the invoice product line (e.g. "Servicii educationale — curs IELTS") |

**Response** `200`:
```json
{
  "message": "Invoice text saved and profile confirmed",
  "profile": { ... }
}
```

**Errors**: `404` if not found, `422` on validation failure.

---

## Confirm Profile (without changing invoice_text)

`POST /api/admin/payment-profiles/{id}/confirm`

Marks the profile as `invoice_confirmed = true` without modifying `invoice_text`. Use this when the profile has no special text but you want to mark it as reviewed.

**Response** `200`:
```json
{
  "message": "Payment profile confirmed",
  "profile": { ... }
}
```

**Errors**: `404` if not found.
