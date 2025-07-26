# Security Documentation - CS3-73 User Authentication & Security Framework

## Overview

The TTPM system implements a comprehensive security framework providing secure user authentication, session management, CSRF protection, input validation, and role-based access control. This document outlines the security architecture, setup procedures, and testing guidelines.

## 🔐 Security Components

### 1. Authentication System
- **User Registration**: Secure account creation with validation
- **Login/Logout**: Session-based authentication
- **Password Security**: Bcrypt hashing with PHP `password_hash()`
- **Session Management**: Timeout-based security with activity tracking

### 2. Access Control
- **Route Guards**: Middleware protection for all pages
- **Role-Based Access Control (RBAC)**: Project-level permissions
- **API Security**: Session validation for all endpoints

### 3. Security Protection
- **CSRF Protection**: Token-based form validation
- **Input Validation**: Server-side sanitization and filtering
- **Output Encoding**: XSS prevention with `htmlspecialchars()`
- **SQL Injection Prevention**: Prepared statements throughout

## 🏗️ Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Web Pages     │    │   API Endpoints │    │   Database      │
│                 │    │                 │    │                 │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │session-check│ │    │ │api-session- │ │    │ │   users     │ │
│ │   .php      │ │    │ │  check.php  │ │    │ │   table     │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ └─────────────┘ │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
         ┌─────────────────────────────────────────────────┐
         │            Security Layer                       │
         │  ┌─────────────┐  ┌─────────────┐  ┌─────────┐ │
         │  │   Session   │  │    CSRF     │  │  RBAC   │ │
         │  │  Manager    │  │ Protection  │  │ Helpers │ │
         │  └─────────────┘  └─────────────┘  └─────────┘ │
         └─────────────────────────────────────────────────┘
