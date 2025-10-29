<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php?redirect=manage_bookings.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Database connection
require_once 'db.php';

// Get bookings for the current user
try {
    // For mysqli, we need to modify our approach with correct table structure
    $query = "
        SELECT b.*, 
               COALESCE(h.name, 'Unknown Hotel') as hotel_name, 
               COALESCE(h.location, 'Unknown Location') as location, 
               COALESCE(h.city, 'Unknown City') as city, 
               COALESCE(h.image_url, 'images/default-hotel.jpg') as image_url, 
               COALESCE(r.room_type, 'Unknown Room') as room_type, 
               COALESCE(r.price_per_night, 0) as price_per_night,
               COALESCE(r.max_guests, 2) as max_guests
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Get all bookings
    $bookings = [];
    
    while ($booking = $result->fetch_assoc()) {
        // Calculate nights
        if (isset($booking['check_in_date']) && isset($booking['check_out_date'])) {
            $booking['nights'] = calculateNights($booking['check_in_date'], $booking['check_out_date']);
        }
        
        $bookings[] = $booking;
    }
    
    $stmt->close();
    
} catch(Exception $e) {
    // Set empty array for error case
    $bookings = [];
    $error_message = "Database error: " . $e->getMessage();
    error_log("Manage Bookings Error: " . $e->getMessage());
}

// Get active tab from URL parameter, default to 'pending'
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// Function to format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Function to calculate nights between two dates
function calculateNights($checkIn, $checkOut) {
    $checkInDate = new DateTime($checkIn);
    $checkOutDate = new DateTime($checkOut);
    $interval = $checkInDate->diff($checkOutDate);
    return $interval->days;
}

// Function to determine status color
function getStatusColor($status) {
    if ($status == 'pending') return '#f39c12';
    if ($status == 'confirmed') return '#16a085';
    if ($status == 'cancelled') return '#e74c3c';
    return '#6b7280';
}

// Function to determine hotel type based on location
function getHotelType($location) {
    if (stripos($location, 'beach') !== false) return 'beach';
    if (stripos($location, 'mountain') !== false) return 'mountain';
    return 'city';
}

