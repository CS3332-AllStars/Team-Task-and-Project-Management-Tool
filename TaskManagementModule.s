--Task management module

-- Task Insertion

-- Parameterized INSERT for new task creation

INSERT INTO tasks (

project_id,

title,

description,

due_date,

assigned_by,

assigned_date

) VALUES (

:project_id,

:title,

:description,

:due_date,

:assigned_by,

NOW()

);

-- Frontend / Middleware Input Validation

if (title.trim() === '' || title.length > 100) {

showError('Title is required and must be under 100 characters.');

} else if (dueDate && !isValidDate(dueDate)) {

showError('Please enter a valid due date.');

} else {

submitTask();

}

-- Backend SQL Validation

ALTER TABLE tasks

ADD CONSTRAINT chk_title_length CHECK (CHAR_LENGTH(title) <= 100);

— Confirmation Feedback

toast.success("Task created successfully!");




--Create task_assignees Join Table

CREATE TABLE task_assignees (

    task_id     BIGINT NOT NULL,

    user_id     BIGINT NOT NULL,

    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    

    PRIMARY KEY (task_id, user_id),

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

);

-- Validate Assignees Are Part of Project

-- projects_users table (must already exist)

-- project_id BIGINT, user_id BIGINT

-- Create a trigger to enforce validation at DB level

CREATE OR REPLACE FUNCTION validate_user_in_project()

RETURNS TRIGGER AS $$

BEGIN

  IF NOT EXISTS (

    SELECT 1

    FROM projects_users

    JOIN tasks ON tasks.project_id = projects_users.project_id

    WHERE projects_users.user_id = NEW.user_id

      AND tasks.id = NEW.task_id

  ) THEN

    RAISE EXCEPTION 'User % is not part of the project for task %', NEW.user_id, NEW.task_id;

  END IF;

  RETURN NEW;

END;

$$ LANGUAGE plpgsql;

CREATE TRIGGER check_user_in_project

BEFORE INSERT OR UPDATE ON task_assignees

FOR EACH ROW

EXECUTE FUNCTION validate_user_in_project();

-- View for Task Assignees With Avatars or Names

CREATE VIEW task_assignee_details AS

SELECT

  ta.task_id,

  u.id AS user_id,

  u.name,

  u.avatar_url,

  ta.assigned_at

FROM task_assignees ta

JOIN users u ON ta.user_id = u.id;

SELECT * FROM task_assignee_details WHERE task_id = 123;





-- Status Transition Enforcement

DELIMITER $$

CREATE TRIGGER trg_validate_task_status_update

BEFORE UPDATE ON tasks

FOR EACH ROW

BEGIN

IF NEW.status = OLD.status THEN

-- No change, allow

LEAVE;

END IF;

IF NOT (

(OLD.status = 'To Do' AND NEW.status = 'In Progress') OR

(OLD.status = 'In Progress' AND NEW.status = 'Done')

) THEN

SIGNAL SQLSTATE '45000'

SET MESSAGE_TEXT = CONCAT('Invalid status transition: ', OLD.status, ' → ', NEW.status);

END IF;

END;

$$

DELIMITER ;

-- Trigger Notifications to Assigned Users on Status Change

DELIMITER $$

CREATE TRIGGER trg_task_status_notify

AFTER UPDATE ON tasks

FOR EACH ROW

BEGIN

-- Only notify if status changed

IF NEW.status <> OLD.status THEN

-- Loop through assigned users and create a notification per user

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

CONCAT('Task Updated: ', NEW.title),

CONCAT('The task "', NEW.title, '" is now "', NEW.status, '".'),

NEW.task_id,

NEW.project_id

FROM task_assignments ta

WHERE ta.task_id = NEW.task_id;

END IF;

END;

$$

DELIMITER ;

-- Update Task Status via SQL

UPDATE tasks

SET status = 'In Progress'

WHERE task_id = 101;



-- Task Editing

--  Update task description and due date

UPDATE tasks

SET description = 'Updated task details here...',

due_date = '2025-08-01',

updated_at = NOW()

WHERE task_id = 101;

-- Update Task Assignees

-- Remove current assignees

DELETE FROM task_assignments

WHERE task_id = 101;

-- Add new assignees

INSERT INTO task_assignments (task_id, user_id)

VALUES (101, 5),

(101, 7);

--Task Deletion (Only by Creator or Project Admin)

-- Check if user is creator or project admin

SELECT COUNT(*) AS can_delete

FROM tasks t

LEFT JOIN project_memberships pm

ON t.project_id = pm.project_id AND pm.user_id = 9 -- acting user ID

WHERE t.task_id = 101

AND (t.assigned_by = 9 OR pm.role = 'admin');

--Delete Task with Confirmation

-- Final delete SQL (after user is authorized)

DELETE FROM tasks

WHERE task_id = 101;



-- Load Kanban Tasks by Status

-- Replace `:project_id` with your actual project ID

SELECT 

    t.task_id,

    t.title,

    t.description,

    t.status,

    t.due_date,

    t.updated_at,

    u.user_id,

    u.name,

    u.username

FROM tasks t

LEFT JOIN task_assignments ta ON ta.task_id = t.task_id

LEFT JOIN users u ON u.user_id = ta.user_id

WHERE t.project_id = :project_id

ORDER BY FIELD(t.status, 'To Do', 'In Progress', 'Done'), t.updated_at DESC;

