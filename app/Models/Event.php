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
        'guest_groups',
        'event_meet_link',
        'event_start_link',
        'event_notes',
        'meeting_account_id',
        'recurrence_parent_id',
    ];

    protected $casts = [
        'event_date'   => 'date',
        'guests'       => 'array',
        'guest_groups' => 'array',
    ];

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'event_organizer');
    }

    public function meetingAccount()
    {
        return $this->belongsTo(MeetingAccount::class, 'meeting_account_id');
    }
}
