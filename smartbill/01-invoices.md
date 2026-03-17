# Invoices (Facturi)

SmartBill invoice endpoints allow you to create, retrieve, manage, and delete invoices programmatically.

**Base URL:** `https://ws.smartbill.ro/SBORO/api`
**Authentication:** HTTP Basic Auth — `email:api_token`

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| POST | `/invoice` | Create a new invoice |
| GET | `/invoice/pdf` | Download invoice as PDF |
| GET | `/invoice/paymentstatus` | Check payment status of an invoice |
| POST | `/invoice/reverse` | Reverse (storno) an invoice |
| PUT | `/invoice/cancel` | Cancel an invoice |
| PUT | `/invoice/restore` | Restore a cancelled invoice |
| DELETE | `/invoice` | Delete an invoice |

---

## POST /invoice

Creates a new invoice. Supports multiple variants: standard, draft, no-VAT, with services, with discount, with payment, in foreign currency, or with stock deduction.

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `companyVatCode` | string | Yes | The VAT code (CIF) of the issuing company |
| `client` | object | Yes | Client (buyer) details — see sub-fields below |
| `client.name` | string | Yes | Client full name or company name |
| `client.vatCode` | string | No | Client VAT/CIF number |
| `client.regCom` | string | No | Client trade registry number (J.../...) |
| `client.isTaxPayer` | boolean | No | Whether the client is VAT registered |
| `client.address` | string | No | Client street address |
| `client.city` | string | No | Client city |
| `client.county` | string | No | Client county/state |
| `client.country` | string | No | Client country (e.g., `Romania`) |
| `client.email` | string | No | Client email address |
| `client.saveToDb` | boolean | No | Save the client to SmartBill's client database |
| `client.contact` | string | No | Contact person name |
| `issueDate` | string | Yes | Invoice issue date in `YYYY-MM-DD` format |
| `seriesName` | string | Yes | The invoice series name (e.g., `FACT`) |
| `isDraft` | boolean | No | If `true`, saves the invoice as a draft without assigning a number |
| `dueDate` | string | No | Payment due date in `YYYY-MM-DD` format |
| `deliveryDate` | string | No | Delivery/service date in `YYYY-MM-DD` format |
| `currency` | string | No | Currency code: `RON`, `EUR`, `USD`, etc. Defaults to `RON` |
| `exchangeRate` | number | No | Exchange rate to RON (required when `currency` is not `RON`) |
| `language` | string | No | Document language: `RO` (default) or `EN` |
| `useStock` | boolean | No | If `true`, deducts quantities from warehouse stock |
| `sendEmail` | boolean | No | Automatically send the invoice by email to the client |
| `paymentUrl` | string | No | URL for online payment link printed on the invoice |
| `useIntraCif` | boolean | No | Use intra-community VAT code (for EU cross-border transactions) |
| `products` | array | Yes | Array of line items — see sub-fields below |
| `products[].name` | string | Yes | Product or service name |
| `products[].code` | string | No | Internal product code / SKU |
| `products[].productDescription` | string | No | Additional description printed on the invoice line |
| `products[].isDiscount` | boolean | No | If `true`, this line is treated as a discount row |
| `products[].measuringUnitName` | string | Yes | Unit of measure (e.g., `buc`, `ore`, `luna`) |
| `products[].currency` | string | No | Line-item currency (if different from invoice currency) |
| `products[].quantity` | number | Yes | Quantity |
| `products[].price` | number | Yes | Unit price |
| `products[].isTaxIncluded` | boolean | No | If `true`, the price already includes VAT |
| `products[].taxName` | string | No | VAT category name (e.g., `Normala`, `Redusa`, `Scutit`) |
| `products[].taxPercentage` | number | No | VAT percentage (e.g., `19`, `9`, `5`, `0`) |
| `products[].isService` | boolean | No | Marks the line item as a service (not a physical product) |
| `products[].saveToDb` | boolean | No | Save this product to SmartBill's product database |
| `products[].warehouseName` | string | No | Warehouse name for stock deduction (requires `useStock: true`) |
| `products[].translatedName` | string | No | Product name translated to invoice language |
| `products[].translatedMeasuringUnit` | string | No | Unit name translated to invoice language |
| `payment` | object | No | Optional: register a payment at invoice creation time |
| `payment.value` | number | No | Payment amount |
| `payment.paymentSeries` | string | No | Receipt/payment series (for Chitanta type) |
| `payment.type` | string | No | Payment type: `Chitanta`, `Card`, `Ordin plata`, `Cec`, `Bilet ordin`, `Mandat postal`, `Alta incasare`, `Bon` |
| `payment.isCash` | boolean | No | Whether the payment is cash (affects cash register reporting) |

### Example Request Body — Standard Invoice (VAT-paying company)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "vatCode": "RO98765432",
    "regCom": "J40/1234/2020",
    "isTaxPayer": true,
    "address": "Str. Exemplu nr. 1",
    "city": "Bucuresti",
    "county": "Sector 1",
    "country": "Romania",
    "email": "client@example.com",
    "saveToDb": false,
    "contact": "Ion Popescu"
  },
  "issueDate": "2024-01-15",
  "seriesName": "FACT",
  "isDraft": false,
  "dueDate": "2024-02-15",
  "deliveryDate": "2024-01-15",
  "currency": "RON",
  "language": "RO",
  "useStock": false,
  "sendEmail": false,
  "products": [
    {
      "name": "Servicii consultanta",
      "code": "CONS-001",
      "measuringUnitName": "ora",
      "quantity": 10,
      "price": 150.00,
      "isTaxIncluded": false,
      "taxName": "Normala",
      "taxPercentage": 19,
      "isService": true,
      "saveToDb": false
    }
  ]
}
```

### Example Request Body — Draft Invoice

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-15",
  "seriesName": "FACT",
  "isDraft": true,
  "products": [
    {
      "name": "Produs test",
      "measuringUnitName": "buc",
      "quantity": 1,
      "price": 100.00,
      "taxName": "Normala",
      "taxPercentage": 19
    }
  ]
}
```

