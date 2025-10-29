<?php
session_start();
require_once 'db.php';

// Get parameters from URL
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;
$option_id = isset($_GET['option_id']) ? (int)$_GET['option_id'] : 1;
$total_price = isset($_GET['total_price']) ? (float)$_GET['total_price'] : 0;

// Set default dates if not provided
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Always set checkin to today and checkout to tomorrow if not provided
$checkin = isset($_GET['checkin']) && !empty($_GET['checkin']) ? $_GET['checkin'] : $today;
$checkout = isset($_GET['checkout']) && !empty($_GET['checkout']) ? $_GET['checkout'] : $tomorrow;

// Ensure checkout is always at least one day after checkin
if ($checkin === $checkout) {
    $checkout = date('Y-m-d', strtotime($checkin . ' +1 day'));
}

// Debug: Log the dates to ensure they're correct
error_log("Checkin date: " . $checkin);
error_log("Checkout date: " . $checkout);

// Check if user is logged in and get their info
$user_data = [];
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_sql = "SELECT first_name, last_name, email, phone FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    
    if ($user_stmt) {
        $user_stmt->bind_param('i', $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            // Add default country since it's not in the database
            $user_data['country'] = 'USA';
        } else {
            // If no user data found, provide default values
            $user_data = [
                'first_name' => 'Guest',
                'last_name' => 'User',
                'email' => 'guest@eredhotel.com',
                'phone' => '+1234567890',
                'country' => 'USA'
            ];
        }
        
        $user_stmt->close();
    } else {
        // Fallback with dummy data if database query doesn't work
        $user_data = [
            'first_name' => 'Guest',
            'last_name' => 'User',
            'email' => 'guest@eredhotel.com',
            'phone' => '+1234567890',
            'country' => 'USA'
        ];
    }
} else {
    // If user is not logged in, provide default values
    $user_data = [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@eredhotel.com',
        'phone' => '+1234567890',
        'country' => 'USA'
    ];
}

// Calculate number of nights
$nights = 1;
if (!empty($checkin) && !empty($checkout)) {
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $interval = $checkin_date->diff($checkout_date);
    $nights = $interval->days;
    if ($nights < 1) $nights = 1;
}

// Fetch hotel details
$sql = "
    SELECT h.hotel_id, h.name, h.location, h.city, h.country, h.image_url,
           COALESCE(AVG(rev.rating), 0) as avg_rating
    FROM hotels h
    LEFT JOIN reviews rev ON h.hotel_id = rev.hotel_id
    WHERE h.hotel_id = ?
    GROUP BY h.hotel_id, h.name, h.location, h.city, h.country, h.image_url
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $hotel_id);
$stmt->execute();
$hotel_result = $stmt->get_result();
$hotel = $hotel_result->fetch_assoc();
$stmt->close();

// Fetch room details
$room_sql = "
    SELECT room_type, price_per_night, max_guests
    FROM rooms
    WHERE hotel_id = ? AND room_type = ?
";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param('is', $hotel_id, $room_type);
$room_stmt->execute();
$room_result = $room_stmt->get_result();
$room = $room_result->fetch_assoc();
$room_stmt->close();

// Fetch average rating
$rating_sql = "
    SELECT AVG(rev.rating) as avg_rating, COUNT(rev.review_id) as review_count
    FROM reviews rev
    WHERE rev.hotel_id = ?
