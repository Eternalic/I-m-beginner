<?php
require_once __DIR__ . '/../db.php';

class NotificationManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a new notification
     * 创建新通知
     */
    public function createNotification($data) {
        $sql = "INSERT INTO notifications (user_id, manager_id, hotel_id, type, title, message, data, is_important) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $data_json = isset($data['data']) ? json_encode($data['data']) : null;
        
        $stmt->bind_param("iiissssi", 
            $data['user_id'], 
            $data['manager_id'], 
            $data['hotel_id'], 
            $data['type'], 
            $data['title'], 
            $data['message'], 
            $data_json, 
            $data['is_important']
        );
        
        if ($stmt->execute()) {
            $notification_id = $this->conn->insert_id;
            
            // Send real-time notification if user is online
            $this->sendRealTimeNotification($notification_id, $data);
            
            return $notification_id;
        }
        
        return false;
    }
    
    /**
     * Get notifications for a user
     * 获取用户通知
     */
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false) {
        // Check if notifications table exists
        $table_check = $this->conn->query("SHOW TABLES LIKE 'notifications'");
        if (!$table_check || $table_check->num_rows == 0) {
            return [];
        }
        
        $sql = "SELECT n.*, h.name as hotel_name 
                FROM notifications n 
                LEFT JOIN hotels h ON n.hotel_id = h.hotel_id 
                WHERE n.user_id = ?";
        
        if ($unread_only) {
            $sql .= " AND n.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Get notifications for a manager
     * 获取经理通知
     */
    public function getManagerNotifications($manager_id, $limit = 20, $unread_only = false) {
        // Check if notifications table exists
        $table_check = $this->conn->query("SHOW TABLES LIKE 'notifications'");
        if (!$table_check || $table_check->num_rows == 0) {
            return [];
        }
        
        $sql = "SELECT n.*, h.name as hotel_name 
                FROM notifications n 
                LEFT JOIN hotels h ON n.hotel_id = h.hotel_id 
                WHERE n.manager_id = ?";
        
        if ($unread_only) {
            $sql .= " AND n.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $manager_id, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     * 标记通知为已读
     */
    public function markAsRead($notification_id, $user_id = null, $manager_id = null) {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?";
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
        } elseif ($manager_id) {
            $sql .= " AND manager_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($user_id) {
            $stmt->bind_param("ii", $notification_id, $user_id);
        } elseif ($manager_id) {
            $stmt->bind_param("ii", $notification_id, $manager_id);
        } else {
            $stmt->bind_param("i", $notification_id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Mark all notifications as read
     * 标记所有通知为已读
     */
    public function markAllAsRead($user_id = null, $manager_id = null) {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0";
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
        } elseif ($manager_id) {
            $sql .= " AND manager_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($user_id) {
            $stmt->bind_param("i", $user_id);
        } elseif ($manager_id) {
            $stmt->bind_param("i", $manager_id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Get unread notification count
     * 获取未读通知数量
     */
    public function getUnreadCount($user_id = null, $manager_id = null) {
        // Check if notifications table exists
        $table_check = $this->conn->query("SHOW TABLES LIKE 'notifications'");
        if (!$table_check || $table_check->num_rows == 0) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
        } elseif ($manager_id) {
            $sql .= " AND manager_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($user_id) {
            $stmt->bind_param("i", $user_id);
        } elseif ($manager_id) {
            $stmt->bind_param("i", $manager_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    }
    
    /**
     * Delete notification
     * 删除通知
     */
    public function deleteNotification($notification_id, $user_id = null, $manager_id = null) {
        $sql = "DELETE FROM notifications WHERE notification_id = ?";
        
        if ($user_id) {
            $sql .= " AND user_id = ?";
        } elseif ($manager_id) {
            $sql .= " AND manager_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($user_id) {
            $stmt->bind_param("ii", $notification_id, $user_id);
        } elseif ($manager_id) {
            $stmt->bind_param("ii", $notification_id, $manager_id);
        } else {
            $stmt->bind_param("i", $notification_id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Send real-time notification using WebSocket or Server-Sent Events
     * 发送实时通知
     */
    private function sendRealTimeNotification($notification_id, $data) {
        // This would typically use WebSocket or Server-Sent Events
        // For now, we'll store it in a simple file-based queue
        $notification_data = [
            'notification_id' => $notification_id,
            'user_id' => $data['user_id'],
            'manager_id' => $data['manager_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'timestamp' => time()
        ];
        
        $queue_file = 'notifications_queue.json';
        $queue = [];
        
        if (file_exists($queue_file)) {
            $queue = json_decode(file_get_contents($queue_file), true) ?: [];
        }
        
        $queue[] = $notification_data;
        file_put_contents($queue_file, json_encode($queue));
    }
    
    /**
     * Get notification settings
     * 获取通知设置
     */
    public function getNotificationSettings($user_id = null, $manager_id = null) {
        // Check if notification_settings table exists
        $table_check = $this->conn->query("SHOW TABLES LIKE 'notification_settings'");
        if (!$table_check || $table_check->num_rows == 0) {
            // Return default settings if table doesn't exist
            return [
                'message' => ['email_enabled' => 1, 'push_enabled' => 1, 'sms_enabled' => 0],
                'booking' => ['email_enabled' => 1, 'push_enabled' => 1, 'sms_enabled' => 0],
                'system' => ['email_enabled' => 1, 'push_enabled' => 1, 'sms_enabled' => 0],
                'payment' => ['email_enabled' => 1, 'push_enabled' => 1, 'sms_enabled' => 0],
                'review' => ['email_enabled' => 1, 'push_enabled' => 1, 'sms_enabled' => 0]
            ];
        }
        
        $sql = "SELECT * FROM notification_settings WHERE";
        
        if ($user_id) {
            $sql .= " user_id = ?";
        } elseif ($manager_id) {
            $sql .= " manager_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($user_id) {
            $stmt->bind_param("i", $user_id);
        } elseif ($manager_id) {
            $stmt->bind_param("i", $manager_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['type']] = $row;
        }
        
        return $settings;
    }
    
    /**
     * Update notification settings
     * 更新通知设置
     */
    public function updateNotificationSettings($user_id, $type, $email_enabled, $push_enabled, $sms_enabled) {
        $sql = "INSERT INTO notification_settings (user_id, type, email_enabled, push_enabled, sms_enabled) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                email_enabled = VALUES(email_enabled),
                push_enabled = VALUES(push_enabled),
                sms_enabled = VALUES(sms_enabled),
                updated_at = NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isiii", $user_id, $type, $email_enabled, $push_enabled, $sms_enabled);
        
        return $stmt->execute();
    }
    
    /**
     * Create notification for new message
     * 为新消息创建通知
     */
    public function notifyNewMessage($conversation_id, $sender_type, $message_content) {
        // Get conversation details
        $sql = "SELECT c.*, u.username as user_name, h.name as hotel_name 
                FROM chat_conversations c 
                JOIN users u ON c.user_id = u.user_id 
                JOIN hotels h ON c.hotel_id = h.hotel_id 
                WHERE c.conversation_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $conversation = $stmt->get_result()->fetch_assoc();
        
        if ($sender_type === 'user') {
            // Notify manager
            $notification_data = [
                'manager_id' => $conversation['hotel_id'], // This should be actual manager_id
                'hotel_id' => $conversation['hotel_id'],
                'type' => 'message',
                'title' => 'New Message from ' . $conversation['user_name'],
                'message' => substr($message_content, 0, 100) . (strlen($message_content) > 100 ? '...' : ''),
                'is_important' => 0,
                'data' => ['conversation_id' => $conversation_id]
            ];
        } else {
            // Notify user
            $notification_data = [
                'user_id' => $conversation['user_id'],
                'hotel_id' => $conversation['hotel_id'],
                'type' => 'message',
                'title' => 'New Message from ' . $conversation['hotel_name'],
                'message' => substr($message_content, 0, 100) . (strlen($message_content) > 100 ? '...' : ''),
                'is_important' => 0,
                'data' => ['conversation_id' => $conversation_id]
            ];
        }
        
        return $this->createNotification($notification_data);
    }
    
    /**
     * Create notification for booking status change
     * 为预订状态变更创建通知
     */
    public function notifyBookingStatusChange($booking_id, $new_status) {
        // Get booking details
        $sql = "SELECT b.*, u.username, h.name as hotel_name 
                FROM bookings b 
                JOIN users u ON b.user_id = u.user_id 
                JOIN hotels h ON b.hotel_id = h.hotel_id 
                WHERE b.booking_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        $status_messages = [
            'confirmed' => 'Your booking has been confirmed!',
            'cancelled' => 'Your booking has been cancelled.',
            'pending' => 'Your booking is pending confirmation.'
        ];
        
        $notification_data = [
            'user_id' => $booking['user_id'],
            'hotel_id' => $booking['hotel_id'],
            'type' => 'booking',
            'title' => 'Booking Update - ' . $booking['book_number'],
            'message' => $status_messages[$new_status] ?? 'Your booking status has been updated.',
            'is_important' => 1,
            'data' => ['booking_id' => $booking_id, 'status' => $new_status]
        ];
        
        return $this->createNotification($notification_data);
    }
}
?>
