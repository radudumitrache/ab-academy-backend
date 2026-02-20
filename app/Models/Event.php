<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'event_date',
        'event_time',
        'event_duration',
        'event_organizer',
        'guests',
        'present_guests',
        'event_meet_link',
        'event_notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'guests' => 'array',
        'present_guests' => 'array',
    ];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'event_organizer');
    }
}
