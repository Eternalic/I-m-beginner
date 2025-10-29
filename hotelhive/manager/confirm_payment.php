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
    // Start transaction
    $conn->begin_transaction();
    
    // First, verify that this booking belongs to this manager's hotel
    $verify_sql = "SELECT b.booking_id, b.hotel_id, b.booking_status
                   FROM bookings b 
                   WHERE b.booking_id = ? AND b.hotel_id = ?";
    
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $booking_id, $hotel_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header("Location: bookings.php?error=booking_not_found");
        exit();
    }
    
    $booking_data = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    // Check if booking is pending
    if ($booking_data['booking_status'] !== 'pending') {
        header("Location: bookings.php?error=booking_not_pending");
        exit();
    }
    
    // Update booking status to confirmed
    $update_booking_sql = "UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = ?";
    $update_stmt = $conn->prepare($update_booking_sql);
    $update_stmt->bind_param("i", $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update booking status");
    }
    
    $update_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to bookings with success message
    header("Location: bookings.php?success=booking_confirmed");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error confirming booking: " . $e->getMessage());
    header("Location: bookings.php?error=system_error");
    exit();
}
?>