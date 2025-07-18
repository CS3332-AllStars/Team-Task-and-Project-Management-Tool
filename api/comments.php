<?php
// CS3332 AllStars Team Task & Project Management System
// Comment API Endpoints - CS3-14B Implementation

header('Content-Type: application/json');
require_once '../includes/session-check.php';
require_once '../src/config/database.php';
require_once '../src/models/Comment.php';
require_once '../src/models/Task.php';
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

$comment = new Comment($pdo);
$task = new Task($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($comment, $action);
            break;
            
        case 'POST':
            handlePostRequest($comment, $task, $action);
            break;
            
        case 'PUT':
        case 'PATCH':
            handlePutRequest($comment, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($comment, $action);
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
function handleGetRequest($comment, $action) {
    global $currentUser;
    
    switch ($action) {
        case 'task':
            // Get all comments for a task
            $taskId = $_GET['task_id'] ?? 0;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            // Check if user has access to this task (through project membership)
            if (!hasTaskAccess($currentUser['user_id'], $taskId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $comments = $comment->getByTask($taskId);
            echo json_encode(['success' => true, 'comments' => $comments]);
            break;
            
        case 'detail':
            // Get single comment details
            $commentId = $_GET['comment_id'] ?? 0;
            if (!$commentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment ID required']);
                return;
            }
            
            // Check if user has access to this comment
            if (!$comment->canUserAccess($commentId, $currentUser['user_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $commentData = $comment->getById($commentId);
            if (!$commentData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Comment not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'comment' => $commentData]);
            break;
            
        case 'user':
            // Get comments by current user
            $limit = $_GET['limit'] ?? 10;
            $comments = $comment->getByUser($currentUser['user_id'], $limit);
            echo json_encode(['success' => true, 'comments' => $comments]);
            break;
            
        case 'search':
            // Search comments
            $query = $_GET['query'] ?? '';
            $projectId = $_GET['project_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            
            if (empty($query)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Search query required']);
                return;
            }
            
            // If project specified, check access
            if ($projectId && !hasPermission($currentUser['user_id'], $projectId, 'view_comments')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $results = $comment->search($query, $projectId, $limit);
            echo json_encode(['success' => true, 'comments' => $results]);
            break;
            
        case 'count':
            // Get comment count for a task
            $taskId = $_GET['task_id'] ?? 0;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                return;
            }
            
            // Check access
            if (!hasTaskAccess($currentUser['user_id'], $taskId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $count = $comment->getTaskCommentCount($taskId);
            echo json_encode(['success' => true, 'count' => $count]);
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
function handlePostRequest($comment, $task, $action) {
    global $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create':
            // Create new comment
            $taskId = $input['task_id'] ?? 0;
            $content = $input['content'] ?? '';
            
            if (!$taskId || empty($content)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID and content required']);
                return;
            }
            
            // Check if user has access to comment on this task
            if (!hasTaskAccess($currentUser['user_id'], $taskId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Validate content
            $validation = $comment->validateContent($content);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $validation['message']]);
                return;
            }
            
            // Sanitize content
            $sanitizedContent = $comment->sanitizeContent($content);
            
            // Create comment
            $result = $comment->create($taskId, $currentUser['user_id'], $sanitizedContent);
            
            http_response_code($result['success'] ? 201 : 400);
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
function handlePutRequest($comment, $action) {
    global $currentUser;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            // Update comment content
            $commentId = $input['comment_id'] ?? 0;
            $content = $input['content'] ?? '';
            
            if (!$commentId || empty($content)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment ID and content required']);
                return;
            }
            
            // Validate content
            $validation = $comment->validateContent($content);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $validation['message']]);
                return;
            }
            
            // Sanitize content
            $sanitizedContent = $comment->sanitizeContent($content);
            
            // Update comment (permission check is inside the method)
            $result = $comment->update($commentId, $sanitizedContent, $currentUser['user_id']);
            
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
function handleDeleteRequest($comment, $action) {
    global $currentUser;
    
    switch ($action) {
        case 'delete':
            $commentId = $_GET['comment_id'] ?? 0;
            
            if (!$commentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment ID required']);
                return;
            }
            
            // Delete comment (permission check is inside the method)
            $result = $comment->delete($commentId, $currentUser['user_id']);
            
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
 * Helper function to check if user has access to a task
 */
function hasTaskAccess($userId, $taskId) {
    global $pdo;
    
    try {
        $sql = "SELECT t.task_id
                FROM tasks t
                JOIN project_memberships pm ON t.project_id = pm.project_id
                WHERE t.task_id = ? AND pm.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$taskId, $userId]);
        
        return $stmt->fetch() !== false;
        
    } catch (PDOException $e) {
        return false;
    }
}