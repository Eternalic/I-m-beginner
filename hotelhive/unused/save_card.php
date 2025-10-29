<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if all required fields are present
if (!isset($_POST['card_number']) || !isset($_POST['expiry_date']) || !isset($_POST['cvv']) || 
    !isset($_POST['cardholder_name']) || !isset($_POST['bank'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'];
$card_number = $_POST['card_number'];
$expiry_date = $_POST['expiry_date'];
$cvv = $_POST['cvv'];
$cardholder_name = $_POST['cardholder_name'];
$bank = $_POST['bank'];

try {
    // Check for duplicate card
    $check_query = "SELECT c_id FROM cards WHERE user_id = ? AND card_number = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $user_id, $card_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This card is already registered for your account']);
        exit;
    }

    // Insert new card
    $query = "INSERT INTO cards (user_id, card_type, card_number, expiry_date, cvv, cardholder_name) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssss", $user_id, $bank, $card_number, $expiry_date, $cvv, $cardholder_name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'card_id' => $conn->insert_id]);
    } else {
        throw new Exception("Failed to save card");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 