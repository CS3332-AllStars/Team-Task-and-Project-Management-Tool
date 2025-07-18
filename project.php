<?php
// CS3332 AllStars Team Task & Project Management System
// Project View Page - FR-9, FR-11

require_once 'includes/session-check.php';

// Database connection
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get project ID
$project_id = (int)($_GET['id'] ?? 0);
if (!$project_id) {
    header('Location: dashboard.php');
    exit;
}

// Check if user has access to this project
$stmt = $mysqli->prepare("SELECT role FROM project_memberships WHERE user_id = ? AND project_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $project_id);
$stmt->execute();
$result = $stmt->get_result();
$membership = $result->fetch_assoc();
$stmt->close();

if (!$membership) {
    echo "Access denied: You are not a member of this project.";
    exit;
}

$user_role = $membership['role'];
$is_admin = ($user_role === 'admin');

// Get project details
$stmt = $mysqli->prepare("SELECT * FROM projects WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "Project not found.";
    exit;
}

// Get project members
$stmt = $mysqli->prepare("
    SELECT pm.role, pm.joined_at, u.username, u.name, u.email, u.user_id
    FROM project_memberships pm 
    JOIN users u ON pm.user_id = u.user_id 
    WHERE pm.project_id = ?
    ORDER BY pm.role DESC, pm.joined_at ASC
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

// Handle member management actions (for admins only)
$message = '';
$error = '';

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_member':
                $email = trim($_POST['email'] ?? '');
                if ($email) {
                    // Find user by email
                    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($user) {
                        // Check if already a member
                        $stmt = $mysqli->prepare("SELECT 1 FROM project_memberships WHERE user_id = ? AND project_id = ?");
                        $stmt->bind_param("ii", $user['user_id'], $project_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $exists = $result->fetch_assoc();
                        $stmt->close();
                        
                        if (!$exists) {
                            // Add as member
                            $stmt = $mysqli->prepare("INSERT INTO project_memberships (user_id, project_id, role) VALUES (?, ?, 'member')");
                            $stmt->bind_param("ii", $user['user_id'], $project_id);
                            if ($stmt->execute()) {
                                $message = "Member added successfully!";
                            } else {
                                $error = "Failed to add member.";
                            }
                            $stmt->close();
                        } else {
                            $error = "User is already a member of this project.";
                        }
                    } else {
                        $error = "User not found with that email address.";
                    }
                }
                break;
                
            case 'remove_member':
                $user_id = (int)($_POST['user_id'] ?? 0);
                if ($user_id && $user_id != $_SESSION['user_id']) {
                    $stmt = $mysqli->prepare("DELETE FROM project_memberships WHERE user_id = ? AND project_id = ?");
                    $stmt->bind_param("ii", $user_id, $project_id);
                    if ($stmt->execute()) {
                        $message = "Member removed successfully!";
                    } else {
                        $error = "Failed to remove member.";
                    }
                    $stmt->close();
                }
                break;
        }
        
        // Refresh members list
        $stmt = $mysqli->prepare("
            SELECT pm.role, pm.joined_at, u.username, u.name, u.email, u.user_id
            FROM project_memberships pm 
            JOIN users u ON pm.user_id = u.user_id 
            WHERE pm.project_id = ?
            ORDER BY pm.role DESC, pm.joined_at ASC
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        $stmt->close();
    }
}

// Get task metrics for CS3-12C
$taskMetrics = [
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'in_progress_tasks' => 0,
    'todo_tasks' => 0,
    'completion_percentage' => 0
];

$stmt = $mysqli->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'Done' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status = 'To Do' THEN 1 ELSE 0 END) as todo_tasks
    FROM tasks 
    WHERE project_id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $taskMetrics = $row;
    // Calculate completion percentage
    $taskMetrics['completion_percentage'] = $taskMetrics['total_tasks'] > 0 
        ? round(($taskMetrics['completed_tasks'] / $taskMetrics['total_tasks']) * 100) 
        : 0;
}
$stmt->close();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - TTPM</title>
    <link rel="stylesheet" href="assets/css/project.css">
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="header">
            <div>
                <h1 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h1>
                <p class="text-muted" style="margin: 5px 0 0 0;">
                    <?php echo htmlspecialchars($project['description'] ?? 'No description provided'); ?>
                </p>
            </div>
            <div>
                <span class="role-badge role-<?php echo $user_role; ?>">
                    Your Role: <?php echo ucfirst($user_role); ?>
                </span>
            </div>
        </div>

        <div class="section">
            <h3>Team Members (<?php echo count($members); ?>)</h3>
            
            <ul class="member-list">
                <?php foreach ($members as $member): ?>
                    <li class="member-item">
                        <div class="member-info">
                            <div class="member-name">
                                <?php echo htmlspecialchars($member['name'] ?: $member['username']); ?>
                            </div>
                            <div class="member-email">
                                <?php echo htmlspecialchars($member['email']); ?>
                            </div>
                        </div>
                        <div class="member-actions">
                            <span class="role-badge role-<?php echo $member['role']; ?>" 
                                  data-tooltip="User role: <?php echo ucfirst($member['role']); ?>">
                                <?php echo ucfirst($member['role']); ?>
                            </span>
                            <?php if ($is_admin && $member['user_id'] != $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-danger remove-member-btn" 
                                        data-user-id="<?php echo $member['user_id']; ?>"
                                        data-tooltip="Remove <?php echo htmlspecialchars($member['name'] ?: $member['username']); ?> from project"
                                        data-tooltip-theme="error">Remove</button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($is_admin): ?>
                <div class="add-member-form">
                    <h4>Add New Member</h4>
                    <form id="add-member-form">
                        <div class="form-group small">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="Enter user's email address">
                        </div>
                        <button type="submit" class="btn btn-success">Add Member</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Project Overview & Task Summary</h3>
            
            <!-- Task Metrics Display per CS3-12C -->
            <div class="task-metrics-grid">
                <div class="metric-card">
                    <h4 class="metric-title">Total Tasks</h4>
                    <div class="metric-value total">
                        <?php echo $taskMetrics['total_tasks']; ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h4 class="metric-title">Completed</h4>
                    <div class="metric-value completed">
                        <?php echo $taskMetrics['completed_tasks']; ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h4 class="metric-title">In Progress</h4>
                    <div class="metric-value in-progress">
                        <?php echo $taskMetrics['in_progress_tasks']; ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h4 class="metric-title">To Do</h4>
                    <div class="metric-value todo">
                        <?php echo $taskMetrics['todo_tasks']; ?>
                    </div>
                </div>
            </div>
            
            <!-- Progress Overview -->
            <div class="progress-overview">
                <h4>Project Progress</h4>
                <div class="project-progress">
                    <div class="progress-label">
                        <span><strong>Completion Status</strong></span>
                        <span><strong><?php echo $taskMetrics['completion_percentage']; ?>%</strong></span>
                    </div>
                    <div class="progress-bar large">
                        <div class="progress-fill <?php echo $taskMetrics['completion_percentage'] == 0 ? 'zero' : ''; ?>" 
                             style="width: <?php echo $taskMetrics['completion_percentage']; ?>%"></div>
                    </div>
                    <div class="progress-details">
                        <?php if ($taskMetrics['total_tasks'] > 0): ?>
                            <?php echo $taskMetrics['completed_tasks']; ?> of <?php echo $taskMetrics['total_tasks']; ?> tasks completed
                        <?php else: ?>
                            No tasks created yet. Tasks will appear here once CS3-13 (Task Management) is implemented.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <div class="section">
                <h3>Project Settings</h3>
                <div class="add-member-form">
                    <p class="mb-2"><strong>Project Management Tools</strong></p>
                    <a href="edit-project.php?id=<?php echo $project_id; ?>" class="btn btn-primary">Edit Project Details</a>
                    <p class="mt-2 text-muted" style="font-size: 0.9rem;">
                        Coming soon: Transfer ownership and delete project
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/tooltips.js"></script>
    <script>
        // Show success toast if project was just created
        <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toastSuccess('Project created successfully!');
                // Clean URL without reloading
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname + '?id=<?php echo $project_id; ?>');
                }
            });
        <?php endif; ?>
        
        // Show error toast if there's an error
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toastError(<?php echo json_encode($error); ?>);
            });
        <?php endif; ?>
        
        // Show success toast if there's a success message
        <?php if ($success): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toastSuccess(<?php echo json_encode($success); ?>);
            });
        <?php endif; ?>
        
        // AJAX Member Management
        document.addEventListener('DOMContentLoaded', function() {
            // Handle add member form
            const addMemberForm = document.getElementById('add-member-form');
            if (addMemberForm) {
                addMemberForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const email = formData.get('email');
                    
                    try {
                        const response = await api.post('api/member-actions.php', {
                            action: 'add_member',
                            project_id: <?php echo $project_id; ?>,
                            email: email
                        });
                        
                        toastSuccess(response.message || 'Member added successfully');
                        this.reset();
                        // Reload page to show new member
                        setTimeout(() => window.location.reload(), 1000);
                        
                    } catch (error) {
                        // Error toast already shown by api.js
                    }
                });
            }
            
            // Handle remove member buttons
            document.addEventListener('click', async function(e) {
                if (e.target.matches('.remove-member-btn')) {
                    e.preventDefault();
                    
                    if (!confirm('Are you sure you want to remove this member?')) {
                        return;
                    }
                    
                    const userId = e.target.getAttribute('data-user-id');
                    const memberItem = e.target.closest('.member-item');
                    
                    try {
                        const response = await api.post('api/member-actions.php', {
                            action: 'remove_member',
                            project_id: <?php echo $project_id; ?>,
                            user_id: parseInt(userId)
                        });
                        
                        toastSuccess(response.message || 'Member removed successfully');
                        
                        // Remove member from UI with animation
                        if (memberItem) {
                            memberItem.style.opacity = '0.5';
                            memberItem.style.transform = 'translateX(-10px)';
                            setTimeout(() => {
                                memberItem.remove();
                            }, 300);
                        }
                        
                    } catch (error) {
                        // Error toast already shown by api.js
                    }
                }
            });
        });
    </script>
</body>
</html>