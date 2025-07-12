<?php
// CS3332 AllStars Team Task & Project Management System
// Logout Script - CS3-11A: Session Management

session_start();
session_destroy();
header('Location: login.php');
exit;
?>