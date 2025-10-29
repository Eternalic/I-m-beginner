<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get parameters
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$total_amount = isset($_GET['total_amount']) ? floatval($_GET['total_amount']) : 0;

// Validate parameters
if (!$booking_id || !$room_id || !$hotel_id || !$user_id || !$total_amount) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    // Get available coupons that match the criteria
    $query = "SELECT DISTINCT c.* 
        FROM coupons c
        LEFT JOIN coupon_rooms cr ON c.coupon_id = cr.coupon_id
        LEFT JOIN coupon_hotels ch ON c.coupon_id = ch.coupon_id
        WHERE c.status = 'active'
        AND c.min_purchase_amount <= ?
        AND (
            c.is_public = 1 
            OR cr.room_id = ? 
            OR ch.hotel_id = ?
        )
        AND (
            c.usage_limit IS NULL OR 
            c.usage_limit > (
                SELECT COUNT(*) FROM coupon_usage 
                WHERE coupon_id = c.coupon_id
            )
        )
        AND (
            c.per_user_limit IS NULL OR 
            c.per_user_limit > (
                SELECT COUNT(*) FROM coupon_usage 
                WHERE coupon_id = c.coupon_id AND user_id = ?
            )
        )
        AND (
            c.valid_from IS NULL OR c.valid_from <= CURRENT_DATE
        )
        AND (
            c.valid_to IS NULL OR c.valid_to >= CURRENT_DATE
        )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("diii", $total_amount, $room_id, $hotel_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $coupons = [];
    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row;
    }

    if (empty($coupons)) {
        echo json_encode([
            'success' => true,
            'message' => 'No coupons available for this booking',
            'coupons' => []
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'coupons' => $coupons
        ]);
    }

} catch (Exception $e) {
    error_log("Error in get_available_coupons.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching coupons: ' . $e->getMessage()
    ]);
}
?> 