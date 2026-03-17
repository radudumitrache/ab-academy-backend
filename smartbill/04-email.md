# Email Sending

SmartBill provides a single endpoint for sending invoice or estimate documents directly to clients by email. Both subject and body are transmitted as Base64-encoded strings.

**Base URL:** `https://ws.smartbill.ro/SBORO/api`
**Authentication:** HTTP Basic Auth — `email:api_token`

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| POST | `/document/send` | Send an invoice or estimate by email |

---

## POST /document/send

Sends a SmartBill document (invoice or estimate/proforma) to one or more email recipients. The PDF is automatically attached by SmartBill based on the document reference. Subject and body text must be Base64-encoded.

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `companyVatCode` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesName` | string | Yes | Document series name (e.g., `FACT`, `PRO`) |
| `number` | string | Yes | Document number to send |
| `type` | string | Yes | Document type: `factura` for invoices, `proforma` for estimates |
| `subject` | string | Yes | Email subject, Base64-encoded |
| `to` | string | Yes | Recipient email address (single address) |
| `bodyText` | string | Yes | Email body text, Base64-encoded (plain text or HTML) |

### Supported `type` Values

| Value | Description |
|-------|-------------|
| `factura` | Invoice (Factura) |
| `proforma` | Estimate / proforma invoice (Proforma) |

### Base64 Encoding

The `subject` and `bodyText` fields must be Base64-encoded strings. In PHP, use `base64_encode()`. In JavaScript, use `btoa()`.

**Examples:**

```php
// PHP
$subject  = base64_encode('Factura FACT-0001');
$bodyText = base64_encode('Va transmitem anexat factura aferenta serviciilor prestate. Va multumim!');
```

```javascript
// JavaScript
const subject  = btoa('Factura FACT-0001');
const bodyText = btoa('Va transmitem anexat factura aferenta serviciilor prestate. Va multumim!');
```

### Example Request Body — Send Invoice

```json
{
  "companyVatCode": "RO12345678",
  "seriesName": "FACT",
  "number": "0001",
  "type": "factura",
  "subject": "RmFjdHVyYSBGQUNULTAwMDE=",
  "to": "client@example.com",
  "bodyText": "VmEgdHJhbnNtaXRlbSBhbmV4YXQgZmFjdHVyYSBhZmVyZW50YSBzZXJ2aWNpaWxvciBwcmVzdGF0ZS4gVmEgbXVsdHVtaW0h"
}
```

Decoded values for clarity:
- `subject` → `Factura FACT-0001`
- `bodyText` → `Va transmitem anexat factura aferenta serviciilor prestate. Va multumim!`

### Example Request Body — Send Estimate/Proforma

```json
{
  "companyVatCode": "RO12345678",
  "seriesName": "PRO",
  "number": "0005",
  "type": "proforma",
  "subject": "UHJvZm9ybWEgUFJPLTAwMDU=",
  "to": "prospect@example.com",
  "bodyText": "VmEgdHJhbnNtaXRlbSBhbmV4YXQgcHJvZm9ybWEgc29saWNpdGF0YS4gVmEgcm91Z2FtIHNhIG5lIGNvbnRhY3RhdGkgcGVudHJ1IG9yaWNlIGludHJlYmFyaS4="
}
```

Decoded values for clarity:
- `subject` → `Proforma PRO-0005`
- `bodyText` → `Va transmitem anexat proforma solicitata. Va rugam sa ne contactati pentru orice intrebari.`

### Response

On success, SmartBill returns a confirmation:

```json
{
  "errorText": "",
  "message": "Email sent successfully"
}
```

On failure (e.g., invalid document reference or encoding error):

```json
{
  "errorText": "Document not found or invalid parameters",
  "message": ""
}
```

---

## Integration Example (PHP / Laravel)

The following example shows how this endpoint is typically called from the AB Academy Laravel backend via the `SmartBillService`:

```php
$payload = [
    'companyVatCode' => config('smartbill.vat_code'),
    'seriesName'     => $invoice->series,
    'number'         => $invoice->number,
    'type'           => 'factura',
    'subject'        => base64_encode("Factura {$invoice->series}-{$invoice->number}"),
    'to'             => $client->email,
    'bodyText'       => base64_encode(
        "Stimate client,\n\nVa transmitem anexat factura {$invoice->series}-{$invoice->number}.\n\nVa multumim!"
    ),
];

$response = Http::withBasicAuth(config('smartbill.email'), config('smartbill.token'))
    ->post('https://ws.smartbill.ro/SBORO/api/document/send', $payload);
```

---

## Notes

- The document PDF is generated and attached automatically by SmartBill — you do not need to upload or reference a file.
- The `to` field accepts a single email address. To send to multiple recipients, call the endpoint multiple times.
- Both `subject` and `bodyText` **must** be valid Base64 strings. Sending plain text will result in an error or garbled email content.
- The `type` field is case-sensitive: use lowercase `factura` or `proforma`.
- The `seriesName` and `number` must reference an existing, non-deleted document in SmartBill.
- SmartBill sends the email from its own servers using its configured SMTP; the sender address is your SmartBill account email.
