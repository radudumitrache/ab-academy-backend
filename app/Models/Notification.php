<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_owner',
        'notification_message',
        'notification_time',
        'is_seen',
    ];

    protected $casts = [
        'notification_time' => 'datetime',
        'is_seen' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'notification_owner');
    }

    public function scopeFilterNotificationOwner($query, $ownerId)
    {
        return $query->where('notification_owner', $ownerId);
    }

    public function scopeFilterNotificationMessage($query, $message)
    {
        return $query->where('notification_message', 'like', '%' . $message . '%');
    }

    public function scopeFilterNotificationTime($query, $notificationTime)
    {
        return $query->whereDate('notification_time', $notificationTime);
    }

    public function scopeFilterIsSeen($query, $isSeen)
    {
        return $query->where('is_seen', $isSeen);
    }
}
