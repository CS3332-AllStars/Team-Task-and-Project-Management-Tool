// CS3332 AllStars Team Task & Project Management System
// Notification System Frontend - CS3-15D & CS3-15E

class NotificationManager {
    constructor() {
        this.lastUpdateTime = null;
        this.pollInterval = 30000; // Poll every 30 seconds
        this.csrfToken = this.getCSRFToken();
        this.init();
    }

    init() {
        this.loadNotifications();
        this.setupEventListeners();
        this.startPolling();
    }

    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    async loadNotifications() {
        try {
            const response = await fetch('/api/notifications.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.updateUI(data.notifications || []);
            this.lastUpdateTime = new Date();

        } catch (error) {
            console.error('Failed to load notifications:', error);
            this.showError('Failed to load notifications');
        }
    }

    updateUI(notifications) {
        const badge = document.getElementById('notifBadge');
        const itemsContainer = document.getElementById('notifItems');
        
        if (!badge || !itemsContainer) {
            return;
        }

        let unreadCount = 0;
        itemsContainer.innerHTML = '';

        if (notifications.length === 0) {
            itemsContainer.innerHTML = `
                <li class="dropdown-item-text text-muted text-center py-3">
                    <i class="bi bi-bell-slash"></i> No notifications
                </li>
            `;
        } else {
            notifications.forEach(notification => {
                if (!notification.is_read) {
                    unreadCount++;
                }

                const item = this.createNotificationItem(notification);
                itemsContainer.appendChild(item);
            });
        }

        // Update badge
        badge.textContent = unreadCount;
        badge.classList.toggle('d-none', unreadCount === 0);

        // Update page title with count
        this.updatePageTitle(unreadCount);
    }

    createNotificationItem(notification) {
        const item = document.createElement('a');
        item.href = this.getNotificationURL(notification);
        item.className = `dropdown-item text-wrap ${notification.is_read ? '' : 'fw-bold bg-light'}`;
        item.style.whiteSpace = 'normal';
        item.dataset.notificationId = notification.notification_id;

        // Create time ago text
        const timeAgo = this.getTimeAgo(notification.created_at);
        
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                    <small class="text-muted">${timeAgo}</small>
                </div>
                ${!notification.is_read ? '<div class="notification-dot bg-primary rounded-circle ms-2" style="width: 8px; height: 8px;"></div>' : ''}
            </div>
        `;

        // Mark as read when clicked
        item.addEventListener('click', (e) => {
            if (!notification.is_read) {
                this.markAsRead(notification.notification_id);
            }
        });

        return item;
    }

    getNotificationURL(notification) {
        if (notification.task_id) {
            return `/project.php?id=${notification.project_id}#task-${notification.task_id}`;
        } else if (notification.project_id) {
            return `/project.php?id=${notification.project_id}`;
        }
        return '#';
    }

    getTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffInSeconds = Math.floor((now - time) / 1000);

        if (diffInSeconds < 60) {
            return `${diffInSeconds} seconds ago`;
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('/api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    notification_id: notificationId,
                    csrf_token: this.csrfToken
                })
            });

            if (response.ok) {
                // Update UI immediately
                const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('fw-bold', 'bg-light');
                    const dot = item.querySelector('.notification-dot');
                    if (dot) {
                        dot.remove();
                    }
                }
                // Reload to get accurate count
                this.loadNotifications();
            }

        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'mark_all_read',
                    csrf_token: this.csrfToken
                })
            });

            if (response.ok) {
                this.loadNotifications();
            }

        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    setupEventListeners() {
        const markAllBtn = document.getElementById('markAllRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }

        // Refresh notifications when dropdown is opened
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.addEventListener('click', () => {
                this.loadNotifications();
            });
        }
    }

    startPolling() {
        setInterval(() => {
            this.loadNotifications();
        }, this.pollInterval);
    }

    updatePageTitle(unreadCount) {
        const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
        document.title = unreadCount > 0 ? `(${unreadCount}) ${originalTitle}` : originalTitle;
    }

    showError(message) {
        const itemsContainer = document.getElementById('notifItems');
        if (itemsContainer) {
            itemsContainer.innerHTML = `
                <li class="dropdown-item-text text-danger text-center py-3">
                    <i class="bi bi-exclamation-triangle"></i> ${message}
                </li>
            `;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notification manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in and notification elements exist
    if (document.getElementById('notifDropdown') && document.querySelector('meta[name="user-id"]')) {
        window.notificationManager = new NotificationManager();
    }
});

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}