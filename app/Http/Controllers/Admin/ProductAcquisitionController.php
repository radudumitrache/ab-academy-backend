<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAcquisition;
use App\Services\SmartBillService;
use Illuminate\Http\Request;

class ProductAcquisitionController extends Controller
{
    public function __construct(private SmartBillService $smartbill) {}

    /**
     * List all acquisitions, optionally filtered by student, status, or product.
     * Also accepts ?needs_groups=1 to show paid acquisitions with no groups assigned yet.
     */
    public function index(Request $request)
    {
        $query = ProductAcquisition::with(['product', 'student:id,username,email', 'paymentProfile'])
            ->orderByDesc('created_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('status')) {
            $query->where('acquisition_status', $request->status);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        // Quick filter: active acquisitions with no groups assigned yet
        if ($request->boolean('needs_groups')) {
            $query->where('acquisition_status', 'active')
                  ->whereNull('groups_access');
        }

        $acquisitions = $query->get()->map(fn($a) => $this->format($a));

        return response()->json([
            'message'      => 'Acquisitions retrieved successfully',
            'count'        => $acquisitions->count(),
            'acquisitions' => $acquisitions,
        ]);
    }

    /**
     * Show a single acquisition with full payment profile details.
     */
    public function show($id)
    {
        $acquisition = ProductAcquisition::with([
            'product',
            'student:id,username,email',
            'paymentProfile.physicalPerson',
            'paymentProfile.company',
        ])->find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        return response()->json([
            'message'     => 'Acquisition retrieved successfully',
            'acquisition' => $this->format($acquisition, true),
        ]);
    }

    /**
     * Manually create an acquisition on behalf of a student.
     * Used for cash/bank-transfer payments that bypass EuPlatesc.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'         => 'required|integer|exists:users,id',
            'product_id'         => 'required|integer|exists:products,id',
            'payment_profile_id' => 'nullable|integer|exists:payment_profiles,id',
            'amount_paid'        => 'required|numeric|min:0',
            'currency'           => 'required|in:RON,EUR',
            'acquisition_status' => 'required|in:pending_payment,paid,active,completed,cancelled,expired',
            'acquisition_date'   => 'nullable|date',
            'acquisition_notes'  => 'nullable|string',
            'groups_access'      => 'nullable|array',
            'groups_access.*'    => 'integer|exists:groups,group_id',
            'tests_access'       => 'nullable|array',
            'tests_access.*'     => 'integer|exists:tests,id',
        ]);

        $acquisition = ProductAcquisition::create($data);

        return response()->json([
            'message'     => 'Acquisition created successfully',
            'acquisition' => $this->format($acquisition->load(['product', 'student', 'paymentProfile'])),
        ], 201);
    }

    /**
     * Grant access to groups/tests for a paid acquisition and mark it active.
     */
    public function grantAccess(Request $request, $id)
    {
        $acquisition = ProductAcquisition::with('product')->find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if ($acquisition->acquisition_status !== 'paid') {
            return response()->json([
                'message' => 'Access can only be granted to paid acquisitions',
            ], 422);
        }

        $data = $request->validate([
            'groups_access'    => 'nullable|array',
            'groups_access.*'  => 'integer|exists:groups,group_id',
            'tests_access'     => 'nullable|array',
            'tests_access.*'   => 'integer|exists:tests,id',
            'invoice_series'   => 'nullable|string|max:50',
            'invoice_number'   => 'nullable|string|max:50',
            'acquisition_notes'=> 'nullable|string',
        ]);

        $acquisition->update(array_merge($data, [
            'acquisition_status' => 'active',
            'acquisition_date'   => $acquisition->acquisition_date ?? now()->toDateString(),
        ]));

        return response()->json([
            'message'     => 'Access granted successfully',
            'acquisition' => $this->format($acquisition->fresh(['product', 'student', 'paymentProfile'])),
        ]);
    }

    /**
     * Create a SmartBill invoice for an acquisition.
     *
     * Flow:
     * - If the payment profile has observations/invoice_text and has NOT been confirmed yet,
     *   this returns a 422 prompting the admin to complete the invoice_text and confirm first.
     * - Once the profile is confirmed (or has no special text), the invoice is created in SmartBill
     *   and the acquisition is updated with the invoice_series and invoice_number.
     */
    public function createInvoice(Request $request)
    {
        $data = $request->validate([
            'id'     => 'required|integer',
            'series' => 'required|string|max:50',
        ]);

        $acquisition = ProductAcquisition::with([
            'paymentProfile.physicalPerson',
            'paymentProfile.company',
            'product',
            'student',
        ])->find($data['id']);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!in_array($acquisition->acquisition_status, ['paid', 'active'])) {
            return response()->json([
                'message' => 'Invoice can only be created for paid or active acquisitions',
            ], 422);
        }

        if ($acquisition->invoice_number) {
            return response()->json([
                'message' => 'An invoice has already been created for this acquisition',
                'invoice_series' => $acquisition->invoice_series,
                'invoice_number' => $acquisition->invoice_number,
            ], 409);
        }

        $profile = $acquisition->paymentProfile;

        // If the profile has special mentions (observations or invoice_text) and has not
        // been confirmed by the admin yet, block invoice creation.
        if ($profile && !$profile->invoice_confirmed &&
            ($profile->observations || $profile->invoice_text)) {
            return response()->json([
                'message'    => 'This payment profile has billing mentions that require admin confirmation before the first invoice can be issued. Please set the invoice_text and confirm the profile first.',
                'profile_id' => $profile->id,
                'needs_confirmation' => true,
            ], 422);
        }

        try {
            $smartbillNumber = $this->smartbill->createInvoiceForAcquisition($acquisition, $data['series']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'SmartBill invoice creation failed: ' . $e->getMessage(),
            ], 502);
        }

