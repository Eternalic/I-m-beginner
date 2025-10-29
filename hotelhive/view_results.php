<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Check if hotel ID is provided
if (!isset($_GET['id'])) {
    header("Location: SignedIn_homepage.php");
    exit;
}

$hotel_id = $_GET['id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch hotel information
$sql = "SELECT h.*, 
        (SELECT MIN(price_per_night) FROM rooms WHERE hotel_id = h.hotel_id) as min_price,
        (SELECT MAX(price_per_night) FROM rooms WHERE hotel_id = h.hotel_id) as max_price
        FROM hotels h 
        WHERE h.hotel_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$result = $stmt->get_result();
$hotel = $result->fetch_assoc();

// Fetch available room types
$sql = "SELECT room_id, room_type, price_per_night, max_guests, bed_type, availability, availability_count, discount 
        FROM rooms 
        WHERE hotel_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$rooms = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Results - <?php echo htmlspecialchars($hotel['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
        }

        .header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            padding: 15px 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
        }

        .nav-menu a {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .hotel-card {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.2);
            padding: 30px;
            margin-bottom: 30px;
        }

        .hotel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .hotel-title h1 {
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .hotel-location {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .hotel-rating {
            color: #c8a97e;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .hotel-description {
            color: #444;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .price-range {
            font-size: 18px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .room-card {
            position: relative;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s ease;
            border: 1px solid #eee;
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .room-type {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .room-details {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .room-details p {
            margin-bottom: 8px;
        }

        .availability {
            color: #28a745;
            font-weight: 500;
        }

        .discount {
            color: #dc3545;
            font-weight: 500;
        }

        .room-price {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .original-price {
            color: #666;
            text-decoration: line-through;
            font-size: 16px;
        }

        .discounted-price {
            color: #c8a97e;
            font-size: 22px;
            font-weight: 600;
        }

        .per-night {
            color: #666;
            font-size: 14px;
        }

        .view-details-btn {
            display: inline-block;
            background: #c8a97e;
            color: #fff;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
                margin: 20px auto;
            }

            .hotel-header {
                flex-direction: column;
            }

            .hotel-title h1 {
                font-size: 24px;
            }

            .room-grid {
                grid-template-columns: 1fr;
            }
        }

        .booking-form {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c8a97e;
        }

        .flatpickr-calendar {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .room-id {
            font-size: 14px;
            color: #666;
            font-weight: normal;
            margin-left: 8px;
        }

        .no-availability {
            color: #dc3545;
            font-weight: 500;
        }

        .regular-price {
            color: #c8a97e;
            font-size: 22px;
            font-weight: 600;
        }

        .room-card {
            position: relative;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .room-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .room-card {
            position: relative;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #c8a97e;
        }

        .room-type {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        /* Remove the booking form since we're using direct room links */
        .booking-form {
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
        <div class="nav-menu">
            <a href="SignedIn_homepage.php">Home</a>
            <a href="#">Hello, <?php echo htmlspecialchars($username); ?></a>
        </div>
    </div>

    <div class="container">
        <?php if ($hotel): ?>
        <div class="hotel-card">
            <div class="hotel-header">
                <div class="hotel-title">
                    <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
                    <div class="hotel-location">📍 <?php echo htmlspecialchars($hotel['location']); ?></div>
                    <div class="hotel-rating">
                        <?php
                        for ($i = 0; $i < $hotel['star_rating']; $i++) {
                            echo "⭐";
                        }
                        ?>
                    </div>
                </div>
                <div class="price-range">
                    Price Range: RM <?php echo number_format($hotel['min_price'], 2); ?> - RM <?php echo number_format($hotel['max_price'], 2); ?> per night
                </div>
            </div>

            <div class="hotel-description">
                <?php echo htmlspecialchars($hotel['description']); ?>
            </div>

            <div class="booking-form">
                <h2>Check Availability</h2>
                <form id="bookingForm" onsubmit="return redirectToBooking(event)">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="checkin">Check-in Date</label>
                            <input type="text" id="checkin" required>
                        </div>
                        <div class="form-group">
                            <label for="checkout">Check-out Date</label>
                            <input type="text" id="checkout" required>
                        </div>
                    </div>
                    <button type="submit" class="view-details-btn">View Full Details & Book</button>
                </form>
            </div>

            <h2>Available Rooms</h2>
            <div class="room-grid">
                <?php 
                // Reset the result pointer
                mysqli_data_seek($rooms, 0);
                while ($room = $rooms->fetch_assoc()): 
                ?>
                <a href="view_details.php?hotel_id=<?php echo $hotel_id; ?>&room_id=<?php echo $room['room_id']; ?>" class="room-card-link">
                    <div class="room-card" data-room-id="<?php echo $room['room_id']; ?>">
                        <div class="room-type">
                            <?php echo htmlspecialchars($room['room_type']); ?>
                        </div>
                        <div class="room-details">
                            <p>Max Guests: <?php echo htmlspecialchars($room['max_guests']); ?></p>
                            <?php if ($room['bed_type']): ?>
                            <p>Bed Type: <?php echo htmlspecialchars($room['bed_type']); ?></p>
                            <?php endif; ?>
                            <?php if ($room['availability']): ?>
                                <?php if ($room['availability_count'] > 0): ?>
                                    <p class="availability">Available Rooms: <?php echo htmlspecialchars($room['availability_count']); ?></p>
                                <?php else: ?>
                                    <p class="no-availability">Currently Full</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="no-availability">Not Available</p>
                            <?php endif; ?>
                            <?php if ($room['discount'] > 0): ?>
                                <p class="discount">Discount: <?php echo htmlspecialchars($room['discount']); ?>%</p>
                            <?php endif; ?>
                        </div>
                        <div class="room-price">
                            <?php if ($room['discount'] > 0): ?>
                                <span class="original-price">RM <?php echo number_format($room['price_per_night'], 2); ?></span>
                                <span class="discounted-price">RM <?php echo number_format($room['price_per_night'] * (1 - $room['discount']/100), 2); ?></span>
                            <?php else: ?>
                                <span class="regular-price">RM <?php echo number_format($room['price_per_night'], 2); ?></span>
                            <?php endif; ?>
                            <span class="per-night">per night</span>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="hotel-card">
            <h1>Hotel not found</h1>
            <p>Sorry, we couldn't find the hotel you're looking for.</p>
            <a href="SignedIn_homepage.php" class="view-details-btn">Back to Homepage</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Initialize date pickers
        const checkinPicker = flatpickr("#checkin", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates) {
                checkoutPicker.set("minDate", selectedDates[0]);
            }
        });

        const checkoutPicker = flatpickr("#checkout", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });

        function redirectToBooking(event) {
            event.preventDefault();
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            
            if (checkin && checkout) {
                window.location.href = `view_details.php?hotel_id=<?php echo $hotel_id; ?>&checkin=${checkin}&checkout=${checkout}`;
            }
            return false;
        }
    </script>
</body>
</html> 