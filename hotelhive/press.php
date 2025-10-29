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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Press - Ered Hotel</title>
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
            background-color: #ffffff;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
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
            margin-top: 100px;
            padding: 40px 0;
        }

        .press-section {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 40px;
        }

        .press-section h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 30px;
            text-align: center;
        }

        .press-section h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin: 30px 0 15px;
        }

        .press-section p {
            margin-bottom: 15px;
            color: #666;
            font-size: 15px;
        }

        .press-section ul {
            margin: 15px 0;
            padding-left: 20px;
        }

        .press-section li {
            margin-bottom: 10px;
            color: #666;
            font-size: 15px;
        }

        .press-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .press-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .press-card:hover {
            transform: translateY(-5px);
        }

        .press-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .press-card-content {
            padding: 20px;
        }

        .press-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .press-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .press-card .date {
            font-size: 12px;
            color: #999;
        }

        .media-kit {
            background: #f8f8f8;
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
        }

        .media-kit h2 {
            margin-bottom: 20px;
        }

        .media-kit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .media-kit-item {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .media-kit-item:hover {
            transform: translateY(-5px);
        }

        .media-kit-item i {
            font-size: 24px;
            color: #c8a97e;
            margin-bottom: 10px;
        }

        .media-kit-item h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .media-kit-item p {
            font-size: 14px;
            color: #666;
        }

        .contact-section {
            background: #f8f8f8;
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
        }

        .contact-section h2 {
            margin-bottom: 20px;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-item {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .contact-item i {
            font-size: 24px;
            color: #c8a97e;
            margin-bottom: 10px;
        }

        .contact-item h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .contact-item p {
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-top: 80px;
                padding: 20px 0;
            }

            .press-section {
                padding: 20px;
            }

            .press-section h1 {
                font-size: 28px;
            }

            .press-section h2 {
                font-size: 20px;
            }

            .nav-menu {
                display: none;
            }

            .press-grid {
                grid-template-columns: 1fr;
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
                        <a href="manage_bookings.php">Hello, <?php echo htmlspecialchars($username); ?></a>
                    <?php else: ?>
                        <a href="signin.php">Sign In/Sign up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="press-section">
                <h1>Press Center</h1>
                
                <h2>About Ered Hotel</h2>
                <p>Ered Hotel is Malaysia's leading hotel booking platform, revolutionizing the way travelers find and book accommodations. Founded in 2024, we've grown to become the preferred choice for millions of travelers seeking quality hotel stays at competitive prices.</p>

                <h2>Latest News</h2>
                <div class="press-grid">
                    <div class="press-card">
                        <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Ered Hotel Expansion">
                        <div class="press-card-content">
                            <h3>Ered Hotel Expands to Southeast Asia</h3>
                            <p>We're excited to announce our expansion into key Southeast Asian markets, bringing our innovative hotel booking platform to more travelers.</p>
                            <div class="date">March 15, 2024</div>
                        </div>
                    </div>

                    <div class="press-card">
                        <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="New Partnership">
                        <div class="press-card-content">
                            <h3>Strategic Partnership with Leading Hotels</h3>
                            <p>Ered Hotel announces new partnerships with top hotel chains, offering exclusive deals to our customers.</p>
                            <div class="date">March 10, 2024</div>
                        </div>
                    </div>

                    <div class="press-card">
                        <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Mobile App Launch">
                        <div class="press-card-content">
                            <h3>New Mobile App Launch</h3>
                            <p>Ered Hotel launches its new mobile app, making hotel booking even more convenient for our users.</p>
                            <div class="date">March 5, 2024</div>
                        </div>
                    </div>
                </div>

                <div class="media-kit">
                    <h2>Media Kit</h2>
                    <div class="media-kit-grid">
                        <div class="media-kit-item">
                            <i class="fas fa-images"></i>
                            <h3>Brand Assets</h3>
                            <p>Download our logo, brand colors, and visual guidelines</p>
                        </div>
                        <div class="media-kit-item">
                            <i class="fas fa-file-alt"></i>
                            <h3>Fact Sheet</h3>
                            <p>Key information and statistics about Ered Hotel</p>
                        </div>
                        <div class="media-kit-item">
                            <i class="fas fa-camera"></i>
                            <h3>Press Photos</h3>
                            <p>High-resolution images for media use</p>
                        </div>
                        <div class="media-kit-item">
                            <i class="fas fa-video"></i>
                            <h3>Video Assets</h3>
                            <p>Brand videos and promotional content</p>
                        </div>
                    </div>
                </div>

                <div class="contact-section">
                    <h2>Press Contact</h2>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <h3>Email</h3>
                            <p>press@hotelhive.com</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <h3>Phone</h3>
                            <p>(+60) 11-11287021</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>Address</h3>
                            <p>123 Hotel Street, Hospitality City, HC 12345</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?> 