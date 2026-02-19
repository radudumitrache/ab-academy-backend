# Fixing Laravel Passport Authentication Issues

This document provides instructions for fixing the OAuth authentication error: "The resource owner or authorization server denied the request."

## The Problem

This error occurs when Laravel Passport cannot properly validate access tokens. This can happen due to several reasons:

1. Missing or corrupted Passport encryption keys
2. Issues with OAuth clients in the database
3. Token validation problems
4. Database migration issues

## Solution

I've created a command to fix Passport installation issues:

```bash
php artisan passport:fix-installation
```

This command will:

1. Check if OAuth tables exist in the database
2. Remove existing Passport keys
3. Generate new Passport keys
4. Clear existing OAuth clients
5. Create new personal access and password grant clients
6. Clear all existing tokens

**Note:** After running this command, all users will need to log in again as their existing tokens will be invalidated.

## Deployment Steps

1. Push these changes to your repository:
   ```bash
   git add .
   git commit -m "Add command to fix Passport authentication issues"
   git push
   ```

2. SSH into your server or use cPanel Terminal

3. Navigate to your Laravel project directory:
   ```bash
   cd /home/andreeaberkhout/backend.andreeaberkhout.com
   ```

4. Run the fix command:
   ```bash
   php artisan passport:fix-installation
   ```

5. Verify that the command completed successfully

## Common Causes of This Error

1. **Missing Passport Keys**: The encryption keys used by Passport are missing or corrupted.
2. **Database Issues**: The OAuth tables in the database are missing or corrupted.
3. **Token Expiration**: The access token has expired or been revoked.
4. **Configuration Mismatch**: The Passport configuration doesn't match the database state.

## Preventative Measures

To prevent this issue in the future:

1. Include Passport key generation in your deployment process
2. Back up your Passport keys
3. Set appropriate token lifetimes in your Passport configuration
4. Ensure your database migrations are properly maintained

## Troubleshooting

If the issue persists after running the fix command:

1. Check your `.env` file for any Passport-related settings
2. Verify that the database user has proper permissions
3. Check the storage directory permissions
4. Review the Laravel logs for additional error details
