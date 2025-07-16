# CS3-19D: Test Fixtures & Database Reset Strategy

## ✅ **COMPLETED** - Documentation and Usage Guide

This document covers the completed implementation of CS3-19D, providing reliable test fixtures and database reset functionality for the CS3332 AllStars project.

## 📁 File Structure

```
tests/
├── fixtures/
│   ├── test_seed.sql           # Additional test data extending sample_data.sql
│   └── reset_database.php      # Database reset utility class
├── bootstrap.php               # Enhanced with fixture integration
└── README.md                   # This documentation

database/
└── sample_data.sql             # Primary fixture data (existing, high quality)
```

## 🎯 What Was Implemented

### 1. **Enhanced Existing Fixtures** ✅
- **Kept** the excellent `database/sample_data.sql` as the primary fixture
- **Added** `tests/fixtures/test_seed.sql` with edge cases and boundary testing data
- **Created** modular approach: main data + test-specific additions

### 2. **Robust Reset Mechanism** ✅  
- **Multi-interface** reset utility: CLI, programmatic, and web access
- **Two reset modes**: Full fixtures vs. minimal state
- **Error handling** with proper validation and verification
- **Security** considerations for production environments

### 3. **PHPUnit Integration** ✅
- **Enhanced** `tests/bootstrap.php` with new fixture system  
- **Backward compatibility** maintained for existing tests
- **Verification functions** to ensure test environment readiness

## 🚀 Usage Guide

### **Method 1: Command Line (Recommended for Development)**

```bash
# Reset to full test fixtures (default)
cd tests/fixtures
php reset_database.php

# Reset to minimal state only  
php reset_database.php --minimal

# Check current database state
php reset_database.php --verify

# Show help
php reset_database.php --help
```

### **Method 2: Programmatic (PHPUnit Tests)**

```php
// In your PHPUnit test class
class MyTest extends PHPUnit\Framework\TestCase 
{
    public function setUp(): void 
    {
        // Reset to full fixtures before each test
        TestDatabaseHelper::resetToFixtures();
        
        // Or reset to minimal state
        // TestDatabaseHelper::resetToMinimal();
    }
    
    public function testWithRealData() 
    {
        // Test runs with comprehensive fixture data
        // - 7+ users including CS3332 team members  
        // - 5+ projects with varying complexity
        // - 17+ tasks in different states
        // - Comments, notifications, edge cases
    }
}
```

### **Method 3: Web Interface (Development Only)**

```http
# Reset to full fixtures
GET /tests/fixtures/reset_database.php?token=dev_reset&fixtures=true

# Reset to minimal state
GET /tests/fixtures/reset_database.php?token=dev_reset&minimal=true

# Returns JSON response with statistics
```

## 📊 Fixture Data Overview

### **Primary Data (sample_data.sql)**
- **Users**: 7 accounts including CS3332 AllStars team members
- **Projects**: 5 projects with realistic complexity variation
- **Tasks**: 17 tasks covering all status states (To Do, In Progress, Done)
- **Comments**: 13 comments demonstrating collaboration workflows  
- **Memberships**: Various admin/member role assignments
- **Notifications**: 14 notifications covering all types

### **Test-Specific Data (test_seed.sql)**
- **Edge Case Users**: Long names, Unicode characters, security test accounts
- **Boundary Projects**: Empty projects, single-task projects, long descriptions
- **Edge Case Tasks**: Overdue tasks, due-today tasks, unassigned tasks
- **Security Test Data**: XSS attempts, SQL injection attempts (safely stored)
- **Cleanup Test Data**: Old notifications for testing cleanup functionality

## 🔧 Technical Implementation Details

### **Reset Mechanism Features**
- **Clean Truncation**: Proper foreign key handling, no cascade issues
- **Auto-increment Reset**: Maintains consistent IDs across test runs
- **Error Recovery**: Continues execution even if individual statements fail
- **Verification**: Confirms fixture data loaded correctly
- **Performance**: Fast execution suitable for frequent test runs

### **Security Considerations**
- **Development Only**: Web interface disabled in production
- **Token Protection**: Requires dev token for web access
- **Input Sanitization**: All fixture data properly escaped
- **SQL Injection Safe**: Uses PDO prepared statements where applicable
- **Error Logging**: Database errors logged but don't expose sensitive info

### **Database Schema Compatibility**
- **Maintains Referential Integrity**: Proper foreign key relationships
- **Unicode Support**: UTF-8 encoding for international characters
- **Timestamp Consistency**: Uses NOW() and proper date functions
- **Auto-increment Management**: Prevents ID conflicts across test runs

## 🧪 Testing Scenarios Supported

### **Authentication Testing**
```php
// All users have password 'password123' (hashed)
$testUsers = [
    'james_ward' => 'james.ward@allstars.edu',      // Team lead, admin
    'summer_hill' => 'summer.hill@allstars.edu',    // Team member, various roles
    'juan_ledet' => 'juan.ledet@allstars.edu',      // Team member
    'alaric_higgins' => 'alaric.higgins@allstars.edu', // Team member
    'test_member' => 'member@test.com',             // Generic member
    'project_admin' => 'admin@test.com',            // Generic admin
    'security_test' => 'security@test.com'          // Security testing
];
```

