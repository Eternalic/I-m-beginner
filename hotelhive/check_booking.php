<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID not provided']);
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if booking exists and belongs to the user
    $query = "SELECT booking_status FROM bookings WHERE booking_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    // Check if booking is in pending status
    if ($booking['booking_status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Booking is not in pending status']);
        exit;
    }
    
    // Check if payment already exists
    $payment_query = "SELECT payment_id FROM payments WHERE booking_id = ?";
    $payment_stmt = $conn->prepare($payment_query);
    $payment_stmt->bind_param("i", $booking_id);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    
    if ($payment_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Payment already exists for this booking']);
        exit;
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error']);
}

$conn->close();
?> 