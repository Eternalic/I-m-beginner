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

// Check if POST data is provided
if (!isset($_POST['booking_id']) || !isset($_POST['new_status'])) {
    header("Location: bookings.php?error=invalid_data");
    exit();
}

$booking_id = (int)$_POST['booking_id'];
$new_status = $_POST['new_status'];

// Validate status
$valid_statuses = ['pending', 'confirmed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    header("Location: bookings.php?error=invalid_status");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // First, verify that this booking belongs to this manager's hotel
    $verify_sql = "SELECT booking_id, booking_status FROM bookings WHERE booking_id = ? AND hotel_id = ?";
    
    $verify_stmt = $conn->prepare($verify_sql);
    if (!$verify_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $verify_stmt->bind_param("ii", $booking_id, $hotel_id);
    if (!$verify_stmt->execute()) {
        throw new Exception("Execute failed: " . $verify_stmt->error);
    }
    
    $verify_result = $verify_stmt->get_result();
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header("Location: bookings.php?error=booking_not_found");
        exit();
    }
    
    $booking_data = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    // Update booking status
    $update_sql = "UPDATE bookings SET booking_status = ? WHERE booking_id = ? AND hotel_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("sii", $new_status, $booking_id, $hotel_id);
    if (!$update_stmt->execute()) {
        throw new Exception("Execute failed: " . $update_stmt->error);
    }
    
    // Payment table no longer exists, so no additional updates needed
    
    // Commit transaction
    $conn->commit();
    
    // Close statement
    $update_stmt->close();
    
    // Redirect with success message
    header("Location: view_booking.php?id=" . $booking_id . "&success=booking_updated");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Close statements if they exist
    if (isset($update_stmt)) {
        $update_stmt->close();
    }
    
    // Log error
    error_log("Update Booking Status Error: " . $e->getMessage());
    
    // Redirect with error message
    header("Location: view_booking.php?id=" . $booking_id . "&error=update_failed");
    exit();
}

$conn->close();
?>
