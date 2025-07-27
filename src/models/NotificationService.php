<?php
// CS3332 AllStars Team Task & Project Management System
// Notification Service - CS3-15B Backend Notification Trigger Logic

class NotificationService {
    private $pdo;
    private $logFile;

    public function __construct(PDO $pdo, $logFile = '/var/log/notifications.log') {
        $this->pdo = $pdo;
        $this->logFile = $logFile;
    }

    /**
     * Send a notification by inserting into DB.
     *
     * @param int $recipientID
     * @param string $type ENUM('task_assigned', 'task_updated', 'comment_added')
     * @param string $message
     * @param int|null $taskID
     * @param int|null $projectID
     * @param int|null $actorID
     * @return bool success
     */
    public function notify($recipientID, $type, $message, $taskID = null, $projectID = null, $actorID = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications
                (user_id, actor_id, task_id, project_id, type, message)
                VALUES
                (:user_id, :actor_id, :task_id, :project_id, :type, :message)
            ");

            $stmt->execute([
                ':user_id' => $recipientID,
                ':actor_id' => $actorID,
                ':task_id' => $taskID,
                ':project_id' => $projectID,
                ':type' => $type,
                ':message' => $message
            ]);

            $this->log("Notification created: user_id={$recipientID}, type={$type}, task_id={$taskID}, project_id={$projectID}, actor_id={$actorID}");
            return true;

        } catch (PDOException $e) {
            $this->log("Notification insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a user
     */
    public function getNotifications($userID, $limit = 25) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT notification_id, actor_id, task_id, project_id, type, message, is_read, created_at
                FROM notifications
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':user_id', $userID, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $this->log("Get notifications failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationID, $userID) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications
                SET is_read = TRUE
                WHERE notification_id = :notification_id AND user_id = :user_id
            ");

            $stmt->execute([
                ':notification_id' => $notificationID,
                ':user_id' => $userID
            ]);

            return $stmt->rowCount() === 1;

        } catch (PDOException $e) {
            $this->log("Mark as read failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userID) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications
                SET is_read = TRUE
                WHERE user_id = :user_id AND is_read = FALSE
            ");

            $stmt->execute([':user_id' => $userID]);
            return $stmt->rowCount();

        } catch (PDOException $e) {
            $this->log("Mark all as read failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOldNotifications($userID, $days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications
                WHERE user_id = :user_id
                AND is_read = TRUE
                AND created_at < NOW() - INTERVAL :days DAY
            ");

            $stmt->execute([
                ':user_id' => $userID,
                ':days' => $days
            ]);

            return $stmt->rowCount();

        } catch (PDOException $e) {
            $this->log("Delete old notifications failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create notification (compatible with test API)
     */
    public function create($userId, $type, $title, $message, $relatedTaskId = null, $relatedProjectId = null) {
        $validTypes = ['task_assigned', 'task_updated', 'task_completed', 'comment_added', 'project_invitation', 'deadline_reminder'];
        
        if (!in_array($type, $validTypes)) {
            return ['success' => false, 'message' => 'Invalid notification type'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_task_id, related_project_id, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, FALSE, NOW())
            ");
            
            $stmt->execute([$userId, $type, $title, $message, $relatedTaskId, $relatedProjectId]);
            $notificationId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notification created successfully'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get notification by ID
     */
    public function getById($notificationId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications WHERE notification_id = ?
            ");
            $stmt->execute([$notificationId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                // Convert to boolean for consistency
                $result['is_read'] = (bool)$result['is_read'];
            }
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get user notifications (compatible with test API)
     */
    public function getUserNotifications($userId, $limit = 25) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Convert is_read to boolean for all results
            foreach ($results as &$result) {
                $result['is_read'] = (bool)$result['is_read'];
            }
            return $results;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Mark as read (single notification - compatible with test API)
     */
    public function markNotificationAsRead($notificationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE notification_id = ?
            ");
            $stmt->execute([$notificationId]);
            
            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark all as read (compatible with test API)
     */
    public function markAllNotificationsAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            
            return [
                'success' => true,
                'message' => 'All notifications marked as read'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cleanup old notifications (compatible with test API)
     */
    public function cleanupOld($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            
            return [
                'success' => true,
                'message' => 'Old notifications cleaned up'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function log($msg) {
        $date = date('Y-m-d H:i:s');
        error_log("[$date] $msg\n", 3, $this->logFile);
    }
}

// Helper functions for common notification patterns

/**
 * Notify about task assignment
 */
function notifyTaskAssigned($pdo, $taskID, $recipientID, $actorID, $projectID) {
    $notificationService = new NotificationService($pdo);
    $message = "You have been assigned a new task (#$taskID).";
    return $notificationService->notify($recipientID, 'task_assigned', $message, $taskID, $projectID, $actorID);
}

/**
 * Notify about task status update
 */
function notifyTaskUpdated($pdo, $taskID, $recipientID, $actorID, $projectID, $newStatus) {
    $notificationService = new NotificationService($pdo);
    $message = "Task #$taskID status changed to $newStatus.";
    return $notificationService->notify($recipientID, 'task_updated', $message, $taskID, $projectID, $actorID);
}

/**
 * Notify about new comment
 */
function notifyCommentAdded($pdo, $taskID, $recipientID, $actorID, $projectID, $commentText = null) {
    $notificationService = new NotificationService($pdo);
    $message = $commentText ? "New comment added: \"" . substr($commentText, 0, 50) . "...\"" : "New comment added to your task.";
    return $notificationService->notify($recipientID, 'comment_added', $message, $taskID, $projectID, $actorID);
}
?>