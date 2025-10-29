<?php
require_once 'db.php';

// Function to get travel guides with hotel counts and average prices
function getTravelGuides($conn) {
    $query = "SELECT 
                h.country,
                COUNT(DISTINCT h.hotel_id) as hotel_count,
                AVG(r.price_per_night) as avg_price,
                GROUP_CONCAT(DISTINCT h.city) as cities,
                GROUP_CONCAT(DISTINCT h.description) as descriptions,
                GROUP_CONCAT(DISTINCT h.location) as locations,
                GROUP_CONCAT(DISTINCT (
                    SELECT hi.hotel_image 
                    FROM hotel_img hi 
                    WHERE hi.hotel_id = h.hotel_id 
                    LIMIT 1
                )) as hotel_images,
                CASE h.country
                    WHEN 'Malaysia' THEN 'images/country/malaysia.jpg'
                    WHEN 'Japan' THEN 'images/country/japan.jpg'
                    WHEN 'Korea' THEN 'images/country/korea.jpg'
                    WHEN 'Singapore' THEN 'images/country/singapore.jpg'
                    WHEN 'Thailand' THEN 'images/country/thailand.jpg'
                    ELSE 'images/country/default.jpg'
                END as country_image
              FROM hotels h
              LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
              GROUP BY h.country
              ORDER BY hotel_count DESC";
    
    $result = $conn->query($query);
    $guides = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $guides[] = $row;
        }
    }
    
    return $guides;
}

$guides = getTravelGuides($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Guides - Ered Hotel</title>
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

        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 30px;
            text-align: center;
        }

        .guides-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .guide-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .guide-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #eee;
        }

        .guide-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .country-flag {
            width: 100px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .guide-content {
            padding: 25px;
        }

        .guide-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .guide-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .stat-item i {
            color: #c8a97e;
        }

        .guide-description {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .guide-cities {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .cities-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .cities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .city-tag {
            background: #f8f8f8;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #666;
            transition: all 0.3s ease;
        }

        .city-tag:hover {
            background: #c8a97e;
            color: #fff;
        }

        .hotel-preview {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .hotel-preview-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .hotel-preview-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .hotel-preview-image {
            width: 80px;
            height: 60px;
            border-radius: 5px;
            object-fit: cover;
        }

        .hotel-preview-content {
            flex: 1;
        }

        .hotel-preview-name {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 5px;
        }

        .hotel-preview-location {
            font-size: 12px;
            color: #666;
        }

        .search-btn {
            display: inline-block;
            background: #c8a97e;
            color: #fff;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .search-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .guides-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                width: 100%;
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 15px;
            }

            .nav-menu a {
                margin: 0;
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
        <h1 class="page-title">Travel Guides</h1>
        <div class="guides-grid">
            <?php foreach ($guides as $guide): 
                $cities = explode(',', $guide['cities']);
                $cities = array_unique($cities);
                $cities = array_slice($cities, 0, 5); // Show only first 5 cities
                
                $descriptions = explode(',', $guide['descriptions']);
                $locations = explode(',', $guide['locations']);
                $hotel_images = explode(',', $guide['hotel_images']);
            ?>
            <div class="guide-card">
                <div class="guide-image">
                    <?php if (!empty($guide['country_image'])): ?>
                        <img src="<?php echo htmlspecialchars($guide['country_image']); ?>" 
                             alt="<?php echo htmlspecialchars($guide['country']); ?> Flag">
                    <?php else: ?>
                        <img src="images/country/default.jpg" alt="Default Flag">
                    <?php endif; ?>
                </div>
                <div class="guide-content">
                    <h2 class="guide-title"><?php echo htmlspecialchars($guide['country']); ?></h2>
                    <div class="guide-stats">
                        <div class="stat-item">
                            <i class="fas fa-hotel"></i>
                            <?php echo $guide['hotel_count']; ?> Hotels
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-dollar-sign"></i>
                            From RM <?php echo number_format($guide['avg_price'], 2); ?>/night
                        </div>
                    </div>
                    <?php if (!empty($descriptions[0])): ?>
                        <div class="guide-description">
                            <?php echo htmlspecialchars(substr($descriptions[0], 0, 150)) . '...'; ?>
                        </div>
                    <?php endif; ?>
                    <div class="guide-cities">
                        <div class="cities-title">Popular Cities:</div>
                        <div class="cities-list">
                            <?php foreach ($cities as $city): ?>
                                <span class="city-tag">
                                    <?php echo htmlspecialchars($city); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="hotel-preview">
                        <div class="hotel-preview-title">Featured Hotels:</div>
                        <?php for ($i = 0; $i < min(2, count($hotel_images)); $i++): ?>
                            <div class="hotel-preview-item">
                                <?php if (!empty($hotel_images[$i])): ?>
                                    <img src="<?php echo htmlspecialchars($hotel_images[$i]); ?>" 
                                         alt="Hotel Preview" 
                                         class="hotel-preview-image">
                                <?php endif; ?>
                                <div class="hotel-preview-content">
                                    <div class="hotel-preview-name">
                                        <?php echo htmlspecialchars($descriptions[$i]); ?>
                                    </div>
                                    <div class="hotel-preview-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($locations[$i]); ?>
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
</body>
</html> 