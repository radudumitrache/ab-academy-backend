<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private chat channel: private-chat.{chatId}
 *
 * Authorized if the authenticated user is either:
 *   - the student_id on the chat, or
 *   - the admin_id on the chat
 */
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::find($chatId);

    if (!$chat) {
        return false;
    }

    return (int) $user->id === (int) $chat->student_id
        || (int) $user->id === (int) $chat->admin_id;
});
