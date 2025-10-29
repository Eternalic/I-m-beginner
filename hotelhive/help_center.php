<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Ered Hotel</title>
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1486406146923-c4336f4c6b1a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1500&q=80');
            background-size: cover;
            background-position: center;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            margin-top: 70px;
        }

        .hero-content h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .hero-content p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }

        /* Help Categories */
        .help-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .category-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .category-icon {
            font-size: 40px;
            color: #c8a97e;
            margin-bottom: 20px;
        }

        .category-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .category-card p {
            color: #666;
            font-size: 15px;
            margin-bottom: 20px;
        }

        .category-card a {
            color: #c8a97e;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .category-card a:hover {
            color: #b69468;
        }

        /* FAQ Section */
        .faq-section {
            background: #f8f8f8;
            padding: 60px 0;
            margin: 60px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .section-title p {
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .faq-item {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .faq-item h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .faq-item p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Contact Support Section */
        .contact-section {
            text-align: center;
            margin-bottom: 60px;
        }

        .contact-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .contact-option {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .contact-icon {
            font-size: 40px;
            color: #c8a97e;
            margin-bottom: 20px;
        }

        .contact-option h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .contact-option p {
            color: #666;
            font-size: 15px;
            margin-bottom: 20px;
        }

        .contact-option a {
            display: inline-block;
            background: #c8a97e;
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .contact-option a:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        /* Responsive Design */
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

            .hero-content h1 {
                font-size: 36px;
            }

            .help-categories,
            .faq-grid,
            .contact-options {
                grid-template-columns: 1fr;
            }
        }

        /* Help Categories - Add these styles */
        .category-articles {
            display: none;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .category-articles.active {
            display: block;
        }

        .article-list {
            list-style: none;
        }

        .article-list-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .article-list-item:last-child {
            border-bottom: none;
        }

        .article-list-item a {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            text-decoration: none;
            color: #1a1a1a;
        }

        .article-list-item i {
            color: #c8a97e;
            margin-top: 4px;
        }

        .article-list-item:hover {
            background: rgba(200, 169, 126, 0.1);
            border-radius: 8px;
        }

        .article-content {
            flex: 1;
        }

        .article-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .article-description {
            font-size: 14px;
            color: #666;
        }

        .article-meta {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        /* Add these styles in the CSS section */
        .category-card a i {
            transition: transform 0.3s ease;
        }

        .category-card a.active i {
            transform: rotate(90deg);
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

    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h1>Help Center</h1>
            <p>Find answers to your questions and get the support you need</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Help Categories -->
            <div class="help-categories">
                <div class="category-card" id="booking-help">
                    <div class="category-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Booking Help</h3>
                    <p>Learn how to make, modify, or cancel your hotel bookings.</p>
                    <a href="javascript:void(0)" onclick="toggleArticles('bookingArticles', this)" class="toggle-link">
                        View Articles <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <div class="category-articles" id="bookingArticles">
                        <ul class="article-list">
                            <li class="article-list-item" data-article="how-to-book-a-hotel">
                                <a href="help_article.php?slug=how-to-book-a-hotel" onclick="console.log('Clicking article link:', this.href);">
                                    <i class="fas fa-hotel"></i>
                                    <div class="article-content">
                                        <div class="article-title">How to Book a Hotel</div>
                                        <div class="article-description">Learn how to book a hotel room through our platform</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="modifying-your-booking">
                                <a href="help_article.php?slug=modifying-your-booking">
                                    <i class="fas fa-edit"></i>
                                    <div class="article-content">
                                        <div class="article-title">Modifying Your Booking</div>
                                        <div class="article-description">How to modify your hotel booking details</div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="category-card" id="payment-help">
                    <div class="category-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Payment Help</h3>
                    <p>Information about payment methods, refunds, and billing.</p>
                    <a href="javascript:void(0)" onclick="toggleArticles('paymentArticles', this)" class="toggle-link">
                        View Articles <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <div class="category-articles" id="paymentArticles">
                        <ul class="article-list">
                            <li class="article-list-item" data-article="payment-methods">
                                <a href="help_article.php?slug=payment-methods">
                                    <i class="fas fa-money-bill"></i>
                                    <div class="article-content">
                                        <div class="article-title">Payment Methods</div>
                                        <div class="article-description">Learn about accepted payment methods</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="understanding-refunds">
                                <a href="help_article.php?slug=understanding-refunds">
                                    <i class="fas fa-undo"></i>
                                    <div class="article-content">
                                        <div class="article-title">Understanding Refunds</div>
                                        <div class="article-description">Information about our refund process</div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="category-card" id="account-help">
                    <div class="category-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3>Account Help</h3>
                    <p>Manage your account settings and preferences.</p>
                    <a href="javascript:void(0)" onclick="toggleArticles('accountArticles', this)" class="toggle-link">
                        View Articles <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <div class="category-articles" id="accountArticles">
                        <ul class="article-list">
                            <li class="article-list-item" data-article="managing-your-account">
                                <a href="help_article.php?slug=managing-your-account">
                                    <i class="fas fa-cog"></i>
                                    <div class="article-content">
                                        <div class="article-title">Managing Your Account</div>
                                        <div class="article-description">Guide to managing your account</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="password-reset">
                                <a href="help_article.php?slug=password-reset">
                                    <i class="fas fa-key"></i>
                                    <div class="article-content">
                                        <div class="article-title">Password Reset</div>
                                        <div class="article-description">Steps to reset your password</div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="category-card" id="troubleshooting">
                    <div class="category-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <h3>Troubleshooting</h3>
                    <p>Find solutions to common technical issues and problems.</p>
                    <a href="javascript:void(0)" onclick="toggleArticles('troubleshootingArticles', this)" class="toggle-link">
                        View Articles <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    <div class="category-articles" id="troubleshootingArticles">
                        <ul class="article-list">
                            <li class="article-list-item" data-article="website-loading-issues">
                                <a href="help_article.php?slug=website-loading-issues">
                                    <i class="fas fa-globe"></i>
                                    <div class="article-content">
                                        <div class="article-title">Website Loading Issues</div>
                                        <div class="article-description">Solutions for website loading and performance issues</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="payment-processing-errors">
                                <a href="help_article.php?slug=payment-processing-errors">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <div class="article-content">
                                        <div class="article-title">Payment Processing Errors</div>
                                        <div class="article-description">Troubleshoot common payment processing errors</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="booking-confirmation-issues">
                                <a href="help_article.php?slug=booking-confirmation-issues">
                                    <i class="fas fa-envelope"></i>
                                    <div class="article-content">
                                        <div class="article-title">Booking Confirmation Issues</div>
                                        <div class="article-description">Solutions for booking confirmation problems</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="mobile-app-troubleshooting">
                                <a href="help_article.php?slug=mobile-app-troubleshooting">
                                    <i class="fas fa-mobile-alt"></i>
                                    <div class="article-content">
                                        <div class="article-title">Mobile App Troubleshooting</div>
                                        <div class="article-description">Troubleshooting guide for mobile app issues</div>
                                    </div>
                                </a>
                            </li>
                            <li class="article-list-item" data-article="account-access-problems">
                                <a href="help_article.php?slug=account-access-problems">
                                    <i class="fas fa-user-lock"></i>
                                    <div class="article-content">
                                        <div class="article-title">Account Access Problems</div>
                                        <div class="article-description">Solutions for account access and login issues</div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <div class="section-title">
                    <h2>Frequently Asked Questions</h2>
                    <p>Find quick answers to common questions about Ered Hotel</p>
                </div>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h4>How do I modify my booking?</h4>
                        <p>You can modify your booking through your account dashboard or by contacting our customer service team. Changes are subject to availability and may incur additional charges.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What is your cancellation policy?</h4>
                        <p>Most bookings can be cancelled free of charge up to 24 hours before check-in. Some special rates may have different policies. Check your booking confirmation for details.</p>
                    </div>

                    <div class="faq-item">
                        <h4>How do I contact customer support?</h4>
                        <p>You can reach our customer support team 24/7 through live chat, email, or phone. Visit our contact page for all support options.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What payment methods do you accept?</h4>
                        <p>We accept all major credit cards, PayPal, and bank transfers. Some hotels may have additional payment options available.</p>
                    </div>
                </div>
            </div>

            <!-- Contact Support Section -->
            <div class="contact-section">
                <div class="section-title">
                    <h2>Need More Help?</h2>
                    <p>Our support team is here to assist you with any questions or concerns</p>
                </div>
                <div class="contact-options">
                    <div class="contact-option">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Live Chat</h3>
                        <p>Get instant help from our support team</p>
                        <a href="chat.php">Start Chat</a>
                    </div>

                    <div class="contact-option">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Support</h3>
                        <p>Send us your questions via email</p>
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=cjw12033@gmail.com" target="_blank">Email Us</a>
                    </div>

                    <div class="contact-option">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Phone Support</h3>
                        <p>Call us for immediate assistance</p>
                        <a href="tel:+601111287021">+(60)1111287021</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleArticles(articleId, linkElement) {
            const articlesDiv = document.getElementById(articleId);
            const allArticles = document.querySelectorAll('.category-articles');
            const allLinks = document.querySelectorAll('.toggle-link');
            
            allArticles.forEach(article => {
                if (article.id !== articleId && article.classList.contains('active')) {
                    article.classList.remove('active');
                }
            });
            
            allLinks.forEach(link => {
                if (link !== linkElement && link.classList.contains('active')) {
                    link.classList.remove('active');
                }
            });
            
            articlesDiv.classList.toggle('active');
            linkElement.classList.toggle('active');
            
            if (articlesDiv.classList.contains('active')) {
                setTimeout(() => {
                    articlesDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }

        function toggleTroubleshootingArticles() {
            const link = document.querySelector('#troubleshooting .toggle-link');
            toggleArticles('troubleshootingArticles', link);
        }
    </script>
</body>
</html>