<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\EuPlatescService;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function __construct(private EuPlatescService $euplatesc) {}

    /**
     * List all invoices belonging to the authenticated student.
     */
    public function index()
    {
        $studentId = Auth::id();

        $invoices = Invoice::where('student_id', $studentId)
            ->orderByDesc('due_date')
            ->get()
            ->map(fn($inv) => $this->format($inv));

        return response()->json([
            'message'  => 'Invoices retrieved successfully',
            'count'    => $invoices->count(),
            'invoices' => $invoices,
        ]);
    }

    /**
     * Get a single invoice for the student.
     */
    public function show($id)
    {
        $invoice = Invoice::where('student_id', Auth::id())->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        return response()->json([
            'message' => 'Invoice retrieved successfully',
            'invoice' => $this->format($invoice, true),
        ]);
    }

    /**
     * Initiate an EuPlatesc payment for an invoice.
     *
     * Returns an HTML page with an auto-submitting form that redirects
     * the user's browser to the EuPlatesc hosted checkout page.
     */
    public function pay($id)
    {
        $student = Auth::user();
        $invoice = Invoice::where('student_id', $student->id)->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'Invoice is already paid'], 409);
        }

        if (in_array($invoice->status, ['cancelled', 'draft'])) {
            return response()->json(['message' => 'Invoice cannot be paid in its current status'], 422);
        }

        // Check for a pending payment already in progress
        $pending = InvoicePayment::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        // Reuse an existing pending attempt only if it was created in the last 30 minutes
        if ($pending && $pending->created_at->gt(now()->subMinutes(30))) {
            // Already have a fresh pending attempt — just re-initiate with same order_key is not
            // possible through the library, so we create a fresh one
        }

        try {
            $html = $this->euplatesc->initiatePayment($invoice, $student);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not initiate payment: ' . $e->getMessage(),
            ], 500);
        }

        // Return the HTML form directly — the browser auto-submits it to EuPlatesc
        return response($html, 200)->header('Content-Type', 'text/html');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function format(Invoice $invoice, bool $withPayments = false): array
    {
        $data = [
            'id'       => $invoice->id,
            'title'    => $invoice->title,
            'series'   => $invoice->series,
            'number'   => $invoice->number,
            'value'    => $invoice->value,
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_date?->toDateString(),
            'status'   => $invoice->status,
        ];

        if ($withPayments) {
            $data['payments'] = InvoicePayment::where('invoice_id', $invoice->id)
                ->orderByDesc('created_at')
                ->get(['id', 'order_key', 'amount', 'currency', 'status', 'status_message', 'paid_at', 'created_at']);
        }

        return $data;
    }
}
