<?php
session_start();
require_once 'db.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Fetch user data first
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get chat history for the user
$chat_history = [];
$unread_messages = 0;

// Check if chat tables exist
$table_check_sql = "SHOW TABLES LIKE 'chat_conversations'";
$table_result = $conn->query($table_check_sql);
$chat_tables_exist = $table_result && $table_result->num_rows > 0;

if ($chat_tables_exist) {
    // Get user's chat conversations
    $chat_sql = "SELECT c.*, h.name as hotel_name, h.location as hotel_location,
                        COUNT(m.message_id) as message_count,
                        MAX(m.created_at) as last_message_time,
                        COUNT(CASE WHEN m.is_read = 0 AND m.sender_type = 'manager' THEN 1 END) as unread_count
                 FROM chat_conversations c
                 JOIN hotels h ON c.hotel_id = h.hotel_id
                 LEFT JOIN chat_messages m ON c.conversation_id = m.conversation_id
                 WHERE c.user_id = ?
                 GROUP BY c.conversation_id
                 ORDER BY last_message_time DESC";
    $stmt = $conn->prepare($chat_sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $chat_result = $stmt->get_result();
    
    while ($row = $chat_result->fetch_assoc()) {
        $chat_history[] = $row;
        $unread_messages += $row['unread_count'];
    }
    $stmt->close();
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_img'])) {
    $target_dir = "images/profile/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($_FILES["profile_img"]["name"]);
    
    // Check if image file is actual image
    if (!empty($_FILES["profile_img"]["tmp_name"]) && file_exists($_FILES["profile_img"]["tmp_name"])) {
        $check = getimagesize($_FILES["profile_img"]["tmp_name"]);
        if ($check !== false) {
            // Delete old profile image if it exists and is different from new one
            if (!empty($user['profile_img']) && file_exists($user['profile_img']) && $user['profile_img'] !== $target_file) {
                unlink($user['profile_img']);
            }
            
            if (move_uploaded_file($_FILES["profile_img"]["tmp_name"], $target_file)) {
                // Update database with new profile image path
                $stmt = $conn->prepare("UPDATE users SET profile_img = ? WHERE user_id = ?");
                $stmt->bind_param("si", $target_file, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $_SESSION['profile_img'] = $target_file;
                    $user['profile_img'] = $target_file;
                    $success_message = "Profile picture updated successfully!";
                }
                $stmt->close();
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Basic validation
    if (empty($username)) {
        $error_message = "Username is required.";
    } elseif (empty($email)) {
        $error_message = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Check if username is already taken
        if ($username !== $user['username']) {
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $check_stmt->bind_param("si", $username, $_SESSION['user_id']);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_message = "Username is already taken. Please choose another one.";
            }
            $check_stmt->close();
        }
        
        // Check if email is already taken
        if ($email !== $user['email']) {
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_stmt->bind_param("si", $email, $_SESSION['user_id']);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_message = "Email is already taken. Please choose another one.";
            }
            $check_stmt->close();
        }
    }
    
    if (!isset($error_message)) {
        // Update user information
        $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $username, $first_name, $last_name, $email, $phone, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username; // Update session username
            $success_message = "Profile updated successfully!";
            // Refresh user data to show updated information
            $stmt->close(); // Close the update statement first
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error_message = "Error updating profile. Please try again.";
            $stmt->close();
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!password_verify($current_password, $user_data['password_hash'])) {
        $error_message = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } else {
        // Update password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $password_hash, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Password updated successfully!";
        } else {
            $error_message = "Error updating password. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Ered Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #f8fafc;
            line-height: 1.8;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            padding: 15px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .nav-menu a {
            color: #f8fafc;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .nav-menu a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }

        /* Profile Section */
        .profile-section {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 40px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .profile-title-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-image {
            position: relative;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #ffd700;
            background: rgba(30, 41, 59, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-image i {
            font-size: 24px;
            color: #ffd700;
        }

        .profile-image-preview {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
            border: 3px solid #ffd700;
            background: rgba(30, 41, 59, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-image-preview i {
            font-size: 40px;
            color: #ffd700;
        }

        .image-upload-group {
            text-align: center;
            margin-bottom: 30px;
        }

        .image-upload-group input[type="file"] {
            display: none;
        }

        .change-photo-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: transparent;
            color: #cbd5e1;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 12px;
        }

        .change-photo-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            border-color: #ffd700;
        }

        .change-photo-btn i {
            font-size: 14px;
        }

        .profile-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .edit-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-group label {
            display: block;
            font-size: 14px;
            color: #cbd5e1;
            margin-bottom: 5px;
        }

        .info-group .value {
            font-size: 16px;
            color: #f8fafc;
            font-weight: 500;
        }

        /* Edit Profile Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            z-index: 1001;
            max-height: 90vh;
            overflow-y: auto;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .modal-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #cbd5e1;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #ffd700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #cbd5e1;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            color: #f8fafc;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            background: rgba(30, 41, 59, 0.95);
            outline: none;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            color: #f8fafc;
            font-size: 15px;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s ease;
        }

        .form-group textarea:focus {
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            background: rgba(30, 41, 59, 0.95);
            outline: none;
        }

        .char-count {
            display: block;
            text-align: right;
            font-size: 12px;
            color: #cbd5e1;
            margin-top: 5px;
        }

        .char-count.warning {
            color: #ff6b35;
        }

        .char-count.error {
            color: #dc3545;
        }

        .save-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            border: none;
            padding: 14px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .save-btn:hover {
            background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .password-input-group {
            position: relative;
            width: 100%;
        }

        .password-input-group .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            padding: 5px;
        }

        .password-input-group .toggle-password:hover {
            color: #1a1a1a;
        }

        .button-group {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }

        .button-group button {
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 140px;
            border: none;
        }

        .save-btn {
            background: #c8a97e;
            color: #fff;
        }

        .save-btn:hover {
            background: #b69468;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .change-password-btn {
            background: transparent;
            color: #f8fafc;
            border: 1px solid rgba(255, 215, 0, 0.5);
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .change-password-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: #ffd700;
            color: #ffd700;
            transform: translateY(-2px);
        }

        /* Mobile Dropdown Styles */
        .mobile-profile-dropdown {
            display: none;
        }

        .mobile-dropdown-btn {
            display: none;
            background: #c8a97e;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .mobile-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 200px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            z-index: 1000;
            right: 20px;
            top: 100%;
            margin-top: 10px;
        }

        .mobile-dropdown-content a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #1a1a1a;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .mobile-dropdown-content a:hover {
            background: #f8f8f8;
            color: #c8a97e;
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }

            .modal {
                width: 95%;
                padding: 20px;
            }

            .profile-actions {
                display: none;
            }

            .mobile-profile-dropdown {
                display: block;
                position: relative;
            }

            .mobile-dropdown-btn {
                display: block;
            }

            .mobile-dropdown-content.active {
                display: block;
            }
        }

        /* Add these dropdown styles */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .nav-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px 16px;
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 500;
        }

        .nav-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 180px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            z-index: 1000;
        }

        .nav-dropdown.active .nav-dropdown-content {
            display: block;
        }

        .nav-dropdown-content a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #1a1a1a;
            font-size: 14px;
            transition: all 0.3s ease;
            margin: 0;
        }

        .nav-dropdown-content a:hover {
            background: #f8f8f8;
            color: #c8a97e;
        }

        .nav-dropdown i {
            font-size: 12px;
            color: #666;
        }

        /* Chat History Styles */
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
        }

        .chat-history-section {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .chat-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }

        .chat-history-header h2 {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            font-size: 1.8rem;
        }

        .close-chat-btn {
            background: none;
            border: none;
            color: #cbd5e1;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .close-chat-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
        }

        .chat-history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-item {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
            border-color: rgba(255, 215, 0, 0.6);
        }

        .chat-hotel-info h4 {
            color: #ffd700;
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
        }

        .chat-hotel-info p {
            color: #cbd5e1;
            margin: 0;
            font-size: 0.9rem;
        }

        .chat-meta {
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .chat-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-closed {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .chat-stats {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .message-count {
            color: #cbd5e1;
            font-size: 0.85rem;
        }

        .unread-count {
            color: #ef4444;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .chat-time {
            color: #94a3b8;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <a href="SignedIn_homepage.php" class="logo">Ered Hotel</a>
                <div class="nav-menu">
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn">
                            Menu <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="SignedIn_homepage.php">Home</a>
                            <a href="manage_bookings.php">Your Bookings</a>
                            <?php if ($chat_tables_exist): ?>
                                <a href="#chat-history" onclick="showChatHistory()">
                                    Chat History
                                    <?php if ($unread_messages > 0): ?>
                                        <span class="unread-badge"><?php echo $unread_messages; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                            <a href="SignedIn_homepage.php?signout=true">Sign Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-title-section">
                    <div class="profile-image">
                        <?php if (!empty($user['profile_img'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_img']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <h1>My Profile</h1>
                </div>
                <div class="profile-actions">
                    <button class="change-password-btn" onclick="openPasswordModal()">Change Password</button>
                    <button class="edit-btn" onclick="openEditModal()">Edit Profile</button>
                </div>
                <div class="mobile-profile-dropdown">
                    <button class="mobile-dropdown-btn" onclick="toggleMobileDropdown()">
                        Profile Actions <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="mobile-dropdown-content">
                        <a href="javascript:void(0)" onclick="openPasswordModal()">Change Password</a>
                        <a href="javascript:void(0)" onclick="openEditModal()">Edit Profile</a>
                    </div>
                </div>
            </div>
            <div class="profile-info">
                <div class="info-group">
                    <label>Username</label>
                    <div class="value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="info-group">
                    <label>Email</label>
                    <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-group">
                    <label>First Name</label>
                    <div class="value"><?php echo htmlspecialchars($user['first_name'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-group">
                    <label>Last Name</label>
                    <div class="value"><?php echo htmlspecialchars($user['last_name'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-group">
                    <label>Phone</label>
                    <div class="value"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-group">
                </div>
            </div>
        </div>

        <!-- Chat History Section -->
        <?php if ($chat_tables_exist && !empty($chat_history)): ?>
        <div class="chat-history-section" id="chat-history-section" style="display: none;">
            <div class="chat-history-header">
                <h2><i class="fas fa-comments"></i> Chat History</h2>
                <button class="close-chat-btn" onclick="hideChatHistory()">&times;</button>
            </div>
            <div class="chat-history-list">
                <?php foreach ($chat_history as $chat): ?>
                    <div class="chat-item" onclick="openChatConversation(<?php echo $chat['conversation_id']; ?>)">
                        <div class="chat-hotel-info">
                            <h4><?php echo htmlspecialchars($chat['hotel_name']); ?></h4>
                            <p><?php echo htmlspecialchars($chat['hotel_location']); ?></p>
                        </div>
                        <div class="chat-meta">
                            <div class="chat-status status-<?php echo $chat['status']; ?>">
                                <?php echo ucfirst($chat['status']); ?>
                            </div>
                            <div class="chat-stats">
                                <span class="message-count"><?php echo $chat['message_count']; ?> messages</span>
                                <?php if ($chat['unread_count'] > 0): ?>
                                    <span class="unread-count"><?php echo $chat['unread_count']; ?> unread</span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-time">
                                <?php echo date('M d, Y H:i', strtotime($chat['last_message_time'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="own_account.php" enctype="multipart/form-data">
                <div class="image-upload-group">
                    <div class="profile-image-preview">
                        <?php if (!empty($user['profile_img'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_img']); ?>" alt="Profile Picture Preview">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="change-photo-btn" onclick="document.getElementById('profile_img').click()">
                        <i class="fas fa-camera"></i>
                        Change Photo
                    </button>
                    <input type="file" id="profile_img" name="profile_img" accept="image/*" onchange="previewImage(this)">
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                </div>
                <input type="hidden" name="update_profile" value="1">
                <button type="submit" class="save-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Change Password</h2>
                <button class="close-btn" onclick="closePasswordModal()">&times;</button>
            </div>
            <form method="POST" action="own_account.php">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-group">
                        <input type="password" id="current_password" name="current_password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-input-group">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-group">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="update_password" value="1">
                <div class="button-group">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <button type="button" class="save-btn" onclick="closePasswordModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Auto hide success and error messages after 2 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.success-message, .error-message');
            
            messages.forEach(function(message) {
                if (message) {
                    setTimeout(function() {
                        message.style.transition = 'all 0.5s ease';
                        message.style.opacity = '0';
                        message.style.transform = 'translateY(-10px)';
                        setTimeout(function() {
                            message.style.display = 'none';
                        }, 500);
                    }, 2000);
                }
            });
        });

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const passwordModal = document.getElementById('passwordModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === passwordModal) {
                closePasswordModal();
            }
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
                closePasswordModal();
            }
        });

        function previewImage(input) {
            const preview = document.querySelector('.profile-image-preview img');
            const icon = document.querySelector('.profile-image-preview i');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        icon.parentNode.replaceChild(newImg, icon);
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }


        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Page initialization code
        });

        // Update the form to handle file upload
        document.querySelector('#editModal form').setAttribute('enctype', 'multipart/form-data');

        document.addEventListener('DOMContentLoaded', function() {
            const navDropdown = document.querySelector('.nav-dropdown');
            const navDropdownBtn = document.querySelector('.nav-dropdown-btn');

            navDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                navDropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-dropdown')) {
                    navDropdown.classList.remove('active');
                }
            });
        });

        function toggleMobileDropdown() {
            const dropdown = document.querySelector('.mobile-dropdown-content');
            dropdown.classList.toggle('active');
        }

        // Close mobile dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.mobile-dropdown-content');
            const dropdownBtn = document.querySelector('.mobile-dropdown-btn');
            
            if (!event.target.closest('.mobile-profile-dropdown') && dropdown.classList.contains('active')) {
                dropdown.classList.remove('active');
            }
        });

        // Chat History Functions
        function showChatHistory() {
            const chatSection = document.getElementById('chat-history-section');
            if (chatSection) {
                chatSection.style.display = 'block';
                chatSection.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function hideChatHistory() {
            const chatSection = document.getElementById('chat-history-section');
            if (chatSection) {
                chatSection.style.display = 'none';
            }
        }

        function openChatConversation(conversationId) {
            // Open the chat system with the specific conversation
            // This could redirect to a dedicated chat page or open a modal
            window.open(`chat.php?conversation_id=${conversationId}`, '_blank');
        }
    </script>
</body>
</html> 