-- CS3332 AllStars Team Task & Project Management System
-- Sample Test Data for MySQL/XAMPP
-- Supports comprehensive testing scenarios per Test Plan v1.0

USE ttpm_system;

-- Clear existing data (for clean resets during testing)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE task_assignments;
TRUNCATE TABLE comments;
TRUNCATE TABLE tasks;
TRUNCATE TABLE project_memberships;
TRUNCATE TABLE projects;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Test Users - covers different permission levels per test plan
-- Passwords are all 'password123' hashed with PHP password_hash()
INSERT INTO users (user_id, username, email, password_hash, name) VALUES
(1, 'james_ward', 'james.ward@allstars.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Ward'),
(2, 'summer_hill', 'summer.hill@allstars.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Summer Hill'),
(3, 'juan_ledet', 'juan.ledet@allstars.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Ledet'),
(4, 'alaric_higgins', 'alaric.higgins@allstars.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alaric Higgins'),
(5, 'test_member', 'member@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Member'),
(6, 'project_admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Project Admin'),
(7, 'new_user', 'newuser@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'New User Test');

-- Sample Projects - varying complexity levels per test plan
INSERT INTO projects (project_id, title, description, created_date) VALUES
(1, 'CS3332 Software Engineering Project', 'Team task management system development for CS3332 course. Demonstrates agile principles and software engineering concepts.', '2025-06-01 09:00:00'),
(2, 'Website Redesign Project', 'Complete overhaul of company website with modern design and improved user experience.', '2025-06-10 14:30:00'),
(3, 'Simple Bug Tracking', 'Basic project to track and resolve software bugs. Good for testing simple workflows.', '2025-06-15 10:15:00'),
(4, 'Complex Multi-Team Initiative', 'Large-scale project involving multiple teams, complex dependencies, and tight deadlines.', '2025-06-18 16:45:00'),
(5, 'Learning Management System', 'Educational platform development with student and instructor portals.', '2025-06-20 11:20:00');

-- Project Memberships - covers different role scenarios (FR-4, FR-6)
INSERT INTO project_memberships (project_id, user_id, role, joined_at) VALUES
-- CS3332 Project (All team members)
(1, 1, 'admin', '2025-06-01 09:00:00'),  -- James Ward - Team Lead
(1, 2, 'member', '2025-06-01 09:15:00'), -- Summer Hill
(1, 3, 'member', '2025-06-01 09:20:00'), -- Juan Ledet  
(1, 4, 'member', '2025-06-01 09:25:00'), -- Alaric Higgins

-- Website Redesign (Mixed roles)
(2, 6, 'admin', '2025-06-10 14:30:00'),  -- Project Admin
(2, 5, 'member', '2025-06-10 15:00:00'), -- Test Member
(2, 2, 'admin', '2025-06-11 09:00:00'),  -- Summer promoted to admin (test FR-6)

-- Simple Bug Tracking (Single admin)
(3, 3, 'admin', '2025-06-15 10:15:00'),  -- Juan Ledet

-- Complex Multi-Team (Large team)
(4, 1, 'admin', '2025-06-18 16:45:00'),  -- James Ward
(4, 2, 'admin', '2025-06-18 17:00:00'),  -- Summer Hill
(4, 3, 'member', '2025-06-18 17:15:00'), -- Juan Ledet
(4, 4, 'member', '2025-06-18 17:30:00'), -- Alaric Higgins
(4, 5, 'member', '2025-06-18 17:45:00'), -- Test Member
(4, 6, 'member', '2025-06-18 18:00:00'), -- Project Admin

-- Learning Management (New user test)
(5, 7, 'admin', '2025-06-20 11:20:00');  -- New User Test

-- Tasks - covers all status states and assignment scenarios (FR-14, FR-15, FR-16)
INSERT INTO tasks (task_id, project_id, title, description, status, assigned_by, assigned_date, due_date, created_at) VALUES
-- CS3332 Project Tasks
(1, 1, 'Create Use Case Diagrams', 'Develop comprehensive use case diagrams based on requirements analysis', 'Done', 1, '2025-06-01 10:00:00', '2025-06-08', '2025-06-01 10:00:00'),
(2, 1, 'Design Class Diagrams', 'Create detailed class diagrams showing system architecture', 'Done', 1, '2025-06-01 10:15:00', '2025-06-10', '2025-06-01 10:15:00'),
(3, 1, 'Implement Database Schema', 'Set up MySQL database with proper table structure', 'In Progress', 1, '2025-06-15 09:00:00', '2025-06-25', '2025-06-15 09:00:00'),
(4, 1, 'Develop User Authentication', 'Build secure login/registration system with password hashing', 'To Do', 1, '2025-06-20 14:30:00', '2025-06-30', '2025-06-20 14:30:00'),
(5, 1, 'Create Project Management Interface', 'Design and implement project dashboard and management features', 'To Do', NULL, NULL, '2025-07-05', '2025-06-22 11:00:00'),
(6, 1, 'Write Test Documentation', 'Develop comprehensive test plan and test cases', 'In Progress', 1, '2025-06-18 16:00:00', '2025-06-28', '2025-06-18 16:00:00'),

-- Website Redesign Tasks
(7, 2, 'Conduct User Research', 'Survey current users and analyze website usage patterns', 'Done', 6, '2025-06-10 15:30:00', '2025-06-17', '2025-06-10 15:30:00'),
(8, 2, 'Create Wireframes', 'Design low-fidelity wireframes for all major pages', 'In Progress', 6, '2025-06-12 10:00:00', '2025-06-24', '2025-06-12 10:00:00'),
(9, 2, 'Develop CSS Framework', 'Build responsive CSS grid system and component library', 'To Do', 2, '2025-06-15 13:20:00', '2025-07-01', '2025-06-15 13:20:00'),

-- Bug Tracking Tasks
(10, 3, 'Fix Login Bug', 'Resolve issue where password reset emails are not sending', 'Done', 3, '2025-06-15 11:00:00', '2025-06-16', '2025-06-15 11:00:00'),
(11, 3, 'Improve Error Messages', 'Make error messages more user-friendly and informative', 'In Progress', 3, '2025-06-16 14:30:00', '2025-06-20', '2025-06-16 14:30:00'),

-- Complex Project Tasks (testing multi-user assignments)
(12, 4, 'Architecture Planning', 'Define overall system architecture and technology stack', 'Done', 1, '2025-06-18 17:00:00', '2025-06-22', '2025-06-18 17:00:00'),
(13, 4, 'Database Design', 'Create comprehensive database schema for all modules', 'In Progress', 1, '2025-06-19 09:00:00', '2025-06-26', '2025-06-19 09:00:00'),
(14, 4, 'Frontend Development', 'Build responsive user interface components', 'To Do', 2, '2025-06-20 10:30:00', '2025-07-10', '2025-06-20 10:30:00'),
(15, 4, 'Backend API Development', 'Implement RESTful API endpoints for all functionality', 'To Do', NULL, NULL, '2025-07-15', '2025-06-21 15:45:00'),

-- Learning Management Tasks
(16, 5, 'Setup Course Management', 'Create course creation and enrollment system', 'In Progress', 7, '2025-06-20 12:00:00', '2025-06-30', '2025-06-20 12:00:00'),
(17, 5, 'Student Portal', 'Develop student dashboard and assignment submission features', 'To Do', NULL, NULL, '2025-07-08', '2025-06-21 09:30:00');

-- Task Assignments - supports FR-15 (multiple team member assignments)
INSERT INTO task_assignments (task_id, user_id, assigned_at) VALUES
-- Single assignments
(1, 2, '2025-06-01 10:00:00'),   -- Summer: Use Case Diagrams
(2, 4, '2025-06-01 10:15:00'),   -- Alaric: Class Diagrams  
(3, 1, '2025-06-15 09:00:00'),   -- James: Database Schema
(4, 2, '2025-06-20 14:30:00'),   -- Summer: User Authentication
(6, 1, '2025-06-18 16:00:00'),   -- James: Test Documentation
(7, 5, '2025-06-10 15:30:00'),   -- Test Member: User Research
(8, 2, '2025-06-12 10:00:00'),   -- Summer: Wireframes
(9, 2, '2025-06-15 13:20:00'),   -- Summer: CSS Framework
(10, 3, '2025-06-15 11:00:00'),  -- Juan: Fix Login Bug
(11, 3, '2025-06-16 14:30:00'),  -- Juan: Error Messages
(12, 1, '2025-06-18 17:00:00'),  -- James: Architecture Planning
(16, 7, '2025-06-20 12:00:00'),  -- New User: Course Management

-- Multiple assignments (testing FR-15)
(13, 3, '2025-06-19 09:00:00'),  -- Juan: Database Design
(13, 4, '2025-06-19 09:05:00'),  -- Alaric: Database Design
(14, 2, '2025-06-20 10:30:00'),  -- Summer: Frontend Development
(14, 5, '2025-06-20 10:35:00'),  -- Test Member: Frontend Development
(14, 6, '2025-06-20 10:40:00');  -- Project Admin: Frontend Development

-- Comments - tests FR-21, FR-22, FR-23 (task collaboration)
INSERT INTO comments (comment_id, task_id, user_id, content, timestamp) VALUES
(1, 1, 2, 'I have completed the use case diagrams. Please review the actor identification and use case descriptions.', '2025-06-07 15:30:00'),
(2, 1, 1, 'Great work on the use cases! The actor separation between Member and Project Admin is exactly what we need.', '2025-06-07 16:45:00'),
(3, 2, 4, 'Class diagram is taking shape. I am focusing on the five core classes: User, Project, Task, Comment, and ProjectMembership.', '2025-06-09 11:20:00'),
(4, 2, 1, 'Make sure the relationships match our functional requirements, especially the many-to-many associations.', '2025-06-09 14:15:00'),
(5, 3, 1, 'Database schema converted to MySQL syntax. All tables created successfully with proper foreign key constraints.', '2025-06-24 10:30:00'),
(6, 6, 1, 'Test plan documentation is comprehensive. Covers all functional requirements and testing scenarios.', '2025-06-22 17:00:00'),
(7, 8, 2, 'Wireframes are in progress. Focusing on responsive design that works well on both desktop and mobile.', '2025-06-18 13:45:00'),
(8, 8, 6, 'Remember to include accessibility features in the wireframes. We need to meet WCAG guidelines.', '2025-06-18 15:20:00'),
(9, 10, 3, 'Login bug has been fixed. The issue was with the SMTP configuration for password reset emails.', '2025-06-16 09:15:00'),
(10, 13, 3, 'Database design is complex due to multi-team requirements. Working on optimization for performance.', '2025-06-23 14:30:00'),
(11, 13, 4, 'I can help with the indexing strategy. Database performance is critical for this scale.', '2025-06-23 15:45:00'),
(12, 14, 2, 'Frontend framework selection complete. Using modern CSS Grid and Flexbox for responsive layout.', '2025-06-22 11:00:00'),
(13, 14, 5, 'Working on the component library. Making sure all UI elements are reusable and consistent.', '2025-06-22 16:30:00');

-- Reset AUTO_INCREMENT values to maintain consistency
ALTER TABLE users AUTO_INCREMENT = 8;
ALTER TABLE projects AUTO_INCREMENT = 6;
ALTER TABLE tasks AUTO_INCREMENT = 18;
ALTER TABLE comments AUTO_INCREMENT = 14;
ALTER TABLE project_memberships AUTO_INCREMENT = 13;
ALTER TABLE task_assignments AUTO_INCREMENT = 16;

-- Verification queries (commented out - uncomment for testing)
-- SELECT 'User Accounts Created:' as info, COUNT(*) as count FROM users;
-- SELECT 'Projects Created:' as info, COUNT(*) as count FROM projects;
-- SELECT 'Tasks Created:' as info, COUNT(*) as count FROM tasks;
-- SELECT 'Task Status Distribution:' as info, status, COUNT(*) as count FROM tasks GROUP BY status;
-- SELECT 'Project Memberships:' as info, COUNT(*) as count FROM project_memberships;
-- SELECT 'Role Distribution:' as info, role, COUNT(*) as count FROM project_memberships GROUP BY role;
