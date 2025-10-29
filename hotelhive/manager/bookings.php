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
$hotel = $hotel_result->fetch_assoc();

// Handle status updates
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];
    
    // Update booking status for this manager's hotel only
    $update_sql = "UPDATE bookings SET booking_status = ? WHERE booking_id = ? AND hotel_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sii", $new_status, $booking_id, $hotel_id);
    
    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: bookings.php?success=status_updated");
        exit();
    } else {
        // Redirect with error message
        header("Location: bookings.php?error=update_failed");
        exit();
    }
}

// Get bookings for this manager's hotel
$bookings_sql = "SELECT b.*, u.first_name, u.last_name, u.email, u.phone, r.room_type, r.price_per_night, r.max_guests
                 FROM bookings b 
                 LEFT JOIN users u ON b.user_id = u.user_id 
                 LEFT JOIN rooms r ON b.room_id = r.room_id 
                 WHERE b.hotel_id = ?
                 ORDER BY b.created_at DESC";

$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$bookings = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 10px rgba(255, 215, 0, 0.3);
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-item {
            color: #ffffff !important;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .nav-item:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .nav-item.active {
            background-color: rgba(255,255,255,0.2);
        }

        .main-content {
            padding: 2rem 0;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .bookings-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .booking-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .booking-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .booking-id {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }
        
        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
            color: #333;
        }

        .booking-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .no-bookings {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-bookings i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: 2px solid rgba(255, 215, 0, 0.3);
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
        
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .booking-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="manager_dashboard.php">
                <i class="fas fa-hotel"></i> Ered Hotel Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
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
                        <i class="fas fa-bed"></i> Rooms
                    </a>
                    <a href="gallery.php" class="nav-item">
                        <i class="fas fa-images"></i> Gallery
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="manager_logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1 class="welcome-title">Manage Bookings</h1>
            <p class="welcome-subtitle">View and manage all bookings for <?php echo htmlspecialchars($hotel['name']); ?></p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> 
                <?php 
                switch($_GET['success']) {
                    case 'status_updated':
                        echo 'Booking status updated successfully!';
                        break;
                    case 'booking_confirmed':
                        echo 'Booking confirmed successfully!';
                        break;
                    case 'receipt_generated':
                        echo 'Receipt generated successfully!';
                        break;
                    default:
                        echo 'Operation completed successfully!';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                switch($_GET['error']) {
                    case 'update_failed':
                        echo 'Failed to update booking status. Please try again.';
                        break;
                    case 'invalid_booking_id':
                        echo 'Invalid booking ID provided.';
                        break;
                    case 'booking_not_found':
                        echo 'Booking not found or access denied.';
                        break;
                    case 'booking_not_pending':
                        echo 'Booking is not pending.';
                        break;
                    case 'confirmation_failed':
                        echo 'Failed to confirm booking. Please try again.';
                        break;
                    case 'booking_not_found_or_not_confirmed':
                        echo 'Booking not found or not confirmed.';
                        break;
                    case 'receipt_already_exists':
                        echo 'Receipt already exists for this booking.';
                        break;
                    case 'receipt_generation_failed':
                        echo 'Failed to generate receipt. Please try again.';
                        break;
                    default:
                        echo 'An error occurred. Please try again.';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>


        <!-- Bookings List -->
        <div class="bookings-list">
            <?php if ($bookings->num_rows > 0): ?>
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <div>
                                <span class="booking-id">Booking #<?php echo htmlspecialchars($booking['book_number']); ?></span>
                                <small class="text-muted ms-2">ID: <?php echo $booking['booking_id']; ?></small>
                            </div>
                            <span class="booking-status status-<?php echo $booking['booking_status']; ?>">
                                <?php echo ucfirst(htmlspecialchars($booking['booking_status'])); ?>
                            </span>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-item">
                                <span class="detail-label">Guest Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Room Type</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-in Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-out Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Price</span>
                                <span class="detail-value">RM <?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Booking Date</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="booking-actions">
                            <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            
                            <?php if ($booking['booking_status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <input type="hidden" name="new_status" value="confirmed">
                                    <button type="submit" name="update_status" class="btn btn-success btn-sm"
                                            onclick="return confirm('Confirm this booking?')">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <input type="hidden" name="new_status" value="cancelled">
                                    <button type="submit" name="update_status" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Cancel this booking?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fas fa-info-circle"></i>
                    <h4>No bookings found</h4>
                    <p>No bookings match your current criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });

        // Add confirmation for status updates
        document.addEventListener('DOMContentLoaded', function() {
            const statusForms = document.querySelectorAll('form[name="update_status"]');
            statusForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const status = this.querySelector('input[name="new_status"]').value;
                    const action = status === 'confirmed' ? 'confirm' : 'cancel';
                    
                    if (!confirm(`Are you sure you want to ${action} this booking?`)) {
                        e.preventDefault();
                    }
                });
            });
        });

        function printReceipt(receiptPath) {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Receipt - Ered Hotel</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                margin: 20px; 
                                text-align: center;
                            }
                            .receipt-container {
                                max-width: 400px;
                                margin: 0 auto;
                                border: 1px solid #ddd;
                                padding: 20px;
                            }
                            .receipt-image {
                                max-width: 100%;
                                height: auto;
                            }
                            @media print {
                                body { margin: 0; }
                                .receipt-container { border: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="receipt-container">
                            <h2>Ered Hotel Receipt</h2>
                            <img src="${receiptPath}" alt="Receipt" class="receipt-image" onload="window.print()">
                            <p><small>Thank you for choosing Ered Hotel!</small></p>
                        </div>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>
<?php
$conn->close();
?> 