";
$rating_stmt = $conn->prepare($rating_sql);
$rating_stmt->bind_param('i', $hotel_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();
$avg_rating = $rating_data['avg_rating'] ? number_format($rating_data['avg_rating'], 1) : 'N/A';
$review_count = $rating_data['review_count'] ?? 0;
$rating_stmt->close();

// Free amenities
$amenities = ['Free WiFi', 'Parking', 'Welcome drink', 'Coffee & tea', 'Drinking water', 'Free fitness center access'];

// Function to generate a unique booking number
function generateBookingNumber($conn) {
    do {
        // Create arrays for letters and numbers
        $letters = range('A', 'Z');
        $numbers = range(0, 9);
        $all_chars = array_merge($letters, $numbers);
        
        // Initialize booking number
        $booking_number = '';
        
        // Generate 5 characters ensuring at least 2 letters and 2 numbers
        $num_count = 0;
        $letter_count = 0;
        
        // First, add 2 random letters
        for ($i = 0; $i < 2; $i++) {
            $booking_number .= $letters[array_rand($letters)];
            $letter_count++;
        }
        
        // Then, add 2 random numbers
        for ($i = 0; $i < 2; $i++) {
            $booking_number .= $numbers[array_rand($numbers)];
            $num_count++;
        }
        
        // Add the final character randomly from all possible characters
        $booking_number .= $all_chars[array_rand($all_chars)];
        
        // Shuffle the booking number to mix the characters
        $booking_number = str_shuffle($booking_number);
        
        // Check if this booking number already exists
        $check_sql = "SELECT book_number FROM bookings WHERE book_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $booking_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $exists = $result->num_rows > 0;
        $check_stmt->close();
        
    } while ($exists); // Keep generating until we get a unique number
    
    return $booking_number;
}

// Process booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    // Get updated dates from form if available
    $updated_checkin = $_POST['checkin_date'] ?? $checkin;
    $updated_checkout = $_POST['checkout_date'] ?? $checkout;
    
    // Dates are already in Y-m-d format from the form
    // No conversion needed
    
    // Validate required fields
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
        $error_message = "Please fill in all required fields";
    } else if (empty($updated_checkin) || empty($updated_checkout)) {
        $error_message = "Please select valid check-in and check-out dates";
    } else {
        // Validate dates
            $checkin_date = new DateTime($updated_checkin);
            $checkout_date = new DateTime($updated_checkout);
        
        if ($checkin_date >= $checkout_date) {
            $error_message = "Check-out date must be after check-in date";
        } else {
            // Check if user is logged in
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
                $error_message = "You must be logged in to complete a booking. Please sign in.";
            } else {
                // Verify user exists in database
                $user_id = $_SESSION['user_id'];
                $user_check_query = "SELECT user_id FROM users WHERE user_id = ?";
                $user_check_stmt = $conn->prepare($user_check_query);
                $user_check_stmt->bind_param('i', $user_id);
                $user_check_stmt->execute();
                $user_check_result = $user_check_stmt->get_result();
                
                if ($user_check_result->num_rows === 0) {
                    $error_message = "User account not found. Please sign in again.";
                    $user_check_stmt->close();
                } else {
                    $user_check_stmt->close();
                    
                    // Additional validation: ensure user_id is valid
                    if (!is_numeric($user_id) || $user_id <= 0) {
                        $error_message = "Invalid user ID. Please sign in again.";
                    } else {
                
                // Debug: Log user and booking details
                error_log("User ID: " . $user_id);
                error_log("Hotel ID: " . $hotel_id);
                error_log("Room Type: " . $room_type);
                error_log("Check-in: " . $checkin);
                error_log("Check-out: " . $checkout);
                
                // Get room_id from the database
                $room_query = "SELECT room_id FROM rooms WHERE hotel_id = ? AND room_type = ? LIMIT 1";
                $room_stmt = $conn->prepare($room_query);
                $room_stmt->bind_param('is', $hotel_id, $room_type);
                $room_stmt->execute();
                $room_result = $room_stmt->get_result();
                
                if ($room_result && $room_result->num_rows > 0) {
                    $room_data = $room_result->fetch_assoc();
                    $room_id = $room_data['room_id'];
                    $room_stmt->close();
                    
                    // Debug: Log room ID
                    error_log("Room ID: " . $room_id);
                    
                    // Generate unique booking number
                    $book_number = generateBookingNumber($conn);
                    
                    // Debug: Log booking number
                    error_log("Generated Booking Number: " . $book_number);
                    
                    // Format dates for MySQL
                    try {
                        // Ensure dates are in correct format
                            $checkin_date = DateTime::createFromFormat('Y-m-d', $updated_checkin);
                            $checkout_date = DateTime::createFromFormat('Y-m-d', $updated_checkout);
                        
                        if (!$checkin_date || !$checkout_date) {
                            throw new Exception("Invalid date format");
                        }
                        
                        // Format dates in Y-m-d format for MySQL
                        $checkin_formatted = $checkin_date->format('Y-m-d');
                        $checkout_formatted = $checkout_date->format('Y-m-d');
                        
                        // Debug: Log formatted dates
                        error_log("Formatted Check-in: " . $checkin_formatted);
                        error_log("Formatted Check-out: " . $checkout_formatted);
                        
                        // Insert booking record with special_requests
                        $booking_status = 'pending';
                        $special_requests = $_POST['room_preference'] ?? 'non-smoking'; // Default to non-smoking if not set
                        
                        // Get late checkout time and room service package
                        $late_checkout_time = $_POST['checkout_time'] ?? '12:00';
                        $room_service_package = $_POST['room_service'] ?? 'none';
                        
                        // Calculate additional fees
                        $additional_fees = 0;
                        
                        // Late checkout fee
                        if ($late_checkout_time !== '12:00') {
                            switch ($late_checkout_time) {
                                case '14:00':
                                    $additional_fees += 15;
                                    break;
                                case '16:00':
                                    $additional_fees += 30;
                                    break;
                                case '18:00':
                                    $additional_fees += 50;
                                    break;
                            }
                        }
                        
                        // Room service fee
                        if ($room_service_package !== 'none') {
                            $nights = (new DateTime($checkout))->diff(new DateTime($checkin))->days;
                            switch ($room_service_package) {
                                case 'basic':
                                    $additional_fees += 25 * $nights;
                                    break;
                                case 'premium':
                                    $additional_fees += 50 * $nights;
                                    break;
                                case 'luxury':
                                    $additional_fees += 100 * $nights;
                                    break;
                            }
                        }
                        
                        // Calculate final total price including additional fees
                        $final_total_price = $total_price + $additional_fees;
                        
                        // Debug: Log special requests
                        error_log("Special Requests: " . $special_requests);
                        error_log("Late Checkout Time: " . $late_checkout_time);
                        error_log("Room Service Package: " . $room_service_package);
                        error_log("Additional Fees: " . $additional_fees);
                        error_log("Final Total Price: " . $final_total_price);
                        
                        $booking_query = "INSERT INTO bookings (hotel_id, user_id, room_id, book_number, check_in_date, check_out_date, total_price, booking_status, special_requests, late_checkout_time, room_service_package) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $booking_stmt = $conn->prepare($booking_query);
                        
                        // Corrected bind_param string: i=integer, s=string, d=double
                        $booking_stmt->bind_param('iiisssdssss', 
                            $hotel_id,          // i
                            $user_id,           // i
                            $room_id,           // i
                            $book_number,       // s
                            $checkin_formatted, // s
                            $checkout_formatted,// s
                            $final_total_price, // d
                            $booking_status,    // s
                            $special_requests,  // s
                            $late_checkout_time,// s
                            $room_service_package // s
                        );
                        
                        if ($booking_stmt->execute()) {
                            $booking_id = $conn->insert_id;
                            $booking_stmt->close();
                            
                            // Debug: Log successful booking
                            error_log("Booking successful! Booking ID: " . $booking_id);
                            
                            // Clear any output before redirect
                            ob_clean();
                            
                            // Redirect to payment page with booking ID
                            header("Location: payment.php?booking_id=" . $booking_id);
                            exit();
                        } else {
                            $error_message = "Error creating booking: " . $conn->error;
                            error_log("Database Error: " . $conn->error);
                            $booking_stmt->close();
                        }
                    } catch (Exception $e) {
                        $error_message = "Error processing dates: " . $e->getMessage();
                        error_log("Date Processing Error: " . $e->getMessage());
                    }
                } else {
                    $error_message = "Room not found. Please try a different room type.";
                    error_log("Room not found for hotel_id: " . $hotel_id . " and room_type: " . $room_type);
                    $room_stmt->close();
                }
                    }
                }
            }
        }
    }
}

