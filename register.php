<?php
// CS3332 AllStars Team Task & Project Management System
// User Registration Page - CS3-11A: Clean, Professional Implementation
// Properly organized with external CSS/JS and proper validation

session_start();
require_once 'src/config/database.php';
require_once 'src/models/User.php';

$user = new User($pdo);
$errors = [];
$success = '';

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    
    // Basic validation
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($name)) $errors[] = "Full name is required";
    
    // Email format validation
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Password confirmation
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // If no basic errors, attempt registration
    if (empty($errors)) {
        $result = $user->register($username, $email, $password, $name);
        
        if ($result['success']) {
            $success = "Registration successful! You can now login.";
            // Clear form data on success
            $username = $email = $name = '';
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Team Task & Project Management</title>
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="assets/js/auth.js" as="script">
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>
        
        <!-- Display Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please fix the following errors:</strong>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Display Success -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br><a href="login.php" class="success-link">Click here to login</a>
            </div>
        <?php endif; ?>
        
        <!-- Registration Form -->
        <form method="POST" id="registrationForm" novalidate>
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                        required
                        autocomplete="name"
                        aria-describedby="name-help"
                        placeholder="Enter your full name"
                    >
                    <div class="validation-indicator" id="name-indicator"></div>
                </div>
                <small id="name-help" class="form-text">Your display name for the team</small>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                        required
                        autocomplete="username"
                        aria-describedby="username-help"
                        placeholder="Choose a unique username"
                        minlength="3"
                        maxlength="50"
                    >
                    <div class="validation-indicator" id="username-indicator"></div>
                </div>
                <small id="username-help" class="form-text">3-50 characters, used for login</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                        required
                        autocomplete="email"
                        aria-describedby="email-help"
                        placeholder="your.email@example.com"
                    >
                    <div class="validation-indicator" id="email-indicator"></div>
                </div>
                <small id="email-help" class="form-text">Used for login and notifications</small>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="new-password"
                        aria-describedby="password-help"
                        placeholder="Create a strong password"
                        minlength="8"
                    >
                    <div class="validation-indicator" id="password-indicator"></div>
                </div>
                <small id="password-help" class="form-text">Minimum 8 characters with uppercase, lowercase, number, and symbol</small>
                <!-- Password strength indicator will be inserted here by JavaScript -->
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        autocomplete="new-password"
                        aria-describedby="confirm-password-help"
                        placeholder="Confirm your password"
                    >
                    <div class="validation-indicator" id="confirm-password-indicator"></div>
                </div>
                <small id="confirm-password-help" class="form-text">Must match the password above</small>
            </div>
            
            <button type="submit" class="btn btn-primary" data-original-text="Create Account">
                Create Account
            </button>
        </form>
        
        <div class="links">
            <a href="login.php">Already have an account? Sign in</a><br>
            <a href="index.php">← Back to Home</a>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="assets/js/auth.js"></script>
</body>
</html>
