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
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

// Fetch hotel details
$sql = "
    SELECT h.hotel_id, h.name, h.location, h.city, h.country, h.description, h.image_url, 
           MIN(r.price_per_night) AS min_price,
           COALESCE(AVG(rev.rating), 0) as avg_rating
    FROM hotels h
    LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
    LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
    WHERE h.hotel_id = ?
    GROUP BY h.hotel_id, h.name, h.location, h.city, h.country, h.description, h.image_url
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Failed to prepare hotel query: " . $conn->error);
} else {
    $stmt->bind_param('i', $hotel_id);
    if (!$stmt->execute()) {
        error_log("Failed to execute hotel query: " . $stmt->error);
    } else {
        $hotel_result = $stmt->get_result();
        $hotel = $hotel_result->fetch_assoc();
        error_log("Hotel query executed successfully. Hotel found: " . ($hotel ? 'Yes' : 'No'));
        if ($hotel) {
            error_log("Hotel name: " . $hotel['name']);
            error_log("Hotel image_url: " . ($hotel['image_url'] ?? 'NULL'));
        }
    }
    $stmt->close();
}

// Fetch hotel images from hotel_img table
$images_sql = "
    SELECT hotel_image 
    FROM hotel_img 
    WHERE hotel_id = ? 
    ORDER BY hi_id ASC
";
$images_stmt = $conn->prepare($images_sql);
if (!$images_stmt) {
    error_log("Failed to prepare images query: " . $conn->error);
} else {
    $images_stmt->bind_param('i', $hotel_id);
    if (!$images_stmt->execute()) {
        error_log("Failed to execute images query: " . $images_stmt->error);
    } else {
        $images_result = $images_stmt->get_result();
        $hotelImages = [];
        while ($img = $images_result->fetch_assoc()) {
            $hotelImages[] = $img['hotel_image'];
        }
        error_log("Images query executed successfully. Found " . count($hotelImages) . " images.");
    }
    $images_stmt->close();
}

// If no images found in hotel_img table, use the default image from hotels table
if (empty($hotelImages) && $hotel['image_url']) {
    $hotelImages[] = $hotel['image_url'];
}

// Debug: Log image information
error_log("Hotel ID: " . $hotel_id);
error_log("Hotel Images Count: " . count($hotelImages));
error_log("Hotel Images: " . print_r($hotelImages, true));
error_log("Hotel Image URL: " . ($hotel['image_url'] ?? 'NULL'));

// Fetch average rating
$rating_sql = "
    SELECT AVG(rev.rating) as avg_rating, COUNT(rev.review_id) as review_count
    FROM reviews rev
    WHERE rev.hotel_id = ?
