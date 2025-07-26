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

// Auto-migration check (CS3-12F migration system)
require_once 'src/utils/AutoMigration.php';
$pending_migrations = AutoMigration::hasPendingMigrations($mysqli);

// Get user projects with comprehensive metrics for CS3-12B
$userProjects = [];
if (isset($_SESSION['user_id'])) {
    // Check if is_archived column exists (CS3-12F migration check)
    $column_check = $mysqli->query("
        SELECT COUNT(*) as column_exists
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'ttpm_system' 
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME = 'is_archived'
    ");
    $has_archived_column = $column_check->fetch_assoc()['column_exists'] > 0;

    // Build query based on whether is_archived column exists
    $where_clause = $has_archived_column 
        ? "WHERE pm.user_id = ? AND (p.is_archived IS NULL OR p.is_archived = FALSE)"
        : "WHERE pm.user_id = ?";

    $stmt = $mysqli->prepare("
        SELECT 
            p.project_id, 
            p.title, 
            p.description, 
            pm.role,
            (SELECT COUNT(*) FROM project_memberships pm2 WHERE pm2.project_id = p.project_id) as team_size,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id) as total_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status = 'Done') as completed_tasks,
            (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status IN ('To Do', 'In Progress')) as active_tasks,
            p.created_date
        FROM projects p 
        JOIN project_memberships pm ON p.project_id = pm.project_id 
        $where_clause
        ORDER BY pm.joined_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Calculate progress percentage
        $row['progress_percent'] = $row['total_tasks'] > 0 
            ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) 
            : 0;
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
    <meta name="user-id" content="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
    <meta name="user-role" content="<?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?>">
    <title>Simple Dashboard - TTPM</title>
    <link rel="stylesheet" href="assets/css/project.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>
    <div class="container dashboard">
        <?php if ($pending_migrations): ?>
            <?php AutoMigration::showMigrationBanner(); ?>
        <?php endif; ?>
        
        <div class="header center">
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
            <div class="flex-between mb-2">
                <h3 class="margin-0">Your Projects (<?php echo count($userProjects); ?>)</h3>
                <div>
                    <a href="create-project.php" class="btn-create member-only">+ Create New Project</a>
                    <a href="archived-projects.php" class="btn-create member-only archived-btn">📦 Archived Projects</a>
                    <button class="btn-create admin-only admin-button" data-role-show="admin">🔧 Admin Tools</button>
                </div>
            </div>
            
            <?php if (empty($userProjects)): ?>
                <div class="text-center tasks-placeholder">
                    <p><strong>No projects yet!</strong></p>
                    <p>Create your first project to get started with task management.</p>
                </div>
            <?php else: ?>
                <div class="project-cards">
                    <?php foreach ($userProjects as $project): ?>
                        <div class="project-card" onclick="window.location.href='project.php?id=<?php echo $project['project_id']; ?>'">
                            <div class="project-card-header">
                                <div>
                                    <a href="project.php?id=<?php echo $project['project_id']; ?>" class="project-card-title">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </a>
                                </div>
                                <span class="role-badge role-<?php echo $project['role']; ?>">
                                    <?php echo ucfirst($project['role']); ?>
                                </span>
                            </div>
                            
                            <div class="project-card-description">
                                <?php echo htmlspecialchars($project['description'] ?? 'No description provided'); ?>
                            </div>
                            
                            <div class="project-progress">
                                <div class="progress-label">
                                    <span>Progress</span>
                                    <span><?php echo $project['progress_percent']; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill <?php echo $project['progress_percent'] == 0 ? 'zero' : ''; ?>" 
                                         style="width: <?php echo $project['progress_percent']; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="project-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $project['team_size']; ?></span>
                                    <div class="stat-label">Team Size</div>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $project['active_tasks']; ?></span>
                                    <div class="stat-label">Active Tasks</div>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $project['total_tasks']; ?></span>
                                    <div class="stat-label">Total Tasks</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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

        <!-- Admin-Only Section -->
        <div class="section admin-only" data-role-show="admin">
            <h3 class="admin-section-header">🔧 Admin Dashboard</h3>
            <div class="admin-stats">
                <div class="stat-card">
                    <h4>System Management</h4>
                    <p>Manage users, projects, and system settings</p>
                    <button class="btn-create success-button">User Management</button>
                    <button class="btn-create info-button">System Settings</button>
                </div>
            </div>
        </div>

        <div class="page-footer">
            <p>Simple Dashboard - Authentication Testing Complete</p>
        </div>
    </div>
    
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/auth.js"></script>
    <script>
        // Initialize role-based UI and welcome message
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize role-based UI
            if (window.initRoleBasedUI) {
                window.initRoleBasedUI('<?php echo $_SESSION['role'] ?? 'user'; ?>', <?php echo $_SESSION['user_id']; ?>);
            }
            
            // Only show welcome message on first load, not when returning from other pages
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('welcome') === '1') {
                toastInfo('Welcome to your project dashboard!');
                // Clean URL without reloading
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    </script>
</body>
</html>