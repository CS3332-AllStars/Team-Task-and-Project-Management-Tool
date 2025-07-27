<?php

use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    public function testExample()
    {
        $this->assertTrue(true); // A simple assertion that should always pass
    }

    // You can add another test that uses the database connection defined in test-config.php
    /*
    public function testDatabaseConnection()
    {
        // Assuming your DB connection is available, e.g., through a global variable or injected
        global $pdo; // Or however you access your DB connection

        $this->assertInstanceOf(PDO::class, $pdo); // Assert that $pdo is a PDO object
    }
    */
}