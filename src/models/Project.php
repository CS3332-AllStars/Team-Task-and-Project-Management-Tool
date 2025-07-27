<?php
// CS3332 AllStars Team Task & Project Management System  
// Project Model - CS3-19B: Project Management Implementation
// Handles project creation, membership, and management

class Project {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new project
     * CS3-19B requirement: Project creation with title and description
     */
    public function create($title, $description, $ownerId) {
        // Sanitize inputs
        $title = htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');
        
        // Validate input
        if (empty($title)) {
            return ['success' => false, 'message' => 'Title is required'];
        }
        
        if (strlen($title) > 100) {
            return ['success' => false, 'message' => 'Title too long (max 100 characters)'];
        }
        
        // Validate user exists
        if (!$this->userExists($ownerId)) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Create project
            $stmt = $this->pdo->prepare("
                INSERT INTO projects (title, description, created_date, updated_at) 
                VALUES (?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([$title, $description]);
            $projectId = $this->pdo->lastInsertId();
            
            // Add owner as admin member
            $memberStmt = $this->pdo->prepare("
                INSERT INTO project_memberships (project_id, user_id, role, joined_at) 
                VALUES (?, ?, 'admin', NOW())
            ");
            
            $memberStmt->execute([$projectId, $ownerId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Project created successfully'
            ];
        } catch (PDOException $e) {
            $this->pdo->rollback();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Add member to project
     * CS3-19B requirement: User invitations to projects
     */
    public function addMember($projectId, $userId, $role = 'member') {
        $validRoles = ['member', 'admin'];
        
        if (!in_array($role, $validRoles)) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        // Validate project exists
        if (!$this->getById($projectId)) {
            return ['success' => false, 'message' => 'Project not found'];
        }
        
        // Validate user exists
        if (!$this->userExists($userId)) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        try {
            // Check if user is already a member
            $checkStmt = $this->pdo->prepare("
                SELECT 1 FROM project_memberships 
                WHERE project_id = ? AND user_id = ?
            ");
            
            $checkStmt->execute([$projectId, $userId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => 'User is already a member of this project'];
            }
            
            // Add member
            $stmt = $this->pdo->prepare("
                INSERT INTO project_memberships (project_id, user_id, role, joined_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$projectId, $userId, $role]);
            
            return [
                'success' => true,
                'message' => 'Member added successfully'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get projects for a user
     * CS3-19B requirement: Project dashboard display
     */
    public function getUserProjects($userId) {
        try {
            $stmt = $this->pdo->prepare("
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
                WHERE pm.user_id = ? AND (p.is_archived IS NULL OR p.is_archived = FALSE)
                ORDER BY p.updated_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get project by ID with details
     */
    public function getById($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       (SELECT COUNT(*) FROM project_memberships pm WHERE pm.project_id = p.project_id) as team_size,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id) as total_tasks,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.project_id AND t.status = 'Done') as completed_tasks
                FROM projects p 
                WHERE p.project_id = ?
            ");
            
            $stmt->execute([$projectId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get project members
     */
    public function getMembers($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.user_id, u.username, u.name, u.email, pm.role, pm.joined_at
                FROM users u
                JOIN project_memberships pm ON u.user_id = pm.user_id
                WHERE pm.project_id = ?
                ORDER BY pm.role DESC, pm.joined_at ASC
            ");
            
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Update project details
     */
    public function update($projectId, $title = null, $description = null) {
        $project = $this->getById($projectId);
        if (!$project) {
            return ['success' => false, 'message' => 'Project not found'];
        }
        
        // Use existing values if not provided
        $title = $title !== null ? htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8') : $project['title'];
        $description = $description !== null ? htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8') : $project['description'];
        
        // Validate title
        if (empty($title)) {
            return ['success' => false, 'message' => 'Title is required'];
        }
        
        if (strlen($title) > 100) {
            return ['success' => false, 'message' => 'Title too long (max 100 characters)'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE projects 
                SET title = ?, description = ?, updated_at = NOW()
                WHERE project_id = ?
            ");
            
            $stmt->execute([$title, $description, $projectId]);
            
            return [
                'success' => true,
                'message' => 'Project updated successfully'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Archive project
     */
    public function archive($projectId) {
        if (!$this->getById($projectId)) {
            return ['success' => false, 'message' => 'Project not found'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE projects 
                SET is_archived = TRUE, updated_at = NOW()
                WHERE project_id = ?
            ");
            
            $stmt->execute([$projectId]);
            
            return [
                'success' => true,
                'message' => 'Project archived successfully'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Unarchive project
     */
    public function unarchive($projectId) {
        if (!$this->getById($projectId)) {
            return ['success' => false, 'message' => 'Project not found'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE projects 
                SET is_archived = FALSE, updated_at = NOW()
                WHERE project_id = ?
            ");
            
            $stmt->execute([$projectId]);
            
            return [
                'success' => true,
                'message' => 'Project unarchived successfully'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete project (admin only)
     */
    public function delete($projectId) {
        if (!$this->getById($projectId)) {
            return ['success' => false, 'message' => 'Project not found'];
        }
        
        try {
            // Foreign key constraints will handle cascade deletes
            $stmt = $this->pdo->prepare("DELETE FROM projects WHERE project_id = ?");
            $stmt->execute([$projectId]);
            
            return [
                'success' => true,
                'message' => 'Project deleted successfully'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove member from project
     */
    public function removeMember($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM project_memberships 
                WHERE project_id = ? AND user_id = ?
            ");
            
            $stmt->execute([$projectId, $userId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Member removed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Member not found'
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update member role
     */
    public function updateMemberRole($projectId, $userId, $newRole) {
        $validRoles = ['member', 'admin'];
        
        if (!in_array($newRole, $validRoles)) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE project_memberships 
                SET role = ?
                WHERE project_id = ? AND user_id = ?
            ");
            
            $stmt->execute([$newRole, $projectId, $userId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Member role updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Member not found'
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if user is member of project
     */
    public function isMember($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT role FROM project_memberships 
                WHERE project_id = ? AND user_id = ?
            ");
            
            $stmt->execute([$projectId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if user is admin of project
     */
    public function isAdmin($projectId, $userId) {
        $membership = $this->isMember($projectId, $userId);
        return $membership && $membership['role'] === 'admin';
    }
    
    /**
     * Get archived projects for user
     */
    public function getArchivedProjects($userId) {
        try {
            $stmt = $this->pdo->prepare("
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
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Helper methods
    
    private function userExists($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>