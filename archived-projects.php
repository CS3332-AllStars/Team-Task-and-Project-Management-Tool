<?php
// CS3332 AllStars Team Task & Project Management System
// Archived Projects View - CS3-12F Extension

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

// Auto-migration check
require_once 'src/utils/AutoMigration.php';
$pending_migrations = AutoMigration::hasPendingMigrations($mysqli);

// Get user's archived projects
$archivedProjects = [];
if (isset($_SESSION['user_id'])) {
    // Check if is_archived column exists
    $column_check = $mysqli->query("
        SELECT COUNT(*) as column_exists
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'ttpm_system' 
        AND TABLE_NAME = 'projects' 
        AND COLUMN_NAME = 'is_archived'
    ");
    $has_archived_column = $column_check->fetch_assoc()['column_exists'] > 0;

    if ($has_archived_column) {
        $stmt = $mysqli->prepare("
            SELECT 
                p.project_id, 
                p.title, 
                p.description, 
                pm.role,
                p.created_date,
                p.updated_at,
                (SELECT COUNT(*) FROM project_memberships pm2 WHERE pm2.project_id = p.project_id) as team_size,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id) as total_tasks,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status = 'Done') as completed_tasks
            FROM projects p 
            JOIN project_memberships pm ON p.project_id = pm.project_id 
            WHERE pm.user_id = ? AND p.is_archived = TRUE
            ORDER BY p.updated_at DESC
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Calculate progress percentage
            $row['progress_percent'] = $row['total_tasks'] > 0 
                ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) 
                : 0;
            $archivedProjects[] = $row;
        }
        $stmt->close();
    }
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
    <title>Archived Projects - TTPM</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/project.css">
