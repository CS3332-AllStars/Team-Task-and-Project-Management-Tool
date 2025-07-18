<?php
// CS3332 AllStars Team Task & Project Management System  
// Task Model - Complete implementation for CS3-13B, CS3-13C, CS3-13D

class Task {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new task
     * CS3-95: Task Creation & Input Validation
     */
    public function create($projectId, $title, $description = '', $assignedBy = null, $dueDate = null) {
        try {
            // Validation
            if (empty(trim($title))) {
                return ['success' => false, 'message' => 'Title is required'];
            }
            
            if (strlen($title) > 100) {
                return ['success' => false, 'message' => 'Title must be 100 characters or less'];
            }
            
            // Insert task
            $sql = "INSERT INTO tasks (project_id, title, description, assigned_by, due_date) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$projectId, trim($title), $description, $assignedBy, $dueDate]);
            
            $taskId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'task_id' => $taskId,
                'status' => 'To Do',
                'message' => 'Task created successfully'
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get task by ID
     */
    public function getById($taskId) {
        try {
            $sql = "SELECT t.*, 
                           u.username as assigned_by_username,
                           p.title as project_title
                    FROM tasks t 
                    LEFT JOIN users u ON t.assigned_by = u.user_id
                    LEFT JOIN projects p ON t.project_id = p.project_id
                    WHERE t.task_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all tasks for a project
     */
    public function getByProject($projectId) {
        try {
            $sql = "SELECT t.*, 
                           u.username as assigned_by_username,
                           GROUP_CONCAT(
                               CONCAT(ua.username, ':', ua.user_id) 
                               SEPARATOR ','
                           ) as assignees
                    FROM tasks t 
                    LEFT JOIN users u ON t.assigned_by = u.user_id
                    LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
                    LEFT JOIN users ua ON ta.user_id = ua.user_id
                    WHERE t.project_id = ?
                    GROUP BY t.task_id
                    ORDER BY t.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$projectId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Update task status
     * CS3-97: Status Update Logic (To Do → In Progress → Done)
     */
    public function updateStatus($taskId, $newStatus) {
        try {
            // Validate status
            $validStatuses = ['To Do', 'In Progress', 'Done'];
            if (!in_array($newStatus, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            // Get current status for transition validation
            $currentTask = $this->getById($taskId);
            if (!$currentTask) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Validate status transitions
            $currentStatus = $currentTask['status'];
            if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
                return ['success' => false, 'message' => 'Invalid status transition'];
            }
            
            // Update status
            $sql = "UPDATE tasks SET status = ? WHERE task_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$newStatus, $taskId]);
            
            return ['success' => true, 'message' => 'Task status updated successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate status transitions
     */
    private function isValidStatusTransition($currentStatus, $newStatus) {
        // Allow any backward movement and logical forward progression
        $transitions = [
            'To Do' => ['To Do', 'In Progress', 'Done'],
            'In Progress' => ['To Do', 'In Progress', 'Done'],
            'Done' => ['To Do', 'In Progress', 'Done'] // Allow reopening
        ];
        
        return isset($transitions[$currentStatus]) && 
               in_array($newStatus, $transitions[$currentStatus]);
    }
    
    /**
     * Assign task to user
     * CS3-96: Multi-User Task Assignment
     */
    public function assignToUser($taskId, $userId) {
        try {
            // Check if assignment already exists
            $checkSql = "SELECT * FROM task_assignments WHERE task_id = ? AND user_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$taskId, $userId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'User already assigned to this task'];
            }
            
            // Create assignment
            $sql = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId, $userId]);
            
            return ['success' => true, 'message' => 'Task assigned successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to assign task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Assign task to multiple users
     */
    public function assignToUsers($taskId, $userIds) {
        try {
            $this->pdo->beginTransaction();
            
            $successCount = 0;
            $errors = [];
            
            foreach ($userIds as $userId) {
                $result = $this->assignToUser($taskId, $userId);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "User ID $userId: " . $result['message'];
                }
            }
            
            $this->pdo->commit();
            
            if ($successCount > 0) {
                $message = "Successfully assigned $successCount user(s)";
                if (!empty($errors)) {
                    $message .= ". Errors: " . implode(', ', $errors);
                }
                return ['success' => true, 'message' => $message];
            } else {
                return ['success' => false, 'message' => 'No assignments created. ' . implode(', ', $errors)];
            }
            
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return ['success' => false, 'message' => 'Failed to assign task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove user assignment from task
     */
    public function unassignUser($taskId, $userId) {
        try {
            $sql = "DELETE FROM task_assignments WHERE task_id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId, $userId]);
            
            return ['success' => true, 'message' => 'User unassigned successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to unassign user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get task assignees
     */
    public function getAssignees($taskId) {
        try {
            $sql = "SELECT u.user_id, u.username, u.full_name, ta.assigned_at
                    FROM task_assignments ta
                    JOIN users u ON ta.user_id = u.user_id
                    WHERE ta.task_id = ?
                    ORDER BY ta.assigned_at";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Update task details
     * CS3-98: Task Editing & Deletion Controls
     */
    public function update($taskId, $title = null, $description = null, $dueDate = null) {
        try {
            $currentTask = $this->getById($taskId);
            if (!$currentTask) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Use current values if not provided
            $title = $title !== null ? trim($title) : $currentTask['title'];
            $description = $description !== null ? $description : $currentTask['description'];
            $dueDate = $dueDate !== null ? $dueDate : $currentTask['due_date'];
            
            // Validation
            if (empty($title)) {
                return ['success' => false, 'message' => 'Title is required'];
            }
            
            if (strlen($title) > 100) {
                return ['success' => false, 'message' => 'Title must be 100 characters or less'];
            }
            
            // Update task
            $sql = "UPDATE tasks SET title = ?, description = ?, due_date = ? WHERE task_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$title, $description, $dueDate, $taskId]);
            
            return ['success' => true, 'message' => 'Task updated successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete task
     * CS3-98: Task Editing & Deletion Controls
     */
    public function delete($taskId) {
        try {
            // Check if task exists
            $task = $this->getById($taskId);
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Delete task (cascading will handle assignments and comments)
            $sql = "DELETE FROM tasks WHERE task_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$taskId]);
            
            return ['success' => true, 'message' => 'Task deleted successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete task: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get tasks assigned to user
     */
    public function getByUser($userId, $limit = 10) {
        try {
            $sql = "SELECT t.*, p.title as project_title
                    FROM tasks t 
                    JOIN task_assignments ta ON t.task_id = ta.task_id 
                    JOIN projects p ON t.project_id = p.project_id
                    WHERE ta.user_id = ?
                    ORDER BY t.due_date ASC, t.created_at DESC
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Validate user can edit/delete task
     * CS3-98: Authorization check
     */
    public function canUserModify($taskId, $userId, $userRole = null) {
        $task = $this->getById($taskId);
        if (!$task) {
            return false;
        }
        
        // Task creator can always modify
        if ($task['assigned_by'] == $userId) {
            return true;
        }
        
        // Project admin can modify
        if ($userRole === 'admin') {
            return true;
        }
        
        return false;
    }
}
?>