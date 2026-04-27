<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAcquisition extends Model
{
    protected $fillable = [
        'payment_profile_id',
        'product_id',
        'student_id',
        'amount_paid',
        'currency',
        'order_key',
        'acquisition_status',
        'acquisition_notes',
        'groups_access',
        'tests_access',
        'acquisition_date',
        'completion_date',
        'is_completed',
        'remaining_courses',
        'invoice_series',
        'invoice_number',
        'ep_id',
        'payment_status_message',
        'paid_at',
        'renewed_from_id',
        'group_id',
        'marked_courses',
    ];

    protected $casts = [
        'amount_paid'       => 'decimal:2',
        'groups_access'     => 'array',
        'tests_access'      => 'array',
        'acquisition_date'  => 'date',
        'completion_date'   => 'date',
        'is_completed'      => 'boolean',
        'paid_at'           => 'datetime',
        'remaining_courses' => 'integer',
        'marked_courses'    => 'array',
    ];

    public function paymentProfile()
    {
        return $this->belongsTo(PaymentProfile::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function renewedFrom()
    {
        return $this->belongsTo(ProductAcquisition::class, 'renewed_from_id');
    }

    public function renewals()
    {
        return $this->hasMany(ProductAcquisition::class, 'renewed_from_id');
    }

    /**
     * Remove the student from all groups linked to this acquisition.
     */
    public function removeStudentFromGroups(): void
    {
        $groupIds = $this->groups_access ?? [];

        if (empty($groupIds)) {
            return;
        }

        foreach ($groupIds as $groupId) {
            $group = Group::find($groupId);
            if ($group) {
                $group->students()->detach($this->student_id);
            }
        }
    }
}
