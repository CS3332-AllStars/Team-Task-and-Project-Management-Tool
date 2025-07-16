<?php
// CS3332 AllStars Team Task & Project Management System  
// Task Model - TDD RED PHASE: Minimal class that will make tests FAIL
// This is intentionally incomplete to demonstrate TDD

class Task {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // TDD RED PHASE: Methods don't exist yet, so tests will fail
    // Students will implement these to make tests pass (GREEN PHASE)
}
?>