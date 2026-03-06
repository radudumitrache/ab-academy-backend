<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $primaryKey = 'material_id';

    protected $fillable = [
        'material_name',
        'file_type',
        'date_created',
        'authors',
        'allowed_users',
        'gcs_path',
        'uploader_id',
        'folder',
    ];

    protected $casts = [
        'date_created'  => 'datetime',
        'authors'       => 'array',
        'allowed_users' => 'array',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id', 'id');
    }
}
