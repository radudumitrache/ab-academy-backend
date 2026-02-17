<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_recipient',
        'admin_recipient',
        'date_created',
    ];

    protected $casts = [
        'date_created' => 'datetime',
    ];

    public function studentRecipient()
    {
        return $this->belongsTo(User::class, 'student_recipient');
    }

    public function adminRecipient()
    {
        return $this->belongsTo(User::class, 'admin_recipient');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeFilterStudentRecipient($query, $studentRecipient)
    {
        return $query->where('student_recipient', $studentRecipient);
    }

    public function scopeFilterDateCreated($query, $date)
    {
        return $query->whereDate('date_created', $date);
    }
}
