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