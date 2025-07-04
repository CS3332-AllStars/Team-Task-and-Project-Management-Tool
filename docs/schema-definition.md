# Database Schema Definition

## CS3332 AllStars Team Task & Project Management System

**Version**: 1.0  
**Date**: 2025-07-04  
**Database**: MySQL 8.0+ with InnoDB Storage Engine  
**Character Set**: UTF8MB4 (Unicode support)

## Overview

This document defines the complete database schema for the Team Task & Project Management System. The schema implements a normalized relational database design supporting project management, task tracking, user collaboration, and notification systems.

## Database Configuration

- **Database Name**: `ttpm_system`
- **Character Set**: `utf8mb4_unicode_ci`
- **Storage Engine**: InnoDB (default)
- **Foreign Key Constraints**: Enabled with cascading deletes
- **Security**: Bcrypt password hashing via PHP `password_hash()`

## Core Tables

### 1. users
**Purpose**: User account management and authentication

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| username | VARCHAR(50) | UNIQUE, NOT NULL | Login username |
| email | VARCHAR(100) | UNIQUE, NOT NULL | User email address |
| password_hash | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| name | VARCHAR(100) | NOT NULL | User's full name |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |

### 2. projects
**Purpose**: Project metadata and organization

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| project_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique project identifier |
| title | VARCHAR(100) | NOT NULL | Project title |
| description | TEXT | NULL | Project description |
| created_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Project creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last modification timestamp |

### 3. tasks
**Purpose**: Task management and tracking

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| task_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique task identifier |
| project_id | INT | NOT NULL, FK → projects(project_id) | Associated project |
| title | VARCHAR(100) | NOT NULL | Task title |
| description | TEXT | NULL | Task description |
| status | ENUM('To Do', 'In Progress', 'Done') | DEFAULT 'To Do' | Task status |
| assigned_by | INT | FK → users(user_id) | User who assigned the task |
| assigned_date | TIMESTAMP | NULL | Task assignment timestamp |
| due_date | DATE | NULL | Task deadline |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Task creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last modification timestamp |

### 4. comments
**Purpose**: Task-specific discussion and collaboration

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| comment_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique comment identifier |
| task_id | INT | NOT NULL, FK → tasks(task_id) | Associated task |
| user_id | INT | NOT NULL, FK → users(user_id) | Comment author |
| content | TEXT | NOT NULL | Comment text |
| timestamp | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Comment creation timestamp |

### 5. project_memberships
**Purpose**: User-project associations with roles

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| membership_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique membership identifier |
| project_id | INT | NOT NULL, FK → projects(project_id) | Associated project |
| user_id | INT | NOT NULL, FK → users(user_id) | Associated user |
| role | ENUM('member', 'admin') | DEFAULT 'member' | User's role in project |
| joined_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Membership creation timestamp |

**Constraints**: UNIQUE(project_id, user_id) - prevents duplicate memberships

### 6. task_assignments
**Purpose**: Many-to-many relationship between users and tasks

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| assignment_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique assignment identifier |
| task_id | INT | NOT NULL, FK → tasks(task_id) | Associated task |
| user_id | INT | NOT NULL, FK → users(user_id) | Assigned user |
| assigned_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Assignment creation timestamp |

**Constraints**: UNIQUE(task_id, user_id) - prevents duplicate assignments

### 7. notifications
**Purpose**: User alerts and notification system

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| notification_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique notification identifier |
| user_id | INT | NOT NULL, FK → users(user_id) | Notification recipient |
| type | ENUM | NOT NULL | Notification type (see below) |
| title | VARCHAR(255) | NOT NULL | Notification title |
| message | TEXT | NOT NULL | Notification message |
| related_task_id | INT | FK → tasks(task_id) | Related task (optional) |
| related_project_id | INT | FK → projects(project_id) | Related project (optional) |
| is_read | BOOLEAN | DEFAULT FALSE | Read status |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Notification creation timestamp |
| read_at | TIMESTAMP | NULL | Read timestamp |

**Notification Types**:
- `task_assigned` - Task assignment notifications
- `task_updated` - Task status change notifications
- `task_completed` - Task completion notifications
- `comment_added` - New comment notifications
- `project_invitation` - Project invitation notifications
- `deadline_reminder` - Deadline reminder notifications

## Foreign Key Relationships

### Cascading Deletes
- **projects** → **tasks**: ON DELETE CASCADE
- **tasks** → **comments**: ON DELETE CASCADE
- **tasks** → **task_assignments**: ON DELETE CASCADE
- **projects** → **project_memberships**: ON DELETE CASCADE
- **users** → **project_memberships**: ON DELETE CASCADE
- **users** → **task_assignments**: ON DELETE CASCADE
- **users** → **notifications**: ON DELETE CASCADE

### Set NULL on Delete
- **users** → **tasks.assigned_by**: ON DELETE SET NULL

## Performance Indexes

### Core Indexes
- `idx_projects_created_date` - Project creation date sorting
- `idx_tasks_status` - Task status filtering
- `idx_tasks_due_date` - Task deadline queries
- `idx_comments_timestamp` - Comment chronological ordering
- `idx_memberships_role` - Role-based access control queries

### Notification Indexes
- `idx_notifications_user_id` - User-specific notifications
- `idx_notifications_is_read` - Read/unread filtering
- `idx_notifications_created_at` - Chronological ordering
- `idx_notifications_type` - Type-based filtering

## Security Considerations

### Password Security
- All passwords stored using PHP `password_hash()` with bcrypt
- Minimum password length enforced at application level
- Password reset functionality via secure tokens

### Data Integrity
- Foreign key constraints prevent orphaned records
- Unique constraints prevent duplicate usernames/emails
- Enum constraints ensure valid status values

### Access Control
- Role-based permissions (member/admin)
- Project-level access control via memberships
- Task assignment validation

## Sample Data

The schema includes comprehensive sample data covering:
- 7 test users with different roles
- 5 projects of varying complexity
- 17 tasks in different status states
- 13 comments demonstrating collaboration
- 14 notifications covering all types
- Multiple project memberships and task assignments

## Alignment with Requirements

This schema fulfills all requirements specified in CS3-10:

✅ **Core Tables**: All 6 required tables implemented  
✅ **UTF8MB4**: Unicode character set configured  
✅ **Foreign Keys**: Proper relationships with cascading deletes  
✅ **Indexing**: Performance indexes on critical fields  
✅ **Security**: Bcrypt password hashing  
✅ **Normalization**: Proper 3NF database design  
✅ **Documentation**: Complete schema definition  

## Usage Notes

1. **Database Creation**: Run `database/schema.sql` to create the database structure
2. **Sample Data**: Run `database/sample_data.sql` to populate with test data
3. **Testing**: Use provided test scenarios to validate functionality
4. **Backup**: Regular backups recommended for production deployment

## File References

- Schema Definition: `/database/schema.sql`
- Sample Data: `/database/sample_data.sql`
- Class Diagram: `/docs/Class Diagram.png`
- Use Case Diagram: `/docs/Use case diagram.png`