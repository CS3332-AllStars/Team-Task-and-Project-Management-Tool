# Frontend Coding Standards & Conventions
## CS3332 AllStars Team Task & Project Management System

*CS3-17F: Frontend Framework Implementation Guidelines*

---

## Table of Contents
1. [Technology Stack](#technology-stack)
2. [File Organization](#file-organization)
3. [HTML Standards](#html-standards)
4. [CSS Standards](#css-standards)
5. [JavaScript Standards](#javascript-standards)
6. [API Usage Patterns](#api-usage-patterns)
7. [Toast Notification System](#toast-notification-system)
8. [Tooltip System](#tooltip-system)
9. [Role-Based Logic](#role-based-logic)
10. [Component Development](#component-development)
11. [PHP Component Standards](#php-component-standards)
12. [Accessibility Guidelines](#accessibility-guidelines)
13. [Performance Best Practices](#performance-best-practices)
14. [Security Considerations](#security-considerations)
15. [Testing Standards](#testing-standards)

---

## Technology Stack

### Core Technologies
- **CSS Framework**: Bootstrap 5.3 (CDN)
- **JavaScript Library**: jQuery 3.7 (CDN)
- **Icons**: Bootstrap Icons 1.10+
- **Backend**: PHP 8.1+ with MySQLi
- **Build System**: None (Simple CDN-based approach)

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## File Organization

### Directory Structure
```
assets/
├── css/
│   ├── main.css          # Core styles and layout
│   ├── forms.css         # Form-specific styling
│   ├── project.css       # Project management UI
│   └── components.css    # Reusable component styles
├── js/
│   ├── api.js           # AJAX/API communication
│   ├── auth.js          # Authentication & role management
│   ├── toast.js         # Success/error feedback
│   └── tooltips.js      # Educational tooltips
includes/
├── layouts/
│   ├── header.php       # Common page header
│   └── footer.php       # Common page footer
└── components/
    ├── task-card.php    # Reusable task display
    ├── dashboard-stat.php # Statistics widgets
    ├── team-member.php  # Team member cards
    └── quick-tip.php    # Help/tooltip components
src/
└── views/
    └── template.php     # Page template engine
```

### Naming Conventions
- **Files**: `kebab-case.php`, `kebab-case.css`, `kebab-case.js`
- **CSS Classes**: `kebab-case`, `component-name__element`
- **JavaScript**: `camelCase` for variables/functions, `PascalCase` for classes
- **PHP Functions**: `camelCase` for methods, `snake_case` for globals

---

## HTML Standards

### Document Structure
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Page description">
    <title>Page Title - TTPM System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <!-- Content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/api.js"></script>
</body>
</html>
```

### Semantic HTML
- Use appropriate semantic elements (`<nav>`, `<main>`, `<section>`, `<article>`)
- Always include `alt` attributes for images
- Use proper heading hierarchy (`h1` → `h6`)
- Include ARIA attributes for accessibility

### Data Attributes
```html
<!-- Role-based visibility -->
<button data-role-show="admin">Admin Only</button>
<div data-role-required="admin,moderator">Staff Only</div>

<!-- Permission checking -->
<button data-permission-required="edit_task" data-project-id="123">Edit</button>

<!-- Tooltips and help -->
<span data-tooltip="Helpful information">?</span>
<input data-help="Field explanation">
```

---

## CSS Standards

### Class Naming (BEM-inspired)
```css
/* Component */
.task-card { }

/* Component element */
.task-card__title { }
.task-card__meta { }

/* Component modifier */
.task-card--urgent { }
.task-card--completed { }

/* Utility classes */
.text-truncate { }
.margin-bottom-sm { }
```

### CSS Organization
```css
/* 1. CSS Reset/Normalize */
* { box-sizing: border-box; }

/* 2. CSS Variables */
:root {
    --primary-color: #007bff;
    --success-color: #28a745;
    --spacing-sm: 0.5rem;
}

/* 3. Base Styles */
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

/* 4. Layout */
.container { max-width: 1200px; }

/* 5. Components */
.task-card { }

/* 6. Utilities */
.hidden { display: none; }

/* 7. Media Queries */
@media (max-width: 768px) { }
```

### Bootstrap Customization
- Use Bootstrap utility classes when possible
- Override Bootstrap variables in `:root`
- Create component-specific styles in separate files
- Use Bootstrap's spacing scale (`mb-3`, `px-4`, etc.)

---

## JavaScript Standards

### ES6+ Features
```javascript
// Use const/let instead of var
const API_BASE = '/api';
let currentUser = null;

// Arrow functions for callbacks
items.forEach(item => processItem(item));

// Template literals
const message = `Hello, ${username}!`;

// Destructuring
const { taskId, title, status } = task;

// Async/await for promises
async function loadTasks() {
    try {
        const response = await api.get('/tasks');
        return response.data;
    } catch (error) {
        console.error('Failed to load tasks:', error);
    }
}
```

### Class Structure
```javascript
class TaskManager {
    constructor(projectId) {
        this.projectId = projectId;
        this.tasks = [];
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadTasks();
    }
    
    bindEvents() {
        // Event binding logic
    }
    
    async loadTasks() {
        // Task loading logic
    }
}
```

### Error Handling
```javascript
// Always include try-catch for async operations
try {
    const result = await api.post('/tasks', taskData);
    toastSuccess('Task created successfully');
} catch (error) {
    console.error('Task creation failed:', error);
    toastError('Failed to create task. Please try again.');
}

// Graceful degradation
if (typeof api !== 'undefined') {
    api.get('/data');
} else {
    console.warn('API not available, using fallback');
}
```

### Event Management
```javascript
class ComponentManager {
    constructor() {
        this.eventHandlers = new Map();
    }
    
    bindEvents() {
        // Store event handlers for cleanup
        this.eventHandlers.set('clickHandler', (e) => this.handleClick(e));
        
        document.addEventListener('click', this.eventHandlers.get('clickHandler'));
    }
    
    destroy() {
        // Clean up event listeners
        this.eventHandlers.forEach((handler, key) => {
            document.removeEventListener(key.replace('Handler', ''), handler);
        });
    }
}
```

---

## API Usage Patterns

### Standardized API Request Function

Use the standardized `apiRequest` function for all API calls:

```javascript
// assets/js/api.js - Standardized API communication
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        // Handle specific 403 responses with detailed messages
        if (response.status === 403) {
            const errorData = await response.json();
            showToast(errorData.message || 'Access denied', 'error');
            return null;
        }
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error(`API Error (${url}):`, error);
        showToast('Network error. Please try again.', 'error');
        throw error;
    }
}
```

### Error Handling Patterns

Implement consistent error handling across all API operations:

```javascript
// Task management example
async function updateTaskStatus(taskId, newStatus) {
    const loadingEl = document.querySelector(`[data-task-id="${taskId}"]`);
    loadingEl.classList.add('loading');
    
    try {
        const result = await apiRequest('/api/tasks.php', {
            method: 'PUT',
            body: JSON.stringify({ 
                task_id: taskId, 
                status: newStatus 
            })
        });
        
        if (result) {
            updateTaskUI(taskId, result);
            showToast('Task status updated', 'success');
        }
    } catch (error) {
        // Error already logged and toast shown by apiRequest
        console.error('Failed to update task status:', error);
    } finally {
        loadingEl.classList.remove('loading');
    }
}
```

### Retry Logic with Exponential Backoff

For critical operations, implement retry logic:

```javascript
async function apiRequestWithRetry(url, options = {}, maxRetries = 3) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await apiRequest(url, options);
        } catch (error) {
            if (attempt === maxRetries) {
                throw error;
            }
            
            // Exponential backoff: 2s, 4s, 8s
            const delay = Math.pow(2, attempt) * 1000;
            await new Promise(resolve => setTimeout(resolve, delay));
            
            console.log(`Retrying API request (attempt ${attempt + 1}/${maxRetries})`);
        }
    }
}
```

### Authorization Error Prevention

Check permissions before making API calls:

```javascript
function canPerformAction(action, taskData = null) {
    const userRole = getUserRole();
    const currentUserId = getCurrentUserId();
    
    switch (action) {
        case 'edit_task':
            return userRole === 'admin' || 
                   taskData?.created_by === currentUserId || 
                   taskData?.assignees?.includes(currentUserId);
        case 'delete_task':
            return userRole === 'admin' || 
                   taskData?.created_by === currentUserId;
        case 'bulk_actions':
            return userRole === 'admin';
        default:
            return false;
    }
}

// Usage
if (canPerformAction('edit_task', taskData)) {
    await updateTask(taskId, updatedData);
} else {
    showToast('You are not authorized to edit this task', 'error');
}
```

---

## Toast Notification System

### Basic Usage

Use `showToast()` for all user feedback:

```javascript
// Success notifications
showToast('Task created successfully', 'success');
showToast('Project settings saved', 'success');

// Error notifications
showToast('Failed to save changes', 'error');
showToast('You are not authorized to perform this action', 'error');

// Warning notifications
showToast('This action cannot be undone', 'warning');
showToast('Your session will expire in 5 minutes', 'warning');

// Info notifications
showToast('New feature available!', 'info');
showToast('Changes saved automatically', 'info');
```

### Toast Configuration

Standard timing and behavior:

```javascript
// assets/js/toast.js
const TOAST_CONFIG = {
    durations: {
        success: 3000,  // 3 seconds
        error: 5000,    // 5 seconds (longer for reading)
        warning: 4000,  // 4 seconds
        info: 4000      // 4 seconds
    },
    maxStack: 3,        // Maximum simultaneous toasts
    position: 'top-end' // Bootstrap positioning
};

function showToast(message, type = 'info', duration = null) {
    duration = duration || TOAST_CONFIG.durations[type];
    
    // Create toast element
    const toastEl = createToastElement(message, type);
    
    // Show with Bootstrap Toast API
    const toast = new bootstrap.Toast(toastEl, {
        delay: duration,
        autohide: true
    });
    
    toast.show();
    
    // Clean up after hide
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}
```

### Toast Integration

Initialize toast container on all pages:

```html
<!-- Include in header.php -->
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

<script src="assets/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initToastContainer();
});
</script>
```

---

## Tooltip System

### Bootstrap Tooltip Implementation

Initialize tooltips consistently across all pages:

```javascript
// assets/js/tooltips.js
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 500, hide: 100 },
            placement: 'auto'
        });
    });
    
    // Initialize all popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'hover focus',
            html: true
        });
    });
});
```

### Standard Tooltip Usage

```html
<!-- Simple tooltip -->
<button data-bs-toggle="tooltip" data-bs-title="Edit this task">
    <i class="bi bi-pencil"></i>
