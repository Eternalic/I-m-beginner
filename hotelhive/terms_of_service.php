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
    <title>Terms of Service - Ered Hotel</title>
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

        .terms-of-service {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 40px;
        }

        .terms-of-service h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 30px;
            text-align: center;
        }

        .terms-of-service h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin: 30px 0 15px;
        }

        .terms-of-service p {
            margin-bottom: 15px;
            color: #666;
            font-size: 15px;
        }

        .terms-of-service ul {
            margin: 15px 0;
            padding-left: 20px;
        }

        .terms-of-service li {
            margin-bottom: 10px;
            color: #666;
            font-size: 15px;
        }

        .last-updated {
            text-align: center;
            color: #999;
            font-size: 14px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-top: 80px;
                padding: 20px 0;
            }

            .terms-of-service {
                padding: 20px;
            }

            .terms-of-service h1 {
                font-size: 28px;
            }

            .terms-of-service h2 {
                font-size: 20px;
            }

            .nav-menu {
                display: none;
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
            <div class="terms-of-service">
                <h1>Terms of Service</h1>
                
                <p>Last updated: <?php echo date('F j, Y'); ?></p>

                <h2>1. Agreement to Terms</h2>
                <p>By accessing or using Ered Hotel's services, you agree to be bound by these Terms of Service. If you disagree with any part of these terms, you may not access our services.</p>

                <h2>2. Definitions</h2>
                <p>For the purposes of these Terms of Service:</p>
                <ul>
                    <li>"Service" refers to the Ered Hotel website and all related services</li>
                    <li>"User," "you," and "your" refer to individuals accessing or using our Service</li>
                    <li>"Company," "we," "us," and "our" refer to Ered Hotel</li>
                    <li>"Content" refers to all information and materials available on our Service</li>
                    <li>"Booking" refers to a reservation made through our Service</li>
                </ul>

                <h2>3. User Accounts</h2>
                <p>When you create an account with us, you must provide accurate, complete, and current information. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account.</p>
                <p>You are responsible for safeguarding your account credentials and for any activities or actions under your account.</p>

                <h2>4. Booking and Payment Terms</h2>
                <p>Our booking and payment terms include:</p>
                <ul>
                    <li>All prices are subject to change without notice</li>
                    <li>Payment must be made in full at the time of booking</li>
                    <li>Prices include applicable taxes and fees</li>
                    <li>Special rates and promotions are subject to specific terms</li>
                    <li>We reserve the right to refuse service to anyone</li>
                    <li>Bookings are subject to hotel availability</li>
                </ul>

                <h2>5. Cancellation and Refund Policy</h2>
                <p>Our cancellation and refund policies are as follows:</p>
                <ul>
                    <li>Cancellations must be made at least 24 hours before check-in</li>
                    <li>Refunds are processed within 5-7 business days</li>
                    <li>Non-refundable bookings are clearly marked</li>
                    <li>Partial refunds may be available for early checkouts</li>
                    <li>No-shows are charged the full amount</li>
                </ul>

                <h2>6. Intellectual Property</h2>
                <p>The Service and its original content, features, and functionality are owned by Ered Hotel and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.</p>

                <h2>7. User Content</h2>
                <p>By posting content on our Service, you grant us the right to use, modify, publicly perform, publicly display, reproduce, and distribute such content.</p>
                <p>You agree not to post content that:</p>
                <ul>
                    <li>Is illegal or promotes illegal activities</li>
                    <li>Contains hate speech or discriminatory content</li>
                    <li>Infringes on intellectual property rights</li>
                    <li>Contains false or misleading information</li>
                    <li>Contains malware or harmful code</li>
                </ul>

                <h2>8. Prohibited Activities</h2>
                <p>Users are prohibited from:</p>
                <ul>
                    <li>Using the Service for any illegal purpose</li>
                    <li>Attempting to access unauthorized areas of the Service</li>
                    <li>Interfering with or disrupting the Service</li>
                    <li>Using automated systems to access the Service</li>
                    <li>Sharing account credentials with others</li>
                    <li>Making false or fraudulent bookings</li>
                    <li>Harassing or abusing other users</li>
                </ul>

                <h2>9. Limitation of Liability</h2>
                <p>Ered Hotel shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the Service.</p>

                <h2>10. Disclaimer of Warranties</h2>
                <p>The Service is provided "as is" and "as available" without any warranties of any kind, either express or implied.</p>

                <h2>11. Indemnification</h2>
                <p>You agree to defend, indemnify, and hold harmless Ered Hotel from any claims, damages, losses, liabilities, and expenses arising from your use of the Service.</p>

                <h2>12. Changes to Terms</h2>
                <p>We reserve the right to modify or replace these Terms at any time. We will provide notice of any significant changes via email or through the Service.</p>

                <h2>13. Governing Law</h2>
                <p>These Terms shall be governed by and construed in accordance with the laws of Malaysia, without regard to its conflict of law provisions.</p>

                <h2>14. Contact Information</h2>
                <p>If you have any questions about these Terms, please contact us at:</p>
                <ul>
                    <li>Email: legal@hotelhive.com</li>
                    <li>Phone: (+60) 11-11287021</li>
                    <li>Address: 123 Hotel Street, Hospitality City, HC 12345</li>
                </ul>

                <div class="last-updated">
                    Last updated: <?php echo date('F j, Y'); ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?> 