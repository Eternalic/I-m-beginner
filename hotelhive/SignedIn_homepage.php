<?php
session_start();
require_once __DIR__ . '/db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Handle sign out
if (isset($_GET['signout'])) {
    session_destroy();
    header("Location: homepage.php");
    exit;
}

// Fetch user data including profile image
$stmt = $conn->prepare("SELECT username, profile_img FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$username = $user_data['username'] ?? 'User';
$_SESSION['profile_img'] = $user_data['profile_img'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ered Hotel - Welcome, <?php echo htmlspecialchars($username); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            padding: 12px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }

        /* Logo Row */
        .logo-row {
            text-align: center;
            padding: 15px 0 10px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px 0;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        /* Desktop Search Bar */
        .desktop-search-bar {
            flex: 1;
            max-width: 600px;
            margin-right: 30px;
        }

        .header-search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .header-input-group {
            position: relative;
            flex: 1;
            min-width: 120px;
        }

        .header-search-form input,
        .header-search-form select {
            width: 100%;
            padding: 10px 15px;
            font-size: 14px;
            border: none;
            background: transparent;
            color: #333;
            outline: none;
        }

        .header-search-form input::placeholder {
            color: #666;
        }

        .header-search-form button {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .header-search-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        .nav-menu {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-menu a, .nav-menu span {
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.5px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            border-radius: 30px;
            position: relative;
        }

        .nav-menu a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        }

        .nav-menu .user-greeting {
            color: #000000;
            font-weight: 600;
            padding: 12px 24px;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            transition: all 0.3s ease;
        }

        .nav-menu .user-greeting:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Search Section */
        .search-section {
            background: #fff;
            padding: 50px;
            margin: 120px 0 60px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
            border: 1px solid rgba(200, 169, 126, 0.1);
        }

        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #c8a97e, #b69468, #c8a97e);
            border-radius: 20px 20px 0 0;
        }

        .search-tabs {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .search-tabs .tab {
            font-size: 16px;
            color: #666;
            padding: 15px 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 15px 15px 0 0;
            font-weight: 500;
            position: relative;
        }

        .search-tabs .tab.active {
            color: #0f172a;
            font-weight: 600;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .input-group {
            position: relative;
            flex: 1;
            min-width: 280px;
            z-index: 3;
        }

        .search-form input,
        .search-form select {
            width: 100%;
            padding: 18px 25px;
            font-size: 16px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            background: #fff;
            transition: all 0.3s ease;
            font-weight: 400;
            color: #1a1a1a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .search-form input:focus,
        .search-form select:focus {
            border-color: #c8a97e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.1);
            transform: translateY(-2px);
        }

        .search-form {
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-form button {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: none;
            padding: 18px 45px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 35px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 160px;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .search-form button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .search-form button:hover::before {
            left: 100%;
        }

        .search-form button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(200, 169, 126, 0.4);
        }

        .suggestion-list {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1001;
            margin-top: 5px;
            max-height: 300px;
            overflow-y: auto;
        }

        .suggestion-list.show {
            display: block;
        }

        .suggestion-list:empty {
            display: none !important;
            border: none;
            box-shadow: none;
        }

        #suggestions:empty {
            display: none;
            border: none;
            box-shadow: none;
        }

        .suggestion-list li {
            padding: 12px 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            border-bottom: 1px solid #eee;
        }

        .suggestion-list li:hover {
            background: rgba(200, 169, 126, 0.1);
            color: #c8a97e;
        }

        /* Promotional Banner */
        .promo-banner {
            position: relative;
            margin-top: 60px;
            background: linear-gradient(135deg, rgba(0,0,0,0.4), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1400&q=90') no-repeat center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 500px;
            height: 60vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 80px;
            border-radius: 25px;
            overflow: hidden;
            z-index: 1;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .promo-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(200, 169, 126, 0.3), rgba(0, 0, 0, 0.4));
            z-index: 1;
        }

        .promo-content {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .text-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
        }

        .welcome-message {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(28px, 5vw, 48px);
            font-weight: 700;
            margin: 0;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
            line-height: 1.2;
            animation: fadeInOut 1.5s ease-in-out forwards;
        }

        .text-wrapper p {
            font-size: clamp(16px, 2.5vw, 24px);
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            opacity: 1;
            line-height: 1.4;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(20px); }
            20% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-20px); }
        }

        @media (max-width: 768px) {
            /* Hide desktop search bar on mobile */
            .desktop-search-bar {
                display: none;
            }
            
            .header-top {
                justify-content: flex-end;
                padding: 10px 20px 0;
            }
            
            .promo-banner {
                min-height: 300px;
                padding: 20px;
            }
            
            .text-wrapper {
                gap: 8px;
                width: 90%;
            }
        }

        @media (max-width: 480px) {
            .promo-banner {
                min-height: 250px;
            }
            
            .text-wrapper {
                gap: 5px;
            }
            
            .text-wrapper p {
                font-size: 16px;
            }
        }

        /* Featured Hotels */
        .section {
            margin-bottom: 80px;
            background: #fff;
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(200, 169, 126, 0.1);
        }

        .section h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 42px;
            color: #1a1a1a;
            margin-bottom: 40px;
            font-weight: 700;
            text-align: center;
            position: relative;
        }

        .section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #c8a97e, #b69468);
            border-radius: 2px;
        }

        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
        }

        .hotel-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            position: relative;
            border: 1px solid rgba(200, 169, 126, 0.1);
        }

        .hotel-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #c8a97e, #b69468);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .hotel-card:hover::before {
            opacity: 1;
        }

        .hotel-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .hotel-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: transform 0.4s ease;
            display: block !important;
            border: 2px solid rgba(255, 215, 0, 0.3);
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1 !important;
        }

        .hotel-card:hover img {
            transform: scale(1.05);
        }

        .hotel-card-content {
            padding: 30px;
        }

        .hotel-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            color: #1a1a1a;
            margin: 0 0 15px;
            font-weight: 700;
        }

        .hotel-card p {
            font-size: 15px;
            color: #666;
            margin: 10px 0;
            line-height: 1.6;
        }

        .star-rating {
            color: #c8a97e;
            font-size: 18px;
            margin: 15px 0 10px 0;
        }

        .price {
            color: #c8a97e;
            font-weight: 700;
            font-size: 22px;
            margin-top: 10px;
            background: linear-gradient(135deg, #c8a97e, #b69468);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: #fff;
            padding: 80px 0 40px;
            font-size: 14px;
            margin-top: 100px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #c8a97e, #b69468, #c8a97e);
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

        @media (max-width: 1024px) {
            .search-section {
                margin-top: 100px;
                padding: 40px;
            }

            .hotel-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }

            .section {
                padding: 40px;
            }
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

            .search-form {
                flex-direction: column;
            }

            .input-group,
            .input-group.date-group {
                min-width: 100%;
            }

            .search-form button {
                width: 100%;
            }

            .hotel-grid {
                grid-template-columns: 1fr;
            }

            .promo-banner {
                height: 400px;
            }

            .welcome-message {
                font-size: 32px;
            }

            .destination-item {
                min-width: 180px;
            }

            .footer-content {
                flex-direction: column;
                gap: 30px;
            }

            /* Hide desktop search on mobile */
            .search-section:not(.mobile-search) {
                display: none;
            }

            /* Show mobile search dropdown when active */
            .mobile-search-dropdown.active {
                display: block;
                animation: slideDown 0.3s ease;
            }

            /* Ensure mobile search form is visible */
            .mobile-search-form {
                display: flex;
                flex-direction: column;
                gap: 15px;
                visibility: visible;
                opacity: 1;
            }
        }

        .search-form input[type="date"] {
            cursor: pointer;
            position: relative;
            min-width: 150px;
        }

        .search-form input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            padding: 5px;
            background-color: transparent;
        }

        .search-form input[type="date"]::-webkit-datetime-edit {
            padding: 0;
        }

        .search-form input[type="date"]::-webkit-inner-spin-button {
            display: none;
        }

        .search-form .date-range-group {
            position: relative;
        }

        /* Combined Date Range Picker Styles */
        .date-range-wrapper {
            position: relative;
        }

        .date-range-wrapper::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            content: '\f073';
            color: #c8a97e;
            font-size: 16px;
            pointer-events: none;
            z-index: 1;
        }

        .date-range-wrapper input {
            width: 100%;
            padding: 18px 25px 18px 45px;
            font-size: 16px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            background: #fff;
            transition: all 0.3s ease;
            font-weight: 400;
            color: #1a1a1a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .date-range-wrapper input:focus {
            border-color: #c8a97e;
            outline: none;
            box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.1);
            transform: translateY(-2px);
        }

        .date-range-wrapper input:not(:placeholder-shown) {
            color: #1a1a1a;
            font-weight: 500;
        }

        .date-range-info {
            position: absolute;
            bottom: -30px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            opacity: 0;
            transform: translateY(-5px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .date-range-wrapper:hover .date-range-info,
        .date-range-wrapper:focus-within .date-range-info {
            opacity: 1;
            transform: translateY(0);
        }

        .date-range-info span {
            padding: 2px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .checkin-info {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .checkout-info {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }

        /* Enhanced Flatpickr Styling */
        .enhanced-datepicker {
            border-radius: 12px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(200, 169, 126, 0.2) !important;
        }

        .enhanced-datepicker .flatpickr-day.selected {
            background: #c8a97e !important;
            border-color: #c8a97e !important;
        }

        .enhanced-datepicker .flatpickr-day:hover {
            background: rgba(200, 169, 126, 0.1) !important;
        }

        .enhanced-datepicker .flatpickr-day.today {
            border-color: #c8a97e !important;
            color: #c8a97e !important;
        }

        .enhanced-datepicker .flatpickr-day.today:hover {
            background: #c8a97e !important;
            color: white !important;
        }

        /* Date validation styles */
        .date-range-wrapper.error input {
            border-color: #f44336;
            box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
        }

        .date-range-wrapper.error .date-range-info {
            opacity: 0;
        }

        .date-validation-message {
            position: absolute;
            bottom: -30px;
            left: 0;
            font-size: 11px;
            color: #f44336;
            background: rgba(244, 67, 54, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            transform: translateY(-5px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .date-range-wrapper.error .date-validation-message {
            opacity: 1;
            transform: translateY(0);
        }

        .mobile-date-validation-message {
            position: absolute;
            bottom: -25px;
            left: 0;
            font-size: 10px;
            color: #f44336;
            background: rgba(244, 67, 54, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            transform: translateY(-5px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .mobile-date-range-wrapper.error .mobile-date-validation-message {
            opacity: 1;
            transform: translateY(0);
        }

        /* Redirection Overlay */
        .redirect-notification {
            display: none;
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            text-align: center;
            animation: slideDown 0.3s ease-out;
            margin-top: 80px;
            border: 2px solid #c8a97e;
        }

        .redirect-notification h3 {
            color: #1a4d3e;
            font-size: 18px;
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .redirect-notification .loading-dots {
            color: #c8a97e;
            font-size: 18px;
            animation: loadingDots 1.5s infinite;
        }

        @keyframes slideDown {
            from { 
                opacity: 0; 
                transform: translate(-50%, -100px); 
            }
            to { 
                opacity: 1; 
                transform: translate(-50%, 0); 
            }
        }

        @keyframes loadingDots {
            0% { content: '.'; }
            33% { content: '..'; }
            66% { content: '...'; }
            100% { content: '.'; }
        }

        .user-greeting {
            display: inline-flex;
            align-items: center;
            color: #c8a97e;
            font-weight: 600;
            padding: 8px 18px;
            background: rgba(200, 169, 126, 0.1);
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .user-greeting:hover {
            background: rgba(200, 169, 126, 0.2);
            transform: translateY(-2px);
        }

        .header-profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            background-color: #f8f8f8;
            border: 2px solid #c8a97e;
        }

        .header-profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header-profile-image i {
            font-size: 18px;
            color: #c8a97e;
        }

        /* Mobile-specific styles */
        @media (max-width: 768px) {
            /* Header Styles */
            .header {
                padding: 10px 0;
                position: fixed;
                width: 100%;
                z-index: 1000;
                background: white;
            }

            .header-top {
                padding: 0 15px;
                flex-direction: row !important;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }

            .logo {
                font-size: 24px;
            }

            .nav-menu {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .nav-menu a {
                font-size: 13px;
                padding: 6px 12px;
            }

            .user-greeting {
                font-size: 13px;
                padding: 6px 12px !important;
            }

            /* Mobile Search Button */
            .mobile-search-btn {
                background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
                color: #0f172a;
                border: none;
                padding: 8px 16px;
                border-radius: 25px;
                font-size: 14px;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            /* Banner Styles */
            .promo-banner {
                margin-top: 80px;
                min-height: 300px;
                padding: 20px;
                border-radius: 0;
            }

            .welcome-message {
                font-size: 28px !important;
                margin-bottom: 10px;
            }

            .text-wrapper p {
                font-size: 16px !important;
            }

            /* Popular Destinations */
            .top-destinations {
                padding: 20px;
                margin: 20px 0;
                border-radius: 12px;
            }

            .top-destinations h3 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .destinations-list-wrapper {
                display: flex;
                overflow-x: auto;
                padding: 10px 0;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .destinations-list-wrapper::-webkit-scrollbar {
                display: none;
            }

            .destination-item {
                min-width: 200px;
                margin-right: 15px;
                padding: 12px;
            }

            .destination-item:last-child {
                margin-right: 0;
            }

            /* Featured Hotels */
            .section {
                padding: 20px 0;
            }

            .section h2 {
                font-size: 24px;
                margin-bottom: 20px;
                padding: 0 15px;
            }

            .hotel-grid {
                display: flex;
                flex-direction: column;
                gap: 20px;
                padding: 0 15px;
            }

            .hotel-card {
                width: 100%;
                margin-bottom: 20px;
            }

            .hotel-card img {
                height: 200px;
            }

            .hotel-card-content {
                padding: 15px;
            }

            .hotel-card h3 {
                font-size: 20px;
            }

            /* Footer */
            .footer {
                padding: 40px 20px 20px;
                margin-top: 40px;
            }

            .footer-content {
                flex-direction: column;
                gap: 30px;
            }

            .footer-column {
                width: 100%;
            }

            .footer-column h4 {
                font-size: 18px;
                margin-bottom: 15px;
            }

            .footer-column a {
                padding: 8px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .footer-column a:last-child {
                border-bottom: none;
            }

            /* Mobile Search Dropdown */
            .mobile-search-dropdown {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.98);
                padding: 20px;
                z-index: 1001;
                overflow-y: auto;
            }

            .mobile-search-dropdown.active {
                display: block;
            }

            .mobile-search-form {
                padding-top: 60px;
                max-width: 600px;
                margin: 0 auto;
            }

            .mobile-input-group {
                background: #fff;
                border: 1px solid #eee;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 16px;
                position: relative;
                transition: all 0.3s ease;
            }

            .mobile-input-group:focus-within {
                border-color: #c8a97e;
                box-shadow: 0 2px 8px rgba(200, 169, 126, 0.1);
            }

            .mobile-input-label {
                font-size: 13px;
                font-weight: 600;
                color: #666;
                margin-bottom: 8px;
                display: block;
            }

            .mobile-search-form input,
            .mobile-search-form select {
                width: 100%;
                background: transparent;
                border: none;
                font-size: 16px;
                color: #333;
                padding: 4px 0;
                outline: none;
            }

            .mobile-date-range-wrapper {
                position: relative;
            }

            .mobile-date-range-wrapper input {
                width: 100%;
                background: transparent;
                border: none;
                font-size: 16px;
                color: #333;
                padding: 4px 0;
                outline: none;
            }

            .mobile-date-range-info {
                position: absolute;
                bottom: -25px;
                left: 0;
                right: 0;
                display: flex;
                justify-content: space-between;
                font-size: 10px;
                opacity: 0;
                transform: translateY(-5px);
                transition: all 0.3s ease;
                pointer-events: none;
            }

            .mobile-date-range-wrapper:hover .mobile-date-range-info,
            .mobile-date-range-wrapper:focus-within .mobile-date-range-info {
                opacity: 1;
                transform: translateY(0);
            }

            .mobile-date-range-info span {
                padding: 2px 6px;
                border-radius: 4px;
                white-space: nowrap;
            }

            .mobile-date-range-info .checkin-info {
                background: rgba(76, 175, 80, 0.1);
                color: #4CAF50;
            }

            .mobile-date-range-info .checkout-info {
                background: rgba(255, 152, 0, 0.1);
                color: #FF9800;
            }

            .mobile-search-form input::placeholder {
                color: #999;
            }

            .mobile-search-form button {
                background: #c8a97e;
                color: white;
                border: none;
                padding: 16px;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                width: 100%;
                margin-top: 20px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .mobile-search-form button:hover {
                background: #b69468;
                transform: translateY(-1px);
            }

            .mobile-suggestion-list {
                display: none;
                position: absolute;
                top: calc(100% + 5px);
                left: 0;
                width: 100%;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                max-height: 200px;
                overflow-y: auto;
                z-index: 1002;
            }

            .mobile-suggestion-list li {
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .mobile-suggestion-list li:hover {
                background: rgba(200, 169, 126, 0.1);
            }

            .mobile-suggestion-list li:last-child {
                border-bottom: none;
            }

            .mobile-search-header {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                background: white;
                padding: 15px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                z-index: 1002;
            }

            .mobile-search-close {
                background: none;
                border: none;
                font-size: 24px;
                color: #333;
                cursor: pointer;
                padding: 5px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-search-title {
                font-size: 18px;
                font-weight: 600;
                color: #333;
                margin: 0;
            }

            @media (max-width: 768px) {
                .mobile-search-btn {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                background: #c8a97e;
                color: white;
                border: none;
                    padding: 8px 16px;
                    border-radius: 25px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .mobile-search-btn:hover {
                    background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
                }

                .search-section {
                    display: none !important;
                }
            }
        }

        @media (min-width: 769px) {
            /* Desktop-specific styles */
            .header-top {
                flex-direction: row !important;
                padding: 0 20px;
            }

            .nav-menu {
                display: flex;
                gap: 20px;
            }

            .mobile-search-btn {
                display: none;  /* Hide mobile search on desktop */
            }

            .search-section {
                display: block !important;  /* Always show search section on desktop */
                margin-top: 100px;
                padding: 40px;
            }

            .search-form {
                display: flex !important;
                flex-direction: row;
                gap: 20px;
                align-items: center;
            }

            .input-group {
                min-width: 250px;
            }

            .input-group.date-group {
                min-width: 350px;
            }

            .mobile-search-dropdown {
                display: none !important;  /* Always hide mobile search dropdown on desktop */
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
            transform: translateY(-100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .notification-bar.show {
            display: block;
            transform: translateY(0);
            animation: slideInOut 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .notification-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(-10px);
            animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .notification-bar.show .notification-content {
            opacity: 1;
            transform: translateY(0);
        }

        .notification-content i {
            font-size: 20px;
        }

        .notification-content p {
            margin: 0;
            font-size: 14px;
        }

        @keyframes slideInOut {
            0% {
                transform: translateY(-100%);
            }
            15% {
                transform: translateY(0);
            }
            85% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-100%);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modify footer link styles */
        .footer-column a[data-unavailable="true"] {
            cursor: pointer;
        }

        /* Remove the hover tooltip styles */
        .footer-column a[data-unavailable="true"]:after {
            display: none;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .loading-animation {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .loading-dot {
            width: 12px;
            height: 12px;
            background-color: #4CAF50;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
            opacity: 0.6;
        }

        @keyframes bounce {
            0%, 80%, 100% { 
                transform: scale(0.6);
                opacity: 0.6;
            }
            40% { 
                transform: scale(1);
                opacity: 1;
            }
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(200, 169, 126, 0.3);
        }

        .btn.outline:hover {
            background: rgba(200, 169, 126, 0.1);
        }

        /* Modern Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hotel-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .hotel-card:nth-child(1) { animation-delay: 0.1s; }
        .hotel-card:nth-child(2) { animation-delay: 0.2s; }
        .hotel-card:nth-child(3) { animation-delay: 0.3s; }
        .hotel-card:nth-child(4) { animation-delay: 0.4s; }

        .search-section {
            animation: slideInLeft 0.8s ease-out;
        }

        .promo-banner {
            animation: slideInRight 0.8s ease-out;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            background: rgba(200, 169, 126, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Glassmorphism Effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Enhanced Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #c8a97e, #b69468);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #b69468, #c8a97e);
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Enhanced Focus States */
        *:focus {
            outline: 2px solid rgba(200, 169, 126, 0.3);
            outline-offset: 2px;
        }

        /* Loading States */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* WhatsApp Button Styles */
        .whatsapp-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(37, 211, 102, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            border: 3px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .whatsapp-button:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 12px 35px rgba(37, 211, 102, 0.6);
            background: linear-gradient(135deg, #128c7e 0%, #25d366 100%);
        }

        .whatsapp-button i {
            font-size: 28px;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .whatsapp-button::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: linear-gradient(135deg, #25d366, #128c7e);
            opacity: 0;
            animation: pulse 2s infinite;
            z-index: -1;
        }

        @keyframes pulse {
            0% {
                opacity: 0.7;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(1.4);
            }
        }

        /* Responsive WhatsApp Button */
        @media (max-width: 768px) {
            .whatsapp-button {
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
            }
            
            .whatsapp-button i {
                font-size: 26px;
            }
        }

        /* Chat System Button */
        .chat-button {
            position: fixed;
            bottom: 30px;
            right: 100px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            border: 3px solid rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .chat-button:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 12px 35px rgba(255, 215, 0, 0.6);
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
        }

        .chat-button i {
            font-size: 28px;
            color: #000000;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.3);
        }

        .chat-button::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd700, #ffffff);
            opacity: 0;
            animation: pulse 2s infinite;
            z-index: -1;
        }

        /* Chat Window */
        .chat-window {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 2px solid rgba(255, 215, 0, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            display: none;
            flex-direction: column;
            z-index: 1001;
            overflow: hidden;
        }

        .chat-window.active {
            display: flex;
            animation: slideUp 0.3s ease-out;
        }

        .chat-header {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            padding: 15px 20px;
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            color: #000000;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .chat-close {
            background: none;
            border: none;
            color: #000000;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .chat-close:hover {
            background: rgba(0, 0, 0, 0.2);
            transform: scale(1.1);
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .chat-message {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.5;
            animation: fadeIn 0.3s ease;
        }

        .chat-message.user {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .chat-message.bot {
            background: rgba(0, 0, 0, 0.8);
            color: #ffffff;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            border: 2px solid rgba(255, 215, 0, 0.4);
        }

        .chat-message.system {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            align-self: center;
            text-align: center;
            font-size: 12px;
            font-style: italic;
            border: 2px solid rgba(255, 215, 0, 0.4);
        }

        .chat-input-container {
            padding: 15px 20px;
            border-top: 2px solid rgba(255, 215, 0, 0.4);
            background: rgba(0, 0, 0, 0.9);
        }

        .chat-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 215, 0, 0.4);
            border-radius: 25px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .chat-input:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .chat-input::placeholder {
            color: #d1d5db;
        }

        .chat-send {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: none;
            padding: 12px 16px;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-send:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        .chat-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            margin-bottom: 10px;
            align-self: flex-start;
            font-size: 14px;
            color: #d1d5db;
            border: 2px solid rgba(255, 215, 0, 0.4);
        }

        .typing-indicator.active {
            display: flex;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: #d1d5db;
            border-radius: 50%;
            opacity: 0.6;
        }

        .typing-dot:nth-child(1) { animation: typing 1s infinite; }
        .typing-dot:nth-child(2) { animation: typing 1s infinite 0.2s; }
        .typing-dot:nth-child(3) { animation: typing 1s infinite 0.4s; }

        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive Chat System */
        @media (max-width: 768px) {
            .chat-button {
                bottom: 20px;
                right: 80px;
                width: 60px;
                height: 60px;
            }
            
            .chat-button i {
                font-size: 26px;
            }

            .chat-window {
                bottom: 90px;
                right: 20px;
                width: calc(100vw - 40px);
                max-width: 350px;
                height: 450px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <!-- Logo Row -->
            <div class="logo-row">
                <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
            </div>
            
            <!-- Search and Navigation Row -->
            <div class="header-top">
                <!-- Desktop Search Bar -->
                <div class="desktop-search-bar">
                    <form id="header-stays-form" class="header-search-form" action="search.php" method="POST">
                        <div class="header-input-group">
                            <input type="text" id="headerDestination" name="destination" placeholder="Where to?" required onkeyup="fetchSuggestions(this.value)" onclick="showAllDestinations()">
                            <ul id="headerSuggestions" class="suggestion-list"></ul>
                        </div>
                        <div class="header-input-group">
                            <input type="text" name="dateRange" id="headerDateRange" placeholder="Check-in — Check-out" required readonly>
                        </div>
                        <div class="header-input-group">
                            <select name="guests" required>
                                <option value="1" selected>1 Room</option>
                            </select>
                        </div>
                        <button type="submit">Search</button>
                    </form>
                </div>
                
                <div class="nav-menu">
                    <button class="mobile-search-btn" id="mobileSearchBtn">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                    <a href="#" class="user-greeting" onclick="showRedirectOverlay(event)">
                        <?php echo htmlspecialchars($username); ?>
                        <div class="header-profile-image">
                            <?php if (!empty($user_data['profile_img'])): ?>
                                <img src="<?php echo htmlspecialchars($user_data['profile_img']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                    <a href="manage_bookings.php">Your Bookings</a>
                    <a href="#" onclick="showSignoutModal(event)">Sign Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Search Dropdown -->
    <div class="mobile-search-dropdown" id="mobileSearchDropdown">
        <div class="mobile-search-header">
            <h3 class="mobile-search-title">Search Hotels</h3>
            <button type="button" class="mobile-search-close" id="mobileSearchClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form class="mobile-search-form" action="search.php" method="POST">
            <div class="mobile-input-group">
                <label class="mobile-input-label">LOCATION</label>
                <input type="text" 
                       id="mobileDestination" 
                       name="destination" 
                       placeholder="Where are you going?" 
                       autocomplete="off"
                       required>
                <ul class="mobile-suggestion-list" id="mobileSuggestions"></ul>
            </div>
            
            <div class="mobile-input-group">
                <label class="mobile-input-label">DATES</label>
                <div class="mobile-date-range-wrapper">
                    <input type="text" 
                           id="mobileDateRange" 
                           name="dateRange" 
                           placeholder="Check-in — Check-out" 
                           readonly 
                           required>
                    <div class="mobile-date-range-info">
                        <span class="checkin-info">Check-in: 3:00 PM onwards</span>
                        <span class="checkout-info">Check-out: 12:00 PM</span>
                    </div>
                    <div class="mobile-date-validation-message" id="mobileDateRange-error"></div>
                </div>
            </div>
            
            <div class="mobile-input-group">
                <label class="mobile-input-label">ROOMS</label>
                <select name="guests" required>
                    <option value="1" selected>1 Room</option>
                </select>
            </div>
            
            <button type="submit">Search Hotels</button>
        </form>
    </div>

    <!-- Floating Decorative Elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>


    <!-- Promotional Banner -->
    <div class="container">
        <div class="promo-banner">
            <div class="promo-content">
                <div class="text-wrapper">
                <h2 class="welcome-message">Welcome Back, <?php echo htmlspecialchars($username); ?>!</h2>
                    <p>Discover Your Perfect Stay with Ered Hotel</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Hotels -->
    <div class="container">
        <div class="section">
            <h2>Featured Luxury Stays</h2>
            <div class="hotel-grid">
                <div class="hotel-card" onclick="window.location.href='view_details.php?hotel_id=1'" style="cursor: pointer;">
                    <img src="images/hotel/oceanview_1.jpg" alt="Ered Hotel KL Central" 
                         onerror="this.src='images/hotel/oceanview.jpg'; console.log('Image failed to load: oceanview_1.jpg');"
                         onload="console.log('Image loaded successfully: oceanview_1.jpg');">
                    <div class="hotel-card-content">
                        <h3>Ered Hotel KL Central</h3>
                        <p>Kuala Lumpur City Center, Kuala Lumpur</p>
                        <?php
                            $hotelId = 1; // Ered Hotel KL Central ID from hotels.sql
                            $ratingQuery = "SELECT AVG(rating) as avg_rating FROM reviews WHERE hotel_id = ?";
                            $stmt = $conn->prepare($ratingQuery);
                            $stmt->bind_param("i", $hotelId);
                            $stmt->execute();
                            $ratingResult = $stmt->get_result();
                            if ($rating = $ratingResult->fetch_assoc()) {
                                $avgRating = round($rating['avg_rating']);
                                echo '<div class="star-rating">' . str_repeat('★', $avgRating) . str_repeat('☆', 5 - $avgRating) . '</div>';
                            } else {
                                echo '<div class="star-rating">No ratings yet</div>';
                            }
                        ?>
                        <div class="price">From MYR 385.00 / night</div>
                    </div>
                </div>

                <div class="hotel-card" onclick="window.location.href='view_details.php?hotel_id=2'" style="cursor: pointer;">
                    <img src="images/hotel/citylights_1.jpg" alt="Ered Hotel Twin Towers" onerror="this.src='images/hotel/citylights.jpg'">
                    <div class="hotel-card-content">
                        <h3>Ered Hotel Twin Towers</h3>
                        <p>KLCC District, Kuala Lumpur</p>
                        <?php
                            $hotelId = 2; // Ered Hotel Twin Towers ID from hotels.sql
                            $stmt->execute();
                            $ratingResult = $stmt->get_result();
                            if ($rating = $ratingResult->fetch_assoc()) {
                                $avgRating = round($rating['avg_rating']);
                                echo '<div class="star-rating">' . str_repeat('★', $avgRating) . str_repeat('☆', 5 - $avgRating) . '</div>';
                            } else {
                                echo '<div class="star-rating">No ratings yet</div>';
                            }
                        ?>
                        <div class="price">From MYR 165.00 / night</div>
                    </div>
                </div>

                <div class="hotel-card" onclick="window.location.href='view_details.php?hotel_id=3'" style="cursor: pointer;">
                    <img src="images/hotel/luxe_suites_1.jpg" alt="Ered Hotel Bukit Bintang" onerror="this.src='images/hotel/luxe_suites.jpg'">
                    <div class="hotel-card-content">
                        <h3>Ered Hotel Bukit Bintang</h3>
                        <p>Bukit Bintang, Kuala Lumpur</p>
                        <?php
                            $hotelId = 3; // Ered Hotel Bukit Bintang ID from hotels.sql
                            $stmt->execute();
                            $ratingResult = $stmt->get_result();
                            if ($rating = $ratingResult->fetch_assoc()) {
                                $avgRating = round($rating['avg_rating']);
                                echo '<div class="star-rating">' . str_repeat('★', $avgRating) . str_repeat('☆', 5 - $avgRating) . '</div>';
                            } else {
                                echo '<div class="star-rating">No ratings yet</div>';
                            }
                        ?>
                        <div class="price">From MYR 495.00 / night</div>
                    </div>
                </div>

                <div class="hotel-card" onclick="window.location.href='view_details.php?hotel_id=4'" style="cursor: pointer;">
                    <img src="images/hotel/budget_inn_1.jpg" alt="Budget Inn Kuta" onerror="this.src='images/hotel/budget_inn.jpg'">
                    <div class="hotel-card-content">
                        <h3>Budget Inn Kuta</h3>
                        <p>Malacca City Center, Malacca</p>
                        <?php
                            $hotelId = 4; // Budget Inn Kuta ID from hotels.sql
                            $stmt->execute();
                            $ratingResult = $stmt->get_result();
                            if ($rating = $ratingResult->fetch_assoc()) {
                                $avgRating = round($rating['avg_rating']);
                                echo '<div class="star-rating">' . str_repeat('★', $avgRating) . str_repeat('☆', 5 - $avgRating) . '</div>';
                            } else {
                                echo '<div class="star-rating">No ratings yet</div>';
                            }
                        ?>
                        <div class="price">From MYR 85.00 / night</div>
                    </div>
                </div>
            </div>
        </div>
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
                    <a href="press.php">Press</a>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <a href="help_center.php">Help Center</a>
                    <a href="javascript:void(0)" data-unavailable="true" onclick="showNotification()">Contact Us</a>
                    <a href="private_policy.php">Privacy Policy</a>
                    <a href="terms_of_service.php">Terms of Service</a>
                </div>
                <div class="footer-column">
                    <h4>Destinations</h4>
                    <a href="popular_cities.php">Popular Cities</a>
                    <a href="travel_guides.php">Travel Guides</a>
                </div>
                <div class="footer-column">
                    <h4>Follow Us</h4>
                    <a href="https://www.facebook.com/hotelhive/" target="_blank"><i class="fab fa-facebook-f"></i> Facebook</a>
                    <a href="https://x.com/hotelhive" target="_blank"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="https://www.instagram.com/hotelhive/" target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
                </div>
            </div>
            <p>&copy; 2024 Ered Hotel. All rights reserved.</p>
        </div>
    </div>

    <!-- Redirection Overlay -->
    <div id="redirectNotification" class="redirect-notification">
        <h3>Redirecting you to your profile<span class="loading-dots"></span></h3>
    </div>

    <!-- Add this before the closing body tag -->
    <div id="notificationBar" class="notification-bar">
        <div class="notification-content">
            <i class="fas fa-info-circle"></i>
            <p>This feature is coming soon! Stay tuned for updates.</p>
        </div>
    </div>

    <!-- Add this before the closing body tag -->
    <div id="signoutModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px; text-align: center; background: #fff; border-radius: 20px; padding: 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <span class="close-modal" id="closeSignoutModal" style="position: absolute; top: 20px; right: 20px; font-size: 24px; color: #c8a97e; cursor: pointer;">&times;</span>
            
            <div style="position: relative; z-index: 1;">
                <div style="font-size: 48px; color: #c8a97e; margin-bottom: 20px;">🏨</div>
                <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 20px; font-size: 28px; font-weight: 700;">
                    Until Next Time!
                </h2>
                <p style="margin-bottom: 25px; line-height: 1.8; color: #666; font-size: 16px;">
                    Thank you for choosing Ered Hotel for your travel needs.
                    <br><br>
                    <span style="color: #c8a97e; font-weight: 500; display: block; margin: 15px 0; font-size: 18px;">
                        We look forward to welcoming you back soon.
                    </span>
                </p>
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button class="btn outline" id="cancelSignout" style="background: transparent; border: 2px solid #c8a97e; color: #c8a97e; padding: 12px 30px; border-radius: 30px; font-weight: 600; transition: all 0.3s ease;">
                        Continue Exploring
                    </button>
                    <button class="btn" id="confirmSignout" style="background: #c8a97e; color: white; border: none; padding: 12px 30px; border-radius: 30px; font-weight: 600; transition: all 0.3s ease;">
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this before the closing body tag -->
    <div id="logoutSuccessModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px; text-align: center; background: #fff; border-radius: 20px; padding: 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <div style="position: relative; z-index: 1;">
                <div style="font-size: 48px; color: #4CAF50; margin-bottom: 20px;">✅</div>
                <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 20px; font-size: 28px; font-weight: 700;">
                    Successfully Logged Out
                </h2>
                <p style="margin-bottom: 25px; line-height: 1.8; color: #666; font-size: 16px;">
                    You have been successfully logged out of your account.
                    <br><br>
                    <span style="color: #4CAF50; font-weight: 500; display: block; margin: 15px 0; font-size: 18px;">
                        Redirecting you to the homepage...
                    </span>
                </p>
                <div style="display: flex; justify-content: center; margin-top: 30px;">
                    <div class="loading-animation">
                        <div class="loading-dot" style="animation-delay: 0s;"></div>
                        <div class="loading-dot" style="animation-delay: 0.2s;"></div>
                        <div class="loading-dot" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Tabs, Autocomplete, and Carousel -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Helper function to format dates in local timezone
            function formatDateLocal(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Initialize combined date range picker for header search
            const headerDateRangePicker = flatpickr("#headerDateRange", {
                mode: "range",
                minDate: "today",
                dateFormat: "Y-m-d",
                disableMobile: true,
                placeholder: "Check-in — Check-out",
                allowInput: false,
                clickOpens: true,
                onChange: function(selectedDates, dateStr, instance) {
                    console.log('Header date selection changed:', selectedDates, 'dateStr:', dateStr);
                    
                    if (selectedDates.length === 2) {
                        const checkin = selectedDates[0];
                        const checkout = selectedDates[1];
                        
                        // Format the display text using local timezone
                        const checkinStr = formatDateLocal(checkin);
                        const checkoutStr = formatDateLocal(checkout);
                        const displayText = `${checkinStr} — ${checkoutStr}`;
                        
                        console.log('Header formatted dates:', checkinStr, checkoutStr);
                        
                        instance.input.value = displayText;
                        instance.input.placeholder = displayText;
                        
                        // Real-time validation
                        validateDateRange(checkinStr, checkoutStr, 'header');
                    } else if (selectedDates.length === 1) {
                        // User is still selecting, show partial selection
                        const checkinStr = formatDateLocal(selectedDates[0]);
                        instance.input.value = checkinStr + ' — ';
                        instance.input.placeholder = checkinStr + ' — Check-out';
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    instance.calendarContainer.classList.add('enhanced-datepicker');
                }
            });

            // Initialize mobile date range picker
            const mobileDateRangePicker = flatpickr("#mobileDateRange", {
                mode: "range",
                minDate: "today",
                dateFormat: "Y-m-d",
                disableMobile: false,
                placeholder: "Check-in — Check-out",
                allowInput: false,
                clickOpens: true,
                onChange: function(selectedDates, dateStr, instance) {
                    console.log('Mobile date selection changed:', selectedDates, 'dateStr:', dateStr);
                    
                    if (selectedDates.length === 2) {
                        const checkin = selectedDates[0];
                        const checkout = selectedDates[1];
                        
                        // Format the display text using local timezone
                        const checkinStr = formatDateLocal(checkin);
                        const checkoutStr = formatDateLocal(checkout);
                        const displayText = `${checkinStr} — ${checkoutStr}`;
                        
                        console.log('Mobile formatted dates:', checkinStr, checkoutStr);
                        
                        instance.input.value = displayText;
                        instance.input.placeholder = displayText;
                        
                        // Real-time validation
                        validateDateRange(checkinStr, checkoutStr, 'mobile');
                    } else if (selectedDates.length === 1) {
                        // User is still selecting, show partial selection
                        const checkinStr = formatDateLocal(selectedDates[0]);
                        instance.input.value = checkinStr + ' — ';
                        instance.input.placeholder = checkinStr + ' — Check-out';
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    instance.calendarContainer.classList.add('enhanced-datepicker');
                }
            });

            // Destination suggestions
            function setupDestinationField(inputId, suggestionListId) {
                const input = document.getElementById(inputId);
                const suggestionList = document.getElementById(suggestionListId);

                // Show all destinations on focus
                input.addEventListener('focus', function() {
                    fetchDestinations('', inputId, suggestionListId);
                });

                // Update suggestions as user types
                input.addEventListener('input', function() {
                    fetchDestinations(this.value, inputId, suggestionListId);
                });

                // Close suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest(`#${inputId}`) && !e.target.closest(`#${suggestionListId}`)) {
                suggestionList.style.display = 'none';
                    }
                });
            }

            // Setup both desktop and mobile destination fields
            setupDestinationField('destination', 'suggestions');
            setupDestinationField('mobileDestination', 'mobileSuggestions');
            setupDestinationField('headerDestination', 'headerSuggestions');

            // Global fetchSuggestions function for HTML compatibility
            window.fetchSuggestions = function(query) {
                fetchDestinations(query, 'headerDestination', 'headerSuggestions');
            };

            // Global showAllDestinations function for HTML compatibility
            window.showAllDestinations = function() {
                fetchDestinations('', 'headerDestination', 'headerSuggestions');
            };

            // Fetch destinations function
            function fetchDestinations(query, inputId, suggestionListId) {
                const suggestionList = document.getElementById(suggestionListId);
                suggestionList.innerHTML = '';

            fetch(`suggestions.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        suggestionList.style.display = 'block';
                        data.forEach(item => {
                            const li = document.createElement('li');
                            li.textContent = item;
                                li.addEventListener('click', () => {
                                    document.getElementById(inputId).value = item;
                                suggestionList.style.display = 'none';
                                });
                            suggestionList.appendChild(li);
                        });
                    } else {
                        suggestionList.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching suggestions:', error));
        }

            // Enhanced date range validation
            function validateDateRange(checkinDate, checkoutDate, formType) {
                const prefix = formType === 'mobile' ? 'mobile' : (formType === 'header' ? 'header' : '');
                const wrapper = document.querySelector(`#${prefix}dateRange`).closest(`.${prefix}date-range-wrapper`);
                const errorElement = document.getElementById(`${prefix}dateRange-error`);
                
                let isValid = true;
                let errorMessage = '';
                
                // Clear previous errors
                wrapper.classList.remove('error');
                if (errorElement) errorElement.textContent = '';
                
                if (!checkinDate || !checkoutDate) {
                    wrapper.classList.add('error');
                    if (errorElement) errorElement.textContent = 'Please select both check-in and check-out dates';
                    return false;
                }
                
                const checkin = new Date(checkinDate);
                const checkout = new Date(checkoutDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (checkin < today) {
                    errorMessage = 'Check-in date cannot be in the past';
                    isValid = false;
                } else if (checkout <= checkin) {
                    errorMessage = 'Check-out must be after check-in date';
                    isValid = false;
                } else {
                    // Check for minimum stay (1 night)
                    const nights = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
                    if (nights < 1) {
                        errorMessage = 'Minimum stay is 1 night';
                        isValid = false;
                    }
                }
                
                if (!isValid) {
                    wrapper.classList.add('error');
                    if (errorElement) errorElement.textContent = errorMessage;
                }
                
                return isValid;
            }

            // Form submission handling
            function handleFormSubmission(form, formType) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const prefix = formType === 'mobile' ? 'mobile' : '';
                    const destination = document.getElementById(`${prefix}destination`).value.trim();
                    const dateRange = document.getElementById(`${prefix}dateRange`).value;
                    const guests = this.querySelector('select[name="guests"]').value;
                    
                    // Validate all fields
                    if (!destination) {
                        showNotification('Please enter a destination');
                        return;
                    }
                    
                    if (!dateRange || !dateRange.includes('—')) {
                        showNotification('Please select both check-in and check-out dates');
                        return;
                    }
                    
                    // Parse dates from the combined field
                    const dates = dateRange.split(' — ');
                    const checkin = dates[0] ? dates[0].trim() : '';
                    const checkout = dates[1] ? dates[1].trim() : '';
                    
                    console.log('Parsed dates:', { checkin, checkout });
                    
                    // Validate that both dates are present
                    if (!checkin || !checkout) {
                        showNotification('Please select both check-in and check-out dates');
                        return;
                    }
                    
                    if (!validateDateRange(checkin, checkout, formType)) {
                        showNotification('Please correct the date selection');
                        return;
                    }
                    
                    // Room selection is pre-set to 1, no validation needed
                    
                    // Add hidden inputs for checkin and checkout
                    const checkinInput = document.createElement('input');
                    checkinInput.type = 'hidden';
                    checkinInput.name = 'checkin';
                    checkinInput.value = checkin;
                    
                    const checkoutInput = document.createElement('input');
                    checkoutInput.type = 'hidden';
                    checkoutInput.name = 'checkout';
                    checkoutInput.value = checkout;
                    
                    this.appendChild(checkinInput);
                    this.appendChild(checkoutInput);
                    
                    // Debug: Log form data before submission
                    console.log('Form submission data:', {
                        destination: destination,
                        checkin: checkin,
                        checkout: checkout,
                        guests: guests
                    });
                    
                    // Show loading state
                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                    
                    // Submit the form
                    try {
                        this.submit();
                    } catch (error) {
                        console.error('Form submission error:', error);
                        showNotification('Error submitting search. Please try again.');
                        
                        // Re-enable button
                        const submitButton = this.querySelector('button[type="submit"]');
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Search';
                    }
                });
            }

            // Setup form submissions
            handleFormSubmission(document.getElementById('stays-form'), 'desktop');
            handleFormSubmission(document.querySelector('.mobile-search-form'), 'mobile');

            // Mobile search functionality
                const mobileSearchBtn = document.getElementById('mobileSearchBtn');
                const mobileSearchDropdown = document.getElementById('mobileSearchDropdown');
            const mobileSearchClose = document.getElementById('mobileSearchClose');
            const mobileSearchForm = document.querySelector('.mobile-search-form');

            if (mobileSearchBtn && mobileSearchDropdown) {
                // Toggle mobile search dropdown
                mobileSearchBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    mobileSearchDropdown.classList.add('active');
                        document.body.style.overflow = 'hidden';
                });

                // Close mobile search
                mobileSearchClose.addEventListener('click', function() {
                        mobileSearchDropdown.classList.remove('active');
                        document.body.style.overflow = '';
                });

                // Prevent dropdown from closing when clicking inside
                mobileSearchDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                // Mobile form submission is now handled by the general handleFormSubmission function

                // Mobile date range picker is initialized above

                // Mobile destination suggestions
                const mobileDestination = document.getElementById('mobileDestination');
                const mobileSuggestions = document.getElementById('mobileSuggestions');

                if (mobileDestination && mobileSuggestions) {
                    mobileDestination.addEventListener('focus', function() {
                        fetchMobileSuggestions('');
                    });

                    mobileDestination.addEventListener('input', function() {
                        fetchMobileSuggestions(this.value);
                    });

                function fetchMobileSuggestions(query) {
                    fetch(`suggestions.php?query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            mobileSuggestions.innerHTML = '';
                            if (data.length > 0) {
                                mobileSuggestions.style.display = 'block';
                                data.forEach(item => {
                                    const li = document.createElement('li');
                                    li.textContent = item;
                                    li.addEventListener('click', () => {
                                        mobileDestination.value = item;
                                        mobileSuggestions.style.display = 'none';
                                    });
                                    mobileSuggestions.appendChild(li);
                                });
                            } else {
                                mobileSuggestions.style.display = 'none';
                            }
                        })
                        .catch(error => console.error('Error fetching suggestions:', error));
                }

                // Close suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.mobile-input-group')) {
                        mobileSuggestions.style.display = 'none';
                        }
                    });
                }
            }
        });

        // Show notification function
        function showNotification() {
            const notificationBar = document.getElementById('notificationBar');
            notificationBar.classList.add('show');
            
            // Remove the show class after animation completes
            setTimeout(() => {
                notificationBar.classList.remove('show');
            }, 1500);
        }

        // Redirection functionality
        function showRedirectOverlay(event) {
            event.preventDefault();
            const notification = document.getElementById('redirectNotification');
            notification.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = 'own_account.php';
            }, 1500);
        }

        // Sign Out Modal functionality
        function showSignoutModal(event) {
            event.preventDefault();
            const signoutModal = document.getElementById("signoutModal");
            signoutModal.style.display = "flex";
            setTimeout(() => {
                signoutModal.classList.add('show');
            }, 10);
        }

        const signoutModal = document.getElementById("signoutModal");
        const logoutSuccessModal = document.getElementById("logoutSuccessModal");
        const closeSignoutModal = document.getElementById("closeSignoutModal");
        const cancelSignout = document.getElementById("cancelSignout");
        const confirmSignout = document.getElementById("confirmSignout");

        // Close modal when clicking close button or cancel
        if (closeSignoutModal) {
            closeSignoutModal.onclick = function() {
                signoutModal.classList.remove('show');
                setTimeout(() => {
                    signoutModal.style.display = "none";
                }, 400);
            }
        }

        if (cancelSignout) {
            cancelSignout.onclick = function() {
                signoutModal.classList.remove('show');
                setTimeout(() => {
                    signoutModal.style.display = "none";
                }, 400);
            }
        }

        // Handle sign out confirmation
        if (confirmSignout) {
            confirmSignout.onclick = function() {
                // Show loading state
                confirmSignout.disabled = true;
                confirmSignout.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing out...';

                // Make AJAX request to logout.php
                fetch('logout.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide sign out modal
                        signoutModal.classList.remove('show');
                        
                        // Show success modal
                        if (logoutSuccessModal) {
                            logoutSuccessModal.style.display = "flex";
            setTimeout(() => {
                                logoutSuccessModal.classList.add('show');
                                // Redirect after a short delay
                                setTimeout(() => {
                                    window.location.href = 'homepage.php';
            }, 1500);
                            }, 10);
                        } else {
                            // Fallback if success modal doesn't exist
                            window.location.href = 'homepage.php';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to direct logout
                    window.location.href = 'logout.php';
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == signoutModal) {
                signoutModal.classList.remove('show');
                setTimeout(() => {
                    signoutModal.style.display = "none";
                }, 400);
            }
        }

        // Chat System Functions
        let chatOpen = false;
        let chatHistory = [];

        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');
            chatOpen = !chatOpen;
            
            if (chatOpen) {
                chatWindow.classList.add('active');
                document.getElementById('chatInput').focus();
                loadChatHistory();
            } else {
                chatWindow.classList.remove('active');
            }
        }

        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatMessage();
            }
        }

        function sendChatMessage() {
            const chatInput = document.getElementById('chatInput');
            const message = chatInput.value.trim();
            
            if (message) {
                // Add user message
                addChatMessage(message, 'user');
                chatInput.value = '';
                
                // Show typing indicator
                showTypingIndicator();
                
                // Generate auto-response
                setTimeout(() => {
                    hideTypingIndicator();
                    const response = generateAutoResponse(message);
                    addChatMessage(response, 'bot');
                }, 1000 + Math.random() * 1000); // Random delay between 1-2 seconds
            }
        }

        function addChatMessage(message, type) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${type}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.innerHTML = `<p>${message.replace(/\n/g, '<br>')}</p>`;
            
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = new Date().toLocaleTimeString('zh-CN', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            messageDiv.appendChild(contentDiv);
            messageDiv.appendChild(timeDiv);
            chatMessages.appendChild(messageDiv);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Save to history
            chatHistory.push({
                message: message,
                type: type,
                timestamp: new Date().toISOString()
            });
            
            // Save to localStorage
            localStorage.setItem('ered_hotel_chat_history', JSON.stringify(chatHistory));
        }

        function showTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            typingIndicator.classList.add('active');
            
            // Scroll to bottom
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            typingIndicator.classList.remove('active');
        }

        function loadChatHistory() {
            const savedHistory = localStorage.getItem('ered_hotel_chat_history');
            if (savedHistory) {
                try {
                    chatHistory = JSON.parse(savedHistory);
                    
                    // Only load if there are messages (more than just the welcome message)
                    if (chatHistory.length > 0) {
                        const chatMessages = document.getElementById('chatMessages');
                        
                        // Clear existing messages except welcome message
                        const welcomeMessage = chatMessages.querySelector('.chat-message.bot');
                        chatMessages.innerHTML = '';
                        chatMessages.appendChild(welcomeMessage);
                        
                        // Add history messages
                        chatHistory.forEach(msg => {
                            addChatMessage(msg.message, msg.type);
                        });
                    }
                } catch (e) {
                    console.error('Error loading chat history:', e);
                    chatHistory = [];
                }
            }
        }

        function clearChatHistory() {
            chatHistory = [];
            localStorage.removeItem('ered_hotel_chat_history');
            
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = `
                <div class="chat-message bot">
                    <div class="message-content">
                        <p>欢迎来到 Ered Hotel！我是您的专属客服助手，有什么可以帮助您的吗？</p>
                    </div>
                    <div class="message-time">${new Date().toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
            `;
        }

        // Auto-responses for common questions
        function generateAutoResponse(message) {
            const msg = message.toLowerCase();
            
            if (msg.includes('价格') || msg.includes('费用') || msg.includes('多少钱')) {
                return '我们的房价根据房型和季节有所不同。您可以在搜索页面查看具体价格，或者告诉我您想要预订的日期和房型，我可以为您查询最新价格。';
            }
            
            if (msg.includes('预订') || msg.includes('订房') || msg.includes('预约')) {
                return '您可以通过我们的网站直接预订房间。请选择您的入住和退房日期、房型以及客人数量，系统会为您显示可用的房间和价格。';
            }
            
            if (msg.includes('取消') || msg.includes('退订')) {
                return '我们的取消政策是：入住前24小时可以免费取消，24小时内取消将收取第一晚房费。您可以在"我的预订"页面管理您的订单。';
            }
            
            if (msg.includes('设施') || msg.includes('服务') || msg.includes('设备')) {
                return '我们提供以下设施和服务：\n• 免费WiFi\n• 24小时前台服务\n• 健身房\n• 游泳池\n• 餐厅\n• 商务中心\n• 客房服务\n• 停车场';
            }
            
            if (msg.includes('位置') || msg.includes('地址') || msg.includes('在哪里')) {
                return '我们的酒店位于市中心，交通便利。具体地址和交通信息您可以在酒店详情页面查看。您也可以告诉我您要预订的具体酒店，我可以为您提供详细的位置信息。';
            }
            
            if (msg.includes('联系') || msg.includes('电话') || msg.includes('客服')) {
                return '您可以通过以下方式联系我们：\n• 在线客服（当前聊天）\n• WhatsApp: +60 12-969-3317\n• 邮箱: support@eredhotel.com\n• 电话: +60 3-1234-5678';
            }
            
            if (msg.includes('支付') || msg.includes('付款') || msg.includes('付费')) {
                return '我们接受以下支付方式：\n• 信用卡（Visa、MasterCard、American Express）\n• 借记卡\n• PayPal\n• 银行转账\n• 现金（部分酒店）\n所有在线交易都是安全加密的。';
            }
            
            if (msg.includes('会员') || msg.includes('积分') || msg.includes('奖励')) {
                return '我们提供 Ered Hotel 会员计划：\n• 每次住宿获得积分\n• 免费房间升级\n• 延迟退房特权\n• 会员专属价格\n• 免费WiFi\n• 积分永不过期\n\n您想了解更多关于会员计划的信息吗？';
            }
            
            return '感谢您的咨询！我是 Ered Hotel 的智能客服助手。如果您需要帮助，可以询问关于：\n• 房间预订和价格\n• 酒店设施和服务\n• 取消和退款政策\n• 会员计划\n• 支付方式\n• 酒店位置和交通\n• 其他任何问题\n\n或者您也可以直接通过 WhatsApp 联系我们的客服团队。';
        }
    </script>

    <!-- Chat System Button -->
    <button class="chat-button" onclick="toggleChat()" title="在线客服聊天">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Chat Window -->
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h3><i class="fas fa-headset"></i> Ered Hotel 客服</h3>
            <button class="chat-close" onclick="toggleChat()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="chat-message bot">
                <div class="message-content">
                    <p>欢迎来到 Ered Hotel！我是您的专属客服助手，有什么可以帮助您的吗？</p>
                </div>
                <div class="message-time"><?php echo date('H:i'); ?></div>
            </div>
        </div>
        
        <div class="typing-indicator" id="typingIndicator">
            <span>客服正在输入</span>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <input type="text" 
                       class="chat-input" 
                       id="chatInput" 
                       placeholder="输入您的问题..." 
                       onkeypress="handleChatKeyPress(event)">
                <button class="chat-send" onclick="sendChatMessage()" id="chatSendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- WhatsApp Button -->
    <a href="https://api.whatsapp.com/send/?phone=60129693317&text&type=phone_number&app_absent=0" 
       target="_blank" 
       class="whatsapp-button" 
       title="联系 Ered Hotel 客服">
        <i class="fab fa-whatsapp"></i>
    </a>
</body>
</html>