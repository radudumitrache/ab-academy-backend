# Database Setup Guide

This guide will help you set up your database connection for Laravel.

## 📋 Database Options

Laravel supports multiple databases:
- **MySQL** (Most common for cPanel)
- **PostgreSQL**
- **SQLite** (Simple, file-based)
- **SQL Server**

## 🔧 Setup Instructions

### **Option 1: MySQL (Recommended for cPanel)**

#### **Step 1: Create Database in cPanel**

1. Login to cPanel
2. Go to **MySQL Databases**
3. Create a new database (e.g., `andreeaberkhout_laravel`)
4. Create a new user (e.g., `andreeaberkhout_user`)
5. Set a strong password
6. Add user to database with **ALL PRIVILEGES**

#### **Step 2: Update .env File**

Edit your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=andreeaberkhout_laravel
DB_USERNAME=andreeaberkhout_user
DB_PASSWORD=your_password_here
```

**Important for cPanel:**
- Database name is usually: `cpanel_username_dbname`
- Username is usually: `cpanel_username_user`

#### **Step 3: Test Connection**

```bash
php artisan migrate:status
```

If it works, you're connected!

---

### **Option 2: SQLite (Simple, No Setup)**

Perfect for local development and testing.

#### **Step 1: Create Database File**

```bash
touch database/database.sqlite
```

Or on Windows:
```bash
type nul > database/database.sqlite
```

#### **Step 2: Update .env File**

```env
DB_CONNECTION=sqlite
DB_DATABASE=/full/path/to/database/database.sqlite
```

For local development:
```env
DB_CONNECTION=sqlite
```

That's it! SQLite requires no server setup.

---

## 🧪 Testing Your Connection

### **Test 1: Check Configuration**

```bash
php artisan config:clear
php artisan config:cache
```

### **Test 2: Try to Connect**

```bash
php artisan tinker
```

Then in tinker:
```php
DB::connection()->getPdo();
```

If you see a PDO object, you're connected! Type `exit` to leave tinker.

### **Test 3: Run Migrations**

```bash
php artisan migrate
```

This will create the default Laravel tables.

---

## 🔍 Common Issues

### **Error: "Access denied for user"**

**Problem:** Wrong username or password

**Solution:** 
- Double-check credentials in cPanel
- Make sure user has privileges on the database

### **Error: "Unknown database"**

**Problem:** Database doesn't exist

**Solution:**
- Create database in cPanel MySQL Databases
- Check database name spelling in `.env`

### **Error: "SQLSTATE[HY000] [2002] Connection refused"**

**Problem:** Can't connect to MySQL server

**Solution:**
- Check `DB_HOST` (try `localhost` or `127.0.0.1`)
- Make sure MySQL is running
- On cPanel, use `localhost`

### **Error: "could not find driver"**

**Problem:** PDO MySQL extension not installed

**Solution:**
- Check PHP has `pdo_mysql` extension
- In cPanel, use "Select PHP Version" to enable it

---

## 📊 Database Configuration File

The database configuration is in `config/database.php`. It supports:

### **MySQL Configuration**
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]
```

### **SQLite Configuration**
```php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
]
```

---

## 🎯 Quick Start Commands

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear

# Test connection
php artisan tinker
> DB::connection()->getPdo();
> exit

# Run migrations
php artisan migrate

# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Fresh database (drops all tables)
php artisan migrate:fresh
```

---

## 🔐 Security Tips

1. **Never commit `.env` to Git** - It's already in `.gitignore`
2. **Use strong passwords** for database users
3. **Limit database user privileges** - Only give what's needed
4. **Different credentials per environment** - Local vs Production
5. **Keep backups** - Regular database backups

---

## 📝 Environment-Specific Settings

### **Local Development (.env)**
```env
DB_CONNECTION=sqlite
APP_DEBUG=true
```

### **Production (.env on server)**
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=production_db
DB_USERNAME=production_user
DB_PASSWORD=strong_password_here
APP_DEBUG=false
APP_ENV=production
```

---

## ✅ Checklist

- [ ] Database created in cPanel (or SQLite file created)
- [ ] User created and added to database (MySQL only)
- [ ] `.env` file updated with credentials
- [ ] Connection tested with `php artisan tinker`
- [ ] Migrations run successfully
- [ ] `.env` NOT committed to Git

---

## 🚀 Next Steps

Once your database is connected:

1. **Create migrations** - Define your database structure
2. **Create models** - Interact with database tables
3. **Seed data** - Add test data
4. **Build API endpoints** - CRUD operations

Ready to create your first migration? 🎉
