<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/NotificationManager.php';

header('Content-Type: application/json');

$notificationManager = new NotificationManager($conn);

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['manager_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $limit = (int)($_GET['limit'] ?? 20);
                    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
                    
                    if (isset($_SESSION['user_id'])) {
                        $notifications = $notificationManager->getUserNotifications($_SESSION['user_id'], $limit, $unread_only);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $notifications = $notificationManager->getManagerNotifications($_SESSION['manager_id'], $limit, $unread_only);
                    }
                    
                    echo json_encode(['success' => true, 'notifications' => $notifications]);
                    break;
                    
                case 'count':
                    if (isset($_SESSION['user_id'])) {
                        $count = $notificationManager->getUnreadCount($_SESSION['user_id']);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $count = $notificationManager->getUnreadCount(null, $_SESSION['manager_id']);
                    }
                    
                    echo json_encode(['success' => true, 'count' => $count]);
                    break;
                    
                case 'settings':
                    if (isset($_SESSION['user_id'])) {
                        $settings = $notificationManager->getNotificationSettings($_SESSION['user_id']);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $settings = $notificationManager->getNotificationSettings(null, $_SESSION['manager_id']);
                    }
                    
                    echo json_encode(['success' => true, 'settings' => $settings]);
                    break;
                    
                case 'queue':
                    // Get pending notifications from queue
                    $queue_file = 'notifications_queue.json';
                    $queue = [];
                    
                    if (file_exists($queue_file)) {
                        $queue = json_decode(file_get_contents($queue_file), true) ?: [];
                    }
                    
                    // Filter notifications for current user
                    $user_notifications = [];
                    foreach ($queue as $notification) {
                        if (isset($_SESSION['user_id']) && $notification['user_id'] == $_SESSION['user_id']) {
                            $user_notifications[] = $notification;
                        } elseif (isset($_SESSION['manager_id']) && $notification['manager_id'] == $_SESSION['manager_id']) {
                            $user_notifications[] = $notification;
                        }
                    }
                    
                    // Clear the queue after reading
                    if (!empty($user_notifications)) {
                        $remaining_queue = array_diff($queue, $user_notifications);
                        file_put_contents($queue_file, json_encode($remaining_queue));
                    }
                    
                    echo json_encode(['success' => true, 'notifications' => $user_notifications]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'mark_read':
                    $notification_id = (int)$_POST['notification_id'];
                    
                    if (isset($_SESSION['user_id'])) {
                        $result = $notificationManager->markAsRead($notification_id, $_SESSION['user_id']);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $result = $notificationManager->markAsRead($notification_id, null, $_SESSION['manager_id']);
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'mark_all_read':
                    if (isset($_SESSION['user_id'])) {
                        $result = $notificationManager->markAllAsRead($_SESSION['user_id']);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $result = $notificationManager->markAllAsRead(null, $_SESSION['manager_id']);
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'delete':
                    $notification_id = (int)$_POST['notification_id'];
                    
                    if (isset($_SESSION['user_id'])) {
                        $result = $notificationManager->deleteNotification($notification_id, $_SESSION['user_id']);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $result = $notificationManager->deleteNotification($notification_id, null, $_SESSION['manager_id']);
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                case 'update_settings':
                    $type = $_POST['type'];
                    $email_enabled = isset($_POST['email_enabled']) ? 1 : 0;
                    $push_enabled = isset($_POST['push_enabled']) ? 1 : 0;
                    $sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;
                    
                    if (isset($_SESSION['user_id'])) {
                        $result = $notificationManager->updateNotificationSettings($_SESSION['user_id'], $type, $email_enabled, $push_enabled, $sms_enabled);
                    } elseif (isset($_SESSION['manager_id'])) {
                        $result = $notificationManager->updateNotificationSettings($_SESSION['manager_id'], $type, $email_enabled, $push_enabled, $sms_enabled);
                    }
                    
                    echo json_encode(['success' => $result]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
