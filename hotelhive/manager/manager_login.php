<?php
require_once '../db.php';

// Check if manager is already logged in
if (isset($_SESSION['manager_id'])) {
    header("Location: manager_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $manager_name = trim($_POST['manager_name']);
    $password = $_POST['password'];

    if (empty($manager_name) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Prepare a select statement
        $sql = "SELECT manager_id, manager_name, manager_password, hotel_id FROM hotel_managers WHERE manager_name = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $manager_name);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($manager_id, $manager_name, $hashed_password, $hotel_id);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION['manager_id'] = $manager_id;
                            $_SESSION['manager_name'] = $manager_name;
                            $_SESSION['hotel_id'] = $hotel_id;
                            
                            // Show success message before redirect
                            $success_message = true;
                        } else {
                            $error = "Invalid username or password.";
                        }
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Manager Login - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            transition: opacity 0.5s ease;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .login-header p {
            color: #cbd5e1;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            background: rgba(0, 0, 0, 0.8);
            color: #ffffff;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
            background: rgba(0, 0, 0, 0.9);
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }


        .btn-login {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .error-message {
            color: #fca5a5;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.3);
            font-size: 0.875rem;
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            text-align: center;
        }

        .customer-signin {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 215, 0, 0.3);
        }

        .customer-signin a {
            color: #ffd700;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .customer-signin a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .input-group-text {
            background-color: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-right: none;
            color: #ffd700;
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus {
            border-left: 1px solid #e2e8f0;
        }

        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .success-overlay.show {
            display: flex;
            opacity: 1;
        }

        .success-content {
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.5s ease;
        }

        .success-overlay.show .success-content {
            transform: translateY(0);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            animation: pulse 2s infinite;
        }

        .success-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: var(--primary-color);
            opacity: 0.2;
            animation: ripple 2s infinite;
        }

        .success-icon i {
            color: white;
            font-size: 40px;
            position: relative;
            z-index: 1;
        }

        .success-message {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .manager-name {
            font-size: 1.2rem;
            color: #4b5563;
            margin-bottom: 20px;
        }

        .loading-text {
            color: #6b7280;
            font-size: 0.9rem;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 0.2;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        .login-container.hide {
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="login-container <?php echo isset($success_message) && $success_message ? 'hide' : ''; ?>">
        <div class="login-header">
            <h1>Hotel Manager Login</h1>
            <p>Access your hotel management dashboard</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="manager_name" class="form-control" placeholder="Manager Username" required>
                </div>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>

        <div class="customer-signin">
            <a href="manager_signup.php">
                <i class="fas fa-user-plus"></i> Manager Sign Up
            </a>
        </div>
    </div>

    <?php if (isset($success_message) && $success_message): ?>
    <div class="success-overlay show">
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="success-message">Welcome Back!</div>
            <div class="manager-name"><?php echo htmlspecialchars($manager_name); ?></div>
            <div class="loading-text">Redirecting to your dashboard...</div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'manager_dashboard.php';
        }, 2000);
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 