-- Update Status via Drag-and-Drop

UPDATE tasks

SET status = 'In Progress',

    updated_at = NOW()

WHERE task_id = 25;

-- jQuery AJAX Snippet (Front-End Trigger)

$('.kanban-column .task-card').on('dragend', function (e) {

  const taskId = $(this).data('task-id');

  const newStatus = $(this).closest('.kanban-column').data('status');

  $.ajax({

    url: '/api/update_task_status.php',

    method: 'POST',

    data: {

      task_id: taskId,

      status: newStatus,

      csrf_token: getCsrfToken()

    },

    success: function (response) {

      if (response.success) {

        // Optionally show toast or update UI

      } else {

        alert('Error updating status');

      }

    }

  });

});

-- Display Assignee Avatars

SELECT 

    t.task_id,

    t.title,

    t.status,

    GROUP_CONCAT(u.name SEPARATOR ', ') AS assignees

FROM tasks t

LEFT JOIN task_assignments ta ON ta.task_id = t.task_id

LEFT JOIN users u ON u.user_id = ta.user_id

WHERE t.project_id = :project_id

GROUP BY t.task_id

ORDER BY FIELD(t.status, 'To Do', 'In Progress', 'Done'), t.updated_at DESC;

--Click Task Card to View/Edit Details

SELECT 

    t.*, 

    GROUP_CONCAT(u.name SEPARATOR ', ') AS assignees

FROM tasks t

LEFT JOIN task_assignments ta ON ta.task_id = t.task_id

LEFT JOIN users u ON u.user_id = ta.user_id

WHERE t.task_id = :task_id

GROUP BY t.task_id;



-- Full-Text Index for Title & Description Search

ALTER TABLE tasks

  ADD FULLTEXT INDEX ft_title_description (title, description);

-- Dynamic Query to Filter, Search & Sort Tasks

-- Template query: supports filtering by status, assignee, due date, and search keyword

SELECT 

    t.task_id,

    t.title,

    t.description,

    t.status,

    t.due_date,

    t.created_at,

    t.updated_at,

    GROUP_CONCAT(ta.user_id) AS assigned_users

FROM tasks t

LEFT JOIN task_assignments ta ON t.task_id = ta.task_id

WHERE 

    -- Status filter

    (:status IS NULL OR t.status = :status)

    -- Assignee filter (user_id in task_assignments)

    AND (

        :assignee_id IS NULL 

        OR EXISTS (

            SELECT 1 FROM task_assignments 

            WHERE task_id = t.task_id AND user_id = :assignee_id

        )

    )

    -- Due date range filter

    AND (

        (:due_start IS NULL OR t.due_date >= :due_start) 

        AND (:due_end IS NULL OR t.due_date <= :due_end)

    )

    -- Full-text search on title and description

    AND (

        :search_query IS NULL 

        OR MATCH(t.title, t.description) AGAINST(:search_query IN NATURAL LANGUAGE MODE)

    )

GROUP BY t.task_id

ORDER BY t.due_date ASC, t.updated_at DESC

LIMIT 100;

-- Quick Filters

SELECT 

    t.task_id, t.title, t.status, t.due_date

FROM tasks t

LEFT JOIN task_assignments ta ON t.task_id = ta.task_id

WHERE ta.user_id IS NULL;

-- Combined Query Use Case

SELECT 

    t.task_id,

    t.title,

    t.description,

    t.status,

    t.due_date

FROM tasks t

JOIN task_assignments ta ON t.task_id = ta.task_id

WHERE 

    t.status = 'In Progress'

    AND ta.user_id = 42

    AND t.due_date BETWEEN '2025-07-01' AND '2025-07-31'

    AND MATCH(t.title, t.description) AGAINST('urgent')

ORDER BY t.due_date ASC;




-- List View

SELECT

t.task_id,

t.title,

t.status,

GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS assignees,

t.due_date

FROM tasks t

LEFT JOIN task_assignments ta ON t.task_id = ta.task_id

LEFT JOIN users u ON ta.user_id = u.user_id

GROUP BY t.task_id

ORDER BY t.due_date ASC;

-- Calendar View

SELECT

t.task_id,

t.title,

t.due_date,

t.status

FROM tasks t

WHERE t.due_date IS NOT NULL

ORDER BY t.due_date;

-- Personal View ("My Tasks")

SELECT

t.task_id,

t.title,

t.status,

t.due_date

FROM tasks t

JOIN task_assignments ta ON t.task_id = ta.task_id

WHERE ta.user_id = :user_id

ORDER BY t.due_date ASC;

-- Team View

-- Grouped Team View

SELECT

u.user_id,

u.name AS assignee_name,

t.task_id,

t.title,

t.status,

t.due_date

FROM users u

JOIN task_assignments ta ON u.user_id = ta.user_id

JOIN tasks t ON t.task_id = ta.task_id

ORDER BY u.name ASC, t.due_date ASC;

-- Optional Role-Based Visibility (e.g., admins only)

SELECT

u.user_id,

u.name AS assignee_name,

t.task_id,

t.title,

t.status,

t.due_date

FROM users u

JOIN task_assignments ta ON u.user_id = ta.user_id

JOIN tasks t ON t.task_id = ta.task_id

JOIN project_memberships pm ON pm.user_id = :current_user_id AND pm.project_id = t.project_id

WHERE pm.role = 'admin'

ORDER BY u.name ASC, t.due_date ASC;

