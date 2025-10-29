<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate inputs
    if ($hotel_id > 0 && $rating >= 1 && $rating <= 5 && !empty($comment)) {
        // Insert review into database
        $sql = "INSERT INTO reviews (user_id, hotel_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $_SESSION['user_id'], $hotel_id, $rating, $comment);
        
        if ($stmt->execute()) {
            // Redirect back to hotel details page with success message
            header("Location: view_details.php?hotel_id=$hotel_id&review=success");
        } else {
            // Redirect with error message
            header("Location: view_details.php?hotel_id=$hotel_id&review=error");
        }
        $stmt->close();
    } else {
        // Redirect with invalid input message
        header("Location: view_details.php?hotel_id=$hotel_id&review=invalid");
    }
} else {
    // If someone tries to access this file directly without POST data
    header('Location: homepage.php');
}
exit();
?> 