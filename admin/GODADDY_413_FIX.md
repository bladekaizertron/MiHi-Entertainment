# Fix for "413 Request Entity Too Large" Error on GoDaddy

## Problem
When submitting large content (especially with TinyMCE editor), you get:
```
413 Request Entity Too Large
The requested resource does not allow request data with GET requests, or the amount of data provided in the request exceeds the capacity limit.
```

## Solutions Implemented

### ✅ Solution 1: PHP Runtime Configuration (DONE)
Both `create.php` and `edit.php` now have these lines at the top:
```php
@ini_set('post_max_size', '1024M');
@ini_set('upload_max_filesize', '1024M');
@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');
@ini_set('memory_limit', '1024M');
```

### ✅ Solution 2: .user.ini for GoDaddy (DONE)
Created `/admin/.user.ini` with:
```ini
post_max_size = 1024M
upload_max_filesize = 1024M
max_execution_time = 600
max_input_time = 600
memory_limit = 1024M
max_input_vars = 10000
```

**Note:** Changes to `.user.ini` may take 5-10 minutes to take effect on GoDaddy due to caching.

### ✅ Solution 3: .htaccess Configuration (DONE)
Created `/admin/.htaccess` with:
```apache
php_value post_max_size 1024M
php_value upload_max_filesize 1024M
php_value max_input_time 600
php_value max_execution_time 600
php_value memory_limit 1024M
LimitRequestBody 1073741824
```

## Testing on GoDaddy

### Step 1: Upload All Files
Make sure these files are uploaded to your GoDaddy server:
- `/admin/.htaccess`
- `/admin/.user.ini`
- `/admin/create.php` (updated)
- `/admin/edit.php` (updated)

### Step 2: Wait for Cache
Wait 5-10 minutes for GoDaddy's PHP configuration cache to clear.

### Step 3: Test
Try creating or editing a post with large content.

## If Still Not Working on GoDaddy

### Option A: Contact GoDaddy Support
GoDaddy may have hard limits at the server level. Contact them and request:
1. Increase `post_max_size` to 1024M
2. Increase `upload_max_filesize` to 1024M
3. Increase `LimitRequestBody` to 1GB

### Option B: Reduce Content Size
If GoDaddy won't increase limits:
1. **Don't embed images in TinyMCE** - Instead, upload images separately and insert as URLs
2. **Use external image hosting** - Consider using Cloudinary, Imgur, or similar
3. **Compress images** - Before uploading, compress images to reduce size
4. **Split large posts** - Break very large posts into multiple smaller posts

### Option C: Use GoDaddy's php.ini (Advanced)
Some GoDaddy hosting plans allow custom `php.ini`:
1. Check if you can create a custom `php.ini` in your account
2. Add the same settings from `.user.ini`
3. Place it in the `/admin/` directory

## Verification

### Check Current PHP Limits
Create a file called `check_limits.php` in `/admin/`:
```php
<?php
phpinfo();
```

Visit it in your browser and search for:
- `post_max_size`
- `upload_max_filesize`
- `max_execution_time`
- `memory_limit`

Delete this file after checking for security.

## Additional Notes

### Why This Happens
The error occurs because:
1. **Apache level**: Request body exceeds `LimitRequestBody`
2. **PHP level**: POST data exceeds `post_max_size`
3. **TinyMCE**: Embedded images are base64 encoded, making them ~33% larger

### Best Practices
1. **Use image URLs** instead of embedding
2. **Optimize images** before uploading (compress, resize)
3. **Monitor content size** - The admin panel warns if content > 500MB
4. **Regular testing** - Test on GoDaddy after making changes

## Support
If issues persist, check:
- GoDaddy error logs (via cPanel)
- Browser console for JavaScript errors
- Network tab to see actual request size
