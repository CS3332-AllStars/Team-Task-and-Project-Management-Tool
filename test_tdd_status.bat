@echo off
echo Testing current state with TDD test files...
echo.

echo Running existing working tests:
php tests/phpunit-9.phar --configuration phpunit.xml --testsuite unit --filter UserTest --verbose

echo.
echo.
echo Running TDD placeholder tests (should be skipped):
php tests/phpunit-9.phar --configuration phpunit.xml --testsuite unit --filter ProjectTest
php tests/phpunit-9.phar --configuration phpunit.xml --testsuite unit --filter TaskTest  
php tests/phpunit-9.phar --configuration phpunit.xml --testsuite unit --filter CommentTest

echo.
echo Summary:
echo - UserTest: Should have 21 passing tests
echo - ProjectTest, TaskTest, CommentTest: Should show skipped tests (TDD placeholders)
echo - Integration tests: Should have 12 passing AJAX tests
echo.
pause
