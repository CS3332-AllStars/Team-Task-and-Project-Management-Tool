<?php
// CS3332 AllStars Team Task & Project Management System
// Task API Endpoints - CS3-13B, CS3-13C, CS3-13D Implementation

header('Content-Type: application/json');
require_once '../includes/api-session-check.php';
require_once '../src/config/database.php';
require_once '../src/models/Task.php';
require_once '../src/models/Project.php';
require_once '../includes/rbac-helpers.php';

// Set current user from session
$currentUser = [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? 'user'
];

// CSRF protection for state-changing operations
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    require_once '../includes/csrf-protection.php';
}

$task = new Task($pdo);
$project = new Project($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse request path
$pathParts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($task, $action);
            break;
            
        case 'POST':
            handlePostRequest($task, $project, $action);
            break;
            
        case 'PUT':
        case 'PATCH':
            handlePutRequest($task, $pdo, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($task, $action);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($task, $action) {
    global $currentUser;
    
    switch ($action) {
        case 'project':
            // Get all tasks for a project
            $projectId = $_GET['project_id'] ?? 0;
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            // Check project membership
            if (!hasPermission($currentUser['user_id'], $projectId, 'view_tasks')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $tasks = $task->getByProject($projectId);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'filter':
            // Get filtered tasks for a project (CS3-13F enhancement)
            $projectId = $_GET['project_id'] ?? 0;
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            // Check project membership
            if (!hasPermission($currentUser['user_id'], $projectId, 'view_tasks')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Build filters from query parameters
            $filters = [];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['assignee'])) $filters['assignee'] = $_GET['assignee'];
            if (!empty($_GET['due_start'])) $filters['due_start'] = $_GET['due_start'];
            if (!empty($_GET['due_end'])) $filters['due_end'] = $_GET['due_end'];
            if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
            
            $tasks = $task->getFilteredTasks($projectId, $filters);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'calendar':
            // Get calendar view tasks (CS3-13G)
            $projectId = $_GET['project_id'] ?? 0;
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            if (!hasPermission($currentUser['user_id'], $projectId, 'view_tasks')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $tasks = $task->getCalendarTasks($projectId);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'mytasks':
            // Get personal tasks view (CS3-13G)
            $projectId = $_GET['project_id'] ?? 0;
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            if (!hasPermission($currentUser['user_id'], $projectId, 'view_tasks')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $tasks = $task->getUserTasks($projectId, $currentUser['user_id']);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'team':
            // Get team view tasks (CS3-13G)
            $projectId = $_GET['project_id'] ?? 0;
            if (!$projectId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID required']);
                return;
            }
            
            if (!hasPermission($currentUser['user_id'], $projectId, 'view_tasks')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $tasks = $task->getTeamTasks($projectId);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'user':
            // Get tasks assigned to current user
            $limit = $_GET['limit'] ?? 10;
            $tasks = $task->getByUser($currentUser['user_id'], $limit);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'detail':
            // Get single task details
            $taskId = $_GET['task_id'] ?? 0;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            $taskData = $task->getById($taskId);
            if (!$taskData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            // Check project membership
            if (!hasPermission($currentUser['user_id'], $taskData['project_id'], 'view_tasks')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Get assignees
            $assignees = $task->getAssignees($taskId);
            $taskData['assignees'] = $assignees;
            
            echo json_encode(['success' => true, 'task' => $taskData]);
            break;
            
        case 'assignees':
            // Get task assignees
            $taskId = $_GET['task_id'] ?? 0;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            $assignees = $task->getAssignees($taskId);
            echo json_encode(['success' => true, 'assignees' => $assignees]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

/**
 * Handle POST requests (Create)
 */
function handlePostRequest($task, $project, $action) {
    global $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            // Create new task
            $projectId = $input['project_id'] ?? 0;
            $title = $input['title'] ?? '';
            $description = $input['description'] ?? '';
            $dueDate = $input['due_date'] ?? null;
            $assignees = $input['assignees'] ?? [];
            
            if (!$projectId || empty($title)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Project ID and title required']);
                return;
            }
            
            // Check permission to create tasks
            if (!hasPermission($currentUser['user_id'], $projectId, 'create_task')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Create task
            $result = $task->create($projectId, $title, $description, $currentUser['user_id'], $dueDate);
            
            if ($result['success'] && !empty($assignees)) {
                // Assign users to the task
                $assignResult = $task->assignToUsers($result['task_id'], $assignees);
                if (!$assignResult['success']) {
                    $result['assignment_warning'] = $assignResult['message'];
                }
            }
            
            http_response_code($result['success'] ? 201 : 400);
            echo json_encode($result);
            break;
            
        case 'assign':
            // Assign users to task
            $taskId = $input['task_id'] ?? 0;
            $userIds = $input['user_ids'] ?? [];
            
            if (!$taskId || empty($userIds)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID and user IDs required']);
                return;
            }
            
            // Get task to check project membership
            $taskData = $task->getById($taskId);
            if (!$taskData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            // Check permission
            if (!hasPermission($currentUser['user_id'], $taskData['project_id'], 'assign_task')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $task->assignToUsers($taskId, $userIds);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        case 'unassign':
            // Remove user from task
            $taskId = $input['task_id'] ?? 0;
            $userId = $input['user_id'] ?? 0;
            
            if (!$taskId || !$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID and user ID required']);
                return;
            }
            
            // Get task to check project membership
            $taskData = $task->getById($taskId);
            if (!$taskData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            // Check permission
            if (!hasPermission($currentUser['user_id'], $taskData['project_id'], 'assign_task')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $task->unassignUser($taskId, $userId);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

/**
 * Handle PUT/PATCH requests (Update)
 */
function handlePutRequest($task, $pdo, $action) {
    global $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            // Update task details and assignees
            $taskId = $input['task_id'] ?? 0;
            $title = $input['title'] ?? null;
            $description = $input['description'] ?? null;
            $dueDate = $input['due_date'] ?? null;
            $assignees = $input['assignees'] ?? null;
            
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            // Get task to check permissions
            $taskData = $task->getById($taskId);
            if (!$taskData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            // Check permission to edit task
            $userRole = getUserRole($currentUser['user_id'], $taskData['project_id']);
            if (!$task->canUserModify($taskId, $currentUser['user_id'], $userRole)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Update basic task details
            $result = $task->update($taskId, $title, $description, $dueDate);
            
            // Update assignees if provided
            if ($result['success'] && $assignees !== null) {
                // Clear existing assignments
                $clearSql = "DELETE FROM task_assignments WHERE task_id = ?";
                $clearStmt = $pdo->prepare($clearSql);
                $clearStmt->execute([$taskId]);
                
                // Add new assignments if any
                if (!empty($assignees)) {
                    $assignResult = $task->assignToUsers($taskId, $assignees);
                    if (!$assignResult['success']) {
                        $result['assignment_warning'] = $assignResult['message'];
                    }
                }
            }
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        case 'status':
            // Update task status
            $taskId = $input['task_id'] ?? 0;
            $status = $input['status'] ?? '';
            
            if (!$taskId || empty($status)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID and status required']);
                return;
            }
            
            // Get task to check permissions
            $taskData = $task->getById($taskId);
            if (!$taskData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            // Check permission to update status (any project member can update task status)
            if (!isProjectMember($currentUser['user_id'], $taskData['project_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $task->updateStatus($taskId, $status);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($task, $action) {
    global $currentUser;
    
    switch ($action) {
        case 'delete':
            $taskId = $_GET['task_id'] ?? 0;
            
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            // Get task to check permissions
            $taskData = $task->getById($taskId);
            if (!$taskData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                return;
            }
            
            // Check permission to delete task
            $userRole = getUserRole($currentUser['user_id'], $taskData['project_id']);
            if (!$task->canUserModify($taskId, $currentUser['user_id'], $userRole)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $result = $task->delete($taskId);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>