<?php
// CS3332 AllStars Team Task & Project Management System
// BACKUP - Original development status page
// Moved from index.php on <?php echo date('Y-m-d H:i:s'); ?>

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BACKUP - Original Index (Development Status)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .feature-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 20px; margin: 20px 0; }
        .feature-title { font-weight: bold; color: #495057; margin-bottom: 10px; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #45a049; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .user-info { background: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
        .status-list { list-style: none; padding: 0; }
        .status-list li { padding: 5px 0; }
        .status-list .completed { color: #4caf50; }
        .status-list .completed:before { content: "✓ "; }
        .warning { background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px 0; border-radius: 4px; color: #c62828; }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning">
            <strong>⚠️ ARCHIVED DEVELOPMENT PAGE</strong><br>
            This was the original index.php file that mixed development status with the landing page.
            It has been moved to maintain a clean separation between user interface and development tools.
            <br><br>
            <a href="index.php">← Go to proper landing page</a> | 
            <a href="dev-status.php?dev=status">View current development status</a>
        </div>
        
        <h1>Team Task & Project Management System</h1>
        <p class="subtitle">CS3332 AllStars - CS3-11A: Registration & Password Security Implementation</p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <h3>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        <?php else: ?>
            <div class="feature-box">
                <div class="feature-title">Authentication System</div>
                <p>Secure user registration and login system with advanced password strength validation.</p>
                <a href="register.php" class="btn">Create Account</a>
                <a href="login.php" class="btn btn-secondary">Sign In</a>
            </div>
        <?php endif; ?>
        
        <div class="feature-box">
            <div class="feature-title">CS3-11A Implementation Status</div>
            <ul class="status-list">
                <li class="completed">User signup form with password strength feedback</li>
                <li class="completed">Email + username validation via AJAX</li>
                <li class="completed">Secure password hashing with password_hash()</li>
                <li class="completed">Duplicate check using SQL prepared statements</li>
                <li class="completed">Login verification with password_verify()</li>
                <li class="completed">Integration with existing database schema</li>
                <li class="completed">Real-time availability checking</li>
            </ul>
        </div>
        
        <div class="feature-box">
            <div class="feature-title">Security Features Implemented</div>
            <ul>
                <li><strong>Password Requirements:</strong> Minimum 8 characters, uppercase, lowercase, number, symbol</li>
                <li><strong>Real-time Validation:</strong> Username/email availability checking</li>
                <li><strong>Secure Hashing:</strong> PHP password_hash() with PASSWORD_DEFAULT</li>
                <li><strong>SQL Injection Protection:</strong> Prepared statements throughout</li>
                <li><strong>Input Sanitization:</strong> HTML escaping and validation</li>
                <li><strong>Duplicate Prevention:</strong> Database-level uniqueness enforcement</li>
            </ul>
        </div>
        
        <div class="feature-box">
            <div class="feature-title">Database Integration</div>
            <p>
                The registration system integrates seamlessly with the existing database schema 
                implemented in CS3-10. Users can be assigned to projects, create tasks, and 
                participate in the full team management workflow.
            </p>
            <p>
                <strong>Database:</strong> ttpm_system<br>
                <strong>Table:</strong> users (user_id, username, email, password_hash, name, created_at)<br>
                <strong>Relations:</strong> Compatible with project_membership, tasks, comments, notifications
            </p>
        </div>
    </div>
</body>
</html>
