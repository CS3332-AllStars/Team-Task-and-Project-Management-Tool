// CS3332 AllStars Team Task & Project Management System
// Authentication Module - Login/Registration Logic
// Professional JavaScript with proper error handling

class AuthManager {
    constructor() {
        this.apiBase = '/Team-Task-and-Project-Management-Tool/api';
        this.passwordTimeout = null; // For debouncing
        this.init();
    }

    init() {
        // Initialize form handlers
        this.initLoginForm();
        this.initRegistrationForm();
        
        // Initialize validation
        this.initRealTimeValidation();
    }

    // ===== LOGIN FUNCTIONALITY =====
    initLoginForm() {
        const loginForm = document.querySelector('#loginForm');
        if (!loginForm) return;

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin(e.target);
        });
    }

    async handleLogin(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        this.setLoadingState(submitBtn, true);
        
        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.text();
            
            if (result.includes('Welcome back!') || result.includes('Login successful')) {
                this.showAlert('Login successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            } else if (result.includes('Invalid credentials')) {
                this.showAlert('Invalid username or password', 'error');
            } else {
                window.location.reload();
            }
            
        } catch (error) {
            this.showAlert('Network error. Please try again.', 'error');
            console.error('Login error:', error);
        } finally {
            this.setLoadingState(submitBtn, false);
        }
    }

    // ===== REGISTRATION FUNCTIONALITY =====
    initRegistrationForm() {
        const regForm = document.querySelector('#registrationForm');
        if (!regForm) return;

        regForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (await this.validateRegistrationForm(e.target)) {
                await this.handleRegistration(e.target);
            }
        });
    }

    async validateRegistrationForm(form) {
        const password = form.querySelector('input[name="password"]').value;
        const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
        
        this.clearErrors();
        
        let isValid = true;
        
        if (password !== confirmPassword) {
            this.showFieldError('confirm_password', 'Passwords do not match');
            isValid = false;
        }
        
        const strengthResult = await this.validatePasswordStrengthAjax(password);
        if (!strengthResult.valid) {
            this.showFieldError('password', strengthResult.message);
            isValid = false;
        }
        
        return isValid;
    }

    async handleRegistration(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        this.setLoadingState(submitBtn, true);
        
        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.text();
            
            if (result.includes('Registration successful')) {
                this.showAlert('Registration successful! You can now login.', 'success');
                form.reset();
                // Optionally redirect to login
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                // Reload to show server-side validation
                window.location.reload();
            }
            
        } catch (error) {
            this.showAlert('Registration failed. Please try again.', 'error');
            console.error('Registration error:', error);
        } finally {
            this.setLoadingState(submitBtn, false);
        }
    }

    // ===== REAL-TIME VALIDATION =====
    initRealTimeValidation() {
        // Only check availability on registration page
        const isRegistrationPage = document.querySelector('#registrationForm') !== null;
        
        if (isRegistrationPage) {
            // Username availability checking
            const usernameField = document.querySelector('input[name="username"]');
            if (usernameField) {
                usernameField.addEventListener('blur', () => {
                    this.checkUsernameAvailability(usernameField.value);
                });
            }

            // Email availability checking
            const emailField = document.querySelector('input[name="email"]');
            if (emailField) {
                emailField.addEventListener('blur', () => {
                    this.checkEmailAvailability(emailField.value);
                });
            }

            // Password strength checking with debouncing
            const passwordField = document.querySelector('input[name="password"]');
            if (passwordField) {
                passwordField.addEventListener('input', () => {
                    clearTimeout(this.passwordTimeout);
                    
                    this.passwordTimeout = setTimeout(() => {
                        this.updatePasswordStrength(passwordField.value);
                    }, 500); // 500ms delay after user stops typing
                });
            }
        }
    }

    async checkUsernameAvailability(username) {
        if (username.length < 3) return;
        
        const field = document.querySelector('input[name="username"]');
        
        try {
            const response = await fetch('./ajax/check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'type=username&value=' + encodeURIComponent(username)
            });
            
            const result = await response.json();
            
            if (result.available) {
                this.setFieldSuccess(field);
            } else {
                this.setFieldError(field, 'Username is already taken');
            }
        } catch (error) {
            console.error('Username check error:', error);
            this.setFieldError(field, 'Error checking username availability');
        }
    }

    async checkEmailAvailability(email) {
        if (!this.isValidEmail(email)) return;
        
        const field = document.querySelector('input[name="email"]');
        
        try {
            const response = await fetch('./ajax/check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'type=email&value=' + encodeURIComponent(email)
            });
            
            const result = await response.json();
            
            if (result.available) {
                this.setFieldSuccess(field);
            } else {
                this.setFieldError(field, 'Email is already registered');
            }
        } catch (error) {
            console.error('Email check error:', error);
            this.setFieldError(field, 'Error checking email availability');
        }
    }

    // ===== PASSWORD STRENGTH VALIDATION =====
    async validatePasswordStrengthAjax(password) {
        const field = document.querySelector('input[name="password"]');
        try {
            // Always call the server to validate password strength
            const response = await fetch('./ajax/check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'type=password&value=' + encodeURIComponent(password)
            });
            const result = await response.json();
            
            const totalRequirements = 5;
            const score = Math.max(0, totalRequirements - (result.errors?.length || 0));
            result.score = score;
            
            if (result.valid) {
                this.setFieldSuccess(field);
            } else {
                this.setFieldError(field, result.message);
            }
            
            return result;
            
        } catch (error) {
            console.error('Password strength check error:', error);
            this.setFieldError(field, 'Error checking password strength');
            return { valid: false, score: 0, message: 'Error checking password strength', errors: [] };
        }
    }

    async updatePasswordStrength(password) {
        const result = await this.validatePasswordStrengthAjax(password);
        const field = document.querySelector('input[name="password"]');
        
        // Remove existing strength indicator
        const existingIndicator = document.querySelector('.password-strength');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        if (password.length === 0) return;
        
        // Create strength indicator
        const indicator = document.createElement('div');
        indicator.className = 'password-strength';
        
        // Create strength bar
        const strengthBar = document.createElement('div');
        strengthBar.className = 'strength-bar';
        
        const strengthFill = document.createElement('div');
        strengthFill.className = `strength-fill strength-${result.score}`;
        strengthBar.appendChild(strengthFill);
        
        // Create strength text
        const strengthText = document.createElement('div');
        strengthText.className = 'strength-text';
        strengthText.textContent = this.getStrengthText(result.score);
        
        // Assemble the indicator
        indicator.appendChild(strengthBar);
        indicator.appendChild(strengthText);
        
        // Add requirements text if needed
        if (!result.valid) {
            const requirementsDiv = document.createElement('div');
            requirementsDiv.className = 'strength-requirements';
            requirementsDiv.textContent = result.message;
            indicator.appendChild(requirementsDiv);
        }
        
        field.parentNode.appendChild(indicator);
        
        // Update field styling is already handled by validatePasswordStrengthAjax
    }

    getStrengthText(score) {
        const texts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
        return texts[score] || 'Very Weak';
    }

    // ===== UTILITY METHODS =====
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    setLoadingState(button, loading) {
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> Loading...';
            button.classList.add('loading');
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || 'Submit';
            button.classList.remove('loading');
        }
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        
        const container = document.querySelector('.container');
        container.insertBefore(alert, container.firstChild);
        
        if (type === 'success') {
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    }

    setFieldError(field, message = '') {
        field.classList.remove('success');
        field.classList.add('error');
        
        if (message) {
            this.showFieldError(field.name, message);
        }
    }

    setFieldSuccess(field) {
        field.classList.remove('error');
        field.classList.add('success');
        this.clearFieldError(field.name);
    }

    showFieldError(fieldName, message) {
        this.clearFieldError(fieldName);
        
        const field = document.querySelector(`input[name="${fieldName}"]`);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(fieldName) {
        const field = document.querySelector(`input[name="${fieldName}"]`);
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    clearErrors() {
        const errors = document.querySelectorAll('.field-error');
        errors.forEach(error => error.remove());
        
        const fields = document.querySelectorAll('input.error');
        fields.forEach(field => field.classList.remove('error'));
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
});

// ===== CS3-17A: ROLE DETECTION & ADMIN TOGGLE LOGIC =====

/**
 * Role-based UI Management System
 * Handles visibility and functionality based on user roles
 */
class RoleManager {
    constructor() {
        this.currentUserRole = 'user'; // Default role
        this.currentUserId = null;
        this.permissions = {};
        this.init();
    }
    
    init() {
        // Extract user role from global context or meta tags
        this.detectUserRole();
        
        // Initialize role-based UI
        this.initRoleBasedUI();
        
        // Set up permission checking
        this.setupPermissions();
    }
    
    /**
     * Detect current user role from various sources
     */
    detectUserRole() {
        // Try to get role from meta tag
        const roleMeta = document.querySelector('meta[name="user-role"]');
        if (roleMeta) {
            this.currentUserRole = roleMeta.getAttribute('content');
        }
        
        // Try to get from global JavaScript variable
        if (typeof window.userRole !== 'undefined') {
            this.currentUserRole = window.userRole;
        }
        
        // Try to get user ID
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            this.currentUserId = parseInt(userIdMeta.getAttribute('content'));
        }
        
        if (typeof window.userId !== 'undefined') {
            this.currentUserId = window.userId;
        }
    }
    
    /**
     * Initialize role-based UI elements
     */
    initRoleBasedUI() {
        // Apply role-based visibility using new methods
        this.updateRoleVisibility();
        
        // Legacy method for backward compatibility
        this.applyRoleVisibility();
        
        // Setup admin toggles
        this.setupAdminToggles();
        
        // Apply permission-based disabling
        this.applyPermissionStates();
        
        // Setup role-specific event handlers
        this.bindRoleEvents();
    }
    
    /**
     * Apply visibility rules based on user role
     */
    applyRoleVisibility() {
        // Elements visible only to admins
        const adminOnlyElements = document.querySelectorAll('[data-role-show="admin"]');
        adminOnlyElements.forEach(element => {
            element.style.display = this.isAdmin() ? '' : 'none';
        });
        
        // Elements hidden from guests
        const userOnlyElements = document.querySelectorAll('[data-role-hide="guest"]');
        userOnlyElements.forEach(element => {
            element.style.display = this.isLoggedIn() ? '' : 'none';
        });
        
        // Elements visible only to specific roles
        const roleSpecificElements = document.querySelectorAll('[data-role-required]');
        roleSpecificElements.forEach(element => {
            const requiredRoles = element.getAttribute('data-role-required').split(',');
            const hasRequiredRole = requiredRoles.some(role => this.hasRole(role.trim()));
            element.style.display = hasRequiredRole ? '' : 'none';
        });
        
        // Project-specific role elements
        const projectRoleElements = document.querySelectorAll('[data-project-role]');
        projectRoleElements.forEach(element => {
            const requiredRole = element.getAttribute('data-project-role');
            const projectId = element.getAttribute('data-project-id');
            
            if (projectId) {
                this.checkProjectRole(projectId, requiredRole).then(hasRole => {
                    element.style.display = hasRole ? '' : 'none';
                });
            }
        });
    }
    
    /**
     * Setup admin toggle switches and controls
     */
    setupAdminToggles() {
        if (!this.isAdmin()) return;
        
        this.setupBulkActions();
        this.addAdminControls();
    }
    
    /**
     * Add admin mode toggle switch
     */
    addAdminModeToggle() {
        const navbar = document.querySelector('.navbar-nav');
        if (!navbar) return;
        
        const adminToggle = document.createElement('li');
        adminToggle.className = 'nav-item';
        adminToggle.innerHTML = `
            <div class="nav-link">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="adminModeToggle" 
                           ${this.isAdminModeActive() ? 'checked' : ''}>
                    <label class="form-check-label" for="adminModeToggle">
                        <small>Admin Mode</small>
                    </label>
                </div>
            </div>
        `;
        
        navbar.appendChild(adminToggle);
        
        // Bind toggle event
        const toggleInput = adminToggle.querySelector('#adminModeToggle');
        toggleInput.addEventListener('change', (e) => {
            this.toggleAdminMode(e.target.checked);
        });
    }
    
    /**
     * Setup bulk action controls for admins
     */
    setupBulkActions() {
        // Add bulk selection controls to data tables
        const dataTables = document.querySelectorAll('.data-table');
        dataTables.forEach(table => {
            this.addBulkSelectionToTable(table);
        });
    }
    
    /**
     * Add admin-specific control buttons
     */
    addAdminControls() {
        // Add admin panel link
        const userDropdown = document.querySelector('#userDropdown');
        if (userDropdown) {
            const adminPanelLink = document.createElement('li');
            adminPanelLink.innerHTML = '<a class="dropdown-item" href="admin/panel.php"><i class="bi bi-shield-check"></i> Admin Panel</a>';
            userDropdown.parentNode.querySelector('.dropdown-menu').appendChild(adminPanelLink);
        }
    }
    
    /**
     * Apply permission-based element states
     */
    applyPermissionStates() {
        // Disable elements based on permissions
        const permissionElements = document.querySelectorAll('[data-permission-required]');
        permissionElements.forEach(element => {
            const requiredPermission = element.getAttribute('data-permission-required');
            const projectId = element.getAttribute('data-project-id');
            
            if (!this.hasPermission(requiredPermission, projectId)) {
                element.disabled = true;
                element.classList.add('permission-denied');
                element.title = 'You do not have permission to perform this action';
            }
        });
    }
    
    /**
     * Bind role-specific event handlers
     */
    bindRoleEvents() {
        // Admin-only right-click context menus
        if (this.isAdmin()) {
            document.addEventListener('contextmenu', (e) => {
                this.handleAdminContextMenu(e);
            });
        }
        
        // Role-specific keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleRoleShortcuts(e);
        });
    }
    
    /**
     * Toggle admin mode on/off
     */
    toggleAdminMode(enabled) {
        localStorage.setItem('adminMode', enabled ? 'true' : 'false');
        
        // Toggle admin-specific UI elements
        const adminElements = document.querySelectorAll('.admin-mode-only, .admin-only');
        adminElements.forEach(element => {
            element.style.display = enabled ? '' : 'none';
        });
        
        // Show/hide debug information
        const debugElements = document.querySelectorAll('.debug-info');
        debugElements.forEach(element => {
            element.style.display = enabled ? 'block' : 'none';
        });
        
        // Emit admin mode change event
        window.dispatchEvent(new CustomEvent('adminModeChanged', { 
            detail: { enabled, role: this.currentUserRole } 
        }));
    }
    
    /**
     * Check if admin mode is currently active
     */
    isAdminModeActive() {
        return this.isAdmin();
    }
    
    /**
     * Role checking methods
     */
    isLoggedIn() {
        return this.currentUserId !== null;
    }
    
    isAdmin() {
        return this.currentUserRole === 'admin';
    }
    
    isModerator() {
        return this.currentUserRole === 'moderator' || this.isAdmin();
    }
    
    hasRole(role) {
        if (role === 'admin') return this.isAdmin();
        if (role === 'moderator') return this.isModerator();
        if (role === 'user') return this.isLoggedIn();
        return this.currentUserRole === role;
    }
    
    /**
     * Permission checking
     */
    hasPermission(permission, projectId = null) {
        // Admin has all permissions
        if (this.isAdmin()) return true;
        
        // Check project-specific permissions if projectId provided
        if (projectId) {
            const projectKey = `project_${projectId}`;
            return this.permissions[projectKey]?.[permission] || false;
        }
        
        // Check global permissions
        return this.permissions.global?.[permission] || false;
    }
    
    /**
     * Check project-specific role
     */
    async checkProjectRole(projectId, requiredRole) {
        try {
            const response = await fetch(`api/users.php?action=project_role&project_id=${projectId}`);
            const data = await response.json();
            
            if (data.success) {
                const userRole = data.role;
                return this.compareRoles(userRole, requiredRole);
            }
        } catch (error) {
            console.error('Error checking project role:', error);
        }
        
        return false;
    }
    
    /**
     * Compare role hierarchy
     */
    compareRoles(userRole, requiredRole) {
        const hierarchy = { 'guest': 0, 'user': 1, 'moderator': 2, 'admin': 3 };
        return (hierarchy[userRole] || 0) >= (hierarchy[requiredRole] || 0);
    }
    
    /**
     * Setup permissions from server
     */
    setupPermissions() {
        // Permissions can be loaded from API or embedded in page
        const permissionsMeta = document.querySelector('meta[name="user-permissions"]');
        if (permissionsMeta) {
            try {
                this.permissions = JSON.parse(permissionsMeta.getAttribute('content'));
            } catch (error) {
                console.error('Error parsing permissions:', error);
            }
        }
    }
    
    /**
     * Handle admin context menu
     */
    handleAdminContextMenu(e) {
        if (!this.isAdminModeActive()) return;
        
        // Add admin context menu for debugging
        const target = e.target;
        if (target.hasAttribute('data-debug-info')) {
            e.preventDefault();
            this.showDebugInfo(target);
        }
    }
    
    /**
     * Handle role-specific keyboard shortcuts
     */
    handleRoleShortcuts(e) {
        // Admin shortcuts
        if (this.isAdmin() && e.ctrlKey && e.shiftKey) {
            switch (e.key) {
                case 'A':
                    e.preventDefault();
                    this.toggleAdminMode(!this.isAdminModeActive());
                    break;
                case 'D':
                    e.preventDefault();
                    this.showDebugPanel();
                    break;
            }
        }
    }
    
    /**
     * Show debug information
     */
    showDebugInfo(element) {
        const debugInfo = {
            element: element.tagName,
            classes: element.className,
            id: element.id,
            role: this.currentUserRole,
            permissions: this.permissions
        };
        
        // Debug info available in development
        alert('Debug info logged to console');
    }
    
    /**
     * Show admin-only controls and elements
     */
    showAdminControls() {
        const adminElements = document.querySelectorAll('.admin-only, [data-role-show="admin"]');
        adminElements.forEach(element => {
            element.style.display = 'block';
            element.style.visibility = 'visible';
            element.classList.remove('hidden');
            element.classList.add('admin-enabled');
        });
        
        // Enable admin-specific features
        const adminFeatures = document.querySelectorAll('.admin-feature');
        adminFeatures.forEach(feature => {
            feature.disabled = false;
            feature.classList.add('admin-enabled');
        });
        
        // Admin controls activated
    }
    
    /**
     * Hide admin-only controls and elements
     */
    hideAdminControls() {
        const adminElements = document.querySelectorAll('.admin-only, [data-role-show="admin"]');
        adminElements.forEach(element => {
            element.style.display = 'none';
            element.classList.add('hidden');
        });
        
        // Disable admin-specific features
        const adminFeatures = document.querySelectorAll('.admin-feature');
        adminFeatures.forEach(feature => {
            feature.disabled = true;
            feature.classList.remove('admin-enabled');
        });
        
        // Admin controls deactivated
    }
    
    /**
     * Hide member-only elements (for guests/non-members)
     */
    hideMemberOnly() {
        const memberElements = document.querySelectorAll('.member-only, [data-role-hide="guest"]');
        memberElements.forEach(element => {
            element.style.display = 'none';
            element.classList.add('hidden');
        });
        
        // Member elements restricted
    }
    
    /**
     * Show member-only elements (for logged-in users)
     */
    showMemberOnly() {
        const memberElements = document.querySelectorAll('.member-only, [data-role-hide="guest"]');
        memberElements.forEach(element => {
            element.style.display = '';
            element.classList.remove('hidden');
        });
        
        // Member elements enabled
    }
    
    /**
     * Toggle role-based visibility based on current user role
     */
    updateRoleVisibility() {
        // Hide all role-specific elements first
        this.hideAdminControls();
        this.hideMemberOnly();
        
        // Show appropriate elements based on role
        if (this.isLoggedIn()) {
            this.showMemberOnly();
        }
        
        if (this.isAdmin()) {
            this.showAdminControls();
        }
        
        // Handle moderator-specific elements
        const modElements = document.querySelectorAll('.moderator-only, [data-role-show="moderator"]');
        modElements.forEach(element => {
            element.style.display = this.isModerator() ? '' : 'none';
        });
        
        // Emit visibility change event
        window.dispatchEvent(new CustomEvent('roleVisibilityChanged', { 
            detail: { 
                role: this.currentUserRole, 
                isAdmin: this.isAdmin(), 
                isMember: this.isLoggedIn() 
            } 
        }));
    }
    
    /**
     * Add bulk selection to data tables
     */
    addBulkSelectionToTable(table) {
        // Add "Select All" checkbox to table header
        const headerRow = table.querySelector('thead tr');
        if (!headerRow) return;
        
        const selectAllCell = document.createElement('th');
        selectAllCell.innerHTML = '<input type="checkbox" class="bulk-select-all">';
        headerRow.insertBefore(selectAllCell, headerRow.firstChild);
        
        // Add individual checkboxes to each row
        const bodyRows = table.querySelectorAll('tbody tr');
        bodyRows.forEach(row => {
            const selectCell = document.createElement('td');
            selectCell.innerHTML = '<input type="checkbox" class="bulk-select-item">';
            row.insertBefore(selectCell, row.firstChild);
        });
        
        // Bind select all functionality
        const selectAllCheckbox = selectAllCell.querySelector('.bulk-select-all');
        selectAllCheckbox.addEventListener('change', (e) => {
            const itemCheckboxes = table.querySelectorAll('.bulk-select-item');
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    }
}

// Global function to initialize role-based UI
window.initRoleBasedUI = function(userRole, userId = null) {
    window.userRole = userRole;
    window.userId = userId;
    
    if (typeof window.roleManager === 'undefined') {
        window.roleManager = new RoleManager();
    }
};

// Global helper functions for role management
window.showAdminControls = function() {
    if (window.roleManager) {
        window.roleManager.showAdminControls();
    }
};

window.hideAdminControls = function() {
    if (window.roleManager) {
        window.roleManager.hideAdminControls();
    }
};

window.hideMemberOnly = function() {
    if (window.roleManager) {
        window.roleManager.hideMemberOnly();
    }
};

window.showMemberOnly = function() {
    if (window.roleManager) {
        window.roleManager.showMemberOnly();
    }
};

window.updateRoleVisibility = function() {
    if (window.roleManager) {
        window.roleManager.updateRoleVisibility();
    }
};

window.isAdmin = function() {
    return window.roleManager ? window.roleManager.isAdmin() : false;
};

window.isMember = function() {
    return window.roleManager ? window.roleManager.isLoggedIn() : false;
};

window.hasRole = function(role) {
    return window.roleManager ? window.roleManager.hasRole(role) : false;
};

// Initialize role manager if user data is available
document.addEventListener('DOMContentLoaded', () => {
    // Auto-initialize if role is available
    const roleMeta = document.querySelector('meta[name="user-role"]');
    if (roleMeta) {
        const role = roleMeta.getAttribute('content');
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        const userId = userIdMeta ? parseInt(userIdMeta.getAttribute('content')) : null;
        
        window.initRoleBasedUI(role, userId);
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AuthManager, RoleManager };
}
