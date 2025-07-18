/* CS3332 AllStars Team Task & Project Management System */
/* CS3-36: Success/Error Feedback Manager - toast.js */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.defaultTimeout = 4000; // 4 seconds
        this.maxToasts = 5;
        this.init();
    }

    /**
     * Initialize the toast system
     */
    init() {
        // Create toast container if it doesn't exist
        this.createContainer();
        
        // Bind cleanup on page unload
        window.addEventListener('beforeunload', () => {
            this.clearAll();
        });
    }

    /**
     * Create the toast container element
     */
    createContainer() {
        if (this.container) return;

        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    /**
     * Show a toast notification
     * @param {string} type - Toast type: 'success', 'error', 'warning', 'info'
     * @param {string} message - Toast message content
     * @param {Object} options - Optional configuration
     */
    showToast(type, message, options = {}) {
        if (!message || typeof message !== 'string') {
            console.warn('Toast message must be a non-empty string');
            return null;
        }

        // Validate type
        const validTypes = ['success', 'error', 'warning', 'info'];
        if (!validTypes.includes(type)) {
            console.warn(`Invalid toast type: ${type}. Using 'info' instead.`);
            type = 'info';
        }

        // Create toast configuration
        const config = {
            type: type,
            message: message,
            timeout: options.timeout !== undefined ? options.timeout : this.defaultTimeout,
            dismissible: options.dismissible !== false, // Default to true
            persistent: options.persistent === true, // Default to false
            html: options.html === true, // Default to false for security
            ...options
        };

        // Enforce max toasts limit
        this.enforceMaxToasts();

        // Create and show toast
        const toast = this.createToast(config);
        this.addToast(toast);

        return toast;
    }

    /**
     * Create a toast element
     * @param {Object} config - Toast configuration
     * @returns {HTMLElement} Toast element
     */
    createToast(config) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${config.type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');

        // Create toast content
        const content = document.createElement('div');
        content.className = 'toast-content';

        // Add icon
        const icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.innerHTML = this.getIcon(config.type);
        content.appendChild(icon);

        // Add message
        const messageEl = document.createElement('span');
        messageEl.className = 'toast-message';
        if (config.html) {
            messageEl.innerHTML = config.message;
        } else {
            messageEl.textContent = config.message;
        }
        content.appendChild(messageEl);

        toast.appendChild(content);

        // Add close button if dismissible
        if (config.dismissible) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'toast-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.setAttribute('aria-label', 'Close notification');
            closeBtn.addEventListener('click', () => {
                this.dismissToast(toast);
            });
            toast.appendChild(closeBtn);
        }

        // Store config on element
        toast._config = config;

        return toast;
    }

    /**
     * Get icon for toast type
     * @param {string} type - Toast type
     * @returns {string} Icon HTML
     */
    getIcon(type) {
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }

    /**
     * Add toast to container and manage lifecycle
     * @param {HTMLElement} toast - Toast element
     */
    addToast(toast) {
        // Add to array
        this.toasts.push(toast);

        // Add to DOM with animation
        this.container.appendChild(toast);
        
        // Trigger reflow for animation
        toast.offsetHeight;
        
        // Add show class for animation
        setTimeout(() => {
            toast.classList.add('toast-show');
        }, 10);

        // Set auto-dismiss timer if not persistent
        if (!toast._config.persistent && toast._config.timeout > 0) {
            toast._timer = setTimeout(() => {
                this.dismissToast(toast);
            }, toast._config.timeout);
        }
    }

    /**
     * Dismiss a specific toast
     * @param {HTMLElement} toast - Toast element to dismiss
     */
    dismissToast(toast) {
        if (!toast || !toast.parentNode) return;

        // Clear timer
        if (toast._timer) {
            clearTimeout(toast._timer);
        }

        // Add dismiss animation
        toast.classList.add('toast-dismiss');

        // Remove after animation
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            
            // Remove from array
            const index = this.toasts.indexOf(toast);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }
        }, 300);
    }

    /**
     * Enforce maximum number of toasts
     */
    enforceMaxToasts() {
        while (this.toasts.length >= this.maxToasts) {
            const oldestToast = this.toasts[0];
            this.dismissToast(oldestToast);
        }
    }

    /**
     * Clear all toasts
     */
    clearAll() {
        this.toasts.forEach(toast => {
            if (toast._timer) {
                clearTimeout(toast._timer);
            }
        });
        
        if (this.container) {
            this.container.innerHTML = '';
        }
        
        this.toasts = [];
    }

    /**
     * Helper methods for common toast types
     */
    success(message, options = {}) {
        return this.showToast('success', message, options);
    }

    error(message, options = {}) {
        return this.showToast('error', message, options);
    }

    warning(message, options = {}) {
        return this.showToast('warning', message, options);
    }

    info(message, options = {}) {
        return this.showToast('info', message, options);
    }
}

// Create global instance
window.ToastManager = new ToastManager();

// Global helper function for easy access
window.showToast = function(type, message, options) {
    return window.ToastManager.showToast(type, message, options);
};

// Additional helper functions
window.toastSuccess = function(message, options) {
    return window.ToastManager.success(message, options);
};

window.toastError = function(message, options) {
    return window.ToastManager.error(message, options);
};

window.toastWarning = function(message, options) {
    return window.ToastManager.warning(message, options);
};

window.toastInfo = function(message, options) {
    return window.ToastManager.info(message, options);
};

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastManager;
}