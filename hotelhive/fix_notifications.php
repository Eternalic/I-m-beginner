<?php
require_once __DIR__ . '/db.php';

echo "<h2>🔧 Fixing Notification System...</h2>";

try {
    // Create notifications table
    $notifications_sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `notification_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `manager_id` int(11) DEFAULT NULL,
        `hotel_id` int(11) DEFAULT NULL,
        `type` enum('message','booking','system','payment','review') NOT NULL,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `data` json DEFAULT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `is_important` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `read_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`notification_id`),
        KEY `user_id` (`user_id`),
        KEY `manager_id` (`manager_id`),
        KEY `hotel_id` (`hotel_id`),
        KEY `type` (`type`),
        KEY `is_read` (`is_read`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($notifications_sql)) {
        echo "<p style='color: green;'>✅ Notifications table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating notifications table: " . $conn->error . "</p>";
    }
    
    // Create notification_settings table
    $settings_sql = "CREATE TABLE IF NOT EXISTS `notification_settings` (
        `setting_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `manager_id` int(11) DEFAULT NULL,
        `type` enum('message','booking','system','payment','review') NOT NULL,
        `email_enabled` tinyint(1) DEFAULT 1,
        `push_enabled` tinyint(1) DEFAULT 1,
        `sms_enabled` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`setting_id`),
        UNIQUE KEY `unique_user_type` (`user_id`, `type`),
        UNIQUE KEY `unique_manager_type` (`manager_id`, `type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($settings_sql)) {
        echo "<p style='color: green;'>✅ Notification settings table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating notification settings table: " . $conn->error . "</p>";
    }
    
    // Insert default settings for existing users
    $users_sql = "SELECT user_id FROM users";
    $users_result = $conn->query($users_sql);
    
    if ($users_result && $users_result->num_rows > 0) {
        $types = ['message', 'booking', 'system', 'payment', 'review'];
        
        foreach ($types as $type) {
            $insert_sql = "INSERT IGNORE INTO notification_settings (user_id, type, email_enabled, push_enabled, sms_enabled) 
                          SELECT user_id, '$type', 1, 1, 0 FROM users";
            
            if ($conn->query($insert_sql)) {
                echo "<p style='color: green;'>✅ Default settings inserted for users - $type</p>";
            }
        }
    }
    
    // Insert default settings for existing managers
    $managers_sql = "SELECT manager_id FROM hotel_managers";
    $managers_result = $conn->query($managers_sql);
    
    if ($managers_result && $managers_result->num_rows > 0) {
        $types = ['message', 'booking', 'system', 'payment', 'review'];
        
        foreach ($types as $type) {
            $insert_sql = "INSERT IGNORE INTO notification_settings (manager_id, type, email_enabled, push_enabled, sms_enabled) 
                          SELECT manager_id, '$type', 1, 1, 0 FROM hotel_managers";
            
            if ($conn->query($insert_sql)) {
                echo "<p style='color: green;'>✅ Default settings inserted for managers - $type</p>";
            }
        }
    }
    
    echo "<h3 style='color: green;'>🎉 Notification system installation completed!</h3>";
    echo "<p>You can now use the notification features.</p>";
    echo "<p><a href='SignedIn_homepage.php' style='color: #ffd700;'>← Back to Homepage</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
