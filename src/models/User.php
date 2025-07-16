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
        // Sanitize inputs (allow some content, just remove dangerous HTML)
        $username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
        
        // Validate input lengths to prevent database errors
        if (strlen($username) > 50) {
            return ['success' => false, 'message' => 'Username too long (max 50 characters)'];
        }
        if (strlen($email) > 100) {
            return ['success' => false, 'message' => 'Email too long (max 100 characters)'];
        }
        if (strlen($name) > 100) {
            return ['success' => false, 'message' => 'Name too long (max 100 characters)'];
        }
        if (empty($username) || empty($email) || empty($name)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Validate password strength FIRST (before any DB queries)
        $passwordValidation = $this->validatePasswordStrength($password);
        if (!$passwordValidation['valid']) {
            return ['success' => false, 'message' => 'Password must have: ' . $passwordValidation['message']];
        }
        
        // Check for duplicate username or email
        if ($this->isDuplicate($username, $email)) {
            return ['success' => false, 'message' => 'Username or email already exists'];
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
            $errors[] = "Password must be at least 8 characters long";
        }
        
        // Require at least one number
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Password must have at least one number";
        }
        
        // Require at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must have at least one uppercase letter";
        }
        
        // Require at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must have at least one lowercase letter";
        }
        
        // Require at least one symbol
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must have at least one symbol (!@#$%^&*)";
        }
        
        if (empty($errors)) {
            return [
                'valid' => true,
                'message' => '',
                'errors' => []
            ];
        }
        
        // Format message for single vs multiple errors
        $message = count($errors) === 1 ? $errors[0] : implode('. ', $errors);
        
        return [
            'valid' => false,
            'message' => $message,
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