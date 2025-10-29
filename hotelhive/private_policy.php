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
    <title>Privacy Policy - Ered Hotel</title>
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

        .privacy-policy {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 40px;
        }

        .privacy-policy h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 30px;
            text-align: center;
        }

        .privacy-policy h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            color: #1a1a1a;
            margin: 30px 0 15px;
        }

        .privacy-policy p {
            margin-bottom: 15px;
            color: #666;
            font-size: 15px;
        }

        .privacy-policy ul {
            margin: 15px 0;
            padding-left: 20px;
        }

        .privacy-policy li {
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

            .privacy-policy {
                padding: 20px;
            }

            .privacy-policy h1 {
                font-size: 28px;
            }

            .privacy-policy h2 {
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
            <div class="privacy-policy">
                <h1>Privacy Policy</h1>
                
                <p>Last updated: <?php echo date('F j, Y'); ?></p>

                <h2>1. Introduction</h2>
                <p>Welcome to Ered Hotel. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we handle your personal information when you visit our website and tell you about your privacy rights and how the law protects you.</p>
                <p>Ered Hotel is committed to ensuring that your privacy is protected. Should we ask you to provide certain information by which you can be identified when using this website, then you can be assured that it will only be used in accordance with this privacy statement.</p>

                <h2>2. Information We Collect</h2>
                <p>We collect several types of information for various purposes to provide and improve our service to you:</p>
                <ul>
                    <li>Personal identification information (Name, email address, phone number, etc.)</li>
                    <li>Booking information (Check-in/out dates, room preferences, etc.)</li>
                    <li>Payment information (Credit card details, billing address, etc.)</li>
                    <li>Usage data (Browser type, device information, etc.)</li>
                    <li>Location data (IP address, GPS coordinates, etc.)</li>
                    <li>Communication preferences</li>
                    <li>Travel preferences and history</li>
                    <li>Reviews and ratings you provide</li>
                    <li>Social media information (if connected)</li>
                    <li>Device and usage information</li>
                </ul>

                <h2>3. How We Use Your Information</h2>
                <p>We use the collected data for various purposes:</p>
                <ul>
                    <li>To provide and maintain our service</li>
                    <li>To notify you about changes to our service</li>
                    <li>To provide customer support</li>
                    <li>To gather analysis or valuable information so that we can improve our service</li>
                    <li>To process your bookings and payments</li>
                    <li>To send you marketing communications (with your consent)</li>
                    <li>To personalize your experience</li>
                    <li>To improve our website and services</li>
                    <li>To comply with legal obligations</li>
                    <li>To prevent fraud and enhance security</li>
                </ul>

                <h2>4. Data Security</h2>
                <p>The security of your data is important to us. We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction. Our security measures include:</p>
                <ul>
                    <li>Encryption of sensitive data</li>
                    <li>Regular security assessments</li>
                    <li>Secure data storage systems</li>
                    <li>Access controls and authentication</li>
                    <li>Regular security training for employees</li>
                    <li>Secure data transmission protocols</li>
                    <li>Regular backups and recovery procedures</li>
                </ul>

                <h2>5. Your Rights</h2>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal data</li>
                    <li>Correct inaccurate data</li>
                    <li>Request deletion of your data</li>
                    <li>Object to data processing</li>
                    <li>Data portability</li>
                    <li>Withdraw consent at any time</li>
                    <li>Request restriction of processing</li>
                    <li>Lodge a complaint with supervisory authorities</li>
                    <li>Request information about third-party sharing</li>
                    <li>Opt-out of marketing communications</li>
                </ul>

                <h2>6. Cookies and Tracking Technologies</h2>
                <p>We use cookies and similar tracking technologies to track activity on our service and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. Our cookies policy includes:</p>
                <ul>
                    <li>Essential cookies for website functionality</li>
                    <li>Analytics cookies to understand usage</li>
                    <li>Marketing cookies for targeted advertising</li>
                    <li>Preference cookies to remember your settings</li>
                    <li>Session cookies for secure browsing</li>
                </ul>
                <p>You can control cookie preferences through your browser settings or our cookie consent banner.</p>

                <h2>7. Third-Party Services</h2>
                <p>We may employ third-party companies and individuals to facilitate our service, provide the service on our behalf, or assist us in analyzing how our service is used. These include:</p>
                <ul>
                    <li>Payment processors</li>
                    <li>Analytics providers</li>
                    <li>Marketing partners</li>
                    <li>Hotel partners</li>
                    <li>Customer service providers</li>
                    <li>Cloud storage providers</li>
                </ul>
                <p>These third parties have access to your personal data only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p>

                <h2>8. International Data Transfers</h2>
                <p>Your information may be transferred to and maintained on computers located outside of your state, province, country, or other governmental jurisdiction where the data protection laws may differ from those in your jurisdiction. We ensure appropriate safeguards are in place to protect your data during such transfers.</p>

                <h2>9. Children's Privacy</h2>
                <p>Our service does not address anyone under the age of 18. We do not knowingly collect personally identifiable information from children under 18. If you are a parent or guardian and you are aware that your child has provided us with personal data, please contact us.</p>

                <h2>10. Changes to This Privacy Policy</h2>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. We recommend that you review this Privacy Policy periodically for any changes.</p>

                <h2>11. Contact Us</h2>
                <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                <ul>
                    <li>Email: privacy@hotelhive.com</li>
                    <li>Phone: (+60) 11-11287021</li>
                    <li>Address: 123 Hotel Street, Hospitality City, HC 12345</li>
                </ul>
                <p>Our Data Protection Officer can be reached at:</p>
                <ul>
                    <li>Email: dpo@hotelhive.com</li>
                    <li>Phone: (+60) 11-11287022</li>
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