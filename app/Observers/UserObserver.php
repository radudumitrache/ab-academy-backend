<?php

namespace App\Observers;

use App\Models\User;
use App\Models\DatabaseLog;

class UserObserver
{
    public function created(User $user)
    {
        DatabaseLog::logAction(
            'created',
            get_class($user),
            $user->id,
            "User '{$user->username}' with role '{$user->role}' was created",
            [
                'username' => $user->username,
                'role' => $user->role,
            ]
        );
    }

    public function updated(User $user)
    {
        $changes = $user->getChanges();
        unset($changes['updated_at']);

        if (!empty($changes)) {
            DatabaseLog::logAction(
                'updated',
                get_class($user),
                $user->id,
                "User '{$user->username}' (ID: {$user->id}) was updated",
                [
                    'old' => $user->getOriginal(),
                    'new' => $changes,
                ]
            );
        }
    }

    public function deleted(User $user)
    {
        DatabaseLog::logAction(
            'deleted',
            get_class($user),
            $user->id,
            "User '{$user->username}' (ID: {$user->id}) with role '{$user->role}' was deleted",
            [
                'username' => $user->username,
                'role' => $user->role,
            ]
        );
    }
}
