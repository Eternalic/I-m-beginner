<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/NotificationManager.php';

// Check if user is signed in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['manager_id'])) {
    header("Location: signin.php");
    exit;
}

$notificationManager = new NotificationManager($conn);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $types = ['message', 'booking', 'system', 'payment', 'review'];
    
    foreach ($types as $type) {
        $email_enabled = isset($_POST[$type . '_email']) ? 1 : 0;
        $push_enabled = isset($_POST[$type . '_push']) ? 1 : 0;
        $sms_enabled = isset($_POST[$type . '_sms']) ? 1 : 0;
        
        if (isset($_SESSION['user_id'])) {
            $notificationManager->updateNotificationSettings($_SESSION['user_id'], $type, $email_enabled, $push_enabled, $sms_enabled);
        } elseif (isset($_SESSION['manager_id'])) {
            $notificationManager->updateNotificationSettings($_SESSION['manager_id'], $type, $email_enabled, $push_enabled, $sms_enabled);
        }
    }
    
    $success_message = "Notification settings updated successfully!";
}

// Get current settings
if (isset($_SESSION['user_id'])) {
    $settings = $notificationManager->getNotificationSettings($_SESSION['user_id']);
    $notifications = $notificationManager->getUserNotifications($_SESSION['user_id'], 50);
} elseif (isset($_SESSION['manager_id'])) {
    $settings = $notificationManager->getNotificationSettings(null, $_SESSION['manager_id']);
    $notifications = $notificationManager->getManagerNotifications($_SESSION['manager_id'], 50);
}

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notificationManager->markAsRead($_POST['notification_id'], $_SESSION['user_id'] ?? null, $_SESSION['manager_id'] ?? null);
    } elseif (isset($_POST['delete'])) {
        $notificationManager->deleteNotification($_POST['notification_id'], $_SESSION['user_id'] ?? null, $_SESSION['manager_id'] ?? null);
    } elseif (isset($_POST['mark_all_read'])) {
        $notificationManager->markAllAsRead($_SESSION['user_id'] ?? null, $_SESSION['manager_id'] ?? null);
    }
    
    header("Location: notifications.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
            min-height: 100vh;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            padding: 12px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .main-content {
            margin-top: 80px;
            padding: 2rem 0;
        }
        
        .page-header {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .page-title {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #cbd5e1;
            font-size: 1.1rem;
        }
        
        .content-section {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .section-title {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }
        
        .notification-item {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
            border-color: rgba(255, 215, 0, 0.6);
        }
        
        .notification-item.unread {
            background: rgba(255, 215, 0, 0.1);
            border-left: 4px solid #ffd700;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .notification-title {
            color: #ffd700;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .notification-time {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .notification-message {
            color: #cbd5e1;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
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
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-mark-read {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .btn-mark-read:hover {
            background: rgba(34, 197, 94, 0.3);
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        
        .settings-form {
            display: grid;
            gap: 1.5rem;
        }
        
        .setting-group {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .setting-title {
            color: #ffd700;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .setting-options {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .setting-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .setting-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #ffd700;
        }
        
        .setting-option label {
            color: #cbd5e1;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .no-notifications {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        
        .no-notifications i {
            font-size: 64px;
            margin-bottom: 1rem;
            color: #6b7280;
        }
        
        .no-notifications h3 {
            color: #cbd5e1;
            margin-bottom: 0.5rem;
        }
        
        .back-link {
            color: #ffd700;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="<?php echo isset($_SESSION['manager_id']) ? 'manager/manager_dashboard.php' : 'SignedIn_homepage.php'; ?>" class="logo">Ered Hotel</a>
            <div>
                <a href="<?php echo isset($_SESSION['manager_id']) ? 'manager/manager_dashboard.php' : 'SignedIn_homepage.php'; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-bell"></i> Notifications
            </h1>
            <p class="page-subtitle">Manage your notification preferences and view your notification history</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Notification Settings -->
        <div class="content-section">
            <h2 class="section-title">
                <i class="fas fa-cog"></i> Notification Settings
            </h2>
            
            <form method="POST" class="settings-form">
                <div class="setting-group">
                    <h3 class="setting-title">
                        <i class="fas fa-comments"></i> Message Notifications
                    </h3>
                    <div class="setting-options">
                        <div class="setting-option">
                            <input type="checkbox" id="message_email" name="message_email" <?php echo ($settings['message']['email_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="message_email">Email</label>
                        </div>
                        <div class="setting-option">
                            <input type="checkbox" id="message_push" name="message_push" <?php echo ($settings['message']['push_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="message_push">Push Notifications</label>
                        </div>
                        <div class="setting-option">
                            <input type="checkbox" id="message_sms" name="message_sms" <?php echo ($settings['message']['sms_enabled'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="message_sms">SMS</label>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3 class="setting-title">
                        <i class="fas fa-calendar-check"></i> Booking Notifications
                    </h3>
                    <div class="setting-options">
                        <div class="setting-option">
                            <input type="checkbox" id="booking_email" name="booking_email" <?php echo ($settings['booking']['email_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="booking_email">Email</label>
                        </div>
                        <div class="setting-option">
                            <input type="checkbox" id="booking_push" name="booking_push" <?php echo ($settings['booking']['push_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="booking_push">Push Notifications</label>
                        </div>
                        <div class="setting-option">
                            <input type="checkbox" id="booking_sms" name="booking_sms" <?php echo ($settings['booking']['sms_enabled'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="booking_sms">SMS</label>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3 class="setting-title">
                        <i class="fas fa-info-circle"></i> System Notifications
                    </h3>
                    <div class="setting-options">
                        <div class="setting-option">
                            <input type="checkbox" id="system_email" name="system_email" <?php echo ($settings['system']['email_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="system_email">Email</label>
                        </div>
                        <div class="setting-option">
                            <input type="checkbox" id="system_push" name="system_push" <?php echo ($settings['system']['push_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="system_push">Push Notifications</label>
                        </div>
                        <div class="setting-option">
                            <input type="checkbox" id="system_sms" name="system_sms" <?php echo ($settings['system']['sms_enabled'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="system_sms">SMS</label>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>
        </div>

        <!-- Notification History -->
        <div class="content-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Notification History
                </h2>
                <?php if (!empty($notifications)): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-primary btn-sm">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications yet</h3>
                    <p>You'll see your notifications here when you receive them.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-header">
                            <h4 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <span class="notification-time"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></span>
                        </div>
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        <div class="notification-meta">
                            <span class="notification-type <?php echo $notification['type']; ?>">
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                        <button type="submit" name="mark_read" class="btn-sm btn-mark-read">
                                            <i class="fas fa-check"></i> Mark Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?')">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <button type="submit" name="delete" class="btn-sm btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