</button>

<!-- Tooltip with custom placement -->
<span data-bs-toggle="tooltip" 
      data-bs-placement="top" 
      data-bs-title="This field is required">
    <i class="bi bi-info-circle text-muted"></i>
</span>

<!-- Help popover -->
<button data-bs-toggle="popover" 
        data-bs-title="Status Options" 
        data-bs-content="Choose from: To Do, In Progress, or Done">
    <i class="bi bi-question-circle"></i>
</button>
```

### Component Tooltip Integration

Use the quick-tip component for complex tooltips:

```php
// Educational tooltips
echo renderInfoTip('Click to view task details');
echo renderHelpPopover('Assignees', 'Select one or more team members to assign this task');
echo renderWarningTip('Changes will be saved automatically');

// Feature introduction tooltips
echo renderFeatureTip('Bulk Actions', 'Select multiple tasks to perform actions on them all at once');
```

---

## Role-Based Logic

### Role Detection

Implement consistent role checking:

```javascript
// Get user role from meta tag set by PHP
function getUserRole() {
    const metaRole = document.querySelector('meta[name="user-role"]');
    return metaRole ? metaRole.getAttribute('content') : 'guest';
}

function getCurrentUserId() {
    const metaUserId = document.querySelector('meta[name="current-user-id"]');
    return metaUserId ? parseInt(metaUserId.getAttribute('content')) : null;
}

