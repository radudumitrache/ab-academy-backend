<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PaymentProfile;
use App\Models\Product;
use App\Models\ProductAcquisition;
use App\Services\EuPlatescService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductAcquisitionController extends Controller
{
    public function __construct(private EuPlatescService $euplatesc) {}

    /**
     * List all acquisitions for the authenticated student.
     */
    public function index()
    {
        $acquisitions = ProductAcquisition::where('student_id', Auth::id())
            ->with(['product', 'paymentProfile'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($a) => $this->format($a));

        return response()->json([
            'message'      => 'Acquisitions retrieved successfully',
            'count'        => $acquisitions->count(),
            'acquisitions' => $acquisitions,
        ]);
    }

    /**
     * Show a single acquisition.
     */
    public function show($id)
    {
        $acquisition = ProductAcquisition::where('student_id', Auth::id())
            ->with(['product', 'paymentProfile'])
            ->find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        return response()->json([
            'message'     => 'Acquisition retrieved successfully',
            'acquisition' => $this->format($acquisition),
        ]);
    }

    /**
     * Initiate a product purchase.
     *
     * Creates a pending acquisition and returns an EuPlatesc checkout form.
     */
    public function purchase(Request $request, $id)
    {
        $data = $request->validate([
            'payment_profile_id' => 'required|integer',
        ]);

        $student = Auth::user();

        $product = Product::where('is_active', true)->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found or not available'], 404);
        }

        $profile = PaymentProfile::where('user_id', $student->id)->find($data['payment_profile_id']);
        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        // Convert price from EUR to the profile's preferred currency
        $amountEur = (float) $product->price;
        if ($profile->currency === 'RON') {
            $rate      = (float) config('payment.eur_to_ron_rate', 4.95);
            $amount    = round($amountEur * $rate, 2);
            $currency  = 'RON';
        } else {
            $amount   = $amountEur;
            $currency = 'EUR';
        }

        // Create a pending acquisition record
        $acquisition = ProductAcquisition::create([
            'payment_profile_id' => $profile->id,
            'product_id'         => $product->id,
            'student_id'         => $student->id,
            'amount_paid'        => $amount,
            'currency'           => $currency,
            'acquisition_status' => 'pending_payment',
        ]);

        try {
            $html = $this->euplatesc->initiateProductPayment($acquisition, $student, $product);
        } catch (\Throwable $e) {
            $acquisition->delete();
            return response()->json([
                'message' => 'Could not initiate payment: ' . $e->getMessage(),
            ], 500);
        }

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * Renew a completed or expired acquisition.
     *
     * Creates a new acquisition record linked to the original and initiates payment.
     */
    public function renew(Request $request, $id)
    {
        $student = Auth::user();

        $original = ProductAcquisition::where('student_id', $student->id)->find($id);
        if (!$original) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!in_array($original->acquisition_status, ['completed', 'expired'])) {
            return response()->json([
                'message' => 'Only completed or expired acquisitions can be renewed',
            ], 422);
        }

        $product = Product::where('is_active', true)->find($original->product_id);
        if (!$product) {
            return response()->json(['message' => 'Product is no longer available'], 422);
        }

        // Allow switching payment profile on renewal
        $profileId = $request->input('payment_profile_id', $original->payment_profile_id);
        $profile   = PaymentProfile::where('user_id', $student->id)->find($profileId);
        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        $amountEur = (float) $product->price;
        if ($profile->currency === 'RON') {
            $rate     = (float) config('payment.eur_to_ron_rate', 4.95);
            $amount   = round($amountEur * $rate, 2);
            $currency = 'RON';
        } else {
            $amount   = $amountEur;
            $currency = 'EUR';
        }

        $renewal = ProductAcquisition::create([
            'payment_profile_id' => $profile->id,
            'product_id'         => $product->id,
            'student_id'         => $student->id,
            'amount_paid'        => $amount,
            'currency'           => $currency,
            'acquisition_status' => 'pending_payment',
            'groups_access'      => $original->groups_access,  // carry over same access
            'tests_access'       => $original->tests_access,
            'renewed_from_id'    => $original->id,
        ]);

        try {
            $html = $this->euplatesc->initiateProductPayment($renewal, $student, $product);
        } catch (\Throwable $e) {
            $renewal->delete();
            return response()->json([
                'message' => 'Could not initiate payment: ' . $e->getMessage(),
            ], 500);
        }

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function format(ProductAcquisition $a): array
    {
        return [
            'id'                 => $a->id,
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
            'acquisition_date'   => $a->acquisition_date?->toDateString(),
            'completion_date'    => $a->completion_date?->toDateString(),
            'is_completed'       => $a->is_completed,
            'invoice_series'     => $a->invoice_series,
            'invoice_number'     => $a->invoice_number,
            'groups_access'      => $a->groups_access,
            'tests_access'       => $a->tests_access,
            'renewed_from_id'    => $a->renewed_from_id,
            'created_at'         => $a->created_at,
        ];
    }
}
