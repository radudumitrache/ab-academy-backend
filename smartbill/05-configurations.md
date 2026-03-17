# Configurations

SmartBill configuration endpoints allow you to query the VAT rates and document series configured for your company account. These values are required when creating invoices, estimates, or receipts to ensure field values match the account's setup.

**Base URL:** `https://ws.smartbill.ro/SBORO/api`
**Authentication:** HTTP Basic Auth — `email:api_token`

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| GET | `/tax` | Get all configured VAT rates |
| GET | `/series` | Get all document series |
| GET | `/series?type=f` | Get invoice series only |
| GET | `/series?type=p` | Get estimate/proforma series only |
| GET | `/series?type=c` | Get receipt (chitanta) series only |

---

## GET /tax

Returns all VAT (TVA) rates configured for the company. The `taxName` and `taxPercentage` values returned here must be used verbatim when creating invoices or estimates.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the company |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/tax?cif=RO12345678
```

### Response

Returns an array of VAT rate objects:

```json
{
  "errorText": "",
  "taxes": [
    {
      "name": "Normala",
      "percentage": 19
    },
    {
      "name": "Redusa",
      "percentage": 9
    },
    {
      "name": "Redusa",
      "percentage": 5
    },
    {
      "name": "Scutit",
      "percentage": 0
    },
    {
      "name": "Neinclus",
      "percentage": 0
    }
  ]
}
```

### Common VAT Rate Names

| Name | Typical Percentage | Usage |
|------|--------------------|-------|
| `Normala` | 19% | Standard VAT rate — most goods and services |
| `Redusa` | 9% | Reduced rate — food, medicine, hotels |
| `Redusa` | 5% | Super-reduced rate — books, schools, housing |
| `Scutit` | 0% | VAT-exempt with right of deduction (e.g., exports, intra-EU) |
| `Neinclus` | 0% | Outside VAT scope (non-VAT companies, certain transactions) |

---

## GET /series

Returns all document series configured for the company. Use the returned `name` values as `seriesName` in invoice, estimate, and payment creation requests.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the company |
| `type` | string | No | Filter by series type — see values below |

### Series Type Filter Values

| `type` Value | Filters For |
|--------------|-------------|
| *(omitted)* | All series (invoices + estimates + receipts) |
| `f` | Invoice (Factura) series only |
| `p` | Estimate/Proforma series only |
| `c` | Receipt (Chitanta) series only |

---

## GET /series — All Series

Returns all document series of every type configured for the company.

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/series?cif=RO12345678
```

### Response

```json
{
  "errorText": "",
  "list": [
    {
      "name": "FACT",
      "nextNumber": "0042",
      "type": "f"
    },
    {
      "name": "PRO",
      "nextNumber": "0015",
      "type": "p"
    },
    {
      "name": "CH",
      "nextNumber": "0008",
      "type": "c"
    }
  ]
}
```

---

## GET /series?type=f — Invoice Series

Returns only invoice (Factura) series.

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/series?cif=RO12345678&type=f
```

### Response

```json
{
  "errorText": "",
  "list": [
    {
      "name": "FACT",
      "nextNumber": "0042",
      "type": "f"
    },
    {
      "name": "FACT-EN",
      "nextNumber": "0007",
      "type": "f"
    }
  ]
}
```

---

## GET /series?type=p — Estimate/Proforma Series

Returns only estimate/proforma series.

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/series?cif=RO12345678&type=p
```

### Response

```json
{
  "errorText": "",
  "list": [
    {
      "name": "PRO",
      "nextNumber": "0015",
      "type": "p"
    }
  ]
}
```

---

## GET /series?type=c — Receipt Series

Returns only receipt (Chitanta) series.

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/series?cif=RO12345678&type=c
```

### Response

```json
{
  "errorText": "",
  "list": [
    {
      "name": "CH",
      "nextNumber": "0008",
      "type": "c"
    }
  ]
}
```

---

## Series Type Reference

| Type Code | Document Type | Used In |
|-----------|--------------|---------|
| `f` | Invoice (Factura) | `POST /invoice`, `DELETE /invoice`, `GET /invoice/pdf`, etc. |
| `p` | Estimate / Proforma | `POST /estimate`, `DELETE /estimate`, `GET /estimate/pdf`, etc. |
| `c` | Receipt (Chitanta) | `POST /payment` with `type: "Chitanta"`, `DELETE /payment/chitanta` |

---

## Integration Notes

These configuration endpoints are recommended as part of your application's startup or setup routine. Cache the results to avoid making repeated configuration queries on every document creation.

**Recommended usage in Laravel:**

```php
// Fetch and cache VAT rates for 24 hours
$taxes = Cache::remember("smartbill_taxes_{$cif}", 86400, function () use ($cif) {
    $response = Http::withBasicAuth(config('smartbill.email'), config('smartbill.token'))
        ->get('https://ws.smartbill.ro/SBORO/api/tax', ['cif' => $cif]);
    return $response->json('taxes');
});

// Fetch and cache invoice series for 1 hour
$series = Cache::remember("smartbill_invoice_series_{$cif}", 3600, function () use ($cif) {
    $response = Http::withBasicAuth(config('smartbill.email'), config('smartbill.token'))
        ->get('https://ws.smartbill.ro/SBORO/api/series', ['cif' => $cif, 'type' => 'f']);
    return $response->json('list');
});
```

---

## Notes

- The `taxName` used in invoice/estimate product lines must exactly match a name returned by `GET /tax` for your company.
- The `seriesName` used when creating documents must exactly match a name returned by `GET /series`.
- The `nextNumber` field in the series response shows the number that will be assigned to the next document created in that series.
- Series are configured within the SmartBill web interface and cannot be created or modified via API.
- If your company is not VAT-registered, the `/tax` endpoint will return only the `Neinclus` or `Scutit` entries.
