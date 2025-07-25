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
    
    <!-- Role-Based UI Meta Tags -->
    <?php if ($isLoggedIn): ?>
        <meta name="user-id" content="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
        <meta name="user-role" content="<?php echo htmlspecialchars($userRole); ?>">
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
                    <li class="nav-item member-only">
                        <a class="nav-link" href="create-project.php">
                            <i class="bi bi-plus-circle"></i> New Project
                        </a>
                    </li>
                    <li class="nav-item admin-only" data-role-show="admin">
                        <a class="nav-link" href="#admin-panel">
                            <i class="bi bi-shield-check"></i> Admin Panel
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Notification Bell - CS3-15D -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                                0
                            </span>
                        </a>
                        <!-- Notification Dropdown -->
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown" style="width: 350px;" id="notifList">
                            <li class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                <button class="btn btn-sm btn-link text-decoration-none p-0" id="markAllRead">Mark all as read</button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notifItems" style="max-height: 300px; overflow-y: auto;">
                                <li class="dropdown-item-text text-muted text-center py-3">
                                    <i class="bi bi-bell-slash"></i> No notifications
                                </li>
                            </div>
                        </ul>
                    </li>
                    
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
                            <li><a class="dropdown-item member-only" href="#"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item member-only" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                            <li class="admin-only" data-role-show="admin"><hr class="dropdown-divider"></li>
                            <li class="admin-only" data-role-show="admin"><a class="dropdown-item" href="#"><i class="bi bi-shield"></i> Admin Panel</a></li>
                            <li class="admin-only" data-role-show="admin"><a class="dropdown-item" href="#"><i class="bi bi-tools"></i> Site Management</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Role-Based JavaScript -->
    <?php if ($isLoggedIn): ?>
        <script src="assets/js/auth.js"></script>
        <script src="assets/js/notifications.js"></script>
        <script>
        // Initialize role-based UI when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initRoleBasedUI) {
                window.initRoleBasedUI('<?php echo $userRole; ?>', <?php echo $_SESSION['user_id']; ?>);
            }
        });
        </script>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <div class="main-wrapper">
        <?php if ($isLoggedIn && !isset($hideNavigation)): ?>
            <div class="container-fluid mt-4">
        <?php else: ?>
            <div class="container">
        <?php endif; ?>