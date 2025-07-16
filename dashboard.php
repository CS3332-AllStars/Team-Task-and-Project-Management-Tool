<?php
// CS3332 AllStars Team Task & Project Management System
// Simple Dashboard for Authentication Testing

require_once 'includes/session-check.php';

// Simple mysqli connection
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get user projects
$userProjects = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("
        SELECT p.project_id, p.title, p.description, pm.role 
        FROM projects p 
        JOIN project_memberships pm ON p.project_id = pm.project_id 
        WHERE pm.user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $userProjects[] = $row;
    }
    $stmt->close();
}

// Get user tasks
$userTasks = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("
        SELECT t.task_id, t.title, t.status, p.title as project_title
        FROM tasks t 
        JOIN task_assignments ta ON t.task_id = ta.task_id 
        JOIN projects p ON t.project_id = p.project_id
        WHERE ta.user_id = ?
        LIMIT 10
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $userTasks[] = $row;
    }
    $stmt->close();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Dashboard - TTPM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h3 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .item {
            padding: 10px;
            margin: 5px 0;
            background: #f9f9f9;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .logout-link {
            float: right;
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-link:hover {
            text-decoration: underline;
        }
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            background: #6c757d;
        }
        .auth-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="logout.php" class="logout-link">Logout</a>
            <h1>Simple Dashboard</h1>
            <p>Authentication Test Interface</p>
        </div>

        <div class="auth-info">
            <h3>Authentication Status: ✅ SUCCESS</h3>
            <p><strong>Welcome:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></p>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'Not set'); ?></p>
            <p><strong>Session Started:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <div class="section">
            <h3>Your Projects (<?php echo count($userProjects); ?>)</h3>
            <?php if (empty($userProjects)): ?>
                <p>No projects found.</p>
            <?php else: ?>
                <?php foreach ($userProjects as $project): ?>
                    <div class="item">
                        <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                        <span style="float: right; color: #666;"><?php echo htmlspecialchars($project['role']); ?></span>
                        <br>
                        <small><?php echo htmlspecialchars($project['description'] ?? 'No description'); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Your Tasks (<?php echo count($userTasks); ?>)</h3>
            <?php if (empty($userTasks)): ?>
                <p>No tasks assigned.</p>
            <?php else: ?>
                <?php foreach ($userTasks as $task): ?>
                    <div class="item">
                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                        <span class="status-badge"><?php echo htmlspecialchars($task['status']); ?></span>
                        <br>
                        <small>Project: <?php echo htmlspecialchars($task['project_title']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #666;">
            <p>Simple Dashboard - Authentication Testing Complete</p>
        </div>
    </div>
</body>
</html>