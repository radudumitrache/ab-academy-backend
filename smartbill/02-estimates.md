# Estimates / Proformas (Proforme)

SmartBill estimate (proforma invoice) endpoints allow you to create, retrieve, manage, and delete proforma invoices. An estimate can later be converted to a final invoice.

**Base URL:** `https://ws.smartbill.ro/SBORO/api`
**Authentication:** HTTP Basic Auth — `email:api_token`

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| POST | `/estimate` | Create a new estimate/proforma |
| GET | `/estimate/pdf` | Download estimate as PDF |
| GET | `/estimate/invoices` | Check whether an estimate has been invoiced |
| PUT | `/estimate/cancel` | Cancel an estimate |
| PUT | `/estimate/restore` | Restore a cancelled estimate |
| DELETE | `/estimate` | Delete an estimate |

---

## POST /estimate

Creates a new estimate (proforma invoice). Supports the same variants as invoice creation: standard, draft, no-VAT, with services, with discount, with payment, in foreign currency, and with stock.

### Request Body

The estimate creation body is identical in structure to the invoice creation body. All fields below apply.

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
| `issueDate` | string | Yes | Estimate issue date in `YYYY-MM-DD` format |
| `seriesName` | string | Yes | The estimate series name (e.g., `PRO`) |
| `isDraft` | boolean | No | If `true`, saves the estimate as a draft without assigning a number |
| `dueDate` | string | No | Validity/due date in `YYYY-MM-DD` format |
| `deliveryDate` | string | No | Expected delivery/service date in `YYYY-MM-DD` format |
| `currency` | string | No | Currency code: `RON`, `EUR`, `USD`, etc. Defaults to `RON` |
| `exchangeRate` | number | No | Exchange rate to RON (required when `currency` is not `RON`) |
| `language` | string | No | Document language: `RO` (default) or `EN` |
| `useStock` | boolean | No | If `true`, reserves quantities from warehouse stock |
| `sendEmail` | boolean | No | Automatically send the estimate by email to the client |
| `paymentUrl` | string | No | URL for online payment link printed on the estimate |
| `useIntraCif` | boolean | No | Use intra-community VAT code (for EU cross-border transactions) |
| `products` | array | Yes | Array of line items — see sub-fields below |
| `products[].name` | string | Yes | Product or service name |
| `products[].code` | string | No | Internal product code / SKU |
| `products[].productDescription` | string | No | Additional description printed on the estimate line |
| `products[].isDiscount` | boolean | No | If `true`, this line is treated as a discount row |
| `products[].measuringUnitName` | string | Yes | Unit of measure (e.g., `buc`, `ore`, `luna`) |
| `products[].currency` | string | No | Line-item currency (if different from estimate currency) |
| `products[].quantity` | number | Yes | Quantity |
| `products[].price` | number | Yes | Unit price |
| `products[].isTaxIncluded` | boolean | No | If `true`, the price already includes VAT |
| `products[].taxName` | string | No | VAT category name (e.g., `Normala`, `Redusa`, `Scutit`) |
| `products[].taxPercentage` | number | No | VAT percentage (e.g., `19`, `9`, `5`, `0`) |
| `products[].isService` | boolean | No | Marks the line item as a service (not a physical product) |
| `products[].saveToDb` | boolean | No | Save this product to SmartBill's product database |
| `products[].warehouseName` | string | No | Warehouse name for stock reservation (requires `useStock: true`) |
| `products[].translatedName` | string | No | Product name translated to document language |
| `products[].translatedMeasuringUnit` | string | No | Unit name translated to document language |
| `payment` | object | No | Optional: register an advance payment at estimate creation time |
| `payment.value` | number | No | Payment amount |
| `payment.paymentSeries` | string | No | Receipt/payment series |
| `payment.type` | string | No | Payment type: `Chitanta`, `Card`, `Ordin plata`, etc. |
| `payment.isCash` | boolean | No | Whether the payment is cash |

### Example Request Body — Standard Estimate

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
    "saveToDb": false
  },
  "issueDate": "2024-01-15",
  "seriesName": "PRO",
  "isDraft": false,
  "dueDate": "2024-02-15",
  "currency": "RON",
  "language": "RO",
  "products": [
    {
      "name": "Servicii web development",
      "code": "WEB-001",
      "measuringUnitName": "ora",
      "quantity": 20,
      "price": 200.00,
      "isTaxIncluded": false,
      "taxName": "Normala",
      "taxPercentage": 19,
      "isService": true,
      "saveToDb": false
    }
  ]
}
```

### Example Request Body — Draft Estimate

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Potential Client SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-15",
  "seriesName": "PRO",
  "isDraft": true,
  "products": [
    {
      "name": "Pachet servicii",
      "measuringUnitName": "buc",
      "quantity": 1,
      "price": 1000.00,
      "taxName": "Normala",
      "taxPercentage": 19,
      "isService": true
    }
  ]
}
```

