<?php
// Include database connection
require_once 'db.php';

// Function to get hotel images
function getHotelImages($hotelType, $count = 1) {
    $images = array();
    
    switch($hotelType) {
        case 'luxury':
            $images = [
                'images/hotel/oceanview_1.jpg',
                'images/hotel/citylights_1.jpg',
                'images/hotel/sunset_paradise_1.jpg'
            ];
            break;
        case 'city':
            $images = [
                'images/hotel/budget_inn_1.jpg',
                'images/hotel/midtown_1.jpg',
                'images/hotel/star_hotel_1.jpg'
            ];
            break;
        default:
            $images = [
                'images/hotel/oceanview_1.jpg',
                'images/hotel/citylights_1.jpg',
                'images/hotel/sunset_paradise_1.jpg'
            ];
    }
    
    return array_slice($images, 0, $count);
}

// Fetch unique destinations from hotels table
$destinationsQuery = "SELECT DISTINCT city, country FROM hotels ORDER BY city";
$destinationsResult = $conn->query($destinationsQuery);
$destinations = array();

if ($destinationsResult->num_rows > 0) {
    while($row = $destinationsResult->fetch_assoc()) {
        $destinations[] = $row['city'] . ', ' . $row['country'];
    }
}

// Convert destinations array to JSON for JavaScript use
$destinationsJson = json_encode($destinations);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ered Hotel - Your Luxury Travel Companion</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            position: fixed;
            width: 100%;
            z-index: 1000;
            padding: 20px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        }

        .nav-menu a {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
            padding: 12px 24px;
            transition: all 0.3s ease;
            border-radius: 25px;
            position: relative;
        }

        .nav-menu a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.8) 0%, rgba(26, 26, 26, 0.6) 50%, rgba(45, 45, 45, 0.4) 100%),
                        url('images/hotel/oceanview_1.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            color: #ffffff;
            position: relative;
            padding: 80px 20px;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            width: 100%;
        }

        .hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(36px, 8vw, 64px);
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: clamp(16px, 3vw, 18px);
            margin-bottom: 40px;
            font-weight: 300;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Search Section */
        .search-container {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            padding: 40px;
            margin: 100px 0 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .search-tabs {
            display: flex;
            gap: 25px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            padding-bottom: 5px;
        }

        .search-tabs .tab {
            font-size: 15px;
            color: #cbd5e1;
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 10px 10px 0 0;
            font-weight: 500;
        }

        .search-tabs .tab.active {
            color: #0f172a;
            font-weight: 600;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .input-group {
            position: relative;
            flex: 1;
            min-width: 250px;
            z-index: 3;
            box-shadow: none;
            border: none;
        }

        .search-form {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: none;
        }

        .search-form input,
        .search-form select {
            width: 100%;
            padding: 16px 20px;
            font-size: 15px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            font-weight: 400;
            color: #f8fafc;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .search-form input:focus,
        .search-form select:focus {
            border-color: #ffd700;
            outline: none;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            background: rgba(30, 41, 59, 0.95);
        }

        .search-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            border: none;
            padding: 15px 40px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 140px;
            box-shadow: 0 4px 15px rgba(200, 169, 126, 0.2);
        }

        .search-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(200, 169, 126, 0.3);
        }

        /* Location Dropdown Specific Styles */
        .location-wrapper {
            position: relative;
            flex: 1;
            min-width: 250px;
            z-index: 100;
        }

        .location-wrapper input {
            padding-left: 45px;
            cursor: pointer;
        }

        .location-wrapper::before {
            content: '\f3c5';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #c8a97e;
            z-index: 2;
            pointer-events: none;
        }

        .suggestion-list {
            display: none;
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            width: 100%;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-top: 5px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 99;
            padding: 8px 0;
            scrollbar-width: thin;
            scrollbar-color: #c8a97e #f5f5f5;
        }

        .suggestion-list::-webkit-scrollbar {
            width: 6px;
        }

        .suggestion-list::-webkit-scrollbar-track {
            background: #f5f5f5;
            border-radius: 3px;
        }

        .suggestion-list::-webkit-scrollbar-thumb {
            background-color: #c8a97e;
            border-radius: 3px;
        }

        .suggestion-list.show {
            display: block;
            animation: fadeInDropdown 0.2s ease;
        }

        @keyframes fadeInDropdown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .suggestion-list li {
            padding: 12px 20px 12px 45px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            border-bottom: 1px solid #eee;
            position: relative;
            color: #333;
            display: flex;
            align-items: center;
            line-height: 1.4;
        }

        .suggestion-list li:last-child {
            border-bottom: none;
        }

        .suggestion-list li::before {
            content: '\f3c5';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 20px;
            color: #c8a97e;
            font-size: 14px;
        }

        .suggestion-list li:hover {
            background: rgba(200, 169, 126, 0.08);
            color: #c8a97e;
            padding-left: 50px;
        }

        /* Prevent dropdown from closing when clicking inside */
        .suggestion-list.show li:active {
            background: rgba(200, 169, 126, 0.15);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .location-wrapper {
                min-width: 100%;
            }

            .suggestion-list {
                position: absolute;
                max-height: 250px;
                border-radius: 8px;
                margin-top: 3px;
            }

            .suggestion-list li {
                padding: 10px 15px 10px 40px;
                font-size: 13px;
            }

            .suggestion-list li::before {
                left: 15px;
                font-size: 13px;
            }

            .suggestion-list li:hover {
                padding-left: 45px;
            }
        }

        /* Featured Hotels */
        .section {
            padding: 100px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .section-title p {
            color: #666;
            font-size: 16px;
        }

        /* Luxury Hotels Section */
        .hotels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .hotel-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .hotel-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .hotel-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block !important;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .hotel-info {
            padding: 25px;
        }

        .hotel-info h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .hotel-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .hotel-price {
            color: #c8a97e;
            font-weight: 600;
            font-size: 18px;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: #fff;
            padding: 80px 0 40px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
        }

        .footer-column h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            margin-bottom: 20px;
            color: #c8a97e;
        }

        .footer-column a {
            color: #fff;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .footer-column a:hover {
            opacity: 1;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .search-container {
                margin: 80px 0 40px;
                padding: 30px;
            }

            .search-form {
                flex-direction: column;
                gap: 15px;
            }

            .input-group {
                min-width: 100%;
            }

            .search-btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .hero {
                height: auto;
                min-height: 100vh;
                padding: 120px 20px 40px;
            }

            .hero-content {
                padding: 0 15px;
            }

            .search-container {
                margin: 60px 15px 30px;
                padding: 20px;
            }

            .search-tabs {
                gap: 15px;
                margin-bottom: 20px;
            }

            .search-tabs .tab {
                padding: 10px 15px;
                font-size: 14px;
            }

            .search-form input,
            .search-form select {
                padding: 12px 15px;
                font-size: 14px;
            }

            .search-btn {
                padding: 12px 30px;
                font-size: 14px;
            }

            .section {
                padding: 60px 0;
            }

            .destination-card {
                height: 300px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 100px 15px 30px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
                margin-bottom: 30px;
            }

            .search-container {
                padding: 15px;
            }

            .search-form input,
            .search-form select,
            .search-btn {
                padding: 12px;
                font-size: 14px;
            }
        }

        /* Date Input Specific Styles */
        .date-wrapper {
            position: relative;
            flex: 1;
            min-width: 200px;
            z-index: 98;
        }

        .date-wrapper input[type="date"] {
            width: 100%;
            padding: 14px 20px;
            font-size: 15px;
            border: 2px solid #eee;
            border-radius: 8px;
            background: #fff;
            transition: all 0.3s ease;
            font-weight: 400;
            color: #1a1a1a;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .date-wrapper input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            right: 0;
            top: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .date-wrapper::after {
            content: '\f073';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #c8a97e;
            pointer-events: none;
            font-size: 16px;
        }

        .date-wrapper input[type="date"]:focus {
            border-color: #c8a97e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.1);
        }

        /* Calendar Popup Styles */
        .flatpickr-calendar {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
            font-family: 'Montserrat', sans-serif;
            padding: 10px;
            width: 320px !important;
        }

        .flatpickr-months {
            padding: 5px 0;
            background: transparent;
            position: relative;
        }

        .flatpickr-month {
            background: transparent;
            color: #1a1a1a;
            fill: #1a1a1a;
            height: 40px;
            line-height: 1;
            position: relative;
            user-select: none;
        }

        .flatpickr-current-month {
            font-size: 16px;
            font-weight: 500;
            color: #1a1a1a;
            padding: 8px 0;
            position: relative;
            width: 100%;
        }

        .flatpickr-monthDropdown-months {
            font-weight: 500;
            color: #1a1a1a;
        }

        .flatpickr-weekdays {
            background: transparent;
            text-align: center;
            overflow: hidden;
            width: 100%;
            display: flex;
            align-items: center;
            height: 28px;
        }

        .flatpickr-weekday {
            background: transparent;
            color: #666;
            font-size: 12px;
            font-weight: 500;
            line-height: 1;
            margin: 0;
            text-align: center;
            display: block;
            flex: 1;
            width: 14.28571%;
        }

        .flatpickr-days {
            padding: 0;
            outline: 0;
            text-align: left;
            width: 100%;
            box-sizing: border-box;
            display: inline-block;
        }

        .dayContainer {
            padding: 0;
            outline: 0;
            text-align: left;
            width: 100%;
            min-width: 100%;
            max-width: 100%;
            display: inline-block;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            transform: translate3d(0px, 0px, 0px);
            opacity: 1;
        }

        .flatpickr-day {
            background: none;
            border: 1px solid transparent;
            border-radius: 4px;
            box-sizing: border-box;
            color: #333;
            cursor: pointer;
            font-weight: 400;
            width: 14.2857143%;
            flex-basis: 14.2857143%;
            max-width: 40px;
            height: 40px;
            line-height: 40px;
            margin: 0;
            display: inline-block;
            position: relative;
            justify-content: center;
            text-align: center;
            font-size: 14px;
        }

        .flatpickr-day:hover {
            background: rgba(200, 169, 126, 0.1);
            border-color: transparent;
        }

        .flatpickr-day.selected {
            background: #c8a97e;
            border-color: #c8a97e;
            color: #fff;
        }

        .flatpickr-day.today {
            border-color: #c8a97e;
            color: #c8a97e;
        }

        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: #ccc;
        }

        .flatpickr-prev-month,
        .flatpickr-next-month {
            color: #666 !important;
            fill: #666 !important;
            padding: 10px;
            position: absolute;
            top: 5px;
            height: 30px;
            width: 30px;
            text-decoration: none;
            cursor: pointer;
        }

        .flatpickr-prev-month {
            left: 10px;
        }

        .flatpickr-next-month {
            right: 10px;
        }

        .flatpickr-prev-month:hover,
        .flatpickr-next-month:hover {
            color: #c8a97e !important;
            fill: #c8a97e !important;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .flatpickr-calendar {
                width: 100% !important;
                max-width: 320px;
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
            }
        }


        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .search-container {
                margin: 80px 0 40px;
                padding: 30px;
            }

            .search-form {
                flex-direction: column;
                gap: 15px;
            }

            .input-group {
                min-width: 100%;
            }

            .search-btn {
                width: 100%;
            }

            .location-wrapper,
            .date-wrapper {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="homepage.php" class="logo">Ered Hotel</a>
                <nav class="nav-menu">
                    <a href="signin.php">Sign In/Sign up</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Discover Your Perfect Getaway</h1>
            <p>Experience luxury and comfort at the world's finest destinations</p>
            
            <!-- Search Form -->
            <div class="search-container">
                <form class="search-form" action="search.php" method="POST">
                    <div class="location-wrapper">
                        <input type="text" 
                               id="destination" 
                               name="destination" 
                               placeholder="Where to?" 
                               required 
                               autocomplete="off">
                        <ul id="suggestions" class="suggestion-list"></ul>
                    </div>
                    <div class="date-wrapper">
                        <input type="text" 
                               name="checkin" 
                               id="checkin"
                               placeholder="Check-in"
                               required 
                               readonly>
                    </div>
                    <div class="date-wrapper">
                        <input type="text" 
                               name="checkout" 
                               id="checkout"
                               placeholder="Check-out"
                               required 
                               readonly>
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Featured Hotels -->
    <section class="section" style="background-color: #f8f8f8;">
        <div class="container">
            <div class="section-title">
                <h2>Luxury Stays</h2>
                <p>Experience unparalleled comfort and elegance</p>
            </div>
            <div class="hotels-grid">
                <?php
                $sql = "
                    SELECT h.hotel_id, h.name, h.location, h.city, h.image_url, 
                           r.room_id, r.price_per_night
                    FROM hotels h
                    LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
                    GROUP BY h.hotel_id
                    LIMIT 3
                ";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $hotelType = 'luxury';
                        if ($row['price_per_night'] < 200) {
                            $hotelType = 'city';
                        }
                        
                        $hotelImages = getHotelImages($hotelType, 1);
                        $imageUrl = $hotelImages[0];
                        
                        echo '<div class="hotel-card">';
                        echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($row['name']) . '" onerror="this.src=\'images/hotel/default_hotel.jpg\'">';
                        echo '<div class="hotel-info">';
                        echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($row['location']) . ', ' . htmlspecialchars($row['city']) . '</p>';
                        echo '<div class="hotel-price">From RM ' . number_format($row['price_per_night'], 2) . ' / night</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                $conn->close();
                ?>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.style.background = '#ffffff';
                header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = 'none';
            }
        });

        // Location Autocomplete
        const destinationInput = document.getElementById('destination');
        const suggestionsList = document.getElementById('suggestions');
        const locationWrapper = document.querySelector('.location-wrapper');

        // Get destinations from PHP
        const destinations = <?php echo $destinationsJson; ?>;

        // Show all destinations on focus
        destinationInput.addEventListener('focus', function() {
            showSuggestions(destinations);
        });

        // Handle input changes
        destinationInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const filteredDestinations = destinations.filter(dest => 
                dest.toLowerCase().includes(query)
            );
            showSuggestions(filteredDestinations);
        });

        // Show suggestions function
        function showSuggestions(destinations) {
            suggestionsList.innerHTML = '';
            if (destinations.length > 0) {
                suggestionsList.classList.add('show');
                destinations.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    li.addEventListener('click', () => {
                        destinationInput.value = item;
                        suggestionsList.classList.remove('show');
                    });
                    suggestionsList.appendChild(li);
                });
            } else {
                suggestionsList.classList.remove('show');
            }
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!locationWrapper.contains(e.target)) {
                suggestionsList.classList.remove('show');
            }
        });

        // Date handling
        const checkinInput = document.getElementById('checkin');
        const checkoutInput = document.getElementById('checkout');

        // Set minimum dates
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        // Format dates for input min attribute
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        // Set initial min dates
        checkinInput.min = formatDate(today);
        checkoutInput.min = formatDate(tomorrow);

        // Update checkout min date when checkin changes
        checkinInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const nextDay = new Date(selectedDate);
            nextDay.setDate(selectedDate.getDate() + 1);
            checkoutInput.min = formatDate(nextDay);
            
            // If checkout date is before new checkin date, update it
            if (new Date(checkoutInput.value) <= selectedDate) {
                checkoutInput.value = formatDate(nextDay);
            }
        });

        // Prevent selecting past dates
        checkinInput.addEventListener('input', function() {
            if (new Date(this.value) < today) {
                this.value = formatDate(today);
            }
        });

        checkoutInput.addEventListener('input', function() {
            const minCheckout = new Date(checkinInput.value);
            minCheckout.setDate(minCheckout.getDate() + 1);
            if (new Date(this.value) < minCheckout) {
                this.value = formatDate(minCheckout);
            }
        });

        // Handle calendar popup
        function handleDateInput(input) {
            input.addEventListener('click', function(e) {
                e.stopPropagation();
                this.showPicker();
            });

            input.addEventListener('blur', function() {
                // Small delay to allow the calendar to close
                setTimeout(() => {
                    this.blur();
                }, 100);
            });
        }

        // Apply calendar popup handling to both date inputs
        handleDateInput(checkinInput);
        handleDateInput(checkoutInput);

        // Close calendar when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('input[type="date"]')) {
                checkinInput.blur();
                checkoutInput.blur();
            }
        });

        // Initialize date pickers
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize check-in date picker
            const checkinPicker = flatpickr("#checkin", {
                minDate: "today",
                dateFormat: "Y-m-d",
                disableMobile: true,
                onChange: function(selectedDates) {
                    if (selectedDates[0]) {
                        const nextDay = new Date(selectedDates[0]);
                        nextDay.setDate(nextDay.getDate() + 1);
                        checkoutPicker.set('minDate', nextDay);
                        
                        if (checkoutPicker.selectedDates[0] && checkoutPicker.selectedDates[0] <= selectedDates[0]) {
                            checkoutPicker.setDate(nextDay);
                        }
                    }
                }
            });

            // Initialize check-out date picker
            const checkoutPicker = flatpickr("#checkout", {
                minDate: new Date().fp_incr(1),
                dateFormat: "Y-m-d",
                disableMobile: true
            });

            // Close date pickers when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.flatpickr-calendar') && !event.target.matches('#checkin') && !event.target.matches('#checkout')) {
                    checkinPicker.close();
                    checkoutPicker.close();
                }
            });
        });
    </script>
</body>
</html>