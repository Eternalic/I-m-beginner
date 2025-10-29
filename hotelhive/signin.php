<?php
session_start();
require_once 'db.php';

$success = false; // Flag for successful sign-in

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check user
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, c_status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['c_status'] === 'banned') {
                $banned = true;
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $success = true;
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ered Hotel - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .auth-box {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            padding: 40px 50px 50px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 237, 78, 0.1) 100%);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .logo img {
            width: auto;
            height: 100px; /* Reduced from 120px to 100px */
            object-fit: contain;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #cbd5e1;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 16px 20px;
            font-size: 16px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            color: #f8fafc;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .password-container input {
            padding-right: 50px;
        }

        .form-group input:focus {
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            background: rgba(30, 41, 59, 0.95);
            outline: none;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            font-size: 16px;
            padding: 8px;
            transition: color 0.3s ease;
            z-index: 10;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .password-toggle:hover {
            color: #ffd700;
        }

        .password-toggle:focus {
            outline: none;
            color: #ffd700;
        }

        .error {
            color: #d32f2f; /* Keep error color as red for clarity */
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
        }

        .success {
            color: #1a4d3e;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
            animation: fadeOut 2s ease-in-out forwards;
            padding: 15px;
            line-height: 1.6;
        }

        .success .welcome-name {
            font-size: 24px;
            color: #ff6f61;
            display: block;
            margin-bottom: 8px;
            font-family: 'Playfair Display', serif;
        }

        .success .redirect-text {
            font-size: 14px;
            color: #6b7280;
            display: block;
            margin-top: 8px;
            font-weight: normal;
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff8a7a 0%, #ff6f61 100%); /* Soft Coral Gradient */
            color: #1a1a1a; /* Primary Text */
            font-size: 16px;
            font-weight: 700;
            border: none;
            border-radius: 10px; /* Rounded corners */
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background: linear-gradient(135deg, #ff8a7a 0%, #ff4d3e 100%); /* Darker Coral Gradient */
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280; /* Medium Gray */
        }

        .signup-link a {
            color: #1a4d3e; /* Deep Emerald Green */
            text-decoration: none;
            font-weight: 700;
        }

        .signup-link a:hover {
            color: #ff8a7a; /* Soft Coral */
            text-decoration: underline;
        }

        .manager-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .manager-link a {
            color: #1a4d3e; /* Deep Emerald Green */
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .manager-link a:hover {
            color: #2d7a5f;
            text-decoration: underline;
        }

        .manager-link a i {
            font-size: 16px;
        }

        .banned-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.5s ease-out;
        }

        .banned-container {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            position: relative;
            animation: slideUp 0.5s ease-out;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .banned-icon {
            font-size: 64px;
            color: #e53e3e;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .banned-title {
            color: #e53e3e;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }

        .banned-text {
            color: #4a5568;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .banned-contact {
            background: #fff5f5;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .banned-contact-title {
            color: #e53e3e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .banned-email {
            color: #718096;
            font-size: 16px;
            font-weight: 500;
        }

        .banned-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #f8f9fa;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .banned-close:hover {
            background: #e53e3e;
            color: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                transform: translateY(50px);
                opacity: 0;
            }
            to { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <div class="logo">
                <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; font-weight: 700; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);">Ered Hotel</h1>
            </div>
            <h2>Login</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (isset($banned)): ?>
                <div class="banned-overlay">
                    <div class="banned-container">
                        <button class="banned-close" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="banned-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="banned-title">Account Banned</div>
                        <div class="banned-text">
                            Your account has been suspended due to violation of our terms of service.
                            Please contact support for more information.
                        </div>
                        <div class="banned-contact">
                            <div class="banned-contact-title">Contact Support</div>
                            <div class="banned-email">support@eredhotel.com</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <span class="welcome-name">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</span>
                    Logged in successfully to Ered Hotel
                    <span class="redirect-text">Taking you to your personalized experience...</span>
                </div>
                <script>
                    setTimeout(() => {
                        window.location.href = 'SignedIn_homepage.php';
                    }, 2000);
                </script>
            <?php else: ?>
                <form method="POST" action="signin.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit">Login</button>
                </form>
                <div class="signup-link">
                    Don't have an account? <a href="signup.php">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(inputId + '-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>