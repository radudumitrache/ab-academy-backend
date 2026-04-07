<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PaymentProfile;
use App\Models\PaymentProfileCompany;
use App\Models\PaymentProfilePhysicalPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentProfileController extends Controller
{
    /**
     * List all payment profiles for the authenticated student.
     */
    public function index()
    {
        $profiles = PaymentProfile::where('user_id', Auth::id())
            ->with(['physicalPerson', 'company'])
            ->get()
            ->map(fn($p) => $this->format($p));

        return response()->json([
            'message'  => 'Payment profiles retrieved successfully',
            'count'    => $profiles->count(),
            'profiles' => $profiles,
        ]);
    }

    /**
     * Show a single payment profile.
     */
    public function show($id)
    {
        $profile = PaymentProfile::where('user_id', Auth::id())
            ->with(['physicalPerson', 'company'])
            ->find($id);

        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        return response()->json([
            'message' => 'Payment profile retrieved successfully',
            'profile' => $this->format($profile),
        ]);
    }

    /**
     * Create a new payment profile.
     */
    public function store(Request $request)
    {
        $base = $request->validate([
            'type'         => 'required|in:physical_person,company',
            'nickname'     => 'required|string|max:255',
            'currency'     => 'required|in:EUR,RON',
            'observations' => 'nullable|string',
        ]);

        if ($request->type === 'physical_person') {
            $details = $request->validate([
                'first_name'      => 'required|string|max:255',
                'last_name'       => 'required|string|max:255',
                'billing_address' => 'nullable|string|max:500',
                'billing_city'    => 'nullable|string|max:255',
                'billing_state'   => 'nullable|string|max:255',
                'billing_zip_code'=> 'nullable|string|max:20',
                'billing_country' => 'nullable|string|max:255',
            ]);
        } else {
            $details = $request->validate([
                'cui'                  => 'required|string|max:50',
                'company_name'         => 'required|string|max:500',
                'trade_register_number'=> 'required|string|max:100',
                'registration_date'    => 'nullable|date',
                'legal_address'        => 'required|string|max:500',
                'billing_address'      => 'required|string|max:500',
                'billing_city'         => 'required|string|max:255',
                'billing_state'        => 'nullable|string|max:255',
                'billing_zip_code'     => 'nullable|string|max:20',
                'billing_country'      => 'nullable|string|max:255',
            ]);
        }

        $profile = DB::transaction(function () use ($base, $details, $request) {
            $profile = PaymentProfile::create([
                'user_id'      => Auth::id(),
                'type'         => $base['type'],
                'nickname'     => $base['nickname'],
                'currency'     => $base['currency'],
                'observations' => $base['observations'] ?? null,
            ]);

            if ($request->type === 'physical_person') {
                PaymentProfilePhysicalPerson::create(array_merge(
                    ['payment_profile_id' => $profile->id],
                    $details
                ));
            } else {
                PaymentProfileCompany::create(array_merge(
                    ['payment_profile_id' => $profile->id],
                    $details
                ));
            }

            return $profile->load(['physicalPerson', 'company']);
        });

        return response()->json([
            'message' => 'Payment profile created successfully',
            'profile' => $this->format($profile),
        ], 201);
    }

    /**
     * Update an existing payment profile.
     */
    public function update(Request $request, $id)
    {
        $profile = PaymentProfile::where('user_id', Auth::id())->find($id);

        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        $base = $request->validate([
            'nickname'     => 'sometimes|string|max:255',
            'currency'     => 'sometimes|in:EUR,RON',
            'observations' => 'nullable|string',
        ]);

        if ($profile->type === 'physical_person') {
            $details = $request->validate([
                'first_name'      => 'sometimes|string|max:255',
                'last_name'       => 'sometimes|string|max:255',
                'billing_address' => 'sometimes|string|max:500',
                'billing_city'    => 'sometimes|string|max:255',
                'billing_state'   => 'nullable|string|max:255',
                'billing_zip_code'=> 'nullable|string|max:20',
                'billing_country' => 'nullable|string|max:255',
            ]);
        } else {
            $details = $request->validate([
                'cui'                  => 'sometimes|string|max:50',
                'company_name'         => 'sometimes|string|max:500',
                'trade_register_number'=> 'sometimes|string|max:100',
                'registration_date'    => 'nullable|date',
                'legal_address'        => 'sometimes|string|max:500',
                'billing_address'      => 'sometimes|string|max:500',
                'billing_city'         => 'sometimes|string|max:255',
                'billing_state'        => 'nullable|string|max:255',
                'billing_zip_code'     => 'nullable|string|max:20',
                'billing_country'      => 'nullable|string|max:255',
            ]);
        }

        DB::transaction(function () use ($profile, $base, $details) {
            $profile->update($base);

            if ($profile->type === 'physical_person') {
                $profile->physicalPerson()->updateOrCreate(
                    ['payment_profile_id' => $profile->id],
                    $details
                );
            } else {
                $profile->company()->updateOrCreate(
                    ['payment_profile_id' => $profile->id],
                    $details
                );
            }
        });

        $profile->load(['physicalPerson', 'company']);

        return response()->json([
            'message' => 'Payment profile updated successfully',
            'profile' => $this->format($profile),
        ]);
    }

    /**
     * Delete a payment profile (soft delete).
     */
    public function destroy($id)
    {
        $profile = PaymentProfile::where('user_id', Auth::id())->find($id);

        if (!$profile) {
            return response()->json(['message' => 'Payment profile not found'], 404);
        }

        // Prevent deletion if it has pending or active acquisitions
        $activeAcquisitions = $profile->acquisitions()
            ->whereIn('acquisition_status', ['pending_payment', 'paid', 'active'])
            ->exists();

        if ($activeAcquisitions) {
            return response()->json([
                'message' => 'Cannot delete a payment profile with active acquisitions',
            ], 422);
        }

        $profile->delete();

        return response()->json(['message' => 'Payment profile deleted successfully']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function format(PaymentProfile $profile): array
    {
        $data = [
            'id'           => $profile->id,
            'type'         => $profile->type,
            'nickname'     => $profile->nickname,
            'currency'     => $profile->currency,
            'observations' => $profile->observations,
            'details'      => null,
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

        return $data;
    }
}
