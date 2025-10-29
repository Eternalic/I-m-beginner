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

    // Get hotel information
    $hotel_sql = "SELECT h.*, hm.manager_name 
                FROM hotels h 
                JOIN hotel_managers hm ON h.hotel_id = hm.hotel_id 
                WHERE h.hotel_id = ? AND hm.manager_id = ?";
    $stmt = $conn->prepare($hotel_sql);
    $stmt->bind_param("ii", $hotel_id, $manager_id);
    $stmt->execute();
    $hotel_result = $stmt->get_result();

    if ($hotel_result->num_rows === 0) {
        header("Location: manager_login.php");
        exit();
    }

    $hotel = $hotel_result->fetch_assoc();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hotel_name = trim($_POST['hotel_name']);
        $location = trim($_POST['location']);
        $city = trim($_POST['city']);
        $country = trim($_POST['country']);
        $description = trim($_POST['description']);

        $errors = [];

        // Validate input
        if (empty($hotel_name)) {
            $errors[] = "Hotel name is required";
        }
        if (empty($location)) {
            $errors[] = "Location is required";
        }
        if (empty($city)) {
            $errors[] = "City is required";
        }
        if (empty($country)) {
            $errors[] = "Country is required";
        }

        if (empty($errors)) {
            // Update hotel information
            $update_sql = "UPDATE hotels SET 
                        name = ?, 
                        location = ?, 
                        city = ?, 
                        country = ?, 
                        description = ?
                        WHERE hotel_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssi", $hotel_name, $location, $city, $country, $description, $hotel_id);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Settings updated successfully";
                header("Location: settings.php");
                exit();
            } else {
                $errors[] = "Error updating settings: " . $conn->error;
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Settings - Ered Hotel</title>
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

            .settings-section {
                margin-bottom: 2rem;
            }

            .settings-section-title {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 2px solid #e5e7eb;
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
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i>
                        Profile
                    </a>
                    <a href="settings.php" class="nav-item active">
                        <i class="fas fa-cog"></i>
                        Hotel Info
                    </a>
                    <a href="manager_logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
            <div class="main-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Hotel Settings</h5>
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

                        <form method="POST" action="">
                            <div class="settings-section">
                                <h6 class="settings-section-title">Basic Information</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="hotel_name" class="form-label">Hotel Name</label>
                                        <input type="text" class="form-control" id="hotel_name" name="hotel_name" 
                                            value="<?php echo htmlspecialchars($hotel['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location" 
                                            value="<?php echo htmlspecialchars($hotel['location']); ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                            value="<?php echo htmlspecialchars($hotel['city']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="country" name="country" 
                                            value="<?php echo htmlspecialchars($hotel['country']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($hotel['description']); ?></textarea>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
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