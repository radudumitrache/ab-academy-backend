<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupAnnouncement extends Model
{
    use HasFactory;

    protected $primaryKey = 'announcement_id';

    protected $fillable = [
        'title',
        'group_id',
        'message',
        'attached_files',
        'time_created',
    ];

    protected $casts = [
        'attached_files' => 'array',
        'time_created'   => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function attachedMaterials()
    {
        return Material::whereIn('material_id', $this->attached_files ?? []);
    }
}
