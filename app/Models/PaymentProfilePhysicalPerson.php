<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProfilePhysicalPerson extends Model
{
    protected $fillable = [
        'payment_profile_id',
        'first_name',
        'last_name',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip_code',
        'billing_country',
    ];

    public function profile()
    {
        return $this->belongsTo(PaymentProfile::class, 'payment_profile_id');
    }
}