// Set in PHP header
echo '<meta name="user-role" content="' . htmlspecialchars($_SESSION['role']) . '">';
echo '<meta name="current-user-id" content="' . (int)$_SESSION['user_id'] . '">';
```

### UI Visibility Control

Control element visibility based on roles:

```javascript
// Show/hide elements based on role
function updateRoleBasedVisibility() {
    const userRole = getUserRole();
    
    // Show admin controls
    if (userRole === 'admin') {
        document.querySelectorAll('.admin-only').forEach(el => {
            el.style.display = '';
        });
    }
    
    // Show member controls
    if (['admin', 'moderator', 'member'].includes(userRole)) {
        document.querySelectorAll('.member-only').forEach(el => {
            el.style.display = '';
        });
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', updateRoleBasedVisibility);
```

### Permission-Based Actions

Check permissions before performing actions:

```javascript
// Task permission checking
function hasTaskPermission(action, taskData) {
    const userRole = getUserRole();
    const currentUserId = getCurrentUserId();
    
    // Admin can do anything
    if (userRole === 'admin') return true;
    
    // Task creator permissions
    if (taskData.created_by === currentUserId) {
        return ['view', 'edit', 'delete'].includes(action);
    }
    
    // Assignee permissions
    if (taskData.assignees && taskData.assignees.includes(currentUserId)) {
        return ['view', 'edit'].includes(action);
    }
    
    // Member can view
    if (userRole === 'member') {
        return action === 'view';
    }
    
    return false;
}

// Usage in event handlers
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-task-btn')) {
        const taskId = e.target.dataset.taskId;
        
        if (!hasTaskPermission('edit', getTaskData(taskId))) {
            showToast('You are not authorized to edit this task', 'error');
            return;
        }
        
        openEditTaskModal(taskId);
    }
});
```

### CSS Role Classes

Use consistent CSS classes for role-based styling:

```css
/* Role visibility classes */
.admin-only { display: none; }
.member-only { display: none; }
.moderator-only { display: none; }
.lead-only { display: none; }

