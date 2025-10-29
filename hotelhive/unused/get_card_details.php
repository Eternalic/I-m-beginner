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
    echo json_encode(['success' => false, 'message' => 'Card ID not provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$card_id = (int)$_POST['card_id'];

try {
    // Fetch card details
    $query = "SELECT * FROM cards WHERE c_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Card not found");
    }
    
    $card = $result->fetch_assoc();
    echo json_encode(['success' => true, 'card' => $card]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 