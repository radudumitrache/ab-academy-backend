# Deploying Laravel to cPanel Shared Hosting

This guide will help you deploy your Laravel "Hello World" API to a cPanel shared hosting environment.

## 📋 Prerequisites

- cPanel hosting account with PHP 8.1+ support
- SSH access (optional but recommended)
- File Manager or FTP access
- Composer installed on the server (most cPanel hosts have this)

## 🚀 Deployment Steps

### Step 1: Prepare Your Project

Before uploading, make sure your project is ready:

1. **Generate a proper APP_KEY** (if you haven't already):
   ```bash
   php artisan key:generate
   ```
   This will update your `.env` file with a secure key.

2. **Optimize for production** (optional):
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   ```

### Step 2: Upload Files to cPanel

You have two main options:

#### Option A: Using File Manager (Easier)

1. **Login to cPanel**
2. **Open File Manager**
3. **Navigate to your home directory** (usually `/home/yourusername/`)
4. **Create a folder** called `laravel_app` (or any name you prefer)
5. **Upload all your project files** to this folder
   - You can zip your project first, upload the zip, then extract it

#### Option B: Using FTP

1. Use an FTP client like FileZilla
2. Connect to your cPanel FTP
3. Upload all files to a folder like `/home/yourusername/laravel_app/`

### Step 3: Configure the Public Directory

**IMPORTANT**: In cPanel shared hosting, your website files must be in the `public_html` folder, but Laravel's entry point is in the `public` folder.

Here's how to fix this:

#### Method 1: Symlink (Recommended if you have SSH)

1. **SSH into your server**
2. **Run these commands**:
   ```bash
   cd /home/yourusername/
   # Remove the default public_html if it exists
   rm -rf public_html
   # Create a symlink from public_html to your Laravel public folder
   ln -s /home/yourusername/laravel_app/public public_html
   ```

#### Method 2: Move Files (If no SSH access)

1. **Upload Laravel to** `/home/yourusername/laravel_app/`
2. **Copy contents of** `laravel_app/public/` **to** `public_html/`
3. **Edit** `public_html/index.php` and update the paths:

   Change this:
   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```

   To this:
   ```php
   require __DIR__.'/../laravel_app/vendor/autoload.php';
   $app = require_once __DIR__.'/../laravel_app/bootstrap/app.php';
   ```

### Step 4: Configure Environment Variables

1. **Navigate to your Laravel root** (e.g., `/home/yourusername/laravel_app/`)
2. **Edit the `.env` file**:
   ```env
   APP_NAME="Hello World API"
   APP_ENV=production
   APP_KEY=base64:YOUR_GENERATED_KEY_HERE
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   LOG_CHANNEL=stack
   LOG_LEVEL=error
   ```

   **Important changes for production:**
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false` (never leave debug on in production!)
   - Update `APP_URL` to your actual domain
   - Make sure `APP_KEY` is set (run `php artisan key:generate` if empty)

### Step 5: Set Correct Permissions

Using File Manager or SSH, set these permissions:

```bash
# Storage and cache folders need write permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# If using SSH, you can also run:
chown -R yourusername:yourusername /home/yourusername/laravel_app
```

Or in cPanel File Manager:
- Right-click on `storage` folder → Change Permissions → Set to `775`
- Right-click on `bootstrap/cache` folder → Change Permissions → Set to `775`

### Step 6: Install Dependencies on Server

If Composer is available on your cPanel:

1. **Open Terminal in cPanel** (if available)
2. **Navigate to your Laravel directory**:
   ```bash
   cd /home/yourusername/laravel_app
   ```
3. **Install dependencies**:
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

If Composer is NOT available:
- Upload the entire `vendor` folder from your local machine (this is why we ran `composer install` locally first)

### Step 7: Configure PHP Version

Most shared hosting uses older PHP versions by default:

1. **In cPanel, find "Select PHP Version" or "MultiPHP Manager"**
2. **Select PHP 8.1 or higher** for your domain
3. **Enable required extensions**:
   - mbstring
   - openssl
   - PDO
   - tokenizer
   - XML
   - ctype
   - JSON
   - BCMath

### Step 8: Create .htaccess File

If you used Method 2 (moved files), create/edit `.htaccess` in `public_html/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to HTTPS (optional but recommended)
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect Trailing Slashes If Not A Folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    
    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Step 9: Test Your API

Visit your endpoint:
```
https://yourdomain.com/api/hello
```

You should see:
```json
{
    "message": "Hello World!"
}
```

## 🐛 Troubleshooting

### Error: "500 Internal Server Error"

1. **Check error logs** in cPanel → Error Logs
2. **Make sure** `storage` and `bootstrap/cache` have write permissions (775)
3. **Verify** `.env` file exists and has correct `APP_KEY`
4. **Check** PHP version is 8.1 or higher

### Error: "The stream or file could not be opened"

This means Laravel can't write to the `storage` folder:
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Error: "No application encryption key has been specified"

Run this command via SSH or Terminal:
```bash
php artisan key:generate
```

Or manually add a key to `.env`:
```env
APP_KEY=base64:some_random_32_character_string_here
```

### API Returns 404

1. **Check** `.htaccess` file exists in your public folder
2. **Verify** mod_rewrite is enabled (contact hosting support)
3. **Clear route cache**:
   ```bash
   php artisan route:clear
   php artisan cache:clear
   ```

## 📁 Final Directory Structure on Server

```
/home/yourusername/
├── laravel_app/              # Your Laravel application
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── composer.json
└── public_html/              # Symlink or copy of public folder
    ├── index.php
    └── .htaccess
```

## 🔒 Security Tips

1. **Never expose your `.env` file** - it should be outside `public_html`
2. **Set `APP_DEBUG=false`** in production
3. **Use HTTPS** - most cPanel hosts offer free SSL certificates
4. **Keep Laravel updated** - run `composer update` regularly
5. **Protect your admin routes** with authentication

## 🎯 Quick Checklist

- [ ] Files uploaded to server
- [ ] `public` folder linked/copied to `public_html`
- [ ] `.env` file configured for production
- [ ] `APP_KEY` generated
- [ ] PHP version set to 8.1+
- [ ] Composer dependencies installed
- [ ] Storage permissions set to 775
- [ ] `.htaccess` file in place
- [ ] Tested the `/api/hello` endpoint

## 📞 Need Help?

If you encounter issues:
1. Check cPanel error logs
2. Contact your hosting provider's support
3. Make sure your hosting plan supports Laravel requirements

Happy deploying! 🚀
