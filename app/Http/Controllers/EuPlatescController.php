<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\ProductAcquisition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EuPlatescController extends Controller
{
    /**
     * Server-to-server IPN callback from EuPlatesc.
     *
     * EuPlatesc POSTs: invoice_id, ep_id, action, message, amount, currency, timestamp, fp_hash
     * action = 0 means approved; anything else is an error.
     */
    public function notify(Request $request)
    {
        Log::info('EuPlatesc IPN received', $request->all());

        $this->handleCallback($request);

        // EuPlatesc expects a plain-text "<EPAYMENT>" response to acknowledge IPN
        return response('<EPAYMENT>' . date('YmdHis') . '|OK', 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * User return URL after completing (or failing) payment on EuPlatesc.
     *
     * EuPlatesc POSTs the same fields as the IPN. We update the payment record
     * and redirect the user to a frontend page.
     */
    public function return(Request $request)
    {
        Log::info('EuPlatesc return received', $request->all());

        [$type, $record] = $this->handleCallback($request);

        $frontendBase = config('app.frontend_url', config('app.url'));

        if ($type === 'invoice') {
            if ($record && $record->status === 'approved') {
                return redirect($frontendBase . '/payment/success?invoice_id=' . $record->invoice_id);
            }
            return redirect($frontendBase . '/payment/failed?invoice_id=' . ($record?->invoice_id ?? ''));
        }

        // product acquisition
        if ($record && $record->acquisition_status === 'paid') {
            return redirect($frontendBase . '/payment/success?acquisition_id=' . $record->id);
        }
        return redirect($frontendBase . '/payment/failed?acquisition_id=' . ($record?->id ?? ''));
    }

    // ── Private ────────────────────────────────────────────────────────────────

    /**
     * Resolves the order key to either an InvoicePayment or ProductAcquisition,
     * updates its status, and returns ['invoice'|'acquisition', $record].
     */
    private function handleCallback(Request $request): array
    {
        $data = $request->all();

        // invoice_id in EuPlatesc response maps to our order_key
        $orderKey = $data['invoice_id'] ?? null;
        if (!$orderKey) {
            return ['unknown', null];
        }

        $action   = (int) ($data['action'] ?? -1);
        $approved = $action === 0;

        $paidAt = null;
        if (!empty($data['timestamp'])) {
            try {
                $paidAt = Carbon::createFromFormat('YmdHis', $data['timestamp']);
            } catch (\Throwable) {
                $paidAt = now();
            }
        }

        // ── Invoice payment ───────────────────────────────────────────────────
        $payment = InvoicePayment::where('order_key', $orderKey)->first();
        if ($payment) {
            $payment->update([
                'status_code'    => $action,
                'status_message' => $data['message'] ?? null,
                'ep_id'          => $data['ep_id'] ?? null,
                'paid_at'        => $paidAt,
                'status'         => $approved ? 'approved' : 'failed',
            ]);

            if ($approved) {
                Invoice::where('id', $payment->invoice_id)->update(['status' => 'paid']);
            }

            return ['invoice', $payment];
        }

        // ── Product acquisition payment ───────────────────────────────────────
        $acquisition = ProductAcquisition::where('order_key', $orderKey)->first();
        if ($acquisition) {
            $acquisition->update([
                'ep_id'                  => $data['ep_id'] ?? null,
                'payment_status_message' => $data['message'] ?? null,
                'paid_at'                => $paidAt,
                'acquisition_status'     => $approved ? 'paid' : 'payment_failed',
                'acquisition_date'       => $approved ? now()->toDateString() : $acquisition->acquisition_date,
            ]);

            return ['acquisition', $acquisition];
        }

        Log::warning('EuPlatesc callback: no record found for order_key ' . $orderKey);
        return ['unknown', null];
    }
}
