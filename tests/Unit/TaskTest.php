<?php
// CS3332 AllStars - Task Model Unit Tests  
// TDD: Tests written BEFORE implementation - THESE SHOULD FAIL FIRST

use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase {
    private $pdo;
    private $task;
    
    protected function setUp(): void {
        global $pdo;
        $this->pdo = $pdo;
        
        // Reset database before each test
        TestDatabaseHelper::resetDatabase($this->pdo);
        
        // TDD: Task model doesn't exist yet - these tests will FAIL
        $this->task = new Task($this->pdo);
    }
    
    // ===== TDD RED PHASE TESTS - SHOULD FAIL UNTIL IMPLEMENTED =====
    
    /**
     * Test successful task creation
     * Covers: FR-14 (Task creation with title and description)
     * TDD: This test will FAIL until Task class is implemented
     */
    public function testCreateTask_Success() {
        $projectId = $this->createTestProject();
        
        $result = $this->task->create($projectId, 'Test Task', 'Task description');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('task_id', $result);
        $this->assertEquals('To Do', $result['status']); // Default status
    }
    
    /**
     * Test task creation with missing title
     * Covers: Input validation requirements
     * TDD: This test will FAIL until validation is implemented
     */
    public function testCreateTask_MissingTitle() {
        $projectId = $this->createTestProject();
        
        $result = $this->task->create($projectId, '', 'Description');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('title', $result['message']);
    }
    
    /**
     * Test updating task status
     * Covers: FR-16 (Task status updates)
     */
    public function testUpdateStatus_Success() {
        $projectId = $this->createTestProject();
        $taskResult = $this->task->create($projectId, 'Test Task', 'Description');
        $taskId = $taskResult['task_id'];
        
        $result = $this->task->updateStatus($taskId, 'In Progress');
        
        $this->assertTrue($result['success']);
        
        // Verify status changed
        $task = $this->task->getById($taskId);
        $this->assertEquals('In Progress', $task['status']);
    }
    
    /**
     * Test assigning task to team member
     * Covers: FR-15 (Task assignment)
     */
    public function testAssignTask_Success() {
        $projectOwnerId = $this->createTestUser();
        $project = new Project($this->pdo);
        $projectResult = $project->create('Test Project', 'Test description', $projectOwnerId);
        $projectId = $projectResult['project_id'];
        
        $taskResult = $this->task->create($projectId, 'Test Task', 'Description');
        $taskId = $taskResult['task_id'];
        
        // Add user as member to the project first
        $userId = $this->createTestUser();
        $project->addMember($projectId, $userId, 'member');
        
        $result = $this->task->assignToUser($taskId, $userId);
        
        $this->assertTrue($result['success']);
    }
    
    // ===== HELPER METHODS =====
    
    private function createTestUser() {
        $user = new User($this->pdo);
        $result = $user->register('testuser' . uniqid(), 'test' . uniqid() . '@example.com', 'ValidPass123!', 'Test User');
        return $result['user_id'];
    }
    
    private function createTestProject() {
        $project = new Project($this->pdo);
        $userId = $this->createTestUser();
        $result = $project->create('Test Project', 'Test description', $userId);
        return $result['project_id'];
    }
}
?>