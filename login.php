<?php
// CS3332 AllStars Team Task & Project Management System
// User Login Page - CS3-11A: Clean, Professional Implementation
// Properly organized with external CSS/JS

require_once 'includes/session-manager.php';
require_once 'includes/csrf-protection.php';
require_once 'src/config/database.php';
require_once 'src/models/User.php';

startSecureSession();

$user = new User($pdo);
$error = '';
$success = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $result = $user->login($username, $password);
        
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['user_id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['name'] = $result['user']['name'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Team Task & Project Management</title>
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="assets/js/auth.js" as="script">
</head>
<body>
    <div class="container">
        <h1>Sign In</h1>
        
        <?php if (isLoggedIn()): ?>
            <!-- Logged In User Info -->
            <div class="user-info">
                <h3>Welcome back!</h3>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <!-- Login Form -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm" novalidate autocomplete="on">
                <?php echo csrfTokenInput(); ?>
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                            required
                            autocomplete="username"
                            aria-describedby="username-help"
                            placeholder="Enter your username or email"
                        >
                        <div class="validation-indicator" id="username-indicator"></div>
                    </div>
                    <small id="username-help" class="form-text">Enter your username or email address</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                            aria-describedby="password-help"
                            placeholder="Enter your password"
                        >
                        <div class="validation-indicator" id="password-indicator"></div>
                    </div>
                    <small id="password-help" class="form-text">Enter your password</small>
                </div>
                
                <button type="submit" class="btn btn-primary" data-original-text="Sign In">
                    Sign In
                </button>
            </form>
        <?php endif; ?>
        
        <div class="links">
            <a href="register.php">Don't have an account? Create one</a><br>
            <a href="index.php">← Back to Home</a>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="assets/js/auth.js"></script>
</body>
</html>
