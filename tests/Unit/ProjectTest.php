<?php
// CS3332 AllStars - Project Model Unit Tests
// TDD: Tests written BEFORE implementation - THESE SHOULD FAIL FIRST

use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase {
    private $pdo;
    private $project;
    
    protected function setUp(): void {
        global $pdo;
        $this->pdo = $pdo;
        
        // Reset database before each test
        TestDatabaseHelper::resetDatabase($this->pdo);
        
        // TDD: Project model doesn't exist yet - these tests will FAIL
        $this->project = new Project($this->pdo);
    }
    
    // ===== TDD RED PHASE TESTS - SHOULD FAIL UNTIL IMPLEMENTED =====
    
    /**
     * Test successful project creation
     * Covers: FR-8 (Project creation with title and description)
     * TDD: This test will FAIL until Project class is implemented
     */
    public function testCreateProject_Success() {
        $userId = $this->createTestUser();
        
        $result = $this->project->create('Test Project', 'Project description', $userId);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('project_id', $result);
        $this->assertIsNumeric($result['project_id']);
    }
    
    /**
     * Test project creation with missing title
     * Covers: Input validation requirements
     * TDD: This test will FAIL until validation is implemented
     */
    public function testCreateProject_MissingTitle() {
        $userId = $this->createTestUser();
        
        $result = $this->project->create('', 'Description', $userId);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Title is required', $result['message']);
    }
    
    /**
     * Test adding team member to project
     * Covers: FR-5 (User invitations to projects)
     * TDD: This test will FAIL until membership methods are implemented
     */
    public function testAddMember_Success() {
        $ownerId = $this->createTestUser();
        $memberId = $this->createTestUser();
        
        // Create project first
        $projectResult = $this->project->create('Test Project', 'Description', $ownerId);
        $projectId = $projectResult['project_id'];
        
        // Add member
        $result = $this->project->addMember($projectId, $memberId, 'member');
        
        $this->assertTrue($result['success']);
    }
    
    /**
     * Test getting user's accessible projects
     * Covers: FR-9 (Project dashboard display)
     * TDD: This test will FAIL until getUserProjects is implemented
     */
    public function testGetUserProjects_Success() {
        $userId = $this->createTestUser();
        
        // Create a project for this user
        $this->project->create('Test Project', 'Description', $userId);
        
        $projects = $this->project->getUserProjects($userId);
        
        $this->assertIsArray($projects);
        $this->assertGreaterThan(0, count($projects));
        $this->assertEquals('Test Project', $projects[0]['title']);
    }
    
    // ===== HELPER METHODS =====
    
    /**
     * Helper method to create test user (reuse User class that works)
     */
    private function createTestUser() {
        $user = new User($this->pdo);
        $result = $user->register('testuser' . uniqid(), 'test' . uniqid() . '@example.com', 'ValidPass123!', 'Test User');
        return $result['user_id'];
    }
}
?>