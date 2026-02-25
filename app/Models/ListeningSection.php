<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListeningSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'homework_id',
        'title',
        'audio_url',
        'transcript',
        'order',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'section_id')
                    ->where('section_type', 'listening');
    }
}
