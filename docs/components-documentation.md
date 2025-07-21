# Reusable Components Documentation

## CS3-17E: Frontend Component System with Variable Injection

This document provides comprehensive guidance on including and using the reusable PHP components with variable injection support.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Task Card Component](#task-card-component)
3. [Dashboard Statistics Component](#dashboard-statistics-component)
4. [Team Member Component](#team-member-component)
5. [Quick Tip Component](#quick-tip-component)
6. [Variable Injection System](#variable-injection-system)
7. [Best Practices](#best-practices)
8. [Examples](#examples)

---

## Getting Started

### Including Components

Include the component files in your PHP pages:

```php
<?php
// Include individual components
require_once 'includes/components/task-card.php';
require_once 'includes/components/dashboard-stat.php';
require_once 'includes/components/team-member.php';
require_once 'includes/components/quick-tip.php';

// Or include all at once
$components = ['task-card', 'dashboard-stat', 'team-member', 'quick-tip'];
foreach ($components as $component) {
    require_once "includes/components/{$component}.php";
}
?>
```

### CSS Dependencies

Ensure components.css is included:

```html
<link rel="stylesheet" href="assets/css/components.css">
```

---

## Task Card Component

### Basic Usage

```php
// Simple task card
echo renderTaskCard($task);

// Customized task card
echo renderTaskCard($task, [
    'size' => 'large',
    'showActions' => true,
    'clickable' => false
]);
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `showAssignees` | boolean | `true` | Show assignee badges |
| `showDueDate` | boolean | `true` | Show due date information |
| `showDescription` | boolean | `true` | Show task description |
| `showActions` | boolean | `true` | Show edit/delete buttons |
| `clickable` | boolean | `true` | Make card clickable |
| `cardClass` | string | `''` | Additional CSS classes |
| `size` | string | `'default'` | Size: `'small'`, `'default'`, `'large'` |
| `template` | string | `null` | Custom HTML template |
| `customFields` | array | `[]` | Additional data fields |

### Helper Functions

```php
// Simplified task card for lists
echo renderTaskCardSimple($task, $variables);

// Detailed task card for modals
echo renderTaskCardDetailed($task, $variables);

// Custom template
echo renderTaskCardWithTemplate($task, $template, $variables, $options);

// Custom fields
echo renderTaskCardWithFields($task, $customFields, $options, $variables);
```

### Data Structure

Expected `$task` array structure:

```php
$task = [
    'task_id' => 123,
    'title' => 'Complete project documentation',
    'description' => 'Write comprehensive docs for the system',
    'status' => 'In Progress', // 'To Do', 'In Progress', 'Done'
    'due_date' => '2024-01-15',
    'assignees' => 'john:1,jane:2', // username:id pairs
    'assigned_by_username' => 'manager'
];
```

### Variable Injection

Available variables for injection:

```php
$variables = [
    'task.id' => $taskId,
    'task.title' => $title,
    'task.description' => $description,
    'task.status' => $status,
    'task.due_date' => $formattedDueDate,
    'task.created_by' => $createdBy,
    'assignees_count' => $assigneeCount,
    'is_overdue' => $isOverdue,
    'is_due_today' => $isDueToday,
    'status_class' => $statusClass,
    'due_date_class' => $dueDateClass
];
```

### Examples

```php
// Basic usage
echo renderTaskCard($task);

// Large card without actions
echo renderTaskCard($task, [
    'size' => 'large',
    'showActions' => false
]);

// With custom variables
echo renderTaskCard($task, [], [
    'project_name' => 'TTPM System',
    'priority' => 'High'
]);

// Custom template
$template = '
<div class="custom-task-card">
    <h3>{{task.title}} ({{priority}})</h3>
    <p>Project: {{project_name}}</p>
    <span class="status">{{task.status}}</span>
</div>';

echo renderTaskCardWithTemplate($task, $template, [
    'project_name' => 'My Project',
    'priority' => 'High'
]);
```

---

## Dashboard Statistics Component

### Basic Usage

```php
// Simple statistic
echo renderDashboardStat([
    'title' => 'Total Tasks',
    'value' => 150,
    'icon' => 'bi-list-check'
]);

// Advanced statistic with trend
echo renderDashboardStat([
    'title' => 'Completed Tasks',
    'value' => 89,
    'icon' => 'bi-check-circle',
    'trend' => 'up',
    'trend_value' => '12%'
], [
    'variant' => 'success',
    'showTrend' => true,
    'animated' => true
]);
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `size` | string | `'default'` | Size: `'small'`, `'default'`, `'large'` |
| `variant` | string | `'primary'` | Color: `'primary'`, `'success'`, `'warning'`, `'danger'`, `'info'` |
| `showIcon` | boolean | `true` | Show icon |
| `showTrend` | boolean | `false` | Show trend indicator |
| `animated` | boolean | `false` | Fade-in animation |
| `clickable` | boolean | `false` | Make clickable |
| `href` | string | `'#'` | Link URL if clickable |
| `template` | string | `null` | Custom template |
| `customFields` | array | `[]` | Additional fields |

### Helper Functions

```php
// Simple numeric stat
echo renderSimpleStat('Total Users', 1250, 'bi-people', 'info', $variables);

// Progress stat with percentage
echo renderProgressStat('Task Completion', 75, 100);

// Trend comparison stat
echo renderTrendStat('Monthly Tasks', 150, 120, 'bi-graph-up');

// Custom template
echo renderDashboardStatWithTemplate($stat, $template, $variables, $options);

// Custom fields
echo renderDashboardStatWithFields($stat, $customFields, $options, $variables);
```

### Data Structure

Expected `$stat` array structure:

```php
$stat = [
    'title' => 'Active Projects',
    'value' => 25,
    'subtitle' => 'This month',
    'icon' => 'bi-folder',
    'trend' => 'up', // 'up', 'down', 'stable'
    'trend_value' => '15%',
    'description' => 'Currently active projects'
];
```

### Variable Injection

Available variables:

```php
$variables = [
    'stat.title' => $title,
    'stat.value' => $formattedValue,
    'stat.raw_value' => $rawValue,
    'stat.subtitle' => $subtitle,
    'stat.description' => $description,
    'stat.icon' => $icon,
    'variant' => $variant,
    'variant_class' => $variantClass,
    'size_class' => $sizeClass,
    'trend_class' => $trendClass,
    'trend_icon' => $trendIcon,
    'trend_value' => $trendValue
];
```

### Examples

```php
// Basic stat
echo renderSimpleStat('Total Tasks', 42);

// Clickable stat with custom variant
echo renderDashboardStat([
    'title' => 'Pending Reviews',
    'value' => 8,
    'icon' => 'bi-clock'
], [
    'variant' => 'warning',
    'clickable' => true,
    'href' => '/reviews'
]);

// Progress indicator
echo renderProgressStat('Project Progress', 67, 100);

// With custom variables
echo renderDashboardStat($stat, [], [
    'department' => 'Engineering',
    'period' => 'Q4 2024'
]);
```

---

## Team Member Component

### Basic Usage

```php
// Simple member card
echo renderTeamMember($member);

// Vertical layout with actions
echo renderTeamMember($member, [
    'layout' => 'vertical',
    'showActions' => true,
    'size' => 'large'
]);
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `size` | string | `'default'` | Size: `'small'`, `'default'`, `'large'` |
| `showRole` | boolean | `true` | Show role badge |
| `showEmail` | boolean | `true` | Show email address |
| `showJoinDate` | boolean | `true` | Show join date |
| `showActions` | boolean | `false` | Show action buttons |
| `showAvatar` | boolean | `true` | Show avatar image |
| `layout` | string | `'horizontal'` | Layout: `'horizontal'`, `'vertical'` |
| `clickable` | boolean | `false` | Make clickable |
| `href` | string | `'#'` | Link URL if clickable |
| `template` | string | `null` | Custom template |
| `customFields` | array | `[]` | Additional fields |

### Helper Functions

```php
// Simple member badge for lists
echo renderMemberBadge($member);

// Member dropdown list for forms
echo renderMemberDropdownList($members, $selectedIds);
```

### Data Structure

Expected `$member` array structure:

```php
$member = [
    'user_id' => 123,
    'username' => 'johndoe',
    'name' => 'John Doe', // or 'full_name'
    'email' => 'john@example.com',
    'role' => 'admin', // 'admin', 'moderator', 'lead', 'member'
    'joined_at' => '2024-01-01', // or 'join_date'
    'avatar_url' => 'https://example.com/avatar.jpg',
    'is_online' => true,
    'last_active' => '2024-01-15 10:30:00'
];
```

### Variable Injection

Available variables:

```php
$variables = [
    'member.id' => $userId,
    'member.username' => $username,
    'member.full_name' => $fullName,
    'member.email' => $email,
    'member.role' => $role,
    'member.avatar_url' => $avatarSrc,
    'member.is_online' => $isOnline,
    'member.join_date' => $formattedJoinDate,
    'member.last_active' => $timeAgo,
    'role_class' => $roleClass,
    'layout' => $layout,
    'size' => $size,
    'online_indicator' => $onlineIndicator
];
```

### Examples

```php
// Basic member card
echo renderTeamMember($member);

// Large vertical card with actions
echo renderTeamMember($member, [
    'layout' => 'vertical',
    'size' => 'large',
    'showActions' => true
]);

// Member badge for lists
echo renderMemberBadge($member);

// With custom variables
echo renderTeamMember($member, [], [
    'department' => 'Engineering',
    'project_count' => 5
]);
```

---

## Quick Tip Component

### Basic Usage

```php
// Simple tooltip
echo renderQuickTip([
    'content' => 'This is a helpful tip'
]);

// Warning alert
echo renderQuickTip([
    'title' => 'Important Notice',
    'content' => 'Please save your work before proceeding'
], [
    'type' => 'inline',
    'variant' => 'warning',
    'dismissible' => true
]);
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `type` | string | `'tooltip'` | Type: `'tooltip'`, `'popover'`, `'inline'`, `'modal'` |
| `variant` | string | `'info'` | Variant: `'info'`, `'warning'`, `'success'`, `'danger'`, `'light'` |
| `size` | string | `'default'` | Size: `'small'`, `'default'`, `'large'` |
| `dismissible` | boolean | `false` | Show close button |
| `showIcon` | boolean | `true` | Show icon |
| `placement` | string | `'top'` | Tooltip placement: `'top'`, `'bottom'`, `'left'`, `'right'` |
| `trigger` | string | `'hover'` | Trigger: `'hover'`, `'click'`, `'focus'` |
| `autoShow` | boolean | `false` | Auto-show on load |
| `template` | string | `null` | Custom template |
| `customFields` | array | `[]` | Additional fields |

### Helper Functions

```php
// Simple info tooltip
echo renderInfoTip('Helpful information', 'top');

// Help popover
echo renderHelpPopover('Need Help?', 'Click here for more details', 'right');

// Warning tip
echo renderWarningTip('This action cannot be undone', true);

// Success tip
echo renderSuccessTip('Task completed successfully!', 'Great Job!');

// Field help
echo renderFieldHelp('Enter your full name as it appears on your ID');

// Feature introduction
echo renderFeatureTip('New Feature!', 'Try our new bulk actions system');

// Tip collection
echo renderTipCollection($tips, 'Getting Started');

// Initialize tooltips (add to page footer)
echo initQuickTips();
```

### Data Structure

Expected `$tip` array structure:

```php
$tip = [
    'title' => 'Quick Tip',
    'content' => 'This is the tip content',
    'category' => 'general',
    'icon' => 'bi-info-circle',
    'target' => '#element-id', // For tooltips
    'id' => 'unique-tip-id'
];
```

### Variable Injection

Available variables:

```php
$variables = [
    'tip.title' => $title,
    'tip.content' => $content,
    'tip.category' => $category,
    'tip.icon' => $icon,
    'tip.id' => $id,
    'variant' => $variant,
    'variant_class' => $variantClass,
    'icon_class' => $iconClass,
    'size_class' => $sizeClass,
    'type' => $type,
    'placement' => $placement,
    'trigger' => $trigger
];
```

### Examples

```php
// Tooltip
echo renderInfoTip('Click to edit this task');

// Help popover
echo renderHelpPopover('Status Options', 'Choose from: To Do, In Progress, or Done');

// Inline warning
echo renderWarningTip('Changes will be saved automatically');

// Modal tip
echo renderQuickTip([
    'title' => 'Welcome Tour',
    'content' => 'Let us show you around the new interface'
], ['type' => 'modal']);

// With variables
echo renderQuickTip([
    'title' => 'Hello {{user.name}}',
    'content' => 'You have {{task_count}} tasks pending'
], [], [
    'user' => ['name' => 'John'],
    'task_count' => 5
]);

// Initialize Bootstrap components
echo initQuickTips();
```

---

## Variable Injection System

### Syntax

Use double curly braces for variable placeholders:

```php
// Simple variables
'Hello {{name}}'

// Nested variables (dot notation)
'Welcome {{user.name}}, you have {{stats.tasks}} tasks'

// In templates
$template = '
<div class="custom-card">
    <h3>{{title}}</h3>
    <p>Status: {{status}} | Priority: {{priority}}</p>
    <small>Created by {{user.name}} on {{date}}</small>
</div>';
```

### Security

- All variables are automatically HTML-escaped
- Prevents XSS attacks
- Raw HTML should be avoided in variables

### Custom Templates

```php
// Define custom template
$customTemplate = '
<div class="my-custom-card border p-3">
    <div class="d-flex justify-content-between">
        <h4>{{task.title}}</h4>
        <span class="badge bg-{{variant}}">{{task.status}}</span>
    </div>
    <p>{{task.description}}</p>
    <small class="text-muted">
        Assigned to: {{assignee}} | Due: {{task.due_date}}
    </small>
</div>';

// Use with component
echo renderTaskCardWithTemplate($task, $customTemplate, [
    'variant' => 'primary',
    'assignee' => 'John Doe'
]);
```

### Custom Fields

```php
// Add custom fields to any component
$customFields = [
    'department' => 'Engineering',
    'priority_level' => 'High',
    'project_code' => 'PROJ-123'
];

echo renderTaskCardWithFields($task, $customFields, [
    'template' => 'Project {{project_code}}: {{task.title}} ({{priority_level}})'
]);
```

---

## Best Practices

### 1. Performance

```php
// Include components only when needed
if ($showTaskCards) {
    require_once 'includes/components/task-card.php';
}

// Cache rendered output for repeated use
$renderedCard = renderTaskCard($task);
echo $renderedCard; // Use multiple times
```

### 2. Security

```php
// Always sanitize user input before passing to components
$task['title'] = htmlspecialchars($_POST['title']);

// Use variable injection instead of direct HTML concatenation
// Good:
echo renderTaskCard($task, [], ['user_note' => $userInput]);

// Bad:
$task['title'] .= '<span>' . $userInput . '</span>';
```

### 3. Consistency

```php
// Use consistent option patterns
$standardOptions = [
    'size' => 'default',
    'showActions' => true,
    'clickable' => true
];

// Apply to all cards
foreach ($tasks as $task) {
    echo renderTaskCard($task, $standardOptions);
}
```

### 4. Error Handling

```php
// Check required data before rendering
if (empty($task['task_id'])) {
    echo '<div class="alert alert-warning">Invalid task data</div>';
} else {
    echo renderTaskCard($task);
}
```

### 5. Responsive Design

```php
// Use different sizes for different screen contexts
$isMobile = wp_is_mobile(); // or custom detection
$cardSize = $isMobile ? 'small' : 'default';

echo renderTaskCard($task, ['size' => $cardSize]);
```

---

## Examples

### Complete Dashboard Example

```php
<?php
// Include components
require_once 'includes/components/dashboard-stat.php';
require_once 'includes/components/task-card.php';
require_once 'includes/components/team-member.php';
require_once 'includes/components/quick-tip.php';

// Sample data
$stats = [
    ['title' => 'Total Tasks', 'value' => 150, 'icon' => 'bi-list-check'],
    ['title' => 'Completed', 'value' => 89, 'icon' => 'bi-check-circle'],
    ['title' => 'In Progress', 'value' => 45, 'icon' => 'bi-clock'],
    ['title' => 'Team Members', 'value' => 12, 'icon' => 'bi-people']
];

$recentTasks = [
    ['task_id' => 1, 'title' => 'Design new dashboard', 'status' => 'In Progress'],
    ['task_id' => 2, 'title' => 'Fix login bug', 'status' => 'Done'],
    ['task_id' => 3, 'title' => 'Update documentation', 'status' => 'To Do']
];

$teamMembers = [
    ['user_id' => 1, 'name' => 'John Doe', 'role' => 'admin', 'is_online' => true],
    ['user_id' => 2, 'name' => 'Jane Smith', 'role' => 'developer', 'is_online' => false]
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Project Dashboard</h1>
        
        <!-- Help tip -->
        <?php echo renderFeatureTip('Dashboard Overview', 'This dashboard shows your project statistics and recent activity'); ?>
        
        <!-- Statistics Grid -->
        <div class="row mb-4">
            <?php foreach ($stats as $index => $stat): ?>
                <div class="col-md-3 mb-3">
                    <?php 
                    $variants = ['primary', 'success', 'warning', 'info'];
                    echo renderDashboardStat($stat, [
                        'variant' => $variants[$index],
                        'animated' => true,
                        'clickable' => true
                    ]); 
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Recent Tasks -->
        <div class="row">
            <div class="col-md-8">
                <h3>Recent Tasks</h3>
                <?php foreach ($recentTasks as $task): ?>
                    <div class="mb-3">
                        <?php echo renderTaskCardSimple($task, [
                            'project_name' => 'TTPM System'
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Team Members -->
            <div class="col-md-4">
                <h3>Team Members</h3>
                <?php foreach ($teamMembers as $member): ?>
                    <div class="mb-3">
                        <?php echo renderTeamMember($member, [
                            'size' => 'small',
                            'showEmail' => false,
                            'showJoinDate' => false
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Initialize tooltips -->
    <?php echo initQuickTips(); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### Custom Template Example

```php
<?php
// Custom task card template for project view
$projectTaskTemplate = '
<div class="project-task-item d-flex align-items-center p-3 border-bottom">
    <div class="flex-grow-1">
        <h6 class="mb-1">{{task.title}}</h6>
        <small class="text-muted">
            Project: {{project_name}} | 
            Assigned to: {{assignee_name}} | 
            Priority: {{priority_level}}
        </small>
    </div>
    <div class="text-end">
        <span class="badge bg-{{status_color}} mb-1">{{task.status}}</span>
        <br>
        <small class="text-muted">{{task.due_date}}</small>
    </div>
</div>';

// Use custom template
foreach ($projectTasks as $task) {
    echo renderTaskCardWithTemplate($task, $projectTaskTemplate, [
        'project_name' => $currentProject['name'],
        'assignee_name' => $task['assignee_username'],
        'priority_level' => $task['priority'],
        'status_color' => $task['status'] === 'Done' ? 'success' : 'primary'
    ]);
}
?>
```

---

## Troubleshooting

### Common Issues

1. **Components not styling correctly**
   - Ensure `components.css` is included
   - Check for CSS conflicts
   - Verify Bootstrap is loaded

2. **Variables not being replaced**
   - Check variable syntax: `{{variable}}`
   - Ensure variables array is passed correctly
   - Verify variable names match exactly

3. **Tooltips not working**
   - Include `initQuickTips()` at page bottom
   - Ensure Bootstrap JavaScript is loaded
   - Check for JavaScript errors in console

4. **Permission errors**
   - Verify file paths are correct
   - Check component files exist and are readable
   - Ensure PHP has proper permissions

### Debug Tips

```php
// Debug variable injection
$variables = ['test' => 'Hello World'];
$content = 'Testing: {{test}}';
echo injectVariables($content, $variables); // Should output: "Testing: Hello World"

// Debug component rendering
try {
    echo renderTaskCard($task);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

This documentation provides complete guidance for implementing and using the reusable component system. For additional support, refer to the component source files or create an issue in the project repository.