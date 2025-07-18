/* CS3332 AllStars Team Task & Project Management System */
/* CS3-37: AJAX Communication Helper - api.js */

class APIManager {
    constructor() {
        this.baseURL = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
        this.defaultTimeout = 10000; // 10 seconds
        this.pendingRequests = new Map();
        this.csrfToken = null;
        this.init();
    }

    /**
     * Initialize the API manager
     */
    init() {
        // Extract CSRF token if available
        this.extractCSRFToken();
        
        // Set up global error handlers
        this.setupGlobalHandlers();
    }

    /**
     * Extract CSRF token from meta tag or form
     */
    extractCSRFToken() {
        // Try to get from meta tag first
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            this.csrfToken = metaToken.getAttribute('content');
            return;
        }

        // Try to get from hidden form field
        const hiddenToken = document.querySelector('input[name="csrf_token"]');
        if (hiddenToken) {
            this.csrfToken = hiddenToken.value;
        }
    }

    /**
     * Set up global error handlers and interceptors
     */
    setupGlobalHandlers() {
        // Handle page unload - cancel pending requests
        window.addEventListener('beforeunload', () => {
            this.cancelAllRequests();
        });
    }

    /**
     * Make an AJAX request
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise} Request promise
     */
    async request(url, options = {}) {
        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            },
            timeout: options.timeout || this.defaultTimeout,
            ...options
        };

        // Add CSRF token if available and needed
        if (this.csrfToken && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(config.method.toUpperCase())) {
            config.headers['X-CSRF-Token'] = this.csrfToken;
        }

        // Create AbortController for request cancellation
        const controller = new AbortController();
        config.signal = controller.signal;

        // Store request for potential cancellation
        const requestId = this.generateRequestId();
        this.pendingRequests.set(requestId, controller);

        try {
            // Set timeout
            const timeoutId = setTimeout(() => {
                controller.abort();
            }, config.timeout);

            // Make the request
            const response = await fetch(this.resolveURL(url), config);
            
            clearTimeout(timeoutId);
            this.pendingRequests.delete(requestId);

            // Handle response
            return await this.handleResponse(response, options);

        } catch (error) {
            this.pendingRequests.delete(requestId);
            throw this.handleError(error, options);
        }
    }

    /**
     * Handle response based on content type
     * @param {Response} response - Fetch response
     * @param {Object} options - Original request options
     * @returns {*} Parsed response data
     */
    async handleResponse(response, options = {}) {
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type') || '';

        try {
            if (contentType.includes('application/json')) {
                return await response.json();
            } else if (contentType.includes('text/html')) {
                return await response.text();
            } else if (contentType.includes('text/')) {
                return await response.text();
            } else {
                return await response.blob();
            }
        } catch (parseError) {
            if (options.suppressParseErrors) {
                return response;
            }
            throw new Error(`Failed to parse response: ${parseError.message}`);
        }
    }

    /**
     * Handle request errors
     * @param {Error} error - Request error
     * @param {Object} options - Original request options
     * @returns {Error} Processed error
     */
    handleError(error, options = {}) {
        let processedError;

        if (error.name === 'AbortError') {
            processedError = new Error('Request was cancelled');
            processedError.type = 'cancelled';
        } else if (error.message.includes('Failed to fetch')) {
            processedError = new Error('Network error - please check your connection');
            processedError.type = 'network';
        } else if (error.message.includes('HTTP 40')) {
            processedError = new Error('Client error - please check your request');
            processedError.type = 'client';
        } else if (error.message.includes('HTTP 50')) {
            processedError = new Error('Server error - please try again later');
            processedError.type = 'server';
        } else {
            processedError = error;
            processedError.type = 'unknown';
        }

        // Show toast error unless suppressed
        if (!options.suppressErrors && window.toastError) {
            window.toastError(processedError.message);
        }

        return processedError;
    }

    /**
     * GET request helper
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise} Request promise
     */
    get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    }

    /**
     * POST request helper
     * @param {string} url - Request URL
     * @param {*} data - Request body data
     * @param {Object} options - Request options
     * @returns {Promise} Request promise
     */
    post(url, data = null, options = {}) {
        const config = { ...options, method: 'POST' };
        
        if (data) {
            if (data instanceof FormData) {
                // Let browser set content-type for FormData
                delete config.headers?.['Content-Type'];
                config.body = data;
            } else if (typeof data === 'object') {
                config.body = JSON.stringify(data);
            } else {
                config.body = data;
            }
        }

        return this.request(url, config);
    }

    /**
     * PUT request helper
     * @param {string} url - Request URL
     * @param {*} data - Request body data
     * @param {Object} options - Request options
     * @returns {Promise} Request promise
     */
    put(url, data = null, options = {}) {
        const config = { ...options, method: 'PUT' };
        
        if (data) {
            if (data instanceof FormData) {
                delete config.headers?.['Content-Type'];
                config.body = data;
            } else if (typeof data === 'object') {
                config.body = JSON.stringify(data);
            } else {
                config.body = data;
            }
        }

        return this.request(url, config);
    }

    /**
     * DELETE request helper
     * @param {string} url - Request URL
     * @param {Object} options - Request options
     * @returns {Promise} Request promise
     */
    delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    }

    /**
     * Submit form via AJAX
     * @param {HTMLFormElement|string} form - Form element or selector
     * @param {Object} options - Submit options
     * @returns {Promise} Request promise
     */
    async submitForm(form, options = {}) {
        const formElement = typeof form === 'string' ? document.querySelector(form) : form;
        
        if (!formElement || formElement.tagName !== 'FORM') {
            throw new Error('Invalid form element provided');
        }

        // Get form data
        const formData = new FormData(formElement);
        const method = (formElement.method || 'POST').toUpperCase();
        const action = formElement.action || window.location.href;

        // Show loading state
        if (options.showLoading !== false) {
            this.setFormLoading(formElement, true);
        }

        try {
            // Make request
            const response = await this.request(action, {
                method,
                body: formData,
                headers: {}, // Let browser set Content-Type for FormData
                ...options
            });

            // Show success message if provided
            if (options.successMessage) {
                if (window.toastSuccess) {
                    window.toastSuccess(options.successMessage);
                }
            }

            // Reset form if successful and requested
            if (options.resetOnSuccess !== false) {
                formElement.reset();
            }

            return response;

        } catch (error) {
            throw error;
        } finally {
            // Remove loading state
            if (options.showLoading !== false) {
                this.setFormLoading(formElement, false);
            }
        }
    }

    /**
     * Set form loading state
     * @param {HTMLFormElement} form - Form element
     * @param {boolean} loading - Loading state
     */
    setFormLoading(form, loading) {
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        
        submitButtons.forEach(button => {
            if (loading) {
                button.disabled = true;
                button.setAttribute('data-original-text', button.textContent || button.value);
                if (button.tagName === 'BUTTON') {
                    button.textContent = 'Loading...';
                } else {
                    button.value = 'Loading...';
                }
            } else {
                button.disabled = false;
                const originalText = button.getAttribute('data-original-text');
                if (originalText) {
                    if (button.tagName === 'BUTTON') {
                        button.textContent = originalText;
                    } else {
                        button.value = originalText;
                    }
                    button.removeAttribute('data-original-text');
                }
            }
        });
    }

    /**
     * Load content into element
     * @param {string} url - Content URL
     * @param {HTMLElement|string} target - Target element or selector
     * @param {Object} options - Load options
     * @returns {Promise} Request promise
     */
    async loadContent(url, target, options = {}) {
        const targetElement = typeof target === 'string' ? document.querySelector(target) : target;
        
        if (!targetElement) {
            throw new Error('Target element not found');
        }

        // Show loading state
        if (options.showLoading !== false) {
            targetElement.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">Loading...</div>';
        }

        try {
            const content = await this.get(url, options);
            targetElement.innerHTML = content;
            
            // Execute scripts in loaded content if requested
            if (options.executeScripts) {
                this.executeScripts(targetElement);
            }

            return content;
        } catch (error) {
            if (options.showError !== false) {
                targetElement.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Failed to load content</div>';
            }
            throw error;
        }
    }

    /**
     * Execute scripts in loaded content
     * @param {HTMLElement} container - Container element
     */
    executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(script => {
            const newScript = document.createElement('script');
            if (script.src) {
                newScript.src = script.src;
            } else {
                newScript.textContent = script.textContent;
            }
            document.head.appendChild(newScript);
            document.head.removeChild(newScript);
        });
    }

    /**
     * Cancel all pending requests
     */
    cancelAllRequests() {
        this.pendingRequests.forEach(controller => {
            controller.abort();
        });
        this.pendingRequests.clear();
    }

    /**
     * Cancel specific request
     * @param {string} requestId - Request ID
     */
    cancelRequest(requestId) {
        const controller = this.pendingRequests.get(requestId);
        if (controller) {
            controller.abort();
            this.pendingRequests.delete(requestId);
        }
    }

    /**
     * Generate unique request ID
     * @returns {string} Request ID
     */
    generateRequestId() {
        return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Resolve relative URLs to absolute
     * @param {string} url - URL to resolve
     * @returns {string} Resolved URL
     */
    resolveURL(url) {
        if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('//')) {
            return url;
        }
        
        if (url.startsWith('/')) {
            return window.location.origin + url;
        }
        
        return this.baseURL + '/' + url;
    }
}

// Create global instance
window.APIManager = new APIManager();

// Global helper functions for easy access
window.api = {
    get: (url, options) => window.APIManager.get(url, options),
    post: (url, data, options) => window.APIManager.post(url, data, options),
    put: (url, data, options) => window.APIManager.put(url, data, options),
    delete: (url, options) => window.APIManager.delete(url, options),
    submitForm: (form, options) => window.APIManager.submitForm(form, options),
    loadContent: (url, target, options) => window.APIManager.loadContent(url, target, options)
};

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = APIManager;
}