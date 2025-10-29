<?php
require_once 'db.php';

$message = '';
$error = '';

if (isset($_POST['create_tables'])) {
    try {
        // Read the SQL file
        $sql_file = 'database/chat_system.sql';
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
            $message = "✅ Successfully created $success_count database objects!";
        }
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Check if tables exist
$tables_exist = false;
$table_check_sql = "SHOW TABLES LIKE 'chat_conversations'";
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
    <title>Create Chat Tables - Ered Hotel</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center py-3">
                <h2 class="card-title">
                    <i class="fas fa-database"></i> Create Chat System Tables
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
                        <p class="mb-0">✅ Chat tables already exist in the database.</p>
                        <p class="mb-0">You can now use the chat management system.</p>
                    <?php else: ?>
                        <p class="mb-0">⚠️ Chat tables do not exist yet.</p>
                        <p class="mb-0">Click the button below to create them.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Create Tables Form -->
                <form method="post">
                    <div class="text-center">
                        <button type="submit" name="create_tables" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle"></i> 
                            <?php echo $tables_exist ? 'Recreate Chat Tables' : 'Create Chat Tables'; ?>
                        </button>
                    </div>
                </form>
                
                <!-- Information -->
                <div class="mt-4">
                    <h5><i class="fas fa-info-circle"></i> What will be created:</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-table"></i> <strong>chat_conversations</strong> - Stores chat conversations between users and managers</li>
                        <li><i class="fas fa-comments"></i> <strong>chat_messages</strong> - Stores individual messages in conversations</li>
                        <li><i class="fas fa-link"></i> Foreign key relationships to users and hotels tables</li>
                    </ul>
                </div>
                
                <!-- Navigation -->
                <div class="mt-4 text-center">
                    <a href="manager/manager_dashboard.php" class="btn btn-primary me-2">
                        <i class="fas fa-tachometer-alt"></i> Manager Dashboard
                    </a>
                    <a href="manager/chat_management.php" class="btn btn-primary">
                        <i class="fas fa-comments"></i> Chat Management
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
