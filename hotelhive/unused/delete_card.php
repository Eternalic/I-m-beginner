<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if card_id is provided
if (!isset($_POST['card_id'])) {
    echo json_encode(['success' => false, 'message' => 'No card specified']);
    exit;
}

$user_id = $_SESSION['user_id'];
$card_id = (int)$_POST['card_id'];

try {
    // First verify that the card belongs to the user
    $check_query = "SELECT c_id FROM cards WHERE c_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $card_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Card not found or unauthorized']);
        exit;
    }

    // Delete the card
    $delete_query = "DELETE FROM cards WHERE c_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $card_id, $user_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to delete card");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 