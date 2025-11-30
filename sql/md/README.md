# RentConnect - Standard Authentication Setup

## 🎯 Overview
Your RentConnect application now uses standard PHP/MySQL authentication instead of Firebase. All authentication is handled server-side with secure password hashing.

## 📁 Updated File Structure
```
RENTCONNECT(2)/
├── assets/
│   ├── images/
│   ├── scripts/
│   │   └── index.js          ✅ UPDATED - No Firebase
│   └── styles/
│       └── index.css
├── functions/
│   ├── check_user.php        ✅ UPDATED - Handles login
│   ├── config.php            ✅ UPDATED - Database config
│   ├── get_user.php          ⚠️  Keep as is (for compatibility)
│   ├── save_user.php         ✅ UPDATED - Handles registration
│   └── session_check.php     ✨ NEW - Session helpers
├── database_codes/
│   └── update_schema.sql     ✨ NEW - Update database
├── index.php                 ✅ UPDATED - Changed from .html
└── logout.php                ✨ NEW - Logout handler
```

## 🚀 Setup Instructions

### Step 1: Update Database Schema
Run this SQL to add password support to your existing database:

```sql
-- Run this in phpMyAdmin or MySQL command line
USE rentconnect;

-- Add new columns for standard auth
ALTER TABLE users
ADD COLUMN IF NOT EXISTS uid VARCHAR(255) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL AFTER role,
ADD COLUMN IF NOT EXISTS auth_provider ENUM('email', 'google') DEFAULT 'email' AFTER password,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add indexes
ALTER TABLE users
ADD INDEX IF NOT EXISTS idx_uid (uid),
ADD INDEX IF NOT EXISTS idx_auth_provider (auth_provider);

-- Update existing records to have UID
UPDATE users 
SET uid = CONCAT('user_', id, '_', UNIX_TIMESTAMP()) 
WHERE uid IS NULL OR uid = '';

-- Make uid NOT NULL
ALTER TABLE users
MODIFY COLUMN uid VARCHAR(255) NOT NULL;
```

### Step 2: Update Database Configuration
Edit `functions/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // ⚠️ Change this
define('DB_PASS', '');               // ⚠️ Change this
define('DB_NAME', 'rentconnect');    // ⚠️ Verify this
```

### Step 3: Update Your Files
Replace these files with the updated versions:

1. ✅ `index.php` (renamed from index.html)
2. ✅ `assets/scripts/index.js`
3. ✅ `functions/save_user.php`
4. ✅ `functions/check_user.php`
5. ✅ `functions/config.php`
6. ✨ `functions/session_check.php` (new file)
7. ✨ `logout.php` (new file)

### Step 4: Test the System

1. **Start your server** (XAMPP, WAMP, etc.)
2. **Navigate to** `http://localhost/rentconnect/index.php`
3. **Test Registration:**
   - Click "Sign Up"
   - Fill in the form
   - Should create account and redirect to dashboard
4. **Test Login:**
   - Click "Log In" 
   - Choose Tenant or Landlord
   - Enter credentials
   - Should login and redirect to dashboard

## 🔐 Security Features

✅ **Password Hashing** - Using PHP's `password_hash()` with bcrypt
✅ **SQL Injection Prevention** - All queries use prepared statements
✅ **Session Management** - Secure session handling
✅ **Role-Based Access** - Enforces tenant/landlord separation
✅ **Input Validation** - Both client and server-side
✅ **XSS Prevention** - Sanitized outputs

## 📝 How It Works

### Registration Flow:
1. User fills signup form
2. JavaScript validates input
3. Data sent to `functions/save_user.php`
4. PHP validates data again
5. Password is hashed using `password_hash()`
6. User saved to database
7. Session created
8. User redirected to dashboard

### Login Flow:
1. User clicks Tenant or Landlord card
2. Fills login form
3. Data sent to `functions/check_user.php` with action='login'
4. PHP verifies email exists
5. Password verified using `password_verify()`
6. Role checked against requested role
7. Session created
8. User redirected to appropriate dashboard

## 🛠️ Using Session Helpers

In your dashboard files (tenant-dashboard.php, landlord-dashboard.php):

```php
<?php
// At the top of your dashboard files
require_once 'functions/session_check.php';

// Require user to be logged in
requireLogin();

// Or require specific role
requireRole('tenant'); // or 'landlord'

// Get current user data
$user = getCurrentUser();
echo "Welcome, " . $user['name'];
?>
```

## 🔄 Migration Notes

### If you have existing Firebase users:
You'll need to migrate them. Options:
1. **Have them re-register** (simplest)
2. **Manual migration** - Export Firebase users and import to MySQL
3. **Hybrid approach** - Keep Firebase for existing users, new users use standard auth

### Backward Compatibility:
The updated `check_user.php` still supports checking if a user exists by UID (for any remaining Firebase code), but login now uses email/password.

## 🐛 Troubleshooting

### "Database connection failed"
- Check `functions/config.php` credentials
- Ensure MySQL is running
- Verify database name exists

### "Headers already sent"
- Check for any output before `session_start()`
- Remove any BOM characters from PHP files
- Ensure no whitespace before `<?php`

### "Email already exists"
- User already registered
- Check database for duplicate entries

### Login redirect not working
- Check if `tenant-dashboard.php` and `landlord-dashboard.php` exist
- Verify file permissions
- Check Apache error logs

## 📊 Database Schema

Your `users` table should now have:
- `id` - Auto increment primary key
- `uid` - Unique identifier (for compatibility)
- `first_name` - User's first name
- `last_name` - User's last name
- `email` - Unique email address
- `contact` - Phone number
- `role` - 'tenant' or 'landlord'
- `password` - Hashed password (for email auth)
- `auth_provider` - 'email' or 'google' (for future)
- `created_at` - Registration timestamp
- `last_login` - Last login timestamp

## 🎉 What's Changed

### ❌ REMOVED:
- All Firebase SDK imports
- Google Sign-In buttons
- Firebase authentication functions
- `window.firebase` references

### ✅ ADDED:
- Standard password authentication
- Server-side password hashing
- Session-based authentication
- Role verification
- Secure logout functionality

## 📞 Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check Apache/PHP error logs
3. Verify database structure matches schema
4. Test database connection directly
5. Check file permissions (should be readable by web server)

## 🚨 Important Security Notes

1. **Never commit `config.php` with real credentials** to version control
2. **Use HTTPS in production** for secure password transmission
3. **Set secure session settings** in production
4. **Regularly update PHP** to latest secure version
5. **Implement rate limiting** for login attempts
6. **Add CAPTCHA** to prevent automated attacks

---

**Your authentication system is now completely independent of Firebase!** 🎊