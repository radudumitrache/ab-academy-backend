# EuPlatesc Payment Gateway Integration

This document describes how this Laravel project integrates with the EuPlatesc payment gateway, intended as a reference for replicating the integration in another Laravel project.

---

## 1. Package

```json
"bytic/omnipay-euplatesc": "^3.0"
```

Install via Composer:

```bash
composer require bytic/omnipay-euplatesc
```

---

## 2. Environment Variables

Add to `.env`:

```env
EU_PLATESC_MID=your_merchant_id
EU_PLATESC_KEY=your_api_key
EUR_TO_RON_RATE=5.09
APP_URL=https://yourdomain.com
```

---

## 3. Config File

`config/payment.php`

```php
return [
    'eur_to_ron_rate' => env('EUR_TO_RON_RATE', 4.95),
    'base_currency'   => 'EUR',
];
```

---

## 4. Routes

Defined in `routes/web.php`:

```php
// EuPlatesc webhook endpoints (outside locale prefix, no auth middleware)
Route::post('/notify-url-euplatesc', 'Frontend\EuPlatescController@notifyEuPlatesc')->name('euplatesc.notify');
Route::post('/return-url-euplatesc',  'Frontend\EuPlatescController@returnEuPlatesc')->name('euplatesc.return');

// Inside locale-prefixed, auth-protected group:
Route::post('checkout/purchase',    'Frontend\CartController@purchase')->name('cart.checkout.purchase');
Route::get('checkout/success',      'Frontend\EuPlatescController@successPay')->name('cart.checkout.success');
Route::post('register-assessment',  'Frontend\AssessmentController@registerAssessment')->name('register.assessment');
```

### CSRF Exception

The return URL receives a POST from EuPlatesc and must be excluded from CSRF verification.

`app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    '/return-url-euplatesc',
];
```

---

## 5. EuPlatesc API Endpoints

| Direction       | URL                                                           | Method | Purpose                          |
|-----------------|---------------------------------------------------------------|--------|----------------------------------|
| Outbound (form) | `https://secure.euplatesc.ro/tdsprocess/tranzactd.php`        | POST   | Redirect user to payment page    |
| Inbound (IPN)   | `{APP_URL}/notify-url-euplatesc`                              | POST   | Server-to-server IPN callback    |
| Inbound (return)| `{APP_URL}/return-url-euplatesc`                              | POST   | User return after payment        |
| Outbound (API)  | `https://manager.euplatesc.ro/v3/index.php?action=ws`         | POST   | Check payment status (back-end)  |

---

## 6. Payment Flow

### Step 1 — Initiate Payment

Controller: `CartController@purchase` or `AssessmentController@registerAssessment`

```php
$gateway = new \Paytic\Omnipay\Euplatesc\Gateway();

$data = [
    'amount'      => number_format($total, 2, '.', ''),  // e.g. "99.00"
    'currency'    => 'EUR',                               // or 'RON'
    'fname'       => $billingAddress->first_name,
    'lname'       => $billingAddress->last_name,
    'orderId'     => $orderID,                            // unique 7-digit string
    'orderName'   => 'Plata online',
    'key'         => env('EU_PLATESC_KEY'),
    'mid'         => env('EU_PLATESC_MID'),
    'notifyUrl'   => env('APP_URL') . '/notify-url-euplatesc',
    'returnUrl'   => env('APP_URL') . '/return-url-euplatesc',
    'testMode'    => true,                                // set false in production
    'endpointUrl' => env('APP_URL'),
    'card'        => [
        'billingFirstName' => $billingAddress->first_name,
        'billingLastName'  => $billingAddress->last_name,
        'country'          => $billingAddress->country->name,
        'company'          => $billingAddress->company ?? null,
        'city'             => $billingAddress->city,
        'billingAddress1'  => $billingAddress->new_address,
        'billingAddress2'  => null,
        'email'            => $billingAddress->email,
        'phone'            => $billingAddress->phone,
    ],
];

$request  = $gateway->purchase($data);
$response = $request->send();
$response->send(); // Redirects user to EuPlatesc hosted page
```

The library builds a POST form with HMAC-signed fields and auto-submits it to:
`https://secure.euplatesc.ro/tdsprocess/tranzactd.php`

### Step 2 — Handle Return (User redirect back)

Controller: `EuPlatescController@returnEuPlatesc`

EuPlatesc POSTs the following fields to `{APP_URL}/return-url-euplatesc`:

| Field        | Description                                   |
|--------------|-----------------------------------------------|
| `invoice_id` | Your `orderId` sent during initiation         |
| `action`     | `0` = success, non-zero = failure/error       |
| `message`    | Human-readable status (e.g. "Approved")       |
| `amount`     | Amount charged                                |
| `timestamp`  | Payment timestamp in `YmdHis` format          |
| `fp_hash`    | HMAC signature for validation                 |

```php
public function returnEuPlatesc(Request $request)
{
    $data    = $request->all();
    $payment = Payment::where('order_key', $data['invoice_id'])->first();

    if ($payment) {
        $payment->fill([
            'pay_at'         => $data['timestamp']
                                    ? Carbon::createFromFormat('YmdHis', $data['timestamp'])
                                    : Carbon::now(),
            'amount'         => $data['amount'],
            'status'         => $data['message'],
            'status_code'    => $data['action'],
            'status_message' => $data['message'],
        ]);
        $payment->save();

        if ($data['action'] == 0) {
            // Payment successful — update your order/records here
        }
    }

    return redirect()->route('cart.checkout.success', ['locale' => app()->getLocale()]);
}
```

### Step 3 — Handle IPN Notification (Server-to-server)

Controller: `EuPlatescController@notifyEuPlatesc`

