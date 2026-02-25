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
     * All sections (any type) ordered by display order.
     */
    public function sections()
    {
        return $this->hasMany(HomeworkSection::class, 'homework_id')->orderBy('order');
    }

    /**
     * Convenience scoped accessors per section type.
     */
    public function grammarAndVocabularySections()
    {
        return $this->hasMany(HomeworkSection::class, 'homework_id')
                    ->where('section_type', 'GrammarAndVocabulary')->orderBy('order');
    }

    public function writingSections()
    {
        return $this->hasMany(HomeworkSection::class, 'homework_id')
                    ->where('section_type', 'Writing')->orderBy('order');
    }

    public function readingSections()
    {
        return $this->hasMany(HomeworkSection::class, 'homework_id')
                    ->where('section_type', 'Reading')->orderBy('order');
    }

    public function listeningSections()
    {
        return $this->hasMany(HomeworkSection::class, 'homework_id')
                    ->where('section_type', 'Listening')->orderBy('order');
    }

    /**
     * All questions across all sections (via hasManyThrough).
     */
    public function allQuestions()
    {
        return $this->hasManyThrough(Question::class, HomeworkSection::class, 'homework_id', 'section_id');
    }
}
