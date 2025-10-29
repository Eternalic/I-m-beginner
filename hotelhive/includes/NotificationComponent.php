<?php
// Notification Component for including in pages
// 通知组件，用于在页面中包含

function renderNotificationBell($user_id = null, $manager_id = null) {
    ?>
    <div class="notification-bell" id="notificationBell">
        <button class="bell-button" onclick="toggleNotificationPanel()">
            <i class="fas fa-bell"></i>
            <span class="notification-count" id="notificationCount">0</span>
        </button>
        
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h3>Notifications</h3>
                <div class="notification-actions">
                    <button onclick="markAllAsRead()" class="mark-all-btn">Mark All Read</button>
                    <button onclick="toggleNotificationPanel()" class="close-btn">&times;</button>
                </div>
            </div>
            
            <div class="notification-list" id="notificationList">
                <div class="loading-notifications">
                    <i class="fas fa-spinner fa-spin"></i> Loading notifications...
                </div>
            </div>
            
            <div class="notification-footer">
                <a href="notifications.php" class="view-all-link">View All Notifications</a>
            </div>
        </div>
    </div>
    
    <style>
        .notification-bell {
            position: relative;
            display: inline-block;
        }
        
        .bell-button {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .bell-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
        }
        
        .bell-button i {
            color: #000000;
            font-size: 18px;
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border: 2px solid #ffffff;
            min-width: 20px;
        }
        
        .notification-count.hidden {
            display: none;
        }
        
        .notification-panel {
            position: absolute;
            top: 60px;
            right: 0;
            width: 350px;
            max-height: 500px;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            overflow: hidden;
        }
        
        .notification-panel.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-header {
            padding: 20px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h3 {
            color: #ffd700;
            margin: 0;
            font-size: 18px;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .mark-all-btn {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #ffd700;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mark-all-btn:hover {
            background: rgba(255, 215, 0, 0.2);
        }
        
        .close-btn {
            background: none;
            border: none;
            color: #cbd5e1;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .close-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
        }
        
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .loading-notifications {
            padding: 20px;
            text-align: center;
            color: #cbd5e1;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-item:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .notification-item.unread {
            background: rgba(255, 215, 0, 0.1);
            border-left: 4px solid #ffd700;
        }
        
        .notification-item.unread::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 8px;
            background: #ffd700;
            border-radius: 50%;
        }
        
        .notification-title {
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .notification-message {
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 8px;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #94a3b8;
        }
        
        .notification-time {
            font-size: 11px;
        }
        
        .notification-type {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .notification-type.message {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        
        .notification-type.booking {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        
        .notification-type.system {
            background: rgba(168, 85, 247, 0.2);
            color: #a78bfa;
        }
        
        .notification-type.payment {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .notification-footer {
            padding: 15px 20px;
            border-top: 2px solid rgba(255, 215, 0, 0.3);
            text-align: center;
        }
        
        .view-all-link {
            color: #ffd700;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .view-all-link:hover {
            color: #ffffff;
        }
        
        .no-notifications {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }
        
        .no-notifications i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #6b7280;
        }
        
        .no-notifications h4 {
            color: #cbd5e1;
            margin-bottom: 10px;
        }
        
        .no-notifications p {
            font-size: 14px;
            margin: 0;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .notification-panel {
                width: 300px;
                right: -50px;
            }
            
            .notification-item {
                padding: 12px 15px;
            }
            
            .notification-title {
                font-size: 13px;
            }
            
            .notification-message {
                font-size: 12px;
            }
        }
    </style>
    
    <script>
        let notificationPanelOpen = false;
        let notificationCheckInterval;
        
        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            loadNotificationCount();
            loadNotifications();
            
            // Check for new notifications every 30 seconds
            notificationCheckInterval = setInterval(checkForNewNotifications, 30000);
        });
        
        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            notificationPanelOpen = !notificationPanelOpen;
            
            if (notificationPanelOpen) {
                panel.classList.add('show');
                loadNotifications();
            } else {
                panel.classList.remove('show');
            }
        }
        
        function loadNotificationCount() {
            fetch('api/notifications.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const countElement = document.getElementById('notificationCount');
                        if (data.count > 0) {
                            countElement.textContent = data.count;
                            countElement.classList.remove('hidden');
                        } else {
                            countElement.classList.add('hidden');
                        }
                    }
                })
                .catch(error => console.error('Error loading notification count:', error));
        }
        
        function loadNotifications() {
            const listElement = document.getElementById('notificationList');
            listElement.innerHTML = '<div class="loading-notifications"><i class="fas fa-spinner fa-spin"></i> Loading notifications...</div>';
            
            fetch('api/notifications.php?action=list&limit=10')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderNotifications(data.notifications);
                    } else {
                        listElement.innerHTML = '<div class="no-notifications"><i class="fas fa-bell-slash"></i><h4>No notifications</h4><p>You\'re all caught up!</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    listElement.innerHTML = '<div class="no-notifications"><i class="fas fa-exclamation-triangle"></i><h4>Error loading notifications</h4><p>Please try again later.</p></div>';
                });
        }
        
        function renderNotifications(notifications) {
            const listElement = document.getElementById('notificationList');
            
            if (notifications.length === 0) {
                listElement.innerHTML = '<div class="no-notifications"><i class="fas fa-bell-slash"></i><h4>No notifications</h4><p>You\'re all caught up!</p></div>';
                return;
            }
            
            listElement.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.is_read ? '' : 'unread'}" onclick="handleNotificationClick(${notification.notification_id}, ${notification.is_read})">
                    <div class="notification-title">${escapeHtml(notification.title)}</div>
                    <div class="notification-message">${escapeHtml(notification.message)}</div>
                    <div class="notification-meta">
                        <span class="notification-type ${notification.type}">${notification.type}</span>
                        <span class="notification-time">${formatTime(notification.created_at)}</span>
                    </div>
                </div>
            `).join('');
        }
        
        function handleNotificationClick(notificationId, isRead) {
            if (!isRead) {
                markAsRead(notificationId);
            }
            
            // Handle notification action based on type
            // This could redirect to relevant pages
        }
        
        function markAsRead(notificationId) {
            fetch('api/notifications.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotificationCount();
                    loadNotifications();
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
        
        function markAllAsRead() {
            fetch('api/notifications.php?action=mark_all_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotificationCount();
                    loadNotifications();
                }
            })
            .catch(error => console.error('Error marking all notifications as read:', error));
        }
        
        function checkForNewNotifications() {
            fetch('api/notifications.php?action=queue')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        loadNotificationCount();
                        
                        // Show browser notification if permission granted
                        if (Notification.permission === 'granted') {
                            data.notifications.forEach(notification => {
                                new Notification(notification.title, {
                                    body: notification.message,
                                    icon: 'images/logo.png'
                                });
                            });
                        }
                    }
                })
                .catch(error => console.error('Error checking for new notifications:', error));
        }
        
        function formatTime(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = now - time;
            
            if (diff < 60000) { // Less than 1 minute
                return 'Just now';
            } else if (diff < 3600000) { // Less than 1 hour
                return Math.floor(diff / 60000) + 'm ago';
            } else if (diff < 86400000) { // Less than 1 day
                return Math.floor(diff / 3600000) + 'h ago';
            } else {
                return Math.floor(diff / 86400000) + 'd ago';
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
        
        // Close panel when clicking outside
        document.addEventListener('click', function(event) {
            const bell = document.getElementById('notificationBell');
            const panel = document.getElementById('notificationPanel');
            
            if (!bell.contains(event.target) && notificationPanelOpen) {
                toggleNotificationPanel();
            }
        });
    </script>
    <?php
}
?>
