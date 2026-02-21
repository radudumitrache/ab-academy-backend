<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultipleChoiceQuestion extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id',
        'variants',
        'correct_variant',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'variants' => 'array',
    ];
    
    /**
     * Get the question that owns the multiple choice details.
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }
}
