<?php
session_start();
require_once 'db.php';

$success = false; // Flag for successful registration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param('ss', $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Insert new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $username, $email, $password_hash, $first_name, $last_name);
            if ($stmt->execute()) {
                $success = true; // Set success flag
                // Delay redirect handled by JavaScript below
            } else {
                $error = "An error occurred. Please try again.";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ered Hotel - Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
            max-width: 450px;
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
            height: 100px;
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
            color: #1a4d3e; /* Deep Emerald Green */
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            text-align: center;
            animation: fadeOut 2s ease-in-out forwards;
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

        .signin-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280; /* Medium Gray */
        }

        .signin-link a {
            color: #1a4d3e; /* Deep Emerald Green */
            text-decoration: none;
            font-weight: 700;
        }

        .signin-link a:hover {
            color: #ff8a7a; /* Soft Coral */
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <div class="logo">
                <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; font-weight: 700; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);">Ered Hotel</h1>
            </div>
            <h2>Sign Up</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">Welcome to Ered Hotel! Registration successful.</div>
                <script>
                    setTimeout(() => {
                        window.location.href = 'signin.php';
                    }, 2000); // Redirect after 2 seconds
                </script>
            <?php else: ?>
                <form method="POST" action="signup.php">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
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
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password-icon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name (Optional)</label>
                        <input type="text" id="first_name" name="first_name">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name (Optional)</label>
                        <input type="text" id="last_name" name="last_name">
                    </div>
                    <button type="submit">Sign Up</button>
                </form>
                <div class="signin-link">
                    Already have an account? <a href="signin.php">Sign In</a>
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