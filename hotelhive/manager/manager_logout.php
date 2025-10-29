<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #059669;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .logout-container {
            text-align: center;
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            animation: scaleIn 0.5s ease-out;
        }

        .message {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .sub-message {
            color: #64748b;
            margin-bottom: 2rem;
        }

        .loading-bar {
            width: 100%;
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .loading-progress {
            width: 0%;
            height: 100%;
            background-color: var(--primary-color);
            animation: loading 2s ease-in-out forwards;
        }

        .redirect-message {
            color: #64748b;
            font-size: 0.875rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes loading {
            0% { width: 0%; }
            50% { width: 50%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="message">Successfully Logged Out</h1>
        <p class="sub-message">Thank you for using Ered Hotel Manager Portal</p>
        <div class="loading-bar">
            <div class="loading-progress"></div>
        </div>
        <p class="redirect-message">
            <i class="fas fa-spinner fa-spin"></i>
            Redirecting to login page...
        </p>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "manager_login.php";
        }, 2000);
    </script>
</body>
</html> 