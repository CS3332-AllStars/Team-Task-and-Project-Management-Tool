-- CS3332 AllStars Team Task & Project Management System
-- Database Schema for MySQL/XAMPP
-- Based on Class Diagram v1.0

DROP DATABASE IF EXISTS ttpm_system;
CREATE DATABASE ttpm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ttpm_system;

-- Users table (User class)
-- Attributes: +userID: int, -username: string, -email: string, -passwordHash: string, -name: string
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects table (Project class)  
-- Attributes: +projectID: int, -title: string, -createdDate: Date, -description: string
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    is_archived BOOLEAN DEFAULT FALSE,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tasks table (Task class)
-- Attributes: +taskID: int, +assignedBy: int, +assignedDate: Date, -title: string, -description: string, -status: enumeration, -dueDate: Date
CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('To Do', 'In Progress', 'Done') DEFAULT 'To Do',
    assigned_by INT,
    assigned_date TIMESTAMP NULL,
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    -- CS3-13A: Database constraint for title length validation (contributed by Juan Ledet)
    CONSTRAINT chk_title_length CHECK (CHAR_LENGTH(title) <= 100 AND CHAR_LENGTH(title) >= 1),
    -- CS3-13F: Full-text search index for title and description (contributed by Juan Ledet)
    FULLTEXT INDEX ft_title_description (title, description)
);

-- Comments table (Comment class)
-- Attributes: +commentID: int, -content: string, -timestamp: DateTime
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Project Memberships table (ProjectMembership association class)
-- Attributes: +userID: int, +projectID: int, -role: string
CREATE TABLE project_memberships (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'admin') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_membership (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Task Assignments table (many-to-many relationship between Users and Tasks)
-- Supports FR-15: task assignment to one or more team members
-- CS3-13B: Enhanced structure and validation contributed by Juan Ledet
CREATE TABLE task_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (task_id, user_id),
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Notifications table - User alerts based on project events
-- Supports real-time notifications for task updates, assignments, and comments
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('task_assigned', 'task_updated', 'task_completed', 'comment_added', 'project_invitation', 'deadline_reminder') NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    message TEXT,
    related_task_id INT NULL,
    related_project_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (related_project_id) REFERENCES projects(project_id) ON DELETE CASCADE
);

-- Add indexes for performance
CREATE INDEX idx_projects_created_date ON projects(created_date);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_comments_timestamp ON comments(timestamp);
CREATE INDEX idx_memberships_role ON project_memberships(role);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_notifications_type ON notifications(type);

-- CS3-13C: Status transition triggers contributed by Juan Ledet
-- Status Transition Enforcement
DELIMITER $$

CREATE TRIGGER trg_validate_task_status_update
BEFORE UPDATE ON tasks
FOR EACH ROW
BEGIN
    -- Only validate if status actually changed
    IF NEW.status <> OLD.status THEN
        IF NOT (
            (OLD.status = 'To Do' AND NEW.status IN ('In Progress', 'Done')) OR
            (OLD.status = 'In Progress' AND NEW.status IN ('To Do', 'Done')) OR
            (OLD.status = 'Done' AND NEW.status IN ('To Do', 'In Progress'))
        ) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Invalid status transition detected';
        END IF;
    END IF;
END$$

-- Trigger Notifications to Assigned Users on Status Change
CREATE TRIGGER trg_task_status_notify
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    -- Only notify if status changed
    IF NEW.status <> OLD.status THEN
        -- Create notifications for assigned users
        INSERT INTO notifications (
            user_id,
            type,
            title,
            message,
            related_task_id,
            related_project_id
        )
        SELECT
            ta.user_id,
            'task_updated',
            'Task Updated',
            'Task status has been updated',
            NEW.task_id,
            NEW.project_id
        FROM task_assignments ta
        WHERE ta.task_id = NEW.task_id;
    END IF;
END$$

DELIMITER ;
