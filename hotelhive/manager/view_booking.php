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

// Check if booking ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: bookings.php?error=invalid_booking_id");
    exit();
}

$booking_id = (int)$_GET['id'];

try {
    // Get hotel information
    $hotel_sql = "SELECT h.*, hm.manager_name 
                  FROM hotels h 
                  JOIN hotel_managers hm ON h.hotel_id = hm.hotel_id 
                  WHERE h.hotel_id = ? AND hm.manager_id = ?";
    $stmt = $conn->prepare($hotel_sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $hotel_id, $manager_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    
    $hotel_result = $stmt->get_result();
    $hotel = $hotel_result->fetch_assoc();
    $stmt->close();
    
    if (!$hotel) {
        die("Hotel not found or you don't have permission to access it.");
    }
    
    // Get booking details with customer and payment information
    $booking_sql = "SELECT b.*, 
                           u.first_name, u.last_name, u.email, u.phone,
                           r.room_type, r.price_per_night, r.max_guests
                    FROM bookings b
                    JOIN users u ON b.user_id = u.user_id
                    JOIN rooms r ON b.room_id = r.room_id
                    WHERE b.booking_id = ? AND b.hotel_id = ?";
    
    $stmt = $conn->prepare($booking_sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $hotel_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    
    $booking_result = $stmt->get_result();
    $booking = $booking_result->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        header("Location: bookings.php?error=booking_not_found");
        exit();
    }
    
    // Handle success/error messages
    $success_message = '';
    $error_message = '';
    
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case 'booking_updated':
                $success_message = 'Booking has been successfully updated.';
                break;
        }
    }
    
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'invalid_booking_id':
                $error_message = 'Invalid booking ID provided.';
                break;
            case 'booking_not_found':
                $error_message = 'Booking not found or you do not have permission to access it.';
                break;
            case 'update_failed':
                $error_message = 'Failed to update booking. Please try again.';
                break;
            default:
                $error_message = 'An error occurred. Please try again.';
        }
    }
    
} catch (Exception $e) {
    error_log("View Booking Error: " . $e->getMessage());
    header("Location: bookings.php?error=system_error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booking - Ered Hotel</title>
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

        .welcome-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .welcome-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }

        .booking-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .booking-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .booking-status {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-confirmed { background-color: #dcfce7; color: var(--success-color); }
        .status-pending { background-color: #fef3c7; color: var(--warning-color); }
        .status-cancelled { background-color: #fee2e2; color: var(--danger-color); }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .detail-section {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .detail-section h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 500;
            color: #64748b;
        }

        .detail-value {
            font-weight: 600;
            color: #1e293b;
        }

        .booking-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .receipt-preview {
            max-width: 200px;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .receipt-preview:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                display: none;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }

            .booking-actions {
                flex-direction: column;
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
                    <?php echo htmlspecialchars($hotel['name']); ?>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Main</div>
                    <a href="manager_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="bookings.php" class="nav-item active">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="manage_payments.php" class="nav-item">
                        <i class="fas fa-list-alt"></i> All Bookings
                    </a>
                    <a href="rooms.php" class="nav-item">
                        <i class="fas fa-bed"></i> Hotels
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Management</div>
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        Hotel Info
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
                <div class="welcome-header">
                    <h1 class="welcome-title">Booking Details</h1>
                    <p class="welcome-subtitle">View detailed information for booking #<?php echo $booking['booking_id']; ?></p>
                </div>

                <!-- Success/Error Messages -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Booking Details -->
                <div class="booking-card">
                    <div class="booking-header">
                        <h2 class="booking-title">Booking #<?php echo $booking['booking_id']; ?></h2>
                        <span class="booking-status status-<?php echo $booking['booking_status']; ?>">
                            <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                    </div>

                    <div class="booking-details">
                        <!-- Customer Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-user"></i> Customer Information</h3>
                            <div class="detail-item">
                                <span class="detail-label">Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['phone'] ?? 'Not provided'); ?></span>
                            </div>
                        </div>

                        <!-- Booking Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-calendar"></i> Booking Information</h3>
                            <div class="detail-item">
                                <span class="detail-label">Booking Number:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['book_number']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-in Date:</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-out Date:</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Room Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Max Guests:</span>
                                <span class="detail-value"><?php echo $booking['max_guests']; ?></span>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                            <?php if ($booking['payment_method']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Payment Method:</span>
                                    <span class="detail-value"><?php echo strtoupper($booking['payment_method']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Payment Status:</span>
                                    <span class="detail-value">
                                        <span class="badge bg-<?php echo $booking['payment_status'] === 'completed' ? 'success' : ($booking['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Amount:</span>
                                    <span class="detail-value">RM <?php echo number_format($booking['amount'], 2); ?></span>
                                </div>
                                <?php if ($booking['transaction_id']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Transaction ID:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($booking['paid_at']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Payment Date:</span>
                                        <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($booking['paid_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($booking['receipt_img']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Receipt:</span>
                                        <span class="detail-value">
                                            <img src="../<?php echo htmlspecialchars($booking['receipt_img']); ?>" 
                                                 alt="Payment Receipt" 
                                                 class="receipt-preview"
                                                 onclick="window.open('../<?php echo htmlspecialchars($booking['receipt_img']); ?>', '_blank')">
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="detail-item">
                                    <span class="detail-label">Payment:</span>
                                    <span class="detail-value">No payment information available</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Additional Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                            <div class="detail-item">
                                <span class="detail-label">Total Price:</span>
                                <span class="detail-value">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                            <?php if ($booking['special_requests']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Special Requests:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['special_requests']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($booking['late_checkout_time']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Late Checkout:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['late_checkout_time']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($booking['room_service_package']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Room Service:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking['room_service_package']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">Booking Date:</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="booking-actions">
                        <a href="bookings.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Bookings
                        </a>
                        <?php if ($booking['booking_status'] === 'pending'): ?>
                            <button type="button" class="btn btn-success" onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'confirmed')">
                                <i class="fas fa-check"></i> Confirm Booking
                            </button>
                        <?php endif; ?>
                        <?php if ($booking['booking_status'] !== 'cancelled'): ?>
                            <button type="button" class="btn btn-danger" onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Cancel Booking
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateBookingStatus(bookingId, newStatus) {
        const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        
        if (confirm(`Are you sure you want to ${newStatus === 'cancelled' ? 'cancel' : 'update'} this booking to ${statusText}?`)) {
            // Create a form to submit the status update
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update_booking_status.php';
            
            const bookingIdInput = document.createElement('input');
            bookingIdInput.type = 'hidden';
            bookingIdInput.name = 'booking_id';
            bookingIdInput.value = bookingId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'new_status';
            statusInput.value = newStatus;
            
            form.appendChild(bookingIdInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>