/* Show for appropriate roles via JavaScript */
.role-admin .admin-only,
.role-member .member-only,
.role-moderator .moderator-only,
.role-lead .lead-only {
    display: block;
}

/* Bulk selection mode */
.bulk-mode-active .task-card {
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.bulk-mode-active .task-card:hover {
    border-color: var(--bs-primary);
}

.bulk-mode-active .task-card.selected {
    border-color: var(--bs-success);
    background-color: var(--bs-success-bg-subtle);
}
```

---

## Component Development

### Creating New Components

Follow this step-by-step process for new components:

#### 1. Component Planning
```
Component Name: [descriptive-name]
Purpose: [what problem it solves]
Data Required: [input data structure]
Options: [customization options]
Output: [HTML structure]
```

#### 2. PHP Component File
Create `includes/components/[component-name].php`:

```php
<?php
// CS3332 AllStars Team Task & Project Management System
// Component: [Component Name]

/**
 * Process variable injection in strings using {{variable}} syntax
 */
if (!function_exists('injectVariables')) {
    function injectVariables($content, $variables = []) {
        if (empty($variables) || !is_string($content)) {
            return $content;
        }
        
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $placeholder = '{{' . $key . '.' . $subKey . '}}';
                    $content = str_replace($placeholder, htmlspecialchars((string)$subValue), $content);
                }
            } else {
                $placeholder = '{{' . $key . '}}';
                $content = str_replace($placeholder, htmlspecialchars((string)$value), $content);
            }
        }
        
        return $content;
    }
}

/**
 * Main component render function
 */
