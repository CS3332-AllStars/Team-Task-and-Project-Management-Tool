<?php
// CS3332 AllStars Team Task & Project Management System
// Comment API Endpoints - CS3-14B Implementation

header('Content-Type: application/json');
require_once '../includes/api-session-check.php';
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

/**
 * Validate that a user exists
 */
function validateUserExists($pdo, $userId) {
    try {
        $sql = "SELECT user_id FROM users WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Validate that a task exists and user has access
 */
function validateTaskExists($pdo, $taskId, $userId) {
    try {
        // Check if task exists and user has access through project membership
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

/**
 * Validate that a comment exists and get its details
 */
function validateCommentExists($pdo, $commentId) {
    try {
        $sql = "SELECT comment_id, task_id, user_id FROM comments WHERE comment_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$commentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

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
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($comment, $action) {
    global $currentUser, $pdo;
    
    switch ($action) {
        case 'task':
            // Get all comments for a task
            $taskId = $_GET['task_id'] ?? 0;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                return;
            }
            
            // Validate task exists and user has access
            if (!validateTaskExists($pdo, $taskId, $currentUser['user_id'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found or access denied']);
                return;
            }
            
            $comments = $comment->getByTask($taskId);
            echo json_encode(['success' => true, 'data' => ['comments' => $comments]]);
            break;
            
        case 'detail':
            // Get single comment details
            $commentId = $_GET['comment_id'] ?? 0;
            
            if (!$commentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Comment ID required']);
                return;
            }
            
            // Check if user has access to this comment
            if (!$comment->canUserAccess($commentId, $currentUser['user_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                return;
            }
            
            $commentData = $comment->getById($commentId);
            if (!$commentData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comment not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => ['comment' => $commentData]]);
            break;
            
        case 'user':
            // Get comments by current user
            $limit = $_GET['limit'] ?? 10;
            $comments = $comment->getByUser($currentUser['user_id'], $limit);
            echo json_encode(['success' => true, 'data' => ['comments' => $comments]]);
            break;
            
        case 'search':
            // Search comments
            $query = $_GET['query'] ?? '';
            $projectId = $_GET['project_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            
            if (empty($query)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Search query required']);
                return;
            }
            
            // If project specified, check access
            if ($projectId && !hasPermission($currentUser['user_id'], $projectId, 'view_comments')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                return;
            }
            
            $results = $comment->search($query, $projectId, $limit);
            echo json_encode(['success' => true, 'data' => ['comments' => $results]]);
            break;
            
        case 'count':
            // Get comment count for a task
            $taskId = $_GET['task_id'] ?? 0;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Task ID required']);
                return;
            }
            
            // Validate task exists and user has access
            if (!validateTaskExists($pdo, $taskId, $currentUser['user_id'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found or access denied']);
                return;
            }
            
            $count = $comment->getTaskCommentCount($taskId);
            echo json_encode(['success' => true, 'data' => ['count' => $count]]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
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
                echo json_encode(['success' => false, 'error' => 'Task ID and content required']);
                return;
            }
            
            // Validate user exists
            global $pdo;
            if (!validateUserExists($pdo, $currentUser['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid user']);
                return;
            }
            
            // Validate task exists and user has access
            if (!validateTaskExists($pdo, $taskId, $currentUser['user_id'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found or access denied']);
                return;
            }
            
            // Validate content
            $validation = $comment->validateContent($content);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $validation['message']]);
                return;
            }
            
            // Sanitize content
            $sanitizedContent = $comment->sanitizeContent($content);
            
            // Create comment
            $result = $comment->create($taskId, $currentUser['user_id'], $sanitizedContent);
            
            http_response_code($result['success'] ? 201 : 400);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'comment_id' => $result['comment_id'],
                        'comment' => $result['comment']
                    ],
                    'message' => $result['message']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
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
                echo json_encode(['success' => false, 'error' => 'Comment ID and content required']);
                return;
            }
            
            // Validate user exists
            global $pdo;
            if (!validateUserExists($pdo, $currentUser['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid user']);
                return;
            }
            
            // Validate comment exists and get its details
            $commentData = validateCommentExists($pdo, $commentId);
            if (!$commentData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comment not found']);
                return;
            }
            
            // Validate user owns this comment
            if ($commentData['user_id'] != $currentUser['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'You can only edit your own comments']);
                return;
            }
            
            // Validate task still exists and user has access
            if (!validateTaskExists($pdo, $commentData['task_id'], $currentUser['user_id'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Associated task not found or access denied']);
                return;
            }
            
            // Validate content
            $validation = $comment->validateContent($content);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $validation['message']]);
                return;
            }
            
            // Sanitize content
            $sanitizedContent = $comment->sanitizeContent($content);
            
            // Update comment (permission check is inside the method)
            $result = $comment->update($commentId, $sanitizedContent, $currentUser['user_id']);
            
            http_response_code($result['success'] ? 200 : 400);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'data' => ['comment' => $result['comment']],
                    'message' => $result['message']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
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
                echo json_encode(['success' => false, 'error' => 'Comment ID required']);
                return;
            }
            
            // Validate user exists
            global $pdo;
            if (!validateUserExists($pdo, $currentUser['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid user']);
                return;
            }
            
            // Validate comment exists and get its details
            $commentData = validateCommentExists($pdo, $commentId);
            if (!$commentData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comment not found']);
                return;
            }
            
            // Validate user owns this comment
            if ($commentData['user_id'] != $currentUser['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'You can only delete your own comments']);
                return;
            }
            
            // Validate task still exists and user has access
            if (!validateTaskExists($pdo, $commentData['task_id'], $currentUser['user_id'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Associated task not found or access denied']);
                return;
            }
            
            // Delete comment (permission check is already done above)
            $result = $comment->delete($commentId, $currentUser['user_id']);
            
            http_response_code($result['success'] ? 200 : 400);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['message']]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
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