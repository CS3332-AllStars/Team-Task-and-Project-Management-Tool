<?php
// CS3332 AllStars Team Task & Project Management System
// Session Management & Security Framework - CS3-11B

function startSecureSession() {
    // Configure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    
    session_start();
    session_regenerate_id(true);
    
    // Track session activity
    $_SESSION['last_active'] = time();
    
    // Initialize CSRF token if not set
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function checkSessionTimeout($timeout = 900) { // 15 minutes default
    if (isset($_SESSION['last_active']) && 
        (time() - $_SESSION['last_active'] > $timeout)) {
        destroySession();
        return false;
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
?>