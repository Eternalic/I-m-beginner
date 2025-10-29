<?php
require_once __DIR__ . '/db.php';

$message = '';
$error = '';

if (isset($_POST['install_notifications'])) {
    try {
        // Read the SQL file
        $sql_file = 'database/notifications_system.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found: $sql_file");
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Split by semicolon and execute each statement
        $statements = explode(';', $sql_content);
        $success_count = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                if ($conn->query($statement)) {
                    $success_count++;
                } else {
                    $error .= "Error executing: " . $conn->error . "<br>";
                }
            }
        }
        
        if ($success_count > 0) {
            $message = "✅ Successfully installed notification system! Created $success_count database objects.";
        }
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Check if notification tables exist
$tables_exist = false;
$table_check_sql = "SHOW TABLES LIKE 'notifications'";
$table_result = $conn->query($table_check_sql);
if ($table_result && $table_result->num_rows > 0) {
    $tables_exist = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Notification System - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .container {
            max-width: 800px;
        }
        
        .card {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .card-header {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border-radius: 15px 15px 0 0;
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .card-title {
            font-weight: bold;
            margin: 0;
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
            color: #22c55e;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .status-check {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .status-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .status-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feature-list i {
            color: #ffd700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center py-3">
                <h2 class="card-title">
                    <i class="fas fa-bell"></i> Install Notification System
                </h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Status Check -->
                <div class="status-check <?php echo $tables_exist ? 'status-success' : 'status-warning'; ?>">
                    <h5><i class="fas fa-info-circle"></i> Current Status</h5>
                    <?php if ($tables_exist): ?>
                        <p class="mb-0">✅ Notification system is already installed.</p>
                        <p class="mb-0">You can now use the notification features.</p>
                    <?php else: ?>
                        <p class="mb-0">⚠️ Notification system is not installed yet.</p>
                        <p class="mb-0">Click the button below to install it.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Features List -->
                <div class="mb-4">
                    <h5><i class="fas fa-star"></i> Notification System Features:</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Real-time notifications for new messages</li>
                        <li><i class="fas fa-check"></i> Booking status change notifications</li>
                        <li><i class="fas fa-check"></i> System notifications and announcements</li>
                        <li><i class="fas fa-check"></i> Customizable notification settings</li>
                        <li><i class="fas fa-check"></i> Email, push, and SMS notification options</li>
                        <li><i class="fas fa-check"></i> Notification history and management</li>
                        <li><i class="fas fa-check"></i> Beautiful notification bell interface</li>
                        <li><i class="fas fa-check"></i> Mobile-responsive design</li>
                    </ul>
                </div>
                
                <!-- Install Button -->
                <form method="post">
                    <div class="text-center">
                        <button type="submit" name="install_notifications" class="btn btn-primary btn-lg">
                            <i class="fas fa-download"></i> 
                            <?php echo $tables_exist ? 'Reinstall Notification System' : 'Install Notification System'; ?>
                        </button>
                    </div>
                </form>
                
                <!-- What will be created -->
                <div class="mt-4">
                    <h5><i class="fas fa-database"></i> What will be created:</h5>
                    <ul class="feature-list">
                        <li><i class="fas fa-table"></i> <strong>notifications</strong> - Stores all notification data</li>
                        <li><i class="fas fa-cog"></i> <strong>notification_settings</strong> - User notification preferences</li>
                        <li><i class="fas fa-link"></i> Foreign key relationships to users, managers, and hotels</li>
                        <li><i class="fas fa-bell"></i> Sample notifications for testing</li>
                        <li><i class="fas fa-user"></i> Default settings for existing users and managers</li>
                    </ul>
                </div>
                
                <!-- Navigation -->
                <div class="mt-4 text-center">
                    <a href="SignedIn_homepage.php" class="btn btn-primary me-2">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                    <a href="manager/manager_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Manager Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
