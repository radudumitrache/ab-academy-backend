<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionResponse extends Model
{
    use HasFactory;
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'response_id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'related_question',
        'related_student',
        'answer',
    ];
    
    /**
     * Get the question that this response is for.
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'related_question', 'question_id');
    }
    
    /**
     * Get the student who submitted this response.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'related_student');
    }
}
