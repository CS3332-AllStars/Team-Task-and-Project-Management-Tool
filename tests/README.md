# PHPUnit Testing Setup for CS3332 AllStars
# Test-Driven Development Infrastructure

## Quick Setup (Windows/XAMPP)

### Option 1: Download PHPUnit PHAR (Recommended for beginners)
```bash
# Download PHPUnit
curl -O https://phar.phpunit.de/phpunit-9.phar

# Make it executable (Windows)
# No additional step needed

# Test installation
php phpunit-9.phar --version
```

### Option 2: Global Installation via Composer
```bash
# If you have Composer installed
composer global require phpunit/phpunit ^9

# Add to PATH: C:\Users\%USERNAME%\AppData\Roaming\Composer\vendor\bin
```

## Running Tests

### Run All Tests
```bash
# Using PHAR
php phpunit-9.phar

# Using global installation
phpunit

# Specific test suite
php phpunit-9.phar --testsuite Unit
php phpunit-9.phar --testsuite Integration
```

### Run Specific Test File
```bash
php phpunit-9.phar tests/Unit/UserTest.php
php phpunit-9.phar tests/Integration/AjaxEndpointsTest.php
```

### Run with Coverage (if Xdebug enabled)
```bash
php phpunit-9.phar --coverage-html coverage-report
```

## Test Structure

```
tests/
├── bootstrap.php          # Test environment setup
├── Unit/                  # Unit tests (isolated component testing)
│   └── UserTest.php      # User model validation tests
└── Integration/           # Integration tests (multiple components)
    └── AjaxEndpointsTest.php # AJAX endpoint testing
```

## What These Tests Cover

### Unit Tests (UserTest.php)
- ✅ Password validation (all requirements)
- ✅ User registration (success/failure cases)
- ✅ Authentication (login/logout)
- ✅ Duplicate prevention (username/email)
- ✅ Security edge cases (injection attempts)
- ✅ Input validation and sanitization

### Integration Tests (AjaxEndpointsTest.php)
- ✅ AJAX endpoint functionality
- ✅ Real-time validation (as user types)
- ✅ JavaScript compatibility (response format)
- ✅ Error handling and edge cases
- ✅ Performance testing (rapid requests)
- ✅ Security testing (special characters)

## Test-Driven Development Workflow

### 1. Red Phase (Write Failing Test)
```bash
# Write a test for new functionality
# Run test - it should fail
php phpunit-9.phar tests/Unit/UserTest.php
```

### 2. Green Phase (Make Test Pass)
```bash
# Implement minimum code to make test pass
# Run test - it should pass
php phpunit-9.phar tests/Unit/UserTest.php
```

### 3. Refactor Phase (Improve Code)
```bash
# Clean up code while keeping tests passing
# Run all tests to ensure no regression
php phpunit-9.phar
```

## Adding New Tests

### For New Features
1. Create test file in appropriate directory
2. Follow naming convention: `FeatureNameTest.php`
3. Extend PHPUnit\Framework\TestCase
4. Use setUp() method for test preparation
5. Write descriptive test method names

### Example Test Method
```php
/**
 * Test specific functionality
 * Covers: FR-XX (Functional Requirement)
 */
public function testSpecificFunctionality() {
    // Arrange
    $input = 'test data';
    
    // Act
    $result = $this->someMethod($input);
    
    // Assert
    $this->assertTrue($result['success']);
    $this->assertEquals('expected', $result['value']);
}
```

## Integration with Your Development Process

### Before Committing Code
```bash
# Run all tests
php phpunit-9.phar

# Fix any failures before commit
git add .
git commit -m "Add feature with tests"
```

### Team Workflow
1. **Pull latest code**: `git pull origin feature-branch`
2. **Run tests**: `php phpunit-9.phar` (ensure clean start)
3. **Write test for new feature** (TDD Red phase)
4. **Implement feature** (TDD Green phase)
5. **Refactor and optimize** (TDD Refactor phase)
6. **Run full test suite**: `php phpunit-9.phar`
7. **Commit and push**: Include tests with implementation

## Troubleshooting

### Common Issues
- **"PHPUnit not found"**: Check PATH or use full path to PHAR file
- **"Class not found"**: Ensure bootstrap.php includes all necessary files
- **Database errors**: Verify XAMPP MySQL is running and database exists
- **Permission errors**: Run command prompt as administrator if needed

### Test Database Issues
- Tests use the same database as development
- TestDatabaseHelper::resetDatabase() cleans between tests
- Ensure test data doesn't interfere with development

### Performance Issues
- Tests should complete in under 30 seconds total
- If slow, check database queries and network calls
- Use `@group slow` annotation for longer-running tests

This testing infrastructure supports your team's development process and ensures code quality while learning TDD principles.
