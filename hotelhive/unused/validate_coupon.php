<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get parameters
$coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$hotel_id = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;

// Validate parameters
if (!$coupon_id || !$booking_id || !$room_id || !$hotel_id || !$user_id || !$total_amount) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get coupon details
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = c.coupon_id) as total_usage,
               (SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = c.coupon_id AND user_id = ?) as user_usage
        FROM coupons c
        WHERE c.coupon_id = ? AND c.status = 'active'
    ");
    $stmt->bind_param("ii", $user_id, $coupon_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Invalid or inactive coupon");
    }

    $coupon = $result->fetch_assoc();

    // Validate coupon
    if ($coupon['min_purchase_amount'] > $total_amount) {
        throw new Exception("Minimum purchase amount not met");
    }

    if ($coupon['usage_limit'] && $coupon['total_usage'] >= $coupon['usage_limit']) {
        throw new Exception("Coupon usage limit reached");
    }

    if ($coupon['per_user_limit'] && $coupon['user_usage'] >= $coupon['per_user_limit']) {
        throw new Exception("Your coupon usage limit reached");
    }

    if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > time()) {
        throw new Exception("Coupon not yet valid");
    }

    if ($coupon['valid_to'] && strtotime($coupon['valid_to']) < time()) {
        throw new Exception("Coupon has expired");
    }

    // Check room and hotel restrictions
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM coupon_rooms 
        WHERE coupon_id = ? AND room_id = ?
    ");
    $stmt->bind_param("ii", $coupon_id, $room_id);
    $stmt->execute();
    $room_result = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM coupon_hotels 
        WHERE coupon_id = ? AND hotel_id = ?
    ");
    $stmt->bind_param("ii", $coupon_id, $hotel_id);
    $stmt->execute();
    $hotel_result = $stmt->get_result()->fetch_assoc();

    if ($room_result['count'] == 0 && $hotel_result['count'] == 0) {
        // Check if coupon is public
        if (!$coupon['is_public']) {
            throw new Exception("Coupon not applicable for this booking");
        }
    }

    // Calculate discount
    $discount_amount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount_amount = $total_amount * ($coupon['discount_value'] / 100);
        if ($coupon['max_discount_amount'] && $discount_amount > $coupon['max_discount_amount']) {
            $discount_amount = $coupon['max_discount_amount'];
        }
    } else {
        $discount_amount = $coupon['discount_value'];
    }

    // Record coupon usage
    $stmt = $conn->prepare("
        INSERT INTO coupon_usage (coupon_id, user_id, booking_id, used_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iii", $coupon_id, $user_id, $booking_id);
    $stmt->execute();

    // Update booking with discount
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET discount_amount = ?, 
            final_price = total_price - ?
        WHERE booking_id = ?
    ");
    $stmt->bind_param("ddi", $discount_amount, $discount_amount, $booking_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Coupon applied successfully',
        'discount_amount' => $discount_amount,
        'final_price' => $total_amount - $discount_amount,
        'coupon' => [
            'code' => $coupon['coupon_code'],
            'description' => $coupon['description'],
            'valid_from' => $coupon['valid_from'],
            'valid_to' => $coupon['valid_to']
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 