<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'question_id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_text',
        'homework_id',
        'question_type',
    ];
    
    /**
     * Get the homework that owns the question.
     */
    public function homework()
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }
    
    /**
     * Get the multiple choice details for this question.
     */
    public function multipleChoiceDetails()
    {
        return $this->hasOne(MultipleChoiceQuestion::class, 'question_id');
    }
    
    /**
     * Get the responses for this question.
     */
    public function responses()
    {
        return $this->hasMany(QuestionResponse::class, 'related_question');
    }
}