        $acquisition->update([
            'invoice_series' => $data['series'],
            'invoice_number' => $smartbillNumber,
        ]);

        return response()->json([
            'message'        => 'Invoice created successfully',
            'invoice_series' => $data['series'],
            'invoice_number' => $smartbillNumber,
            'acquisition'    => $this->format($acquisition->fresh(['product', 'student', 'paymentProfile'])),
        ]);
    }

    /**
     * Mark the SmartBill invoice for an acquisition as paid.
     */
    public function markInvoicePaid($id)
    {
        $acquisition = ProductAcquisition::find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!$acquisition->invoice_number) {
            return response()->json([
                'message' => 'No invoice exists for this acquisition yet',
            ], 422);
        }

        try {
            $this->smartbill->markAcquisitionPaid($acquisition);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'SmartBill mark-paid failed: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json(['message' => 'Invoice marked as paid in SmartBill']);
    }

    /**
     * Send the SmartBill invoice for an acquisition by email.
     */
    public function sendInvoiceByEmail(Request $request, $id)
    {
        $acquisition = ProductAcquisition::with('student:id,username,email')->find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!$acquisition->invoice_number || !$acquisition->invoice_series) {
            return response()->json([
                'message' => 'No invoice exists for this acquisition yet',
            ], 422);
        }

        $data = $request->validate([
            'email' => 'nullable|email',
        ]);

        $email = $data['email'] ?? $acquisition->student?->email;

        if (!$email) {
            return response()->json([
                'message' => 'No email address provided and the student has no email on record',
            ], 422);
        }

        try {
            $this->smartbill->sendInvoiceByEmail(
                $acquisition->invoice_series,
                $acquisition->invoice_number,
                $email,
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'SmartBill send-email failed: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'Invoice sent by email successfully',
            'email'   => $email,
        ]);
    }

    /**
     * Send the SmartBill invoice to SPV (ANAF e-Factura) for an acquisition.
     */
    public function sendInvoiceToSpv($id)
    {
        $acquisition = ProductAcquisition::find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!$acquisition->invoice_number || !$acquisition->invoice_series) {
            return response()->json(['message' => 'No invoice exists for this acquisition yet'], 422);
        }

        try {
            $this->smartbill->sendInvoiceToSpv(
                $acquisition->invoice_series,
                $acquisition->invoice_number,
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'SmartBill SPV submission failed: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json(['message' => 'Invoice sent to SPV successfully']);
    }

    /**
     * Download the SmartBill invoice PDF for an acquisition.
     */
    public function downloadInvoice($id)
    {
        $acquisition = ProductAcquisition::find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        if (!$acquisition->invoice_number || !$acquisition->invoice_series) {
            return response()->json(['message' => 'No invoice exists for this acquisition yet'], 422);
        }

        try {
            $pdf = $this->smartbill->downloadInvoicePdf(
                $acquisition->invoice_series,
                $acquisition->invoice_number,
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'SmartBill PDF download failed: ' . $e->getMessage(),
            ], 502);
        }

        $filename = "invoice-{$acquisition->invoice_series}-{$acquisition->invoice_number}.pdf";

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Update acquisition status (complete, cancel, expire, etc.).
     */
    public function updateStatus(Request $request, $id)
    {
        $acquisition = ProductAcquisition::find($id);

        if (!$acquisition) {
            return response()->json(['message' => 'Acquisition not found'], 404);
        }

        $data = $request->validate([
            'acquisition_status' => 'required|in:pending_payment,paid,active,completed,cancelled,expired',
            'completion_date'    => 'nullable|date',
            'is_completed'       => 'boolean',
            'acquisition_notes'  => 'nullable|string',
        ]);

        $acquisition->update($data);

        return response()->json([
            'message'     => 'Acquisition updated successfully',
            'acquisition' => $this->format($acquisition->fresh(['product', 'student', 'paymentProfile'])),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function format(ProductAcquisition $a, bool $withProfileDetails = false): array
    {
        $profileData = null;
        if ($a->paymentProfile) {
            $p = $a->paymentProfile;
            $profileData = [
                'id'                => $p->id,
                'nickname'          => $p->nickname,
                'type'              => $p->type,
                'currency'          => $p->currency,
                'observations'      => $p->observations,
                'invoice_text'      => $p->invoice_text,
                'invoice_confirmed' => $p->invoice_confirmed,
            ];
            if ($withProfileDetails) {
                if ($p->type === 'physical_person' && $p->physicalPerson) {
                    $pp = $p->physicalPerson;
                    $profileData['details'] = [
                        'first_name'      => $pp->first_name,
                        'last_name'       => $pp->last_name,
                        'billing_address' => $pp->billing_address,
                        'billing_city'    => $pp->billing_city,
                        'billing_country' => $pp->billing_country,
                    ];
                } elseif ($p->type === 'company' && $p->company) {
                    $c = $p->company;
                    $profileData['details'] = [
                        'cui'           => $c->cui,
                        'company_name'  => $c->company_name,
                        'billing_address' => $c->billing_address,
                        'billing_city'  => $c->billing_city,
                        'billing_country' => $c->billing_country,
                    ];
                }
            }
        }

        return [
            'id'                 => $a->id,
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
            'payment_profile'    => $profileData,
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
            'acquisition_notes'  => $a->acquisition_notes,
            'renewed_from_id'    => $a->renewed_from_id,
            'paid_at'            => $a->paid_at,
            'created_at'         => $a->created_at,
        ];
    }
}
