<?php
// CS3332 AllStars Team Task & Project Management System
// Notification API Endpoints - CS3-15C

session_start();
require_once '../includes/session-check.php';
require_once '../includes/csrf-protection.php';
require_once '../src/config/database.php';
require_once '../src/models/NotificationService.php';

header('Content-Type: application/json');

// Ensure user is authenticated
if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userID = $_SESSION['userID'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDatabaseConnection();
    $notificationService = new NotificationService($pdo);

    switch ($method) {
        case 'GET':
            // Get notifications for current user
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 25;
            $notifications = $notificationService->getNotifications($userID, $limit);
            
            echo json_encode(['notifications' => $notifications]);
            break;

        case 'POST':
            // Handle different POST actions
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';

            switch ($action) {
                case 'mark_read':
                    // CSRF validation for write operations
                    if (!validateCSRFToken($data['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['error' => 'CSRF token validation failed']);
                        exit;
                    }

                    $notificationID = (int)($data['notification_id'] ?? 0);
                    if ($notificationService->markAsRead($notificationID, $userID)) {
                        echo json_encode(['status' => 'success']);
                    } else {
                        http_response_code(403);
                        echo json_encode(['error' => 'Not authorized or not found']);
                    }
                    break;

                case 'mark_all_read':
                    // CSRF validation for write operations
                    if (!validateCSRFToken($data['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['error' => 'CSRF token validation failed']);
                        exit;
                    }

                    $updated = $notificationService->markAllAsRead($userID);
                    echo json_encode(['status' => 'success', 'updated' => $updated]);
                    break;

                case 'cleanup':
                    // CSRF validation for write operations
                    if (!validateCSRFToken($data['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['error' => 'CSRF token validation failed']);
                        exit;
                    }

                    $days = isset($data['days']) ? max((int)$data['days'], 7) : 30;
                    $deleted = $notificationService->deleteOldNotifications($userID, $days);
                    echo json_encode(['status' => 'success', 'deleted' => $deleted]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;

        case 'DELETE':
            // Archive old notifications (CS3-15F cleanup)
            parse_str(file_get_contents('php://input'), $data);
            
            // Basic CSRF check for DELETE operations
            if (!validateCSRFToken($data['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['error' => 'CSRF token validation failed']);
                exit;
            }

            $deleted = $notificationService->deleteOldNotifications($userID, 30);
            echo json_encode([
                'status' => 'success',
                'deleted' => $deleted
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Notification API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>