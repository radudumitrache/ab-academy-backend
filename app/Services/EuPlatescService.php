<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Support\Str;

class EuPlatescService
{
    /**
     * Initiate a payment for an invoice.
     *
     * Returns the HTML form that auto-submits to EuPlatesc, or throws on failure.
     * The controller should return this HTML directly so the browser posts to EuPlatesc.
     */
    public function initiatePayment(Invoice $invoice, User $student): string
    {
        $gateway = new \Paytic\Omnipay\Euplatesc\Gateway();

        // Generate a unique 7-character order key
        $orderKey = strtoupper(Str::random(7));
        // Ensure uniqueness
        while (InvoicePayment::where('order_key', $orderKey)->exists()) {
            $orderKey = strtoupper(Str::random(7));
        }

        $amount   = number_format((float) $invoice->value, 2, '.', '');
        $currency = $invoice->currency; // EUR or RON

        // Record the pending payment attempt
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'order_key'  => $orderKey,
            'amount'     => $invoice->value,
            'currency'   => $currency,
            'status'     => 'pending',
        ]);

        $data = [
            'amount'      => $amount,
            'currency'    => $currency,
            'fname'       => $student->username,
            'lname'       => '',
            'orderId'     => $orderKey,
            'orderName'   => $invoice->title,
            'key'         => config('payment.euplatesc_key'),
            'mid'         => config('payment.euplatesc_mid'),
            'notifyUrl'   => config('app.url') . '/api/euplatesc/notify',
            'returnUrl'   => config('app.url') . '/api/euplatesc/return',
            'testMode'    => config('payment.test_mode', true),
            'endpointUrl' => config('app.url'),
            'card'        => [
                'billingFirstName' => $student->username,
                'billingLastName'  => '',
                'country'          => $student->country ?? 'Romania',
                'city'             => $student->city ?? '',
                'billingAddress1'  => trim(($student->street ?? '') . ' ' . ($student->house_number ?? '')),
                'billingAddress2'  => null,
                'email'            => $student->email ?? '',
                'phone'            => $student->telephone ?? '',
            ],
        ];

        $request  = $gateway->purchase($data);
        $response = $request->send();

        // The library returns an HTML auto-submit form
        return $response->getRedirectForm() ?? $response->getRedirectUrl();
    }

    /**
     * Generate an HMAC signature for back-end API calls.
     *
     * Each value is prefixed with its length, or '-' if empty/null.
     * hash_hmac('MD5', $str, pack('H*', $key))
     */
    public function generateHmac(array $data, string $key): string
    {
        $str = '';
        foreach ($data as $d) {
            if ($d === null || strlen((string) $d) === 0) {
                $str .= '-';
            } else {
                $str .= strlen((string) $d) . $d;
            }
        }
        return strtoupper(hash_hmac('MD5', $str, pack('H*', $key)));
    }

    /**
     * Check the payment status of a transaction via the EuPlatesc back-end API.
     *
     * Returns the decoded JSON response or null on failure.
     */
    public function checkStatus(string $epid): ?array
    {
        $key  = config('payment.euplatesc_key');
        $data = [
            'method'    => 'check_status',
            'mid'       => config('payment.euplatesc_mid'),
            'epid'      => $epid,
            'timestamp' => gmdate('YmdHis'),
            'nonce'     => md5(mt_rand() . time()),
        ];
        $data['fp_hash'] = $this->generateHmac($data, $key);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://manager.euplatesc.ro/v3/index.php?action=ws');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);

        if (!$output) {
            return null;
        }

        $decoded = json_decode($output, true);
        if (!isset($decoded['success'])) {
            return null;
        }

        return json_decode($decoded['success'], true);
    }
}
