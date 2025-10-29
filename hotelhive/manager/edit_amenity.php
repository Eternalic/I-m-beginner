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

// Get room ID from URL parameter
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($room_id <= 0) {
    $_SESSION['error_message'] = "Invalid room ID.";
    header("Location: rooms.php");
    exit();
}

// Verify that the room belongs to the manager's hotel
$verify_sql = "SELECT r.room_id, r.room_type, r.hotel_id 
              FROM rooms r 
              WHERE r.room_id = ? AND r.hotel_id = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("ii", $room_id, $hotel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Room not found or you don't have permission to edit this room.";
    header("Location: rooms.php");
    exit();
}

$room = $result->fetch_assoc();

// Get existing amenities for this room
$amenities_sql = "SELECT amenities, room_size, beds FROM room_amenities WHERE room_id = ?";
$stmt = $conn->prepare($amenities_sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$amenities_result = $stmt->get_result();
$existing_amenities = $amenities_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amenities = isset($_POST['amenities']) ? trim($_POST['amenities']) : '';
    $room_size = isset($_POST['room_size']) ? trim($_POST['room_size']) : '';
    $beds = isset($_POST['beds']) ? trim($_POST['beds']) : '';
    
    // Validate inputs
    if (empty($amenities) || empty($room_size) || empty($beds)) {
        $error_message = "Please fill in all fields.";
    } else {
        try {
            // Check if amenities record exists for this room
            $check_sql = "SELECT room_id FROM room_amenities WHERE room_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing record
                $update_sql = "UPDATE room_amenities SET amenities = ?, room_size = ?, beds = ? WHERE room_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssi", $amenities, $room_size, $beds, $room_id);
            } else {
                // Insert new record
                $insert_sql = "INSERT INTO room_amenities (room_id, amenities, room_size, beds) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("isss", $room_id, $amenities, $room_size, $beds);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Room amenities updated successfully!";
                header("Location: rooms.php");
                exit();
            } else {
                $error_message = "Failed to update amenities. Please try again.";
            }
            
        } catch (Exception $e) {
            error_log("Error updating room amenities: " . $e->getMessage());
            $error_message = "An error occurred while updating the amenities.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room Amenities - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
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
        }

        .page-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }

        .form-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--card-shadow);
        }

        .form-label {
            color: #475569;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary {
            background-color: #64748b;
            border-color: #64748b;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background-color: #475569;
            border-color: #475569;
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .room-info {
            background-color: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .room-info h5 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .room-info p {
            color: #64748b;
            margin: 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-brand">
                    <i class="fas fa-hotel"></i>
                    Ered Hotel
                </div>

                <div class="nav-section">
                    <div class="nav-header">Main</div>
                    <a href="manager_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="bookings.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="manage_payments.php" class="nav-item">
                        <i class="fas fa-list-alt"></i> All Bookings
                    </a>
                    <a href="rooms.php" class="nav-item active">
                        <i class="fas fa-bed"></i> Hotels
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Management</div>
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i> Hotel Info
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Account</div>
                    <a href="manager_logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title">Edit Room Amenities</h1>
                            <p class="page-subtitle">Update amenities and details for this room</p>
                        </div>
                        <a href="rooms.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Rooms
                        </a>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="room-info">
                    <h5><i class="fas fa-bed"></i> Room Information</h5>
                    <p><strong>Room Type:</strong> <?php echo htmlspecialchars($room['room_type']); ?></p>
                    <p><strong>Room ID:</strong> #<?php echo $room['room_id']; ?></p>
                </div>

                <div class="form-container">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="room_size" class="form-label">
                                        <i class="fas fa-expand-arrows-alt"></i> Room Size (m²)
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="room_size" 
                                           name="room_size" 
                                           value="<?php echo htmlspecialchars($existing_amenities['room_size'] ?? ''); ?>"
                                           placeholder="e.g., 25, 30, 35"
                                           required>
                                    <div class="form-text">Enter the room size in square meters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="beds" class="form-label">
                                        <i class="fas fa-bed"></i> Bed Configuration
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="beds" 
                                           name="beds" 
                                           value="<?php echo htmlspecialchars($existing_amenities['beds'] ?? ''); ?>"
                                           placeholder="e.g., 1 King Bed, 2 Twin Beds, 1 Queen Bed"
                                           required>
                                    <div class="form-text">Describe the bed setup in this room</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="amenities" class="form-label">
                                <i class="fas fa-list"></i> Room Amenities
                            </label>
                            <textarea class="form-control" 
                                      id="amenities" 
                                      name="amenities" 
                                      rows="6" 
                                      placeholder="List all amenities separated by commas (e.g., WiFi, Air Conditioning, Mini Bar, Safe, TV, Coffee Machine, Balcony)"
                                      required><?php echo htmlspecialchars($existing_amenities['amenities'] ?? ''); ?></textarea>
                            <div class="form-text">
                                <strong>Tip:</strong> Separate each amenity with a comma. Common amenities include: WiFi, Air Conditioning, Mini Bar, Safe, TV, Coffee Machine, Balcony, Ocean View, City View, etc.
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Amenities
                            </button>
                            <a href="rooms.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const roomSize = document.getElementById('room_size').value.trim();
            const beds = document.getElementById('beds').value.trim();
            const amenities = document.getElementById('amenities').value.trim();

            if (!roomSize || !beds || !amenities) {
                e.preventDefault();
                alert('Please fill in all fields before submitting.');
                return false;
            }

            // Validate room size is numeric
            if (isNaN(roomSize) || parseFloat(roomSize) <= 0) {
                e.preventDefault();
                alert('Please enter a valid room size (positive number).');
                return false;
            }
        });
    </script>
</body>
</html>