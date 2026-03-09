<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'account_id',
        'client_id',
        'client_secret',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'client_id'     => 'encrypted',
        'client_secret' => 'encrypted',
        'is_active'     => 'boolean',
    ];

    // Never expose credentials in JSON responses
    protected $hidden = ['client_id', 'client_secret'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'meeting_account_id');
    }
}
