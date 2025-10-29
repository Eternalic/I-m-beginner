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
    <title>How It Works - Ered Hotel</title>
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

        /* Main Content Styles */
        .main-content {
            margin-top: 100px;
            padding: 40px 0;
        }

        .page-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .page-title p {
            font-size: 18px;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Process Steps */
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .step-card {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .step-number {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 48px;
            font-weight: 700;
            color: rgba(200, 169, 126, 0.2);
            font-family: 'Cormorant Garamond', serif;
        }

        .step-icon {
            font-size: 40px;
            color: #c8a97e;
            margin-bottom: 20px;
        }

        .step-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .step-card p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Process Flow */
        .process-flow {
            background: #f8f8f8;
            padding: 60px 0;
            margin: 60px 0;
        }

        .flow-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .flow-title h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .flow-steps {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .flow-step {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .flow-step-icon {
            width: 50px;
            height: 50px;
            background: #c8a97e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
        }

        .flow-step-content {
            flex: 1;
        }

        .flow-step-content h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .flow-step-content p {
            color: #666;
            font-size: 15px;
        }

        /* FAQ Section */
        .faq-section {
            margin: 60px 0;
        }

        .faq-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .faq-title h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .faq-item {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
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

            .page-title h1 {
                font-size: 36px;
            }

            .process-steps {
                grid-template-columns: 1fr;
            }

            .flow-step {
                flex-direction: column;
                text-align: center;
            }

            .flow-step-icon {
                margin: 0 auto;
            }

            .faq-grid {
                grid-template-columns: 1fr;
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-title">
                <h1>How It Works</h1>
                <p>Discover the simple process of booking your perfect stay with Ered Hotel</p>
            </div>

            <!-- Process Steps -->
            <div class="process-steps">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Search Hotels</h3>
                    <p>Enter your destination, dates, and number of guests to find the perfect hotel for your stay.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3>Compare Options</h3>
                    <p>Browse through various hotels, compare prices, amenities, and read guest reviews to make an informed decision.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Book & Pay</h3>
                    <p>Select your preferred room, enter payment details, and confirm your booking securely.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">04</div>
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Enjoy Your Stay</h3>
                    <p>Receive your booking confirmation and enjoy a hassle-free hotel experience.</p>
                </div>
            </div>

            <!-- Process Flow -->
            <div class="process-flow">
                <div class="flow-title">
                    <h2>Detailed Booking Process</h2>
                </div>
                <div class="flow-steps">
                    <div class="flow-step">
                        <div class="flow-step-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="flow-step-content">
                            <h4>Choose Your Destination</h4>
                            <p>Enter your desired location or browse through popular destinations to find your perfect getaway spot.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="flow-step-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="flow-step-content">
                            <h4>Select Dates</h4>
                            <p>Pick your check-in and check-out dates using our easy-to-use calendar interface.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="flow-step-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="flow-step-content">
                            <h4>Specify Guests</h4>
                            <p>Indicate the number of adults and children staying to find rooms that accommodate your group.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="flow-step-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="flow-step-content">
                            <h4>Choose Your Room</h4>
                            <p>Browse through available room types, compare amenities, and select the perfect accommodation for your needs.</p>
                        </div>
                    </div>

                    <div class="flow-step">
                        <div class="flow-step-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="flow-step-content">
                            <h4>Secure Payment</h4>
                            <p>Enter your payment details securely and complete your booking with our encrypted payment system.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <div class="faq-title">
                    <h2>Frequently Asked Questions</h2>
                </div>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h4>What payment methods do you accept?</h4>
                        <p>We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What is your cancellation policy?</h4>
                        <p>Most bookings can be cancelled free of charge up to 24 hours before check-in. Some special rates may have different policies.</p>
                    </div>

                    <div class="faq-item">
                        <h4>How do I modify my booking?</h4>
                        <p>You can modify your booking through your account dashboard or by contacting our customer service team.</p>
                    </div>

                    <div class="faq-item">
                        <h4>Is my payment information secure?</h4>
                        <p>Yes, we use industry-standard encryption to protect your payment information and personal data.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 