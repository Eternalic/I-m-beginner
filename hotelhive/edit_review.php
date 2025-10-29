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
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate inputs
    if ($review_id > 0 && $hotel_id > 0 && $rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            // Ensure connection is alive before proceeding
            $conn = ensureConnection($conn);
            
            // First check if the review belongs to the current user
            $check_sql = "SELECT user_id FROM reviews WHERE review_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if (!$check_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $check_stmt->bind_param('i', $review_id);
            
            if (!$check_stmt->execute()) {
                throw new Exception("Execute failed: " . $check_stmt->error);
            }
            
            $check_result = $check_stmt->get_result();
            $review_data = $check_result->fetch_assoc();
            
            if ($review_data && $review_data['user_id'] == $_SESSION['user_id']) {
                // Update the review
                $update_sql = "UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if (!$update_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $update_stmt->bind_param('isi', $rating, $comment, $review_id);
                
                if ($update_stmt->execute()) {
                    // Redirect back to hotel details page with success message
                    header("Location: view_details.php?hotel_id=$hotel_id&edit=success");
                } else {
                    // Redirect with error message
                    header("Location: view_details.php?hotel_id=$hotel_id&edit=error");
                }
                $update_stmt->close();
            } else {
                // Redirect with unauthorized message
                header("Location: view_details.php?hotel_id=$hotel_id&edit=unauthorized");
            }
            $check_stmt->close();
            
        } catch (Exception $e) {
            // Log the error (you might want to log this to a file)
            error_log("Database error in edit_review.php: " . $e->getMessage());
            
            // Redirect with error message
            header("Location: view_details.php?hotel_id=$hotel_id&edit=error");
        }
    } else {
        // Redirect with invalid input message
        header("Location: view_details.php?hotel_id=$hotel_id&edit=invalid");
    }
} else {
    // If someone tries to access this file directly without POST data
    header('Location: homepage.php');
}
exit();
?> 