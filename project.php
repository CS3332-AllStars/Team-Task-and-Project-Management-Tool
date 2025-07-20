<?php
// CS3332 AllStars Team Task & Project Management System
// Project View Page - FR-9, FR-11
// CS3-13A frontend validation logic contributed by Juan Ledet

require_once 'includes/session-check.php';

// Set current user from session
$currentUser = [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? 'user'
];

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
                    <div class="metric-value total" id="total-tasks-count">
                        <?php echo $taskMetrics['total_tasks']; ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h4 class="metric-title">Completed</h4>
                    <div class="metric-value completed" id="completed-tasks-count">
                        <?php echo $taskMetrics['completed_tasks']; ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h4 class="metric-title">In Progress</h4>
                    <div class="metric-value in-progress" id="in-progress-tasks-count">
                        <?php echo $taskMetrics['in_progress_tasks']; ?>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h4 class="metric-title">To Do</h4>
                    <div class="metric-value todo" id="todo-tasks-count">
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
                        <span><strong id="completion-percentage"><?php echo $taskMetrics['completion_percentage']; ?>%</strong></span>
                    </div>
                    <div class="progress-bar large">
                        <div class="progress-fill <?php echo $taskMetrics['completion_percentage'] == 0 ? 'zero' : ''; ?>" 
                             id="progress-fill" style="width: <?php echo $taskMetrics['completion_percentage']; ?>%"></div>
                    </div>
                    <div class="progress-details" id="progress-details">
                        <?php if ($taskMetrics['total_tasks'] > 0): ?>
                            <?php echo $taskMetrics['completed_tasks']; ?> of <?php echo $taskMetrics['total_tasks']; ?> tasks completed
                        <?php else: ?>
                            No tasks created yet. Tasks will appear here once CS3-13 (Task Management) is implemented.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Management Section - CS3-13B, CS3-13C, CS3-13D -->
        <div class="section">
            <div class="section-header">
                <h3>Task Management</h3>
                <button id="create-task-btn" class="btn btn-success" 
                        onclick="showTaskModal(); return false;"
                        data-tooltip="Create a new task for this project">
                    + Create Task
                </button>
            </div>
            
            <!-- View Toggle & Filters -->
            <div class="task-controls">
                <div class="view-toggle">
                    <button id="list-view-btn" class="btn btn-secondary active" data-tooltip="List view">
                        📋 List
                    </button>
                    <button id="kanban-view-btn" class="btn btn-secondary" data-tooltip="Kanban board">
                        🗂️ Kanban
                    </button>
                    <button id="calendar-view-btn" class="btn btn-secondary" data-tooltip="Calendar view">
                        📅 Calendar
                    </button>
                    <button id="mytasks-view-btn" class="btn btn-secondary" data-tooltip="My tasks">
                        👤 My Tasks
                    </button>
                    <button id="team-view-btn" class="btn btn-secondary" data-tooltip="Team view">
                        👥 Team
                    </button>
                </div>
                
                <div class="task-filters">
                    <select id="status-filter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="To Do">To Do</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Done">Done</option>
                    </select>
                    
                    <select id="assignee-filter" class="filter-select">
                        <option value="">All Assignees</option>
                        <option value="unassigned">Unassigned</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['user_id']; ?>">
                                <?php echo htmlspecialchars($member['name'] ?: $member['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" id="due-start-filter" class="filter-select" title="Due date from">
                    <input type="date" id="due-end-filter" class="filter-select" title="Due date to">
                    
                    <input type="text" id="search-tasks" placeholder="Search tasks..." class="search-input">
                    
                    <button type="button" id="clear-filters-btn" class="btn btn-secondary btn-small" 
                            data-tooltip="Clear all filters">Clear</button>
                </div>
            </div>
            
            <!-- Task List View -->
            <div id="task-list-view" class="task-list-view">
                <div id="task-list" class="task-list">
                    <div class="loading-message">Loading tasks...</div>
                </div>
                
                <!-- Empty State -->
                <div id="empty-state" class="empty-state" style="display: none;">
                    <div class="empty-icon">📋</div>
                    <h4>No tasks yet</h4>
                    <p>Create your first task to get started with project management.</p>
                    <button id="create-first-task-btn" class="btn btn-primary">Create First Task</button>
                </div>
            </div>
            
            <!-- Kanban Board View -->
            <div id="kanban-board" class="kanban-board" style="display: none;">
                <div class="kanban-column" data-status="To Do">
                    <div class="kanban-header">
                        <h4>📝 To Do</h4>
                        <span class="task-count" id="todo-count">0</span>
                    </div>
                    <div class="kanban-tasks" id="kanban-todo">
                        <!-- Tasks will be populated here -->
                    </div>
                </div>
                
                <div class="kanban-column" data-status="In Progress">
                    <div class="kanban-header">
                        <h4>⚡ In Progress</h4>
                        <span class="task-count" id="progress-count">0</span>
                    </div>
                    <div class="kanban-tasks" id="kanban-progress">
                        <!-- Tasks will be populated here -->
                    </div>
                </div>
                
                <div class="kanban-column" data-status="Done">
                    <div class="kanban-header">
                        <h4>✅ Done</h4>
                        <span class="task-count" id="done-count">0</span>
                    </div>
                    <div class="kanban-tasks" id="kanban-done">
                        <!-- Tasks will be populated here -->
                    </div>
                </div>
            </div>
            
            <!-- Calendar View - CS3-13G -->
            <div id="calendar-view" class="calendar-view" style="display: none;">
                <div class="calendar-header">
                    <h4>📅 Calendar View</h4>
                    <p class="text-muted">Tasks organized by due date</p>
                </div>
                <div id="calendar-content" class="calendar-content">
                    <div class="loading-message">Loading calendar...</div>
                </div>
            </div>
            
            <!-- My Tasks View - CS3-13G -->
            <div id="mytasks-view" class="mytasks-view" style="display: none;">
                <div class="mytasks-header">
                    <h4>👤 My Tasks</h4>
                    <p class="text-muted">Tasks assigned to you</p>
                </div>
                <div id="mytasks-content" class="mytasks-content">
                    <div class="loading-message">Loading your tasks...</div>
                </div>
            </div>
            
            <!-- Team View - CS3-13G -->
            <div id="team-view" class="team-view" style="display: none;">
                <div class="team-header">
                    <h4>👥 Team View</h4>
                    <p class="text-muted">Tasks grouped by team member</p>
                </div>
                <div id="team-content" class="team-content">
                    <div class="loading-message">Loading team tasks...</div>
                </div>
            </div>
        </div>

        <!-- Task Creation Modal -->
        <div id="task-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 id="modal-title">Create New Task</h4>
                    <button type="button" class="close-modal" data-tooltip="Close modal">&times;</button>
                </div>
                <form id="task-form" class="modal-body">
                    <input type="hidden" id="task-id" name="task_id">
                    
                    <div class="form-group">
                        <label for="task-title">Task Title *</label>
                        <input type="text" id="task-title" name="title" required maxlength="100" 
                               placeholder="Enter task title">
                    </div>
                    
                    <div class="form-group">
                        <label for="task-description">Description</label>
                        <textarea id="task-description" name="description" rows="3" 
                                  placeholder="Describe the task..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="task-due-date">Due Date</label>
                            <input type="date" id="task-due-date" name="due_date">
                        </div>
                        
                        <div class="form-group">
                            <label for="task-status">Status</label>
                            <select id="task-status" name="status">
                                <option value="To Do">To Do</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="task-assignees">Assign To</label>
                        <div class="assignee-selection">
                            <?php foreach ($members as $member): ?>
                                <label class="assignee-option">
                                    <input type="checkbox" name="assignees[]" 
                                           value="<?php echo $member['user_id']; ?>">
                                    <span class="checkmark"></span>
                                    <?php echo htmlspecialchars($member['name'] ?: $member['username']); ?>
                                    <small>(<?php echo htmlspecialchars($member['email']); ?>)</small>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-task-btn">Cancel</button>
                    <button type="submit" form="task-form" class="btn btn-success" id="save-task-btn">
                        Create Task
                    </button>
                </div>
            </div>
        </div>

        <!-- Task Detail Modal with Comments - CS3-14C -->
        <div id="task-detail-modal" class="modal" style="display: none;">
            <div class="modal-content large">
                <div class="modal-header">
                    <h4 id="detail-modal-title">Task Details</h4>
                    <div class="modal-actions">
                        <button type="button" id="edit-task-modal-btn" class="btn btn-secondary btn-small" 
                                data-tooltip="Edit task">✏️ Edit</button>
                        <button type="button" id="delete-task-modal-btn" class="btn btn-danger btn-small" 
                                data-tooltip="Delete task">🗑️ Delete</button>
                        <button type="button" class="close-modal" data-modal="task-detail-modal" data-tooltip="Close modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="task-detail-content">
                        <!-- Task details will be loaded here -->
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="comments-section">
                        <h5>Comments</h5>
                        
                        <!-- Comment Form -->
                        <form id="comment-form" class="comment-form">
                            <input type="hidden" id="comment-task-id" name="task_id">
                            <div class="form-group">
                                <textarea id="comment-content" name="content" rows="3" 
                                          placeholder="Add a comment..." required maxlength="1000"></textarea>
                                <div class="comment-form-actions">
                                    <small class="char-counter">0/1000</small>
                                    <button type="submit" class="btn btn-primary btn-small">Post Comment</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Comments List -->
                        <div id="comments-list" class="comments-list">
                            <div class="loading-comments">Loading comments...</div>
                        </div>
                        
                        <!-- Empty Comments State -->
                        <div id="empty-comments" class="empty-comments" style="display: none;">
                            <div class="empty-icon">💬</div>
                            <p>No comments yet. Start the conversation!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comment Edit Modal -->
        <div id="edit-comment-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4>Edit Comment</h4>
                    <button type="button" class="close-modal" data-modal="edit-comment-modal" data-tooltip="Close modal">&times;</button>
                </div>
                <form id="edit-comment-form" class="modal-body">
                    <input type="hidden" id="edit-comment-id" name="comment_id">
                    <div class="form-group">
                        <label for="edit-comment-content">Comment</label>
                        <textarea id="edit-comment-content" name="content" rows="4" 
                                  required maxlength="1000"></textarea>
                        <small class="char-counter">0/1000</small>
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal="edit-comment-modal" onclick="hideModal('edit-comment-modal')">Cancel</button>
                    <button type="submit" form="edit-comment-form" class="btn btn-primary">Update Comment</button>
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
    
    <script>
        
        // Define showTaskModal function in its own script block
        function showTaskModal() {
            const taskModal = document.getElementById('task-modal');
            if (taskModal) {
                taskModal.style.display = 'flex';
                taskModal.style.visibility = 'visible';
                taskModal.style.opacity = '1';
                taskModal.style.zIndex = '9999';
                document.body.style.overflow = 'hidden';
                
                // Reset form
                const taskForm = document.getElementById('task-form');
                if (taskForm) {
                    taskForm.reset();
                }
                
                // Set default values
                const modalTitle = document.getElementById('modal-title');
                const saveBtn = document.getElementById('save-task-btn');
                const taskStatus = document.getElementById('task-status');
                
                if (modalTitle) modalTitle.textContent = 'Create New Task';
                if (saveBtn) saveBtn.textContent = 'Create Task';
                if (taskStatus) taskStatus.value = 'To Do';
                
            }
        }
        
        
        // Hide modal function
        function hideTaskModal() {
            const taskModal = document.getElementById('task-modal');
            if (taskModal) {
                taskModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Helper functions for validation
        function showError(message) {
            if (window.ToastManager) {
                window.ToastManager.error(message);
            } else {
                alert(message);
            }
        }
        
        function isValidDate(dateString) {
            if (!dateString) return true; // Allow empty dates
            const regex = /^\d{4}-\d{2}-\d{2}$/;
            if (!regex.test(dateString)) return false;
            
            const date = new Date(dateString);
            const timestamp = date.getTime();
            
            if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
                return false;
            }
            
            return date.toISOString().startsWith(dateString);
        }
        
        
        // Handle task form submission
        async function handleTaskSubmit(event) {
            event.preventDefault();
            
            const form = document.getElementById('task-form');
            const formData = new FormData(form);
            
            // Get form values
            const taskData = {
                project_id: <?php echo $project_id; ?>,
                title: formData.get('title'),
                description: formData.get('description'),
                due_date: formData.get('due_date') || null,
                assignees: []
            };
            
            // Frontend validation
            const title = taskData.title ? taskData.title.trim() : '';
            const dueDate = taskData.due_date;
            
            // Validate title
            if (title === '' || title.length > 100) {
                showError('Title is required and must be under 100 characters.');
                return;
            }
            
            // Validate due date
            if (dueDate && !isValidDate(dueDate)) {
                showError('Please enter a valid due date.');
                return;
            }
            
            // Update title with trimmed value
            taskData.title = title;
            
            // Get selected assignees
            const assigneeCheckboxes = form.querySelectorAll('input[name="assignees[]"]:checked');
            assigneeCheckboxes.forEach(checkbox => {
                taskData.assignees.push(parseInt(checkbox.value));
            });
            
            
            try {
                const response = await fetch('api/tasks.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(taskData)
                });
                
                // Log the raw response first
                const responseText = await response.text();
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Server returned invalid JSON');
                }
                
                if (result.success) {
                    // Show success message
                    if (window.ToastManager) {
                        window.ToastManager.success('Task created successfully!');
                    }
                    
                    // Hide modal
                    hideTaskModal();
                    
                    // Reload tasks if TaskManager exists
                    if (window.taskManager && typeof window.taskManager.loadTasks === 'function') {
                        window.taskManager.loadTasks();
                    }
                } else {
                    // Show error message
                    if (window.ToastManager) {
                        window.ToastManager.error(result.message || 'Failed to create task');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to create task'));
                    }
                }
                
            } catch (error) {
                console.error('Error creating task:', error);
                if (window.ToastManager) {
                    window.ToastManager.error('Network error occurred');
                } else {
                    alert('Network error occurred');
                }
            }
        }
        
        // Add modal event listeners when DOM is ready (TaskManager handles form submission)
        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtn = document.getElementById('cancel-task-btn');
            const closeBtn = document.querySelector('.close-modal');
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', hideTaskModal);
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', hideTaskModal);
            }
        });
        
    </script>
    
    
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/tooltips.js"></script>
    <script>
        
        // Test basic functionality first
        
        // Show success toast if project was just created
        /* <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toastSuccess('Project created successfully!');
                // Clean URL without reloading
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname + '?id=<?php echo $project_id; ?>');
                }
            });
        <?php endif; ?> */
        
        // Show error toast if there's an error
        /* <?php if (isset($error) && $error): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toastError(<?php echo json_encode($error); ?>);
            });
        <?php endif; ?>
        
        // Show success toast if there's a success message
        <?php if (isset($message) && $message): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toastSuccess(<?php echo json_encode($message); ?>);
            });
        <?php endif; ?> */
        
        
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
        
        // Task Management JavaScript - CS3-13B, CS3-13C, CS3-13D
        
        class TaskManager {
            constructor(projectId) {
                this.projectId = projectId;
                this.tasks = [];
                this.currentTask = null;
                this.currentView = 'list'; // 'list' or 'kanban'
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.loadTasks();
                this.startAutoRefresh();
            }
            
            bindEvents() {
                // View toggle buttons
                const listViewBtn = document.getElementById('list-view-btn');
                const kanbanViewBtn = document.getElementById('kanban-view-btn');
                const calendarViewBtn = document.getElementById('calendar-view-btn');
                const mytasksViewBtn = document.getElementById('mytasks-view-btn');
                const teamViewBtn = document.getElementById('team-view-btn');
                
                if (listViewBtn) {
                    listViewBtn.addEventListener('click', () => this.switchView('list'));
                }
                
                if (kanbanViewBtn) {
                    kanbanViewBtn.addEventListener('click', () => this.switchView('kanban'));
                }
                
                if (calendarViewBtn) {
                    calendarViewBtn.addEventListener('click', () => this.switchView('calendar'));
                }
                
                if (mytasksViewBtn) {
                    mytasksViewBtn.addEventListener('click', () => this.switchView('mytasks'));
                }
                
                if (teamViewBtn) {
                    teamViewBtn.addEventListener('click', () => this.switchView('team'));
                }
                
                // Modal controls with error handling
                const createBtn = document.getElementById('create-task-btn');
                const createFirstBtn = document.getElementById('create-first-task-btn');
                const cancelBtn = document.getElementById('cancel-task-btn');
                const closeBtn = document.querySelector('.close-modal');
                const taskForm = document.getElementById('task-form');
                const statusFilter = document.getElementById('status-filter');
                const assigneeFilter = document.getElementById('assignee-filter');
                const dueStartFilter = document.getElementById('due-start-filter');
                const dueEndFilter = document.getElementById('due-end-filter');
                const searchInput = document.getElementById('search-tasks');
                const clearFiltersBtn = document.getElementById('clear-filters-btn');
                const taskModal = document.getElementById('task-modal');
                
                if (createBtn) {
                    createBtn.addEventListener('click', () => {
                        this.showCreateModal();
                    });
                }
                
                if (createFirstBtn) {
                    createFirstBtn.addEventListener('click', () => this.showCreateModal());
                }
                
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => this.hideModal());
                }
                
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => this.hideModal());
                }
                
                // Form submission
                if (taskForm) {
                    taskForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
                }
                
                // Filters
                if (statusFilter) {
                    statusFilter.addEventListener('change', () => this.filterTasks());
                }
                
                if (assigneeFilter) {
                    assigneeFilter.addEventListener('change', () => this.filterTasks());
                }
                
                if (dueStartFilter) {
                    dueStartFilter.addEventListener('change', () => this.filterTasks());
                }
                
                if (dueEndFilter) {
                    dueEndFilter.addEventListener('change', () => this.filterTasks());
                }
                
                if (searchInput) {
                    // Debounce search input to avoid excessive API calls
                    let searchTimeout;
                    searchInput.addEventListener('input', () => {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => this.filterTasks(), 300);
                    });
                }
                
                if (clearFiltersBtn) {
                    clearFiltersBtn.addEventListener('click', () => this.clearFilters());
                }
                
                // Close modal on outside click
                if (taskModal) {
                    taskModal.addEventListener('click', (e) => {
                        if (e.target.id === 'task-modal') this.hideModal();
                    });
                }
            }
            
            async loadTasks() {
                try {
                    const url = `api/tasks.php?action=project&project_id=${this.projectId}`;
                    
                    const response = await api.get(url);
                    
                    this.tasks = response.tasks || [];
                    this.renderTasks();
                    // Update dashboard metrics whenever tasks are loaded
                    this.updateDashboardMetrics();
                } catch (error) {
                    console.error('Failed to load tasks:', error);
                    this.showEmptyState();
                }
            }
            
            async loadViewData(view) {
                try {
                    let url, tasks;
                    
                    switch (view) {
                        case 'list':
                        case 'kanban':
                            url = `api/tasks.php?action=project&project_id=${this.projectId}`;
                            break;
                        case 'calendar':
                            url = `api/tasks.php?action=calendar&project_id=${this.projectId}`;
                            break;
                        case 'mytasks':
                            url = `api/tasks.php?action=mytasks&project_id=${this.projectId}`;
                            break;
                        case 'team':
                            url = `api/tasks.php?action=team&project_id=${this.projectId}`;
                            break;
                        default:
                            url = `api/tasks.php?action=project&project_id=${this.projectId}`;
                    }
                    
                    const response = await api.get(url);
                    this.tasks = response.tasks || [];
                    
                    // Render the appropriate view
                    this.renderTasks();
                    this.updateDashboardMetricsFromAllTasks();
                    
                } catch (error) {
                    console.error('Failed to load view data:', error);
                    this.showEmptyState();
                }
            }
            
            renderTasks() {
                switch (this.currentView) {
                    case 'kanban':
                        this.renderKanban();
                        break;
                    case 'calendar':
                        this.renderCalendar();
                        break;
                    case 'mytasks':
                        this.renderMyTasks();
                        break;
                    case 'team':
                        this.renderTeam();
                        break;
                    case 'list':
                    default:
                        this.renderList();
                        break;
                }
            }
            
            renderList() {
                const taskList = document.getElementById('task-list');
                const emptyState = document.getElementById('empty-state');
                
                if (this.tasks.length === 0) {
                    this.showEmptyState();
                    return;
                }
                
                emptyState.style.display = 'none';
                taskList.innerHTML = this.tasks.map(task => this.renderTaskCard(task)).join('');
                
                // Bind task-specific events
                this.bindTaskEvents();
            }
            
            renderKanban() {
                const emptyState = document.getElementById('empty-state');
                
                if (this.tasks.length === 0) {
                    this.showEmptyState();
                    return;
                }
                
                emptyState.style.display = 'none';
                
                // Group tasks by status
                const tasksByStatus = {
                    'To Do': this.tasks.filter(task => task.status === 'To Do'),
                    'In Progress': this.tasks.filter(task => task.status === 'In Progress'),
                    'Done': this.tasks.filter(task => task.status === 'Done')
                };
                
                // Render tasks in each column
                Object.keys(tasksByStatus).forEach(status => {
                    const columnId = status === 'To Do' ? 'kanban-todo' : 
                                   status === 'In Progress' ? 'kanban-progress' : 'kanban-done';
                    const countId = status === 'To Do' ? 'todo-count' : 
                                  status === 'In Progress' ? 'progress-count' : 'done-count';
                    
                    const column = document.getElementById(columnId);
                    const countEl = document.getElementById(countId);
                    
                    if (column && countEl) {
                        const tasks = tasksByStatus[status];
                        countEl.textContent = tasks.length;
                        column.innerHTML = tasks.map(task => this.renderKanbanCard(task)).join('');
                    }
                });
                
                // Setup drag and drop without delays
                this.setupDragAndDrop();
                this.bindKanbanEvents();
            }
            
            renderCalendar() {
                const calendarContent = document.getElementById('calendar-content');
                const emptyState = document.getElementById('empty-state');
                
                if (this.tasks.length === 0) {
                    calendarContent.innerHTML = '<div class="empty-calendar"><div class="empty-icon">📅</div><p>No tasks with due dates found</p></div>';
                    return;
                }
                
                // Group tasks by due date
                const tasksByDate = {};
                this.tasks.forEach(task => {
                    const dueDate = task.due_date;
                    if (!tasksByDate[dueDate]) {
                        tasksByDate[dueDate] = [];
                    }
                    tasksByDate[dueDate].push(task);
                });
                
                // Render calendar
                const sortedDates = Object.keys(tasksByDate).sort();
                let calendarHtml = '<div class="calendar-timeline">';
                
                sortedDates.forEach(date => {
                    const tasks = tasksByDate[date];
                    const dateObj = new Date(date);
                    const isOverdue = dateObj < new Date() && tasks.some(t => t.status !== 'Done');
                    
                    calendarHtml += `
                        <div class="calendar-date-group ${isOverdue ? 'overdue' : ''}">
                            <div class="calendar-date-header">
                                <h5>${dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h5>
                                <span class="task-count">${tasks.length} task${tasks.length !== 1 ? 's' : ''}</span>
                            </div>
                            <div class="calendar-tasks">
                                ${tasks.map(task => this.renderCalendarTask(task)).join('')}
                            </div>
                        </div>
                    `;
                });
                
                calendarHtml += '</div>';
                calendarContent.innerHTML = calendarHtml;
            }
            
            renderMyTasks() {
                const mytasksContent = document.getElementById('mytasks-content');
                
                if (this.tasks.length === 0) {
                    mytasksContent.innerHTML = '<div class="empty-mytasks"><div class="empty-icon">👤</div><p>No tasks assigned to you</p></div>';
                    return;
                }
                
                // Group by status
                const tasksByStatus = {
                    'To Do': this.tasks.filter(task => task.status === 'To Do'),
                    'In Progress': this.tasks.filter(task => task.status === 'In Progress'),
                    'Done': this.tasks.filter(task => task.status === 'Done')
                };
                
                let mytasksHtml = '<div class="mytasks-groups">';
                
                Object.keys(tasksByStatus).forEach(status => {
                    const tasks = tasksByStatus[status];
                    if (tasks.length > 0) {
                        mytasksHtml += `
                            <div class="mytasks-status-group">
                                <div class="mytasks-status-header">
                                    <h5>${status}</h5>
                                    <span class="task-count">${tasks.length}</span>
                                </div>
                                <div class="mytasks-list">
                                    ${tasks.map(task => this.renderMyTaskCard(task)).join('')}
                                </div>
                            </div>
                        `;
                    }
                });
                
                mytasksHtml += '</div>';
                mytasksContent.innerHTML = mytasksHtml;
            }
            
            renderTeam() {
                const teamContent = document.getElementById('team-content');
                
                if (this.tasks.length === 0) {
                    teamContent.innerHTML = '<div class="empty-team"><div class="empty-icon">👥</div><p>No tasks assigned to team members</p></div>';
                    return;
                }
                
                // Group by assignee
                const tasksByAssignee = {};
                this.tasks.forEach(task => {
                    const assigneeName = task.assignee_name || 'Unassigned';
                    if (!tasksByAssignee[assigneeName]) {
                        tasksByAssignee[assigneeName] = [];
                    }
                    tasksByAssignee[assigneeName].push(task);
                });
                
                let teamHtml = '<div class="team-groups">';
                
                Object.keys(tasksByAssignee).sort().forEach(assignee => {
                    const tasks = tasksByAssignee[assignee];
                    teamHtml += `
                        <div class="team-assignee-group">
                            <div class="team-assignee-header">
                                <h5>👤 ${this.escapeHtml(assignee)}</h5>
                                <span class="task-count">${tasks.length} task${tasks.length !== 1 ? 's' : ''}</span>
                            </div>
                            <div class="team-tasks">
                                ${tasks.map(task => this.renderTeamTask(task)).join('')}
                            </div>
                        </div>
                    `;
                });
                
                teamHtml += '</div>';
                teamContent.innerHTML = teamHtml;
            }
            
            renderCalendarTask(task) {
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'Done';
                return `
                    <div class="calendar-task ${task.status.toLowerCase().replace(' ', '-')} ${isOverdue ? 'overdue' : ''}" 
                         data-task-id="${task.task_id}" style="cursor: pointer;">
                        <div class="calendar-task-title">${this.escapeHtml(task.title)}</div>
                        <div class="calendar-task-meta">
                            <span class="status-badge status-${task.status.toLowerCase().replace(' ', '-')}">${task.status}</span>
                            ${task.assigned_by_username ? `<span class="assigned-by">by ${this.escapeHtml(task.assigned_by_username)}</span>` : ''}
                        </div>
                    </div>
                `;
            }
            
            renderMyTaskCard(task) {
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'Done';
                return `
                    <div class="mytask-card ${task.status.toLowerCase().replace(' ', '-')} ${isOverdue ? 'overdue' : ''}" 
                         data-task-id="${task.task_id}" style="cursor: pointer;">
                        <div class="mytask-title">${this.escapeHtml(task.title)}</div>
                        ${task.description ? `<div class="mytask-description">${this.escapeHtml(task.description.substring(0, 100))}${task.description.length > 100 ? '...' : ''}</div>` : ''}
                        <div class="mytask-meta">
                            ${task.due_date ? `<span class="due-date ${isOverdue ? 'overdue' : ''}">📅 ${new Date(task.due_date).toLocaleDateString()}</span>` : ''}
                            ${task.assigned_by_username ? `<span class="assigned-by">Created by ${this.escapeHtml(task.assigned_by_username)}</span>` : ''}
                        </div>
                    </div>
                `;
            }
            
            renderTeamTask(task) {
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'Done';
                return `
                    <div class="team-task ${task.status.toLowerCase().replace(' ', '-')} ${isOverdue ? 'overdue' : ''}" 
                         data-task-id="${task.task_id}" style="cursor: pointer;">
                        <div class="team-task-header">
                            <div class="team-task-title">${this.escapeHtml(task.title)}</div>
                            <span class="status-badge status-${task.status.toLowerCase().replace(' ', '-')}">${task.status}</span>
                        </div>
                        ${task.due_date ? `<div class="team-task-due ${isOverdue ? 'overdue' : ''}">📅 Due: ${new Date(task.due_date).toLocaleDateString()}</div>` : ''}
                    </div>
                `;
            }
            
            renderTaskCard(task) {
                const assignees = task.assignees ? task.assignees.split(',').map(a => {
                    const [username, userId] = a.split(':');
                    return { username, userId };
                }).filter(a => a.username) : [];
                
                const statusClass = task.status.toLowerCase().replace(' ', '-');
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'Done';
                
                return `
                    <div class="task-card ${statusClass}" data-task-id="${task.task_id}">
                        <div class="task-header">
                            <h4 class="task-title clickable" data-task-id="${task.task_id}" data-tooltip="Click to view details and comments">
                                ${this.escapeHtml(task.title)}
                            </h4>
                            <div class="task-actions">
                                <button class="btn-icon edit-task-btn" data-task-id="${task.task_id}" 
                                        data-tooltip="Edit task">✏️</button>
                                <button class="btn-icon delete-task-btn" data-task-id="${task.task_id}" 
                                        data-tooltip="Delete task">🗑️</button>
                            </div>
                        </div>
                        
                        ${task.description ? `<p class="task-description">${this.escapeHtml(task.description)}</p>` : ''}
                        
                        <div class="task-meta">
                            <div class="task-status">
                                <select class="status-select" data-task-id="${task.task_id}">
                                    <option value="To Do" ${task.status === 'To Do' ? 'selected' : ''}>To Do</option>
                                    <option value="In Progress" ${task.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="Done" ${task.status === 'Done' ? 'selected' : ''}>Done</option>
                                </select>
                            </div>
                            
                            ${task.due_date ? `
                                <div class="task-due-date ${isOverdue ? 'overdue' : ''}">
                                    📅 ${new Date(task.due_date).toLocaleDateString()}
                                </div>
                            ` : ''}
                        </div>
                        
                        ${assignees.length > 0 ? `
                            <div class="task-assignees">
                                <span class="assignees-label">Assigned to:</span>
                                ${assignees.map(a => `<span class="assignee-badge">${this.escapeHtml(a.username)}</span>`).join('')}
                            </div>
                        ` : '<div class="task-assignees"><span class="unassigned">Unassigned</span></div>'}
                        
                        <div class="task-footer">
                            <div class="task-meta-footer">
                                <small class="task-created">
                                    Created ${new Date(task.created_at).toLocaleDateString()}
                                    ${task.assigned_by_username ? `by ${this.escapeHtml(task.assigned_by_username)}` : ''}
                                </small>
                                <button class="btn-link comment-count-btn" data-task-id="${task.task_id}" data-tooltip="View comments">
                                    💬 <span class="comment-count" data-task-id="${task.task_id}">...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            bindTaskEvents() {
                // Remove existing event listeners to prevent memory leaks
                this.removeTaskEventListeners();
                
                // Store event handlers for cleanup
                this.taskEventHandlers = {
                    statusChange: (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        const newStatus = e.target.value;
                        this.updateTaskStatus(taskId, newStatus);
                    },
                    editTask: (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        this.showEditModal(taskId);
                    },
                    deleteTask: (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        this.deleteTask(taskId);
                    },
                    showDetail: (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        this.showTaskDetailModal(taskId);
                    },
                    showComments: (e) => {
                        e.stopPropagation();
                        const taskId = e.currentTarget.getAttribute('data-task-id');
                        this.showTaskDetailModal(taskId);
                    }
                };
                
                // Add event listeners with stored handlers
                document.querySelectorAll('.status-select').forEach(select => {
                    select.addEventListener('change', this.taskEventHandlers.statusChange);
                });
                
                document.querySelectorAll('.edit-task-btn').forEach(btn => {
                    btn.addEventListener('click', this.taskEventHandlers.editTask);
                });
                
                document.querySelectorAll('.delete-task-btn').forEach(btn => {
                    btn.addEventListener('click', this.taskEventHandlers.deleteTask);
                });
                
                document.querySelectorAll('.task-title.clickable').forEach(title => {
                    title.addEventListener('click', this.taskEventHandlers.showDetail);
                });
                
                document.querySelectorAll('.comment-count-btn').forEach(btn => {
                    btn.addEventListener('click', this.taskEventHandlers.showComments);
                });
                
                // CS3-13G: Add click bindings for Calendar, My Tasks, and Team view task cards
                document.querySelectorAll('.calendar-task').forEach(card => {
                    card.addEventListener('click', this.taskEventHandlers.showDetail);
                });
                
                document.querySelectorAll('.mytask-card').forEach(card => {
                    card.addEventListener('click', this.taskEventHandlers.showDetail);
                });
                
                document.querySelectorAll('.team-task').forEach(card => {
                    card.addEventListener('click', this.taskEventHandlers.showDetail);
                });
                
                // Load comment counts (throttled)
                if (!this.commentCountsLoaded) {
                    this.loadCommentCounts();
                    this.commentCountsLoaded = true;
                }
            }
            
            removeTaskEventListeners() {
                if (this.taskEventHandlers) {
                    document.querySelectorAll('.status-select').forEach(select => {
                        select.removeEventListener('change', this.taskEventHandlers.statusChange);
                    });
                    
                    document.querySelectorAll('.edit-task-btn').forEach(btn => {
                        btn.removeEventListener('click', this.taskEventHandlers.editTask);
                    });
                    
                    document.querySelectorAll('.delete-task-btn').forEach(btn => {
                        btn.removeEventListener('click', this.taskEventHandlers.deleteTask);
                    });
                    
                    document.querySelectorAll('.task-title.clickable').forEach(title => {
                        title.removeEventListener('click', this.taskEventHandlers.showDetail);
                    });
                    
                    document.querySelectorAll('.comment-count-btn').forEach(btn => {
                        btn.removeEventListener('click', this.taskEventHandlers.showComments);
                    });
                    
                    // CS3-13G: Remove click bindings for Calendar, My Tasks, and Team view task cards
                    document.querySelectorAll('.calendar-task').forEach(card => {
                        card.removeEventListener('click', this.taskEventHandlers.showDetail);
                    });
                    
                    document.querySelectorAll('.mytask-card').forEach(card => {
                        card.removeEventListener('click', this.taskEventHandlers.showDetail);
                    });
                    
                    document.querySelectorAll('.team-task').forEach(card => {
                        card.removeEventListener('click', this.taskEventHandlers.showDetail);
                    });
                }
            }
            
            async updateTaskStatus(taskId, status) {
                const task = this.tasks.find(t => t.task_id == taskId);
                if (!task) {
                    toastError('Task not found');
                    return;
                }
                
                const oldStatus = task.status;
                if (oldStatus === status) {
                    return; // No change needed
                }
                
                // OPTIMISTIC UPDATE: Update UI immediately
                this.updateTaskInArray(taskId, status);
                
                // Update dropdown if it exists (for list view)
                const select = document.querySelector(`.status-select[data-task-id="${taskId}"]`);
                if (select) {
                    select.value = status;
                }
                
                // Update views and metrics immediately (throttled)
                this.scheduleViewUpdate();
                
                try {
                    // Make backend call
                    await this.updateTaskStatusBackend(taskId, status);
                    toastSuccess('Task status updated successfully');
                    
                } catch (error) {
                    // ROLLBACK on failure
                    this.revertTaskInArray(taskId, oldStatus);
                    
                    // Revert dropdown
                    if (select) {
                        select.value = oldStatus;
                    }
                    
                    // Re-render views to show reverted state (throttled)
                    this.scheduleViewUpdate();
                    
                    toastError('Failed to update task status - changes reverted');
                }
            }
            
            async showEditModalFromDetail(taskId) {
                try {
                    // Load task details for editing
                    const response = await api.get(`api/tasks.php?action=detail&task_id=${taskId}`);
                    this.currentTask = response.task;
                    
                    // Hide detail modal instantly
                    document.getElementById('task-detail-modal').style.display = 'none';
                    
                    // Prepare edit modal (don't reset overflow yet)
                    document.getElementById('modal-title').textContent = 'Edit Task';
                    document.getElementById('save-task-btn').textContent = 'Update Task';
                    
                    // Populate form
                    document.getElementById('task-id').value = this.currentTask.task_id;
                    document.getElementById('task-title').value = this.currentTask.title;
                    document.getElementById('task-description').value = this.currentTask.description || '';
                    document.getElementById('task-due-date').value = this.currentTask.due_date || '';
                    document.getElementById('task-status').value = this.currentTask.status;
                    
                    // Set assignees
                    document.querySelectorAll('input[name="assignees[]"]').forEach(checkbox => {
                        checkbox.checked = this.currentTask.assignees.some(a => a.user_id == checkbox.value);
                    });
                    
                    // Show edit modal immediately (seamless transition)
                    document.getElementById('task-modal').style.display = 'flex';
                    // Keep body overflow as 'hidden' for seamless transition
                    
                } catch (error) {
                    console.error('Failed to load task details for editing:', error);
                    // Fallback to normal edit modal
                    document.body.style.overflow = 'auto';
                    this.showEditModal(taskId);
                }
            }
            
            showCreateModal() {
                this.currentTask = null;
                
                const modalTitle = document.getElementById('modal-title');
                const saveBtn = document.getElementById('save-task-btn');
                const taskForm = document.getElementById('task-form');
                const taskStatus = document.getElementById('task-status');
                
                if (modalTitle) {
                    modalTitle.textContent = 'Create New Task';
                } else {
                    console.error('modal-title element not found');
                }
                
                if (saveBtn) {
                    saveBtn.textContent = 'Create Task';
                } else {
                    console.error('save-task-btn element not found');
                }
                
                if (taskForm) {
                    taskForm.reset();
                } else {
                    console.error('task-form element not found');
                }
                
                if (taskStatus) {
                    taskStatus.value = 'To Do';
                } else {
                    console.error('task-status element not found');
                }
                
                this.showModal();
            }
            
            async showEditModal(taskId) {
                try {
                    const response = await api.get(`api/tasks.php?action=detail&task_id=${taskId}`);
                    this.currentTask = response.task;
                    
                    document.getElementById('modal-title').textContent = 'Edit Task';
                    document.getElementById('save-task-btn').textContent = 'Update Task';
                    
                    // Populate form
                    document.getElementById('task-id').value = this.currentTask.task_id;
                    document.getElementById('task-title').value = this.currentTask.title;
                    document.getElementById('task-description').value = this.currentTask.description || '';
                    document.getElementById('task-due-date').value = this.currentTask.due_date || '';
                    document.getElementById('task-status').value = this.currentTask.status;
                    
                    // Set assignees
                    document.querySelectorAll('input[name="assignees[]"]').forEach(checkbox => {
                        checkbox.checked = this.currentTask.assignees.some(a => a.user_id == checkbox.value);
                    });
                    
                    this.showModal();
                    
                } catch (error) {
                    console.error('Failed to load task details:', error);
                }
            }
            
            showModal() {
                document.getElementById('task-modal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            
            hideModal() {
                document.getElementById('task-modal').style.display = 'none';
                document.body.style.overflow = 'auto';
                this.currentTask = null;
            }
            
            async handleFormSubmit(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const assignees = Array.from(formData.getAll('assignees[]')).map(id => parseInt(id));
                
                const taskData = {
                    project_id: this.projectId,
                    title: formData.get('title'),
                    description: formData.get('description'),
                    due_date: formData.get('due_date') || null,
                    assignees: assignees
                };
                
                // Apply validation logic
                const title = taskData.title ? taskData.title.trim() : '';
                const dueDate = taskData.due_date;
                
                // Validate title
                if (title === '' || title.length > 100) {
                    showError('Title is required and must be under 100 characters.');
                    return;
                }
                
                // Validate due date
                if (dueDate && !isValidDate(dueDate)) {
                    showError('Please enter a valid due date.');
                    return;
                }
                
                // Update title with trimmed value
                taskData.title = title;
                
                try {
                    if (this.currentTask) {
                        // Update existing task
                        taskData.task_id = this.currentTask.task_id;
                        await api.put('api/tasks.php?action=update', taskData);
                        toastSuccess('Task updated successfully');
                    } else {
                        // Create new task
                        await api.post('api/tasks.php?action=create', taskData);
                        toastSuccess('Task created successfully');
                    }
                    
                    this.hideModal();
                    this.loadTasks();
                    
                } catch (error) {
                    console.error('Failed to save task:', error);
                }
            }
            
            async deleteTask(taskId) {
                if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                    return;
                }
                
                try {
                    await api.delete(`api/tasks.php?action=delete&task_id=${taskId}`);
                    toastSuccess('Task deleted successfully');
                    
                    // Remove from UI
                    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
                    if (taskCard) {
                        taskCard.style.opacity = '0.5';
                        taskCard.style.transform = 'translateX(-10px)';
                        setTimeout(() => {
                            taskCard.remove();
                            this.tasks = this.tasks.filter(t => t.task_id != taskId);
                            if (this.tasks.length === 0) this.showEmptyState();
                        }, 300);
                    }
                    
                    // Reload page to update metrics
                    
                } catch (error) {
                    console.error('Failed to delete task:', error);
                }
            }
            
            async filterTasks() {
                // CS3-13F: Server-side filtering with Juan's enhancements
                try {
                    const statusFilter = document.getElementById('status-filter').value;
                    const assigneeFilter = document.getElementById('assignee-filter').value;
                    const dueStartFilter = document.getElementById('due-start-filter').value;
                    const dueEndFilter = document.getElementById('due-end-filter').value;
                    const searchText = document.getElementById('search-tasks').value;
                    
                    // Build query parameters
                    const params = new URLSearchParams({
                        action: 'filter',
                        project_id: this.projectId
                    });
                    
                    if (statusFilter) params.append('status', statusFilter);
                    if (assigneeFilter) params.append('assignee', assigneeFilter);
                    if (dueStartFilter) params.append('due_start', dueStartFilter);
                    if (dueEndFilter) params.append('due_end', dueEndFilter);
                    if (searchText.trim()) params.append('search', searchText.trim());
                    
                    // Make API call for filtered results
                    const url = `api/tasks.php?${params.toString()}`;
                    const response = await api.get(url);
                    
                    // Update tasks array and re-render
                    this.tasks = response.tasks || [];
                    this.renderTasks();
                    this.updateDashboardMetricsFromAllTasks();
                    
                } catch (error) {
                    console.error('Failed to filter tasks:', error);
                    // Fall back to showing all tasks
                    this.loadTasks();
                }
            }
            
            clearFilters() {
                // Reset all filters
                document.getElementById('status-filter').value = '';
                document.getElementById('assignee-filter').value = '';
                document.getElementById('due-start-filter').value = '';
                document.getElementById('due-end-filter').value = '';
                document.getElementById('search-tasks').value = '';
                
                // Reload all tasks
                this.loadTasks();
            }
            
            showEmptyState() {
                document.getElementById('task-list').style.display = 'none';
                document.getElementById('empty-state').style.display = 'block';
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            startAutoRefresh() {
                // Disabled auto-refresh to prevent performance issues
                // Users can manually refresh if needed
                // TODO: Implement WebSocket or server-sent events for real-time updates
            }
            
            switchView(view) {
                this.currentView = view;
                
                // Update button states
                const buttons = {
                    list: document.getElementById('list-view-btn'),
                    kanban: document.getElementById('kanban-view-btn'),
                    calendar: document.getElementById('calendar-view-btn'),
                    mytasks: document.getElementById('mytasks-view-btn'),
                    team: document.getElementById('team-view-btn')
                };
                
                const views = {
                    list: document.getElementById('task-list-view'),
                    kanban: document.getElementById('kanban-board'),
                    calendar: document.getElementById('calendar-view'),
                    mytasks: document.getElementById('mytasks-view'),
                    team: document.getElementById('team-view')
                };
                
                // Update button states
                Object.keys(buttons).forEach(key => {
                    if (buttons[key]) {
                        buttons[key].classList.toggle('active', key === view);
                    }
                });
                
                // Show/hide appropriate view
                Object.keys(views).forEach(key => {
                    if (views[key]) {
                        views[key].style.display = key === view ? 'block' : 'none';
                    }
                });
                
                // Load appropriate data and render
                this.loadViewData(view);
            }
            
            renderKanbanCard(task) {
                const assignees = task.assignees ? task.assignees.split(',').map(a => {
                    const [username, userId] = a.split(':');
                    return { username, userId };
                }).filter(a => a.username) : [];
                
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'Done';
                
                return `
                    <div class="kanban-task-card" draggable="true" data-task-id="${task.task_id}" data-status="${task.status}">
                        <div class="kanban-task-header">
                            <div class="kanban-task-title" data-task-id="${task.task_id}" style="cursor: pointer;">
                                ${this.escapeHtml(task.title)}
                            </div>
                            <div class="kanban-task-actions">
                                <button class="kanban-action-btn edit-kanban-task" data-task-id="${task.task_id}" 
                                        data-tooltip="Edit task" title="Edit task">✏️</button>
                                <button class="kanban-action-btn delete-kanban-task" data-task-id="${task.task_id}" 
                                        data-tooltip="Delete task" title="Delete task">🗑️</button>
                            </div>
                        </div>
                        
                        <div class="kanban-task-meta">
                            <div class="kanban-task-assignees">
                                ${assignees.length > 0 ? 
                                    assignees.map(a => `<span class="kanban-assignee-badge">${this.escapeHtml(a.username)}</span>`).join('') :
                                    '<span class="kanban-assignee-badge">Unassigned</span>'
                                }
                            </div>
                            
                            ${task.due_date ? `
                                <div class="kanban-task-due ${isOverdue ? 'overdue' : ''}">
                                    📅 ${new Date(task.due_date).toLocaleDateString()}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
            
            setupDragAndDrop() {
                // Remove existing drag event listeners
                this.removeDragEventListeners();
                
                // Store drag event handlers for cleanup
                if (!this.dragEventHandlers) {
                    this.dragEventHandlers = {
                        dragStart: this.handleDragStart.bind(this),
                        dragEnd: this.handleDragEnd.bind(this),
                        dragOver: this.handleDragOver.bind(this),
                        drop: this.handleDrop.bind(this),
                        dragEnter: this.handleDragEnter.bind(this),
                        dragLeave: this.handleDragLeave.bind(this)
                    };
                }
                
                // Add drag event listeners to task cards
                const cards = document.querySelectorAll('.kanban-task-card');
                cards.forEach(card => {
                    card.addEventListener('dragstart', this.dragEventHandlers.dragStart);
                    card.addEventListener('dragend', this.dragEventHandlers.dragEnd);
                });
                
                // Add drop event listeners to columns
                const columns = document.querySelectorAll('.kanban-tasks');
                columns.forEach(column => {
                    column.addEventListener('dragover', this.dragEventHandlers.dragOver);
                    column.addEventListener('drop', this.dragEventHandlers.drop);
                    column.addEventListener('dragenter', this.dragEventHandlers.dragEnter);
                    column.addEventListener('dragleave', this.dragEventHandlers.dragLeave);
                });
            }
            
            removeDragEventListeners() {
                if (this.dragEventHandlers) {
                    const cards = document.querySelectorAll('.kanban-task-card');
                    cards.forEach(card => {
                        card.removeEventListener('dragstart', this.dragEventHandlers.dragStart);
                        card.removeEventListener('dragend', this.dragEventHandlers.dragEnd);
                    });
                    
                    const columns = document.querySelectorAll('.kanban-tasks');
                    columns.forEach(column => {
                        column.removeEventListener('dragover', this.dragEventHandlers.dragOver);
                        column.removeEventListener('drop', this.dragEventHandlers.drop);
                        column.removeEventListener('dragenter', this.dragEventHandlers.dragEnter);
                        column.removeEventListener('dragleave', this.dragEventHandlers.dragLeave);
                    });
                }
            }
            
            handleDragStart(e) {
                const card = e.target.closest('.kanban-task-card');
                if (!card) return;
                
                card.classList.add('dragging');
                // Use simple text format for better compatibility
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    taskId: card.dataset.taskId,
                    currentStatus: card.dataset.status
                }));
                e.dataTransfer.effectAllowed = 'move';
            }
            
            handleDragEnd(e) {
                e.target.classList.remove('dragging');
            }
            
            handleDragOver(e) {
                e.preventDefault();
            }
            
            handleDragEnter(e) {
                if (e.target.classList.contains('kanban-tasks')) {
                    e.target.classList.add('drag-over');
                }
            }
            
            handleDragLeave(e) {
                if (e.target.classList.contains('kanban-tasks')) {
                    e.target.classList.remove('drag-over');
                }
            }
            
            async handleDrop(e) {
                e.preventDefault();
                
                const dropZone = e.target.closest('.kanban-tasks');
                if (!dropZone) {
                    return;
                }
                
                dropZone.classList.remove('drag-over');
                
                const column = dropZone.closest('.kanban-column');
                const newStatus = column.dataset.status;
                
                try {
                    const dragData = JSON.parse(e.dataTransfer.getData('text/plain'));
                    const taskId = dragData.taskId;
                    const currentStatus = dragData.currentStatus;
                    
                    if (currentStatus === newStatus) {
                        return; // No change needed
                    }
                    
                    // OPTIMISTIC UPDATE: Move card immediately in UI
                    const draggedCard = document.querySelector(`.kanban-task-card[data-task-id="${taskId}"]`);
                    if (draggedCard) {
                        // Store original position for potential rollback
                        const originalColumn = draggedCard.closest('.kanban-tasks');
                        const originalNextSibling = draggedCard.nextElementSibling;
                        
                        // Update local task data first
                        const task = this.updateTaskInArray(taskId, newStatus);
                        if (task) {
                            const oldStatus = currentStatus;
                            
                            // Move card to new column
                            dropZone.appendChild(draggedCard);
                            draggedCard.dataset.status = newStatus;
                            
                            // Update counts and metrics immediately
                            this.updateColumnCounts();
                            
                            try {
                                // Make backend call
                                await this.updateTaskStatusBackend(taskId, newStatus);
                                toastSuccess('Task status updated successfully');
                            } catch (error) {
                                // ROLLBACK on failure
                                this.revertTaskInArray(taskId, oldStatus);
                                draggedCard.dataset.status = oldStatus;
                                
                                // Move card back to original position
                                if (originalNextSibling) {
                                    originalColumn.insertBefore(draggedCard, originalNextSibling);
                                } else {
                                    originalColumn.appendChild(draggedCard);
                                }
                                
                                // Restore counts and metrics
                                this.updateColumnCounts();
                                
                                toastError('Failed to update task status - changes reverted');
                            }
                        }
                    }
                    
                } catch (error) {
                    toastError('Failed to process task move');
                }
            }
            
            updateColumnCounts() {
                // Update task counts for each column
                const statuses = ['To Do', 'In Progress', 'Done'];
                statuses.forEach(status => {
                    const columnId = status === 'To Do' ? 'kanban-todo' : 
                                   status === 'In Progress' ? 'kanban-progress' : 'kanban-done';
                    const countId = status === 'To Do' ? 'todo-count' : 
                                  status === 'In Progress' ? 'progress-count' : 'done-count';
                    
                    const column = document.getElementById(columnId);
                    const countEl = document.getElementById(countId);
                    
                    if (column && countEl) {
                        const taskCount = column.querySelectorAll('.kanban-task-card').length;
                        countEl.textContent = taskCount;
                    }
                });
                
                // Update dashboard metrics
                this.updateDashboardMetricsFromAllTasks();
            }
            
            updateDashboardMetrics() {
                // Throttle dashboard updates to prevent excessive DOM manipulation
                if (this.dashboardUpdateTimeout) {
                    clearTimeout(this.dashboardUpdateTimeout);
                }
                
                this.dashboardUpdateTimeout = setTimeout(() => {
                    this.performDashboardUpdate();
                }, 100); // 100ms throttle
            }
            
            async updateDashboardMetricsFromAllTasks() {
                // Always fetch complete project tasks for dashboard metrics, ignoring current view/filters
                try {
                    const url = `api/tasks.php?action=project&project_id=${this.projectId}`;
                    const response = await api.get(url);
                    const allTasks = response.tasks || [];
                    
                    // Calculate metrics from all project tasks
                    const metrics = this.calculateTaskMetricsFromArray(allTasks);
                    
                    // Update dashboard display
                    this.updateDashboardDisplay(metrics);
                    
                } catch (error) {
                    console.error('Failed to update dashboard metrics:', error);
                }
            }
            
            performDashboardUpdate() {
                // Calculate metrics from current tasks array
                const metrics = this.calculateTaskMetrics();
                
                // Update metric cards (batch DOM updates)
                const elements = {
                    total: document.getElementById('total-tasks-count'),
                    completed: document.getElementById('completed-tasks-count'),
                    inProgress: document.getElementById('in-progress-tasks-count'),
                    todo: document.getElementById('todo-tasks-count'),
                    percentage: document.getElementById('completion-percentage'),
                    progressFill: document.getElementById('progress-fill'),
                    progressDetails: document.getElementById('progress-details')
                };
                
                // Batch DOM updates to reduce reflow
                requestAnimationFrame(() => {
                    if (elements.total) elements.total.textContent = metrics.total;
                    if (elements.completed) elements.completed.textContent = metrics.completed;
                    if (elements.inProgress) elements.inProgress.textContent = metrics.inProgress;
                    if (elements.todo) elements.todo.textContent = metrics.todo;
                    
                    if (elements.percentage) elements.percentage.textContent = metrics.percentage + '%';
                    if (elements.progressFill) {
                        elements.progressFill.style.width = metrics.percentage + '%';
                        elements.progressFill.classList.toggle('zero', metrics.percentage === 0);
                    }
                    if (elements.progressDetails) {
                        if (metrics.total > 0) {
                            elements.progressDetails.textContent = `${metrics.completed} of ${metrics.total} tasks completed`;
                        } else {
                            elements.progressDetails.textContent = 'No tasks created yet';
                        }
                    }
                });
            }
            
            scheduleViewUpdate() {
                // Throttle view updates to prevent excessive rendering
                if (this.viewUpdateTimeout) {
                    clearTimeout(this.viewUpdateTimeout);
                }
                
                this.viewUpdateTimeout = setTimeout(() => {
                    if (this.currentView === 'kanban') {
                        this.renderKanban();
                    } else {
                        this.updateDashboardMetricsFromAllTasks();
                    }
                }, 50); // 50ms throttle for view updates
            }
            
            calculateTaskMetrics() {
                const total = this.tasks.length;
                const completed = this.tasks.filter(task => task.status === 'Done').length;
                const inProgress = this.tasks.filter(task => task.status === 'In Progress').length;
                const todo = this.tasks.filter(task => task.status === 'To Do').length;
                const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
                
                return {
                    total,
                    completed,
                    inProgress,
                    todo,
                    percentage
                };
            }
            
            calculateTaskMetricsFromArray(tasks) {
                const total = tasks.length;
                const completed = tasks.filter(task => task.status === 'Done').length;
                const inProgress = tasks.filter(task => task.status === 'In Progress').length;
                const todo = tasks.filter(task => task.status === 'To Do').length;
                const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
                
                return {
                    total,
                    completed,
                    inProgress,
                    todo,
                    percentage
                };
            }
            
            updateDashboardDisplay(metrics) {
                // Update metric cards (batch DOM updates)
                const elements = {
                    total: document.getElementById('total-tasks-count'),
                    completed: document.getElementById('completed-tasks-count'),
                    inProgress: document.getElementById('in-progress-tasks-count'),
                    todo: document.getElementById('todo-tasks-count'),
                    percentage: document.getElementById('completion-percentage'),
                    progressFill: document.getElementById('progress-fill'),
                    progressDetails: document.getElementById('progress-details')
                };
                
                // Batch DOM updates to reduce reflow
                requestAnimationFrame(() => {
                    if (elements.total) elements.total.textContent = metrics.total;
                    if (elements.completed) elements.completed.textContent = metrics.completed;
                    if (elements.inProgress) elements.inProgress.textContent = metrics.inProgress;
                    if (elements.todo) elements.todo.textContent = metrics.todo;
                    
                    if (elements.percentage) elements.percentage.textContent = metrics.percentage + '%';
                    
                    if (elements.progressFill) {
                        elements.progressFill.style.width = metrics.percentage + '%';
                    }
                    
                    if (elements.progressDetails) {
                        if (metrics.total === 0) {
                            elements.progressDetails.textContent = 'No tasks yet';
                        } else {
                            elements.progressDetails.textContent = `${metrics.completed} of ${metrics.total} tasks completed`;
                        }
                    }
                });
            }
            
            async updateTaskStatusBackend(taskId, status) {
                // Make the actual API call without UI updates
                const response = await api.put('api/tasks.php?action=status', {
                    task_id: parseInt(taskId),
                    status: status
                });
                return response;
            }
            
            updateTaskInArray(taskId, newStatus) {
                // Update the task in the local tasks array
                const task = this.tasks.find(t => t.task_id == taskId);
                if (task) {
                    task.status = newStatus;
                }
                return task;
            }
            
            revertTaskInArray(taskId, oldStatus) {
                // Revert the task status in the local tasks array
                const task = this.tasks.find(t => t.task_id == taskId);
                if (task) {
                    task.status = oldStatus;
                }
                return task;
            }
            
            bindKanbanEvents() {
                // Click to view task details - now works on entire card
                document.querySelectorAll('.kanban-task-card').forEach(card => {
                    let isDragging = false;
                    let dragStartTime = 0;
                    let mouseDownTime = 0;
                    
                    // Handle mouse down - prepare for potential drag
                    card.addEventListener('mousedown', (e) => {
                        mouseDownTime = Date.now();
                        // Add drag-ready class after a short delay if still holding
                        setTimeout(() => {
                            if (Date.now() - mouseDownTime > 150) {
                                card.classList.add('drag-ready');
                            }
                        }, 150);
                    });
                    
                    // Handle mouse up - remove drag-ready state
                    card.addEventListener('mouseup', (e) => {
                        card.classList.remove('drag-ready');
                    });
                    
                    // Handle mouse leave - remove drag-ready state
                    card.addEventListener('mouseleave', (e) => {
                        card.classList.remove('drag-ready');
                    });
                    
                    // Track drag start
                    card.addEventListener('dragstart', (e) => {
                        isDragging = true;
                        dragStartTime = Date.now();
                        card.classList.remove('drag-ready');
                    });
                    
                    // Track drag end
                    card.addEventListener('dragend', (e) => {
                        card.classList.remove('drag-ready');
                        // Reset dragging flag after a short delay to prevent click events
                        setTimeout(() => {
                            isDragging = false;
                        }, 100);
                    });
                    
                    // Handle clicks on entire card
                    card.addEventListener('click', (e) => {
                        // Don't open modal if we're dragging or just finished dragging
                        if (isDragging || (Date.now() - dragStartTime) < 200) {
                            return;
                        }
                        
                        // Don't open modal if clicking action buttons
                        if (e.target.matches('.kanban-action-btn, .edit-kanban-task, .delete-kanban-task')) {
                            return;
                        }
                        
                        // Don't open modal if this was a quick click (likely trying to drag)
                        const clickDuration = Date.now() - mouseDownTime;
                        if (clickDuration > 300) {
                            return; // Was holding too long, probably trying to drag
                        }
                        
                        const taskId = card.getAttribute('data-task-id');
                        this.showTaskDetailModal(taskId);
                    });
                });
                
                // Kanban card edit buttons
                document.querySelectorAll('.edit-kanban-task').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent triggering card click
                        const taskId = e.target.getAttribute('data-task-id');
                        this.showEditModal(taskId);
                    });
                });
                
                // Kanban card delete buttons
                document.querySelectorAll('.delete-kanban-task').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent triggering card click
                        const taskId = e.target.getAttribute('data-task-id');
                        this.deleteTask(taskId);
                    });
                });
            }
            
            // Comment Management Methods - CS3-14C, CS3-14D
            async loadCommentCounts() {
                for (const task of this.tasks) {
                    try {
                        const response = await api.get(`api/comments.php?action=count&task_id=${task.task_id}`);
                        const countEl = document.querySelector(`[data-task-id="${task.task_id}"] .comment-count`);
                        if (countEl) {
                            countEl.textContent = response.data?.count || '0';
                        }
                    } catch (error) {
                        console.error(`Failed to load comment count for task ${task.task_id}:`, error);
                    }
                }
            }
            
            async showTaskDetailModal(taskId) {
                try {
                    // Load task details
                    const url = `api/tasks.php?action=detail&task_id=${taskId}`;
                    const taskResponse = await api.get(url);
                    const task = taskResponse.task;
                    
                    // Populate task details
                    document.getElementById('detail-modal-title').textContent = `Task: ${task.title}`;
                    document.getElementById('task-detail-content').innerHTML = this.renderTaskDetails(task);
                    document.getElementById('comment-task-id').value = taskId;
                    
                    // Load comments
                    this.loadComments(taskId);
                    
                    // Bind modal action buttons
                    this.bindModalActions(task);
                    
                    // Show modal
                    document.getElementById('task-detail-modal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    
                } catch (error) {
                    console.error('Failed to load task details:', error);
                    toastError('Failed to load task details');
                }
            }
            
            bindModalActions(task) {
                // Remove existing event listeners
                const editBtn = document.getElementById('edit-task-modal-btn');
                const deleteBtn = document.getElementById('delete-task-modal-btn');
                
                if (editBtn) {
                    // Clone and replace to remove old event listeners
                    const newEditBtn = editBtn.cloneNode(true);
                    editBtn.parentNode.replaceChild(newEditBtn, editBtn);
                    
                    newEditBtn.addEventListener('click', () => {
                        // Seamless transition from detail modal to edit modal
                        this.showEditModalFromDetail(task.task_id);
                    });
                }
                
                if (deleteBtn) {
                    // Clone and replace to remove old event listeners
                    const newDeleteBtn = deleteBtn.cloneNode(true);
                    deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
                    
                    newDeleteBtn.addEventListener('click', () => {
                        // Hide detail modal first, then delete
                        document.getElementById('task-detail-modal').style.display = 'none';
                        document.body.style.overflow = 'auto';
                        this.deleteTask(task.task_id);
                    });
                }
            }
            
            renderTaskDetails(task) {
                const assignees = task.assignees || [];
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'Done';
                
                return `
                    <div class="task-detail-info">
                        <div class="task-detail-header">
                            <div class="task-status-badge status-${task.status.toLowerCase().replace(' ', '-')}">
                                ${task.status}
                            </div>
                            ${task.due_date ? `
                                <div class="task-due-date ${isOverdue ? 'overdue' : ''}">
                                    📅 Due: ${new Date(task.due_date).toLocaleDateString()}
                                </div>
                            ` : ''}
                        </div>
                        
                        ${task.description ? `
                            <div class="task-detail-description">
                                <h6>Description</h6>
                                <p>${this.escapeHtml(task.description)}</p>
                            </div>
                        ` : ''}
                        
                        <div class="task-detail-meta">
                            <div class="task-detail-assignees">
                                <h6>Assigned To</h6>
                                ${assignees.length > 0 ? 
                                    assignees.map(a => `<span class="assignee-badge">${this.escapeHtml(a.username)}</span>`).join('') :
                                    '<span class="unassigned">Unassigned</span>'
                                }
                            </div>
                            
                            <div class="task-detail-created">
                                <h6>Created</h6>
                                <small>
                                    ${new Date(task.created_at).toLocaleDateString()}
                                    ${task.assigned_by_username ? `by ${this.escapeHtml(task.assigned_by_username)}` : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            async loadComments(taskId) {
                try {
                    const url = `api/comments.php?action=task&task_id=${taskId}`;
                    const response = await api.get(url);
                    const comments = response.data?.comments || [];
                    
                    if (comments.length === 0) {
                        // No comments found
                    }
                    
                    const commentsList = document.getElementById('comments-list');
                    const emptyComments = document.getElementById('empty-comments');
                    
                    if (comments.length === 0) {
                        commentsList.style.display = 'none';
                        emptyComments.style.display = 'block';
                    } else {
                        emptyComments.style.display = 'none';
                        commentsList.style.display = 'block';
                        commentsList.innerHTML = comments.map(comment => this.renderComment(comment)).join('');
                        this.bindCommentEvents();
                        this.scrollToNewestComment();
                    }
                    
                } catch (error) {
                    console.error('Failed to load comments:', error);
                    document.getElementById('comments-list').innerHTML = '<div class="error-message">Failed to load comments</div>';
                }
            }
            
            renderComment(comment) {
                const currentUserId = <?php echo isset($currentUser['user_id']) ? json_encode($currentUser['user_id']) : 'null'; ?>;
                const isOwner = comment.user_id == currentUserId;
                
                
                return `
                    <div class="comment" data-comment-id="${comment.comment_id}">
                        <div class="comment-header">
                            <div class="comment-author">
                                <strong>${this.escapeHtml(comment.name || comment.username)}</strong>
                                <small class="comment-time">
                                    ${comment.updated_at && comment.updated_at !== comment.timestamp ? 
                                        `<div class="timestamp-info">
                                            <span class="original-time" title="Originally posted: ${comment.timestamp}">
                                                Posted ${this.formatRelativeTime(comment.timestamp)}
                                            </span>
                                            <span class="edited-time" title="Last edited: ${comment.updated_at}">
                                                • Edited ${this.formatRelativeTime(comment.updated_at)}
                                            </span>
                                        </div>` : 
                                        `<span title="${comment.timestamp}">${this.formatRelativeTime(comment.timestamp)}</span>`
                                    }
                                </small>
                            </div>
                            ${isOwner ? `
                                <div class="comment-actions">
                                    <button class="btn-link edit-comment-btn" data-comment-id="${comment.comment_id}" 
                                            data-tooltip="Edit comment">✏️</button>
                                    <button class="btn-link delete-comment-btn" data-comment-id="${comment.comment_id}" 
                                            data-tooltip="Delete comment">🗑️</button>
                                </div>
                            ` : ''}
                        </div>
                        <div class="comment-content">
                            ${this.formatCommentContent(comment.content)}
                        </div>
                    </div>
                `;
            }
            
            formatCommentContent(content) {
                // Basic formatting: convert line breaks to <br> and escape HTML
                return this.escapeHtml(content).replace(/\n/g, '<br>');
            }
            
            formatRelativeTime(timestamp) {
                const date = new Date(timestamp);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);
                
                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffHours < 24) return `${diffHours}h ago`;
                if (diffDays < 7) return `${diffDays}d ago`;
                
                return date.toLocaleDateString();
            }
            
            bindCommentEvents() {
                // Edit comment buttons
                document.querySelectorAll('.edit-comment-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const commentId = e.target.getAttribute('data-comment-id');
                        this.showEditCommentModal(commentId);
                    });
                });
                
                // Delete comment buttons
                document.querySelectorAll('.delete-comment-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const commentId = e.target.getAttribute('data-comment-id');
                        this.deleteComment(commentId);
                    });
                });
            }
            
            async showEditCommentModal(commentId) {
                try {
                    const url = `api/comments.php?action=detail&comment_id=${commentId}`;
                    const response = await api.get(url);
                    const comment = response.data?.comment;
                    
                    document.getElementById('edit-comment-id').value = commentId;
                    document.getElementById('edit-comment-content').value = comment.content;
                    
                    // Update character counter
                    this.updateCharCounter('edit-comment-content');
                    
                    document.getElementById('edit-comment-modal').style.display = 'flex';
                    
                } catch (error) {
                    console.error('Failed to load comment for editing:', error);
                    toastError('Failed to load comment');
                }
            }
            
            async deleteComment(commentId) {
                if (!confirm('Are you sure you want to delete this comment?')) {
                    return;
                }
                
                try {
                    await api.delete(`api/comments.php?action=delete&comment_id=${commentId}`);
                    toastSuccess('Comment deleted successfully');
                    
                    // Remove comment from UI
                    const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
                    if (commentEl) {
                        commentEl.style.opacity = '0.5';
                        setTimeout(() => {
                            commentEl.remove();
                            
                            // Check if this was the last comment
                            const remainingComments = document.querySelectorAll('.comment');
                            if (remainingComments.length === 0) {
                                document.getElementById('comments-list').style.display = 'none';
                                document.getElementById('empty-comments').style.display = 'block';
                            }
                        }, 300);
                    }
                    
                    // Update comment count
                    const taskId = document.getElementById('comment-task-id').value;
                    this.updateCommentCount(taskId);
                    
                } catch (error) {
                    console.error('Failed to delete comment:', error);
                }
            }
            
            async updateCommentCount(taskId) {
                try {
                    const response = await api.get(`api/comments.php?action=count&task_id=${taskId}`);
                    const countEl = document.querySelector(`[data-task-id="${taskId}"] .comment-count`);
                    if (countEl) {
                        countEl.textContent = response.data?.count || '0';
                    }
                } catch (error) {
                    console.error('Failed to update comment count:', error);
                }
            }
            
            updateCharCounter(textareaId) {
                const textarea = document.getElementById(textareaId);
                const counter = textarea.parentNode.querySelector('.char-counter');
                if (counter && textarea) {
                    const length = textarea.value.length;
                    const maxLength = textarea.getAttribute('maxlength') || 1000;
                    counter.textContent = `${length}/${maxLength}`;
                    
                    if (length > maxLength * 0.9) {
                        counter.style.color = '#dc3545';
                    } else if (length > maxLength * 0.7) {
                        counter.style.color = '#ffc107';
                    } else {
                        counter.style.color = '#666';
                    }
                }
            }
            
            scrollToNewestComment() {
                const commentsList = document.getElementById('comments-list');
                if (commentsList && commentsList.children.length > 0) {
                    // Get the last comment (newest)
                    const lastComment = commentsList.lastElementChild;
                    
                    // Smooth scroll to the newest comment
                    lastComment.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'nearest',
                        inline: 'nearest'
                    });
                    
                    // Alternative: scroll the comments container to bottom
                    // This ensures the newest comment is visible even if scrollIntoView doesn't work perfectly
                    setTimeout(() => {
                        commentsList.scrollTop = commentsList.scrollHeight;
                    }, 100);
                    
                    // Add a subtle highlight effect to the newest comment
                    lastComment.style.transition = 'background-color 0.3s ease';
                    lastComment.style.backgroundColor = '#f0f8ff';
                    setTimeout(() => {
                        lastComment.style.backgroundColor = '';
                    }, 2000);
                }
            }
        }
        
        // Comment form submission - CS3-14C
        document.getElementById('comment-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const taskId = formData.get('task_id');
            const content = formData.get('content');
            
            if (!content.trim()) {
                toastError('Comment cannot be empty');
                return;
            }
            
            try {
                const response = await api.post('api/comments.php?action=create', {
                    task_id: parseInt(taskId),
                    content: content.trim()
                });
                
                toastSuccess('Comment posted successfully');
                
                // Clear form
                document.getElementById('comment-content').value = '';
                taskManager.updateCharCounter('comment-content');
                
                // Reload comments
                taskManager.loadComments(taskId);
                taskManager.updateCommentCount(taskId);
                
            } catch (error) {
                console.error('Failed to post comment:', error);
            }
        });
        
        // Edit comment form submission
        document.getElementById('edit-comment-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const commentId = formData.get('comment_id');
            const content = formData.get('content');
            
            if (!content.trim()) {
                toastError('Comment cannot be empty');
                return;
            }
            
            try {
                const response = await api.put('api/comments.php?action=update', {
                    comment_id: parseInt(commentId),
                    content: content.trim()
                });
                
                toastSuccess('Comment updated successfully');
                
                // Hide edit modal
                hideModal('edit-comment-modal');
                
                // Reload comments
                const taskId = document.getElementById('comment-task-id').value;
                taskManager.loadComments(taskId);
                
            } catch (error) {
                console.error('Failed to update comment:', error);
            }
        });
        
        // Character counter for comment textareas
        document.getElementById('comment-content').addEventListener('input', function() {
            taskManager.updateCharCounter('comment-content');
        });
        
        document.getElementById('edit-comment-content').addEventListener('input', function() {
            taskManager.updateCharCounter('edit-comment-content');
        });
        
        // Modal close handlers
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal');
                if (modalId) {
                    hideModal(modalId);
                } else {
                    // Close parent modal
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                }
            });
        });
        
        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Global modal helper function
        window.hideModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };
        
        // Initialize task manager when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TaskManager
            if (document.getElementById('create-task-btn')) {
                window.taskManager = new TaskManager(<?php echo $project_id; ?>);
            }
            
            // Direct fallback event listener for create task button
            const createTaskBtn = document.getElementById('create-task-btn');
            if (createTaskBtn && !createTaskBtn.hasAttribute('data-listener-added')) {
                createTaskBtn.addEventListener('click', function() {
                    showTaskModal();
                });
                createTaskBtn.setAttribute('data-listener-added', 'true');
            }
        });
    </script>
</body>
</html>