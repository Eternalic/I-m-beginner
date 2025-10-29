<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if booking ID is provided
if (!isset($_POST['booking_id']) || !is_numeric($_POST['booking_id'])) {
    header("Location: manage_bookings.php?error=invalid_booking_id");
    exit();
}

$booking_id = (int)$_POST['booking_id'];
$payment_method = $_POST['payment_method'] ?? '';

if (empty($payment_method)) {
    header("Location: manage_bookings.php?error=invalid_payment_method");
    exit();
}

try {
    // Get booking details
    $booking_sql = "SELECT b.*, h.name as hotel_name, h.location, h.city, 
                           r.room_type, r.price_per_night, r.max_guests,
                           u.first_name, u.last_name, u.email, u.phone
                    FROM bookings b
                    JOIN hotels h ON b.hotel_id = h.hotel_id
                    JOIN rooms r ON b.room_id = r.room_id
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.booking_id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($booking_sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage_bookings.php?error=booking_not_found");
        exit();
    }
    
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Validate booking status
    if ($booking['booking_status'] !== 'pending') {
        throw new Exception("Booking is not available for confirmation");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate transaction ID
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        
        // Set booking status based on payment method
        $booking_status = ($payment_method === 'cash') ? 'pending' : 'confirmed';
        
        // Update booking status
        $update_sql = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $booking_status, $booking_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update booking status");
        }
        
        $update_stmt->close();
        
        // For online payments, confirm immediately
        if ($payment_method === 'online') {
            // Generate receipt image only for online payments
            $receipt_filename = 'receipt_' . $booking_id . '_' . time() . '.jpg';
            $receipt_path = 'images/receipts/' . $receipt_filename;
            
            // Create receipts directory if it doesn't exist
            if (!file_exists('images/receipts/')) {
                mkdir('images/receipts/', 0777, true);
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
            imagestring($image, 5, 50, $y, $booking['hotel_name'], $blue);
            $y += 40;
            
            // Hotel address
            imagestring($image, 3, 50, $y, $booking['location'] . ', ' . $booking['city'], $gray);
            $y += 30;
            
            // Receipt title
            imagestring($image, 4, 50, $y, 'BOOKING RECEIPT', $black);
            $y += 50;
            
            // Booking details
            imagestring($image, 3, 50, $y, 'Booking Number: ' . $booking['book_number'], $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Guest: ' . $booking['first_name'] . ' ' . $booking['last_name'], $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Email: ' . $booking['email'], $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Phone: ' . $booking['phone'], $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Room Type: ' . $booking['room_type'], $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Check-in: ' . date('M d, Y', strtotime($booking['check_in_date'])), $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Check-out: ' . date('M d, Y', strtotime($booking['check_out_date'])), $black);
            $y += $line_height;
            
            imagestring($image, 3, 50, $y, 'Total Amount: RM ' . number_format($booking['total_price'], 2), $black);
            $y += 50;
            
            // Footer
            imagestring($image, 2, 50, $y, 'Thank you for choosing ' . $booking['hotel_name'], $gray);
            $y += 20;
            imagestring($image, 2, 50, $y, 'Generated on: ' . date('M d, Y H:i:s'), $gray);
            
            // Save image
            imagejpeg($image, $receipt_path, 90);
            imagedestroy($image);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to confirmation page
        header("Location: booking_confirmation.php?booking_id=" . $booking_id . "&status=" . $booking_status . "&method=" . $payment_method);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    header("Location: manage_bookings.php?error=payment_failed");
    exit();
}
?>