<?php
// CS3332 AllStars Team Task & Project Management System
// Session Management & Security Framework - CS3-11B

function startSecureSession() {
    // Configure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.cookie_lifetime', 0); // Session cookie (expires when browser closes)
    
    session_start();
    
    // Only regenerate session ID occasionally to prevent conflicts
    // Check if we need to regenerate (every 5 minutes or if not set)
    if (!isset($_SESSION['last_regenerate']) || (time() - $_SESSION['last_regenerate'] > 300)) {
        session_regenerate_id(true);
        $_SESSION['last_regenerate'] = time();
    }
    
    // Track session activity
    $_SESSION['last_active'] = time();
    
    // Initialize CSRF token if not set
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function checkSessionTimeout($timeout = 900) { // 15 minutes default
    if (isset($_SESSION['last_active'])) {
        $timeSinceActive = time() - $_SESSION['last_active'];
        
        if ($timeSinceActive > $timeout) {
            destroySession();
            return false;
        }
    }
    $_SESSION['last_active'] = time();
    return true;
}

function destroySession() {
    session_unset();
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && checkSessionTimeout();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getSessionInfo() {
    if (!isset($_SESSION['last_active'])) {
        return ['status' => 'no_session', 'message' => 'No session data'];
    }
    
    $timeSinceActive = time() - $_SESSION['last_active'];
    $timeRemaining = 900 - $timeSinceActive; // 15 minutes - time since active
    
    return [
        'status' => 'active',
        'last_active' => $_SESSION['last_active'],
        'time_since_active' => $timeSinceActive,
        'time_remaining' => $timeRemaining,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'not_set'
    ];
}
?>