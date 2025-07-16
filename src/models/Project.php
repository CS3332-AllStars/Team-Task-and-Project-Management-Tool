<?php
// CS3332 AllStars Team Task & Project Management System  
// Project Model - TDD RED PHASE: Minimal class that will make tests FAIL
// This is intentionally incomplete to demonstrate TDD

class Project {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // TDD RED PHASE: This method doesn't exist yet, so tests will fail
    // Students will implement this to make tests pass (GREEN PHASE)
}
?>