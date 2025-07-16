<?php
// CS3332 AllStars Team Task & Project Management System
// Logout Script - CS3-11B: Secure Session Destruction

require_once 'includes/session-manager.php';

startSecureSession();
destroySession();
header('Location: login.php?logout=1');
exit;
?>