### Example Request Body — Estimate with Discount

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "Client Fidel SRL",
    "isTaxPayer": true
  },
  "issueDate": "2024-01-15",
  "seriesName": "PRO",
  "products": [
    {
      "name": "Servicii lunare",
      "measuringUnitName": "luna",
      "quantity": 3,
      "price": 500.00,
      "taxName": "Normala",
      "taxPercentage": 19,
      "isService": true
    },
    {
      "name": "Discount client fidel 5%",
      "measuringUnitName": "buc",
      "isDiscount": true,
      "quantity": 1,
      "price": -75.00,
      "taxName": "Normala",
      "taxPercentage": 19
    }
  ]
}
```

### Example Request Body — Estimate in Foreign Currency (EUR)

```json
{
  "companyVatCode": "RO12345678",
  "client": {
    "name": "International Client GmbH",
    "isTaxPayer": true,
    "country": "Germany"
  },
  "issueDate": "2024-01-15",
  "seriesName": "PRO",
  "currency": "EUR",
  "exchangeRate": 4.97,
  "language": "EN",
  "useIntraCif": true,
  "products": [
    {
      "name": "Software Development Services",
      "translatedName": "Software Development Services",
      "measuringUnitName": "hour",
      "translatedMeasuringUnit": "hour",
      "quantity": 40,
      "price": 85.00,
      "taxName": "Scutit",
      "taxPercentage": 0,
      "isService": true
    }
  ]
}
```

### Example Request Body — No-VAT Estimate (non-VAT-registered company)

```json
{
  "companyVatCode": "12345678",
  "client": {
    "name": "Client Persoana Fizica",
    "isTaxPayer": false,
    "city": "Cluj-Napoca",
    "country": "Romania"
  },
  "issueDate": "2024-01-15",
  "seriesName": "PRO",
  "products": [
    {
      "name": "Servicii design",
      "measuringUnitName": "ora",
      "quantity": 5,
      "price": 100.00,
      "taxPercentage": 0,
      "isService": true
    }
  ]
}
```

### Response

Returns a JSON object with the created estimate details including the assigned series name and number.

```json
{
  "errorText": "",
  "number": "0001",
  "series": "PRO",
  "url": "https://..."
}
```

---

## GET /estimate/pdf

Downloads the estimate as a PDF file.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Estimate series name |
| `number` | string | Yes | Estimate number |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/estimate/pdf?cif=RO12345678&seriesname=PRO&number=0001
```

### Response

Returns the PDF binary stream with `Content-Type: application/pdf`.

---

## GET /estimate/invoices

Checks whether a given estimate has been converted to a final invoice. Returns the related invoice details if an invoice exists.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Estimate series name |
| `number` | string | Yes | Estimate number |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/estimate/invoices?cif=RO12345678&seriesname=PRO&number=0001
```

### Response

```json
{
  "errorText": "",
  "invoiced": true,
  "invoices": [
    {
      "series": "FACT",
      "number": "0042"
    }
  ]
}
```

If the estimate has not been invoiced yet:

```json
{
  "errorText": "",
  "invoiced": false,
  "invoices": []
}
```

---

## PUT /estimate/cancel

Cancels an estimate. A cancelled estimate remains in the system but is marked as void and cannot be modified or converted to an invoice.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Estimate series name |
| `number` | string | Yes | Estimate number |

### Example Request

```
PUT https://ws.smartbill.ro/SBORO/api/estimate/cancel?cif=RO12345678&seriesname=PRO&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Estimate cancelled successfully"
}
```

---

## PUT /estimate/restore

Restores a previously cancelled estimate, returning it to active status.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Estimate series name |
| `number` | string | Yes | Estimate number |

### Example Request

```
PUT https://ws.smartbill.ro/SBORO/api/estimate/restore?cif=RO12345678&seriesname=PRO&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Estimate restored successfully"
}
```

---

## DELETE /estimate

Permanently deletes an estimate. This action cannot be undone. Only draft estimates or the last estimate in a series can typically be deleted.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the issuing company |
| `seriesname` | string | Yes | Estimate series name |
| `number` | string | Yes | Estimate number |

### Example Request

```
DELETE https://ws.smartbill.ro/SBORO/api/estimate?cif=RO12345678&seriesname=PRO&number=0001
```

### Response

```json
{
  "errorText": "",
  "message": "Estimate deleted successfully"
}
```

---

## Notes

- Estimates (proformas) follow the same field structure as invoices but use a separate document series (type `p` in SmartBill configuration).
- Use the `GET /estimate/invoices` endpoint to track whether a client has paid and been invoiced against a proforma.
- When `currency` is not `RON`, the `exchangeRate` field is required.
- The `seriesName` must be a series of type proforma — see `05-configurations.md` for how to retrieve configured series.
- A draft estimate (`isDraft: true`) does not get assigned a document number until it is confirmed.
- Cancelled estimates cannot be converted to invoices; restore them first if needed.