EuPlatesc may also POST to `{APP_URL}/notify-url-euplatesc` server-to-server. In this project it only logs the payload, but it can be used for reliable status updates independently of the user's browser return.

```php
public function notifyEuPlatesc(Request $request)
{
    \Log::info(json_encode($request->all()));
}
```

---

## 7. HMAC Signature Generation

Used both by the library (outbound) and the manual API check command (inbound).

```php
public function euplatescMac(array $data, string $key): string
{
    $str = '';
    foreach ($data as $d) {
        if ($d === null || strlen($d) == 0) {
            $str .= '-';
        } else {
            $str .= strlen($d) . $d;
        }
    }
    return hash_hmac('MD5', $str, pack('H*', $key));
}
```

The library's `Helper::generateHmac()` uses the same logic internally.

---

## 8. Manual Payment Status Check (Back-end API)

Used in `app/Console/Commands/TestEuPlatesc.php` via `php artisan test-eu-platesc`.

**Endpoint:** `POST https://manager.euplatesc.ro/v3/index.php?action=ws`

**Request fields:**

| Field       | Value                          |
|-------------|--------------------------------|
| `method`    | `check_status`                 |
| `mid`       | Your Merchant ID               |
| `epid`      | EuPlatesc internal payment ID  |
| `timestamp` | `gmdate('YmdHis')`             |
| `nonce`     | `md5(mt_rand() . time())`      |
| `fp_hash`   | HMAC of the above fields       |

```php
$key  = env('EU_PLATESC_KEY');
$data = [
    'method'    => 'check_status',
    'mid'       => env('EU_PLATESC_MID'),
    'epid'      => $epid,
    'timestamp' => gmdate('YmdHis'),
    'nonce'     => md5(mt_rand() . time()),
];
$data['fp_hash'] = strtoupper($this->euplatescMac($data, $key));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://manager.euplatesc.ro/v3/index.php?action=ws');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output    = curl_exec($ch);
$euplatescPayment = json_decode($server_output, true);
$statusData       = json_decode($euplatescPayment['success'], true);
// $statusData[0]['action']  — 0 = success
// $statusData[0]['message'] — e.g. "Approved"
curl_close($ch);
```

---

## 9. Database Schema

### `payments` table

| Column           | Type        | Notes                                  |
|------------------|-------------|----------------------------------------|
| `id`             | bigint PK   |                                        |
| `student_id`     | string      | FK to students                         |
| `order_key`      | string      | Unique 7-digit order ID sent to EuPlatesc |
| `number`         | string      | EuPlatesc internal reference           |
| `currency`       | string      | `EUR` or `RON`                         |
| `total`          | decimal     | Final amount charged                   |
| `discount_total` | decimal     | Coupon discount applied                |
| `amount`         | decimal     | Amount returned by EuPlatesc           |
| `status`         | string      | `pending`, `Approved`, etc.            |
| `status_code`    | integer     | `0` = approved, non-zero = error       |
| `status_message` | string      | Human-readable status from EuPlatesc   |
| `ip_address`     | string      |                                        |
| `user_agent`     | string      |                                        |
| `pay_at`         | timestamp   | Payment datetime from EuPlatesc        |
| `type_assessment`| string      | Course type if an assessment payment   |
| `assessment_id`  | bigint      | FK to assessment if applicable         |
| `created_at`     | timestamp   |                                        |
| `updated_at`     | timestamp   |                                        |
| `deleted_at`     | timestamp   | Soft deletes                           |

### `payment_courses` table

| Column       | Type      | Notes                        |
|--------------|-----------|------------------------------|
| `id`         | bigint PK |                              |
| `payment_id` | bigint    | FK to payments               |
| `course_id`  | string    | FK to courses                |
| `price`      | decimal   | Unit price                   |
| `subtotal`   | decimal   | Discount amount              |
| `total`      | decimal   | Final line total             |
| `quantity`   | integer   |                              |
| `coupon_id`  | bigint    | FK to coupons (nullable)     |

---

## 10. Currency Handling

- Base currency stored in DB: **EUR**
- Romanian customers (`country_code == 'RO'`) are charged in **RON**
- Conversion: `$price * config('payment.eur_to_ron_rate')`

---

## 11. Free Payment Shortcut (Coupon = 100%)

If a coupon reduces the total to `0`, the payment is marked as approved immediately without redirecting to EuPlatesc:

```php
if (session()->has('coupon') && $total == 0) {
    $payment->fill([
        'pay_at'         => Carbon::now(),
        'amount'         => 0,
        'status'         => 'Approved',
        'status_code'    => 0,
        'status_message' => 'Approved',
    ]);
    $payment->save();
    return redirect()->route('cart.checkout.success');
}
```

---

## 12. Key Files Reference

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Frontend/EuPlatescController.php` | IPN + return handlers, success page |
| `app/Http/Controllers/Frontend/CartController.php`      | `purchase()` — course checkout |
| `app/Http/Controllers/Frontend/AssessmentController.php`| `registerAssessment()` — assessment checkout |
| `app/Console/Commands/TestEuPlatesc.php`                | Back-end status check via API |
| `app/Models/Payment.php`                                | Payment model |
| `app/Models/PaymentCourse.php`                          | Payment line items model |
| `app/Http/Middleware/VerifyCsrfToken.php`               | CSRF exception for return URL |
| `config/payment.php`                                    | Currency config |
| `vendor/bytic/omnipay-euplatesc/src/Gateway.php`        | Omnipay gateway class |
| `vendor/bytic/omnipay-euplatesc/src/Message/PurchaseRequest.php` | Builds + signs payment form |
| `vendor/bytic/omnipay-euplatesc/src/Helper.php`         | HMAC utility |
