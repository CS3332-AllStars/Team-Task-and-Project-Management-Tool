<?php
// CS3332 AllStars - AJAX Endpoint Integration Tests
// Test the actual AJAX endpoints that JavaScript calls

use PHPUnit\Framework\TestCase;

class AjaxEndpointsTest extends TestCase {
    private $pdo;
    
    protected function setUp(): void {
        global $pdo;
        $this->pdo = $pdo;
        
        // Reset database before each test
        TestDatabaseHelper::resetDatabase($this->pdo);
    }
    
    /**
     * Helper method to simulate AJAX POST request
     */
    private function simulateAjaxPost($type, $value) {
        global $pdo;
        $pdo = $this->pdo; // Make test PDO available globally
        
        // Simulate the $_POST data
        $_POST['type'] = $type;
        $_POST['value'] = $value;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Capture output from the AJAX endpoint
        ob_start();
        include __DIR__ . '/../../ajax/check_availability.php';
        $output = ob_get_clean();
        
        // Clean up
        unset($_POST['type'], $_POST['value']);
        
        return json_decode($output, true);
    }
    
    // ===== USERNAME AVAILABILITY TESTS =====
    
    /**
     * Test username availability checking via AJAX
     * Covers: Real-time validation functionality
     */
    public function testAjax_UsernameAvailable() {
        $result = $this->simulateAjaxPost('username', 'newuser');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertTrue($result['available']);
    }
    
    public function testAjax_UsernameTaken() {
        // Create a user first
        TestDatabaseHelper::createTestUser($this->pdo, 'takenuser', 'taken@example.com');
        
        $result = $this->simulateAjaxPost('username', 'takenuser');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertFalse($result['available']);
    }
    
    // ===== EMAIL AVAILABILITY TESTS =====
    
    /**
     * Test email availability checking via AJAX
     */
    public function testAjax_EmailAvailable() {
        $result = $this->simulateAjaxPost('email', 'new@example.com');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertTrue($result['available']);
    }
    
    public function testAjax_EmailTaken() {
        // Create a user first
        TestDatabaseHelper::createTestUser($this->pdo, 'testuser', 'taken@example.com');
        
        $result = $this->simulateAjaxPost('email', 'taken@example.com');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertFalse($result['available']);
    }
    
    // ===== PASSWORD VALIDATION TESTS =====
    
    /**
     * Test password validation via AJAX
     * This is the core functionality we just implemented
     */
    public function testAjax_PasswordValidation_Valid() {
        $result = $this->simulateAjaxPost('password', 'ValidPass123!');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testAjax_PasswordValidation_Weak() {
        $result = $this->simulateAjaxPost('password', 'weak');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertFalse($result['valid']);
        $this->assertGreaterThan(0, count($result['errors']));
        $this->assertNotEmpty($result['message']);
    }
    
    /**
     * Test specific password requirements via AJAX
     */
    public function testAjax_PasswordValidation_Requirements() {
        $testCases = [
            'short' => ['password' => 'Abc1!', 'shouldContain' => '8 characters'],
            'no_upper' => ['password' => 'lowercase123!', 'shouldContain' => 'uppercase'],
            'no_lower' => ['password' => 'UPPERCASE123!', 'shouldContain' => 'lowercase'],
            'no_number' => ['password' => 'NoNumber!', 'shouldContain' => 'number'],
            'no_symbol' => ['password' => 'NoSymbol123', 'shouldContain' => 'symbol']
        ];
        
        foreach ($testCases as $testName => $testData) {
            $result = $this->simulateAjaxPost('password', $testData['password']);
            
            $this->assertFalse($result['valid'], "Test '$testName' should fail validation");
            $this->assertStringContainsString(
                $testData['shouldContain'], 
                $result['message'], 
                "Test '$testName' should mention missing requirement"
            );
        }
    }
    
    // ===== ERROR HANDLING TESTS =====
    
    /**
     * Test invalid request types
     */
    public function testAjax_InvalidType() {
        $result = $this->simulateAjaxPost('invalid', 'value');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }
    
    /**
     * Test empty values
     */
    public function testAjax_EmptyValues() {
        $types = ['username', 'email', 'password'];
        
        foreach ($types as $type) {
            $result = $this->simulateAjaxPost($type, '');
            
            // Should handle empty values gracefully
            $this->assertIsArray($result, "Type '$type' should handle empty values");
        }
    }
    
    /**
     * Test special characters and potential injection
     */
    public function testAjax_SpecialCharacters() {
        $specialInputs = [
            "'OR'1'='1",
            "<script>alert('xss')</script>",
            "'; DROP TABLE users; --",
            "null",
            "undefined",
            str_repeat('a', 1000) // Very long string
        ];
        
        foreach ($specialInputs as $input) {
            $result = $this->simulateAjaxPost('username', $input);
            
            // Should not cause errors or security issues
            $this->assertIsArray($result, "Input '$input' should not break the endpoint");
            $this->assertArrayHasKey('available', $result);
        }
    }
    
    // ===== PERFORMANCE TESTS =====
    
    /**
     * Test response time for multiple requests
     * Simulates the rapid typing scenario
     */
    public function testAjax_MultipleRapidRequests() {
        $passwords = [
            'P', 'Pa', 'Pas', 'Pass', 'Pass1', 'Pass12', 'Pass123', 'Pass123!'
        ];
        
        $startTime = microtime(true);
        
        foreach ($passwords as $password) {
            $result = $this->simulateAjaxPost('password', $password);
            $this->assertIsArray($result, "Password '$password' should return valid response");
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should complete 8 requests in reasonable time (under 1 second)
        $this->assertLessThan(1.0, $totalTime, "Multiple requests should complete quickly");
    }
    
    // ===== INTEGRATION WITH AUTH.JS TESTS =====
    
    /**
     * Test the exact format expected by auth.js
     * This ensures our AJAX endpoints match what JavaScript expects
     */
    public function testAjax_JavaScriptCompatibility() {
        // Test password validation response format
        $result = $this->simulateAjaxPost('password', 'TestPass123!');
        
        // These are the fields auth.js expects
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsArray($result['errors']);
        $this->assertIsBool($result['valid']);
        $this->assertIsString($result['message']);
        
        // Test availability response format
        $result = $this->simulateAjaxPost('username', 'testuser');
        
        $this->assertArrayHasKey('available', $result);
        $this->assertIsBool($result['available']);
    }
}
?>
