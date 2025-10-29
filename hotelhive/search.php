<?php
session_start();
require_once 'db.php';

// Error handling
$error_message = '';
$success_message = '';

// Input validation and sanitization
$destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';
$checkin = isset($_POST['checkin']) ? $_POST['checkin'] : '';
$checkout = isset($_POST['checkout']) ? $_POST['checkout'] : '';
$rooms = 1; // Default to 1 room since guests field was removed
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'price-low';

// Validate required fields
if (empty($destination)) {
    $error_message = "Please enter a destination to search.";
}

// Parse destination into city and country
$destination_parts = explode(',', $destination);
$city = trim($destination_parts[0] ?? '');
$country = trim($destination_parts[1] ?? '');

// Get filter parameters with validation
$stars = isset($_POST['stars']) && is_array($_POST['stars']) ? array_map('intval', $_POST['stars']) : [];
$price_ranges = isset($_POST['price']) && is_array($_POST['price']) ? $_POST['price'] : [];
$facilities = isset($_POST['facilities']) && is_array($_POST['facilities']) ? $_POST['facilities'] : [];
$ratings = isset($_POST['rating']) && is_array($_POST['rating']) ? $_POST['rating'] : [];

// Validate star ratings
$stars = array_filter($stars, function($star) {
    return $star >= 1 && $star <= 5;
});

// Validate price ranges
$valid_price_ranges = ['0-50', '50-100', '100-150', '150-200', '200+'];
$price_ranges = array_filter($price_ranges, function($range) use ($valid_price_ranges) {
    return in_array($range, $valid_price_ranges);
});

// Validate ratings
$valid_ratings = ['1', '2', '3', '4', '5'];
$ratings = array_filter($ratings, function($rating) use ($valid_ratings) {
    return in_array($rating, $valid_ratings);
});

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

// Only proceed with search if no errors
$hotels = [];
$total_results = 0;
$star_counts = [];
$price_counts = [];
$rating_counts = [];

if (empty($error_message)) {
    // Prepare search parameters
    $like_city = "%$city%";
    $like_country = "%$country%";
    $like_location = "%$destination%";
    
    // Build the main SQL query with filters
    $sql = "
        SELECT h.hotel_id, h.name, h.location, h.city, h.country, h.image_url, 
               MIN(r.price_per_night) AS min_price,
               AVG(rev.rating) AS avg_rating,
               COUNT(rev.review_id) AS review_count
        FROM hotels h
        LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
        LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
        WHERE (h.city LIKE ? OR h.country LIKE ? OR h.location LIKE ?)
        AND r.max_guests >= ?
        AND r.availability = TRUE
    ";
    $params = [$like_city, $like_country, $like_location, $rooms];
    $types = 'sssi';

    // Add star rating filter
    if (!empty($stars)) {
        $placeholders = implode(',', array_fill(0, count($stars), '?'));
        $sql .= " AND EXISTS (
            SELECT 1 FROM reviews rev2 
            WHERE rev2.hotel_id = h.hotel_id 
            AND rev2.rating IN ($placeholders)
        )";
        $params = array_merge($params, $stars);
        $types .= str_repeat('i', count($stars));
    }

    // Add price range filter
    if (!empty($price_ranges)) {
        $price_conditions = [];
        foreach ($price_ranges as $range) {
            list($min, $max) = explode('-', $range);
            $max = $max === '+' ? 10000 : (int)$max;
            $price_conditions[] = "(r.price_per_night BETWEEN ? AND ?)";
            $params[] = (int)$min;
            $params[] = $max;
            $types .= 'ii';
        }
        $sql .= " AND (" . implode(' OR ', $price_conditions) . ")";
    }

    // Add guest rating filter
    if (!empty($ratings)) {
        $placeholders = implode(',', array_fill(0, count($ratings), '?'));
        $sql .= " AND EXISTS (
            SELECT 1 FROM reviews rev3 
            WHERE rev3.hotel_id = h.hotel_id 
            AND rev3.rating IN ($placeholders)
        )";
        $params = array_merge($params, $ratings);
        $types .= str_repeat('i', count($ratings));
    }

    // Add GROUP BY and ORDER BY
    $sql .= " GROUP BY h.hotel_id, h.name, h.location, h.city, h.country, h.image_url";

    // Add ORDER BY clause based on sort parameter
    switch ($sort) {
        case 'price-high':
            $sql .= " ORDER BY min_price DESC";
            break;
        case 'rating-high':
            $sql .= " ORDER BY avg_rating DESC, min_price ASC";
            break;
        case 'rating-low':
            $sql .= " ORDER BY avg_rating ASC, min_price ASC";
            break;
        case 'name':
            $sql .= " ORDER BY h.name ASC";
            break;
        default:
            $sql .= " ORDER BY min_price ASC";
    }

    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch all hotels
        while ($row = $result->fetch_assoc()) {
            $hotels[] = $row;
        }
        $total_results = count($hotels);
        $stmt->close();
        
    } catch (Exception $e) {
        $error_message = "Search failed. Please try again.";
        error_log("Search error: " . $e->getMessage());
    }
}

