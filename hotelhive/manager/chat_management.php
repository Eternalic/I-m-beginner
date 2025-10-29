<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/NotificationManager.php';

// Check if manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: manager_login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];
$hotel_id = $_SESSION['hotel_id'];

// Check if chat tables exist
$table_check_sql = "SHOW TABLES LIKE 'chat_conversations'";
$table_result = $conn->query($table_check_sql);
$chat_tables_exist = $table_result && $table_result->num_rows > 0;

// Get hotel information
$hotel_sql = "SELECT h.*, hm.manager_name 
              FROM hotels h 
              JOIN hotel_managers hm ON h.hotel_id = hm.hotel_id 
              WHERE h.hotel_id = ? AND hm.manager_id = ?";
$stmt = $conn->prepare($hotel_sql);
$stmt->bind_param("ii", $hotel_id, $manager_id);
$stmt->execute();
$hotel_result = $stmt->get_result();
$hotel = $hotel_result->fetch_assoc();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Check if chat tables exist before processing
    if (!$chat_tables_exist) {
        echo json_encode(['success' => false, 'error' => 'Chat tables do not exist. Please create them first.']);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'get_conversations':
            $sql = "SELECT c.*, u.username, u.first_name, u.last_name, u.email,
                           COUNT(m.message_id) as message_count,
                           MAX(m.created_at) as last_message_time
                    FROM chat_conversations c
                    JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN chat_messages m ON c.conversation_id = m.conversation_id
                    WHERE c.hotel_id = ?
                    GROUP BY c.conversation_id
                    ORDER BY last_message_time DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $hotel_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $conversations = [];
            while ($row = $result->fetch_assoc()) {
                $conversations[] = $row;
            }
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            exit;
            
        case 'get_messages':
            $conversation_id = (int)$_POST['conversation_id'];
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
            
            // Mark messages as read
            $update_sql = "UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'user'";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $conversation_id);
            $update_stmt->execute();
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            exit;
            
        case 'send_message':
            $conversation_id = (int)$_POST['conversation_id'];
            $message_content = trim($_POST['message_content']);
            
            if (empty($message_content)) {
                echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
                exit;
            }
            
            $sql = "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message_content, message_type) 
                    VALUES (?, 'manager', ?, ?, 'text')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $conversation_id, $manager_id, $message_content);
            
            if ($stmt->execute()) {
                // Update conversation timestamp
                $update_sql = "UPDATE chat_conversations SET updated_at = NOW() WHERE conversation_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $conversation_id);
                $update_stmt->execute();
                
                // Create notification for user
                $notificationManager = new NotificationManager($conn);
                $notificationManager->notifyNewMessage($conversation_id, 'manager', $message_content);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to send message']);
            }
            exit;
            
        case 'update_conversation_status':
            $conversation_id = (int)$_POST['conversation_id'];
            $status = $_POST['status'];
            
            $sql = "UPDATE chat_conversations SET status = ? WHERE conversation_id = ? AND hotel_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $status, $conversation_id, $hotel_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update status']);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Management - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ffd700;
            --secondary-color: #000000;
            --accent-color: #ffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .container-fluid {
            padding: 0;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border-right: 2px solid rgba(255, 215, 0, 0.3);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            text-align: center;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .nav-section {
            padding: 1rem 0;
        }

        .nav-header {
            padding: 0.5rem 1.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            border-left-color: #ffd700;
        }

        .nav-item.active {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            border-left-color: #ffd700;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .welcome-header {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .welcome-title {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: #cbd5e1;
            font-size: 1.1rem;
        }

        .chat-container {
            display: flex;
            height: 70vh;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .conversations-panel {
            width: 350px;
            border-right: 2px solid rgba(255, 215, 0, 0.3);
            background: rgba(0, 0, 0, 0.8);
        }

        .conversations-header {
            padding: 1rem;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
        }

        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            cursor: pointer;
            transition: all 0.2s;
        }

        .conversation-item:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .conversation-item.active {
            background: rgba(255, 215, 0, 0.2);
        }

        .conversation-user {
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 0.25rem;
        }

        .conversation-preview {
            color: #cbd5e1;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .conversation-status {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .status-closed {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }

        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1rem;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.5);
        }

        .message {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
        }

        .message.user {
            align-items: flex-start;
        }

        .message.manager {
            align-items: flex-end;
        }

        .message-content {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            word-wrap: break-word;
        }

        .message.user .message-content {
            background: rgba(30, 41, 59, 0.8);
            color: #f8fafc;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .message.manager .message-content {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
        }

        .message-meta {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .chat-input-container {
            padding: 1rem;
            border-top: 2px solid rgba(255, 215, 0, 0.3);
            background: rgba(0, 0, 0, 0.8);
        }

        .quick-reply-templates {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .quick-reply-btn {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #ffd700;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-reply-btn:hover {
            background: rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.6);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .quick-reply-btn i {
            font-size: 0.8rem;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 0.5rem;
        }

        .chat-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid rgba(255, 215, 0, 0.3);
            background: rgba(0, 0, 0, 0.8);
            color: #ffffff;
            border-radius: 10px;
        }

        .chat-input:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }

        .chat-send {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .chat-send:hover {
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            font-size: 1.2rem;
        }

        .status-controls {
            display: flex;
            gap: 0.5rem;
        }

        .status-btn {
            padding: 0.25rem 0.75rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
            background: transparent;
            color: #ffd700;
            border-radius: 15px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-btn:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        .status-btn.active {
            background: #ffd700;
            color: #000000;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .chat-container {
                flex-direction: column;
                height: 80vh;
            }

            .conversations-panel {
                width: 100%;
                height: 40%;
                border-right: none;
                border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            }

            .chat-panel {
                height: 60%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-brand">
                    <i class="fas fa-hotel"></i>
                    <?php echo htmlspecialchars($hotel['name']); ?>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Main</div>
                    <a href="manager_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="rooms.php" class="nav-item">
                        <i class="fas fa-bed"></i> Rooms
                    </a>
                    <a href="bookings.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="chat_management.php" class="nav-item active">
                        <i class="fas fa-comments"></i> Chat Management
                    </a>
                    <a href="gallery.php" class="nav-item">
                        <i class="fas fa-images"></i> Gallery
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Settings</div>
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Account</div>
                    <a href="manager_logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="welcome-header">
                    <h1 class="welcome-title">Chat Management</h1>
                    <p class="welcome-subtitle">Manage customer conversations and provide support</p>
                </div>

                <?php if (!$chat_tables_exist): ?>
                <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.3); color: #f59e0b; border-radius: 10px; padding: 1rem; margin-bottom: 2rem;">
                    <h5><i class="fas fa-exclamation-triangle"></i> Chat Tables Not Found</h5>
                    <p class="mb-2">The chat system database tables have not been created yet.</p>
                    <p class="mb-3">Please create the required tables to use the chat management system.</p>
                    <a href="../create_chat_tables.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create Chat Tables
                    </a>
                </div>
                <?php endif; ?>

                <div class="chat-container">
                    <!-- Conversations Panel -->
                    <div class="conversations-panel">
                        <div class="conversations-header">
                            <h5 class="mb-0"><i class="fas fa-comments"></i> Customer Conversations</h5>
                        </div>
                        <div id="conversationsList">
                            <!-- Conversations will be loaded here -->
                        </div>
                    </div>

                    <!-- Chat Panel -->
                    <div class="chat-panel">
                        <div class="chat-header">
                            <div>
                                <h5 class="mb-0" id="chatUserInfo">Select a conversation</h5>
                            </div>
                            <div class="status-controls" id="statusControls" style="display: none;">
                                <button class="status-btn" data-status="active">Active</button>
                                <button class="status-btn" data-status="pending">Pending</button>
                                <button class="status-btn" data-status="closed">Closed</button>
                            </div>
                        </div>
                        <div class="chat-messages" id="chatMessages">
                            <div class="no-conversation">
                                <i class="fas fa-comments"></i> Select a conversation to start chatting
                            </div>
                        </div>
                        <div class="chat-input-container" id="chatInputContainer" style="display: none;">
                            <!-- Quick Reply Templates -->
                            <div class="quick-reply-templates">
                                <button class="quick-reply-btn" onclick="insertQuickReply('greeting')">
                                    <i class="fas fa-hand-wave"></i> Greeting
                                </button>
                                <button class="quick-reply-btn" onclick="insertQuickReply('booking')">
                                    <i class="fas fa-calendar-check"></i> Booking Help
                                </button>
                                <button class="quick-reply-btn" onclick="insertQuickReply('payment')">
                                    <i class="fas fa-credit-card"></i> Payment Info
                                </button>
                                <button class="quick-reply-btn" onclick="insertQuickReply('amenities')">
                                    <i class="fas fa-star"></i> Amenities
                                </button>
                                <button class="quick-reply-btn" onclick="insertQuickReply('checkout')">
                                    <i class="fas fa-door-open"></i> Checkout Info
                                </button>
                                <button class="quick-reply-btn" onclick="insertQuickReply('closing')">
                                    <i class="fas fa-heart"></i> Closing
                                </button>
                            </div>
                            <div class="chat-input-wrapper">
                                <input type="text" class="chat-input" id="messageInput" placeholder="Type your message...">
                                <button class="chat-send" id="sendButton">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        let conversations = [];

        // Load conversations
        function loadConversations() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_conversations'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    conversations = data.conversations;
                    renderConversations();
                }
            })
            .catch(error => console.error('Error loading conversations:', error));
        }

        // Render conversations list
        function renderConversations() {
            const container = document.getElementById('conversationsList');
            container.innerHTML = '';

            conversations.forEach(conv => {
                const item = document.createElement('div');
                item.className = 'conversation-item';
                item.dataset.conversationId = conv.conversation_id;
                
                const statusClass = `status-${conv.status}`;
                const lastMessage = conv.last_message_time ? new Date(conv.last_message_time).toLocaleString() : 'No messages';
                
                item.innerHTML = `
                    <div class="conversation-user">${conv.first_name} ${conv.last_name}</div>
                    <div class="conversation-preview">${conv.email}</div>
                    <div class="conversation-meta">
                        <span class="conversation-status ${statusClass}">${conv.status}</span>
                        <span>${conv.message_count} messages</span>
                    </div>
                `;
                
                item.addEventListener('click', () => selectConversation(conv.conversation_id));
                container.appendChild(item);
            });
        }

        // Select conversation
        function selectConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-conversation-id="${conversationId}"]`).classList.add('active');
            
            // Load messages
            loadMessages(conversationId);
            
            // Show chat input
            document.getElementById('chatInputContainer').style.display = 'block';
            document.getElementById('statusControls').style.display = 'flex';
            
            // Update header
            const conv = conversations.find(c => c.conversation_id == conversationId);
            document.getElementById('chatUserInfo').textContent = `${conv.first_name} ${conv.last_name} - ${conv.email}`;
        }

        // Load messages
        function loadMessages(conversationId) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_messages&conversation_id=${conversationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages);
                }
            })
            .catch(error => console.error('Error loading messages:', error));
        }

        // Render messages
        function renderMessages(messages) {
            const container = document.getElementById('chatMessages');
            container.innerHTML = '';

            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.sender_type}`;
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = msg.message_content;
                
                const metaDiv = document.createElement('div');
                metaDiv.className = 'message-meta';
                metaDiv.textContent = `${msg.sender_type === 'user' ? msg.username : 'Manager'} • ${new Date(msg.created_at).toLocaleString()}`;
                
                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(metaDiv);
                container.appendChild(messageDiv);
            });
            
            container.scrollTop = container.scrollHeight;
        }

        // Send message
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentConversationId) return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_message&conversation_id=${currentConversationId}&message_content=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(currentConversationId);
                    loadConversations(); // Refresh conversations list
                } else {
                    alert('Failed to send message: ' + data.error);
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }

        // Update conversation status
        function updateStatus(status) {
            if (!currentConversationId) return;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_conversation_status&conversation_id=${currentConversationId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadConversations(); // Refresh conversations list
                } else {
                    alert('Failed to update status: ' + data.error);
                }
            })
            .catch(error => console.error('Error updating status:', error));
        }

        // Event listeners
        document.getElementById('sendButton').addEventListener('click', sendMessage);
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.dataset.status;
                updateStatus(status);
            });
        });

        // Quick Reply Templates
        const quickReplyTemplates = {
            greeting: "Hello! Thank you for contacting Ered Hotel. How can I assist you today?",
            booking: "I'd be happy to help you with your booking. Could you please provide your booking reference number or the dates you're looking for?",
            payment: "For payment-related inquiries, please note that we accept major credit cards and bank transfers. All transactions are secure and encrypted.",
            amenities: "Our hotel offers a wide range of amenities including free WiFi, fitness center, spa services, room service, and concierge assistance. Is there anything specific you'd like to know about?",
            checkout: "Check-out time is 11:00 AM. Late check-out may be available upon request (subject to availability and additional charges). Would you like me to check availability for your stay?",
            closing: "Thank you for choosing Ered Hotel! If you have any other questions, please don't hesitate to reach out. We look forward to welcoming you soon!"
        };

        function insertQuickReply(templateType) {
            const input = document.getElementById('messageInput');
            if (quickReplyTemplates[templateType]) {
                input.value = quickReplyTemplates[templateType];
                input.focus();
            }
        }

        // Auto-refresh conversations every 30 seconds
        setInterval(loadConversations, 30000);

        // Initial load
        loadConversations();
    </script>
</body>
</html>
