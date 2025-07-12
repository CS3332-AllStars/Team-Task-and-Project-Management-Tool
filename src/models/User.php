<?php
// CS3332 AllStars Team Task & Project Management System  
// User Model - CS3-11A: Registration & Password Security
// Handles user registration, authentication, and security

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Register a new user with secure password hashing
     * CS3-11A requirement: Hash with password_hash($pwd, PASSWORD_DEFAULT)
     */
    public function register($username, $email, $password, $name) {
        // Check for duplicate username or email
        if ($this->isDuplicate($username, $email)) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Validate password strength
        $passwordValidation = $this->validatePasswordStrength($password);
        if (!$passwordValidation['valid']) {
            return ['success' => false, 'message' => $passwordValidation['message']];
        }
        
        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, name, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$username, $email, $passwordHash, $name]);
            
            return [
                'success' => true, 
                'message' => 'User registered successfully',
                'user_id' => $this->pdo->lastInsertId()
            ];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check for duplicate username or email
     * CS3-11A requirement: SELECT * FROM users WHERE username = ? OR email = ?
     */
    public function isDuplicate($username, $email) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Validate password strength
     * CS3-11A requirement: minimum length, symbols
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        // Minimum length check
        if (strlen($password) < 8) {
            $errors[] = "At least 8 characters long";
        }
        
        // Require at least one number
        if (!preg_match('/\d/', $password)) {
            $errors[] = "At least one number";
        }
        
        // Require at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "At least one uppercase letter";
        }
        
        // Require at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "At least one lowercase letter";
        }
        
        // Require at least one symbol
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "At least one symbol (!@#$%^&*)";
        }
        
        return [
            'valid' => empty($errors),
            'message' => implode('. ', $errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Authenticate user login
     * CS3-11A requirement: Login verified with password_verify()
     */
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id, username, email, password_hash, name 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Remove password hash from returned data
                unset($user['password_hash']);
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("
            SELECT user_id, username, email, name, created_at 
            FROM users 
            WHERE user_id = ?
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * AJAX endpoint for checking username availability
     */
    public function checkUsernameAvailability($username) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return ['available' => $stmt->rowCount() === 0];
    }
    
    /**
     * AJAX endpoint for checking email availability  
     */
    public function checkEmailAvailability($email) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return ['available' => $stmt->rowCount() === 0];
    }
}
?>