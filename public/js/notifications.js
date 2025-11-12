// Notifications and UI functionality
class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.darkMode = localStorage.getItem('darkMode') === 'true';
        this.init();
    }

    init() {
        this.loadUnreadNotifications();
        this.setupEventListeners();
        this.applyDarkMode();
        
        // Refresh notifications every 30 seconds
        setInterval(() => this.loadUnreadNotifications(), 30000);
    }

    setupEventListeners() {
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleDarkMode();
            });
        }

        // Mark all as read
        const markAllReadBtn = document.getElementById('markAllAsRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }

        // Individual notification clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-item')) {
                const notificationId = e.target.closest('.notification-item').dataset.notificationId;
                if (notificationId) {
                    this.markAsRead(notificationId);
                }
            }
        });

        // Login Activity Link
        const loginActivityLink = document.getElementById('loginActivityLink');
        if (loginActivityLink) {
            loginActivityLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.showLoginActivity();
            });
        }

        // Profile dark mode toggle
        const darkModeToggleProfile = document.getElementById('darkModeToggleProfile');
        if (darkModeToggleProfile) {
            darkModeToggleProfile.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleDarkMode();
            });
        }
    }

    async loadUnreadNotifications() {
        try {
            const response = await fetch('/notifications/unread');
            const data = await response.json();
            
            this.unreadCount = data.unread_count;
            this.updateNotificationBadge();
            this.updateNotificationDropdown(data.notifications);
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    updateNotificationBadge() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    updateNotificationDropdown(notifications) {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="nk-notification-item text-center py-4">
                    <div class="nk-notification-content">
                        <div class="nk-notification-text text-muted">No new notifications</div>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = notifications.map(notification => {
            const data = notification.formatted_data || this.formatNotificationData(notification);
            return `
                <div class="nk-notification-item dropdown-inner notification-item" data-notification-id="${notification.id}">
                    <div class="nk-notification-icon">
                        <em class="icon icon-circle bg-${data.type}-dim ${data.icon}"></em>
                    </div>
                    <div class="nk-notification-content">
                        <div class="nk-notification-text">${data.title}</div>
                        <div class="nk-notification-text text-muted small">${data.message}</div>
                        <div class="nk-notification-time">${data.time}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    formatNotificationData(notification) {
        const data = notification.data || {};
        return {
            title: notification.title || data.title || 'Notification',
            message: notification.message || data.message || '',
            icon: notification.icon || data.icon || 'ni ni-bell',
            type: this.getNotificationType(notification.type),
            time: this.formatTime(notification.created_at)
        };
    }

    getNotificationType(type) {
        const typeMap = {
            'App\\Notifications\\BookPublished': 'success',
            'App\\Notifications\\BookSold': 'info',
            'App\\Notifications\\PayoutProcessed': 'success',
            'App\\Notifications\\SystemAlert': 'warning',
        };
        return typeMap[type] || 'info';
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInMinutes = Math.floor((now - date) / (1000 * 60));
        
        if (diffInMinutes < 1) return 'Just now';
        if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
        if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
        return `${Math.floor(diffInMinutes / 1440)}d ago`;
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.unreadCount = 0;
                this.updateNotificationBadge();
                this.loadUnreadNotifications();
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.loadUnreadNotifications();
            }
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }

    async toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        
        try {
            await fetch('/toggle-dark-mode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ dark_mode: this.darkMode })
            });
        } catch (error) {
            console.error('Error toggling dark mode:', error);
        }

        this.applyDarkMode();
    }

    applyDarkMode() {
        const body = document.body;
        const darkModeIcon = document.getElementById('darkModeIcon');
        
        if (this.darkMode) {
            body.classList.add('dark-mode');
            if (darkModeIcon) {
                darkModeIcon.className = 'icon ni ni-sun';
            }
        } else {
            body.classList.remove('dark-mode');
            if (darkModeIcon) {
                darkModeIcon.className = 'icon ni ni-moon';
            }
        }
    }

    showLoginActivity() {
        // Create mock login activity data
        const loginActivities = [
            {
                ip: '192.168.1.100',
                location: 'New York, USA',
                device: 'Chrome on Windows',
                time: '2 hours ago',
                status: 'success'
            },
            {
                ip: '10.0.0.1',
                location: 'London, UK',
                device: 'Safari on MacOS',
                time: '1 day ago',
                status: 'success'
            },
            {
                ip: '203.0.113.1',
                location: 'Unknown Location',
                device: 'Firefox on Linux',
                time: '3 days ago',
                status: 'warning'
            }
        ];

        const modalHtml = `
            <div class="modal fade login-activity-modal" id="loginActivityModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Login Activity</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-4">Recent login activity for your account</p>
                            ${loginActivities.map(activity => `
                                <div class="login-activity-item d-flex align-items-center">
                                    <div class="activity-icon activity-${activity.status}">
                                        <em class="icon ni ni-${activity.status === 'success' ? 'check' : 'alert-circle'}"></em>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">${activity.device}</div>
                                        <div class="text-muted small">
                                            ${activity.ip} • ${activity.location}
                                        </div>
                                    </div>
                                    <div class="text-muted small">
                                        ${activity.time}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('loginActivityModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('loginActivityModal'));
        modal.show();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
});
