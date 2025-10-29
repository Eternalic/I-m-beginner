<?php
session_start();
require_once '../db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: manager_login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];
$hotel_id = $_SESSION['hotel_id'];

// Check if booking ID is provided
if (!isset($_POST['booking_id']) || !is_numeric($_POST['booking_id'])) {
    header("Location: bookings.php?error=invalid_booking_id");
    exit();
}

$booking_id = (int)$_POST['booking_id'];

try {
    // Verify that this booking belongs to this manager's hotel and is confirmed
    $verify_sql = "SELECT b.*, h.name as hotel_name, h.location, h.city, r.room_type, 
                          u.first_name, u.last_name, u.email, u.phone
                   FROM bookings b 
                   JOIN hotels h ON b.hotel_id = h.hotel_id
                   LEFT JOIN rooms r ON b.room_id = r.room_id
                   LEFT JOIN users u ON b.user_id = u.user_id
                   WHERE b.booking_id = ? AND b.hotel_id = ? AND b.booking_status = 'confirmed'";
    
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $booking_id, $hotel_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header("Location: bookings.php?error=booking_not_found_or_not_confirmed");
        exit();
    }
    
    $booking_data = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    // Generate receipt image
    $receipt_filename = 'receipt_' . $booking_id . '_' . time() . '.jpg';
    $receipt_path = '../images/receipts/' . $receipt_filename;
    
    // Create receipts directory if it doesn't exist
    if (!file_exists('../images/receipts/')) {
        mkdir('../images/receipts/', 0777, true);
    }
    
    // Generate receipt using GD library
    $width = 600;
    $height = 800;
    $image = imagecreate($width, $height);
    
    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $blue = imagecolorallocate($image, 102, 126, 234);
    $gray = imagecolorallocate($image, 128, 128, 128);
    
    // Fill background
    imagefill($image, 0, 0, $white);
    
    // Add content
    $y = 50;
    $line_height = 25;
    
    // Hotel name
    imagestring($image, 5, 50, $y, $booking_data['hotel_name'], $blue);
    $y += 40;
    
    // Hotel address
    imagestring($image, 3, 50, $y, $booking_data['location'] . ', ' . $booking_data['city'], $gray);
    $y += 30;
    
    // Receipt title
    imagestring($image, 4, 50, $y, 'BOOKING RECEIPT', $black);
    $y += 50;
    
    // Booking details
    imagestring($image, 3, 50, $y, 'Booking Number: ' . $booking_data['book_number'], $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Guest: ' . $booking_data['first_name'] . ' ' . $booking_data['last_name'], $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Email: ' . $booking_data['email'], $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Phone: ' . $booking_data['phone'], $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Room Type: ' . $booking_data['room_type'], $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Check-in: ' . date('M d, Y', strtotime($booking_data['check_in_date'])), $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Check-out: ' . date('M d, Y', strtotime($booking_data['check_out_date'])), $black);
    $y += $line_height;
    
    imagestring($image, 3, 50, $y, 'Total Amount: RM ' . number_format($booking_data['total_price'], 2), $black);
    $y += 50;
    
    // Footer
    imagestring($image, 2, 50, $y, 'Thank you for choosing ' . $booking_data['hotel_name'], $gray);
    $y += 20;
    imagestring($image, 2, 50, $y, 'Generated on: ' . date('M d, Y H:i:s'), $gray);
    
    // Save image
    imagejpeg($image, $receipt_path, 90);
    imagedestroy($image);
    
    // Redirect back to bookings with success message
    header("Location: bookings.php?success=receipt_generated&receipt=" . urlencode($receipt_filename));
    exit();
    
} catch (Exception $e) {
    error_log("Error generating receipt: " . $e->getMessage());
    header("Location: bookings.php?error=receipt_generation_failed");
    exit();
}
?>