<?php
require_once 'db.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$suggestions = [];

if ($query === '') {
    // Return all unique destinations if no query
    $sql = "SELECT DISTINCT CONCAT(city, ', ', country) AS location FROM hotels ORDER BY location LIMIT 10";
    $result = $conn->query($sql);
} else {
    // Return matching destinations
    $sql = "SELECT DISTINCT CONCAT(city, ', ', country) AS location 
            FROM hotels 
            WHERE city LIKE ? OR country LIKE ? 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $likeQuery = "%$query%";
    $stmt->bind_param('ss', $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
}

if (isset($result)) {
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['location'];
    }
}

if (isset($stmt)) $stmt->close();
$conn->close();
echo json_encode($suggestions);
?>