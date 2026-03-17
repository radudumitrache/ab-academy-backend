# Stocks (Stocuri)

SmartBill stock endpoints allow you to query current inventory levels for products across your configured warehouses. These endpoints are read-only and do not modify stock quantities — stock changes occur automatically when invoices or estimates with `useStock: true` are issued.

**Base URL:** `https://ws.smartbill.ro/SBORO/api`
**Authentication:** HTTP Basic Auth — `email:api_token`

---

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| GET | `/stocks` | Get product stocks (with optional filters) |
| GET | `/stocks` | Get all stocks for a specific warehouse |

Both use the same endpoint URL — the behavior is controlled by which query parameters are provided.

---

## GET /stocks — With Product Filters

Returns stock levels filtered by warehouse, product name, and/or product code. Useful for checking the available quantity of a specific product before creating an invoice with stock deduction.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the company |
| `date` | string | Yes | Reference date for stock query in `YYYY-MM-DD` format |
| `warehouseName` | string | No | Filter by warehouse name (must match exactly as configured in SmartBill) |
| `productName` | string | No | Filter by product name (partial or full match) |
| `productCode` | string | No | Filter by product code / SKU |

### Example Request — Specific Product by Code

```
GET https://ws.smartbill.ro/SBORO/api/stocks?cif=RO12345678&date=2024-01-31&warehouseName=Depozit+principal&productCode=PROD-001
```

### Example Request — Products by Name

```
GET https://ws.smartbill.ro/SBORO/api/stocks?cif=RO12345678&date=2024-01-31&productName=Laptop
```

### Example Request — All Products in a Warehouse

```
GET https://ws.smartbill.ro/SBORO/api/stocks?cif=RO12345678&date=2024-01-31&warehouseName=Depozit+principal
```

### Response

Returns an array of stock entries for the matching products:

```json
{
  "errorText": "",
  "list": [
    {
      "productName": "Laptop Dell XPS 15",
      "productCode": "PROD-001",
      "warehouseName": "Depozit principal",
      "measuringUnitName": "buc",
      "quantity": 12.00,
      "buyingPrice": 4500.00,
      "currency": "RON"
    },
    {
      "productName": "Laptop Lenovo ThinkPad",
      "productCode": "PROD-002",
      "warehouseName": "Depozit principal",
      "measuringUnitName": "buc",
      "quantity": 5.00,
      "buyingPrice": 3800.00,
      "currency": "RON"
    }
  ]
}
```

---

## GET /stocks — All Warehouse Stocks

Returns all product stock entries for a given warehouse as of a specific date. Useful for generating full inventory snapshots or reports.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cif` | string | Yes | VAT code (CIF) of the company |
| `date` | string | Yes | Reference date for stock query in `YYYY-MM-DD` format |
| `warehouseName` | string | Yes | Warehouse name to query (must match exactly as configured in SmartBill) |

### Example Request

```
GET https://ws.smartbill.ro/SBORO/api/stocks?cif=RO12345678&date=2024-01-31&warehouseName=Depozit+principal
```

### Response

Returns a full list of all products and their quantities in the specified warehouse:

```json
{
  "errorText": "",
  "list": [
    {
      "productName": "Laptop Dell XPS 15",
      "productCode": "PROD-001",
      "warehouseName": "Depozit principal",
      "measuringUnitName": "buc",
      "quantity": 12.00,
      "buyingPrice": 4500.00,
      "currency": "RON"
    },
    {
      "productName": "Mouse wireless",
      "productCode": "PROD-010",
      "warehouseName": "Depozit principal",
      "measuringUnitName": "buc",
      "quantity": 45.00,
      "buyingPrice": 85.00,
      "currency": "RON"
    },
    {
      "productName": "Tastatura mecanica",
      "productCode": "PROD-011",
      "warehouseName": "Depozit principal",
      "measuringUnitName": "buc",
      "quantity": 0.00,
      "buyingPrice": 320.00,
      "currency": "RON"
    }
  ]
}
```

Products with `quantity: 0.00` are included in the response and indicate out-of-stock items.

---

## Response Fields Reference

| Field | Type | Description |
|-------|------|-------------|
| `productName` | string | Product name as stored in SmartBill |
| `productCode` | string | Product SKU or internal code |
| `warehouseName` | string | Name of the warehouse holding this stock |
| `measuringUnitName` | string | Unit of measure (e.g., `buc`, `kg`, `litri`) |
| `quantity` | number | Current stock quantity as of the requested `date` |
| `buyingPrice` | number | Purchase/acquisition price per unit |
| `currency` | string | Currency of the `buyingPrice` |

---

## Integration Notes

Stock levels are affected by invoice and estimate creation when `useStock: true` is set. To ensure accurate inventory tracking:

1. Query current stock using `GET /stocks` before creating a sales invoice with stock deduction.
2. If the available `quantity` is less than the ordered amount, prompt the user or prevent invoice creation.
3. After issuing the invoice, the stock level is reduced automatically by SmartBill.

**Example stock check before invoicing (PHP / Laravel):**

```php
$response = Http::withBasicAuth(config('smartbill.email'), config('smartbill.token'))
    ->get('https://ws.smartbill.ro/SBORO/api/stocks', [
        'cif'           => config('smartbill.vat_code'),
        'date'          => now()->format('Y-m-d'),
        'warehouseName' => 'Depozit principal',
        'productCode'   => 'PROD-001',
    ]);

$stocks = $response->json('list');
$available = collect($stocks)->firstWhere('productCode', 'PROD-001');

if (!$available || $available['quantity'] < $requestedQuantity) {
    throw new \Exception('Insufficient stock for product PROD-001');
}
```

---

## Notes

- The `date` parameter returns the stock level as it was at the end of that day, taking into account all transactions (invoices, receptions, adjustments) up to and including that date.
- The `warehouseName` parameter is case-sensitive and must match exactly as configured in the SmartBill web interface.
- If no `warehouseName` is specified and no `productName` or `productCode` filter is provided, the response may be very large for companies with many warehouses and products.
- Stock entries only appear for products that have been set up with stock tracking in SmartBill (i.e., products where `isService: false`).
- Service-type products are not tracked in stock and will not appear in stock query results.
- Warehouses are configured within the SmartBill web interface and cannot be created via API.