// Calculate filter counts efficiently
if (empty($error_message)) {
    try {
        // Get base search results for counting
        $base_sql = "
            SELECT h.hotel_id, MIN(r.price_per_night) as min_price, AVG(rev.rating) as avg_rating
            FROM hotels h
            LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
            LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
            WHERE (h.city LIKE ? OR h.country LIKE ? OR h.location LIKE ?)
            AND r.max_guests >= ?
            AND r.availability = TRUE
            GROUP BY h.hotel_id
        ";
        
        $base_stmt = $conn->prepare($base_sql);
        $base_stmt->bind_param('sssi', $like_city, $like_country, $like_location, $rooms);
        $base_stmt->execute();
        $base_result = $base_stmt->get_result();
        
        // Initialize counts
        $star_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $price_counts = ['0-50' => 0, '50-100' => 0, '100-150' => 0, '150-200' => 0, '200+' => 0];
        $rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        // Process each hotel for counts
        while ($row = $base_result->fetch_assoc()) {
            $price = $row['min_price'];
            $avg_rating = $row['avg_rating'];
            
            // Count by price range
            if ($price <= 50) $price_counts['0-50']++;
            elseif ($price <= 100) $price_counts['50-100']++;
            elseif ($price <= 150) $price_counts['100-150']++;
            elseif ($price <= 200) $price_counts['150-200']++;
            else $price_counts['200+']++;
            
            // Count by rating (round to nearest whole number)
            if ($avg_rating > 0) {
                $rounded_rating = round($avg_rating);
                if ($rounded_rating >= 1 && $rounded_rating <= 5) {
                    $rating_counts[$rounded_rating]++;
                }
            }
        }
        $base_stmt->close();
        
        // Get star rating counts (separate query for accuracy)
        for ($i = 1; $i <= 5; $i++) {
            $star_sql = "
                SELECT COUNT(DISTINCT h.hotel_id) as count 
                FROM hotels h 
                LEFT JOIN rooms r ON h.hotel_id = r.hotel_id 
                LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
                WHERE (h.city LIKE ? OR h.country LIKE ? OR h.location LIKE ?) 
                AND r.max_guests >= ? 
                AND r.availability = TRUE
                AND rev.rating = ?
            ";
            $star_stmt = $conn->prepare($star_sql);
            $star_stmt->bind_param('sssii', $like_city, $like_country, $like_location, $rooms, $i);
            $star_stmt->execute();
            $star_counts[$i] = $star_stmt->get_result()->fetch_assoc()['count'] ?? 0;
            $star_stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Filter count error: " . $e->getMessage());
        // Set default counts if error occurs
        $star_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $price_counts = ['0-50' => 0, '50-100' => 0, '100-150' => 0, '150-200' => 0, '200+' => 0];
        $rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ered Hotel - Search Results</title>
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

        /* Main Content Wrapper */
        .main-wrapper {
            padding-top: 80px; /* Adjusted to prevent header overlap */
            min-height: 100vh;
            background: transparent;
        }

        /* Search Summary */
        .search-summary {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            padding: 30px;
            margin: 20px 0 40px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .search-summary h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .search-summary p {
            color: #666;
            font-size: 15px;
            margin: 8px 0;
            line-height: 1.6;
        }

        /* Main Content Layout */
        .main-content {
            display: flex;
            gap: 30px;
            margin-bottom: 50px;
            align-items: flex-start;
        }

        /* Filters Panel */
        .filters {
            position: sticky;
            top: 100px;
            flex: 1;
            min-width: 300px;
            max-width: 340px;
            background: #fff;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            align-self: flex-start;
        }

        .filters h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .filter-group {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 20px;
            padding-bottom: 0;
        }

        .filter-group h4 {
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .filter-option:hover {
            background: #f8f8f8;
        }

        .filter-option input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 12px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-option input[type="checkbox"]:checked {
            background: #c8a97e;
            border-color: #c8a97e;
        }

        .filter-option input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            left: 4px;
            top: 0px;
        }

        .filter-option label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
            font-size: 14px;
            color: #444;
            cursor: pointer;
        }

        .filter-count {
            color: #888;
            font-size: 13px;
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 35px;
            text-align: center;
        }

        .filters button {
            background: #c8a97e;
            color: #fff;
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .filters button:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        .filters button:active {
            transform: translateY(0);
        }

        /* Add ripple effect */
        .filters button::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
            background-repeat: no-repeat;
            background-position: 50%;
            transform: scale(10, 10);
            opacity: 0;
            transition: transform .5s, opacity 1s;
        }

        .filters button:active::after {
            transform: scale(0, 0);
            opacity: .3;
            transition: 0s;
        }

        @media (max-width: 768px) {
            .filters {
                position: static;
                max-width: none;
                border-radius: 15px;
                margin-bottom: 20px;
            }

            .filter-group {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }

            .filter-option {
                padding: 10px;
            }
        }

        /* Results Section */
        .results {
            flex: 3;
            min-width: 300px;
        }

        .sort-bar {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .sort-bar select {
            width: 100%;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            max-width: 300px;
        }

        .sort-bar select:focus {
            border-color: #c8a97e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.1);
        }

        .hotel-result {
            background: #fff;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 25px;
            transition: all 0.3s ease;
        }

        .hotel-result:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .hotel-result img {
            width: 250px;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }

        .hotel-info {
            flex: 1;
        }

        .hotel-info h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .hotel-info p {
            color: #666;
            font-size: 14px;
            margin: 8px 0;
        }

        .hotel-price {
            text-align: right;
            min-width: 150px;
        }

        .hotel-price .price {
            font-size: 24px;
            color: #c8a97e;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .hotel-price .per-night {
            font-size: 14px;
            color: #666;
            font-weight: 400;
        }

        .rating {
            margin: 10px 0;
        }

        .stars {
            color: #ffc107;
            margin-bottom: 5px;
        }

        .stars i {
            font-size: 14px;
            margin-right: 2px;
        }

        .rating-text {
            font-size: 13px;
            color: #666;
        }

        .location {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .city-country {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .no-results i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-results h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }

        .no-results p {
            color: #666;
            font-size: 16px;
        }

        .error-message, .success-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .sort-bar {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sort-bar label {
            font-weight: 600;
            color: #333;
        }

        .filter-option label span:first-child {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filter-option label span:first-child {
            font-size: 16px;
        }

        .hotel-price button {
            background: #c8a97e;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .hotel-price button:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-wrapper {
                padding-top: 70px;
            }

            .header-content {
                padding: 0 15px;
            }

            .search-summary h2 {
                font-size: 28px;
            }

            .main-content {
                flex-direction: column;
            }

            .results {
                min-width: 100%;
            }

            .hotel-result {
                flex-direction: column;
            }

            .hotel-result img {
                width: 100%;
                height: 200px;
            }

            .hotel-price {
                text-align: left;
                margin-top: 15px;
            }
        }

        /* Mobile Filter Styles */
        .filter-toggle {
            display: none;
            width: 100%;
            background: #c8a97e;
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 20px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .filter-toggle i {
            font-size: 16px;
        }

        .filters-header {
            display: none;
        }

        .close-filters {
            display: none;
        }

        @media (max-width: 768px) {
            .filter-toggle {
                display: flex;
            }

            .filters {
                position: fixed;
                left: -100%;
                top: 0;
                height: 100vh;
                width: 85%;
                max-width: 350px;
                background: #fff;
                z-index: 1001;
                padding: 20px;
                overflow-y: auto;
                transition: left 0.3s ease;
                margin: 0;
            }

            .filters.active {
                left: 0;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            }

            .filters-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #e0e0e0;
            }

            .filters-header h3 {
                margin: 0;
            }

            .close-filters {
                display: block;
                background: none;
                border: none;
                font-size: 24px;
                color: #666;
                cursor: pointer;
                padding: 5px;
            }

            .filter-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
            }

            .filter-overlay.active {
                display: block;
            }

            .filter-group {
                margin-bottom: 20px;
            }

            .filter-options {
                max-height: none;
            }

            .filter-option {
                padding: 12px;
            }

            .filter-option input[type="checkbox"] {
                width: 22px;
                height: 22px;
            }

            .filter-option label {
                font-size: 16px;
            }

            .filters button[type="submit"] {
                position: sticky;
                bottom: 20px;
                margin-top: 30px;
            }

            .main-content {
                display: block;
            }

            .results {
                width: 100%;
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

    <div class="main-wrapper">
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="search-summary">
            <h2>Search Results for <?php echo htmlspecialchars($destination); ?></h2>
            <p>Check-in: <?php echo htmlspecialchars($checkin); ?> | Check-out: <?php echo htmlspecialchars($checkout); ?></p>
            <p><?php echo $total_results; ?> properties found</p>
        </div>

        <button class="filter-toggle">
            <i class="fas fa-filter"></i>
            Filter Results
        </button>

        <div class="main-content">
            <div class="filters">
                <div class="filters-header">
                    <h3>Filter Results</h3>
                    <button class="close-filters">&times;</button>
                </div>
                <form method="POST" action="search.php">
                    <input type="hidden" name="destination" value="<?php echo htmlspecialchars($destination); ?>">
                    <input type="hidden" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>">
                    <input type="hidden" name="checkout" value="<?php echo htmlspecialchars($checkout); ?>">
                        
                    <div class="filter-group">
                        <h4>Star Rating</h4>
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input type="checkbox" id="star5" name="stars[]" value="5" <?php echo in_array('5', $stars) ? 'checked' : ''; ?>>
                                    <label for="star5">
                                        <span>5 Stars</span>
                                        <span class="filter-count"><?php echo $star_counts[5]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="star4" name="stars[]" value="4" <?php echo in_array('4', $stars) ? 'checked' : ''; ?>>
                                    <label for="star4">
                                        <span>4 Stars</span>
                                        <span class="filter-count"><?php echo $star_counts[4]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="star3" name="stars[]" value="3" <?php echo in_array('3', $stars) ? 'checked' : ''; ?>>
                                    <label for="star3">
                                        <span>3 Stars</span>
                                        <span class="filter-count"><?php echo $star_counts[3]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="star2" name="stars[]" value="2" <?php echo in_array('2', $stars) ? 'checked' : ''; ?>>
                                    <label for="star2">
                                        <span>2 Stars</span>
                                        <span class="filter-count"><?php echo $star_counts[2]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="star1" name="stars[]" value="1" <?php echo in_array('1', $stars) ? 'checked' : ''; ?>>
                                    <label for="star1">
                                        <span>1 Star</span>
                                        <span class="filter-count"><?php echo $star_counts[1]; ?></span>
                                    </label>
                                </div>
                            </div>
                    </div>

                    <div class="filter-group">
                        <h4>Price per Night</h4>
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input type="checkbox" id="price1" name="price[]" value="0-50" <?php echo in_array('0-50', $price_ranges) ? 'checked' : ''; ?>>
                                    <label for="price1">
                                        <span>MYR 0 - MYR 50</span>
                                        <span class="filter-count"><?php echo $price_counts['0-50']; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="price2" name="price[]" value="50-100" <?php echo in_array('50-100', $price_ranges) ? 'checked' : ''; ?>>
                                    <label for="price2">
                                        <span>MYR 50 - MYR 100</span>
                                        <span class="filter-count"><?php echo $price_counts['50-100']; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="price3" name="price[]" value="100-150" <?php echo in_array('100-150', $price_ranges) ? 'checked' : ''; ?>>
                                    <label for="price3">
                                        <span>MYR 100 - MYR 150</span>
                                        <span class="filter-count"><?php echo $price_counts['100-150']; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="price4" name="price[]" value="150-200" <?php echo in_array('150-200', $price_ranges) ? 'checked' : ''; ?>>
                                    <label for="price4">
                                        <span>MYR 150 - MYR 200</span>
                                        <span class="filter-count"><?php echo $price_counts['150-200']; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="price5" name="price[]" value="200+" <?php echo in_array('200+', $price_ranges) ? 'checked' : ''; ?>>
                                    <label for="price5">
                                        <span>MYR 200+</span>
                                        <span class="filter-count"><?php echo $price_counts['200+']; ?></span>
                                    </label>
                                </div>
                            </div>
                    </div>

                    <div class="filter-group">
                        <h4>Facilities</h4>
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input type="checkbox" id="wifi" name="facilities[]" value="wifi" <?php echo in_array('wifi', $facilities) ? 'checked' : ''; ?>>
                                    <label for="wifi">
                                        <span>Free Wi-Fi</span>
                                        <span class="filter-count">2</span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="pool" name="facilities[]" value="pool" <?php echo in_array('pool', $facilities) ? 'checked' : ''; ?>>
                                    <label for="pool">
                                        <span>Swimming Pool</span>
                                        <span class="filter-count">1</span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="parking" name="facilities[]" value="parking" <?php echo in_array('parking', $facilities) ? 'checked' : ''; ?>>
                                    <label for="parking">
                                        <span>Parking</span>
                                        <span class="filter-count">2</span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="breakfast" name="facilities[]" value="breakfast" <?php echo in_array('breakfast', $facilities) ? 'checked' : ''; ?>>
                                    <label for="breakfast">
                                        <span>Breakfast Included</span>
                                        <span class="filter-count">1</span>
                                    </label>
                                </div>
                            </div>
                    </div>

                    <div class="filter-group">
                        <h4>Guest Rating</h4>
                            <div class="filter-options">
                                <div class="filter-option">
                                    <input type="checkbox" id="rating5" name="rating[]" value="5" <?php echo in_array('5', $ratings) ? 'checked' : ''; ?>>
                                    <label for="rating5">
                                        <span>⭐⭐⭐⭐⭐ 5 Stars</span>
                                        <span class="filter-count"><?php echo $rating_counts[5]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="rating4" name="rating[]" value="4" <?php echo in_array('4', $ratings) ? 'checked' : ''; ?>>
                                    <label for="rating4">
                                        <span>⭐⭐⭐⭐ 4 Stars</span>
                                        <span class="filter-count"><?php echo $rating_counts[4]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="rating3" name="rating[]" value="3" <?php echo in_array('3', $ratings) ? 'checked' : ''; ?>>
                                    <label for="rating3">
                                        <span>⭐⭐⭐ 3 Stars</span>
                                        <span class="filter-count"><?php echo $rating_counts[3]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="rating2" name="rating[]" value="2" <?php echo in_array('2', $ratings) ? 'checked' : ''; ?>>
                                    <label for="rating2">
                                        <span>⭐⭐ 2 Stars</span>
                                        <span class="filter-count"><?php echo $rating_counts[2]; ?></span>
                                    </label>
                                </div>
                                <div class="filter-option">
                                    <input type="checkbox" id="rating1" name="rating[]" value="1" <?php echo in_array('1', $ratings) ? 'checked' : ''; ?>>
                                    <label for="rating1">
                                        <span>⭐ 1 Star</span>
                                        <span class="filter-count"><?php echo $rating_counts[1]; ?></span>
                                    </label>
                                </div>
                            </div>
                    </div>
                        
                    <button type="submit">Apply Filters</button>
                </form>
            </div>
            <div class="filter-overlay"></div>

            <div class="results">
                <div class="sort-bar">
                    <label for="priceSort">Sort by:</label>
                    <select id="priceSort" onchange="sortHotels(this.value)">
                        <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating-high" <?php echo $sort === 'rating-high' ? 'selected' : ''; ?>>Rating: High to Low</option>
                        <option value="rating-low" <?php echo $sort === 'rating-low' ? 'selected' : ''; ?>>Rating: Low to High</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
                    <div id="hotelResults">
                <?php
                if (!empty($hotels)) {
                    foreach ($hotels as $hotel) {
                        $avg_rating = $hotel['avg_rating'] ? round($hotel['avg_rating'], 1) : 0;
                        $review_count = $hotel['review_count'] ?: 0;
                        
                        echo '<div class="hotel-result" data-price="' . $hotel['min_price'] . '" data-rating="' . $avg_rating . '" data-name="' . htmlspecialchars($hotel['name']) . '">';
                        echo '<img src="' . htmlspecialchars($hotel['image_url']) . '" alt="' . htmlspecialchars($hotel['name']) . '" loading="lazy">';
                        echo '<div class="hotel-info">';
                        echo '<h3>' . htmlspecialchars($hotel['name']) . '</h3>';
                        echo '<p class="location">' . htmlspecialchars($hotel['location']) . '</p>';
                        echo '<p class="city-country">' . htmlspecialchars($hotel['city']) . ', ' . htmlspecialchars($hotel['country']) . '</p>';
                        
                        if ($avg_rating > 0) {
                            echo '<div class="rating">';
                            echo '<div class="stars">';
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - 0.5 <= $avg_rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            echo '</div>';
                            echo '<span class="rating-text">' . $avg_rating . ' (' . $review_count . ' reviews)</span>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '<div class="hotel-price">';
                        echo '<div class="price">RM ' . number_format($hotel['min_price'], 2) . '<span class="per-night">/night</span></div>';
                        echo '<a href="view_details.php?hotel_id=' . htmlspecialchars($hotel['hotel_id']) . '&checkin=' . urlencode($checkin) . '&checkout=' . urlencode($checkout) . '"><button>View Details</button></a>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-results">';
                    echo '<i class="fas fa-search"></i>';
                    echo '<h3>No hotels found</h3>';
                    echo '<p>Try adjusting your search criteria or filters to find more options.</p>';
                    echo '</div>';
                }
                ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function sortHotels(sortOrder) {
        const hotelResults = document.getElementById('hotelResults');
        const hotels = Array.from(hotelResults.getElementsByClassName('hotel-result'));
        
        hotels.sort((a, b) => {
            switch (sortOrder) {
                case 'price-high':
                    return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                case 'price-low':
                    return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                case 'rating-high':
                    const ratingA = parseFloat(a.getAttribute('data-rating')) || 0;
                    const ratingB = parseFloat(b.getAttribute('data-rating')) || 0;
                    return ratingB - ratingA;
                case 'rating-low':
                    const ratingA2 = parseFloat(a.getAttribute('data-rating')) || 0;
                    const ratingB2 = parseFloat(b.getAttribute('data-rating')) || 0;
                    return ratingA2 - ratingB2;
                case 'name':
                    const nameA = a.getAttribute('data-name').toLowerCase();
                    const nameB = b.getAttribute('data-name').toLowerCase();
                    return nameA.localeCompare(nameB);
                default:
                    return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
            }
        });
        
        // Clear the container
        while (hotelResults.firstChild) {
            hotelResults.removeChild(hotelResults.firstChild);
        }
        
        // Add sorted hotels back
        hotels.forEach(hotel => hotelResults.appendChild(hotel));
    }

    // Mobile Filter Functionality
    const filterToggle = document.querySelector('.filter-toggle');
    const filters = document.querySelector('.filters');
    const closeFilters = document.querySelector('.close-filters');
    const filterOverlay = document.querySelector('.filter-overlay');

    filterToggle.addEventListener('click', () => {
        filters.classList.add('active');
        filterOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    function closeFilterPanel() {
        filters.classList.remove('active');
        filterOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    closeFilters.addEventListener('click', closeFilterPanel);
    filterOverlay.addEventListener('click', closeFilterPanel);

    // Handle filter panel on resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeFilterPanel();
        }
    });

    // Prevent touchmove on filter panel from scrolling body
    filters.addEventListener('touchmove', (e) => {
        e.stopPropagation();
    }, { passive: true });

    // Add loading state for form submissions
    const filterForm = document.querySelector('.filters form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying Filters...';
                submitBtn.disabled = true;
            }
        });
    }

    // Add smooth scrolling for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add keyboard navigation for accessibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeFilterPanel();
        }
    });
    </script>
</body>
</html>