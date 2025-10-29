<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['booking_id'])) {
    header("Location: manage_bookings.php?error=no_booking_specified");
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    // Fetch booking details with hotel and room information
    $query = "
        SELECT 
            b.*, 
            h.name as hotel_name, 
            h.location, 
            h.city, 
            h.country,
            h.star_rating,
            h.image_url,
            r.room_type,
            r.price_per_night,
            r.max_guests,
            r.amenities as room_amenities
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN hotels h ON b.hotel_id = h.hotel_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage_bookings.php?error=invalid_booking");
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    // Calculate number of nights
    $check_in = new DateTime($booking['check_in_date']);
    $check_out = new DateTime($booking['check_out_date']);
    $nights = $check_in->diff($check_out)->days;
    
    // Get status color
    function getStatusColor($status) {
        switch($status) {
            case 'pending': return ['bg' => 'rgba(243, 156, 18, 0.1)', 'text' => '#f39c12'];
            case 'confirmed': return ['bg' => 'rgba(22, 160, 133, 0.1)', 'text' => '#16a085'];
            case 'cancelled': return ['bg' => 'rgba(231, 76, 60, 0.1)', 'text' => '#e74c3c'];
            default: return ['bg' => 'rgba(107, 114, 128, 0.1)', 'text' => '#6b7280'];
        }
    }
    
    $status_colors = getStatusColor($booking['booking_status']);
    
} catch(Exception $e) {
    header("Location: manage_bookings.php?error=system_error");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - WanderNext</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: #f5f5ee;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #1a4d3e 0%, #2e6b55 100%);
            padding: 25px 0;
            color: #fff;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-menu a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            text-transform: uppercase;
            margin-left: 20px;
        }

        .nav-menu a:hover {
            color: #ff8a7a;
        }

        .booking-details-container {
            margin-top: 120px;
            padding-bottom: 60px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #1a4d3e;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .back-link i {
            margin-right: 8px;
        }

        .booking-header {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .booking-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #1a4d3e;
            margin-bottom: 15px;
        }

        .booking-status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .booking-main {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .booking-sidebar {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            align-self: start;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: #1a4d3e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .hotel-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .hotel-image {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
        }

        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hotel-details h3 {
            font-size: 24px;
            color: #1a4d3e;
            margin-bottom: 10px;
        }

        .hotel-location {
            display: flex;
            align-items: center;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .hotel-location i {
            margin-right: 8px;
            color: #1a4d3e;
        }

        .star-rating {
            color: #f39c12;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }

        .info-item .label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-item .value {
            color: #1a4d3e;
            font-weight: 500;
        }

        .price-breakdown {
            margin-top: 20px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .price-row.total {
            font-weight: bold;
            font-size: 18px;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid #eee;
        }

        .amenities-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #1a4d3e;
        }

        .amenity-item i {
            color: #16a085;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #1a4d3e;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #154033;
        }

        .btn-outline {
            background-color: transparent;
            color: #1a4d3e;
            border: 1px solid #1a4d3e;
        }

        .btn-outline:hover {
            background-color: #f5f5f5;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }

            .hotel-info {
                flex-direction: column;
            }

            .hotel-image {
                width: 100%;
                height: 200px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .amenities-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-top">
                <div class="logo">WanderNext</div>
                <div class="nav-menu">
                    <a href="homepage.php">Home</a>
                    <a href="#">Stays</a>
                    <a href="#">Flights</a>
                    <a href="#">Flight + Hotel</a>
                    <a href="#">Car Rentals</a>
                    <a href="#">My Account</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="booking-details-container">
            <a href="manage_bookings.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to My Bookings
            </a>

            <div class="booking-header">
                <h1 class="booking-title">Booking Details</h1>
                <div class="booking-status" style="background-color: <?php echo $status_colors['bg']; ?>; color: <?php echo $status_colors['text']; ?>;">
                    <?php echo ucfirst($booking['booking_status']); ?>
                </div>
            </div>

            <div class="booking-grid">
                <div class="booking-main">
                    <div class="hotel-info">
                        <div class="hotel-image">
                            <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['hotel_name']); ?>">
                        </div>
                        <div class="hotel-details">
                            <h3><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                            <div class="hotel-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($booking['city'] . ', ' . $booking['country']); ?>
                            </div>
                            <div class="star-rating">
                                <?php echo str_repeat('★', $booking['star_rating']); ?>
                            </div>
                        </div>
                    </div>

                    <h2 class="section-title">Booking Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="label">Check-in</div>
                            <div class="value"><?php echo date('D, M j, Y', strtotime($booking['check_in_date'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Check-out</div>
                            <div class="value"><?php echo date('D, M j, Y', strtotime($booking['check_out_date'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Room Type</div>
                            <div class="value"><?php echo htmlspecialchars($booking['room_type']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Guests</div>
                            <div class="value"><?php echo htmlspecialchars($booking['max_guests']); ?> guests max</div>
                        </div>
                        <div class="info-item">
                            <div class="label">Booking Date</div>
                            <div class="value"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Booking Reference</div>
                            <div class="value">#<?php echo str_pad($booking['booking_id'], 8, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </div>

                    <h2 class="section-title">Room Amenities</h2>
                    <div class="amenities-list">
                        <?php
                        $amenities = ['Free WiFi', 'Air conditioning', 'Flat-screen TV', 'Private bathroom', 'Coffee maker', 'Mini fridge'];
                        foreach ($amenities as $amenity): ?>
                            <div class="amenity-item">
                                <i class="fas fa-check"></i>
                                <?php echo htmlspecialchars($amenity); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($booking['booking_status'] === 'pending'): ?>
                        <div class="action-buttons">
                            <a href="payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">Proceed to Payment</a>
                            <button onclick="confirmCancellation(<?php echo $booking_id; ?>)" class="btn btn-danger">Cancel Booking</button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="booking-sidebar">
                    <h2 class="section-title">Price Details</h2>
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>Room Rate (<?php echo $nights; ?> nights)</span>
                            <span>RM <?php echo number_format($booking['price_per_night'] * $nights, 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Taxes & Fees</span>
                            <span>RM <?php echo number_format($booking['total_price'] - ($booking['price_per_night'] * $nights), 2); ?></span>
                        </div>
                        <div class="price-row total">
                            <span>Total Price</span>
                            <span>RM <?php echo number_format($booking['total_price'], 2); ?></span>
                        </div>
                    </div>

                    <?php if ($booking['booking_status'] === 'confirmed'): ?>
                        <div class="action-buttons">
                            <a href="#" onclick="viewReceipt(<?php echo $booking_id; ?>)" class="btn btn-outline">View Receipt</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmCancellation(bookingId) {
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cancel_booking.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'booking_id';
                input.value = bookingId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewReceipt(bookingId) {
            // In a real application, this would open a modal or navigate to a receipt page
            alert('Receipt viewing functionality will be implemented here');
        }
    </script>
</body>
</html>

<?php
$conn->close();
?> 