function renderComponentName($data, $options = [], $variables = []) {
    // Default options
    $defaults = [
        'size' => 'default',
        'variant' => 'primary',
        'showTitle' => true,
        'template' => null,
        'customFields' => []
    ];
    $options = array_merge($defaults, $options);
    
    // Sanitize input data
    $title = htmlspecialchars($data['title'] ?? 'Default Title');
    
    // Prepare variables for injection
    $injectionVars = array_merge([
        'component' => [
            'title' => $title,
            // ... other component data
        ],
        'variant' => $options['variant'],
        'size' => $options['size']
    ], $variables, $options['customFields']);
    
    // Use custom template if provided
    if ($options['template']) {
        return injectVariables($options['template'], $injectionVars);
    }
    
    ob_start();
    ?>
    <div class="component-name component-name--<?php echo $options['variant']; ?>">
        <?php if ($options['showTitle']): ?>
            <h3><?php echo $title; ?></h3>
        <?php endif; ?>
        <!-- Component HTML structure -->
    </div>
    <?php
    $output = ob_get_clean();
    
    return injectVariables($output, $injectionVars);
}

/**
 * Helper functions
 */
function renderComponentNameSimple($data, $variables = []) {
    return renderComponentName($data, ['size' => 'small'], $variables);
}
?>
```

#### 3. CSS Styles
Add to `assets/css/components.css`:

```css
/* Component Name Styles */
.component-name {
    /* Base styles */
}

.component-name--primary {
    /* Primary variant */
}

.component-name--small {
    /* Small size variant */
}

