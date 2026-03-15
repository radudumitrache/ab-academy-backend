<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'nickname',
        'currency',
        'observations',
        'invoice_text',
        'invoice_confirmed',
    ];

    protected $casts = [
        'invoice_confirmed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function physicalPerson()
    {
        return $this->hasOne(PaymentProfilePhysicalPerson::class);
    }

    public function company()
    {
        return $this->hasOne(PaymentProfileCompany::class);
    }

    public function acquisitions()
    {
        return $this->hasMany(ProductAcquisition::class);
    }

    /**
     * Load the appropriate subtype relationship based on type.
     */
    public function details()
    {
        if ($this->type === 'physical_person') {
            return $this->physicalPerson();
        }
        return $this->company();
    }
}
