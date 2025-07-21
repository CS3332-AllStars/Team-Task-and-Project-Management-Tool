<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17: Frontend UI Framework - Layout Header Component

// Ensure session is started for user context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default page title and description if not provided
$pageTitle = $pageTitle ?? 'Team Task & Project Management';
$pageDescription = $pageDescription ?? 'Collaborative project management system';
$currentUser = $_SESSION['username'] ?? null;
$userRole = $_SESSION['role'] ?? 'user';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- CSRF Token for AJAX requests -->
    <?php if (isset($_SESSION['csrf_token'])): ?>
        <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation Bar -->
    <?php if ($isLoggedIn && !isset($hideNavigation)): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-kanban"></i> TTPM System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-project.php">
                            <i class="bi bi-plus-circle"></i> New Project
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($currentUser); ?>
                            <?php if ($userRole === 'admin'): ?>
                                <span class="badge bg-warning text-dark ms-1">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                            <?php if ($userRole === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-shield"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <div class="main-wrapper">
        <?php if ($isLoggedIn && !isset($hideNavigation)): ?>
            <div class="container-fluid mt-4">
        <?php else: ?>
            <div class="container">
        <?php endif; ?>