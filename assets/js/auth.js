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
        
        // Show loading state
        this.setLoadingState(submitBtn, true);
        
        try {
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.text();
            
            // Check if login was successful (look for success indicators)
            if (result.includes('Welcome back!') || result.includes('Login successful')) {
                this.showAlert('Login successful! Redirecting...', 'success');
                // Redirect logic can go here
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            } else if (result.includes('Invalid credentials')) {
                this.showAlert('Invalid username or password', 'error');
            } else {
                // Reload page to show server-side validation
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
        
        // Clear previous errors
        this.clearErrors();
        
        let isValid = true;
        
        // Password match validation
        if (password !== confirmPassword) {
            this.showFieldError('confirm_password', 'Passwords do not match');
            isValid = false;
        }
        
        // Server-side password validation - wait for AJAX result
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
                    // Clear previous timeout
                    clearTimeout(this.passwordTimeout);
                    
                    // Set new timeout for debouncing
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
            
            // Calculate score from errors array for visual indicator
            const totalRequirements = 5; // length, uppercase, lowercase, number, symbol
            const score = Math.max(0, totalRequirements - (result.errors?.length || 0));
            result.score = score; // Add score to result
            
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
        indicator.innerHTML = `
            <div class="strength-bar">
                <div class="strength-fill strength-${result.score}"></div>
            </div>
            <div class="strength-text">${this.getStrengthText(result.score)}</div>
            ${!result.valid ? `<div class="strength-requirements">${result.message}</div>` : ''}
        `;
        
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
            button.innerHTML = button.dataset.originalText || 'Submit';
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
        
        // Auto-hide success messages
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

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthManager;
}
