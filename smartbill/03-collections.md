# Collections / Payments (Incasari)

SmartBill collection endpoints allow you to register payments against invoices, retrieve cash register receipt content, check payment status, and delete payment records.

**Base URL:** `https://ws.smartbill.ro/SBORO/api`
**Authentication:** HTTP Basic Auth — `email:api_token`

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| POST | `/payment` | Create a new payment/collection record |
| GET | `/invoice/paymentstatus` | Check payment status of an invoice |
| GET | `/payment/text` | Get cash register receipt text content |
| DELETE | `/payment/chitanta` | Delete a receipt (Chitanta) |
| DELETE | `/payment/v2` | Delete other payment types by invoice reference or payment data |

---

## POST /payment

Creates a new payment/collection record and links it to one or more invoices. Supports all SmartBill payment types.

### Supported Payment Types

| Type | Description |
|------|-------------|
| `Chitanta` | Cash receipt (generates a numbered receipt) |
| `Card` | Card payment |
| `Cec` | Check (CEC) |
| `Bilet ordin` | Promissory note |
| `Ordin plata` | Bank transfer / payment order |
| `Mandat postal` | Postal money order |
| `Alta incasare` | Other payment method |
| `Bon` | POS receipt / cash register bon |

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `companyVatCode` | string | Yes | VAT code (CIF) of the issuing company |
| `client` | object | Yes | Client details — see sub-fields below |
| `client.name` | string | Yes | Client full name or company name |
| `client.vatCode` | string | No | Client VAT/CIF number |
| `client.regCom` | string | No | Client trade registry number |
| `client.isTaxPayer` | boolean | No | Whether the client is VAT registered |
| `client.address` | string | No | Client street address |
| `client.city` | string | No | Client city |
| `client.county` | string | No | Client county/state |
| `client.country` | string | No | Client country |
| `client.email` | string | No | Client email address |
| `client.saveToDb` | boolean | No | Save the client to SmartBill's client database |
| `issueDate` | string | Yes | Payment date in `YYYY-MM-DD` format |
| `seriesName` | string | Conditional | Receipt series name — required for `Chitanta` type |
| `precision` | number | No | Number of decimal places for amounts (default: 2) |
| `value` | number | Yes | Payment amount |
| `text` | string | No | Additional notes or description printed on the receipt |
| `isDraft` | boolean | No | If `true`, saves as draft without committing the record |
| `type` | string | Yes | Payment type — see supported types table above |
| `isCash` | boolean | No | Whether the payment is cash (affects cash register reporting) |
| `useInvoiceDetails` | boolean | No | If `true`, copies client details from the referenced invoice(s) |
| `invoicesList` | array | No | List of invoices this payment covers — see sub-fields below |
| `invoicesList[].seriesName` | string | Yes | Series name of the linked invoice |
| `invoicesList[].number` | string | Yes | Number of the linked invoice |
| `currency` | string | No | Currency code of the payment amount (e.g., `RON`, `EUR`) |

### Example Request Body — Cash Receipt (Chitanta)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "vatCode": "RO98765432",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-20",
  "seriesName": "CH",
  "value": 595.00,
  "type": "Chitanta",
  "isCash": true,
  "useInvoiceDetails": true,
  "invoicesList": [
    {
      "seriesName": "FACT",
      "number": "0001"
    }
  ],
  "currency": "RON"
}
```

### Example Request Body — Card Payment

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-20",
  "value": 1190.00,
  "type": "Card",
  "isCash": false,
  "invoicesList": [
    {
      "seriesName": "FACT",
      "number": "0002"
    }
  ],
  "currency": "RON"
}
```

### Example Request Body — Bank Transfer (Ordin plata)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "vatCode": "RO98765432",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-22",
  "value": 2380.00,
  "text": "OP nr. 123/22.01.2024",
  "type": "Ordin plata",
  "isCash": false,
  "invoicesList": [
    {
      "seriesName": "FACT",
      "number": "0003"
    },
    {
      "seriesName": "FACT",
      "number": "0004"
    }
  ],
  "currency": "RON"
}
```

### Example Request Body — Check (Cec)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Partener Comercial SRL",
    "vatCode": "RO11223344",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-25",
  "value": 4760.00,
  "text": "CEC seria ABC nr. 456789",
  "type": "Cec",
  "isCash": false,
  "invoicesList": [
    {
      "seriesName": "FACT",
      "number": "0005"
    }
  ],
  "currency": "RON"
}
```

