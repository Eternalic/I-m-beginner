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

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: rooms.php");
    exit();
}

// Get the room ID and new status from POST data
$room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';

// Validate inputs
if ($room_id <= 0 || !in_array($new_status, ['0', '1', '2', '3'])) {
    $_SESSION['error_message'] = "Invalid room ID or status.";
    header("Location: rooms.php");
    exit();
}

try {
    // First, verify that the room belongs to the manager's hotel
    $verify_sql = "SELECT room_id FROM rooms WHERE room_id = ? AND hotel_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $room_id, $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Room not found or you don't have permission to modify this room.";
        header("Location: rooms.php");
        exit();
    }
    
    // Update the room status
    $update_sql = "UPDATE rooms SET availability = ? WHERE room_id = ? AND hotel_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sii", $new_status, $room_id, $hotel_id);
    
    if ($stmt->execute()) {
        // Determine status text for success message
        $status_text = '';
        switch($new_status) {
            case '1': $status_text = 'Available'; break;
            case '0': $status_text = 'Occupied'; break;
            case '2': $status_text = 'Maintenance'; break;
            case '3': $status_text = 'Cleaning'; break;
        }
        
        $_SESSION['success_message'] = "Room status successfully changed to: " . $status_text;
    } else {
        $_SESSION['error_message'] = "Failed to update room status. Please try again.";
    }
    
} catch (Exception $e) {
    error_log("Error updating room status: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while updating the room status.";
}

// Redirect back to rooms.php
header("Location: rooms.php");
exit();
?>
