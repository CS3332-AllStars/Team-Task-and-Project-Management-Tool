<?php
// CS3332 AllStars - User Model Unit Tests
// Test password validation, registration, and authentication

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase {
    private $pdo;
    private $user;
    
    protected function setUp(): void {
        global $pdo;
        $this->pdo = $pdo;
        $this->user = new User($this->pdo);
        
        // Reset database before each test
        TestDatabaseHelper::resetDatabase($this->pdo);
    }
    
    // ===== PASSWORD VALIDATION TESTS =====
    
    /**
     * Test password validation with valid strong password
     * Covers: FR-1, FR-2 (User registration with secure password)
     */
    public function testValidatePasswordStrength_ValidPassword() {
        $password = 'ValidPass123!';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['message']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test password validation with weak passwords
     * Tests each individual requirement failure
     */
    public function testValidatePasswordStrength_TooShort() {
        $password = 'Weak1!';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('8 characters', $result['message']);
        $this->assertContains('Password must be at least 8 characters long', $result['errors']);
    }
    
    public function testValidatePasswordStrength_NoUppercase() {
        $password = 'lowercase123!';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('uppercase', $result['message']);
    }
    
    public function testValidatePasswordStrength_NoLowercase() {
        $password = 'UPPERCASE123!';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('lowercase', $result['message']);
    }
    
    public function testValidatePasswordStrength_NoNumber() {
        $password = 'NoNumberPass!';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('number', $result['message']);
    }
    
    public function testValidatePasswordStrength_NoSymbol() {
        $password = 'NoSymbolPass123';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('symbol', $result['message']);
    }
    
    /**
     * Test edge cases and security concerns
     */
    public function testValidatePasswordStrength_EmptyPassword() {
        $password = '';
        $result = $this->user->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertGreaterThan(0, count($result['errors']));
    }
    
    public function testValidatePasswordStrength_SQLInjectionAttempt() {
        $password = "' OR '1'='1"; // SQL injection attempt
        $result = $this->user->validatePasswordStrength($password);
        
        // Should still validate as normal password (no special SQL handling)
        $this->assertFalse($result['valid']); // Likely missing uppercase, numbers
    }
    
    // ===== USER REGISTRATION TESTS =====
    
    /**
     * Test successful user registration
     * Covers: FR-1 (User registration)
     */
    public function testRegister_Success() {
        $result = $this->user->register('testuser', 'test@example.com', 'ValidPass123!', 'Test User');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertIsNumeric($result['user_id']);
    }
    
    /**
     * Test duplicate username prevention
     * Covers: FR-1 (Unique username requirement)
     */
    public function testRegister_DuplicateUsername() {
        // Register first user
        $this->user->register('testuser', 'test1@example.com', 'ValidPass123!', 'Test User 1');
        
        // Try to register second user with same username
        $result = $this->user->register('testuser', 'test2@example.com', 'ValidPass123!', 'Test User 2');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }
    
    /**
     * Test duplicate email prevention
     * Covers: FR-1 (Unique email requirement)
     */
    public function testRegister_DuplicateEmail() {
        // Register first user
        $this->user->register('testuser1', 'test@example.com', 'ValidPass123!', 'Test User 1');
        
        // Try to register second user with same email
        $result = $this->user->register('testuser2', 'test@example.com', 'ValidPass123!', 'Test User 2');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }
    
    /**
     * Test registration with weak password
     * Covers: Password strength enforcement during registration
     */
    public function testRegister_WeakPassword() {
        $result = $this->user->register('testuser', 'test@example.com', 'weak', 'Test User');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Password must have', $result['message']);
    }
    
    // ===== USER AUTHENTICATION TESTS =====
    
    /**
     * Test successful login
     * Covers: FR-3 (User authentication)
     */
    public function testLogin_Success() {
        // Register user first
        $this->user->register('testuser', 'test@example.com', 'ValidPass123!', 'Test User');
        
        // Test login
        $result = $this->user->login('testuser', 'ValidPass123!');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertArrayNotHasKey('password_hash', $result['user']); // Security check
    }
    
    /**
     * Test login with email instead of username
     * Covers: FR-3 (Login with email or username)
     */
    public function testLogin_WithEmail() {
        // Register user first
        $this->user->register('testuser', 'test@example.com', 'ValidPass123!', 'Test User');
        
        // Test login with email
        $result = $this->user->login('test@example.com', 'ValidPass123!');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('testuser', $result['user']['username']);
    }
    
    /**
     * Test login failure cases
     */
    public function testLogin_InvalidUsername() {
        $result = $this->user->login('nonexistent', 'ValidPass123!');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid credentials', $result['message']);
    }
    
    public function testLogin_InvalidPassword() {
        // Register user first
        $this->user->register('testuser', 'test@example.com', 'ValidPass123!', 'Test User');
        
        // Test with wrong password
        $result = $this->user->login('testuser', 'WrongPassword');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid credentials', $result['message']);
    }
    
    // ===== AVAILABILITY CHECK TESTS =====
    
    /**
     * Test username availability checking
     * Covers: AJAX endpoint functionality
     */
    public function testCheckUsernameAvailability() {
        // Check available username
        $result = $this->user->checkUsernameAvailability('available');
        $this->assertTrue($result['available']);
        
        // Register user and check again
        $this->user->register('taken', 'test@example.com', 'ValidPass123!', 'Test User');
        $result = $this->user->checkUsernameAvailability('taken');
        $this->assertFalse($result['available']);
    }
    
    /**
     * Test email availability checking
     * Covers: AJAX endpoint functionality
     */
    public function testCheckEmailAvailability() {
        // Check available email
        $result = $this->user->checkEmailAvailability('available@example.com');
        $this->assertTrue($result['available']);
        
        // Register user and check again
        $this->user->register('testuser', 'taken@example.com', 'ValidPass123!', 'Test User');
        $result = $this->user->checkEmailAvailability('taken@example.com');
        $this->assertFalse($result['available']);
    }
    
    // ===== EDGE CASES AND SECURITY TESTS =====
    
    /**
     * Test input sanitization
     */
    public function testRegister_HTMLInjection() {
        $result = $this->user->register(
            '<b>testuser</b>',  // Shorter HTML that sanitizes to safe username
            'test@example.com',
            'ValidPass123!',
            '<i>Test User</i>'  // Shorter HTML for name
        );
        
        // Should succeed but sanitize the input
        $this->assertTrue($result['success']);
    }
    
    /**
     * Test very long inputs
     */
    public function testRegister_LongInputs() {
        $longString = str_repeat('a', 1000);
        $result = $this->user->register($longString, 'test@example.com', 'ValidPass123!', 'Test User');
        
        // Should fail due to database constraints
        $this->assertFalse($result['success']);
    }
    
    /**
     * Test special characters in password
     */
    public function testValidatePasswordStrength_SpecialCharacters() {
        $passwords = [
            'ValidPass123!@#$%^&*()',
            'ValidPass123!',
            'ValidPass123@',
            'ValidPass123#',
            'ValidPass123$',
            'ValidPass123%'
        ];
        
        foreach ($passwords as $password) {
            $result = $this->user->validatePasswordStrength($password);
            $this->assertTrue($result['valid'], "Password '$password' should be valid");
        }
    }
}
?>
