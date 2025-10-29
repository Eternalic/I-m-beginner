<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Ered Hotel - Your Trusted Hotel Information Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.8rem;
            color: #333;
            text-decoration: none;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #1a237e;
        }

        .header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/about-banner.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 0 40px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #1a237e;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
        }

        .section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #1a237e;
        }

        .section p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            color: #555;
            text-align: center;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-color: #1a237e;
        }

        .feature-card i {
            font-size: 3rem;
            color: #1a237e;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            color: #1a237e;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .feature-card p {
            color: #666;
            font-size: 1rem;
            text-align: center;
            margin: 0;
        }

        .stats-section {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 4rem 0;
            margin: 4rem 0;
            border-radius: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 1rem;
        }

        .stat-item h3 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-item p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-member {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .team-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .team-member img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-bottom: 3px solid #1a237e;
        }

        .team-member-content {
            padding: 1.5rem;
            text-align: center;
        }

        .team-member h3 {
            color: #1a237e;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .team-member p {
            color: #666;
            font-size: 1rem;
            margin: 0;
        }

        .contact-section {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 4rem 0;
            border-radius: 15px;
            text-align: center;
        }

        .contact-section h2 {
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }

        .contact-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .contact-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3rem;
            margin-top: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.2rem;
        }

        .contact-item i {
            font-size: 2rem;
        }

        .whatsapp-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            background: #25D366;
            color: white;
            padding: 1.2rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 2rem;
            transition: all 0.3s ease;
        }

        .whatsapp-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }

        .footer {
            background: #1a237e;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5rem;
            }
            
            .header p {
                font-size: 1.2rem;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            .section {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="nav-header">
        <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
        <div class="nav-links">
            <a href="SignedIn_homepage.php">Home</a>
            <a href="your_bookings.php">Your Bookings</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>

    <div class="header">
        <h1>About Ered Hotel</h1>
        <p>Your Trusted Hotel Information Platform</p>
    </div>

    <div class="container">
        <div class="section">
            <h2>Our Story</h2>
            <p>Founded in 2024, Ered Hotel emerged from a vision to revolutionize how travelers access and interact with hotel information. We understand that finding the perfect accommodation is more than just a booking – it's about creating memorable experiences.</p>
            <p>Our platform combines cutting-edge technology with personalized service to ensure that every traveler finds their ideal stay. Whether you're planning a luxury getaway or a budget-friendly trip, Ered Hotel is your trusted partner in making informed accommodation decisions.</p>
        </div>

        <div class="section">
            <h2>What We Offer</h2>
            <div class="features">
                <div class="feature-card">
                    <i class="fas fa-hotel"></i>
                    <h3>Comprehensive Hotel Information</h3>
                    <p>Detailed insights about hotels, including amenities, location, policies, and real guest reviews to help you make informed decisions.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bed"></i>
                    <h3>Room Details & Pricing</h3>
                    <p>Transparent information about room types, prices, and special offers to find the perfect accommodation for your needs.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-comments"></i>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock assistance from our dedicated team to ensure a smooth and hassle-free experience.</p>
                </div>
            </div>
        </div>

        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>10+</h3>
                    <p>Hotels Listed</p>
                </div>
                <div class="stat-item">
                    <h3>3</h3>
                    <p>Countries Covered</p>
                </div>
                <div class="stat-item">
                    <h3>100+</h3>
                    <p>Happy Travelers</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Customer Support</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Our Team</h2>
            <div class="team-grid">
                <div class="team-member">
                    <img src="images/staff1.jpg" alt="John Doe">
                    <div class="team-member-content">
                        <h3>John Doe</h3>
                        <p>Founder & CEO</p>
                    </div>
                </div>
                <div class="team-member">
                    <img src="images/staff2.jpg" alt="Jane Smith">
                    <div class="team-member-content">
                        <h3>Jane Smith</h3>
                        <p>Head of Operations</p>
                    </div>
                </div>
                <div class="team-member">
                    <img src="images/staff3.jpg" alt="Mike Johnson">
                    <div class="team-member-content">
                        <h3>Mike Johnson</h3>
                        <p>Customer Support Lead</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section contact-section">
            <h2>Get in Touch</h2>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>+60 17-728 4421</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@hotelhive.com</span>
                </div>
            </div>
            <a href="https://wa.me/60177284421" class="whatsapp-btn" target="_blank">
                <i class="fab fa-whatsapp"></i>
                Chat with us on WhatsApp
            </a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Ered Hotel. All rights reserved.</p>
    </footer>
</body>
</html> 