### **Project Management Testing**
```php
// Projects with different complexities
$testProjects = [
    1 => 'CS3332 Software Engineering Project',     // Full team, active development
    2 => 'Website Redesign Project',                // Mixed roles, in progress
    3 => 'Simple Bug Tracking',                     // Single admin, simple workflow
    4 => 'Complex Multi-Team Initiative',           // Large team, complex dependencies
    100 => 'Empty Project',                         // Edge case: no tasks/members
    102 => 'Security Test Project'                  // Security testing scenarios
];
```

### **Task Workflow Testing**
```php
// Tasks covering all states and edge cases
$testScenarios = [
    'completed_tasks' => [1, 2, 7, 10, 12],        // Done status
    'active_tasks' => [3, 6, 8, 11, 13, 16],       // In Progress
    'pending_tasks' => [4, 5, 9, 14, 15, 17],      // To Do
    'overdue_tasks' => [102],                       // Past due date
    'due_today' => [103],                           // Due today
    'unassigned' => [5, 15, 17, 101],              // No assignee
    'multi_assigned' => [13, 14]                   // Multiple assignees
];
```

### **Security Testing Scenarios**
```php
// Pre-loaded security test data (safely stored, will be sanitized)
$securityTests = [
    'xss_attempts' => 'Comments with <script> tags',
    'sql_injection' => 'Comments with SQL injection attempts', 
    'unicode_content' => 'International characters and emojis',
    'long_content' => 'Extremely long descriptions testing field limits',
    'special_chars' => 'Various special characters and symbols'
];
```

## 🔍 Verification and Debugging

### **Quick Health Check**
```bash
# Verify fixture data loaded correctly
php reset_database.php --verify
```

**Expected Output:**
```
📊 Current Database Statistics:
   users: 10+ records
   projects: 8+ records  
   tasks: 20+ records
   comments: 15+ records
   project_memberships: 10+ records
   task_assignments: 15+ records
   notifications: 15+ records
```

### **Common Issues & Solutions**

**Issue**: "Config file not found"
```bash
# Solution: Ensure config.php exists and has database constants
cp includes/config.php.template includes/config.php
# Edit config.php with your database credentials
```

**Issue**: "Database connection failed"
```bash
# Solution: Check XAMPP/WAMP is running and credentials are correct
# Verify database exists:
mysql -u root -p
SHOW DATABASES;
USE ttpm_system;
```

**Issue**: "Verification failed: users has 0 records"
```bash
# Solution: Check if sample_data.sql path is correct
# Manual verification:
mysql -u root -p ttpm_system < database/sample_data.sql
```

## 📋 Team Workflow Integration

### **Daily Development Workflow**
1. **Start Development Session**:
   ```bash
   cd tests/fixtures
   php reset_database.php  # Fresh fixtures
   ```

2. **Run Tests** (automated reset):
   ```bash
   cd ../
   php phpunit-9.phar      # Tests auto-reset between runs
   ```

3. **Manual Testing**:
   - Use fixture user accounts for login testing
   - Projects 1-4 have realistic data for feature testing
   - Project 100+ are edge cases for boundary testing

### **Before Team Meetings**
```bash
# Ensure everyone starts with same data
php reset_database.php
echo "Database reset to standard fixtures for team demo"
```

### **Before Major Feature Development** 
```bash
# Start with minimal state for clean TDD
php reset_database.php --minimal
echo "Minimal state ready for test-driven development"
```

## 🎓 Academic Compliance

### **Test Plan Integration**
This fixture system directly supports the CS3332 AllStars Software Test Plan v1.0:

- ✅ **Component Testing**: Reliable data for unit tests
- ✅ **Integration Testing**: End-to-end workflow validation
- ✅ **Boundary Testing**: Edge cases and limit testing
- ✅ **Security Testing**: XSS, injection, and access control
- ✅ **Performance Testing**: Cleanup and optimization scenarios

### **Deliverable Support**
- **Test Execution Logs**: Consistent data for repeatable results
- **Bug Tracking**: Known good state for defect reproduction  
- **Coverage Analysis**: Comprehensive scenarios for requirement validation
- **QA Documentation**: Professional-grade test data management

## 📈 Next Steps

With CS3-19D now complete, the testing infrastructure supports:

1. **CS3-19B Expansion**: Add tests for Project, Task, Comment models as they're developed
2. **CS3-19C Frontend QA**: Manual testing with consistent fixture data
3. **CS3-19F Security Testing**: Built-in security test scenarios ready to use
4. **CS3-19E GitHub Actions**: Automated fixture reset in CI/CD pipeline
5. **CS3-19G Academic Deliverables**: Consistent data for final documentation

---

**CS3-19D Status**: ✅ **COMPLETE**

**Definition of Done Verification**:
- ✅ All developers can reset their local DB
- ✅ Fixtures work across different features (auth, task, comment)
- ✅ Documented in `/tests/README.md`
- ✅ Team workflow integration complete
- ✅ Academic compliance verified

**Ready for**: Team cross-training and parallel feature development using TDD approach.
