<?php
session_start();
require_once '../db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: manager_login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];
$hotel_id = $_SESSION['hotel_id'];

// Get manager and hotel information
$manager_sql = "SELECT hm.*, h.name as hotel_name, h.location, h.city, h.country 
                FROM hotel_managers hm 
                JOIN hotels h ON hm.hotel_id = h.hotel_id 
                WHERE hm.manager_id = ? AND hm.hotel_id = ?";
$stmt = $conn->prepare($manager_sql);
$stmt->bind_param("ii", $manager_id, $hotel_id);
$stmt->execute();
$manager_result = $stmt->get_result();

if ($manager_result->num_rows === 0) {
    header("Location: manager_login.php");
    exit();
}

$manager = $manager_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager_name = trim($_POST['manager_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate input
    if (empty($manager_name)) {
        $errors[] = "Manager name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }

    // Password change validation
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } else {
            // Verify current password
            $verify_sql = "SELECT password FROM hotel_managers WHERE manager_id = ?";
            $stmt = $conn->prepare($verify_sql);
            $stmt->bind_param("i", $manager_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if (!password_verify($current_password, $row['password'])) {
                $errors[] = "Current password is incorrect";
            } elseif (empty($new_password)) {
                $errors[] = "New password is required";
            } elseif (strlen($new_password) < 8) {
                $errors[] = "New password must be at least 8 characters long";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match";
            }
        }
    }

    if (empty($errors)) {
        // Update manager information
        if (!empty($new_password)) {
            $update_sql = "UPDATE hotel_managers SET 
                          manager_name = ?, 
                          email = ?, 
                          phone = ?, 
                          password = ? 
                          WHERE manager_id = ? AND hotel_id = ?";
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssii", $manager_name, $email, $phone, $hashed_password, $manager_id, $hotel_id);
        } else {
            $update_sql = "UPDATE hotel_managers SET 
                          manager_name = ?, 
                          email = ?, 
                          phone = ? 
                          WHERE manager_id = ? AND hotel_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssii", $manager_name, $email, $phone, $manager_id, $hotel_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully";
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Error updating profile: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            overflow-x: hidden;
        }

        .container-fluid {
            max-width: 100%;
            padding: 0;
            overflow-x: hidden;
        }

        .sidebar {
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            width: 280px;
            padding: 1.5rem;
            color: #fff;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand i {
            margin-right: 0.5rem;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-header {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
            transform: translateX(5px);
        }

        .nav-item.active {
            background-color: var(--primary-color);
            color: #fff;
        }

        .nav-item i {
            width: 1.5rem;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            width: calc(100% - 280px);
            overflow-x: hidden;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem;
        }

        .card-title {
            color: #1e293b;
            font-weight: 600;
            margin: 0;
        }

        .form-label {
            color: #475569;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            padding: 0.625rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .alert {
            border-radius: 0.5rem;
            border: none;
            transition: opacity 0.5s ease-in-out;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .alert-success {
            background-color: #dcfce7;
            color: var(--success-color);
        }

        .alert-success.fade-out {
            opacity: 0;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
        }

        .hotel-info {
            background-color: #f1f5f9;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #475569;
        }

        .info-item i {
            width: 1.5rem;
            margin-right: 0.5rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-hotel"></i>
                Ered Hotel
            </div>
            <div class="nav-section">
                <div class="nav-header">Main</div>
                <a href="manager_dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="rooms.php" class="nav-item">
                    <i class="fas fa-bed"></i>
                    Rooms
                </a>
                <a href="manage_payments.php" class="nav-item">
                        <i class="fas fa-list-alt"></i> All Bookings
                    </a>
                <a href="bookings.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    Bookings
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-header">Management</div>
                <a href="profile.php" class="nav-item active">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    Hotel Info
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Profile Settings</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success mb-4" id="success-message">
                            <?php 
                            echo htmlspecialchars($_SESSION['success_message']); 
                            unset($_SESSION['success_message']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="hotel-info">
                        <div class="info-item">
                            <i class="fas fa-hotel"></i>
                            <span>Hotel: <?php echo htmlspecialchars($manager['hotel_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Location: <?php echo htmlspecialchars($manager['location']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-city"></i>
                            <span>City: <?php echo htmlspecialchars($manager['city']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-flag"></i>
                            <span>Country: <?php echo htmlspecialchars($manager['country']); ?></span>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="manager_name" class="form-label">Manager Name</label>
                                <input type="text" class="form-control" id="manager_name" name="manager_name" 
                                       value="<?php echo htmlspecialchars($manager['manager_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($manager['email']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($manager['phone']); ?>" required>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Change Password</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="password-toggle">
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                    <i class="fas fa-eye" onclick="togglePassword('current_password')"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="password-toggle">
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <i class="fas fa-eye" onclick="togglePassword('new_password')"></i>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="password-toggle">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <i class="fas fa-eye" onclick="togglePassword('confirm_password')"></i>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Auto-hide success message after 1.5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('success-message');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.classList.add('fade-out');
                    setTimeout(() => {
                        successMessage.remove();
                    }, 500);
                }, 1500);
            }
        });
    </script>
</body>
</html> 