<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Get article slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: help_center.php");
    exit;
}

try {
    // Get article details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            c.name as category_name,
            c.slug as category_slug,
            c.icon as category_icon
        FROM help_articles a
        JOIN help_categories c ON a.category_id = c.id
        WHERE a.slug = ?
    ");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: help_center.php");
        exit;
    }
    
    $article = $result->fetch_assoc();
    
    // Update view count
    $stmt = $conn->prepare("UPDATE help_articles SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $article['id']);
    $stmt->execute();
    
} catch (Exception $e) {
    header("Location: help_center.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Ered Hotel Help Center</title>
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
            line-height: 1.8;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
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
            transform: translateY(-2px);
        }

        /* Article Content Styles */
        .article-content {
            max-width: 800px;
            margin: 100px auto 60px;
            padding: 0 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #c8a97e;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            color: #b69468;
            transform: translateX(-5px);
        }

        .back-button i {
            font-size: 12px;
        }

        .article-header {
            margin-bottom: 40px;
        }

        .article-category {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #c8a97e;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .article-category i {
            font-size: 16px;
        }

        .article-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .article-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }

        .article-body {
            font-size: 16px;
            line-height: 1.8;
            color: #333;
            margin-bottom: 30px;
        }

        .article-body p {
            margin-bottom: 20px;
        }

        .article-description {
            font-size: 16px;
            color: #555;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 10px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .article-feedback {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .feedback-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .feedback-buttons {
            display: flex;
            gap: 15px;
        }

        .feedback-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 2px solid #eee;
            border-radius: 25px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .feedback-button:hover {
            border-color: #c8a97e;
            color: #c8a97e;
        }

        .feedback-button.active {
            color: #fff;
        }

        .feedback-button.active.like {
            background: #4CAF50;
            border-color: #4CAF50;
        }

        .feedback-button.active.dislike {
            background: #f44336;
            border-color: #f44336;
        }

        .feedback-button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .feedback-message {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            display: none;
        }

        .feedback-message.show {
            display: block;
        }

        .related-articles {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 1px solid #eee;
        }

        .related-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 30px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .related-item {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .related-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .related-item h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .related-item p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .related-item a {
            color: #c8a97e;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .related-item a:hover {
            color: #b69468;
        }

        /* Footer Styles */
        .footer {
            background: #1a1a1a;
            color: #fff;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 40px;
        }

        .footer-column h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 700;
            color: #c8a97e;
        }

        .footer-column a {
            color: #999;
            text-decoration: none;
            display: block;
            margin: 10px 0;
            transition: all 0.3s ease;
        }

        .footer-column a:hover {
            color: #c8a97e;
            transform: translateX(5px);
        }

        .footer p {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .article-content {
                margin-top: 80px;
            }

            .article-title {
                font-size: 28px;
            }

            .article-meta {
                flex-direction: column;
                gap: 10px;
            }

            .feedback-buttons {
                flex-direction: column;
            }

            .feedback-button {
                width: 100%;
                justify-content: center;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="header-top">
                <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
                <div class="nav-menu">
                    <a href="SignedIn_homepage.php">Home</a>
                    <a href="manage_bookings.php">Your Bookings</a>
                    <a href="SignedIn_homepage.php?signout=true">Sign Out</a>
                </div>
            </div>
        </div>
    </div>

    <div class="article-content">
        <a href="help_center.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Help Center
        </a>
        <div class="article-header">
            <div class="article-category">
                <i class="fas <?php echo htmlspecialchars($article['category_icon']); ?>"></i>
                <?php echo htmlspecialchars($article['category_name']); ?>
            </div>
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="article-meta">
                <span>Last updated: <?php echo date('F j, Y', strtotime($article['updated_at'])); ?></span>
                <span>Views: <?php echo number_format($article['views']); ?></span>
            </div>
        </div>

        <div class="article-body">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>

        <div class="article-description">
            <?php echo htmlspecialchars($article['description']); ?>
        </div>

        <?php
        // Get related articles
        $stmt = $conn->prepare("
            SELECT 
                a.title,
                a.slug,
                a.content
            FROM help_articles a
            WHERE 
                a.category_id = ? AND 
                a.id != ?
            LIMIT 3
        ");
        $stmt->bind_param("ii", $article['category_id'], $article['id']);
        $stmt->execute();
        $related = $stmt->get_result();
        
        if ($related->num_rows > 0):
        ?>
        <div class="related-articles">
            <h2 class="related-title">Related Articles</h2>
            <div class="related-grid">
                <?php while ($relatedArticle = $related->fetch_assoc()): ?>
                <div class="related-item">
                    <h3><?php echo htmlspecialchars($relatedArticle['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars(substr($relatedArticle['content'], 0, 150))); ?>...</p>
                    <a href="help_article.php?slug=<?php echo htmlspecialchars($relatedArticle['slug']); ?>">
                        Read More <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>About Ered Hotel</h4>
                    <a href="about_us.php">About Us</a>
                    <a href="how_it_works.php">How it Works</a>
                    <a href="careers.php">Careers</a>
                    <a href="#">Press</a>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <a href="help_center.php">Help Center</a>
                    <a href="chat.php">Contact Us</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                <div class="footer-column">
                    <h4>Destinations</h4>
                    <a href="#">Popular Cities</a>
                    <a href="#">Travel Guides</a>
                    <a href="#">Featured Hotels</a>
                    <a href="#">Deals & Offers</a>
                </div>
                <div class="footer-column">
                    <h4>Follow Us</h4>
                    <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a>
                    <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                    <a href="#"><i class="fab fa-linkedin-in"></i> LinkedIn</a>
                </div>
            </div>
            <p>© 2024 Ered Hotel. All rights reserved.</p>
        </div>
    </div>
</body>
</html>