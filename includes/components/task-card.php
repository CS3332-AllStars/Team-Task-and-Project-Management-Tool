<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17E: Frontend Component Includes - Task Card Component

/**
 * Reusable Task Card Component
 * 
 * @param array $task Task data
 * @param array $options Display options
 */
function renderTaskCard($task, $options = []) {
    // Default options
    $defaults = [
        'showAssignees' => true,
        'showDueDate' => true,
        'showDescription' => true,
        'showActions' => true,
        'clickable' => true,
        'cardClass' => '',
        'size' => 'default' // 'small', 'default', 'large'
    ];
    $options = array_merge($defaults, $options);
    
    // Sanitize task data
    $taskId = (int) $task['task_id'];
    $title = htmlspecialchars($task['title'] ?? 'Untitled Task');
    $description = htmlspecialchars($task['description'] ?? '');
    $status = htmlspecialchars($task['status'] ?? 'To Do');
    $dueDate = $task['due_date'] ?? null;
    $assignees = $task['assignees'] ?? '';
    $createdBy = htmlspecialchars($task['assigned_by_username'] ?? 'Unknown');
    
    // Status styling
    $statusClass = match($status) {
        'To Do' => 'text-bg-secondary',
        'In Progress' => 'text-bg-warning',
        'Done' => 'text-bg-success',
        default => 'text-bg-light'
    };
    
    // Due date styling
    $dueDateClass = '';
    $isDueToday = false;
    $isOverdue = false;
    if ($dueDate) {
        $today = new DateTime();
        $due = new DateTime($dueDate);
        $isDueToday = $today->format('Y-m-d') === $due->format('Y-m-d');
        $isOverdue = $due < $today && $status !== 'Done';
        
        if ($isOverdue) {
            $dueDateClass = 'text-danger';
        } elseif ($isDueToday) {
            $dueDateClass = 'text-warning';
        }
    }
    
    // Card size classes
    $sizeClass = match($options['size']) {
        'small' => 'card-sm',
        'large' => 'card-lg',
        default => ''
    };
    
    // Process assignees
    $assigneeList = [];
    if ($assignees) {
        $assigneePairs = explode(',', $assignees);
        foreach ($assigneePairs as $pair) {
            if (strpos($pair, ':') !== false) {
                [$username, $userId] = explode(':', $pair);
                $assigneeList[] = [
                    'username' => trim($username),
                    'user_id' => trim($userId)
                ];
            }
        }
    }
    
    // Clickable attributes
    $clickableAttrs = $options['clickable'] ? 
        'data-task-id="' . $taskId . '" style="cursor: pointer;" data-tooltip="Click to view details"' : '';
    
    ob_start();
    ?>
    
    <div class="card task-card <?php echo $sizeClass; ?> <?php echo $options['cardClass']; ?>" <?php echo $clickableAttrs; ?>>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="card-title mb-0 flex-grow-1"><?php echo $title; ?></h6>
                <span class="badge <?php echo $statusClass; ?> ms-2"><?php echo $status; ?></span>
            </div>
            
            <?php if ($options['showDescription'] && $description): ?>
                <p class="card-text text-muted small">
                    <?php echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description; ?>
                </p>
            <?php endif; ?>
            
            <div class="task-card-meta">
                <?php if ($options['showAssignees']): ?>
                    <div class="assignees mb-2">
                        <?php if (!empty($assigneeList)): ?>
                            <small class="text-muted">
                                <i class="bi bi-person"></i>
                                <?php foreach ($assigneeList as $index => $assignee): ?>
                                    <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($assignee['username']); ?></span>
                                <?php endforeach; ?>
                            </small>
                        <?php else: ?>
                            <small class="text-muted">
                                <i class="bi bi-person"></i> Unassigned
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($options['showDueDate'] && $dueDate): ?>
                    <div class="due-date mb-2">
                        <small class="<?php echo $dueDateClass; ?>">
                            <i class="bi bi-calendar"></i>
                            Due: <?php echo date('M j, Y', strtotime($dueDate)); ?>
                            <?php if ($isOverdue): ?>
                                <span class="badge bg-danger ms-1">Overdue</span>
                            <?php elseif ($isDueToday): ?>
                                <span class="badge bg-warning ms-1">Due Today</span>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
                
                <div class="task-meta-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Created by <?php echo $createdBy; ?>
                    </small>
                    
                    <?php if ($options['showActions']): ?>
                        <div class="task-actions">
                            <button class="btn btn-sm btn-outline-primary edit-task-btn" 
                                    data-task-id="<?php echo $taskId; ?>" 
                                    data-tooltip="Edit task">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-task-btn" 
                                    data-task-id="<?php echo $taskId; ?>" 
                                    data-tooltip="Delete task">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Render a simplified task card for lists
 */
function renderTaskCardSimple($task) {
    return renderTaskCard($task, [
        'showDescription' => false,
        'showActions' => false,
        'size' => 'small'
    ]);
}

/**
 * Render a detailed task card for modals/detailed views
 */
function renderTaskCardDetailed($task) {
    return renderTaskCard($task, [
        'size' => 'large',
        'clickable' => false
    ]);
}
?>