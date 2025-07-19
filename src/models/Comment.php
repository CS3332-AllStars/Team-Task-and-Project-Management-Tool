<?php
// CS3332 AllStars Team Task & Project Management System  
// Comment Model - CS3-14 Implementation

class Comment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new comment
     * CS3-14A: Database & Schema Setup for Comments
     * CS3-14B: API Endpoint Implementation (CRUD)
     */
    public function create($taskId, $userId, $content) {
        try {
            // Validation
            if (empty(trim($content))) {
                return ['success' => false, 'message' => 'Comment content is required'];
            }
            
            if (strlen($content) > 1000) {
                return ['success' => false, 'message' => 'Comment must be 1000 characters or less'];
            }
            
            // Insert comment
            $sql = "INSERT INTO comments (task_id, user_id, content) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId, $userId, trim($content)]);
            
            $commentId = $this->pdo->lastInsertId();
            
            // Get the created comment with user info
            $comment = $this->getById($commentId);
            
            return [
                'success' => true,
                'comment_id' => $commentId,
                'comment' => $comment,
                'message' => 'Comment added successfully'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create comment: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get comment by ID
     */
    public function getById($commentId) {
        try {
            $sql = "SELECT c.*, u.username, u.name, u.email
                    FROM comments c 
                    JOIN users u ON c.user_id = u.user_id
                    WHERE c.comment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$commentId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all comments for a task
     * CS3-14C: Frontend Display & Submission Logic
     */
    public function getByTask($taskId) {
        try {
            $sql = "SELECT c.*, u.username, u.name, u.email
                    FROM comments c 
                    LEFT JOIN users u ON c.user_id = u.user_id
                    WHERE c.task_id = ?
                    ORDER BY c.timestamp ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Update comment content
     * CS3-14D: Edit/Delete Controls for Own Comments
     */
    public function update($commentId, $content, $userId) {
        try {
            // Check if comment exists and user owns it
            $comment = $this->getById($commentId);
            if (!$comment) {
                return ['success' => false, 'message' => 'Comment not found'];
            }
            
            if ($comment['user_id'] != $userId) {
                return ['success' => false, 'message' => 'You can only edit your own comments'];
            }
            
            // Validation
            if (empty(trim($content))) {
                return ['success' => false, 'message' => 'Comment content is required'];
            }
            
            if (strlen($content) > 1000) {
                return ['success' => false, 'message' => 'Comment must be 1000 characters or less'];
            }
            
            // Update comment
            $sql = "UPDATE comments SET content = ? WHERE comment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([trim($content), $commentId]);
            
            // Get updated comment
            $updatedComment = $this->getById($commentId);
            
            return [
                'success' => true,
                'comment' => $updatedComment,
                'message' => 'Comment updated successfully'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update comment: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete comment
     * CS3-14D: Edit/Delete Controls for Own Comments
     */
    public function delete($commentId, $userId) {
        try {
            // Check if comment exists and user owns it
            $comment = $this->getById($commentId);
            if (!$comment) {
                return ['success' => false, 'message' => 'Comment not found'];
            }
            
            if ($comment['user_id'] != $userId) {
                return ['success' => false, 'message' => 'You can only delete your own comments'];
            }
            
            // Delete comment
            $sql = "DELETE FROM comments WHERE comment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$commentId]);
            
            return ['success' => true, 'message' => 'Comment deleted successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete comment: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get recent comments by user
     */
    public function getByUser($userId, $limit = 10) {
        try {
            $sql = "SELECT c.*, t.title as task_title, p.title as project_title
                    FROM comments c 
                    JOIN tasks t ON c.task_id = t.task_id
                    JOIN projects p ON t.project_id = p.project_id
                    WHERE c.user_id = ?
                    ORDER BY c.timestamp DESC
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get comment count for a task
     */
    public function getTaskCommentCount($taskId) {
        try {
            $sql = "SELECT COUNT(*) as comment_count FROM comments WHERE task_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['comment_count'] : 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Search comments by content
     */
    public function search($query, $projectId = null, $limit = 50) {
        try {
            $searchTerm = '%' . $query . '%';
            
            if ($projectId) {
                $sql = "SELECT c.*, u.username, u.name, t.title as task_title
                        FROM comments c 
                        JOIN users u ON c.user_id = u.user_id
                        JOIN tasks t ON c.task_id = t.task_id
                        WHERE t.project_id = ? AND c.content LIKE ?
                        ORDER BY c.timestamp DESC
                        LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$projectId, $searchTerm, $limit]);
            } else {
                $sql = "SELECT c.*, u.username, u.name, t.title as task_title, p.title as project_title
                        FROM comments c 
                        JOIN users u ON c.user_id = u.user_id
                        JOIN tasks t ON c.task_id = t.task_id
                        JOIN projects p ON t.project_id = p.project_id
                        WHERE c.content LIKE ?
                        ORDER BY c.timestamp DESC
                        LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$searchTerm, $limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Validate user can access comment (through task/project membership)
     */
    public function canUserAccess($commentId, $userId) {
        try {
            $sql = "SELECT c.comment_id
                    FROM comments c
                    JOIN tasks t ON c.task_id = t.task_id
                    JOIN project_memberships pm ON t.project_id = pm.project_id
                    WHERE c.comment_id = ? AND pm.user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$commentId, $userId]);
            
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Validate user can modify comment (own comments only)
     */
    public function canUserModify($commentId, $userId) {
        try {
            $sql = "SELECT user_id FROM comments WHERE comment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$commentId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['user_id'] == $userId;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get task ID from comment ID
     */
    public function getTaskId($commentId) {
        try {
            $sql = "SELECT task_id FROM comments WHERE comment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$commentId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['task_id'] : null;
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Input sanitization and security
     * CS3-14E: Input Validation & Security
     */
    public function sanitizeContent($content) {
        // Strip potentially dangerous HTML tags but allow basic formatting
        $allowedTags = '<b><i><u><strong><em><br><p>';
        $sanitized = strip_tags($content, $allowedTags);
        
        // Remove any potential script content
        $sanitized = preg_replace('/on\w+="[^"]*"/i', '', $sanitized);
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        
        return trim($sanitized);
    }
    
    /**
     * Check for spam or inappropriate content
     */
    public function validateContent($content) {
        // Basic content validation
        $trimmed = trim($content);
        
        if (empty($trimmed)) {
            return ['valid' => false, 'message' => 'Comment cannot be empty'];
        }
        
        if (strlen($trimmed) < 2) {
            return ['valid' => false, 'message' => 'Comment must be at least 2 characters'];
        }
        
        if (strlen($trimmed) > 1000) {
            return ['valid' => false, 'message' => 'Comment must be 1000 characters or less'];
        }
        
        // Check for spam patterns (basic)
        if (preg_match('/(.)\1{10,}/', $trimmed)) {
            return ['valid' => false, 'message' => 'Comment appears to be spam'];
        }
        
        return ['valid' => true];
    }
}
?>