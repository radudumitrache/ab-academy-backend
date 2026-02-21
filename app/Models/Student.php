<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('role', function ($query) {
            $query->where('role', 'student');
        });

        static::creating(function ($model) {
            $model->role = 'student';
        });
    }
    
    /**
     * Get the exams that the student is enrolled in.
     */
    public function enrolledExams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'student_exam')
                    ->withPivot('score', 'feedback')
                    ->withTimestamps();
    }
    
    /**
     * Get the groups that the student belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_student', 'student_id', 'group_id');
    }
    
    /**
     * Get the products that the student has purchased.
     */
    public function purchasedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'student_product', 'student_id', 'product_id')
                    ->withPivot('purchased_at', 'purchase_price')
                    ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function isTeacher(): bool
    {
        return false;
    }

    public function isStudent(): bool
    {
        return true;
    }
}
