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
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $hotel_id, $manager_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$hotel_result = $stmt->get_result();
$hotel = $hotel_result->fetch_assoc();

if (!$hotel) {
    die("Hotel not found or you don't have permission to access it.");
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$booking_id_filter = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Get unique booking statuses for filter options
$statuses_sql = "SELECT DISTINCT booking_status FROM bookings 
                 WHERE hotel_id = ?";
$stmt = $conn->prepare($statuses_sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $hotel_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$statuses_result = $stmt->get_result();
$statuses = [];
while ($row = $statuses_result->fetch_assoc()) {
    $statuses[] = $row['booking_status'];
}

// Build the query - show all bookings for this manager's hotel
$bookings_sql = "SELECT b.booking_id, b.book_number, b.check_in_date, b.check_out_date, 
                        b.total_price, b.booking_status, b.created_at,
                        u.first_name, u.last_name, u.email, u.phone,
                        r.room_type, r.price_per_night
                 FROM bookings b
                 JOIN users u ON b.user_id = u.user_id
                 JOIN rooms r ON b.room_id = r.room_id
                 WHERE b.hotel_id = ?";

$params = [$hotel_id];
$param_types = "i";

// Apply filters
if (!empty($status_filter)) {
    $bookings_sql .= " AND b.booking_status = ?";
        $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($search)) {
    $bookings_sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR b.book_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ssss";
}

if (!empty($date_from)) {
    $bookings_sql .= " AND DATE(b.created_at) >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $bookings_sql .= " AND DATE(b.created_at) <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

if ($booking_id_filter > 0) {
    $bookings_sql .= " AND b.booking_id = ?";
    $params[] = $booking_id_filter;
    $param_types .= "i";
}

$bookings_sql .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($bookings_sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$bookings = $stmt->get_result();

if (!$bookings) {
    die("Failed to get bookings result: " . $stmt->error);
}

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['new_status'];
    
    $update_sql = "UPDATE bookings SET booking_status = ? WHERE booking_id = ? AND hotel_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sii", $new_status, $booking_id, $hotel_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
        // Refresh the page to show updated data
        header("Location: manage_payments.php?success=1");
        exit();
    } else {
        $error_message = "Failed to update booking status.";
    }
}
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
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }

        .nav-item {
            color: white !important;
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

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .bookings-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
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
            justify-content: between;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
                    <a href="bookings.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="manage_payments.php" class="nav-item active">
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
                <i class="fas fa-check-circle"></i> Booking status updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

        <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                        <div class="col-md-3">
                    <label for="status" class="form-label">Booking Status</label>
                    <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" 
                                    <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Name, email, or booking number" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                <div class="col-md-2">
                    <label for="booking_id" class="form-label">Booking ID</label>
                    <input type="number" class="form-control" id="booking_id" name="booking_id" 
                           placeholder="Booking ID" value="<?php echo $booking_id_filter; ?>">
                        </div>
                        <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter Bookings
                    </button>
                            <a href="manage_payments.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>

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
                    <p>No bookings match your current filter criteria.</p>
                        </div>
                    <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('select, input[type="text"], input[type="date"], input[type="number"]');
            
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Only auto-submit for select elements and date inputs
                    if (this.tagName === 'SELECT' || this.type === 'date') {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
