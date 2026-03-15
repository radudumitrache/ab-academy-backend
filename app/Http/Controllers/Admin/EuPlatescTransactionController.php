<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAcquisition;
use App\Services\EuPlatescService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EuPlatescTransactionController extends Controller
{
    public function __construct(private EuPlatescService $euplatesc) {}

    /**
     * List all product acquisition payment records (EuPlatesc transactions).
     *
     * Provides a consolidated view of what payments have been made through EuPlatesc,
     * who made them, for which product, and the current acquisition status.
     *
     * Filterable by student_id, acquisition_status, currency, and date range.
     */
    public function index(Request $request)
    {
        $query = ProductAcquisition::with(['student:id,username,email', 'product:id,name,type', 'paymentProfile:id,nickname,type'])
            ->whereNotNull('order_key')   // only records that went through EuPlatesc
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('status')) {
            $query->where('acquisition_status', $request->status);
        }
        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->currency));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $transactions = $query->get()->map(fn($a) => $this->formatTransaction($a));

        $totalPaid = $query->clone()
            ->where('acquisition_status', '!=', 'pending_payment')
            ->whereNotNull('paid_at')
            ->sum('amount_paid');

        return response()->json([
            'message'      => 'Transactions retrieved successfully',
            'count'        => $transactions->count(),
            'total_paid'   => number_format((float) $totalPaid, 2),
            'transactions' => $transactions,
        ]);
    }

    /**
     * Check the live status of a specific transaction via the EuPlatesc back-end API.
     *
     * Uses the ep_id stored on the acquisition to query EuPlatesc's status API.
     */
    public function checkStatus($id)
    {
        $acquisition = ProductAcquisition::find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!$acquisition->ep_id) {
            return response()->json([
                'message' => 'No EuPlatesc transaction ID (ep_id) on this acquisition yet',
            ], 422);
        }

        try {
            $status = $this->euplatesc->checkStatus($acquisition->ep_id);
        } catch (\Throwable $e) {
            Log::error('EuPlatesc checkStatus error', ['ep_id' => $acquisition->ep_id, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'EuPlatesc status check failed: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message'     => 'Status retrieved from EuPlatesc',
            'ep_id'       => $acquisition->ep_id,
            'ep_status'   => $status,
            'acquisition' => $this->formatTransaction($acquisition),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function formatTransaction(ProductAcquisition $a): array
    {
        return [
            'acquisition_id'     => $a->id,
            'order_key'          => $a->order_key,
            'ep_id'              => $a->ep_id,
            'student'            => $a->student ? [
                'id'       => $a->student->id,
                'username' => $a->student->username,
                'email'    => $a->student->email,
            ] : null,
            'product'            => $a->product ? [
                'id'   => $a->product->id,
                'name' => $a->product->name,
                'type' => $a->product->type,
            ] : null,
            'payment_profile'    => $a->paymentProfile ? [
                'id'       => $a->paymentProfile->id,
                'nickname' => $a->paymentProfile->nickname,
                'type'     => $a->paymentProfile->type,
            ] : null,
            'amount_paid'        => $a->amount_paid,
            'currency'           => $a->currency,
            'acquisition_status' => $a->acquisition_status,
            'payment_status_message' => $a->payment_status_message,
            'paid_at'            => $a->paid_at,
            'invoice_series'     => $a->invoice_series,
            'invoice_number'     => $a->invoice_number,
            'created_at'         => $a->created_at,
        ];
    }
}
