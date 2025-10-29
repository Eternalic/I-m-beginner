<?php
session_start();
require_once 'db.php';

// Get user information if logged in
$username = '';
if (isset($_SESSION['user_id'])) {
    $user_query = "SELECT username FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result->num_rows > 0) {
        $username = $user_result->fetch_assoc()['username'];
    }
    $user_stmt->close();
}

// Get parameters from URL
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

// Calculate number of nights
$nights = 1;
if (!empty($checkin) && !empty($checkout)) {
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $interval = $checkin_date->diff($checkout_date);
    $nights = $interval->days;
    if ($nights < 1) $nights = 1;
}

// Fetch hotel details
$sql = "
    SELECT h.hotel_id, h.name, h.location, h.city, h.country, h.image_url,
           COALESCE(AVG(rev.rating), 0) as avg_rating
    FROM hotels h
    LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
    WHERE h.hotel_id = ?
    GROUP BY h.hotel_id, h.name, h.location, h.city, h.country, h.image_url
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $hotel_id);
$stmt->execute();
$hotel_result = $stmt->get_result();
$hotel = $hotel_result->fetch_assoc();
$stmt->close();

// Determine hotel type for images based on star rating
if ($hotel) {
    $hotelType = 'budget';
    if ($hotel['avg_rating'] >= 4) {
        $hotelType = 'luxury';
    } elseif (stripos($hotel['location'], 'beach') !== false || stripos($hotel['location'], 'ocean') !== false) {
        $hotelType = 'beach';
    } elseif (stripos($hotel['location'], 'city') !== false || stripos($hotel['city'], 'tokyo') !== false) {
        $hotelType = 'city';
    }
    
    // Get high-quality images
    $hotelImages = getHotelImages($hotelType, 5);
}

// Fetch room details
$room_sql = "
    SELECT r.room_type, r.price_per_night, r.max_guests,
           ra.room_size, ra.beds, GROUP_CONCAT(DISTINCT ra.amenities) as all_amenities
    FROM rooms r
    LEFT JOIN room_amenities ra ON r.room_id = ra.room_id
    WHERE r.hotel_id = ? AND r.room_type = ?
    GROUP BY r.room_id
";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param('is', $hotel_id, $room_type);
$room_stmt->execute();
$room_result = $room_stmt->get_result();
$room = $room_result->fetch_assoc();
$room_stmt->close();


