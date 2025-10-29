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

// Get amenity ID from URL
$amenity_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($amenity_id <= 0) {
    header("Location: rooms.php");
    exit();
}

// Verify amenity belongs to manager's hotel
$verify_sql = "SELECT ra.a_id 
               FROM room_amenities ra 
               JOIN rooms r ON ra.room_id = r.room_id 
               WHERE ra.a_id = ? AND r.hotel_id = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("ii", $amenity_id, $hotel_id);
$stmt->execute();
$verify_result = $stmt->get_result();

if ($verify_result->num_rows === 0) {
    $_SESSION['error_message'] = "Amenity not found or you don't have permission to delete it.";
    header("Location: rooms.php");
    exit();
}

// Delete amenity
$delete_sql = "DELETE FROM room_amenities WHERE a_id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("i", $amenity_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Amenity has been deleted successfully!";
} else {
    $_SESSION['error_message'] = "Failed to delete amenity. Please try again.";
}

header("Location: rooms.php");
exit(); 