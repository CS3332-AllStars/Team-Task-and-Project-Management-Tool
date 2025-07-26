/* CS3332 AllStars Team Task & Project Management System */
/* CS3-38: UI Enhancement Helper - tooltips.js */

class TooltipManager {
    constructor() {
        this.tooltips = new Map();
        this.activeTooltip = null;
        this.showDelay = 400; // 400ms delay before showing
        this.hideDelay = 150; // 150ms delay before hiding
        this.offset = 12; // Distance from target element
        this.init();
    }

    /**
     * Initialize the tooltip system
     */
    init() {
        this.createTooltipContainer();
        this.bindEvents();
        this.observeNewElements();
    }

    /**
     * Create tooltip container element
     */
    createTooltipContainer() {
        this.container = document.createElement('div');
        this.container.id = 'tooltip-container';
        this.container.className = 'tooltip-container';
        document.body.appendChild(this.container);
    }

    /**
     * Bind global events
     */
    bindEvents() {
        // Handle mouse events for tooltips
        document.addEventListener('mouseenter', this.handleMouseEnter.bind(this), true);
        document.addEventListener('mouseleave', this.handleMouseLeave.bind(this), true);
        document.addEventListener('mousemove', this.handleMouseMove.bind(this), true);
        
        // Handle focus events for keyboard accessibility
        document.addEventListener('focus', this.handleFocus.bind(this), true);
        document.addEventListener('blur', this.handleBlur.bind(this), true);
        
        // Hide tooltips on scroll and resize
        document.addEventListener('scroll', this.hideTooltip.bind(this), true);
        window.addEventListener('resize', this.hideTooltip.bind(this));
        
        // Hide tooltips on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideTooltip();
            }
        });
    }

    /**
     * Observe new elements for tooltip attributes
     */
    observeNewElements() {
        if ('MutationObserver' in window) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            this.processElement(node);
                        }
                    });
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Process element and its children for tooltip attributes
     * @param {Element} element - Element to process
     */
    processElement(element) {
        // Check the element itself
        if (this.hasTooltipAttribute(element)) {
            this.registerTooltip(element);
        }
        
        // Check all children
        const tooltipElements = element.querySelectorAll('[data-tooltip], [title]');
        tooltipElements.forEach(el => this.registerTooltip(el));
    }

    /**
     * Check if element has tooltip attributes
     * @param {Element} element - Element to check
     * @returns {boolean} Has tooltip attributes
     */
    hasTooltipAttribute(element) {
        return element.hasAttribute('data-tooltip') || 
               (element.hasAttribute('title') && element.title.trim());
    }

    /**
     * Register element for tooltip functionality
     * @param {Element} element - Element to register
     */
    registerTooltip(element) {
        if (this.tooltips.has(element)) return;
        
        const config = this.extractTooltipConfig(element);
        if (config.content) {
            this.tooltips.set(element, config);
        }
    }

    /**
     * Extract tooltip configuration from element
     * @param {Element} element - Element to extract from
     * @returns {Object} Tooltip configuration
     */
    extractTooltipConfig(element) {
        const content = element.getAttribute('data-tooltip') || element.title || '';
        const position = element.getAttribute('data-tooltip-position') || 'top';
        const theme = element.getAttribute('data-tooltip-theme') || 'dark';
        const delay = parseInt(element.getAttribute('data-tooltip-delay')) || this.showDelay;
        const html = element.hasAttribute('data-tooltip-html');
        
        // Move title to data-tooltip to prevent browser tooltip
        if (element.title && !element.hasAttribute('data-tooltip')) {
            element.setAttribute('data-tooltip', element.title);
            element.removeAttribute('title');
        }
        
        return {
            content: content.trim(),
            position,
            theme,
            delay,
            html
        };
    }

    /**
     * Handle mouse enter event
     * @param {Event} e - Mouse event
     */
    handleMouseEnter(e) {
        const element = e.target;
        if (this.tooltips.has(element)) {
            this.scheduleShow(element);
        }
    }

    /**
     * Handle mouse leave event
     * @param {Event} e - Mouse event
     */
    handleMouseLeave(e) {
        const element = e.target;
        if (this.tooltips.has(element)) {
            this.scheduleHide();
        }
    }

    /**
     * Handle mouse move event
     * @param {Event} e - Mouse event
     */
    handleMouseMove(e) {
        if (this.activeTooltip && this.activeTooltip.element) {
            this.updatePosition(this.activeTooltip.element, e);
        }
    }

    /**
     * Handle focus event for accessibility
     * @param {Event} e - Focus event
     */
    handleFocus(e) {
        const element = e.target;
        if (this.tooltips.has(element)) {
            this.scheduleShow(element);
        }
    }

    /**
     * Handle blur event for accessibility
     * @param {Event} e - Blur event
     */
    handleBlur(e) {
        const element = e.target;
        if (this.tooltips.has(element)) {
            this.scheduleHide();
        }
    }

    /**
     * Schedule tooltip to show
     * @param {Element} element - Target element
     */
    scheduleShow(element) {
        this.clearTimers();
        
        const config = this.tooltips.get(element);
        this.showTimer = setTimeout(() => {
            this.showTooltip(element, config);
        }, config.delay);
    }

    /**
     * Schedule tooltip to hide
     */
    scheduleHide() {
        this.clearTimers();
        
        this.hideTimer = setTimeout(() => {
            this.hideTooltip();
        }, this.hideDelay);
    }

    /**
     * Clear all timers
     */
    clearTimers() {
        if (this.showTimer) {
            clearTimeout(this.showTimer);
            this.showTimer = null;
        }
        if (this.hideTimer) {
            clearTimeout(this.hideTimer);
            this.hideTimer = null;
        }
    }

    /**
     * Show tooltip
     * @param {Element} element - Target element
     * @param {Object} config - Tooltip configuration
     * @param {Event} mouseEvent - Optional mouse event for positioning
     */
    showTooltip(element, config, mouseEvent = null) {
        this.hideTooltip();
        
        const tooltip = this.createTooltip(config);
        this.container.appendChild(tooltip);
        
        this.activeTooltip = {
            element: element,
            tooltip: tooltip,
            config: config
        };
        
        // Position the tooltip
        this.updatePosition(element, mouseEvent);
        
        // Show with animation
        setTimeout(() => {
            tooltip.classList.add('tooltip-show');
        }, 10);
    }

    /**
     * Hide active tooltip
     */
    hideTooltip() {
        this.clearTimers();
        
        if (this.activeTooltip) {
            const tooltip = this.activeTooltip.tooltip;
            tooltip.classList.add('tooltip-hide');
            
            setTimeout(() => {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, 200);
            
            this.activeTooltip = null;
        }
    }

    /**
     * Create tooltip element
     * @param {Object} config - Tooltip configuration
     * @returns {HTMLElement} Tooltip element
     */
    createTooltip(config) {
        const tooltip = document.createElement('div');
        tooltip.className = `tooltip tooltip-${config.theme} tooltip-${config.position}`;
        tooltip.setAttribute('role', 'tooltip');
        tooltip.setAttribute('aria-live', 'polite');
        
        // Determine if this should be multiline based on content length and characters
        const isLongText = config.content.length > 50 || 
                          config.content.includes('\n') || 
                          config.content.includes('•') ||
                          config.content.split(' ').length > 8;
        
        if (isLongText) {
            tooltip.classList.add('tooltip-multiline');
        }
        
        // Create tooltip content
        const content = document.createElement('div');
        content.className = 'tooltip-content';
        
        if (config.html) {
            content.innerHTML = config.content;
        } else {
            content.textContent = config.content;
        }
        
        tooltip.appendChild(content);
        
        // Create arrow
        const arrow = document.createElement('div');
        arrow.className = 'tooltip-arrow';
        tooltip.appendChild(arrow);
        
        return tooltip;
    }

    /**
     * Update tooltip position
     * @param {Element} element - Target element
     * @param {Event} mouseEvent - Optional mouse event
     */
    updatePosition(element, mouseEvent = null) {
        if (!this.activeTooltip) return;
        
        const tooltip = this.activeTooltip.tooltip;
        const config = this.activeTooltip.config;
        
        // Get element bounds
        const elementRect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        let position = config.position;
        let left, top;
        
        // Calculate position based on preference
        switch (position) {
            case 'top':
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                top = elementRect.top - tooltipRect.height - this.offset;
                break;
                
            case 'bottom':
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                top = elementRect.bottom + this.offset;
                break;
                
            case 'left':
                left = elementRect.left - tooltipRect.width - this.offset;
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                break;
                
            case 'right':
                left = elementRect.right + this.offset;
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                break;
        }
        
        // Adjust for viewport boundaries
        if (left < this.offset) {
            left = this.offset;
        } else if (left + tooltipRect.width > viewportWidth - this.offset) {
            left = viewportWidth - tooltipRect.width - this.offset;
        }
        
        if (top < this.offset) {
            top = this.offset;
        } else if (top + tooltipRect.height > viewportHeight - this.offset) {
            top = viewportHeight - tooltipRect.height - this.offset;
        }
        
        // Apply position
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    /**
     * Manually show tooltip
     * @param {Element|string} element - Element or selector
     * @param {string} content - Tooltip content
     * @param {Object} options - Options
     */
    show(element, content, options = {}) {
        const targetElement = typeof element === 'string' ? 
            document.querySelector(element) : element;
            
        if (!targetElement) return;
        
        const config = {
            content,
            position: options.position || 'top',
            theme: options.theme || 'dark',
            delay: 0,
            html: options.html || false
        };
        
        this.showTooltip(targetElement, config);
    }

    /**
     * Manually hide tooltip
     */
    hide() {
        this.hideTooltip();
    }

    /**
     * Update tooltip content for an element
     * @param {Element|string} element - Element or selector
     * @param {string} content - New content
     */
    updateContent(element, content) {
        const targetElement = typeof element === 'string' ? 
            document.querySelector(element) : element;
            
        if (!targetElement || !this.tooltips.has(targetElement)) return;
        
        const config = this.tooltips.get(targetElement);
        config.content = content;
        
        // Update data attribute
        targetElement.setAttribute('data-tooltip', content);
        
        // If this tooltip is currently active, update it
        if (this.activeTooltip && this.activeTooltip.element === targetElement) {
            const contentEl = this.activeTooltip.tooltip.querySelector('.tooltip-content');
            if (config.html) {
                contentEl.innerHTML = content;
            } else {
                contentEl.textContent = content;
            }
        }
    }

    /**
     * Remove tooltip from element
     * @param {Element|string} element - Element or selector
     */
    remove(element) {
        const targetElement = typeof element === 'string' ? 
            document.querySelector(element) : element;
            
        if (!targetElement) return;
        
        this.tooltips.delete(targetElement);
        targetElement.removeAttribute('data-tooltip');
        
        if (this.activeTooltip && this.activeTooltip.element === targetElement) {
            this.hideTooltip();
        }
    }
}

// Initialize tooltips when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Create global instance
    window.TooltipManager = new TooltipManager();
    
    // Process existing elements
    const existingElements = document.querySelectorAll('[data-tooltip], [title]');
    existingElements.forEach(el => window.TooltipManager.registerTooltip(el));
});

// Global helper functions
window.tooltip = {
    show: (element, content, options) => window.TooltipManager?.show(element, content, options),
    hide: () => window.TooltipManager?.hide(),
    update: (element, content) => window.TooltipManager?.updateContent(element, content),
    remove: (element) => window.TooltipManager?.remove(element)
};

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TooltipManager;
}