</head>
<body>
    <div class="container dashboard">
        <?php if ($pending_migrations): ?>
            <?php AutoMigration::showMigrationBanner(); ?>
        <?php endif; ?>
        
        <?php require_once 'includes/layouts/header.php'; ?>
        
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <h1>📦 Archived Projects</h1>
                    <p class="header-subtitle">View and manage your archived projects</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-outline-primary">← Back to Dashboard</a>
                </div>
            </div>
        </div>

        <div class="content-section">
            <?php if (empty($archivedProjects)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3>No Archived Projects</h3>
                    <p>You don't have any archived projects yet.</p>
                    <p>When you archive projects from your dashboard, they'll appear here.</p>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="projects-header">
                    <div class="projects-summary">
                        <span class="summary-count"><?php echo count($archivedProjects); ?></span>
                        <span class="summary-label">Archived Project<?php echo count($archivedProjects) !== 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="projects-actions">
                        <div class="view-toggle">
                            <button id="grid-view-btn" class="view-btn active" data-view="grid" title="Grid View">⊞</button>
                            <button id="list-view-btn" class="view-btn" data-view="list" title="List View">☰</button>
                        </div>
                    </div>
                </div>

                <div id="grid-view" class="projects-grid">
                    <?php foreach ($archivedProjects as $project): ?>
                        <div class="project-card archived-project-card">
                            <div class="project-card-header">
                                <div class="project-info">
                                    <h3 class="project-title">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </h3>
                                    <div class="project-meta">
                                        <span class="role-badge role-<?php echo $project['role']; ?>">
                                            <?php echo ucfirst($project['role']); ?>
                                        </span>
                                        <span class="archived-badge">Archived</span>
                                    </div>
                                </div>
                                
                                <?php if ($project['role'] === 'admin'): ?>
                                <div class="project-actions">
                                    <button class="btn btn-sm btn-success unarchive-btn" 
                                            data-project-id="<?php echo $project['project_id']; ?>"
                                            data-project-title="<?php echo htmlspecialchars($project['title']); ?>"
                                            title="Unarchive Project">
                                        📤 Unarchive
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            data-project-id="<?php echo $project['project_id']; ?>"
                                            data-project-title="<?php echo htmlspecialchars($project['title']); ?>"
                                            title="Permanently Delete Project">
                                        🗑️ Delete
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="project-description">
                                <?php echo htmlspecialchars($project['description'] ?? 'No description provided'); ?>
                            </div>
                            
                            <div class="project-progress">
                                <div class="progress-label">
                                    <span>Final Progress</span>
                                    <span><?php echo $project['progress_percent']; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $project['progress_percent']; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="project-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $project['team_size']; ?></span>
                                    <div class="stat-label">Team Size</div>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $project['total_tasks']; ?></span>
                                    <div class="stat-label">Total Tasks</div>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $project['completed_tasks']; ?></span>
                                    <div class="stat-label">Completed</div>
                                </div>
                            </div>
                            
                            <div class="project-footer">
                                <div class="project-dates">
                                    <small class="text-muted">
                                        Created: <?php echo date('M j, Y', strtotime($project['created_date'])); ?>
                                        <?php if ($project['updated_at']): ?>
                                            <br>Archived: <?php echo date('M j, Y', strtotime($project['updated_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="list-view" class="projects-list hidden">
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Role</th>
                                <th>Progress</th>
                                <th>Team</th>
                                <th>Tasks</th>
                                <th>Archived</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedProjects as $project): ?>
                                <tr class="project-row">
                                    <td>
                                        <div class="project-info">
                                            <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                            <div class="project-description-small">
                                                <?php echo htmlspecialchars(substr($project['description'] ?? 'No description', 0, 60)); ?>
                                                <?php if (strlen($project['description'] ?? '') > 60): ?>...<?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?php echo $project['role']; ?>">
                                            <?php echo ucfirst($project['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress-small">
                                            <div class="progress-bar-small">
                                                <div class="progress-fill-small" style="width: <?php echo $project['progress_percent']; ?>%"></div>
                                            </div>
                                            <span class="progress-text"><?php echo $project['progress_percent']; ?>%</span>
                                        </div>
                                    </td>
                                    <td><?php echo $project['team_size']; ?></td>
                                    <td><?php echo $project['completed_tasks']; ?>/<?php echo $project['total_tasks']; ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $project['updated_at'] ? date('M j, Y', strtotime($project['updated_at'])) : 'Unknown'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($project['role'] === 'admin'): ?>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-success unarchive-btn" 
                                                        data-project-id="<?php echo $project['project_id']; ?>"
                                                        data-project-title="<?php echo htmlspecialchars($project['title']); ?>">
                                                    📤 Unarchive
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" 
                                                        data-project-id="<?php echo $project['project_id']; ?>"
                                                        data-project-title="<?php echo htmlspecialchars($project['title']); ?>">
                                                    🗑️ Delete
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Member</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/toast.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View toggle functionality
            const gridViewBtn = document.getElementById('grid-view-btn');
            const listViewBtn = document.getElementById('list-view-btn');
            const gridView = document.getElementById('grid-view');
            const listView = document.getElementById('list-view');

            if (gridViewBtn && listViewBtn) {
                gridViewBtn.addEventListener('click', function() {
                    gridViewBtn.classList.add('active');
                    listViewBtn.classList.remove('active');
                    gridView.classList.remove('hidden');
                    listView.classList.add('hidden');
                });

                listViewBtn.addEventListener('click', function() {
                    listViewBtn.classList.add('active');
                    gridViewBtn.classList.remove('active');
                    listView.classList.remove('hidden');
                    gridView.classList.add('hidden');
                });
            }

            // Unarchive functionality
            document.querySelectorAll('.unarchive-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    const projectId = this.getAttribute('data-project-id');
                    const projectTitle = this.getAttribute('data-project-title');
                    
                    if (confirm(`Are you sure you want to unarchive "${projectTitle}"?\n\nThis will restore the project to your active projects list.`)) {
                        try {
                            const response = await fetch('api/project-actions.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'unarchive_project',
                                    project_id: projectId,
                                    csrf_token: window.csrfToken || ''
                                })
                            });

                            const result = await response.json();
                            
                            if (result.success) {
                                toastSuccess(result.message);
                                // Remove the project card from the view
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                toastError(result.message);
                            }
                        } catch (error) {
                            console.error('Unarchive error:', error);
                            toastError('Failed to unarchive project');
                        }
                    }
                });
            });

            // Delete functionality
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', async function() {
                    const projectId = this.getAttribute('data-project-id');
                    const projectTitle = this.getAttribute('data-project-title');
                    
                    // First confirmation
                    if (confirm(`⚠️ WARNING: This action cannot be undone!\n\nAre you sure you want to permanently DELETE the project "${projectTitle}"?\n\nThis will remove:\n• All tasks and comments\n• All team memberships\n• All project data\n\nClick OK to continue or Cancel to abort.`)) {
                        
                        // Second confirmation with text input
                        const confirmation = prompt(`To confirm deletion, please type "DELETE" (in capital letters):`);
                        
                        if (confirmation === 'DELETE') {
                            try {
                                // Disable button during deletion
                                this.disabled = true;
                                this.textContent = 'Deleting...';
                                
                                const response = await fetch('api/project-actions.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        action: 'delete_project',
                                        project_id: projectId,
                                        csrf_token: window.csrfToken || ''
                                    })
                                });

                                const result = await response.json();
                                
                                if (result.success) {
                                    toastSuccess(result.message + ' - Project permanently deleted.');
                                    // Remove the project card from the view
                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                } else {
                                    toastError(result.message);
                                    // Re-enable button on error
                                    this.disabled = false;
                                    this.textContent = '🗑️ Delete';
                                }
                            } catch (error) {
                                console.error('Delete error:', error);
                                toastError('Failed to delete project');
                                // Re-enable button on error
                                this.disabled = false;
                                this.textContent = '🗑️ Delete';
                            }
                        } else if (confirmation !== null) {
                            // User entered something other than "DELETE"
                            toastError('Project deletion cancelled - confirmation text did not match "DELETE"');
                        }
                        // If confirmation is null, user clicked Cancel, so we do nothing
                    }
                });
            });

            // Initialize role-based UI
            if (window.initRoleBasedUI) {
                window.initRoleBasedUI('<?php echo $_SESSION['role'] ?? 'user'; ?>', <?php echo $_SESSION['user_id']; ?>);
            }
        });

        // Make CSRF token available
        window.csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>';
    </script>
</body>
</html>