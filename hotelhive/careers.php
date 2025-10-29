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
    <title>Careers - Ered Hotel</title>
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
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1500&q=80');
            background-size: cover;
            background-position: center;
            height: 400px;
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
            padding: 60px 0;
        }

        /* Company Culture Section */
        .culture-section {
            margin-bottom: 60px;
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

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .value-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .value-icon {
            font-size: 40px;
            color: #c8a97e;
            margin-bottom: 20px;
        }

        .value-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .value-card p {
            color: #666;
            font-size: 15px;
        }

        /* Job Openings Section */
        .jobs-section {
            background: #f8f8f8;
            padding: 60px 0;
            margin: 60px 0;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .job-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .job-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .job-details {
            margin: 20px 0;
        }

        .job-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #666;
        }

        .job-detail i {
            color: #c8a97e;
        }

        .apply-btn {
            display: inline-block;
            background: #c8a97e;
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .apply-btn:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        /* Benefits Section */
        .benefits-section {
            margin-bottom: 60px;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .benefit-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .benefit-icon {
            font-size: 40px;
            color: #c8a97e;
            margin-bottom: 20px;
        }

        .benefit-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .benefit-card p {
            color: #666;
            font-size: 15px;
        }

        /* Contact Section */
        .contact-section {
            background: #f8f8f8;
            padding: 60px 0;
            text-align: center;
        }

        .contact-info {
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-info h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .contact-info p {
            color: #666;
            margin-bottom: 30px;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
        }

        .contact-detail {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-detail i {
            color: #c8a97e;
            font-size: 20px;
        }

        .contact-detail.combined {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-top: 0;
        }

        .contact-detail.combined > div {
            display: flex;
            align-items: center;
            gap: 10px;
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

            .values-grid,
            .jobs-grid,
            .benefits-grid {
                grid-template-columns: 1fr;
            }

            .contact-details {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Add notification bar styles */
        .notification-bar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #c8a97e;
            color: white;
            padding: 15px;
            text-align: center;
            z-index: 2000;
            animation: slideDown 0.3s ease-out;
        }

        .notification-bar.show {
            display: block;
        }

        .notification-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .notification-content i {
            font-size: 20px;
        }

        .notification-content p {
            margin: 0;
            font-size: 14px;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Add notification bar -->
    <div id="notificationBar" class="notification-bar">
        <div class="notification-content">
            <i class="fas fa-info-circle"></i>
            <p>Application for this position is currently unavailable. Stay tuned for updates!</p>
        </div>
    </div>

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
            <h1>Join Our Team</h1>
            <p>Be part of a dynamic team that's revolutionizing the hotel booking industry</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Company Culture Section -->
            <div class="culture-section">
                <div class="section-title">
                    <h2>Our Culture & Values</h2>
                    <p>At Ered Hotel, we believe in creating an environment where innovation thrives and every team member can reach their full potential.</p>
                </div>
                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Innovation</h3>
                        <p>We constantly push boundaries to create better solutions for our customers.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Collaboration</h3>
                        <p>We believe in the power of teamwork and open communication.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Customer Focus</h3>
                        <p>Our customers are at the heart of everything we do.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Growth</h3>
                        <p>We encourage continuous learning and personal development.</p>
                    </div>
                </div>
            </div>

            <!-- Job Openings Section -->
            <div class="jobs-section">
                <div class="section-title">
                    <h2>Current Openings</h2>
                    <p>Explore exciting career opportunities at Ered Hotel</p>
                </div>
                <div class="jobs-grid">
                    <div class="job-card">
                        <h3>Senior Software Engineer</h3>
                        <div class="job-details">
                            <div class="job-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Remote / Hybrid</span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-clock"></i>
                                <span>Full-time</span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-briefcase"></i>
                                <span>Engineering</span>
                            </div>
                        </div>
                        <a href="#" class="apply-btn" onclick="showNotification(); return false;">Apply Now</a>
                    </div>
                    <div class="job-card">
                        <h3>Customer Success Manager</h3>
                        <div class="job-details">
                            <div class="job-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Remote</span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-clock"></i>
                                <span>Full-time</span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-briefcase"></i>
                                <span>Customer Success</span>
                            </div>
                        </div>
                        <a href="#" class="apply-btn" onclick="showNotification(); return false;">Apply Now</a>
                    </div>
                    <div class="job-card">
                        <h3>Marketing Specialist</h3>
                        <div class="job-details">
                            <div class="job-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Remote / Hybrid</span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-clock"></i>
                                <span>Full-time</span>
                            </div>
                            <div class="job-detail">
                                <i class="fas fa-briefcase"></i>
                                <span>Marketing</span>
                            </div>
                        </div>
                        <a href="#" class="apply-btn" onclick="showNotification(); return false;">Apply Now</a>
                    </div>
                </div>
            </div>

            <!-- Benefits Section -->
            <div class="benefits-section">
                <div class="section-title">
                    <h2>Why Join Ered Hotel?</h2>
                    <p>We offer competitive benefits and opportunities for growth</p>
                </div>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>Competitive Salary</h3>
                        <p>We offer market-competitive compensation packages with regular reviews.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h3>Health Benefits</h3>
                        <p>Comprehensive health insurance coverage for you and your family.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Learning & Development</h3>
                        <p>Access to training programs and professional development opportunities.</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3>Work-Life Balance</h3>
                        <p>Flexible working hours and remote work options.</p>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="contact-section">
                <div class="container">
                    <div class="contact-info">
                        <h3>Get in Touch</h3>
                        <p>Have questions about our career opportunities? Our HR team is here to help.</p>
                        <div class="contact-details">
                            <div class="contact-detail">
                                <i class="fas fa-envelope"></i>
                                <span>careers@hotelhive.com</span>
                            </div>
                            <div class="contact-detail combined">
                                <div>
                                    <i class="fas fa-phone"></i>
                                    <span>(+60)11-11287021</span>
                                </div>
                                <div>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>123 Business Ave, Suite 100</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add notification function
        function showNotification() {
            const notificationBar = document.getElementById('notificationBar');
            notificationBar.classList.add('show');
            
            setTimeout(() => {
                notificationBar.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html> 