<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'homework_title',
        'homework_description',
        'due_date',
        'people_assigned',
        'groups_assigned',
        'date_created',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'due_date' => 'date',
        'people_assigned' => 'array',
        'groups_assigned' => 'array',
        'date_created' => 'datetime',
    ];
    
    /**
     * Get the questions for the homework.
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'homework_id');
    }
}
