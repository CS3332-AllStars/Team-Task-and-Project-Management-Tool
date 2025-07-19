<?php
// CS3332 AllStars Team Task & Project Management System
// Project View Page - FR-9, FR-11

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
            
            <!-- Task Filters -->
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
                
                <input type="text" id="search-tasks" placeholder="Search tasks..." class="search-input">
            </div>
            
            <!-- Task List -->
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
                    <button type="button" class="close-modal" data-modal="task-detail-modal" data-tooltip="Close modal">&times;</button>
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
                    } else {
                        // Fallback: reload the page
                        window.location.reload();
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
        
        // Add form submit event listener when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const taskForm = document.getElementById('task-form');
            const cancelBtn = document.getElementById('cancel-task-btn');
            const closeBtn = document.querySelector('.close-modal');
            
            if (taskForm) {
                taskForm.addEventListener('submit', handleTaskSubmit);
            }
            
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
        
        // Task Management JavaScript - CS3-13B, CS3-13C, CS3-13D
        
        class TaskManager {
            constructor(projectId) {
                this.projectId = projectId;
                this.tasks = [];
                this.currentTask = null;
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.loadTasks();
            }
            
            bindEvents() {
                // Modal controls with error handling
                const createBtn = document.getElementById('create-task-btn');
                const createFirstBtn = document.getElementById('create-first-task-btn');
                const cancelBtn = document.getElementById('cancel-task-btn');
                const closeBtn = document.querySelector('.close-modal');
                const taskForm = document.getElementById('task-form');
                const statusFilter = document.getElementById('status-filter');
                const assigneeFilter = document.getElementById('assignee-filter');
                const searchInput = document.getElementById('search-tasks');
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
                
                if (searchInput) {
                    searchInput.addEventListener('input', () => this.filterTasks());
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
                } catch (error) {
                    console.error('Failed to load tasks:', error);
                    this.showEmptyState();
                }
            }
            
            renderTasks() {
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
                // Status change
                document.querySelectorAll('.status-select').forEach(select => {
                    select.addEventListener('change', (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        const newStatus = e.target.value;
                        this.updateTaskStatus(taskId, newStatus);
                    });
                });
                
                // Edit task
                document.querySelectorAll('.edit-task-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        this.showEditModal(taskId);
                    });
                });
                
                // Delete task
                document.querySelectorAll('.delete-task-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        this.deleteTask(taskId);
                    });
                });
                
                // Task title click - show detail modal
                document.querySelectorAll('.task-title.clickable').forEach(title => {
                    title.addEventListener('click', (e) => {
                        const taskId = e.target.getAttribute('data-task-id');
                        this.showTaskDetailModal(taskId);
                    });
                });
                
                // Comment count button
                document.querySelectorAll('.comment-count-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const taskId = e.currentTarget.getAttribute('data-task-id');
                        this.showTaskDetailModal(taskId);
                    });
                });
                
                // Load comment counts
                this.loadCommentCounts();
            }
            
            async updateTaskStatus(taskId, status) {
                try {
                    await api.put('api/tasks.php?action=status', {
                        task_id: parseInt(taskId),
                        status: status
                    });
                    
                    // Update local task data
                    const task = this.tasks.find(t => t.task_id == taskId);
                    if (task) {
                        task.status = status;
                    }
                    
                    toastSuccess('Task status updated successfully');
                    
                    // Reload page to update metrics
                    setTimeout(() => window.location.reload(), 1000);
                    
                } catch (error) {
                    // Revert the select value
                    const select = document.querySelector(`[data-task-id="${taskId}"]`);
                    if (select) {
                        const task = this.tasks.find(t => t.task_id == taskId);
                        if (task) select.value = task.status;
                    }
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
                    
                    // Reload page to update metrics
                    setTimeout(() => window.location.reload(), 1000);
                    
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
                    setTimeout(() => window.location.reload(), 1500);
                    
                } catch (error) {
                    console.error('Failed to delete task:', error);
                }
            }
            
            filterTasks() {
                const statusFilter = document.getElementById('status-filter').value;
                const assigneeFilter = document.getElementById('assignee-filter').value;
                const searchText = document.getElementById('search-tasks').value.toLowerCase();
                
                const taskCards = document.querySelectorAll('.task-card');
                let visibleCount = 0;
                
                taskCards.forEach(card => {
                    const taskId = card.getAttribute('data-task-id');
                    const task = this.tasks.find(t => t.task_id == taskId);
                    
                    if (!task) return;
                    
                    let visible = true;
                    
                    // Status filter
                    if (statusFilter && task.status !== statusFilter) {
                        visible = false;
                    }
                    
                    // Assignee filter
                    if (assigneeFilter) {
                        if (assigneeFilter === 'unassigned') {
                            if (task.assignees && task.assignees.trim()) {
                                visible = false;
                            }
                        } else {
                            if (!task.assignees || !task.assignees.includes(`:${assigneeFilter}`)) {
                                visible = false;
                            }
                        }
                    }
                    
                    // Search filter
                    if (searchText) {
                        const searchableText = `${task.title} ${task.description || ''}`.toLowerCase();
                        if (!searchableText.includes(searchText)) {
                            visible = false;
                        }
                    }
                    
                    card.style.display = visible ? 'block' : 'none';
                    if (visible) visibleCount++;
                });
                
                // Show empty state if no tasks match filters
                document.getElementById('empty-state').style.display = visibleCount === 0 ? 'block' : 'none';
                document.getElementById('task-list').style.display = visibleCount === 0 ? 'none' : 'block';
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
                    
                    // Show modal
                    document.getElementById('task-detail-modal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    
                } catch (error) {
                    console.error('Failed to load task details:', error);
                    toastError('Failed to load task details');
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
        
        // Initialize task manager
        try {
            window.taskManager = new TaskManager(<?php echo $project_id; ?>);
        } catch (error) {
            // TaskManager initialization failed - will use fallback
        }
        
        
        
        // Separate initialization for task management to ensure it works
        document.addEventListener('DOMContentLoaded', function() {
            // Backup initialization in case of timing issues
            if (!window.taskManager && document.getElementById('create-task-btn')) {
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