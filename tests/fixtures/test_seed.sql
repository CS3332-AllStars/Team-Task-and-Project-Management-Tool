-- CS3332 AllStars Testing Infrastructure
-- Additional Test Fixtures for Edge Cases and Security Testing
-- Note: sample_data.sql is loaded first by reset_database.php

-- Additional test-specific users for edge case testing
INSERT INTO users (user_id, username, email, password_hash, name) VALUES
(100, 'edge_user_empty', 'empty@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Edge Case Empty'),
(101, 'edge_user_long', 'verylongusernamethatmightcauseissues@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Edge Case Very Long Name That Might Cause Display Issues'),
(102, 'security_test', 'security@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Security Test User'),
(103, 'unicode_test', 'unicode@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Üñíçøðé Tëst Ñämé');

-- Edge case projects
INSERT INTO projects (project_id, title, description, created_date) VALUES
(100, 'Empty Project', 'Project with no tasks or members for testing edge cases', NOW()),
(101, 'Single Task Project', 'Minimal project with only one task', NOW()),
(102, 'Security Test Project', 'Project for testing input validation and security', NOW()),
(103, 'Very Long Project Title That Tests UI Layout and Database Limits', 'This project has an extremely long description that tests how the system handles verbose content. It includes multiple paragraphs, special characters (!@#$%^&*), and formatting that might break layouts. This helps ensure robust handling of user-generated content in real-world scenarios where users might paste large amounts of text or documentation.', NOW());

-- Edge case project memberships
INSERT INTO project_memberships (project_id, user_id, role, joined_at) VALUES
(101, 100, 'admin', NOW()),           -- Empty user as admin
(102, 102, 'admin', NOW()),           -- Security test admin
(102, 103, 'member', NOW()),          -- Unicode user as member  
(103, 101, 'admin', NOW());           -- Long name user as admin

-- Edge case tasks for boundary testing
INSERT INTO tasks (task_id, project_id, title, description, status, assigned_by, assigned_date, due_date, created_at) VALUES
-- Minimal/empty scenarios
(100, 101, 'Single Task', 'Only task in this project', 'To Do', 100, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW()),
(101, 100, 'Orphaned Task', 'Task in project with no members', 'To Do', NULL, NULL, DATE_ADD(NOW(), INTERVAL 1 DAY), NOW()),

-- Overdue tasks for deadline testing  
(102, 1, 'Overdue Critical Task', 'Task that is past due for testing deadline notifications', 'In Progress', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(103, 1, 'Due Today Task', 'Task due today for testing current deadline logic', 'To Do', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), CURDATE(), DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- Boundary length testing
(104, 103, 'Task Title That Is Extremely Long And Tests Database Field Limits', 'This task has an exceptionally long description that tests field length limits, special character handling, and UI layout resilience. It includes various characters: áéíóú, ñç, ¿¡, quotes ""'', apostrophes`, parentheses(), brackets[], braces{}, and symbols @#$%^&*. The purpose is ensuring robust input validation and display formatting across different browsers and devices.', 'To Do', 101, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), NOW()),

-- Security testing tasks with potential XSS/injection attempts  
(105, 102, 'Security Test Task', 'Task for testing input sanitization and XSS prevention', 'To Do', 102, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW());

-- Security test task assignments
INSERT INTO task_assignments (task_id, user_id, assigned_at) VALUES
(100, 100, NOW()),  -- Single task assignment
(102, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),   -- Overdue task
(103, 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),   -- Due today
(104, 101, NOW()),  -- Long content task
(105, 102, NOW()),  -- Security test
(105, 103, NOW());  -- Multi-assignment security test

-- Edge case comments for testing various scenarios
INSERT INTO comments (comment_id, task_id, user_id, content, timestamp) VALUES
-- Empty/minimal content
(100, 100, 100, 'OK', NOW()),
(101, 100, 100, '👍', DATE_SUB(NOW(), INTERVAL 1 HOUR)),

-- Long content
(102, 104, 101, 'This is an extremely long comment that tests how the system handles verbose user input. It includes multiple sentences, various punctuation marks, and attempts to simulate real-world usage where users might paste documentation, code snippets, or detailed explanations. The comment system should gracefully handle this content without breaking layouts or causing performance issues. Special characters: áéíóú ñç ¿¡ ""'' ``, symbols: @#$%^&*()[]{}', DATE_SUB(NOW(), INTERVAL 2 HOUR)),

-- Security test comments (potential XSS attempts - will be sanitized)
(103, 105, 102, 'Testing input: <script>alert("xss")</script> and SQL: \' OR 1=1; --', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(104, 105, 103, 'Unicode content: Hello World', DATE_SUB(NOW(), INTERVAL 15 MINUTE));

-- Additional test notifications for edge cases
INSERT INTO notifications (notification_id, user_id, type, title, message, related_task_id, related_project_id, is_read, created_at, read_at) VALUES
-- Unread notifications for testing
(100, 100, 'task_assigned', 'Edge Case Assignment', 'You have been assigned to an edge case task', 100, 101, FALSE, NOW(), NULL),
(101, 102, 'task_assigned', 'Security Test Assignment', 'You have been assigned to security testing task', 105, 102, FALSE, DATE_SUB(NOW(), INTERVAL 30 MINUTE), NULL),

-- Old notifications for cleanup testing
(102, 1, 'deadline_reminder', 'Old Deadline Reminder', 'This is an old notification for cleanup testing', 102, 1, TRUE, DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 44 DAY)),
(103, 2, 'task_completed', 'Old Completion Notice', 'Old task completion notification', 1, 1, TRUE, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 59 DAY));

-- Update AUTO_INCREMENT to avoid conflicts
ALTER TABLE users AUTO_INCREMENT = 104;
ALTER TABLE projects AUTO_INCREMENT = 104;  
ALTER TABLE tasks AUTO_INCREMENT = 106;
ALTER TABLE comments AUTO_INCREMENT = 105;
ALTER TABLE project_memberships AUTO_INCREMENT = 20;
ALTER TABLE task_assignments AUTO_INCREMENT = 25;
ALTER TABLE notifications AUTO_INCREMENT = 104;

-- Verification queries for test data validation
SELECT 'Test Data Summary' as info;
SELECT 'Total Users:' as metric, COUNT(*) as count FROM users;
SELECT 'Total Projects:' as metric, COUNT(*) as count FROM projects;
SELECT 'Total Tasks:' as metric, COUNT(*) as count FROM tasks;
SELECT 'Total Comments:' as metric, COUNT(*) as count FROM comments;
SELECT 'Unread Notifications:' as metric, COUNT(*) as count FROM notifications WHERE is_read = FALSE;
SELECT 'Overdue Tasks:' as metric, COUNT(*) as count FROM tasks WHERE due_date < CURDATE() AND status != 'Done';
