# Pusher Configuration for AB Academy Chat

To enable real-time chat functionality, add these lines to your `.env` file with your Pusher credentials:

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_app_cluster
```

Make sure to replace the placeholder values with your actual Pusher credentials.

## Next Steps After Configuration

1. Run `composer require pusher/pusher-php-server`
2. Run `npm install --save laravel-echo pusher-js` (if using Laravel Echo on the frontend)
3. Run `php artisan migrate` to update the database tables
4. Uncomment the `App\Providers\BroadcastServiceProvider::class` line in `config/app.php`
