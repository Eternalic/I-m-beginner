<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Fetch user data
$username = 'User'; // Default value
$profile_image = 'assets/images/default-profile.png'; // Default profile image

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, profile_img FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $username = htmlspecialchars($row['username']);
        if ($row['profile_img']) {
            $profile_image = htmlspecialchars($row['profile_img']);
        }
    }
    $stmt->close();
}

// Function to get cities with hotel counts and average prices
function getCitiesWithStats($conn) {
    $sql = "SELECT 
                h.city,
                h.country,
                COUNT(DISTINCT h.hotel_id) as hotel_count,
                ROUND(AVG(r.price_per_night), 2) as avg_price,
                GROUP_CONCAT(DISTINCT h.name) as hotel_names,
                GROUP_CONCAT(DISTINCT h.description) as hotel_descriptions,
                GROUP_CONCAT(DISTINCT h.location) as hotel_locations,
                GROUP_CONCAT(DISTINCT (
                    SELECT hi.hotel_image 
                    FROM hotel_img hi 
                    WHERE hi.hotel_id = h.hotel_id 
                    LIMIT 1
                )) as hotel_images
            FROM hotels h
            JOIN rooms r ON h.hotel_id = r.hotel_id
            GROUP BY h.city, h.country
            ORDER BY h.country, h.city";
    
    $result = $conn->query($sql);
    $cities = [];
    
    while ($row = $result->fetch_assoc()) {
        $cities[$row['country']][] = $row;
    }
    
    return $cities;
}

$cities = getCitiesWithStats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popular Cities - Ered Hotel</title>
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
            background-color: #f8f8f8;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            background: #fff;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-menu a {
            color: #666;
            text-decoration: none;
            margin-left: 30px;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-menu a:hover {
            color: #c8a97e;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .container {
            padding-top: 80px; /* Add padding to account for fixed header */
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .page-header p {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .country-section {
            margin-bottom: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .country-header {
            background: #1a1a1a;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .country-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
        }

        .cities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .city-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .city-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .city-info {
            padding: 20px;
        }

        .city-info h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .city-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #666;
            font-size: 14px;
        }

        .city-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hotel-list {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .hotel-item {
            margin-bottom: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            display: flex;
            gap: 15px;
        }

        .hotel-image {
            width: 120px;
            height: 80px;
            border-radius: 5px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .hotel-content {
            flex: 1;
        }

        .hotel-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .hotel-location {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .hotel-description {
            font-size: 13px;
            color: #555;
            line-height: 1.4;
        }

        .stats-badge {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            color: #666;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .stats-badge i {
            color: #c8a97e;
        }

        .city-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .city-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
        }

        .city-stats-container {
            display: flex;
            gap: 10px;
        }

        .search-link {
            display: inline-block;
            background: #c8a97e;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .search-link:hover {
            background: #b69468;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 36px;
            }

            .cities-grid {
                grid-template-columns: 1fr;
            }

            .country-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
            <nav class="nav-menu">
                <a href="SignedIn_homepage.php">Home</a>
                <a href="manage_bookings.php">Your Bookings</a>
                <a href="SignedIn_homepage.php?signout=true">Sign Out</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Popular Cities</h1>
            <p>Discover amazing hotels in our most popular destinations across Asia</p>
        </div>

        <?php foreach ($cities as $country => $countryCities): ?>
            <div class="country-section">
                <div class="country-header">
                    <h2><?php echo htmlspecialchars($country); ?></h2>
                </div>
                <div class="cities-grid">
                    <?php foreach ($countryCities as $city): ?>
                        <div class="city-card">
                            <div class="city-info">
                                <div class="city-header">
                                    <h3 class="city-title"><?php echo htmlspecialchars($city['city']); ?></h3>
                                    <div class="city-stats-container">
                                        <span class="stats-badge">
                                            <i class="fas fa-hotel"></i>
                                            <?php echo $city['hotel_count']; ?> hotels
                                        </span>
                                        <span class="stats-badge">
                                            <i class="fas fa-dollar-sign"></i>
                                            From RM <?php echo number_format($city['avg_price'], 2); ?>/night
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="hotel-list">
                                    <?php 
                                    $hotel_names = explode(',', $city['hotel_names']);
                                    $hotel_descriptions = explode(',', $city['hotel_descriptions']);
                                    $hotel_locations = explode(',', $city['hotel_locations']);
                                    $hotel_images = explode(',', $city['hotel_images']);
                                    
                                    for ($i = 0; $i < min(3, count($hotel_names)); $i++): 
                                    ?>
                                        <div class="hotel-item">
                                            <?php if (!empty($hotel_images[$i])): ?>
                                                <img src="<?php echo htmlspecialchars($hotel_images[$i]); ?>" 
                                                     alt="<?php echo htmlspecialchars($hotel_names[$i]); ?>" 
                                                     class="hotel-image">
                                            <?php endif; ?>
                                            <div class="hotel-content">
                                                <div class="hotel-name">
                                                    <?php echo htmlspecialchars($hotel_names[$i]); ?>
                                                </div>
                                                <div class="hotel-location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($hotel_locations[$i]); ?>
                                                </div>
                                                <div class="hotel-description">
                                                    <?php echo htmlspecialchars(substr($hotel_descriptions[$i], 0, 100)) . '...'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 