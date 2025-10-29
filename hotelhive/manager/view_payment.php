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

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header("Location: manage_payments.php?error=invalid_booking_id");
    exit();
}

// Redirect to view booking details since payments table no longer exists
header("Location: view_booking.php?id=" . $booking_id);
    exit();
?>