### Example Request Body — Other Payment (Alta incasare)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client Online",
    "isTaxPayer": false
  },
  "issueDate": "2024-01-23",
  "value": 297.50,
  "text": "Plata online Stripe #pi_abc123",
  "type": "Alta incasare",
  "isCash": false,
  "invoicesList": [
    {
      "seriesName": "FACT",
      "number": "0006"
    }
  ],
  "currency": "RON"
}
```

### Example Request Body — POS Bon

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client Retail",
    "isTaxPayer": false
  },
  "issueDate": "2024-01-23",
  "seriesName": "BON",
  "value": 119.00,
  "type": "Bon",
  "isCash": true,
  "currency": "RON"
}
```

### Response

```json
{
  "errorText": "",
  "number": "0001",
  "series": "CH",
  "url": "https://..."
}
```

---

## GET /invoice/paymentstatus

Checks whether an invoice has been paid. Returns payment summary including total value, paid amount, and outstanding balance.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Invoice series name |
| `number` | string | Yes | Invoice number |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/invoice/paymentstatus?cif=RO12345678&seriesname=FACT&number=0001
```

### Response

```json
{
  "errorText": "",
  "number": "0001",
  "series": "FACT",
  "client": "Client SRL",
  "invoiceValue": 595.00,
  "paidValue": 595.00,
  "unpaidValue": 0.00,
  "status": "paid"
}
```

Possible `status` values: `paid`, `partial`, `unpaid`.

---

## GET /payment/text

Retrieves the text content of a cash register receipt (bon fiscal) by its ID. Useful for displaying or reprinting bon content.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `id` | string | Yes | The internal ID of the cash register receipt |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/payment/text?cif=RO12345678&id=12345
```

### Response

Returns a JSON object containing the plain text content of the receipt:

```json
{
  "errorText": "",
  "text": "FIRMA DEMO SRL\nStr. Exemplu nr. 1\nBucuresti\n...\nTOTAL: 119.00 RON\n"
}
```

---

## DELETE /payment/chitanta

Permanently deletes a cash receipt (Chitanta). Only the most recently issued receipt in a series can typically be deleted.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Receipt series name |
| `number` | string | Yes | Receipt number |

### Example Request

```
DELETE https://ws.smartbill.ro/SBORO/api/payment/chitanta?cif=RO12345678&seriesname=CH&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Receipt deleted successfully"
}
```

---

## DELETE /payment/v2 — By Invoice Reference

Deletes a non-receipt payment (Card, Ordin plata, etc.) that is linked to a specific invoice.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `paymentType` | string | Yes | Payment type (e.g., `Card`, `Ordin plata`, `Cec`) |
| `invoiceSeries` | string | Yes | Series name of the invoice the payment is linked to |
| `invoiceNumber` | string | Yes | Number of the invoice the payment is linked to |

### Example Request

```
DELETE https://ws.smartbill.ro/SBORO/api/payment/v2?cif=RO12345678&paymentType=Card&invoiceSeries=FACT&invoiceNumber=0002
```

### Response

```json
{
  "errorText": "",
  "message": "Payment deleted successfully"
}
```

---

## DELETE /payment/v2 — By Payment Data

Deletes a non-receipt payment by matching it against specific payment attributes (date, amount, and client). Use this variant when you do not have the linked invoice reference.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `paymentType` | string | Yes | Payment type (e.g., `Card`, `Ordin plata`, `Alta incasare`) |
| `paymentDate` | string | Yes | Date of the payment in `YYYY-MM-DD` format |
| `paymentValue` | number | Yes | Exact payment amount |
| `clientName` | string | Yes | Client name as it appears on the payment record |
| `clientCif` | string | Yes | Client VAT/CIF code as it appears on the payment record |

### Example Request

```
DELETE https://ws.smartbill.ro/SBORO/api/payment/v2?cif=RO12345678&paymentType=Ordin+plata&paymentDate=2024-01-22&paymentValue=2380.00&clientName=Client+SRL&clientCif=RO98765432
```

### Response

```json
{
  "errorText": "",
  "message": "Payment deleted successfully"
}
```

---

## Notes

- For `Chitanta` type payments, a `seriesName` is required and must match a receipt series configured in your SmartBill account (series type `c` — see `05-configurations.md`).
- Setting `isCash: true` flags the payment for cash register (casa de marcat) reporting.
- The `invoicesList` array supports multiple invoices, allowing a single payment to settle several outstanding invoices at once.
- Setting `useInvoiceDetails: true` automatically populates the client fields from the referenced invoice(s), avoiding duplication.
- Payment deletion is subject to SmartBill's accounting constraints — generally only the most recent record in a series or within a fiscal period can be deleted.
- To delete a `Bon` (cash register receipt), contact SmartBill directly as these are fiscally registered and cannot be deleted via API.
