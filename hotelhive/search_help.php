<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in to search help content']);
    exit;
}

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Search query must be at least 2 characters long']);
    exit;
}

try {
    // Search in articles
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.description,
            a.views,
            c.name as category_name,
            c.slug as category_slug,
            c.icon as category_icon
        FROM help_articles a
        JOIN help_categories c ON a.category_id = c.id
        WHERE 
            a.title LIKE ? OR 
            a.description LIKE ? OR 
            a.keywords LIKE ?
        ORDER BY 
            CASE 
                WHEN a.title LIKE ? THEN 1
                WHEN a.description LIKE ? THEN 2
                WHEN a.keywords LIKE ? THEN 3
                ELSE 4
            END,
            a.views DESC
        LIMIT 10
    ");
    
    $searchPattern = "%{$query}%";
    $stmt->bind_param("ssssss", 
        $searchPattern, 
        $searchPattern, 
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $searchPattern
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($article = $result->fetch_assoc()) {
        // Update view count
        $updateStmt = $conn->prepare("UPDATE help_articles SET views = views + 1 WHERE id = ?");
        $updateStmt->bind_param("i", $article['id']);
        $updateStmt->execute();
        
        $articles[] = [
            'id' => $article['id'],
            'title' => $article['title'],
            'slug' => $article['slug'],
            'description' => $article['description'],
            'views' => $article['views'],
            'category' => [
                'name' => $article['category_name'],
                'slug' => $article['category_slug'],
                'icon' => $article['category_icon']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $articles
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while searching']);
} 