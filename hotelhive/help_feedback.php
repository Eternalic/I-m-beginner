<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get article ID, feedback type, and action from URL parameters
$article_id = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : 'add';

if (!$article_id || !in_array($type, ['helpful', 'not-helpful']) || !in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Get current vote counts
    $stmt = $conn->prepare("SELECT helpful_votes, not_helpful_votes FROM help_articles WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();

    if ($action === 'remove') {
        // Update vote count in help_articles table
        if ($type === 'helpful' && $article['helpful_votes'] > 0) {
            $stmt = $conn->prepare("UPDATE help_articles SET helpful_votes = helpful_votes - 1 WHERE id = ?");
        } else if ($type === 'not-helpful' && $article['not_helpful_votes'] > 0) {
            $stmt = $conn->prepare("UPDATE help_articles SET not_helpful_votes = not_helpful_votes - 1 WHERE id = ?");
        }
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Vote removed']);
        exit;
    }
    
    // Add vote
    if ($type === 'helpful') {
        $stmt = $conn->prepare("UPDATE help_articles SET helpful_votes = helpful_votes + 1 WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE help_articles SET not_helpful_votes = not_helpful_votes + 1 WHERE id = ?");
    }
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Vote recorded']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 