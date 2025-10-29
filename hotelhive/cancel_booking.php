<?php
session_start();
require_once 'db.php';

// Set header to return JSON
header('Content-Type: application/json');

// Function to return JSON response
function sendResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'User not logged in');
}

// Check if booking ID is provided
if (!isset($_POST['booking_id'])) {
    sendResponse(false, 'No booking specified');
}

$booking_id = (int)$_POST['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    // First verify that this booking belongs to the current user and is not already cancelled
    $verify_sql = "SELECT booking_id, booking_status FROM bookings 
                  WHERE booking_id = ? AND user_id = ? AND booking_status != 'cancelled'";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $verify_stmt->close();
        
        // Check if booking exists but is already cancelled
        $check_cancelled_sql = "SELECT booking_status FROM bookings WHERE booking_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_cancelled_sql);
        $check_stmt->bind_param("ii", $booking_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $booking_data = $check_result->fetch_assoc();
            if ($booking_data['booking_status'] === 'cancelled') {
                sendResponse(false, 'This booking is already cancelled');
            }
        }
        
        sendResponse(false, 'Invalid booking or not authorized to cancel this booking');
    }
    
    // Update booking status to cancelled
    $update_sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($update_stmt->execute()) {
        if ($update_stmt->affected_rows > 0) {
            sendResponse(true, 'Booking cancelled successfully');
        } else {
            sendResponse(false, 'No changes made to booking');
        }
    } else {
        sendResponse(false, 'Failed to cancel booking: ' . $conn->error);
    }
    
} catch(Exception $e) {
    sendResponse(false, 'System error occurred: ' . $e->getMessage());
} finally {
    if (isset($verify_stmt)) $verify_stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    $conn->close();
} 