$currency = 'MYR ';

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
    <title>Complete Your Booking - <?php echo htmlspecialchars($hotel['name'] ?? 'Hotel'); ?> - Ered Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #f8fafc;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            padding: 12px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-menu a {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border-radius: 25px;
        }

        .nav-menu a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        }

        .booking-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            width: 100%;
            margin-top: 80px;
            z-index: 1;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            position: relative;
            width: 100%;
            padding: 0 10px;
            margin-top: 50px;
        }

        .step-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e0e0e0;
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .step-dot.active {
            background-color: #c8a97e;
            box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.2);
        }
        
        .step-dot.completed {
            background-color: #c8a97e;
        }
        
        .step-text {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .step-dot.active .step-text {
            color: #c8a97e;
            font-weight: bold;
        }
        
        .step-dot.completed .step-text {
            color: #c8a97e;
        }

        .step-line {
            height: 3px;
            background-color: #e0e0e0;
            flex-grow: 1;
            position: relative;
            top: 15px;
            margin: 0;
            z-index: 1;
        }

        .step-line.completed {
            background-color: #c8a97e;
        }

        .booking-container {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .form-section {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.2);
            padding: 30px;
            margin-bottom: 20px;
            flex: 2;
            min-width: 500px;
        }

        .reservation-summary {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 215, 0, 0.2);
            padding: 30px;
            flex: 1;
            min-width: 300px;
            align-self: flex-start;
            position: sticky;
            top: 120px;
        }

        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .required-field {
            color: #c8a97e;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-field {
            flex: 1;
        }

        .form-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #cbd5e1;
            font-size: 14px;
        }

        .form-field input, 
        .form-field select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            color: #f8fafc;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-field input:focus, 
        .form-field select:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            background: rgba(30, 41, 59, 0.95);
        }

        .hotel-info {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 15px;
        }

        .hotel-image {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
        }

        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hotel-details h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #1a1a1a;
            font-family: 'Cormorant Garamond', serif;
        }

        .hotel-details p {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .rating {
            color: #c8a97e;
            margin: 5px 0;
        }

        .stay-details {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .stay-details p {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }

        .stay-details .label {
            color: #666;
        }

        .stay-details .value {
            font-weight: 500;
            color: #1a1a1a;
        }

        .price-details {
            margin-bottom: 25px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }

        .price-row.total {
            font-weight: 600;
            font-size: 18px;
            color: #1a1a1a;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #eee;
        }

        .timer {
            background-color: #fff3e0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #c8a97e;
            border: 1px solid rgba(200, 169, 126, 0.2);
        }

        .timer-icon {
            margin-right: 10px;
            font-size: 18px;
        }

        .checkbox-group {
            margin: 20px 0;
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #1a1a1a;
        }

        .checkbox-label input {
            margin-top: 3px;
        }

        .preference-section {
            margin-top: 30px;
            background: #f8f8f8;
            padding: 25px;
            border-radius: 15px;
        }

        .preference-title {
            font-weight: 500;
            margin-bottom: 15px;
            color: #1a1a1a;
            font-size: 16px;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #1a1a1a;
        }

        .room-environment-display {
            background: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .display-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 500;
        }

        .checkout-display {
            background: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .continue-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 25px;
            transition: all 0.3s ease;
        }

        .continue-btn:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .error-message {
            color: #c8a97e;
            background-color: #fff3e0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            border: 1px solid rgba(200, 169, 126, 0.2);
        }

        .form-info {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }

        .amenities-list {
            margin-top: 20px;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #1a1a1a;
        }

        .amenity-item span {
            color: #c8a97e;
        }

        .room-left {
            display: inline-block;
            background-color: #fff3e0;
            color: #c8a97e;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 15px;
            border: 1px solid rgba(200, 169, 126, 0.2);
        }

        .date-input {
            background: transparent;
            border: none;
            color: #1a1a1a;
            font-weight: 500;
            font-size: 14px;
            padding: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            width: auto;
            min-width: 120px;
        }

        .date-input:hover {
            color: #c8a97e;
        }

        .date-input:focus {
            outline: none;
            color: #c8a97e;
        }

        /* Simple Flatpickr styling to match the design */
        .flatpickr-calendar {
            font-family: 'Montserrat', sans-serif;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border: none;
        }

        .flatpickr-day.selected {
            background: #c8a97e;
            color: white;
        }

        .flatpickr-day:hover {
            background: rgba(200, 169, 126, 0.2);
            color: #c8a97e;
        }

        /* Ensure date inputs are visible and properly styled */
        .stay-details .value {
            display: flex;
            align-items: center;
        }

        .stay-details .date-input {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .date-edit-icon {
            margin-left: 8px;
            color: #c8a97e;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.7;
        }

        .date-edit-icon:hover {
            opacity: 1;
            color: #b69468;
            transform: scale(1.1);
        }

        .stay-details .value {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .price-updating {
            animation: priceUpdate 0.5s ease-in-out;
        }

        @keyframes priceUpdate {
            0% { background-color: transparent; }
            50% { background-color: #fff3e0; }
            100% { background-color: transparent; }
        }

        .view-booking-detail-btn {
            background-color: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
            width: 100%;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1a1a1a;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .view-booking-detail-btn:hover {
            background-color: #f0f0f0;
            border-color: #c8a97e;
        }

        .view-booking-detail-btn i {
            color: #c8a97e;
        }

        /* Hide the button on desktop */
        .mobile-only {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .booking-container {
                margin-top: 100px;
            }

            .form-section,
            .reservation-summary {
                min-width: 100%;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .hotel-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .hotel-image {
                width: 100%;
                max-width: 200px;
                height: 150px;
            }

            .step-text {
                font-size: 12px;
            }

            .nav-menu {
                display: block !important;
            }

            .mobile-dropdown {
                position: relative;
                display: block;
            }

            .mobile-dropdown-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                background: none;
                border: none;
                padding: 8px 16px;
                font-size: 14px;
                color: #1a1a1a;
                cursor: pointer;
                font-weight: 500;
            }

            .mobile-dropdown-content {
                display: none;
                position: absolute;
                right: 0;
                top: 100%;
                background: white;
                min-width: 200px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border-radius: 8px;
                z-index: 9999;
            }

            .mobile-dropdown.show .mobile-dropdown-content {
                display: block;
            }

            .mobile-dropdown-content a {
                display: block;
                padding: 12px 20px;
                text-decoration: none;
                color: #1a1a1a;
                font-size: 14px;
                border-bottom: 1px solid #f5f5f5;
            }

            .mobile-dropdown-content a:last-child {
                border-bottom: none;
            }

            .mobile-dropdown-content a:hover {
                background: #f8f8f8;
                color: #c8a97e;
            }

            .desktop-nav {
                display: none;
            }
        }

        @media (min-width: 769px) {
            .mobile-dropdown {
                display: none;
            }

            .desktop-nav {
                display: flex;
            }
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
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            max-width: 800px;
            width: 95%;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(30px);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.4s ease;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .confirmation-icon {
            transform: scale(0.8);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.4s ease;
        }

        .modal.show .confirmation-icon {
            transform: scale(1);
            opacity: 1;
            transition-delay: 0.2s;
        }

        .cancellation-details {
            transform: translateY(20px);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.4s ease;
        }

        .modal.show .cancellation-details {
            transform: translateY(0);
            opacity: 1;
            transition-delay: 0.3s;
        }

        .modal .btn {
            transform: translateY(20px);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.4s ease, background-color 0.3s ease;
        }

        .modal.show .btn {
            transform: translateY(0);
            opacity: 1;
            transition-delay: 0.4s;
        }

        #cancellationSuccess {
            transform: translateY(30px);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.4s ease;
        }

        #cancellationSuccess.show {
            transform: translateY(0);
            opacity: 1;
        }

        .success-details {
            transform: translateY(20px);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), opacity 0.4s ease;
        }

        #cancellationSuccess.show .success-details {
            transform: translateY(0);
            opacity: 1;
            transition-delay: 0.3s;
        }

        .reminder-message {
            background-color: #fff3e0;
            border: 1px solid #ffd699;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #c8a97e;
            font-size: 14px;
            animation: fadeIn 0.3s ease-in-out;
        }

        .reminder-message i {
            font-size: 16px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-top">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>" class="logo">Ered Hotel</a>
                <div class="nav-menu">
                    <!-- Desktop Navigation -->
                    <div class="desktop-nav">
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>">Home</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="own_account.php">Hello, <?php echo htmlspecialchars($username); ?></a>
                        <?php else: ?>
                            <a href="login.php">Sign In</a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Dropdown -->
                    <div class="mobile-dropdown">
                        <button type="button" class="mobile-dropdown-btn" onclick="toggleMobileMenu()">
                            Menu <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="mobile-dropdown-content">
                            <a href="<?php echo isset($_SESSION['user_id']) ? 'SignedIn_homepage.php' : 'homepage.php'; ?>">Home</a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="own_account.php">Hello, <?php echo htmlspecialchars($username); ?></a>
                            <?php else: ?>
                                <a href="login.php">Sign In</a>
                            <?php endif; ?>
                            <a href="#" onclick="scrollToBookingDetails(); return false;">View booking detail</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="booking-steps">
            <div class="step-indicator">
                <div class="step-dot active">
                    <span>1</span>
                    <div class="step-text">Customer information</div>
                </div>
                <div class="step-line"></div>
                <div class="step-dot">
                    <span>2</span>
                    <div class="step-text">Payment information</div>
                </div>
                <div class="step-line"></div>
                <div class="step-dot">
                    <span>3</span>
                    <div class="step-text">Booking is confirmed!</div>
                </div>
            </div>
        </div>

        <div class="booking-container">
            <div class="form-section">
                <button onclick="scrollToBookingDetails()" class="view-booking-detail-btn mobile-only">
                    <i class="fas fa-info-circle"></i> View booking detail
                </button>
                <h2 class="section-title">Who's the lead guest?</h2>
                <p class="required-field">*Required field</p>

                <?php if (isset($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin-bottom: 20px;">
                        <p style="margin: 0;"><strong>Please note:</strong> You need to be signed in to complete your booking. 
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: #2196f3; font-weight: bold;">Sign in now</a> or continue filling out this form and sign in at checkout.</p>
                    </div>
                <?php endif; ?>

                <div class="auto-fill-notice" style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px; color: #ffd700;">
                    <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                    <strong>Auto-filled Information:</strong> Your personal details have been automatically filled from your account. You can edit them if needed.
                </div>

                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-field">
                            <label for="first_name">First name <span class="required-field">*</span></label>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" readonly style="background: rgba(30, 41, 59, 0.6); color: #cbd5e1; border: 1px solid rgba(255, 215, 0, 0.2);">
                        </div>
                        <div class="form-field">
                            <label for="last_name">Last name <span class="required-field">*</span></label>
                            <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" readonly style="background: rgba(30, 41, 59, 0.6); color: #cbd5e1; border: 1px solid rgba(255, 215, 0, 0.2);">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="email">Email <span class="required-field">*</span></label>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" readonly style="background: rgba(30, 41, 59, 0.6); color: #cbd5e1; border: 1px solid rgba(255, 215, 0, 0.2);">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="phone">Phone number <span class="required-field">*</span></label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" readonly style="background: rgba(30, 41, 59, 0.6); color: #cbd5e1; border: 1px solid rgba(255, 215, 0, 0.2);">
                        </div>
                    </div>





                    <div class="preference-section">
                        <h3 class="section-title">Room Preferences</h3>
                        <p>Tell us your preferences to help us prepare your perfect stay</p>

                        <div class="preference-group">
                            <div class="preference-title">Room Environment</div>
                            <div class="room-environment-display">
                                <div class="display-item">
                                    <i class="fas fa-smoking-ban" style="color: #c8a97e; margin-right: 8px;"></i>
                                    Smoke-free room
                            </div>
                            </div>
                            <input type="hidden" name="room_preference" value="non-smoking">
                        </div>

                        <div class="preference-group">
                            <div class="preference-title">Checkout Time</div>
                            <div class="checkout-display">
                                <div class="display-item">
                                    <i class="fas fa-clock" style="color: #c8a97e; margin-right: 8px;"></i>
                                    Standard Checkout (12:00 PM) - Free
                            </div>
                            </div>
                            <input type="hidden" name="checkout_time" value="12:00">
                            <input type="hidden" name="room_service" value="none">
                        </div>
                    </div>

                    <button type="submit" class="continue-btn">Payment</button>
                    <p style="text-align: center; margin-top: 10px; font-size: 14px; color: #6b7280;">You won't be charged yet.</p>
                </form>
            </div>

            <div class="reservation-summary">
                <div class="timer">
                    <div class="timer-icon">⏱</div>
                    <div>We are holding your price... <strong id="countdown">20:00</strong></div>
                </div>

                <div class="hotel-info">
                    <div class="hotel-image">
                        <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'images/default-hotel.jpg'); ?>" alt="Hotel Image">
                    </div>
                    <div class="hotel-details">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p class="rating">
                            <?php echo str_repeat('★', round($hotel['avg_rating'])); ?>
                        </p>
                        <p><?php echo $avg_rating; ?> Excellent</p>
                        <p><?php echo $review_count; ?> reviews</p>
                    </div>
                </div>

                <div class="stay-details">
                    <div class="section-title">Your stay</div>
                    <p>
                        <span class="label">Check-in:</span>
                        <span class="value">
                            <input type="text" id="checkin_date" name="checkin_date" value="<?php echo htmlspecialchars($checkin); ?>" class="date-input" placeholder="Check-in" readonly>
                            <i class="fas fa-edit date-edit-icon" title="Click to edit date"></i>
                        </span>
                    </p>
                    <p>
                        <span class="label">Check-out:</span>
                        <span class="value">
                            <input type="text" id="checkout_date" name="checkout_date" value="<?php echo htmlspecialchars($checkout); ?>" class="date-input" placeholder="Check-out" readonly>
                            <i class="fas fa-edit date-edit-icon" title="Click to edit date"></i>
                        </span>
                    </p>
                    <p>
                        <span class="label">Total length of stay:</span>
                        <span class="value" id="nights_display"><?php echo $nights; ?> night</span>
                    </p>
                    <p>
                        <span class="label">You selected:</span>
                        <span class="value">
                            <?php echo htmlspecialchars($room_type); ?>
                            <?php
                            // Deals table no longer exists
                            ?>
                        </span>
                    </p>
                </div>

                <div class="price-details">
                    <div class="section-title">Price</div>
                    <div class="price-row">
                        <span>Room (<?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>)</span>
                        <span>RM <?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <div class="price-row" id="late-checkout-fee" style="display: none;">
                        <span>Late Checkout Fee</span>
                        <span id="late-checkout-amount">RM 0.00</span>
                    </div>
                    <div class="price-row" id="room-service-fee" style="display: none;">
                        <span>Room Service Fee</span>
                        <span id="room-service-amount">RM 0.00</span>
                    </div>
                    <div class="price-row total">
                        <span>Total price</span>
                        <span id="final-total">RM <?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <p style="font-size: 14px; color: #6b7280; margin-top: 5px;">Includes taxes and fees</p>
                </div>


            </div>
        </div>
    </div>

    <!-- Cancellation Confirmation Modal -->
    <div id="cancellationModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeCancellationModal">&times;</span>
            <div id="cancellationStep1">
                <div class="confirmation-icon warning">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #e74c3c; margin-bottom: 20px;"></i>
                </div>
                <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 15px;">Cancel Booking</h2>
                <p style="margin-bottom: 20px; color: #666;">Are you sure you want to cancel this booking? This action cannot be undone.</p>
                <div class="cancellation-details" style="background: #f8f8f8; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <p><strong>Please note:</strong></p>
                    <ul style="list-style-type: none; padding-left: 0; margin-top: 10px;">
                        <li style="margin-bottom: 10px;"><i class="fas fa-times" style="color: #e74c3c; margin-right: 10px;"></i> Your reservation will be permanently cancelled</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-times" style="color: #e74c3c; margin-right: 10px;"></i> Any payments made may be subject to cancellation policies</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-times" style="color: #e74c3c; margin-right: 10px;"></i> Room availability cannot be guaranteed if you change your mind</li>
                    </ul>
                </div>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="btn outline" id="closeModalBtn">No, Keep Booking</button>
                    <button type="button" class="btn danger" id="proceedCancellationBtn">Yes, Cancel Booking</button>
                </div>
            </div>
            <div id="cancellationSuccess" style="display: none;">
                <div class="confirmation-icon success">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #27ae60; margin-bottom: 20px;"></i>
                </div>
                <h2 style="font-family: 'Cormorant Garamond', serif; color: #1a1a1a; margin-bottom: 15px;">Booking Cancelled Successfully</h2>
                <p style="margin-bottom: 20px; color: #666;">Your booking has been cancelled and you will receive a confirmation email shortly.</p>
                <div class="success-details" style="background: #f0f9f4; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <p><strong>What happens next:</strong></p>
                    <ul style="list-style-type: none; padding-left: 0; margin-top: 10px;">
                        <li style="margin-bottom: 10px;"><i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i> A cancellation confirmation will be sent to your email</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i> Any eligible refund will be processed within 5-7 business days</li>
                        <li style="margin-bottom: 10px;"><i class="fas fa-check" style="color: #27ae60; margin-right: 10px;"></i> You can make a new booking anytime</li>
                    </ul>
                </div>
                <button type="button" class="btn" id="closeSuccessBtn" style="background-color: #27ae60;">Close</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Countdown Timer functionality
        function startCountdown() {
            // Set initial time to 10 minutes (in seconds)
            let timeLeft = 10 * 60;
            
            const countdownElement = document.getElementById('countdown');
            
            // Clear any existing interval
            if (window.timerInterval) {
                clearInterval(window.timerInterval);
            }
            
            // Clear any existing time in sessionStorage
            sessionStorage.removeItem('bookingTimeLeft');
            
            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                // Format the time to always show two digits
                const formattedTime = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                countdownElement.textContent = formattedTime;
                
                if (timeLeft > 0) {
                    timeLeft--;
                    // Save the current time in sessionStorage
                    sessionStorage.setItem('bookingTimeLeft', timeLeft);
                } else {
                    clearInterval(window.timerInterval);
                    // Redirect to homepage or show expired message
                    alert('Your booking session has expired. You will be redirected to the homepage.');
                    window.location.href = 'homepage.php';
                }
            }
            
            // Update timer immediately and then every second
            updateTimer();
            window.timerInterval = setInterval(updateTimer, 1000);
            
            // Clear timer when leaving the page
            window.addEventListener('beforeunload', function() {
                clearInterval(window.timerInterval);
            });
        }
        
        // Price calculation functionality
        function updateTotalPrice() {
            const checkinInput = document.getElementById('checkin_date');
            const checkoutInput = document.getElementById('checkout_date');
            
            if (!checkinInput || !checkoutInput || !checkinInput.value || !checkoutInput.value) {
                return;
            }
            
            // Calculate number of nights
            const checkin = new Date(checkinInput.value);
            const checkout = new Date(checkoutInput.value);
            const timeDiff = checkout.getTime() - checkin.getTime();
            const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            // Get base price per night from PHP
            const pricePerNight = <?php echo $room['price_per_night'] ?? 0; ?>;
            
            // Calculate total price based on nights
            const total = pricePerNight * nights;

            // Hide additional fee rows since we only have standard checkout and no room service
            const lateCheckoutRow = document.getElementById('late-checkout-fee');
            const roomServiceRow = document.getElementById('room-service-fee');
            
            if (lateCheckoutRow) lateCheckoutRow.style.display = 'none';
            if (roomServiceRow) roomServiceRow.style.display = 'none';

            // Update room price row
            const roomPriceRow = document.querySelector('.price-row:not(.total)');
            if (roomPriceRow) {
                const nightsText = nights === 1 ? 'night' : 'nights';
                roomPriceRow.innerHTML = `<span>Room (${nights} ${nightsText})</span><span><?php echo $currency; ?>${total.toFixed(2)}</span>`;
                
                // Add animation class
                roomPriceRow.classList.add('price-updating');
                setTimeout(() => {
                    roomPriceRow.classList.remove('price-updating');
                }, 500);
            }

            // Update final total
            const finalTotalElement = document.getElementById('final-total');
            if (finalTotalElement) {
                finalTotalElement.textContent = `<?php echo $currency; ?>${total.toFixed(2)}`;
                
                // Add animation class
                finalTotalElement.classList.add('price-updating');
                setTimeout(() => {
                    finalTotalElement.classList.remove('price-updating');
                }, 500);
            }
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();
            
            // Initial price calculation
            updateTotalPrice();
            
            // Initialize date inputs
            initializeDateInputs();
            
            // Add click handlers for edit icons
            addEditIconHandlers();
        });

        // Function to add click handlers for edit icons
        function addEditIconHandlers() {
            const checkinEditIcon = document.querySelector('#checkin_date').nextElementSibling;
            const checkoutEditIcon = document.querySelector('#checkout_date').nextElementSibling;
            
            if (checkinEditIcon && checkinEditIcon.classList.contains('date-edit-icon')) {
                checkinEditIcon.addEventListener('click', function() {
                    if (window.checkinPicker) {
                        window.checkinPicker.open();
                    }
                });
            }
            
            if (checkoutEditIcon && checkoutEditIcon.classList.contains('date-edit-icon')) {
                checkoutEditIcon.addEventListener('click', function() {
                    if (window.checkoutPicker) {
                        window.checkoutPicker.open();
                    }
                });
            }
        }

        // Function to initialize date inputs with Flatpickr
        function initializeDateInputs() {
            const checkinInput = document.getElementById('checkin_date');
            const checkoutInput = document.getElementById('checkout_date');
            
            if (checkinInput && checkoutInput) {
                // Set default values if empty or invalid
                if (!checkinInput.value || checkinInput.value === '1970-01-01') {
                    const today = new Date().toISOString().split('T')[0];
                    checkinInput.value = today;
                }
                if (!checkoutInput.value || checkoutInput.value === '1970-01-01') {
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    checkoutInput.value = tomorrow.toISOString().split('T')[0];
                }
                
                // Initialize Flatpickr for check-in date
                const checkinPicker = flatpickr(checkinInput, {
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    disableMobile: true,
                    onChange: function(selectedDates) {
                        if (selectedDates[0]) {
                            const nextDay = new Date(selectedDates[0]);
                            nextDay.setDate(nextDay.getDate() + 1);
                            checkoutPicker.set('minDate', nextDay);
                            
                            if (checkoutPicker.selectedDates[0] && checkoutPicker.selectedDates[0] <= selectedDates[0]) {
                                checkoutPicker.setDate(nextDay);
                            }
                        }
                        updateStayDetails();
                    }
                });

                // Initialize Flatpickr for check-out date
                const checkoutPicker = flatpickr(checkoutInput, {
                    minDate: new Date().fp_incr(1),
                    dateFormat: "Y-m-d",
                    disableMobile: true,
                    onChange: function(selectedDates) {
                        updateStayDetails();
                    }
                });

                // Store picker instances for later use
                window.checkinPicker = checkinPicker;
                window.checkoutPicker = checkoutPicker;
            }
        }

        // Close modal handlers
        if (closeReceiptModal) {
            closeReceiptModal.onclick = function() {
                receiptModal.classList.remove('show');
                setTimeout(() => {
                    receiptModal.style.display = "none";
                    receiptImageContainer.innerHTML = '<p>Loading receipt...</p>';
                    currentReceiptPath = '';
                }, 400);
            }
        }

        if (closeCancellationModal) {
            closeCancellationModal.onclick = function() {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                    cancellationModal.style.display = "none";
                }, 400);
            }
        }

        if (closeModalBtn) {
            closeModalBtn.onclick = function() {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                    cancellationModal.style.display = "none";
                }, 400);
            }
        }

        // Update window click handler
        window.onclick = function(event) {
            if (event.target == receiptModal) {
                receiptModal.classList.remove('show');
                setTimeout(() => {
                    receiptModal.style.display = "none";
                }, 400);
            }
            if (event.target == cancellationModal) {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                    cancellationModal.style.display = "none";
                }, 400);
            }
            if (event.target == bookingDetailsModal) {
                bookingDetailsModal.classList.remove('show');
                setTimeout(() => {
                    bookingDetailsModal.style.display = "none";
                }, 400);
            }
        }

        // Update the proceedCancellationBtn click handler
        if (proceedCancellationBtn) {
            proceedCancellationBtn.onclick = function() {
                if (currentBookingId) {
                    const formData = new FormData();
                    formData.append('booking_id', currentBookingId);
                    
                    fetch('cancel_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hide cancellation step 1
                            document.getElementById('cancellationStep1').style.display = 'none';
                            
                            // Show and animate success step
                            const successStep = document.getElementById('cancellationSuccess');
                            successStep.style.display = 'block';
                            setTimeout(() => {
                                successStep.classList.add('show');
                            }, 50);

                            // Remove the cancelled booking card with smooth animation
                            const bookingCard = document.querySelector(`.cancel-booking[data-booking="${currentBookingId}"]`).closest('.booking-card');
                            if (bookingCard) {
                                bookingCard.classList.add('fade-out');
                                setTimeout(() => {
                                    bookingCard.remove();
                                    
                                    // Update booking counts
                                    const pendingCount = document.querySelector('.tab[onclick*="pending"] .booking-count');
                                    if (pendingCount) {
                                        let count = parseInt(pendingCount.textContent);
                                        pendingCount.textContent = Math.max(0, count - 1);
                                        
                                        // Check if this was the last booking
                                        if (count === 1) {
                                            // Add the no-bookings message with a fade-in effect
                                            const pendingTab = document.getElementById('pending-tab');
                                            const noBookingsHtml = `
                                                <div class="no-bookings" style="opacity: 0; transform: translateY(20px);">
                                                    <div class="no-bookings-icon"><i class="far fa-calendar-xmark"></i></div>
                                                    <h3>No Pending Bookings</h3>
                                                    <p>You don't have any bookings that are pending confirmation.</p>
                                                </div>
                                            `;
                                            pendingTab.insertAdjacentHTML('beforeend', noBookingsHtml);
                                            
                                            // Trigger the fade-in animation
                                            setTimeout(() => {
                                                const noBookings = pendingTab.querySelector('.no-bookings');
                                                noBookings.style.transition = 'all 0.3s ease';
                                                noBookings.style.opacity = '1';
                                                noBookings.style.transform = 'translateY(0)';
                                            }, 50);
                                        }
                                    }
                                }, 300);
                            }
                        } else {
                            alert(data.message || 'Failed to cancel booking. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while cancelling the booking. Please try again.');
                    });
                }
            }
        }

        // Add close success button handler
        document.getElementById('closeSuccessBtn').onclick = function() {
            cancellationModal.classList.remove('show');
            setTimeout(() => {
                cancellationModal.style.display = "none";
                // Reset modal state for next time
                document.getElementById('cancellationSuccess').style.display = 'none';
                document.getElementById('cancellationSuccess').classList.remove('show');
                document.getElementById('cancellationStep1').style.display = 'block';
            }, 400);
        }

        // Update the close modal handlers to reset the modal state
        if (closeCancellationModal) {
            closeCancellationModal.onclick = function() {
                cancellationModal.classList.remove('show');
                setTimeout(() => {
                    cancellationModal.style.display = "none";
                    // Reset modal state for next time
                    document.getElementById('cancellationSuccess').style.display = 'none';
                    document.getElementById('cancellationSuccess').classList.remove('show');
                    document.getElementById('cancellationStep1').style.display = 'block';
                }, 400);
            }
        }

        // Simplified mobile dropdown functionality
        function toggleMobileMenu() {
            const dropdown = document.querySelector('.mobile-dropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.mobile-dropdown');
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Prevent dropdown from closing when clicking inside
        document.querySelector('.mobile-dropdown').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Add scroll to booking details function
        function scrollToBookingDetails() {
            const timerSection = document.querySelector('.timer');
            if (timerSection) {
                // Get the header height to offset the scroll
                const header = document.querySelector('.header');
                const headerHeight = header ? header.offsetHeight : 0;
                
                // Calculate the position to scroll to
                const elementPosition = timerSection.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerHeight - 20; // 20px extra padding
                
                // Smooth scroll with easing
                const startPosition = window.pageYOffset;
                const distance = offsetPosition - startPosition;
                const duration = 800; // Duration in milliseconds
                let start = null;
                
                function animation(currentTime) {
                    if (start === null) start = currentTime;
                    const timeElapsed = currentTime - start;
                    const progress = Math.min(timeElapsed / duration, 1);
                    
                    // Easing function for smooth acceleration and deceleration
                    const ease = t => t<.5 ? 2*t*t : -1+(4-2*t)*t;
                    
                    window.scrollTo(0, startPosition + (distance * ease(progress)));
                    
                    if (timeElapsed < duration) {
                        requestAnimationFrame(animation);
                    }
                }
                
                requestAnimationFrame(animation);
                
                // Close mobile menu after scrolling
                document.querySelector('.mobile-dropdown').classList.remove('show');
            }
        }

        // Function to update stay details when dates change
        function updateStayDetails() {
            const checkinInput = document.getElementById('checkin_date');
            const checkoutInput = document.getElementById('checkout_date');
            
            if (checkinInput && checkoutInput && checkinInput.value && checkoutInput.value) {
                // Parse dates from Y-m-d format
                const checkin = new Date(checkinInput.value);
                const checkout = new Date(checkoutInput.value);
                
                // Validate dates
                if (checkout <= checkin) {
                    alert('Check-out date must be after check-in date');
                    return;
                }
                
                // Calculate nights
                const timeDiff = checkout.getTime() - checkin.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                // Update nights display
                const nightsDisplay = document.getElementById('nights_display');
                if (nightsDisplay) {
                    nightsDisplay.textContent = nights + (nights === 1 ? ' night' : ' nights');
                }
                
                // Update price calculation
                updateTotalPrice();
                
                // Update URL parameters
                const url = new URL(window.location);
                url.searchParams.set('checkin', checkinInput.value);
                url.searchParams.set('checkout', checkoutInput.value);
                url.searchParams.set('nights', nights);
                window.history.replaceState({}, '', url);
            }
        }



        // Add highlight animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes highlight {
                0% { background-color: transparent; }
                50% { background-color: #fff3e0; }
                100% { background-color: transparent; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

<?php
$conn->close();
?> 