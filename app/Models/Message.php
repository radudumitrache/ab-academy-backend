<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'message',
        'author',
        'message_text',
        'message_author',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function authorUser()
    {
        return $this->belongsTo(User::class, 'message_author');
    }
}
