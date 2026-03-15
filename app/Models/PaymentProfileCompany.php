<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProfileCompany extends Model
{
    protected $fillable = [
        'payment_profile_id',
        'cui',
        'company_name',
        'trade_register_number',
        'registration_date',
        'legal_address',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip_code',
        'billing_country',
    ];

    protected $casts = [
        'registration_date' => 'date',
    ];

    public function profile()
    {
        return $this->belongsTo(PaymentProfile::class, 'payment_profile_id');
    }
}
