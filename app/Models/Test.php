<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $table = 'tests';

    protected $fillable = [
        'test_teacher',
        'test_title',
        'test_description',
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
        return $this->belongsTo(User::class, 'test_teacher');
    }

    public function sections()
    {
        return $this->hasMany(TestSection::class, 'test_id')->orderBy('order');
    }

    public function allQuestions()
    {
        return $this->hasManyThrough(TestQuestion::class, TestSection::class, 'test_id', 'test_section_id');
    }
}
