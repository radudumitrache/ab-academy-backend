<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'action',
        'model',
        'model_id',
        'user_id',
        'user_role',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public static function logAction(string $action, string $model, $modelId, string $description, array $changes = null)
    {
        $user = auth('api')->user();
        
        return self::create([
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
