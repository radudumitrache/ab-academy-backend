# Real-time Broadcasting (Pusher / Laravel Echo)

The chat system broadcasts messages in real-time using Laravel Echo and Pusher. Both admin and student clients must authenticate with the private channel before receiving events.

---

## How It Works

1. Client connects to Pusher and subscribes to `private-chat.{chatId}`
2. Pusher calls the backend `/broadcasting/auth` endpoint to verify the user is allowed on that channel
3. Backend checks the user is either the `admin_id` or `student_id` on that chat
4. If authorized, Pusher delivers `message.sent` events to the client in real-time

---

## Channel Authorization

- **URL**: `POST /broadcasting/auth`
- **Auth Required**: Yes (`Authorization: Bearer {token}`)
- **Called by**: Pusher/Laravel Echo automatically (not called manually)

The authorization is handled by Laravel's broadcaster. Your Echo client must pass the Bearer token in the auth headers:

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.PUSHER_APP_KEY,
    cluster: process.env.PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: 'https://backend.andreeaberkhout.com/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${yourAccessToken}`,
        },
    },
})
```

---

## Subscribing to a Chat Channel

```js
window.Echo.private(`chat.${chatId}`)
    .listen('.message.sent', (e) => {
        console.log('New message:', e.message)
        console.log('Chat ID:', e.chat_id)
    })
```

---

## Event Payload (`message.sent`)

```json
{
  "message": {
    "id": 7,
    "content": "Hello!",
    "sender_id": 1,
    "sender_type": "App\\Models\\Admin",
    "created_at": "2026-03-10T10:00:00.000000Z",
    "sender": {
      "id": 1,
      "username": "admin_user"
    }
  },
  "chat_id": 3
}
```

> The `.message.sent` listener name uses a leading dot because `broadcastAs()` returns `"message.sent"` — Laravel Echo requires the dot prefix to skip the default namespace.

---

## Authorization Rules

| Channel | Who is authorized |
|---------|------------------|
| `private-chat.{chatId}` | The `student_id` or `admin_id` on that chat |

Any other user attempting to subscribe will receive a `403 Forbidden` from `/broadcasting/auth`.
