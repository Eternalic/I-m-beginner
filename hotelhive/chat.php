<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/NotificationManager.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Function to get all hotels
function getAllHotels($conn) {
    $sql = "SELECT hotel_id, name, city, country FROM hotels ORDER BY name";
    $result = $conn->query($sql);
    return $result;
}

// Function to get unique cities
function getUniqueCities($conn) {
    $sql = "SELECT DISTINCT city FROM hotels ORDER BY city";
    $result = $conn->query($sql);
    return $result;
}

// Function to get unique countries
function getUniqueCountries($conn) {
    $sql = "SELECT DISTINCT country FROM hotels ORDER BY country";
    $result = $conn->query($sql);
    return $result;
}

// Function to get hotels by city
function getHotelsByCity($conn, $city) {
    $sql = "SELECT hotel_id, name FROM hotels WHERE city = ? ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $city);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get hotels by country
function getHotelsByCountry($conn, $country) {
    $sql = "SELECT hotel_id, name FROM hotels WHERE country = ? ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $country);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get hotel details
function getHotelDetails($conn, $hotelId) {
    $sql = "SELECT hotel_id, name, location, city, country, description, star_rating, image_url FROM hotels WHERE hotel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hotelId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get unique cities for a specific country
function getCitiesByCountry($conn, $country) {
    $sql = "SELECT DISTINCT city FROM hotels WHERE country = ? ORDER BY city";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $country);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get rooms by hotel
function getRoomsByHotel($conn, $hotelId) {
    $sql = "SELECT room_id, room_type, price_per_night, max_guests, bed_type, availability 
            FROM rooms 
            WHERE hotel_id = ? AND availability = 1
            ORDER BY room_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hotelId);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get unique countries for room selection
function getCountriesForRooms($conn) {
    $sql = "SELECT DISTINCT country FROM hotels ORDER BY country";
    $result = $conn->query($sql);
    return $result;
}

// Function to get cities by country for room selection
function getCitiesByCountryForRooms($conn, $country) {
    $sql = "SELECT DISTINCT city FROM hotels WHERE country = ? ORDER BY city";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $country);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get rooms by city
function getRoomsByCity($conn, $city) {
    $sql = "SELECT DISTINCT r.room_id, r.room_type 
            FROM rooms r 
            JOIN hotels h ON r.hotel_id = h.hotel_id 
            WHERE h.city = ? 
            ORDER BY r.room_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $city);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get room details
function getRoomDetails($conn, $roomId) {
    $sql = "SELECT r.room_id, r.room_type, r.price_per_night, r.max_guests, r.bed_type, 
                   r.availability, r.image_url,
                   GROUP_CONCAT(DISTINCT ra.amenities) as amenities,
                   GROUP_CONCAT(DISTINCT ra.room_size) as room_size,
                   GROUP_CONCAT(DISTINCT ra.beds) as beds
            FROM rooms r 
            LEFT JOIN room_amenities ra ON r.room_id = ra.room_id
            WHERE r.room_id = ?
            GROUP BY r.room_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to generate hotel-related responses
function generateHotelResponse($message, $hotelDetails) {
    $message = strtolower($message);
    $response = [];
    
    // Hotel Details
    if (strpos($message, 'hotel details') !== false || strpos($message, 'show me hotel details') !== false) {
        $response['message'] = "Here are the details for {$hotelDetails['name']}:\n";
        $response['message'] .= "• Location: {$hotelDetails['location']}\n";
        $response['message'] .= "• City: {$hotelDetails['city']}\n";
        $response['message'] .= "• Country: {$hotelDetails['country']}\n";
        $response['message'] .= "• Rating: " . str_repeat('⭐', $hotelDetails['star_rating']) . "\n";
        $response['message'] .= "• Description: {$hotelDetails['description']}\n";
        $response['hasLink'] = false;
    }
    // Check-in/out times
    elseif (strpos($message, 'check-in') !== false || strpos($message, 'checkout') !== false) {
        $response['message'] = "For {$hotelDetails['name']}, check-in time is 3:00 PM and check-out time is 12:00 PM. Early check-in and late check-out are subject to availability.";
        $response['hasLink'] = false;
    }
    // Amenities
    elseif (strpos($message, 'amenities') !== false) {
        $response['message'] = "{$hotelDetails['name']} offers the following amenities:\n";
        $response['message'] .= "• Free Wi-Fi\n";
        $response['message'] .= "• Swimming Pool\n";
        $response['message'] .= "• Fitness Center\n";
        $response['message'] .= "• Restaurant\n";
        $response['message'] .= "• 24/7 Front Desk\n";
        $response['message'] .= "• Room Service";
        $response['hasLink'] = false;
    }
    // Cancellation policy
    elseif (strpos($message, 'cancellation') !== false) {
        $response['message'] = "Cancellation Policy for {$hotelDetails['name']}:\n";
        $response['message'] .= "• Free cancellation up to 24 hours before check-in\n";
        $response['message'] .= "• Late cancellation: 50% of the first night's rate\n";
        $response['message'] .= "• No-show: Full charge for the first night";
        $response['hasLink'] = false;
    }
    // Location
    elseif (strpos($message, 'location') !== false || strpos($message, 'where') !== false) {
        $response['message'] = "{$hotelDetails['name']} is located at:\n";
        $response['message'] .= "📍 {$hotelDetails['location']}\n";
        $response['message'] .= "🏙️ {$hotelDetails['city']}\n";
        $response['message'] .= "🌍 {$hotelDetails['country']}";
        $response['hasLink'] = false;
    }
    // Rating
    elseif (strpos($message, 'hotel rating') !== false || strpos($message, 'stars') !== false) {
        $stars = str_repeat('⭐', $hotelDetails['star_rating']);
        $response['message'] = "{$hotelDetails['name']} has a rating of {$hotelDetails['star_rating']} out of 5 stars:\n";
        $response['message'] .= $stars;
        $response['hasLink'] = false;
    }
    // Default hotel response
    else {
        $response['message'] = "I can help you with information about {$hotelDetails['name']}. Would you like to know about:\n";
        $response['message'] .= "• Check-in/out times (When can I arrive and leave?)\n";
        $response['message'] .= "• Available amenities (What facilities and services are offered?)\n";
        $response['message'] .= "• Cancellation policy (What are the refund rules?)\n";
        $response['message'] .= "• Making a reservation (How do I book a room?)\n";
        $response['message'] .= "• Location details (Where exactly is the hotel located?)\n";
        $response['message'] .= "• Hotel rating (How many stars does the hotel have?)\n";
        $response['message'] .= "• Hotel details (Show me complete hotel information)";
        $response['hasLink'] = false;
    }
    
    return $response;
}

// Function to generate room-related responses
function generateRoomResponse($message, $roomDetails) {
    $message = strtolower($message);
    $response = [];
    
    // Room Prices
    if (strpos($message, 'price') !== false || strpos($message, 'cost') !== false || strpos($message, 'rate') !== false) {
        $response['message'] = "Room Pricing Information:\n\n" .
            "• Room Type: {$roomDetails['room_type']}\n" .
            "• Price per Night: RM " . number_format($roomDetails['price_per_night'], 2) . "\n" .
            "• Maximum Guests: {$roomDetails['max_guests']}\n" .
            "• Bed Type: {$roomDetails['bed_type']}\n\n" .
            "Would you like to know about:\n" .
            "• Additional charges or taxes\n" .
            "• Cancellation policy\n" .
            "• Payment methods";
        $response['hasLink'] = false;
    }
    // Available Room Types
    elseif (strpos($message, 'available room') !== false || strpos($message, 'room types') !== false) {
        $response['message'] = "Available room type:\n";
        $response['message'] .= "• {$roomDetails['room_type']}\n";
        $response['message'] .= "• Maximum occupancy: {$roomDetails['max_guests']} guests\n";
        $response['message'] .= "• Room size: {$roomDetails['room_size']}\n";
        $response['message'] .= "• Bed configuration: {$roomDetails['beds']}";
        $response['hasLink'] = false;
    }
    // Bed Types
    elseif (strpos($message, 'bed types') !== false || strpos($message, 'bed') !== false) {
        $response['message'] = "Bed configuration:\n";
        $response['message'] .= "• {$roomDetails['beds']}\n";
        $response['message'] .= "• Room size: {$roomDetails['room_size']}";
        $response['hasLink'] = false;
    }
    // Room Capacity
    elseif (strpos($message, 'capacity') !== false || strpos($message, 'guests') !== false) {
        $response['message'] = "Room capacity:\n";
        $response['message'] .= "• Maximum {$roomDetails['max_guests']} guests allowed\n";
        $response['message'] .= "• Room size: {$roomDetails['room_size']}\n";
        $response['message'] .= "• Bed configuration: {$roomDetails['beds']}";
        $response['hasLink'] = false;
    }
    // Room Details
    elseif (strpos($message, 'room details') !== false) {
        $response['message'] = "Complete room details:\n";
        $response['message'] .= "• Room Type: {$roomDetails['room_type']}\n";
        $response['message'] .= "• Bed Configuration: {$roomDetails['beds']}\n";
        $response['message'] .= "• Room Size: {$roomDetails['room_size']}\n";
        $response['message'] .= "• Maximum Guests: {$roomDetails['max_guests']}\n";
        $response['message'] .= "• Price per Night: RM " . number_format($roomDetails['price_per_night'], 2) . "\n";
        $response['message'] .= "• Availability: " . ($roomDetails['availability'] ? "Available" : "Not Available") . "\n\n";
        $response['message'] .= "Room Amenities:\n";
        $amenities = explode(',', $roomDetails['amenities']);
        foreach ($amenities as $amenity) {
            $response['message'] .= "• " . trim($amenity) . "\n";
        }
        $response['hasLink'] = false;
    }
    // Default room response
    else {
        $response['message'] = "What would you like to know about this room? You can ask about:\n";
        $response['message'] .= "• Available Room Types (What type of room is this?)\n";
        $response['message'] .= "• Room Prices (How much does it cost per night?)\n";
        $response['message'] .= "• Bed Types (What bed configuration is available?)\n";
        $response['message'] .= "• Room Capacity (How many guests are allowed?)\n";
        $response['message'] .= "• Room Details (Show me all room information)";
        $response['hasLink'] = false;
    }
    
    return $response;
}

// Function to get rooms by hotel and country
function getRoomsByHotelAndCountry($conn, $hotelId, $country) {
    $sql = "SELECT r.room_id, r.room_type, r.price_per_night, r.max_guests, r.bed_type, 
                   r.availability, r.image_url,
                   GROUP_CONCAT(DISTINCT ra.amenities) as amenities,
                   GROUP_CONCAT(DISTINCT ra.room_size) as room_size,
                   GROUP_CONCAT(DISTINCT ra.beds) as beds
            FROM rooms r 
            JOIN hotels h ON r.hotel_id = h.hotel_id
            LEFT JOIN room_amenities ra ON r.room_id = ra.room_id
            WHERE r.hotel_id = ? AND h.country = ?
            GROUP BY r.room_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $hotelId, $country);
    $stmt->execute();
    return $stmt->get_result();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'get_chat_history':
            if (isset($_COOKIE['hotelhive_chat_history'])) {
                $chatHistory = json_decode($_COOKIE['hotelhive_chat_history'], true);
                if (is_array($chatHistory)) {
                    // Ensure we're not sending too much data
                    $chatHistory = array_slice($chatHistory, -50);
                    $response['success'] = true;
                    $response['chat_history'] = json_encode($chatHistory);
                    }
                }
                break;
                
        case 'save_chat_history':
            if (isset($_POST['chat_history'])) {
                $chatHistory = json_decode($_POST['chat_history'], true);
                if (is_array($chatHistory)) {
                    // Keep only the last 50 messages to prevent cookie size issues
                    $chatHistory = array_slice($chatHistory, -50);
                    
                    // Set cookie with proper settings and size limit
                    setcookie('hotelhive_chat_history', json_encode($chatHistory), [
                        'expires' => time() + (86400),
                        'path' => '/',
                        'domain' => '',
                        'secure' => false,
                        'httponly' => false,
                        'samesite' => 'Lax'
                    ]);
                    
                    $response['success'] = true;
                    }
                }
                break;

        case 'create_conversation':
            if (isset($_POST['hotel_id'])) {
                $hotel_id = (int)$_POST['hotel_id'];
                $user_id = $_SESSION['user_id'];
                
                // Check if conversation already exists
                $check_sql = "SELECT conversation_id FROM chat_conversations WHERE user_id = ? AND hotel_id = ? AND status != 'closed'";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $user_id, $hotel_id);
                $check_stmt->execute();
                $existing = $check_stmt->get_result()->fetch_assoc();
                
                if ($existing) {
                    $response['success'] = true;
                    $response['conversation_id'] = $existing['conversation_id'];
                } else {
                    // Create new conversation
                    $create_sql = "INSERT INTO chat_conversations (user_id, hotel_id, status) VALUES (?, ?, 'active')";
                    $create_stmt = $conn->prepare($create_sql);
                    $create_stmt->bind_param("ii", $user_id, $hotel_id);
                    
                    if ($create_stmt->execute()) {
                        $conversation_id = $conn->insert_id;
                        $response['success'] = true;
                        $response['conversation_id'] = $conversation_id;
                    } else {
                        $response['error'] = 'Failed to create conversation';
                    }
                }
            }
            break;
            
        case 'send_message_to_db':
            if (isset($_POST['conversation_id']) && isset($_POST['message'])) {
                $conversation_id = (int)$_POST['conversation_id'];
                $message_content = trim($_POST['message']);
                $user_id = $_SESSION['user_id'];
                
                if (!empty($message_content)) {
                    $sql = "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message_content, message_type) 
                            VALUES (?, 'user', ?, ?, 'text')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iis", $conversation_id, $user_id, $message_content);
                    
                    if ($stmt->execute()) {
                        // Update conversation timestamp
                        $update_sql = "UPDATE chat_conversations SET updated_at = NOW() WHERE conversation_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $conversation_id);
                        $update_stmt->execute();
                        
                        // Create notification for manager
                        $notificationManager = new NotificationManager($conn);
                        $notificationManager->notifyNewMessage($conversation_id, 'user', $message_content);
                        
                        $response['success'] = true;
                    } else {
                        $response['error'] = 'Failed to send message';
                    }
                } else {
                    $response['error'] = 'Message cannot be empty';
                }
            }
            break;
            
        case 'get_conversation_messages':
            if (isset($_POST['conversation_id'])) {
                $conversation_id = (int)$_POST['conversation_id'];
                $user_id = $_SESSION['user_id'];
                
                // Verify user owns this conversation
                $verify_sql = "SELECT conversation_id FROM chat_conversations WHERE conversation_id = ? AND user_id = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("ii", $conversation_id, $user_id);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows > 0) {
                    $sql = "SELECT m.*, u.username, u.first_name, u.last_name
                            FROM chat_messages m
                            LEFT JOIN users u ON m.sender_id = u.user_id
                            WHERE m.conversation_id = ?
                            ORDER BY m.created_at ASC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $conversation_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $messages = [];
                    while ($row = $result->fetch_assoc()) {
                        $messages[] = $row;
                    }
                    
                    $response['success'] = true;
                    $response['messages'] = $messages;
                } else {
                    $response['error'] = 'Unauthorized access';
                }
            }
            break;

        case 'send_general_message':
            if (isset($_POST['message'])) {
                $message = $_POST['message'];
                $response = ['success' => true];
                
                // Generate response based on the message
                switch(true) {
                    case stripos($message, 'payment') !== false:
                        $response['message'] = "We accept the following payment methods:\n" .
                            "• Credit Cards (Visa, MasterCard, American Express)\n" .
                            "• Debit Cards\n" .
                            "• PayPal\n" .
                            "• Bank Transfer\n" .
                            "• Cash (for in-person payments)\n\n" .
                            "All online transactions are secure and encrypted.";
                        break;
                        
                    case stripos($message, 'parking') !== false:
                        $response['message'] = "Our parking facilities include:\n" .
                            "• Secure on-site parking\n" .
                            "• Valet parking service\n" .
                            "• Electric vehicle charging stations\n" .
                            "• 24/7 security monitoring\n\n" .
                            "Parking rates vary by location. Please check with your specific hotel.";
                        break;
                        
                    case stripos($message, 'language') !== false:
                        $response['message'] = "Our staff members speak multiple languages:\n" .
                            "• English\n" .
                            "• Spanish\n" .
                            "• French\n" .
                            "• German\n" .
                            "• Chinese\n" .
                            "• Arabic\n\n" .
                            "Translation services available upon request.";
                        break;
                        
                    case stripos($message, 'loyalty') !== false:
                        $response['message'] = "Ered Hotel Rewards Program benefits:\n" .
                            "• Earn points on every stay\n" .
                            "• Free room upgrades\n" .
                            "• Early check-in and late check-out\n" .
                            "• Exclusive member rates\n" .
                            "• Free Wi-Fi\n" .
                            "• Points never expire\n\n" .
                            "Join now to start earning rewards!";
                        break;
                        
                    case stripos($message, 'room type') !== false:
                        $response['message'] = "Available room types:\n" .
                            "• Standard Room\n" .
                            "• Deluxe Room\n" .
                            "• Executive Suite\n" .
                            "• Family Room\n" .
                            "• Presidential Suite\n" .
                            "• Accessible Room\n\n" .
                            "Each room type includes different amenities and features.";
                        break;
                        
                    case stripos($message, 'location') !== false:
                        $response['message'] = "Our hotels are located in:\n" .
                            "• Major city centers\n" .
                            "• Business districts\n" .
                            "• Tourist destinations\n" .
                            "• Airport areas\n" .
                            "• Beach resorts\n\n" .
                            "Use our search feature to find the perfect location for you.";
                        break;
                        
                    case stripos($message, 'deal') !== false:
                        $response['message'] = "Current special offers:\n" .
                            "• Early booking discount (15% off)\n" .
                            "• Weekend getaway package\n" .
                            "• Stay 3, Pay 2 nights\n" .
                            "• Business traveler rates\n" .
                            "• Seasonal promotions\n" .
                            "• Group booking discounts\n\n" .
                        break;
                        
                    case stripos($message, 'amenities') !== false:
                        $response['message'] = "Standard room amenities include:\n" .
                            "• High-speed Wi-Fi\n" .
                            "• Flat-screen TV\n" .
                            "• Mini refrigerator\n" .
                            "• Coffee maker\n" .
                            "• In-room safe\n" .
                            "• Premium bedding\n" .
                            "• Climate control\n\n" .
                            "Additional amenities available in upgraded rooms.";
                        break;
                        
                    case stripos($message, 'facilities') !== false:
                        $response['message'] = "Hotel facilities include:\n" .
                            "• 24/7 Front desk\n" .
                            "• Restaurant & Bar\n" .
                            "• Fitness center\n" .
                            "• Swimming pool\n" .
                            "• Business center\n" .
                            "• Spa services\n" .
                            "• Conference rooms\n\n" .
                            "Facilities may vary by location.";
                        break;
                        
                    default:
                        $response['message'] = "How can I help you today? Feel free to ask about:\n" .
                            "• Room bookings\n" .
                            "• Hotel facilities\n" .
                            "• Special offers\n" .
                            "• Payment options\n" .
                            "• Or any other questions you may have.";
                }
                
                echo json_encode($response);
                exit;
                }
                break;

        case 'check_admin_typing':
            // Check if admin is typing by looking for a cookie set by admin_livechat.php
            $response['success'] = true;
            $response['admin_typing'] = isset($_COOKIE['admin_typing']) && $_COOKIE['admin_typing'] === 'true';
                break;

        case 'admin_joined':
            // Add a system message to the chat history
            if (isset($_COOKIE['hotelhive_chat_history'])) {
                $chatHistory = json_decode($_COOKIE['hotelhive_chat_history'], true);
                if (!is_array($chatHistory)) {
                    $chatHistory = [];
                }
                
                // Check if admin has already joined
                $adminJoined = false;
                foreach ($chatHistory as $message) {
                    if (isset($message['sender']) && $message['sender'] === 'system' && 
                        strpos($message['text'], 'customer service representative has joined') !== false) {
                        $adminJoined = true;
                        break;
                    }
                }
                
                if (!$adminJoined) {
                    // Add system message about admin joining
                    $chatHistory[] = [
                        'text' => 'A customer service representative has joined the chat.',
                        'isUser' => false,
                        'sender' => 'system',
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    
                    // Save updated chat history
                    setcookie('hotelhive_chat_history', json_encode($chatHistory), time() + (86400), '/');
                    
                    $response['success'] = true;
                    $response['message'] = 'A customer service representative has joined the chat.';
                    }
                }
                break;

        case 'start_live_chat':
            if (isset($_SESSION['user_id'])) {
                // Add connecting message
                addMessage('Connecting you to a customer service representative...', false);
                
                // Generate a unique chat session ID
                $chat_session_id = uniqid('chat_', true);
                
                // Get user information
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $username = $user['username'];
                
                // Initialize chat session data
                $chat_session_data = [
                    'user_id' => $user_id,
                    'user_name' => $username,
                    'last_message' => 'Connecting you to a customer service representative...',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'status' => 'waiting'
                ];
                
                // Save chat session data in cookie
                setcookie('chat_session_' . $chat_session_id, json_encode($chat_session_data), [
                    'expires' => time() + (86400),
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
                
                // Initialize chat history
                $chatHistory = [
                    [
                        'text' => 'Connecting you to a customer service representative...',
                        'isUser' => false,
                        'sender' => 'system',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                ];
                
                // Save chat history in cookie
                setcookie('hotelhive_chat_history', json_encode($chatHistory), [
                    'expires' => time() + (86400),
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
                
                $response['success'] = true;
                $response['message'] = 'A customer service representative will be with you shortly. Thank you for your patience.';
            }
            break;

        case 'check_admin_joined':
            // Check if admin has joined by looking for the admin_joined cookie
            $response['success'] = true;
            $response['admin_joined'] = isset($_COOKIE['admin_joined']) && $_COOKIE['admin_joined'] === 'true';
            break;

        case 'check_chat_closed':
            // Check if chat has been closed by admin
            if (isset($_SESSION['chat_session_id'])) {
                $chat_id = $_SESSION['chat_session_id'];
                $response['success'] = true;
                $response['chat_closed'] = isset($_COOKIE['chat_closed_' . $chat_id]) && $_COOKIE['chat_closed_' . $chat_id] === 'true';
                
                // If chat is closed, clean up cookies
                if ($response['chat_closed']) {
                    // Delete chat history cookie
                    setcookie('hotelhive_chat_history', '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'domain' => '',
                        'secure' => false,
                        'httponly' => false,
                        'samesite' => 'Lax'
                    ]);
                    
                    // Delete chat session cookie
                    setcookie('chat_session_' . $chat_id, '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'domain' => '',
                        'secure' => false,
                        'httponly' => false,
                        'samesite' => 'Lax'
                    ]);
                    
                    // Delete admin joined cookie
                    setcookie('admin_joined', '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'domain' => '',
                        'secure' => false,
                        'httponly' => false,
                        'samesite' => 'Lax'
                    ]);
                    
                    // Clear session chat ID
                    unset($_SESSION['chat_session_id']);
                }
            }
            break;

        case 'clear_chat_history':
            // Delete the chat history cookie
            setcookie('hotelhive_chat_history', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
            
            // Delete any chat session cookies
            foreach ($_COOKIE as $name => $value) {
                if (strpos($name, 'chat_session_') === 0) {
                    setcookie($name, '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'domain' => '',
                        'secure' => false,
                        'httponly' => false,
                        'samesite' => 'Lax'
                    ]);
                }
            }
            
            $response['success'] = true;
                break;
        }
        
    header('Content-Type: application/json');
        echo json_encode($response);
    exit;
}

// Add this function after the existing functions
function generateGeneralResponse($message) {
    $message = strtolower($message);
    
    // Payment Methods
    if (strpos($message, 'payment') !== false || strpos($message, 'pay') !== false) {
        return "We accept various payment methods including:\n" .
               "• Credit/Debit Cards (Visa, MasterCard, American Express)\n" .
               "• PayPal\n" .
               "• Bank Transfer\n" .
               "• Cash on Arrival (at select properties)\n\n" .
               "All transactions are secure and encrypted.";
    }
    // Parking Information
    elseif (strpos($message, 'parking') !== false) {
        return "Parking facilities vary by property:\n" .
               "• Most hotels offer on-site parking\n" .
               "• Some properties have valet parking\n" .
               "• Parking fees may apply (varies by property)\n" .
               "• Limited spaces available at some locations\n\n" .
               "Please check with your specific hotel for details.";
    }
    // Languages Spoken
    elseif (strpos($message, 'language') !== false || strpos($message, 'speak') !== false) {
        return "Our staff speaks multiple languages:\n" .
               "• English\n" .
               "• Spanish\n" .
               "• French\n" .
               "• German\n" .
               "• Chinese\n" .
               "• Japanese\n\n" .
               "Additional languages may be available at specific locations.";
    }
    // Loyalty Program
    elseif (strpos($message, 'loyalty') !== false || strpos($message, 'program') !== false) {
        return "Yes, we have a loyalty program called Ered Hotel Rewards:\n" .
               "• Earn points for every stay\n" .
               "• Free room upgrades\n" .
               "• Late check-out privileges\n" .
               "• Exclusive member rates\n" .
               "• Points never expire\n\n" .
               "Would you like to learn more about joining?";
    }
    // Room Types
    elseif (strpos($message, 'room type') !== false || strpos($message, 'rooms') !== false) {
        return "We offer various room types:\n" .
               "• Standard Rooms\n" .
               "• Deluxe Rooms\n" .
               "• Suites\n" .
               "• Family Rooms\n" .
               "• Executive Rooms\n" .
               "• Accessible Rooms\n\n" .
               "Each room type comes with different amenities and features.";
    }
    // Hotel Locations
    elseif (strpos($message, 'location') !== false || strpos($message, 'where') !== false) {
        return "Our hotels are located in:\n" .
               "• Major cities worldwide\n" .
               "• Popular tourist destinations\n" .
               "• Business districts\n" .
               "• Near airports\n" .
               "• Beachfront properties\n\n" .
               "You can search for specific locations on our website.";
    }
    // Special offers no longer available
    elseif (strpos($message, 'deal') !== false || strpos($message, 'special') !== false) {
        return "We currently don't have any special deals available. Please check back later for promotions.";
    }
    // Room Amenities
    elseif (strpos($message, 'amenit') !== false) {
        return "Our rooms include these amenities:\n" .
               "• Free Wi-Fi\n" .
               "• Flat-screen TV\n" .
               "• Air conditioning\n" .
               "• Mini fridge\n" .
               "• Coffee/tea maker\n" .
               "• Safe deposit box\n" .
               "• Work desk\n" .
               "• Private bathroom\n\n" .
               "Additional amenities may vary by room type.";
    }
    // Hotel Facilities
    elseif (strpos($message, 'facilit') !== false) {
        return "Our hotels offer these facilities:\n" .
               "• Restaurants and bars\n" .
               "• Swimming pools\n" .
               "• Fitness centers\n" .
               "• Business centers\n" .
               "• Conference rooms\n" .
               "• Spa services\n" .
               "• Room service\n" .
               "• Concierge service\n\n" .
               "Facilities may vary by property.";
    }
    // Default response
    else {
        return "I'm here to help you with any questions about our hotels. Please select from the quick questions above or type your specific question.";
    }
}

function addMessage(message, isUser = false) {
    const chatMessages = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = isUser ? 'message user-message' : 'message bot-message';
    messageDiv.innerHTML = `
        <div class="message-content">
            <p>${message}</p>
        </div>
        <div class="message-time">${new Date().toLocaleTimeString()}</div>
    `;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function startLiveChat() {
    // Add connecting message
    addMessage('Connecting you to a customer service representative...', false);
    
    fetch('chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=start_live_chat'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Start checking for admin response and chat closure
            checkAdminJoined();
            checkChatClosed();
        }
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ered Hotel - Customer Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
        }

        .nav-menu a {
            color: #1a1a1a;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover {
            background: #f0f0f0;
            color: #c8a97e;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }

        .left-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .hotel-selection, .room-selection {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .chat-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            text-align: center;
        }

        .chat-header h2 {
            color: #1a1a1a;
            font-size: 20px;
            font-weight: 600;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.5;
        }

        .user-message {
            background: #c8a97e;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .bot-message {
            background: #f0f0f0;
            color: #1a1a1a;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .chat-input {
            padding: 20px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 10px;
            background: #fff;
        }

        .chat-input input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #eee;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .chat-input input:focus {
            outline: none;
            border-color: #c8a97e;
        }

        .chat-input button {
            background: #c8a97e;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chat-input button:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        .selection-group {
            margin-bottom: 20px;
        }

        .selection-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1a1a1a;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
            color: #1a1a1a;
            background-color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: #c8a97e;
        }

        select option {
            padding: 10px;
        }

        .quick-actions {
            margin-top: 20px;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 10px;
        }

        .quick-actions h3 {
            color: #1a1a1a;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .action-btn {
            padding: 10px;
            background: #fff;
            border: 1px solid #c8a97e;
            border-radius: 20px;
            color: #1a1a1a;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }

        .action-btn:hover {
            background: #c8a97e;
            color: #fff;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        .view-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #c8a97e;
            color: #fff;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .view-link:hover {
            background: #b69468;
            transform: translateY(-2px);
        }

        .bot-message a {
            color: #fff;
            text-decoration: none;
        }

        .dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dropdown-header:hover {
            background: #f8f8f8;
        }

        .dropdown-header h3 {
            margin: 0;
            color: #1a1a1a;
            font-size: 18px;
        }

        .dropdown-header i {
            color: #c8a97e;
            transition: transform 0.3s ease;
        }

        .dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: #fff;
            border-radius: 10px;
            margin-top: 10px;
        }

        .dropdown-content.active {
            max-height: 1000px;
        }

        .dropdown-header.active i {
            transform: rotate(180deg);
        }

        .live-chat {
            margin-top: 20px;
            text-align: center;
        }

        .live-chat-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        .live-chat-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .live-chat-btn i {
            font-size: 18px;
        }

        .chat-history-controls {
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            border-top: 2px solid #f0f0f0;
            background: #f8f8f8;
        }
        
        .chat-history-btn {
            background: none;
            border: none;
            color: #c8a97e;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .chat-history-btn:hover {
            color: #b69468;
        }
        
        .chat-history-btn i {
            font-size: 16px;
        }
        
        .chat-history-info {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }
        
        .chat-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #c8a97e;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.5s ease-out, fadeOut 0.5s ease-in 4.5s forwards;
            max-width: 350px;
        }
        
        .chat-notification i {
            font-size: 20px;
        }
        
        .chat-notification-content {
            flex: 1;
        }
        
        .chat-notification-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .chat-notification-message {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .chat-notification-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .chat-notification-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .system-message {
            background: #f8f8f8;
            color: #666;
            text-align: center;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-style: italic;
            align-self: center;
            margin: 10px 0;
        }
        
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: #f0f0f0;
            border-radius: 15px;
            margin-bottom: 10px;
            align-self: flex-start;
            font-size: 14px;
            color: #666;
            animation: fadeIn 0.3s ease;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #666;
            border-radius: 50%;
            opacity: 0.6;
        }

        .typing-dot:nth-child(1) { animation: typing 1s infinite; }
        .typing-dot:nth-child(2) { animation: typing 1s infinite 0.2s; }
        .typing-dot:nth-child(3) { animation: typing 1s infinite 0.4s; }

        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message {
            opacity: 0;
            transform: translateY(10px);
            animation: fadeIn 0.3s ease forwards;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
        <div class="nav-menu">
            <a href="SignedIn_homepage.php">Home</a>
            <a href="manage_bookings.php">Your Bookings</a>
            <a href="SignedIn_homepage.php?signout=true">Sign Out</a>
        </div>
    </div>

    <!-- Chat History Notification -->
    <div id="chatHistoryNotification" class="chat-notification" style="display: none;">
        <div class="chat-notification-content">
            <div class="chat-notification-title">Chat History Restored</div>
            <div class="chat-notification-message">Your previous conversation has been loaded. Chat history is stored for 24 hours.</div>
        </div>
        <button class="chat-notification-close" onclick="closeNotification()">×</button>
            </div>

    <div class="container">
        <div class="left-panel">
            <div class="other-questions">
                <div class="dropdown-header" onclick="toggleDropdown('other')">
                    <h3>Other Questions</h3>
                    <i class="fas fa-chevron-down" id="otherDropdownIcon"></i>
                </div>
                <div class="dropdown-content" id="otherDropdownContent">
                    <div class="quick-actions">
                        <h3>General Information</h3>
                        <div class="action-buttons">
                            <button class="action-btn" onclick="sendGeneralMessage('What payment methods do you accept?')">Payment Methods</button>
                            <button class="action-btn" onclick="sendGeneralMessage('Do you have parking facilities?')">Parking Information</button>
                            <button class="action-btn" onclick="sendGeneralMessage('What languages do your staff speak?')">Languages Spoken</button>
                            <button class="action-btn" onclick="sendGeneralMessage('Do you have a loyalty program?')">Loyalty Program</button>
                            <button class="action-btn" onclick="sendGeneralMessage('What types of rooms do you offer?')">Room Types</button>
                            <button class="action-btn" onclick="sendGeneralMessage('What are your hotel locations?')">Hotel Locations</button>
                            <button class="action-btn" onclick="sendGeneralMessage('What amenities are included in the rooms?')">Room Amenities</button>
                            <button class="action-btn" onclick="sendGeneralMessage('What facilities are available at your hotels?')">Hotel Facilities</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="live-chat">
                <button class="live-chat-btn" onclick="startLiveChat()">
                    <i class="fas fa-comments"></i> Live Chat with Customer Service
                </button>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <h2>Hotel Information Assistant</h2>
            </div>
            <div class="chat-messages" id="chatMessages">
                <div class="message bot-message">
                    Welcome to Ered Hotel! I'm here to help you with any questions about our hotels, rooms, or general information. Please select from the options on the left to get started.
                </div>
            </div>
            <div class="chat-input">
                <input type="text" id="userInput" placeholder="Type your message here..." onkeypress="handleKeyPress(event)">
                <button onclick="sendUserMessage()">Send</button>
            </div>
            <div class="chat-history-controls">
                <button class="chat-history-btn" onclick="clearChatHistory()">
                    <i class="fas fa-trash-alt"></i> Clear Chat History
                </button>
                <span class="chat-history-info">Chat history is stored for 24 hours</span>
            </div>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const userInput = document.getElementById('userInput');
        let selectedHotelId = null;
        let selectedRoomId = null;
        let chatHistory = [];
        let historyLoaded = false;
        let typingIndicator = null;
        let adminTypingCheckInterval;
        let messageCheckInterval;
        let lastMessageTimestamp = null;
        let currentConversationId = null;
        let useDatabase = true; // Flag to use database storage

        // Load chat history when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadChatHistory();
            // Start checking for admin typing status
            adminTypingCheckInterval = setInterval(checkAdminTyping, 2000);
            // Start checking for new messages more frequently
            messageCheckInterval = setInterval(checkNewMessages, 1000);
        });

        // Create or get conversation for database storage
        function createOrGetConversation(hotelId) {
            return fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=create_conversation&hotel_id=${hotelId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentConversationId = data.conversation_id;
                    return data.conversation_id;
                } else {
                    console.error('Failed to create conversation:', data.error);
                    return null;
                }
            })
            .catch(error => {
                console.error('Error creating conversation:', error);
                return null;
            });
        }

        // Send message to database
        function sendMessageToDatabase(message) {
            if (!currentConversationId) {
                console.error('No conversation ID available');
                return Promise.reject('No conversation ID');
            }

            return fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_message_to_db&conversation_id=${currentConversationId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return data;
                } else {
                    throw new Error(data.error || 'Failed to send message');
                }
            });
        }

        // Load messages from database
        function loadMessagesFromDatabase(conversationId) {
            return fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_conversation_messages&conversation_id=${conversationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return data.messages;
                } else {
                    throw new Error(data.error || 'Failed to load messages');
                }
            });
        }

        // Send message to cookie (fallback)
        function sendMessageToCookie(message) {
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_general_message&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        }

        // Send message to cookie with response (fallback)
        function sendMessageToCookieWithResponse(message) {
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_general_message&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove typing indicator after a short delay
                    setTimeout(() => {
                        hideTypingIndicator();
                        // Add bot response
                        addBotMessage(data.message);
                    }, 800);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                hideTypingIndicator();
            });
        }
        
        // Clean up intervals when page is unloaded
        window.addEventListener('beforeunload', function() {
            clearInterval(adminTypingCheckInterval);
            clearInterval(messageCheckInterval);
        });

        function checkNewMessages() {
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_chat_history'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.chat_history) {
                    try {
                        const chatHistory = JSON.parse(data.chat_history);
                        if (chatHistory.length > 0) {
                            const lastMessage = chatHistory[chatHistory.length - 1];
                            const currentTimestamp = lastMessage.timestamp;
                            
                            // Only update if there are new messages
                            if (!lastMessageTimestamp || currentTimestamp > lastMessageTimestamp) {
                                lastMessageTimestamp = currentTimestamp;
                                
                                // Clear the chat messages container
                                chatMessages.innerHTML = '';
                                
                                // Add each message from history
                                chatHistory.forEach(msg => {
                                    if (msg.sender === 'system') {
                                        addSystemMessage(msg.text);
                                    } else if (msg.sender === 'admin') {
                                        addAdminMessage(msg.text, msg.sender_name, msg.timestamp);
                                    } else if (msg.sender === 'user') {
                                        addUserMessage(msg.text, msg.timestamp);
                                    } else if (msg.isUser) {
                                        addUserMessage(msg.text, msg.timestamp);
                                    }
                                });
                                
                                // Scroll to bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing chat history:', e);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking for new messages:', error);
            });
        }

        function loadChatHistory() {
                fetch('chat.php', {
                    method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_chat_history'
                })
                .then(response => response.json())
                .then(data => {
                if (data.chat_history) {
                    try {
                        chatHistory = JSON.parse(data.chat_history);
                        // Only show notification if there are actual messages (more than just the welcome message)
                        if (chatHistory.length > 1) {
                            historyLoaded = true;
                            // Clear the chat messages container
                            chatMessages.innerHTML = '';
                            // Add each message from history
                            chatHistory.forEach(msg => {
                                if (msg.sender === 'system') {
                                    addSystemMessage(msg.text);
                                } else if (msg.sender === 'admin') {
                                    addAdminMessage(msg.text, msg.sender_name, msg.timestamp);
                                } else if (msg.sender === 'user') {
                                    addUserMessage(msg.text, msg.timestamp);
                                } else if (msg.isUser) {
                                    addUserMessage(msg.text, msg.timestamp);
                                }
                            });
                            // Scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            // Show notification
                            showNotification();
                        }
                    } catch (e) {
                        console.error('Error parsing chat history:', e);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading chat history:', error);
            });
        }
        
        function checkAdminTyping() {
                fetch('chat.php', {
                    method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check_admin_typing'
                })
                .then(response => response.json())
                .then(data => {
                if (data.admin_typing === true) {
                    // Show typing indicator if not already shown
                    if (!typingIndicator) {
                        showTypingIndicator();
                    }
            } else {
                    // Remove typing indicator if it exists
                    if (typingIndicator) {
                        removeTypingIndicator();
                    }
                }
            });
        }
        
        function showTypingIndicator() {
            const typingIndicator = document.createElement('div');
                typingIndicator.className = 'typing-indicator';
                typingIndicator.innerHTML = `
                <span>Typing</span>
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                `;
                chatMessages.appendChild(typingIndicator);
            typingIndicator.style.display = 'flex';
                chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function removeTypingIndicator() {
            const typingIndicator = document.querySelector('.typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
        
        function addSystemMessage(message) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message system-message';
            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${message}</p>
                </div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function addAdminMessage(message, senderName, timestamp) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot-message';
            
            const senderDiv = document.createElement('div');
            senderDiv.className = 'message-sender';
            senderDiv.textContent = senderName || 'Admin';
            
            const contentDiv = document.createElement('div');
            contentDiv.innerHTML = message.replace(/\n/g, '<br>');
            
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = formatTimestamp(timestamp);
            
            messageDiv.appendChild(senderDiv);
            messageDiv.appendChild(contentDiv);
            messageDiv.appendChild(timeDiv);
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showNotification() {
            const notification = document.getElementById('chatHistoryNotification');
            notification.style.display = 'flex';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.style.display = 'none';
            }, 5000);
        }
        
        function closeNotification() {
            document.getElementById('chatHistoryNotification').style.display = 'none';
        }

        function saveChatHistory() {
            // Convert chat messages to an array of objects
            const messages = Array.from(chatMessages.children).map(msg => {
                if (msg.classList.contains('system-message')) {
                    return {
                        text: msg.innerHTML,
                        isUser: false,
                        sender: 'system',
                        timestamp: new Date().toISOString()
                    };
                } else if (msg.classList.contains('bot-message')) {
                    const sender = msg.querySelector('.message-sender');
                    const content = msg.querySelector('div:not(.message-sender):not(.message-time)');
                    const time = msg.querySelector('.message-time');
                    
                    return {
                        text: content ? content.innerHTML : '',
                        isUser: false,
                        sender: 'admin',
                        sender_name: sender ? sender.textContent : 'Admin',
                        timestamp: time ? time.textContent : new Date().toISOString()
                    };
                } else {
                    const content = msg.querySelector('div:not(.message-time)');
                    const time = msg.querySelector('.message-time');
                    
                    return {
                        text: content ? content.innerHTML : '',
                        isUser: true,
                        sender: 'user',
                        sender_name: '<?php echo $username; ?>',
                        timestamp: time ? time.textContent : new Date().toISOString()
                    };
                }
            });
            
            // Keep only the last 50 messages
            messages = messages.slice(-50);
            
            // Save to cookie via AJAX
                    fetch('chat.php', {
                        method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=save_chat_history&chat_history=${encodeURIComponent(JSON.stringify(messages))}`
            });
        }

        function clearChatHistory() {
            // Clear the chat messages container
            chatMessages.innerHTML = '';
            // Add welcome message
            addMessage('Welcome to Ered Hotel! I\'m here to help you with any questions about our hotels, rooms, or general information. Please select from the options on the left to get started.', false);
            
            // Call the server to clear chat history
                    fetch('chat.php', {
                        method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear_chat_history'
                    })
                    .then(response => response.json())
                    .then(data => {
                if (data.success) {
                    // Reset chat history array
                    chatHistory = [];
                    historyLoaded = false;
                    
                    // Show confirmation notification
                    const notification = document.getElementById('chatHistoryNotification');
                    notification.querySelector('.chat-notification-title').textContent = 'Chat History Cleared';
                    notification.querySelector('.chat-notification-message').textContent = 'Your chat history has been cleared successfully.';
                    notification.style.display = 'flex';
                    
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error clearing chat history:', error);
            });
        }

        function deleteChatCookie() {
            // Delete the chat history cookie
            document.cookie = 'hotelhive_chat_history=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=; secure=false; samesite=Lax';
        }

        function addMessage(message, isUser = false) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = isUser ? 'message user-message' : 'message bot-message';
            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${message}</p>
                </div>
                <div class="message-time">${new Date().toLocaleTimeString()}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendUserMessage();
            }
        }

        function sendUserMessage() {
            const message = userInput.value.trim();
            if (message) {
                // Add message to chat history
                addUserMessage(message, new Date().toISOString());
                userInput.value = '';
                
                // Send the message to the server
                if (useDatabase && selectedHotelId) {
                    // Use database storage
                    createOrGetConversation(selectedHotelId)
                        .then(conversationId => {
                            if (conversationId) {
                                return sendMessageToDatabase(message);
                            } else {
                                throw new Error('Failed to create conversation');
                            }
                        })
                        .then(data => {
                            // Message sent to database, scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        })
                        .catch(error => {
                            console.error('Error sending message to database:', error);
                            // Fallback to cookie storage
                            sendMessageToCookie(message);
                        });
                } else {
                    // Use cookie storage (fallback)
                    sendMessageToCookie(message);
                }
                });
            }
        }

        function addUserMessage(message, timestamp) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message user-message';
            
            const contentDiv = document.createElement('div');
            contentDiv.innerHTML = message.replace(/\n/g, '<br>');
            
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = formatTimestamp(timestamp);
            
            messageDiv.appendChild(contentDiv);
            messageDiv.appendChild(timeDiv);
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function toggleDropdown(type) {
            const content = document.getElementById(type + 'DropdownContent');
            const header = document.querySelector(`.${type}-questions .dropdown-header`);
            const icon = document.getElementById(type + 'DropdownIcon');
            
            content.classList.toggle('active');
            header.classList.toggle('active');
            icon.style.transform = content.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
        }

        function startLiveChat() {
            // Add connecting message
            addMessage('Connecting you to a customer service representative...', false);
            
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=start_live_chat'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Start checking for admin response and chat closure
                    checkAdminJoined();
                    checkChatClosed();
                }
            });
        }
        
        function checkAdminJoined() {
            // Check if admin has joined the chat
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check_admin_joined'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.admin_joined) {
                    addSystemMessage('A LiveChat Agent has joined the chat.');
                } else {
                    // Continue checking if admin hasn't joined yet
                    setTimeout(checkAdminJoined, 2000);
                }
            });
        }
        
        function checkChatClosed() {
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check_chat_closed'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.chat_closed) {
                    addSystemMessage('Chat session has been closed by the customer service representative.');
                    // Stop checking for admin response
                    clearInterval(adminTypingCheckInterval);
                    clearInterval(messageCheckInterval);
                } else {
                    // Continue checking if chat is still open
                    setTimeout(checkChatClosed, 2000);
                }
            });
        }
        
        function formatTimestamp(timestamp) {
            if (!timestamp) return '';
            
            const date = new Date(timestamp);
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            
            return `${hours}:${minutes}`;
        }

        function sendGeneralMessage(message) {
            if (message) {
                // Add user message to chat
                addUserMessage(message, new Date().toISOString());
                
                // Show typing indicator
                showTypingIndicator();
                
                // Send the message to the server
                if (useDatabase && selectedHotelId) {
                    // Use database storage
                    createOrGetConversation(selectedHotelId)
                        .then(conversationId => {
                            if (conversationId) {
                                return sendMessageToDatabase(message);
                            } else {
                                throw new Error('Failed to create conversation');
                            }
                        })
                        .then(data => {
                            // Remove typing indicator after a short delay
                            setTimeout(() => {
                                hideTypingIndicator();
                                // Add bot response
                                addBotMessage(data.message || "Thank you for your message. Our team will respond shortly.");
                            }, 800);
                        })
                        .catch(error => {
                            console.error('Error sending message to database:', error);
                            // Fallback to cookie storage
                            sendMessageToCookieWithResponse(message);
                        });
                } else {
                    // Use cookie storage (fallback)
                    sendMessageToCookieWithResponse(message);
                }
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    hideTypingIndicator();
                    // Add error message to chat
                    addErrorMessage();
                });
            }
        }

        function showTypingIndicator() {
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'typing-indicator';
            typingIndicator.innerHTML = `
                <span>Typing</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            chatMessages.appendChild(typingIndicator);
            typingIndicator.style.display = 'flex';
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTypingIndicator() {
            const typingIndicator = document.querySelector('.typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        function addBotMessage(message) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot-message';
            messageDiv.innerHTML = `
                <div class="message-content">
                    <p>${message}</p>
                </div>
                <div class="message-time">${new Date().toLocaleTimeString()}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function addErrorMessage() {
            const errorMessage = document.createElement('div');
            errorMessage.className = 'message bot-message';
            errorMessage.innerHTML = 'Sorry, there was an error processing your request. Please try again.';
            chatMessages.appendChild(errorMessage);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html> 
