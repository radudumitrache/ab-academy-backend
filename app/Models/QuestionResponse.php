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
        'submission_id',
        'related_question',
        'related_student',
        'answer',
    ];
    
    public function submission()
    {
        return $this->belongsTo(HomeworkSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'related_question', 'question_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'related_student');
    }
}
