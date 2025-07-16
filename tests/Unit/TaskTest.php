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
        $this->assertStringContainsString('Title is required', $result['message']);
    }
    
    /**\n     * Test updating task status\n     * Covers: FR-16 (Task status updates)\n     * TDD: This test will FAIL until updateStatus is implemented\n     */\n    public function testUpdateStatus_Success() {\n        $projectId = $this->createTestProject();\n        $taskResult = $this->task->create($projectId, 'Test Task', 'Description');\n        $taskId = $taskResult['task_id'];\n        \n        $result = $this->task->updateStatus($taskId, 'In Progress');\n        \n        $this->assertTrue($result['success']);\n        \n        // Verify status changed\n        $task = $this->task->getById($taskId);\n        $this->assertEquals('In Progress', $task['status']);\n    }\n    \n    /**\n     * Test assigning task to team member\n     * Covers: FR-15 (Task assignment)\n     * TDD: This test will FAIL until assignToUser is implemented\n     */\n    public function testAssignTask_Success() {\n        $projectId = $this->createTestProject();\n        $taskResult = $this->task->create($projectId, 'Test Task', 'Description');\n        $taskId = $taskResult['task_id'];\n        $userId = $this->createTestUser();\n        \n        $result = $this->task->assignToUser($taskId, $userId);\n        \n        $this->assertTrue($result['success']);\n    }\n    \n    // ===== HELPER METHODS =====\n    \n    private function createTestUser() {\n        $user = new User($this->pdo);\n        $result = $user->register('testuser' . uniqid(), 'test' . uniqid() . '@example.com', 'ValidPass123!', 'Test User');\n        return $result['user_id'];\n    }\n    \n    private function createTestProject() {\n        // Use the failing Project class - this creates dependencies between TDD tests\n        $project = new Project($this->pdo);\n        $userId = $this->createTestUser();\n        $result = $project->create('Test Project', 'Test description', $userId);\n        return $result['project_id'];\n    }\n}\n?>