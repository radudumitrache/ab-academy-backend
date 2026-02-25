<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use HasFactory;

    protected $fillable = [
        'homework_teacher',
        'homework_title',
        'homework_description',
        'due_date',
        'people_assigned',
        'groups_assigned',
        'date_created',
    ];

    protected $casts = [
        'due_date'        => 'date:Y-m-d',
        'people_assigned' => 'array',
        'groups_assigned' => 'array',
        'date_created'    => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'homework_teacher');
    }

    /**
     * Top-level questions (not belonging to a reading/listening section).
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'homework_id')
                    ->whereNull('section_id')
                    ->orderBy('order');
    }

    /**
     * All questions including those inside sections.
     */
    public function allQuestions()
    {
        return $this->hasMany(Question::class, 'homework_id')->orderBy('order');
    }

    public function readingSections()
    {
        return $this->hasMany(ReadingSection::class, 'homework_id')->orderBy('order');
    }

    public function listeningSections()
    {
        return $this->hasMany(ListeningSection::class, 'homework_id')->orderBy('order');
    }
}
