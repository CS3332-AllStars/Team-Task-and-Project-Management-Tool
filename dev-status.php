<?php
// CS3332 AllStars Team Task & Project Management System
// DEVELOPMENT STATUS PAGE - Internal Use Only
// This page should NOT be accessible in production

// Basic protection (replace with proper dev-only access control)
if (!isset($_GET['dev']) || $_GET['dev'] !== 'status') {
    header('Location: index.php');
    exit;
}

session_start();
require_once 'src/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Development Status - CS3332 AllStars</title>
    <style>
        body {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
            line-height: 1.6;
        }
        .header {
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
        }
        .header p {
            color: #cccccc;
            margin: 5px 0 0;
        }
        .section {
            background: #2d2d30;
            border: 1px solid #3e3e42;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .section h2 {
            color: #569cd6;
            margin-top: 0;
            border-bottom: 1px solid #3e3e42;
            padding-bottom: 10px;
        }
        .status-list {
            list-style: none;
            padding: 0;
        }
        .status-list li {
            padding: 8px 0;
            border-bottom: 1px solid #3e3e42;
        }
        .status-list li:last-child {
            border-bottom: none;
        }
        .completed {
            color: #4CAF50;
        }
        .completed:before {
            content: "✓ ";
            font-weight: bold;
        }
        .in-progress {
            color: #ffa500;
        }
        .in-progress:before {
            content: "⚠ ";
            font-weight: bold;
        }
        .todo {
            color: #f44336;
        }
        .todo:before {
            content: "✗ ";
            font-weight: bold;
        }
        .code-block {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            overflow-x: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
        }
        .database-info {
            background: #2d2d30;
            border-left: 4px solid #569cd6;
            padding: 15px;
            margin: 15px 0;
        }
        .warning {
            background: #3d2914;
            border-left: 4px solid #ffa500;
            color: #ffa500;
            padding: 15px;
            margin: 15px 0;
        }
        .back-link {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CS3332 AllStars - Development Status</h1>
        <p>Internal Development Dashboard - <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><a href="index.php" class="back-link">← Back to Main Site</a></p>
    </div>
    
    <div class="warning">
        <strong>⚠️ DEVELOPMENT PAGE</strong><br>
        This page contains sensitive implementation details and should NOT be accessible in production.
        Access URL: <code>?dev=status</code>
    </div>
    
    <div class="section">
        <h2>CS3-11A Implementation Status</h2>
        <ul class="status-list">
            <li class="completed">User signup form with password strength feedback</li>
            <li class="completed">Email + username validation via AJAX</li>
            <li class="completed">Secure password hashing with password_hash()</li>
            <li class="completed">Duplicate check using SQL prepared statements</li>
            <li class="completed">Login verification with password_verify()</li>
            <li class="completed">Integration with existing database schema</li>
            <li class="completed">Professional CSS/JS architecture</li>
            <li class="completed">Form validation and UX improvements</li>
            <li class="in-progress">API endpoints for AJAX validation</li>
            <li class="todo">Session management and CSRF protection</li>
            <li class="todo">Role-based access control implementation</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Database Connection Status</h2>
        <div class="database-info">
            <?php
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
                $result = $stmt->fetch();
                echo "<strong>✓ Database Connected</strong><br>";
                echo "Database: ttpm_system<br>";
                echo "Users registered: " . $result['user_count'] . "<br>";
                echo "Connection: MySQL via PDO<br>";
                echo "Character set: UTF8MB4";
            } catch (Exception $e) {
                echo "<span style='color: #f44336;'><strong>✗ Database Error</strong><br>";
                echo "Error: " . htmlspecialchars($e->getMessage()) . "</span>";
            }
            ?>
        </div>
    </div>
    
    <div class="section">
        <h2>Security Features Implemented</h2>
        <ul class="status-list">
            <li class="completed"><strong>Password Requirements:</strong> Min 8 chars, uppercase, lowercase, number, symbol</li>
            <li class="completed"><strong>Secure Hashing:</strong> PHP password_hash() with PASSWORD_DEFAULT</li>
            <li class="completed"><strong>SQL Injection Protection:</strong> Prepared statements throughout</li>
            <li class="completed"><strong>Input Sanitization:</strong> HTML escaping and validation</li>
            <li class="completed"><strong>Duplicate Prevention:</strong> Database-level uniqueness enforcement</li>
            <li class="in-progress"><strong>Session Security:</strong> Regeneration and timeout (planned)</li>
            <li class="todo"><strong>CSRF Protection:</strong> Token-based form protection (planned)</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>File Structure</h2>
        <div class="code-block">
assets/
├── css/
│   ├── main.css      ← Core design system
│   └── forms.css     ← Form validation styles
├── js/
│   └── auth.js       ← Authentication module
└── images/           ← Future assets

src/
├── config/
│   └── database.php  ← Database configuration
├── models/
│   └── User.php      ← User authentication model
├── controllers/      ← Future API endpoints
└── views/           ← Future template system

database/
├── schema.sql        ← Database structure
└── sample_data.sql   ← Test data
        </div>
    </div>
    
    <div class="section">
        <h2>Next Development Priorities</h2>
        <ul class="status-list">
            <li class="todo"><strong>Week 5:</strong> Complete CS3-73 (Authentication & Security Framework)</li>
            <li class="todo"><strong>Week 6:</strong> Begin CS3-12 (Project Management Module)</li>
            <li class="todo"><strong>Week 7:</strong> Implement CS3-13 (Task Management Module)</li>
            <li class="todo"><strong>Week 8:</strong> Add CS3-14 (Comment System) and CS3-15 (Notifications)</li>
            <li class="todo"><strong>Week 9:</strong> Integration testing and final delivery</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Testing URLs</h2>
        <div class="code-block">
Registration: <a href="register.php" style="color: #4CAF50;">register.php</a>
Login:        <a href="login.php" style="color: #4CAF50;">login.php</a>
Main Site:    <a href="index.php" style="color: #4CAF50;">index.php</a>
Logout:       <a href="logout.php" style="color: #4CAF50;">logout.php</a>

Test Users (password: password123):
- james_ward / james.ward@allstars.edu
- summer_hill / summer.hill@allstars.edu  
- juan_ledet / juan.ledet@allstars.edu
- alaric_higgins / alaric.higgins@allstars.edu
        </div>
    </div>
</body>
</html>