// Get high-quality hotel image
function getHotelImage($booking) {
    $hotel_type = getHotelType($booking['location'] ?? '');
    
    // If we have the getHotelImages function available
    if (function_exists('getHotelImages')) {
        $images = getHotelImages($hotel_type, 1);
        return $images[0] ?? $booking['image_url'];
    }
    
    // Fallback to default images
    switch ($hotel_type) {
        case 'beach':
            return 'https://images.unsplash.com/photo-1540541338287-41700207dee6?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
        case 'mountain':
            return 'https://images.unsplash.com/photo-1626268174896-a8946530f23f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
        default:
            return 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Your Bookings - Ered Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #f8fafc;
            line-height: 1.6;
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            padding: 15px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-menu a {
            color: #f8fafc;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border-radius: 25px;
        }

        .nav-menu a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }

        .bookings-container {
            margin-top: 120px;
            padding-bottom: 60px;
        }

        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            text-align: center;
        }

        .bookings-summary {
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .summary-stats {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            min-width: 100px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .stat-item:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: #ffd700;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }

        .stat-item.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-color: #ffd700;
            color: #0f172a;
        }

        .stat-item.active .stat-number,
        .stat-item.active .stat-label {
            color: #0f172a !important;
        }

        .stat-number {
            display: block;
            font-size: 32px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            display: block;
            font-size: 14px;
            color: #f8fafc;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .no-bookings {
            text-align: center;
            padding: 40px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .no-bookings-icon {
            font-size: 50px;
            color: #ffd700;
            margin-bottom: 20px;
        }

        .no-bookings h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #f8fafc;
            margin-bottom: 10px;
        }

        .booking-card {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 215, 0, 0.4);
        }

        @media (min-width: 768px) {
            .booking-card {
                flex-direction: row;
            }
        }

        .booking-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        @media (min-width: 768px) {
            .booking-image {
                width: 300px;
                height: auto;
            }
        }

        .booking-details {
            flex: 1;
            padding: 20px;
            position: relative;
        }

        .booking-hotel {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            color: #f8fafc;
            margin-bottom: 10px;
        }

        .booking-location {
            color: #cbd5e1;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .booking-location i {
            margin-right: 8px;
            color: #ffd700;
        }

        .booking-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .booking-info-item {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .booking-info-item i {
            color: #ffd700;
        }

        .booking-price {
            font-weight: 600;
            font-size: 20px;
            color: #ffd700;
            margin-bottom: 25px;
        }

        .booking-status {
            position: absolute;
            top: 30px;
            right: 30px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .booking-actions {
            display: flex;
            gap: 15px;
            margin-top: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn.outline {
            background: transparent;
            border: 1px solid rgba(255, 215, 0, 0.5);
            color: #ffd700;
        }

        .btn.outline:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: #ffd700;
        }

        .btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn.danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
        }
        
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            text-align: center;
            position: relative;
            border: 1px solid #ddd;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        
        .receipt-image {
            width: 100%;
            height: auto;
            max-height: 70vh;
            object-fit: contain;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        #receiptImageContainer {
            margin: 10px 0;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .close-modal {
            color: #1a1a1a;
            font-size: 24px;
            font-weight: bold;
            position: absolute;
            right: 15px;
            top: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .close-modal:hover {
            color: #c8a97e;
        }

        @media (max-width: 768px) {
            .bookings-container {
                margin-top: 100px;
            }

            .page-title {
                font-size: 28px;
            }

            .summary-stats {
                flex-direction: column;
                gap: 15px;
            }

            .stat-item {
                min-width: auto;
            }

            .booking-details {
                padding: 20px;
            }

            .booking-hotel {
                font-size: 22px;
            }

            .booking-status {
                position: static;
                display: inline-block;
                margin-bottom: 15px;
            }

            .booking-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .modal-content {
                width: 95%;
                padding: 15px;
                margin: 10px;
            }
            
            #receiptModal .modal-content {
                max-width: 95%;
            }
            
            .receipt-image {
                max-height: 65vh;
            }
        }

        .cancellation-details {
            margin: 20px 0;
            text-align: left;
        }

        .confirmation-icon {
            margin-bottom: 20px;
        }

        .confirmation-icon.warning i {
            color: #e74c3c;
        }

        .booking-card {
            transition: all 0.3s ease;
        }

        .booking-card.fade-out {
            opacity: 0;
            transform: translateY(20px);
        }

        #cancellationStep2 {
            text-align: center;
        }

        .btn {
            margin: 5px;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10px;
                padding: 15px;
            }
        }

        .booking-details-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
            position: relative;
        }

        .booking-details-header h2 {
            font-family: 'Cormorant Garamond', serif;
            margin: 0;
            font-size: 32px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .booking-details-content {
            padding: 0 25px;
        }

        .booking-info-card {
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid #ddd;
        }

        .booking-info-card-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking-info-card-header i {
            color: #3498db;
            font-size: 24px;
        }

        .booking-info-card-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
        }

        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 30px;
        }

        .booking-info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .booking-info-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 500;
        }

        .booking-info-value {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }

        .booking-info-value.status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .booking-info-value.status.confirmed {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }

        .booking-info-value.status.pending {
            background: rgba(241, 196, 15, 0.1);
            color: #f39c12;
        }

        .booking-info-value.status.cancelled {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .payment-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            border-radius: 25px;
            background: #f8f9fa;
            width: fit-content;
        }

        .payment-status i {
            font-size: 16px;
        }

        .payment-status.completed {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }

        .payment-status.pending {
            background: rgba(241, 196, 15, 0.1);
            color: #f39c12;
        }

        .payment-status.failed {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .booking-info-grid {
                grid-template-columns: 1fr;
                padding: 20px;
                gap: 15px;
            }

            .booking-details-header {
                padding: 20px;
            }

            .booking-details-header h2 {
                font-size: 24px;
            }

            .booking-info-card-header {
                padding: 20px;
            }
        }

        @media (min-width: 1200px) {
            .modal-content {
                max-width: 1000px;
            }
            
            .booking-info-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-top">
                <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
                <div class="nav-menu">
                    <a href="SignedIn_homepage.php">Home</a>
                    <a href="own_account.php">My Account</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="bookings-container">
            <h1 class="page-title">Manage Your Bookings</h1>
            
            <div class="bookings-summary">
                <div class="summary-stats">
                    <div class="stat-item <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" onclick="switchTab('pending')">
                        <span class="stat-number"><?php echo count(array_filter($bookings, function($b) { return $b['booking_status'] == 'pending'; })); ?></span>
                        <span class="stat-label">Pending</span>
                    </div>
                    <div class="stat-item <?php echo $active_tab === 'confirmed' ? 'active' : ''; ?>" onclick="switchTab('confirmed')">
                        <span class="stat-number"><?php echo count(array_filter($bookings, function($b) { return $b['booking_status'] == 'confirmed'; })); ?></span>
                        <span class="stat-label">Confirmed</span>
                    </div>
                    <div class="stat-item <?php echo $active_tab === 'cancelled' ? 'active' : ''; ?>" onclick="switchTab('cancelled')">
                        <span class="stat-number"><?php echo count(array_filter($bookings, function($b) { return $b['booking_status'] == 'cancelled'; })); ?></span>
                        <span class="stat-label">Cancelled</span>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="no-bookings">
                    <div class="no-bookings-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>


            <!-- Filtered Bookings -->
            <?php 
            // Filter bookings based on active tab
            $filtered_bookings = array_filter($bookings, function($booking) use ($active_tab) {
                return $booking['booking_status'] === $active_tab;
            });
            ?>
            
            <?php if (empty($filtered_bookings)): ?>
                <div class="no-bookings">
                    <div class="no-bookings-icon"><i class="far fa-calendar"></i></div>
                    <h3>No <?php echo ucfirst($active_tab); ?> Bookings</h3>
                    <p>You don't have any <?php echo $active_tab; ?> bookings at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach($filtered_bookings as $booking): ?>
                    <div class="booking-card" data-hotel-id="<?php echo $booking['hotel_id']; ?>" data-room-id="<?php echo $booking['room_id']; ?>">
                        <img src="<?php echo getHotelImage($booking); ?>" alt="<?php echo htmlspecialchars($booking['hotel_name']); ?>" class="booking-image">
                        <div class="booking-details">
                            <?php 
                            $status_color = '';
                            $status_bg = '';
                            switch($booking['booking_status']) {
                                case 'pending':
                                    $status_color = '#ffd700';
                                    $status_bg = 'rgba(255, 215, 0, 0.2)';
                                    break;
                                case 'confirmed':
                                    $status_color = '#10b981';
                                    $status_bg = 'rgba(16, 185, 129, 0.2)';
                                    break;
                                case 'cancelled':
                                    $status_color = '#ef4444';
                                    $status_bg = 'rgba(239, 68, 68, 0.2)';
                                    break;
                            }
                            ?>
                            <div class="booking-status" style="background-color: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
                                <?php echo ucfirst($booking['booking_status']); ?>
                            </div>
                            <h3 class="booking-hotel"><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                            <div class="booking-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['city']); ?>, <?php echo htmlspecialchars($booking['location']); ?>
                            </div>
                            <div class="booking-info">
                                <div class="booking-info-item">
                                    <i class="fas fa-calendar-alt"></i> <?php echo formatDate($booking['check_in_date']); ?> - <?php echo formatDate($booking['check_out_date']); ?>
                                </div>
                                <div class="booking-info-item">
                                    <i class="fas fa-bed"></i> <?php echo htmlspecialchars($booking['room_type']); ?>
                                </div>
                                <div class="booking-info-item">
                                    <i class="fas fa-user"></i> <?php echo isset($booking['max_guests']) ? htmlspecialchars($booking['max_guests']) : '2'; ?> Guests
                                </div>
                            </div>
                            <div class="booking-price">
                                RM <?php echo number_format($booking['total_price'], 2); ?> <?php echo isset($booking['nights']) ? '('.$booking['nights'].' nights)' : ''; ?>
                            </div>
                            <div class="booking-actions">
                                <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                    <a href="#" class="btn outline view-receipt" data-booking="<?php echo $booking['booking_id']; ?>">View Receipt</a>
                                <?php endif; ?>
                                <a href="#" class="btn outline view-details" data-booking="<?php echo $booking['booking_id']; ?>">View Details</a>
                                <?php if ($booking['booking_status'] != 'cancelled'): ?>
                                    <a href="#" class="btn danger cancel-booking" data-booking="<?php echo $booking['booking_id']; ?>">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
                    </div>
    </div>

    <!-- Cancellation Confirmation Modal -->
    <div id="cancellationModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeCancellationModal">&times;</span>
            <div id="cancellationStep1">
                <div class="confirmation-icon warning">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #e74c3c; margin-bottom: 20px;"></i>
                </div>
                <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 15px;">Cancel Booking</h2>
                <p style="margin-bottom: 20px; color: #666;">Are you sure you want to cancel this booking? This action cannot be undone.</p>
                <div class="cancellation-details" style="background: #f8f8f8; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <p><strong>Please note:</strong></p>
                    <ul style="list-style-type: none; padding-left: 0; margin-top: 10px;">
                        <li style="margin-bottom: 10px;"><i class="fas fa-times" style="color: #e74c3c; margin-right: 10px;"></i> Your reservation will be permanently cancelled</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-times" style="color: #e74c3c; margin-right: 10px;"></i> No payment has been made yet (cash payment at hotel)</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-times" style="color: #e74c3c; margin-right: 10px;"></i> Room availability cannot be guaranteed if you change your mind</li>
                    </ul>
                </div>
                <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn outline" id="closeModalBtn">No, Keep Booking</button>
                    <button type="button" class="btn danger" id="proceedCancellationBtn">Yes, Cancel Booking</button>
                </div>
            </div>
            <div id="cancellationStep2" style="display: none;">
                <div class="confirmation-icon success">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #4CAF50; margin-bottom: 20px;"></i>
                </div>
                <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 15px;">Booking Cancelled</h2>
                <p style="margin-bottom: 20px; color: #666;">Your booking has been successfully cancelled.</p>
                <div class="cancellation-details" style="background: #f8f8f8; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <p><strong>Next Steps:</strong></p>
                    <ul style="list-style-type: none; padding-left: 0; margin-top: 10px;">
                        <li style="margin-bottom: 10px;"><i class="fas fa-check" style="color: #4CAF50; margin-right: 10px;"></i> A confirmation email has been sent to your registered email address</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-check" style="color: #4CAF50; margin-right: 10px;"></i> No payment was required since it's cash payment at hotel</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-check" style="color: #4CAF50; margin-right: 10px;"></i> You can view your cancelled bookings in the booking history</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeReceiptModal">&times;</span>
            <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 15px;">
                Payment Information
            </h2>
            <div id="receiptImageContainer">
                <p>Loading payment information...</p>
            </div>
            <p style="margin-bottom: 15px;" id="receipt-date"></p>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeBookingDetailsModal">&times;</span>
            <div class="booking-details-header">
                <h2>Booking Details</h2>
            </div>
            <div id="bookingDetailsContainer">
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #c8a97e;"></i>
                    <p style="margin-top: 10px;">Loading booking details...</p>
                </div>
            </div>
            <div style="text-align: center; padding: 20px; border-top: 1px solid #eee;">
                <button class="btn" id="saveBookingDetailsBtn" style="display: none;">
                    <i class="fas fa-download"></i> Save as Image
                </button>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tab) {
            // Update URL parameter
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.location.href = url.toString();
        }

        // Receipt modal functionality
        const receiptModal = document.getElementById("receiptModal");
        const closeReceiptModal = document.getElementById("closeReceiptModal");
        const receiptImageContainer = document.getElementById("receiptImageContainer");
        const receiptDate = document.getElementById("receipt-date");

        // Cancellation modal elements
        const cancellationModal = document.getElementById("cancellationModal");
        const closeCancellationModal = document.getElementById("closeCancellationModal");
        const closeModalBtn = document.getElementById("closeModalBtn");
        const proceedCancellationBtn = document.getElementById("proceedCancellationBtn");
        const viewCancelledBookingsBtn = document.getElementById("viewCancelledBookingsBtn");
        const cancellationStep1 = document.getElementById("cancellationStep1");
        const cancellationStep2 = document.getElementById("cancellationStep2");
        let currentBookingId = null;

        // View Receipt functionality
        const viewReceiptBtns = document.querySelectorAll('.view-receipt');
        viewReceiptBtns.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const bookingId = this.getAttribute('data-booking');
                
                // Show modal with loading state
                receiptModal.style.display = "flex";
                setTimeout(() => {
                    receiptModal.classList.add('show');
                }, 10);
                
                // Since we only have cash payments, show a message about payment at hotel
                receiptImageContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-money-bill-wave" style="font-size: 48px; color: #c8a97e; margin-bottom: 20px;"></i>
                        <h3 style="color: #1a1a1a; margin-bottom: 15px;">Cash Payment</h3>
                        <p style="color: #666; margin-bottom: 20px;">Payment will be made at the hotel during check-in.</p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p style="margin: 0; color: #333;"><strong>Payment Instructions:</strong></p>
                            <ul style="text-align: left; margin: 10px 0 0 0; padding-left: 20px;">
                                <li>Bring a valid ID for verification</li>
                                <li>Payment is due upon arrival at the hotel</li>
                                <li>Cash only - no credit cards accepted</li>
                                <li>Keep this booking confirmation for reference</li>
                            </ul>
                        </div>
                    </div>
                `;
                receiptDate.textContent = `Booking created on ${new Date().toLocaleDateString()}`;
            }
        });

        // Cancellation functionality
        const cancellationBtns = document.querySelectorAll('.cancel-booking');

        cancellationBtns.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                currentBookingId = this.getAttribute('data-booking');
                cancellationModal.style.display = "flex";
                setTimeout(() => {
                    cancellationModal.classList.add('show');
                }, 10);
                cancellationStep1.style.display = "block";
                cancellationStep2.style.display = "none";
            }
        });

        // Close modal handlers
        if (closeReceiptModal) {
            closeReceiptModal.onclick = function() {
                receiptModal.classList.remove('show');
                setTimeout(() => {
                receiptModal.style.display = "none";
                    receiptImageContainer.innerHTML = '<p>Loading payment information...</p>';
                }, 300);
            }
        }

        if (closeCancellationModal) {
            closeCancellationModal.onclick = function() {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                cancellationModal.style.display = "none";
                }, 300);
            }
        }

        if (closeModalBtn) {
            closeModalBtn.onclick = function() {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                cancellationModal.style.display = "none";
                }, 300);
            }
        }

        // Proceed with cancellation
        if (proceedCancellationBtn) {
            proceedCancellationBtn.onclick = function() {
                if (currentBookingId) {
                    const formData = new FormData();
                    formData.append('booking_id', currentBookingId);
                    
                    fetch('cancel_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            cancellationStep1.style.display = "none";
                            cancellationStep2.style.display = "block";
                            
                            // Remove the cancelled booking card with smooth animation
                            const bookingCard = document.querySelector(`.cancel-booking[data-booking="${currentBookingId}"]`).closest('.booking-card');
                            if (bookingCard) {
                                bookingCard.classList.add('fade-out');
                                setTimeout(() => {
                                    bookingCard.remove();
                                    
                                    // Update booking counts in summary stats
                                    const pendingCount = document.querySelectorAll('.stat-item .stat-number')[0];
                                    if (pendingCount) {
                                        let pending = parseInt(pendingCount.textContent);
                                        pendingCount.textContent = Math.max(0, pending - 1);
                                        
                                        // Check if this was the last booking
                                        if (pending === 1) {
                                            // Reload the page to show the no-bookings message
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 2000);
                                        }
                                    }
                                }, 300);
                            }

                            // Auto-close the success message after 2 seconds
                            setTimeout(() => {
                                cancellationModal.classList.remove('show');
                                setTimeout(() => {
                                    cancellationModal.style.display = "none";
                                    // Reset the modal state
                                    cancellationStep1.style.display = "block";
                                    cancellationStep2.style.display = "none";
                                }, 300);
                            }, 2000);
                        } else {
                            alert(data.message || 'Failed to cancel booking. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while cancelling the booking. Please try again.');
                    });
                }
            }
        }

        // Close success modal
        const closeSuccessModalBtn = document.getElementById('closeSuccessModalBtn');
        if (closeSuccessModalBtn) {
            closeSuccessModalBtn.onclick = function() {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                    cancellationModal.style.display = "none";
                }, 300);
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == receiptModal) {
                receiptModal.classList.remove('show');
                setTimeout(() => {
                receiptModal.style.display = "none";
                }, 300);
            }
            if (event.target == cancellationModal) {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                cancellationModal.style.display = "none";
                }, 300);
            }
            if (event.target == bookingDetailsModal) {
                bookingDetailsModal.classList.remove('show');
                setTimeout(() => {
                    bookingDetailsModal.style.display = "none";
                    // Hide the save button
                    saveBookingDetailsBtn.style.display = 'none';
                }, 300);
            }
        }

        // Download receipt functionality removed - cash payment system doesn't generate receipts

        // Booking Details Modal functionality
        const bookingDetailsModal = document.getElementById("bookingDetailsModal");
        const closeBookingDetailsModal = document.getElementById("closeBookingDetailsModal");
        const bookingDetailsContainer = document.getElementById("bookingDetailsContainer");
        const saveBookingDetailsBtn = document.getElementById("saveBookingDetailsBtn");

        // View Booking Details functionality
        const viewDetailsBtns = document.querySelectorAll('.view-details');
        viewDetailsBtns.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const bookingId = this.getAttribute('data-booking');
                
                // Show modal with loading state
                bookingDetailsModal.style.display = "flex";
                setTimeout(() => {
                    bookingDetailsModal.classList.add('show');
                }, 10);
                
                // Fetch booking details
                fetch(`get_booking_details.php?booking_id=${bookingId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const booking = data.booking;
                            
                            bookingDetailsContainer.innerHTML = `
                                <div class="booking-details-content" id="bookingDetailsContent">
                                    <div class="booking-info-card">
                                        <div class="booking-info-card-header">
                                            <i class="fas fa-calendar-check"></i>
                                            <h3>Booking Information</h3>
                                        </div>
                                        <div class="booking-info-grid">
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Booking Number</span>
                                                <span class="booking-info-value">${booking.book_number}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Hotel</span>
                                                <span class="booking-info-value">${booking.hotel_name}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Location</span>
                                                <span class="booking-info-value">${booking.location}, ${booking.city}, ${booking.country}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Room Type</span>
                                                <span class="booking-info-value">${booking.room_type}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Status</span>
                                                <span class="booking-info-value status ${booking.booking_status}">${booking.booking_status}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Check-in Date</span>
                                                <span class="booking-info-value">${new Date(booking.check_in_date).toLocaleDateString()}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Check-out Date</span>
                                                <span class="booking-info-value">${new Date(booking.check_out_date).toLocaleDateString()}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Guests</span>
                                                <span class="booking-info-value">${booking.max_guests} person(s)</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Total Price</span>
                                                <span class="booking-info-value">MYR ${parseFloat(booking.total_price).toFixed(2)}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Payment Method</span>
                                                <span class="booking-info-value">Cash Payment at Hotel</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Payment Status</span>
                                                <span class="booking-info-value" style="color: #f39c12;">To be paid at hotel during check-in</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Special Requests</span>
                                                <span class="booking-info-value">${booking.special_requests || 'None'}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Late Checkout</span>
                                                <span class="booking-info-value">${booking.late_checkout_time || 'Standard'}</span>
                                            </div>
                                            <div class="booking-info-item">
                                                <span class="booking-info-label">Room Service</span>
                                                <span class="booking-info-value">${booking.room_service_package || 'None'}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            `;
                            
                            // Show the save button
                            saveBookingDetailsBtn.style.display = 'inline-flex';
                        } else {
                            bookingDetailsContainer.innerHTML = `
                                <div style="text-align: center; padding: 20px;">
                                    <i class="fas fa-exclamation-circle" style="font-size: 24px; color: #e74c3c;"></i>
                                    <p style="margin-top: 10px;">Unable to load booking details. Please try again later.</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        bookingDetailsContainer.innerHTML = `
                            <div style="text-align: center; padding: 20px;">
                                <i class="fas fa-exclamation-circle" style="font-size: 24px; color: #e74c3c;"></i>
                                <p style="margin-top: 10px;">Error loading booking details. Please try again later.</p>
                            </div>
                        `;
                    });
            }
        });

        // Close booking details modal handler
        if (closeBookingDetailsModal) {
            closeBookingDetailsModal.onclick = function() {
                bookingDetailsModal.classList.remove('show');
                setTimeout(() => {
                    bookingDetailsModal.style.display = "none";
                    bookingDetailsContainer.innerHTML = `
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #c8a97e;"></i>
                            <p style="margin-top: 10px;">Loading booking details...</p>
                        </div>
                    `;
                    // Hide the save button
                    saveBookingDetailsBtn.style.display = 'none';
                }, 300);
            }
        }

        // Save Booking Details functionality
        if (saveBookingDetailsBtn) {
            saveBookingDetailsBtn.onclick = function() {
                const bookingContent = document.getElementById('bookingDetailsContent');
                if (!bookingContent) {
                    alert('No booking details to save');
                    return;
                }

                // Show loading state
                const originalText = saveBookingDetailsBtn.innerHTML;
                saveBookingDetailsBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                saveBookingDetailsBtn.disabled = true;

                // Use html2canvas to capture the booking details
                html2canvas(bookingContent, {
                    backgroundColor: '#ffffff',
                    scale: 2, // Higher quality
                    useCORS: true,
                    allowTaint: true,
                    width: bookingContent.scrollWidth,
                    height: bookingContent.scrollHeight
                }).then(canvas => {
                    // Convert canvas to blob
                    canvas.toBlob(function(blob) {
                        // Create download link
                        const link = document.createElement('a');
                        link.download = `Ered Hotel_Booking_${new Date().getTime()}.jpg`;
                        link.href = URL.createObjectURL(blob);
                        
                        // Trigger download
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        // Clean up
                        URL.revokeObjectURL(link.href);
                        
                        // Reset button
                        saveBookingDetailsBtn.innerHTML = originalText;
                        saveBookingDetailsBtn.disabled = false;
                        
                        // Show success message
                        alert('Booking details saved successfully!');
                    }, 'image/jpeg', 0.95);
                }).catch(error => {
                    console.error('Error saving booking details:', error);
                    alert('Error saving booking details. Please try again.');
                    
                    // Reset button
                    saveBookingDetailsBtn.innerHTML = originalText;
                    saveBookingDetailsBtn.disabled = false;
                });
            };
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</body>
</html> 