// Currency symbol
$currency = 'MYR ';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name'] ?? 'Hotel Deal'); ?> - Ered Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .logo:hover {
            color: #c8a97e;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-menu a {
            color: #1a1a1a;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border-radius: 25px;
        }

        .nav-menu a:hover {
            background: #f8f8f8;
            color: #c8a97e;
        }

        .deal-container {
            margin-top: 100px;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 30px;
            position: relative;
            z-index: 1;
        }

        .hotel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .hotel-title h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .hotel-info p {
            font-size: 15px;
            color: #666;
            margin: 8px 0;
        }

        .hotel-image-gallery {
            margin: 30px 0;
            width: 100%;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 250px 250px;
            gap: 15px;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .gallery-item {
            overflow: hidden;
            position: relative;
            border-radius: 15px;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.03);
        }
        
        .gallery-item.main-image {
            grid-row: span 2;
        }
        
        .gallery-item .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 10px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover .overlay {
            opacity: 1;
        }

        .room-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .room-header {
            background: #f8f8f8;
            padding: 25px 30px;
            border-bottom: 1px solid #eee;
        }

        .room-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            color: #1a1a1a;
            margin: 0;
        }


        .room-details {
            padding: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .room-image {
            flex: 1;
            min-width: 300px;
            border-radius: 15px;
            overflow: hidden;
        }

        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .room-image:hover img {
            transform: scale(1.03);
        }

        .room-info {
            flex: 2;
            min-width: 300px;
        }

        .room-features {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 15px 0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .feature-item i {
            color: #c8a97e;
        }


        .booking-summary {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin: 30px 0;
        }

        .booking-summary h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .booking-details {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin-bottom: 30px;
        }

        .booking-info-col {
            flex: 1;
            min-width: 250px;
        }

        .booking-info-item {
            margin-bottom: 15px;
        }

        .booking-info-label {
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .summary-price-box {
            background: #f8f8f8;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #666;
        }

        .price-row.total {
            font-weight: 600;
            font-size: 20px;
            color: #1a1a1a;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #eee;
        }

        .book-now-btn {
            background: #c8a97e;
            color: #fff;
            padding: 16px 32px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 200px;
            margin-top: 20px;
        }
        
        .book-now-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        .login-required-notice {
            background: #f8f4eb;
            padding: 15px;
            border-radius: 15px;
            margin: 20px auto;
            color: #c8a97e;
            text-align: center;
            font-weight: 500;
            max-width: 400px;
        }

        .back-to-details {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
            padding: 12px 24px;
            background: #fff;
            border: 2px solid #c8a97e;
            border-radius: 30px;
            color: #c8a97e;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .back-to-details:hover {
            background: #c8a97e;
            color: #fff;
            transform: translateX(-5px);
            box-shadow: 0 4px 20px rgba(200, 169, 126, 0.2);
        }

        .back-to-details i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .back-to-details:hover i {
            transform: translateX(-3px);
        }

        @media (max-width: 768px) {
            .deal-container {
                margin-top: 80px;
            }

            .hotel-image-gallery {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(5, 200px);
            }

            .gallery-item.main-image {
                grid-column: 1;
                grid-row: 1;
            }

            .room-details {
                flex-direction: column;
            }


            .booking-details {
                flex-direction: column;
                gap: 20px;
            }

            .hotel-title h1 {
                font-size: 32px;
            }

            .header-top {
                padding: 0 15px;
            }

            .nav-menu {
            gap: 10px;
            }

            .nav-menu a {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-top">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>" class="logo">Ered Hotel</a>
                <div class="nav-menu">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>">Home</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="own_account.php">Hello, <?php echo htmlspecialchars($username); ?></a>
                    <?php else: ?>
                        <a href="login.php">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($hotel && $room): ?>
            <div class="deal-container">
                <div class="hotel-header">
                    <div class="hotel-title">
                        <h1><?php echo htmlspecialchars($hotel['name']); ?> <?php echo str_repeat('★', round($hotel['avg_rating'])); ?></h1>
                        <p><?php echo htmlspecialchars($hotel['location']); ?>, <?php echo htmlspecialchars($hotel['city']); ?>, <?php echo htmlspecialchars($hotel['country']); ?></p>
                    </div>
                </div>
                
                <div class="hotel-image-gallery">
                    <div class="gallery-item main-image">
                        <img src="<?php echo htmlspecialchars($hotelImages[0]); ?>" alt="Main Hotel View">
                        <div class="overlay">Main View</div>
                    </div>
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($hotelImages[1]); ?>" alt="Hotel Room">
                        <div class="overlay">Deluxe Room</div>
                    </div>
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($hotelImages[2]); ?>" alt="Hotel Bathroom">
                        <div class="overlay">Bathroom</div>
                    </div>
                    <div class="gallery-item">
                        <img src="<?php echo htmlspecialchars($hotelImages[3]); ?>" alt="Hotel Restaurant">
                        <div class="overlay">Restaurant</div>
                    </div>
                    <div class="gallery-item">
                        <img src="images/hotel_pool.jpg" alt="Hotel Pool" onerror="this.src='https://images.unsplash.com/photo-1591123120675-6f7f1aae0e5b?ixlib=rb-4.0.3'">
                        <div class="overlay">Swimming Pool</div>
                    </div>
                </div>

                <div class="room-card">
                    <div class="room-header">
                        <h2><?php echo htmlspecialchars($room_type); ?></h2>
                    </div>
                    
                    <div class="room-details">
                        <div class="room-image">
                            <img src="<?php echo htmlspecialchars($hotelImages[1]); ?>" alt="Room Image">
                        </div>
                        
                        <div class="room-info">
                            <p><?php echo htmlspecialchars($room['description'] ?? 'Comfortable room with modern amenities.'); ?></p>
                            
                            <div class="room-features">
                                <div class="feature-item">
                                    <span>✓</span> Room size: <?php echo htmlspecialchars($room['room_size'] ?? '22 m²'); ?>
                                </div>
                                <div class="feature-item">
                                    <span>✓</span> Max guests: <?php echo htmlspecialchars($room['max_guests']); ?>
                                </div>
                                <div class="feature-item">
                                    <span>✓</span> <?php echo htmlspecialchars($room['beds'] ?? '1 king bed or 2 single beds'); ?>
                                </div>
                            </div>
                            
                            <div class="amenities">
                                <?php 
                                if (!empty($room['all_amenities'])) {
                                    $amenities_list = explode(',', $room['all_amenities']);
                                    $unique_amenities = [];
                                    foreach ($amenities_list as $amenity_group) {
                                        $individual_amenities = explode(', ', $amenity_group);
                                        foreach ($individual_amenities as $amenity) {
                                            $unique_amenities[$amenity] = true;
                                        }
                                    }
                                    foreach (array_keys($unique_amenities) as $amenity): 
                                ?>
                                    <div class="amenity">
                                        <span>✓</span> <?php echo htmlspecialchars($amenity); ?>
                                    </div>
                                <?php 
                                    endforeach;
                                } 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="booking-summary">
                    <h2>Booking Summary</h2>
                    
                    <div class="booking-details">
                        <div class="booking-info-col">
                            <div class="booking-info-item">
                                <div class="booking-info-label">Hotel:</div>
                                <div><?php echo htmlspecialchars($hotel['name']); ?></div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">Room Type:</div>
                                <div><?php echo htmlspecialchars($room_type); ?></div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">Guests:</div>
                                <div><?php echo $guests; ?> guest(s)</div>
                            </div>
                        </div>
                        
                        <div class="booking-info-col">
                            <div class="booking-info-item">
                                <div class="booking-info-label">Check-in:</div>
                                <div><?php echo date('l, F j, Y', strtotime($checkin)); ?></div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">Check-out:</div>
                                <div><?php echo date('l, F j, Y', strtotime($checkout)); ?></div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">Length of stay:</div>
                                <div><?php echo $nights; ?> night(s)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="price-breakdown">
                        <div class="summary-price-box">
                            <div class="summary-price-title">Price Details</div>
                            <div class="price-row">
                                <span>Room Rate:</span>
                                <span>RM <?php echo number_format($room['price_per_night'], 2); ?> × <?php echo $nights; ?> night(s)</span>
                            </div>
                            <div class="price-row">
                                <span>Room Subtotal:</span>
                                <span>RM <?php echo number_format($room['price_per_night'] * $nights, 2); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Taxes & Fees (10%):</span>
                                <span>RM <?php echo number_format($room['price_per_night'] * $nights * 0.1, 2); ?></span>
                            </div>
                            <div class="price-row total">
                                <span>Total Price:</span>
                                <span>RM <?php echo number_format($room['price_per_night'] * $nights * 1.1, 2); ?></span>
                            </div>
                            <div class="price-details-note">
                                ✓ Price includes all taxes and fees
                                <br>✓ Free cancellation up to 24 hours before check-in
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Check if user is logged in
                    $is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
                    if ($is_logged_in): 
                    ?>
                        <a href="booking_form.php?hotel_id=<?php echo $hotel_id; ?>&checkin=<?php echo urlencode($checkin); ?>&checkout=<?php echo urlencode($checkout); ?>&guests=<?php echo $guests; ?>&room_type=<?php echo urlencode($room_type); ?>&total_price=<?php echo urlencode($room['price_per_night'] * $nights * 1.1); ?>" class="book-now-btn">Book Now</a>
                    <?php else: ?>
                        <div class="login-required-notice">
                            <i class="fas fa-exclamation-circle"></i> Please sign in to complete your booking
                        </div>
                        <a href="signin.php?redirect=view_deal.php?hotel_id=<?php echo $hotel_id; ?>&checkin=<?php echo urlencode($checkin); ?>&checkout=<?php echo urlencode($checkout); ?>&guests=<?php echo $guests; ?>&room_type=<?php echo urlencode($room_type); ?>" class="book-now-btn">Sign In to Book</a>
                    <?php endif; ?>
                </div>
                
                <a href="view_details.php?hotel_id=<?php echo $hotel_id; ?>&checkin=<?php echo $checkin; ?>&checkout=<?php echo $checkout; ?>&guests=<?php echo $guests; ?>" class="back-to-details">
                    <i class="fas fa-arrow-left"></i> Back to Hotel Details
                </a>
            </div>
        <?php else: ?>
            <div style="margin-top: 120px;">
                <h1>Room or Hotel Not Found</h1>
                <p>Sorry, the room or hotel you're looking for could not be found.</p>
                <a href="homepage.php" class="back-btn">Return to Homepage</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

<?php
$conn->close();
?> 