```

## 🛠️ Setup Instructions

### Prerequisites
- PHP 7.4+ with mysqli extension
- MySQL 5.7+ or MariaDB
- Web server (Apache/Nginx) or PHP development server

### 1. Database Setup

The security framework requires the following database structure:

```sql
-- Users table with secure password storage
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Project membership for RBAC
CREATE TABLE project_memberships (
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, project_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 2. PHP Configuration

Ensure the following PHP settings for security:

```php
// php.ini or runtime configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 0);
```

### 3. File Structure

```
includes/
├── session-manager.php      # Core session handling
├── session-check.php        # Web page protection
├── api-session-check.php    # API endpoint protection
├── csrf-protection.php      # CSRF token system
└── rbac-helpers.php         # Role-based access control

src/models/
└── User.php                 # User authentication model
```

### 4. Integration Steps

**Step 1: Include session protection in web pages**
```php
<?php
require_once 'includes/session-check.php';
// Your page content here
?>
```

**Step 2: Include API protection in endpoints**
```php
<?php
require_once '../includes/api-session-check.php';
// Your API logic here
?>
```

**Step 3: Add CSRF tokens to forms**
```php
<form method="POST" action="your-endpoint.php">
    <?php echo csrfTokenInput(); ?>
    <!-- Your form fields -->
</form>
```

**Step 4: Validate CSRF tokens in processing**
```php
require_once 'includes/csrf-protection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    // Process form data
}
```

## 🔑 Authentication Flow

### Registration Process
1. User submits registration form
2. Server validates input (email format, username length, password strength)
3. Password is hashed with `password_hash($password, PASSWORD_DEFAULT)`
4. User record created in database
5. User redirected to login page

### Login Process
1. User submits credentials
2. Server looks up user by username/email
3. Password verified with `password_verify($password, $stored_hash)`
4. On success: session created with user data
5. On failure: error message displayed

### Session Management
1. `startSecureSession()` called on each page load
2. Session timeout checked (15 minutes default)
3. Activity timestamp updated for valid sessions
4. Expired sessions automatically destroyed

## 🛡️ Security Features

### 1. Session Security
- **Timeout**: 15 minutes of inactivity
- **Regeneration**: Session ID regenerated every 5 minutes
- **HTTPOnly Cookies**: Prevents JavaScript access
- **Secure Configuration**: Production-ready settings

### 2. CSRF Protection
- **Token Generation**: Cryptographically secure random tokens
- **Validation**: Hash-based comparison prevents timing attacks
- **Integration**: Required for all state-changing operations

### 3. Input Validation
- **Email Validation**: `filter_var($email, FILTER_VALIDATE_EMAIL)`
- **Length Checks**: Username and title length constraints
- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Prevention**: All output escaped with `htmlspecialchars()`

### 4. Password Security
- **Hashing Algorithm**: Bcrypt via `PASSWORD_DEFAULT`
- **Automatic Salt**: PHP handles salt generation
- **Timing-Safe Verification**: Uses `password_verify()`
- **No Plain Text Storage**: Hashes never reversed

### 5. Role-Based Access Control
- **Project-Level Permissions**: Admin vs Member roles
- **Action-Based Checks**: Different permissions for different operations
- **Middleware Integration**: Automatic access control on protected routes

## 🧪 Security Testing

### Manual Testing Checklist
- [ ] **Session Timeout**: Wait 16+ minutes, verify logout
- [ ] **CSRF Protection**: Submit form without token, verify rejection
- [ ] **Access Control**: Try accessing project without membership
- [ ] **XSS Prevention**: Submit `<script>alert('xss')</script>` in forms
- [ ] **SQL Injection**: Try `'; DROP TABLE users; --` in inputs
- [ ] **Password Security**: Verify hashes in database start with `$2y$`

### Automated Testing

```bash
# Run security-focused unit tests
composer test -- --filter Security

# Test password hashing
php tests/Unit/UserTest.php

# Test session management
php tests/Unit/SessionTest.php
```

### Browser Testing

```bash
# Test CSRF protection
curl -X POST http://localhost/api/tasks.php \
     -d '{"action":"create","title":"test"}' \
     -H "Content-Type: application/json"
# Expected: 401 Unauthorized

# Test session timeout
curl -b "PHPSESSID=expired_session_id" \
     http://localhost/dashboard.php
# Expected: Redirect to login.php
```

## 📊 Security Monitoring

### Log Analysis
Monitor these events for security issues:
- Failed login attempts
- CSRF token validation failures
- Session timeout events
- Unauthorized access attempts

### Session Information
Use the `getSessionInfo()` function for debugging:

```php
$info = getSessionInfo();
print_r($info);
// Output: status, last_active, time_remaining, session_id, user_id
```

## 🔧 Configuration Options

### Session Timeout
```php
// Default: 900 seconds (15 minutes)
// Adjust in session-manager.php line 18
if ($timeSinceActive > 900) { // Change this value
```

### CSRF Token Length
```php
// Default: 32 bytes (64 hex characters)
// Adjust in csrf-protection.php line 7
$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Change byte count
```

### Password Requirements
```php
// Add to User.php registration validation
if (strlen($password) < 8) {
    return ['success' => false, 'message' => 'Password must be at least 8 characters'];
}
```

## 🚨 Security Best Practices

### Production Deployment
1. **Enable HTTPS**: Set `session.cookie_secure = 1`
2. **Secure Headers**: Add security headers to web server config
3. **Database Security**: Use dedicated database user with minimal privileges
4. **Regular Updates**: Keep PHP and dependencies updated
5. **Monitoring**: Implement security event logging

### Code Security
1. **Never trust user input**: Always validate and sanitize
2. **Use prepared statements**: Never concatenate SQL queries
3. **Escape output**: Always use `htmlspecialchars()` for display
4. **Validate CSRF tokens**: On all state-changing operations
5. **Check permissions**: Verify user authorization before actions

### Common Vulnerabilities Prevented
- ✅ **SQL Injection**: Prepared statements throughout
- ✅ **XSS (Cross-Site Scripting)**: Output encoding with `htmlspecialchars()`
- ✅ **CSRF (Cross-Site Request Forgery)**: Token validation system
- ✅ **Session Hijacking**: Secure session configuration
- ✅ **Brute Force**: Account lockout can be added
- ✅ **Password Attacks**: Strong bcrypt hashing

## 🔍 Troubleshooting

### Common Issues

**Session Not Expiring**
- Check if `session.gc_maxlifetime` is properly set
- Verify `startSecureSession()` is called on every page
- Ensure server time is correct

**CSRF Token Validation Failing**
- Verify form includes `csrfTokenInput()`
- Check that session is properly started
- Ensure POST data includes `csrf_token`

**Access Denied Errors**
- Verify user has proper project membership
- Check role permissions in `rbac-helpers.php`
- Ensure session contains correct user data

**Password Login Failing**
- Verify password was hashed with `password_hash()`
- Check database stores full hash (not truncated)
- Ensure login uses `password_verify()`

### Debug Tools

```php
// Session debugging
var_dump($_SESSION);

// CSRF token debugging  
echo "Token: " . ($_SESSION['csrf_token'] ?? 'none');

// User role debugging
echo "Role: " . getUserRole($userId, $projectId);
```

## 📚 Security References

- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Session Security](https://www.php.net/manual/en/book.session.php)
- [Password Hashing](https://www.php.net/manual/en/book.password.php)

## ✅ Compliance Checklist

### CS3-73 Acceptance Criteria
- ✅ Registration/login/logout fully functional
- ✅ Session expiration + CSRF token checks work
- ✅ Roles enforced on all protected actions  
- ✅ Middleware restricts unauthorized access
- ✅ All inputs validated and output encoded
- ✅ Passwords hashed and verified properly
- ✅ Setup documented in `/docs/security.md` ← **This document**

### Security Standards Met
- ✅ **Authentication**: Multi-factor ready foundation
- ✅ **Authorization**: Role-based access control
- ✅ **Data Protection**: Encryption and hashing
- ✅ **Session Management**: Secure timeout and regeneration
- ✅ **Input Validation**: Comprehensive sanitization
- ✅ **Error Handling**: Secure error messages
- ✅ **Logging**: Security event tracking ready

---

**Document Version**: 1.0  
**Last Updated**: 2025-07-26  
**Reviewed By**: Development Team  
**Next Review**: 2025-10-26