### Example Request Body — Invoice with Discount

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-15",
  "seriesName": "FACT",
  "products": [
    {
      "name": "Produs principal",
      "measuringUnitName": "buc",
      "quantity": 5,
      "price": 200.00,
      "taxName": "Normala",
      "taxPercentage": 19
    },
    {
      "name": "Discount 10%",
      "measuringUnitName": "buc",
      "isDiscount": true,
      "quantity": 1,
      "price": -100.00,
      "taxName": "Normala",
      "taxPercentage": 19
    }
  ]
}
```

### Example Request Body — Invoice in Foreign Currency (EUR)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Foreign Client Ltd",
    "isTaxPayer": false,
    "country": "Germany"
  },
  "issueDate": "2024-01-15",
  "seriesName": "FACT",
  "currency": "EUR",
  "exchangeRate": 4.97,
  "language": "EN",
  "useIntraCif": true,
  "products": [
    {
      "name": "Consulting Services",
      "translatedName": "Consulting Services",
      "measuringUnitName": "hour",
      "translatedMeasuringUnit": "hour",
      "quantity": 8,
      "price": 75.00,
      "taxName": "Scutit",
      "taxPercentage": 0,
      "isService": true
    }
  ]
}
```

### Example Request Body — Invoice with Payment Attached

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-15",
  "seriesName": "FACT",
  "products": [
    {
      "name": "Abonament lunar",
      "measuringUnitName": "luna",
      "quantity": 1,
      "price": 500.00,
      "taxName": "Normala",
      "taxPercentage": 19,
      "isService": true
    }
  ],
  "payment": {
    "value": 595.00,
    "paymentSeries": "CH",
    "type": "Chitanta",
    "isCash": true
  }
}
```

### Example Request Body — Invoice with Stock Deduction

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-15",
  "seriesName": "FACT",
  "useStock": true,
  "products": [
    {
      "name": "Produs fizic",
      "code": "PROD-001",
      "measuringUnitName": "buc",
      "quantity": 3,
      "price": 250.00,
      "taxName": "Normala",
      "taxPercentage": 19,
      "isService": false,
      "warehouseName": "Depozit principal"
    }
  ]
}
```

### Response

Returns a JSON object with the created invoice details including the assigned series name and number.

```json
{
  "errorText": "",
  "number": "0001",
  "series": "FACT",
  "url": "https://..."
}
```

---

## GET /invoice/pdf

Downloads the invoice as a PDF file.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Invoice series name |
| `number` | string | Yes | Invoice number |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/invoice/pdf?cif=RO12345678&seriesname=FACT&number=0001
```

### Response

Returns the PDF binary stream with `Content-Type: application/pdf`.

---

## GET /invoice/paymentstatus

Checks whether an invoice has been paid and returns the payment details.

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

---

## POST /invoice/reverse

Creates a reversal (storno) invoice for an existing invoice. The reversal negates all line items of the original invoice.

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `companyVatCode` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesName` | string | Yes | Series name of the original invoice to reverse |
| `number` | string | Yes | Number of the original invoice to reverse |
| `issueDate` | string | Yes | Issue date for the reversal invoice in `YYYY-MM-DD` format |

### Example Request Body

```json
{
  "companyVatCode": "RO12345678",
  "seriesName": "FACT",
  "number": "0001",
  "issueDate": "2024-01-20"
}
```

### Response

Returns the details of the newly created reversal invoice.

```json
{
  "errorText": "",
  "number": "0002",
  "series": "FACT"
}
```

---

## PUT /invoice/cancel

Cancels an invoice. A cancelled invoice remains in the system but is marked as void and cannot be modified.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Invoice series name |
| `number` | string | Yes | Invoice number |

### Example Request

```
PUT https://ws.smartbill.ro/SBORO/api/invoice/cancel?cif=RO12345678&seriesname=FACT&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Invoice cancelled successfully"
}
```

---

## PUT /invoice/restore

Restores a previously cancelled invoice, returning it to active status.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Invoice series name |
| `number` | string | Yes | Invoice number |

### Example Request

```
PUT https://ws.smartbill.ro/SBORO/api/invoice/restore?cif=RO12345678&seriesname=FACT&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Invoice restored successfully"
}
```

---

## DELETE /invoice

Permanently deletes an invoice. This action cannot be undone. Only draft invoices or the last invoice in a series can typically be deleted.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Invoice series name |
| `number` | string | Yes | Invoice number |

### Example Request

```
DELETE https://ws.smartbill.ro/SBORO/api/invoice?cif=RO12345678&seriesname=FACT&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Invoice deleted successfully"
}
```

---

## Notes

- All dates must be in `YYYY-MM-DD` format.
- When `currency` is not `RON`, the `exchangeRate` field is required to convert amounts to RON for accounting purposes.
- The `isDraft` flag is useful for creating invoices that need review before being officially issued with a number.
- Setting `useStock: true` requires each product line to include a `warehouseName`.
- The `taxName` field must match an existing VAT rate name configured in your SmartBill account (see `/tax` endpoint in `05-configurations.md`).
- The `seriesName` must match a series configured in your SmartBill account (see `/series` endpoint in `05-configurations.md`).
