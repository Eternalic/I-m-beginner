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

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

try {
    // Get booking details
    $booking_query = "
        SELECT b.*, h.name as hotel_name, h.location, h.city, h.country, r.room_type, r.price_per_night, r.max_guests
        FROM bookings b
        JOIN hotels h ON b.hotel_id = h.hotel_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ";
    
    $stmt = $conn->prepare($booking_query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking_result = $stmt->get_result();
    
    if ($booking_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    $booking = $booking_result->fetch_assoc();
    
    // Since we only have cash payments, create payment info structure
    $payment = [
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'amount' => $booking['total_price'],
        'receipt_img' => null,
        'paid_at' => null
    ];
    
    echo json_encode([
        'success' => true,
        'booking' => $booking,
        'payment' => $payment
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching booking details: ' . $e->getMessage()
    ]);
}
?> 