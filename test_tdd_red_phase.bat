@echo off
echo ===== TESTING PROPER TDD SETUP =====
echo.
echo This will demonstrate the TDD RED phase - tests should FAIL
echo because the Project and Task classes don't have the required methods yet.
echo.

echo Running ProjectTest (should FAIL - TDD RED phase):
php tests/phpunit-9.phar --configuration phpunit.xml --filter ProjectTest::testCreateProject_Success --verbose

echo.
echo Running TaskTest (should FAIL - TDD RED phase):  
php tests/phpunit-9.phar --configuration phpunit.xml --filter TaskTest::testCreateTask_Success --verbose

echo.
echo ===== TDD WORKFLOW EXPLANATION =====
echo.
echo RED PHASE (Current): Tests fail because methods don't exist
echo - Project::create() method missing
echo - Task::create() method missing
echo.
echo GREEN PHASE (Next): Implement minimum code to make tests pass
echo - Add create() method to Project class
echo - Add create() method to Task class  
echo.
echo REFACTOR PHASE (Later): Improve code while keeping tests passing
echo - Add validation, error handling, optimization
echo.
echo This is proper Test-Driven Development!
pause