";
$rating_stmt = $conn->prepare($rating_sql);
$rating_stmt->bind_param('i', $hotel_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();
$avg_rating = $rating_data['avg_rating'] ? number_format($rating_data['avg_rating'], 1) : 'N/A';
$review_count = $rating_data['review_count'] ?? 0;
$rating_stmt->close();

// Fetch room details
$room_sql = "
    SELECT room_type, price_per_night, max_guests
    FROM rooms
    WHERE hotel_id = ? AND availability = TRUE AND max_guests >= ?
";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param('ii', $hotel_id, $guests);
$room_stmt->execute();
$room_result = $room_stmt->get_result();
$rooms = [];
while ($row = $room_result->fetch_assoc()) {
    $rooms[] = $row;
}
$room_stmt->close();

// Fetch hotel facilities
$facilities_sql = "
    SELECT facility
    FROM hotel_facilities
    WHERE h_id = ?
    ORDER BY f_id
";
$facilities_stmt = $conn->prepare($facilities_sql);
$facilities_stmt->bind_param('i', $hotel_id);
$facilities_stmt->execute();
$facilities_result = $facilities_stmt->get_result();
$facilities = [];
while ($row = $facilities_result->fetch_assoc()) {
    $facilities[] = $row['facility'];
}
$facilities_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - Ered Hotel</title>
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

        .header-content {
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

        /* Main Content */
        .main-content {
            padding-top: 80px;
            min-height: 100vh;
        }

        /* Hotel Gallery */
        .hotel-gallery {
            margin: 30px 0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 250px);
            gap: 15px;
            border-radius: 15px;
            overflow: hidden;
            min-height: 500px;
        }

        .gallery-main {
            grid-column: span 2;
            grid-row: span 2;
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 215, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hotel-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 0.3s ease;
            cursor: pointer;
            display: block !important;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .hotel-gallery img:hover {
            transform: scale(1.03);
        }

        /* Hotel Info Section */
        .hotel-info {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            padding: 40px;
            margin: 30px 0;
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 40px;
        }

        .hotel-details {
            flex: 2;
        }

        .hotel-details h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .rating-stars {
            color: #c8a97e;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .hotel-details p {
            color: #666;
            font-size: 15px;
            margin: 12px 0;
            line-height: 1.8;
        }

        .price-section {
            flex: 1;
            padding: 30px;
            background: #f8f8f8;
            border-radius: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .price-section h2 {
            font-size: 36px;
            color: #c8a97e;
            margin-bottom: 10px;
        }

        .price-section p {
            color: #666;
            margin: 8px 0;
            font-size: 14px;
        }

        .view-deal-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            padding: 16px 32px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .view-deal-btn:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Sections */
        .section {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            padding: 40px;
            margin: 30px 0;
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Facilities List */
        .facilities-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .facilities-list p {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 15px;
        }

        .facilities-list p::before {
            content: "✓";
            color: #c8a97e;
            margin-right: 10px;
            font-weight: bold;
        }

        /* Room Table */
        .room-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .room-table th,
        .room-table td {
            padding: 20px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .room-table th {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            background: #f8f8f8;
        }

        .room-table tr:last-child td {
            border-bottom: none;
        }

        .book-room-btn {
            background: #c8a97e;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .book-room-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 85vh;
        }

        .lightbox-content img {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 10px;
        }

        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: #fff;
            font-size: 30px;
            cursor: pointer;
            padding: 10px;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 30px;
            transform: translateY(-50%);
        }

        .lightbox-nav button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            font-size: 24px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .lightbox-nav button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .main-content {
                padding-top: 70px;
            }

            .hotel-gallery {
                grid-template-columns: 1fr;
                grid-template-rows: repeat(5, 200px);
            }

            .gallery-main {
                grid-column: 1;
                grid-row: 1;
            }

            .hotel-info {
                flex-direction: column;
                padding: 25px;
            }

            .hotel-details h1 {
                font-size: 32px;
            }

            .price-section {
                margin-top: 20px;
            }

            .section {
                padding: 25px;
            }

            .facilities-list {
                grid-template-columns: 1fr;
            }

            .room-table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Reviews Section Styles */
        .review-form {
            background: #f8f8f8;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .review-form h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .rating-input {
            margin-bottom: 20px;
        }

        .rating-input label {
            display: block;
            margin-bottom: 10px;
            color: #666;
            font-weight: 500;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            padding: 0 5px;
            transition: color 0.3s ease;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #c8a97e;
        }

        .comment-input {
            margin-bottom: 20px;
        }

        .comment-input label {
            display: block;
            margin-bottom: 10px;
            color: #666;
            font-weight: 500;
        }

        .comment-input textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
        }

        .submit-review-btn {
            background: #c8a97e;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-review-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        .reviews-list {
            margin-top: 30px;
        }

        .review-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 600;
            color: #1a1a1a;
        }

        .review-date {
            color: #666;
            font-size: 14px;
        }

        .review-rating {
            color: #c8a97e;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .review-comment {
            color: #666;
            line-height: 1.6;
        }

        .no-reviews {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .review-form {
                padding: 20px;
            }
            
            .star-rating label {
                font-size: 25px;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>" class="logo">Ered Hotel</a>
                <nav class="nav-menu">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>">Home</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="own_account.php">Hello, <?php echo htmlspecialchars($username); ?></a>
                    <?php else: ?>
                        <a href="signin.php">Sign In/Sign up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <?php if ($hotel): ?>
                <div class="hotel-gallery">
                    <?php if (!empty($hotelImages)): ?>
                        <div class="gallery-main">
                            <img src="<?php echo htmlspecialchars($hotelImages[0]); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?> Main View" onclick="openLightbox(0)" onerror="this.src='images/hotel/oceanview_1.jpg'">
                        </div>
                        <?php for ($i = 1; $i < min(count($hotelImages), 5); $i++): ?>
                            <img src="<?php echo htmlspecialchars($hotelImages[$i]); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?> View <?php echo $i; ?>" onclick="openLightbox(<?php echo $i; ?>)" onerror="this.src='images/hotel/oceanview_<?php echo ($i % 5) + 1; ?>.jpg'">
                        <?php endfor; ?>
                    <?php else: ?>
                        <div class="gallery-main">
                            <img src="images/hotel/oceanview_1.jpg" alt="<?php echo htmlspecialchars($hotel['name']); ?> Default View" onerror="this.src='images/hotel/oceanview.jpg'">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="hotel-info">
                    <div class="hotel-details">
                        <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
                        <div class="rating-stars">
                            <?php 
                            $rating = round($hotel['avg_rating']);
                            echo str_repeat('★', $rating);
                            echo str_repeat('☆', 5 - $rating);
                            ?>
                        </div>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hotel['location']); ?>, <?php echo htmlspecialchars($hotel['city']); ?>, <?php echo htmlspecialchars($hotel['country']); ?></p>
                        <p>
                            <?php if ($hotel['description']): ?>
                                <?php echo htmlspecialchars($hotel['description']); ?>
                            <?php else: ?>
                                Experience luxury and comfort at <?php echo htmlspecialchars($hotel['name']); ?>. Located in the heart of <?php echo htmlspecialchars($hotel['city']); ?>, our hotel offers modern amenities, exceptional service, and a perfect base for exploring the city.
                            <?php endif; ?>
                        </p>
                    </div>

                </div>

                <div class="section">
                    <h3>Hotel Facilities</h3>
                    <div class="facilities-list">
                        <?php if (count($facilities) > 0): ?>
                            <?php foreach ($facilities as $facility): ?>
                                <p><?php echo htmlspecialchars($facility); ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No facilities information available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section">
                    <h3>Available Rooms</h3>
                    <table class="room-table">
                        <thead>
                            <tr>
                                <th>Room Type</th>
                                <th>Price per Night</th>
                                <th>Max Guests</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rooms) > 0): ?>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td>RM <?php echo number_format($room['price_per_night'], 2); ?></td>
                                        <td><?php echo $room['max_guests']; ?> guests</td>
                                        <td>
                                            <a href="view_deal.php?hotel_id=<?php echo $hotel_id; ?>&checkin=<?php echo urlencode($checkin); ?>&checkout=<?php echo urlencode($checkout); ?>&guests=<?php echo $guests; ?>&room_type=<?php echo urlencode($room['room_type']); ?>#rate-options" class="book-room-btn">Book Now</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No rooms available for the selected criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Reviews Section -->
                <div class="section">
                    <h3>Guest Reviews</h3>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Review Form -->
                        <div class="review-form">
                            <h4>Write a Review</h4>
                            <form action="submit_review.php" method="POST" class="rating-form">
                                <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                                <div class="rating-input">
                                    <label>Your Rating:</label>
                                    <div class="star-rating">
                                        <input type="radio" name="rating" value="5" id="star5" required>
                                        <label for="star5">★</label>
                                        <input type="radio" name="rating" value="4" id="star4">
                                        <label for="star4">★</label>
                                        <input type="radio" name="rating" value="3" id="star3">
                                        <label for="star3">★</label>
                                        <input type="radio" name="rating" value="2" id="star2">
                                        <label for="star2">★</label>
                                        <input type="radio" name="rating" value="1" id="star1">
                                        <label for="star1">★</label>
                                    </div>
                                </div>
                                <div class="comment-input">
                                    <label for="comment">Your Review:</label>
                                    <textarea name="comment" id="comment" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="submit-review-btn">Submit Review</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: #f8f8f8; border-radius: 15px;">
                            <p style="color: #666; margin-bottom: 15px;">Want to share your experience?</p>
                            <a href="signin.php" style="display: inline-block; background: #c8a97e; color: white; padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">Sign in to write a review</a>
                        </div>
                    <?php endif; ?>

                    <!-- Display Reviews -->
                    <div class="reviews-list">
                        <?php
                        // Fetch reviews for this hotel
                        $reviews_sql = "
                            SELECT r.*, u.username 
                            FROM reviews r 
                            JOIN users u ON r.user_id = u.user_id 
                            WHERE r.hotel_id = ? 
                            ORDER BY r.created_at DESC
                        ";
                        $reviews_stmt = $conn->prepare($reviews_sql);
                        $reviews_stmt->bind_param('i', $hotel_id);
                        $reviews_stmt->execute();
                        $reviews_result = $reviews_stmt->get_result();
                        
                        if ($reviews_result->num_rows > 0):
                            while ($review = $reviews_result->fetch_assoc()):
                        ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                    <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php echo str_repeat('★', $review['rating']); ?>
                                    <?php echo str_repeat('☆', 5 - $review['rating']); ?>
                                </div>
                                <div class="review-comment">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </div>
                                <?php if (isset($_SESSION['user_id']) && $review['user_id'] == $_SESSION['user_id']): ?>
                                    <div class="review-actions" style="margin-top: 10px;">
                                        <button class="edit-review-btn" onclick="showEditForm(<?php echo $review['review_id']; ?>, <?php echo $review['rating']; ?>, '<?php echo addslashes($review['comment']); ?>')" style="background: #c8a97e; color: white; padding: 8px 15px; border: none; border-radius: 20px; font-size: 12px; cursor: pointer;">Edit Review</button>
                                    </div>
                                    <!-- Edit Form (Hidden by default) -->
                                    <div id="edit-form-<?php echo $review['review_id']; ?>" class="edit-review-form" style="display: none; margin-top: 15px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
                                        <form action="edit_review.php" method="POST">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                                            <div class="rating-input">
                                                <label>Edit Rating:</label>
                                                <div class="star-rating">
                                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="edit-star<?php echo $i; ?>-<?php echo $review['review_id']; ?>" <?php echo ($review['rating'] == $i) ? 'checked' : ''; ?>>
                                                        <label for="edit-star<?php echo $i; ?>-<?php echo $review['review_id']; ?>">★</label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="comment-input">
                                                <label>Edit Comment:</label>
                                                <textarea name="comment" rows="4" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
                                            </div>
                                            <div style="margin-top: 10px;">
                                                <button type="submit" class="submit-review-btn" style="margin-right: 10px;">Update Review</button>
                                                <button type="button" onclick="hideEditForm(<?php echo $review['review_id']; ?>)" style="background: #666; color: white; padding: 12px 25px; border: none; border-radius: 25px; font-size: 14px; cursor: pointer;">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <p class="no-reviews">No reviews yet. <?php echo isset($_SESSION['user_id']) ? 'Be the first to review this hotel!' : 'Sign in to be the first to review this hotel!'; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lightbox -->
                <div class="lightbox" id="imageLightbox">
                    <div class="lightbox-content">
                        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
                        <img id="lightboxImage" src="" alt="Hotel image full view">
                        <div class="lightbox-nav">
                            <button onclick="changeImage(-1)"><i class="fas fa-chevron-left"></i></button>
                            <button onclick="changeImage(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>

                <script>
                    // Array to store all gallery images
                    const galleryImages = [
                        <?php foreach($hotelImages as $img): ?>
                        "<?php echo htmlspecialchars($img); ?>",
                        <?php endforeach; ?>
                    ];
                    
                    let currentImageIndex = 0;
                    const lightbox = document.getElementById('imageLightbox');
                    const lightboxImg = document.getElementById('lightboxImage');
                    
                    // Open lightbox with specific image
                    function openLightbox(index) {
                        currentImageIndex = index;
                        lightboxImg.src = galleryImages[index];
                        lightbox.style.display = 'flex';
                        document.body.style.overflow = 'hidden'; // Prevent scrolling
                    }
                    
                    // Close the lightbox
                    function closeLightbox() {
                        lightbox.style.display = 'none';
                        document.body.style.overflow = 'auto'; // Enable scrolling
                    }
                    
                    // Change displayed image
                    function changeImage(direction) {
                        currentImageIndex += direction;
                        
                        // Handle wrap-around
                        if (currentImageIndex >= galleryImages.length) {
                            currentImageIndex = 0;
                        } else if (currentImageIndex < 0) {
                            currentImageIndex = galleryImages.length - 1;
                        }
                        
                        lightboxImg.src = galleryImages[currentImageIndex];
                    }
                    
                    // Keyboard navigation
                    document.addEventListener('keydown', function(e) {
                        if (lightbox.style.display === 'flex') {
                            if (e.key === 'ArrowLeft') {
                                changeImage(-1);
                            } else if (e.key === 'ArrowRight') {
                                changeImage(1);
                            } else if (e.key === 'Escape') {
                                closeLightbox();
                            }
                        }
                    });
                    
                    // Close lightbox when clicking outside the image
                    lightbox.addEventListener('click', function(e) {
                        if (e.target === lightbox) {
                            closeLightbox();
                        }
                    });
                </script>

                <!-- Add JavaScript for edit functionality -->
                <script>
                    function showEditForm(reviewId, rating, comment) {
                        document.getElementById('edit-form-' + reviewId).style.display = 'block';
                    }

                    function hideEditForm(reviewId) {
                        document.getElementById('edit-form-' + reviewId).style.display = 'none';
                    }
                </script>

            <?php else: ?>
                <div class="section" style="margin-top: 120px;">
                    <h3>Hotel not found</h3>
                    <p>Sorry, we couldn't find the hotel you're looking for.</p>
                    <a href="homepage.php" style="display: inline-block; margin-top: 20px; color: #1a4d3e; text-decoration: none;">← Back to Homepage</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>