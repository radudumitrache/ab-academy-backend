<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProfile;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentProfileController extends Controller
{
    /**
     * List all payment profiles across all students.
     * Optionally filter by student_id, type, or whether invoice confirmation is pending.
     */
    public function index(Request $request)
    {
        $query = PaymentProfile::with(['user:id,username,email', 'physicalPerson', 'company'])
            ->orderByDesc('created_at');

        if ($request->filled('student_id')) {
            $query->where('user_id', $request->student_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        // Show only profiles that have observations/invoice_text but invoice not yet confirmed
        if ($request->boolean('needs_confirmation')) {
            $query->where('invoice_confirmed', false)
                  ->where(function ($q) {
                      $q->whereNotNull('observations')
                        ->orWhereNotNull('invoice_text');
                  });
        }

        $profiles = $query->get()->map(fn($p) => $this->format($p));

        return response()->json([
            'message'  => 'Payment profiles retrieved successfully',
            'count'    => $profiles->count(),
            'profiles' => $profiles,
        ]);
    }

    /**
     * Show a single payment profile with full details and acquisition summary.
     */
    public function show($id)
    {
        $profile = PaymentProfile::with(['user:id,username,email', 'physicalPerson', 'company', 'acquisitions.product'])
            ->find($id);

        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        return response()->json([
            'message' => 'Payment profile retrieved successfully',
            'profile' => $this->format($profile, true),
        ]);
    }

    /**
     * List all payment profiles for a specific student.
     */
    public function forStudent($studentId)
    {
        $student = User::find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $profiles = PaymentProfile::where('user_id', $studentId)
            ->with(['physicalPerson', 'company'])
            ->get()
            ->map(fn($p) => $this->format($p));

        return response()->json([
            'message'  => 'Payment profiles retrieved successfully',
            'student'  => ['id' => $student->id, 'username' => $student->username],
            'count'    => $profiles->count(),
            'profiles' => $profiles,
        ]);
    }

    /**
     * Set the invoice_text for a payment profile and mark it as confirmed.
     *
     * This is the admin step required for profiles that have special billing mentions
     * (observations / invoice_text). Once confirmed, subsequent invoices are auto-generated.
     */
    public function setInvoiceText(Request $request, $id)
    {
        $profile = PaymentProfile::find($id);
        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        $data = $request->validate([
            'invoice_text' => 'required|string',
        ]);

        $profile->update([
            'invoice_text'      => $data['invoice_text'],
            'invoice_confirmed' => true,
        ]);

        return response()->json([
            'message' => 'Invoice text saved and profile confirmed',
            'profile' => $this->format($profile->fresh(['physicalPerson', 'company'])),
        ]);
    }

    /**
     * Confirm a payment profile without changing invoice_text.
     * Used when no special text is needed but the admin reviewed the profile.
     */
    public function confirm($id)
    {
        $profile = PaymentProfile::find($id);
        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        $profile->update(['invoice_confirmed' => true]);

        return response()->json([
            'message' => 'Payment profile confirmed',
            'profile' => $this->format($profile->fresh(['physicalPerson', 'company'])),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function format(PaymentProfile $profile, bool $withAcquisitions = false): array
    {
        $data = [
            'id'                 => $profile->id,
            'student'            => $profile->user ? [
                'id'       => $profile->user->id,
                'username' => $profile->user->username,
                'email'    => $profile->user->email,
            ] : null,
            'type'               => $profile->type,
            'nickname'           => $profile->nickname,
            'currency'           => $profile->currency,
            'observations'       => $profile->observations,
            'invoice_text'       => $profile->invoice_text,
            'invoice_confirmed'  => $profile->invoice_confirmed,
            'details'            => null,
        ];

        if ($profile->type === 'physical_person' && $profile->physicalPerson) {
            $pp = $profile->physicalPerson;
            $data['details'] = [
                'first_name'       => $pp->first_name,
                'last_name'        => $pp->last_name,
                'billing_address'  => $pp->billing_address,
                'billing_city'     => $pp->billing_city,
                'billing_state'    => $pp->billing_state,
                'billing_zip_code' => $pp->billing_zip_code,
                'billing_country'  => $pp->billing_country,
            ];
        } elseif ($profile->type === 'company' && $profile->company) {
            $c = $profile->company;
            $data['details'] = [
                'cui'                   => $c->cui,
                'company_name'          => $c->company_name,
                'trade_register_number' => $c->trade_register_number,
                'registration_date'     => $c->registration_date?->toDateString(),
                'legal_address'         => $c->legal_address,
                'billing_address'       => $c->billing_address,
                'billing_city'          => $c->billing_city,
                'billing_state'         => $c->billing_state,
                'billing_zip_code'      => $c->billing_zip_code,
                'billing_country'       => $c->billing_country,
            ];
        }

        if ($withAcquisitions && $profile->relationLoaded('acquisitions')) {
            $data['acquisitions'] = $profile->acquisitions->map(fn($a) => [
                'id'                 => $a->id,
                'product'            => $a->product ? ['id' => $a->product->id, 'name' => $a->product->name] : null,
                'acquisition_status' => $a->acquisition_status,
                'acquisition_date'   => $a->acquisition_date?->toDateString(),
                'amount_paid'        => $a->amount_paid,
                'currency'           => $a->currency,
            ]);
        }

        return $data;
    }
}
