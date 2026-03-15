# Payment Profiles

A payment profile stores the billing details a student uses when purchasing a product. Each student can have multiple payment profiles of two types: **physical person** or **company**.

The selected profile's billing details are sent to EuPlatesc at checkout and are used to generate the SmartBill invoice. Students must create at least one payment profile before purchasing a product.

> **Note on `observations`** — If you fill in the `observations` field (e.g. "please issue invoice to Dept. Marketing"), the admin will review it and set the correct invoice text before the first invoice can be generated. This only applies to the very first invoice for that profile; subsequent ones are automatic.

---

## Profile Types

| Type | Description |
|------|-------------|
| `physical_person` | Individual buyer — first/last name + billing address |
| `company` | Romanian legal entity — CUI, company name, trade register, legal address + billing address |

---

## List Payment Profiles

`GET /api/student/payment-profiles`

Returns all payment profiles belonging to the authenticated student.

**Response** `200`:
```json
{
  "message": "Payment profiles retrieved successfully",
  "count": 2,
  "profiles": [
    {
      "id": 1,
      "type": "physical_person",
      "nickname": "Personal card",
      "currency": "RON",
      "observations": null,
      "details": {
        "first_name": "Andrei",
        "last_name": "Popescu",
        "billing_address": "Str. Florilor 12",
        "billing_city": "Cluj-Napoca",
        "billing_state": "Cluj",
        "billing_zip_code": "400000",
        "billing_country": "Romania"
      }
    },
    {
      "id": 2,
      "type": "company",
      "nickname": "Firma mea",
      "currency": "EUR",
      "observations": "Factura pe firma",
      "details": {
        "cui": "RO12345678",
        "company_name": "Popescu SRL",
        "trade_register_number": "J12/123/2020",
        "registration_date": "2020-03-01",
        "legal_address": "Str. Industriei 5, Cluj",
        "billing_address": "Str. Industriei 5",
        "billing_city": "Cluj-Napoca",
        "billing_state": "Cluj",
        "billing_zip_code": "400001",
        "billing_country": "Romania"
      }
    }
  ]
}
```

---

## Get Single Payment Profile

`GET /api/student/payment-profiles/{id}`

**Errors**: `404` if not found or not owned by the student.

---

## Create Payment Profile

`POST /api/student/payment-profiles`

**Common fields** (all requests):

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | Yes | `physical_person` or `company` |
| `nickname` | string | Yes | Short label to identify this profile |
| `currency` | string | Yes | `EUR` or `RON` — currency used at checkout |
| `observations` | string | No | Free-text notes |

**Additional fields for `physical_person`**:

| Field | Type | Required |
|-------|------|----------|
| `first_name` | string | Yes |
| `last_name` | string | Yes |
| `billing_address` | string | Yes |
| `billing_city` | string | Yes |
| `billing_state` | string | No |
| `billing_zip_code` | string | No |
| `billing_country` | string | No (defaults to `Romania`) |

**Additional fields for `company`**:

| Field | Type | Required |
|-------|------|----------|
| `cui` | string | Yes — Romanian tax identification number |
| `company_name` | string | Yes |
| `trade_register_number` | string | Yes |
| `registration_date` | date | No |
| `legal_address` | string | Yes |
| `billing_address` | string | Yes |
| `billing_city` | string | Yes |
| `billing_state` | string | No |
| `billing_zip_code` | string | No |
| `billing_country` | string | No (defaults to `Romania`) |

**Response** `201`:
```json
{
  "message": "Payment profile created successfully",
  "profile": { ... }
}
```

**Errors**: `422` on validation failure.

---

## Update Payment Profile

`PUT /api/student/payment-profiles/{id}`

All fields are optional — only send what needs to change. The `type` cannot be changed after creation.

**Response** `200`:
```json
{
  "message": "Payment profile updated successfully",
  "profile": { ... }
}
```

**Errors**: `404` if not found, `422` on validation failure.

---

## Delete Payment Profile

`DELETE /api/student/payment-profiles/{id}`

Soft-deletes the profile. Profiles with **pending**, **paid**, or **active** acquisitions cannot be deleted.

**Response** `200`:
```json
{
  "message": "Payment profile deleted successfully"
}
```

**Errors**: `404` if not found, `422` if the profile has active acquisitions.