.component-name--large {
    /* Large size variant */
}
```

#### 4. JavaScript Integration
If needed, add to `assets/js/components.js`:

```javascript
// Component Name JavaScript
function initComponentName() {
    document.querySelectorAll('.component-name').forEach(component => {
        // Component initialization logic
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initComponentName);
```

#### 5. Documentation
Update `docs/components-documentation.md` with:
- Usage examples
- Available options
- Data structure requirements
- Variable injection examples

### Including Components in Pages

```php
<?php
// Include the component
require_once 'includes/components/component-name.php';

// Use in page
echo renderComponentName($componentData, [
    'size' => 'large',
    'variant' => 'success'
], [
    'custom_variable' => 'Custom Value'
]);
?>
```

### Component Testing Checklist

- [ ] Component renders with default options
- [ ] All options work correctly
- [ ] Variable injection works with nested data
- [ ] HTML output is properly escaped
- [ ] Responsive design works on mobile
- [ ] Accessibility attributes included
- [ ] Role-based visibility respected
- [ ] Error handling for missing data

---

## PHP Component Standards

### Component Function Structure
```php
<?php
/**
 * Reusable component documentation
 * 
 * @param array $data Component data
 * @param array $options Display options
 * @return string HTML output
 */
function renderComponent($data, $options = []) {
    // Default options
    $defaults = [
        'showTitle' => true,
        'size' => 'default',
        'variant' => 'primary'
    ];
    $options = array_merge($defaults, $options);
    
    // Data sanitization
    $title = htmlspecialchars($data['title'] ?? 'Default Title');
    
    // Generate output
    ob_start();
    ?>
    <div class="component component--<?php echo $options['variant']; ?>">
        <?php if ($options['showTitle']): ?>
            <h3><?php echo $title; ?></h3>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

### Template Usage
```php
// Using the template system
require_once 'src/views/template.php';

renderTemplate([
    'pageTitle' => 'Dashboard',
    'pageDescription' => 'Project management dashboard',
    'additionalCSS' => ['assets/css/dashboard.css'],
    'additionalJS' => ['assets/js/dashboard.js'],
    'contentFile' => 'views/dashboard-content.php'
]);
```

### Security Standards
```php
// Always escape output
echo htmlspecialchars($userInput);

// Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ?");
$stmt->execute([$projectId]);

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/csrf-protection.php';
}

// Role checking
if (!hasPermission($_SESSION['user_id'], $projectId, 'view_tasks')) {
    http_response_code(403);
    exit('Access denied');
}
```

---

## Accessibility Guidelines

### ARIA Labels
```html
<!-- Button with icon -->
<button aria-label="Delete task" data-tooltip="Remove this task">
    <i class="bi bi-trash" aria-hidden="true"></i>
</button>

<!-- Form controls -->
<label for="task-title">Task Title</label>
<input id="task-title" name="title" aria-describedby="title-help">
<div id="title-help" class="form-help">Enter a descriptive task title</div>

<!-- Live regions -->
<div aria-live="polite" id="status-announcements"></div>
```

### Keyboard Navigation
- All interactive elements must be keyboard accessible
- Implement proper tab order with `tabindex`
- Provide keyboard shortcuts for common actions
- Ensure focus indicators are visible

### Color and Contrast
- Maintain WCAG AA contrast ratios (4.5:1 for normal text)
- Don't rely solely on color to convey information
- Provide alternative text for images and icons

---

## Performance Best Practices

### JavaScript Performance
```javascript
// Debounce user input
let searchTimeout;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => performSearch(), 300);
});

// Throttle scroll events
let isScrolling = false;
window.addEventListener('scroll', () => {
    if (!isScrolling) {
        window.requestAnimationFrame(() => {
            handleScroll();
            isScrolling = false;
        });
        isScrolling = true;
    }
});

// Clean up event listeners
componentInstance.removeEventListeners();
```

### CSS Performance
```css
/* Use efficient selectors */
.task-card { } /* Good */
div.card.task { } /* Avoid */

/* Minimize repaints/reflows */
.animated-element {
    will-change: transform;
    transform: translateZ(0); /* Force GPU acceleration */
}

/* Use CSS Grid/Flexbox for layouts */
.task-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}
```

### Image Optimization
- Use appropriate image formats (WebP, AVIF when supported)
- Implement lazy loading for images below the fold
- Provide multiple image sizes for responsive design

---

## Security Considerations

### XSS Prevention
```php
// Escape all user input
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Use Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net code.jquery.com;");
```

### CSRF Protection
```javascript
// Include CSRF token in AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': csrfToken,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

### Input Validation
```javascript
// Client-side validation (not security, just UX)
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Always validate on server side too
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Invalid email format');
}
```

---

## Testing Standards

### JavaScript Testing
```javascript
// Unit tests for utility functions
function isValidDate(dateString) {
    const regex = /^\d{4}-\d{2}-\d{2}$/;
    return regex.test(dateString) && !isNaN(Date.parse(dateString));
}

// Test
console.assert(isValidDate('2024-01-01') === true);
console.assert(isValidDate('invalid') === false);
```

### Manual Testing Checklist
- [ ] Test all interactive elements with keyboard only
- [ ] Verify responsive design on different screen sizes
- [ ] Check browser compatibility
- [ ] Validate HTML markup
- [ ] Test with screen reader
- [ ] Verify proper error handling

### Performance Testing
- Use browser DevTools to check load times
- Measure Core Web Vitals (LCP, FID, CLS)
- Test on slow network connections
- Monitor JavaScript memory usage

---

## Code Review Guidelines

### Before Submitting
- [ ] Code follows naming conventions
- [ ] All user input is properly escaped
- [ ] Error handling is implemented
- [ ] Code is documented with comments
- [ ] Responsive design is implemented
- [ ] Accessibility features are included

### Review Checklist
- [ ] Security vulnerabilities addressed
- [ ] Performance considerations met
- [ ] Code is maintainable and readable
- [ ] Component reusability considered
- [ ] Browser compatibility verified

---

## Tools and Resources

### Development Tools
- **Browser DevTools**: Chrome/Firefox developer tools
- **Validator**: W3C HTML/CSS validators
- **Accessibility**: WAVE accessibility checker
- **Performance**: Lighthouse audits

### Documentation
- [Bootstrap 5.3 Documentation](https://getbootstrap.com/docs/5.3/)
- [MDN Web Docs](https://developer.mozilla.org/)
- [WCAG Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

---

*Last Updated: July 2025*
*CS3332 AllStars Team - Frontend Standards v2.0 (CS3-17F Implementation)*