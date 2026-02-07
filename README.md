# Laravel Hello World API

A simple Laravel backend with a single "Hello World" endpoint. Perfect for beginners!

## 📁 Project Structure

```
laravel/
├── app/
│   └── Http/
│       └── Controllers/
│           └── HelloController.php    # Your controller with the hello world logic
├── routes/
│   └── api.php                        # Where you define your API routes
├── public/
│   └── index.php                      # Entry point (Laravel handles this automatically)
├── bootstrap/
│   └── app.php                        # Bootstrap file (Laravel setup)
├── config/
│   └── app.php                        # Basic configuration
├── composer.json                      # Dependencies file
├── .env                               # Environment variables
└── README.md                          # This file!
```

## 🚀 Setup Instructions

### Step 1: Install Dependencies

Open your terminal in this directory and run:

```bash
composer install
```

This will download Laravel and all required packages.

### Step 2: Start the Server

Run this command to start the Laravel development server:

```bash
php artisan serve
```

You should see: `Server started on http://127.0.0.1:8000`

### Step 3: Test Your Endpoint

Open your browser or use a tool like Postman/Insomnia and visit:

```
http://localhost:8000/api/hello
```

You should see:

```json
{
    "message": "Hello World!"
}
```

## 📖 Understanding the Code

### 1. **HelloController.php** - The Logic

This file contains your business logic:

```php
public function index()
{
    return response()->json([
        'message' => 'Hello World!'
    ]);
}
```

- `response()->json()` - Returns a JSON response
- The `index()` method handles the request

### 2. **api.php** - The Route

This file connects URLs to controllers:

```php
Route::get('/hello', [HelloController::class, 'index']);
```

- `Route::get()` - Handles GET requests
- `/hello` - The URL path (automatically prefixed with `/api`)
- `[HelloController::class, 'index']` - Calls the `index()` method in HelloController

### 3. **.env** - Configuration

Contains environment settings like app name, debug mode, etc.

## 🎯 How It Works

1. User visits `http://localhost:8000/api/hello`
2. Laravel looks in `routes/api.php` for a matching route
3. Finds the `/hello` route and calls `HelloController@index`
4. The controller returns a JSON response
5. User sees `{"message": "Hello World!"}`

## 🔧 Common Commands

```bash
# Start the server
php artisan serve

# Clear cache (if things act weird)
php artisan cache:clear

# List all routes
php artisan route:list
```

## ✨ Next Steps

Want to add more endpoints? Here's how:

1. Add a new method in `HelloController.php`:
```php
public function goodbye()
{
    return response()->json(['message' => 'Goodbye!']);
}
```

2. Add a new route in `routes/api.php`:
```php
Route::get('/goodbye', [HelloController::class, 'goodbye']);
```

3. Visit `http://localhost:8000/api/goodbye`

## 🐛 Troubleshooting

- **"composer not found"**: Install Composer from https://getcomposer.org/
- **"php not found"**: Install PHP from https://www.php.net/downloads
- **Port 8000 already in use**: Use `php artisan serve --port=8001`

Happy coding! 🎉
