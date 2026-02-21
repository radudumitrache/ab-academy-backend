<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Admin ID for Student-Admin Chats
    |--------------------------------------------------------------------------
    |
    | This value is used when a student creates a chat with an admin.
    | If not specified, the system will use the first admin found in the database.
    |
    */
    'default_admin_id' => env('DEFAULT_ADMIN_ID', null),
];
