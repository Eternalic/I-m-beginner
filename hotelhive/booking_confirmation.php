<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/NotificationManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Fetch booking details
$booking_number = 'N/A';
$booking_data = null;

if ($booking_id > 0) {
    $booking_query = "SELECT b.*, h.name as hotel_name, h.location, h.city, h.country,
                             r.room_type, r.price_per_night, r.max_guests,
                             u.first_name, u.last_name, u.email, u.phone
                     FROM bookings b 
                      JOIN hotels h ON b.hotel_id = h.hotel_id
                      JOIN rooms r ON b.room_id = r.room_id
                      JOIN users u ON b.user_id = u.user_id
                      WHERE b.booking_id = ? AND b.user_id = ?";
    
    $booking_stmt = $conn->prepare($booking_query);
    $booking_stmt->bind_param('ii', $booking_id, $user_id);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    
    if ($booking_result && $booking_result->num_rows > 0) {
        $booking_data = $booking_result->fetch_assoc();
        $booking_number = $booking_data['book_number'];
        // Use actual booking status from database
        $status = $booking_data['booking_status'];
        
        // Auto-create chat conversation for this booking
        $hotel_id = $booking_data['hotel_id'];
        
        // Check if chat tables exist
        $table_check_sql = "SHOW TABLES LIKE 'chat_conversations'";
        $table_result = $conn->query($table_check_sql);
        $chat_tables_exist = $table_result && $table_result->num_rows > 0;
        
        if ($chat_tables_exist) {
            // Check if conversation already exists for this booking
            $conv_check_sql = "SELECT conversation_id FROM chat_conversations WHERE user_id = ? AND hotel_id = ? AND status != 'closed'";
            $conv_stmt = $conn->prepare($conv_check_sql);
            $conv_stmt->bind_param("ii", $user_id, $hotel_id);
            $conv_stmt->execute();
            $existing_conv = $conv_stmt->get_result()->fetch_assoc();
            $conv_stmt->close();
            
            if (!$existing_conv) {
                // Create new conversation
                $create_conv_sql = "INSERT INTO chat_conversations (user_id, hotel_id, status) VALUES (?, ?, 'active')";
                $create_stmt = $conn->prepare($create_conv_sql);
                $create_stmt->bind_param("ii", $user_id, $hotel_id);
                
                if ($create_stmt->execute()) {
                    $conversation_id = $conn->insert_id;
                    
                    // Add welcome message from system
                    $welcome_msg = "Hello! Your booking #{$booking_number} has been confirmed. Our team is here to assist you with any questions about your stay at {$booking_data['hotel_name']}. Feel free to ask about amenities, check-in/check-out times, or any special requests!";
                    
                    $msg_sql = "INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message_content, message_type) VALUES (?, 'system', NULL, ?, 'system')";
                    $msg_stmt = $conn->prepare($msg_sql);
                    $msg_stmt->bind_param("is", $conversation_id, $welcome_msg);
                    $msg_stmt->execute();
                    
                    // Create booking confirmation notification
                    $notificationManager = new NotificationManager($conn);
                    $notification_data = [
                        'user_id' => $_SESSION['user_id'],
                        'hotel_id' => $booking_data['hotel_id'],
                        'type' => 'booking',
                        'title' => 'Booking Confirmed - ' . $booking_number,
                        'message' => "Your booking at {$booking_data['hotel_name']} has been confirmed. Check-in: {$booking_data['check_in']}",
                        'is_important' => 1,
                        'data' => ['booking_id' => $booking_data['booking_id'], 'status' => 'confirmed']
                    ];
                    $notificationManager->createNotification($notification_data);
                    $msg_stmt->close();
                }
                $create_stmt->close();
            }
        }
    }
    $booking_stmt->close();
}

// Determine display status and payment info (only cash payment available)
$display_status = $status;
$payment_display = "Cash Payment at Hotel";
$payment_status_display = "To be paid at hotel during check-in";

$payment_date = date('M j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f8fafc;
        }
        
        .confirmation-container {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            padding: 2rem;
            text-align: center;
        }
        
        .confirmation-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .confirmation-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .booking-details {
            padding: 2rem;
        }
        
        .detail-section {
            margin-bottom: 2rem;
        }
        
        .detail-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .hotel-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .hotel-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .hotel-location {
            color: #666;
            margin-bottom: 0;
        }
        
        .receipt-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .pending-message {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .confirmation-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
        <div class="confirmation-container">
                    <!-- Header -->
                    <div class="confirmation-header">
                        <h1><i class="fas fa-check-circle"></i> Booking Confirmation</h1>
                        <p>Your hotel reservation has been processed</p>
                        <div class="status-badge status-<?php echo $display_status; ?>">
                            <?php echo ucfirst($display_status); ?>
                </div>
            </div>

                    <!-- Booking Details -->
                <div class="booking-details">
                        <?php if ($display_status === 'confirmed'): ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i>
                                Thank you for booking with Ered Hotel. Your reservation has been confirmed by hotel staff. Please complete your payment at the hotel during check-in. You can view your booking details below.
                    </div>
                        <?php else: ?>
                            <div class="pending-message">
                                <i class="fas fa-clock"></i>
                                Thank you for booking with Ered Hotel. Your reservation is pending confirmation by hotel staff. Please complete your payment at the hotel during check-in. You can view your booking details below.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Hotel Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-hotel"></i> Hotel Information</h3>
                            <div class="hotel-info">
                                <div class="hotel-name"><?php echo htmlspecialchars($booking_data['hotel_name']); ?></div>
                                <div class="hotel-location"><?php echo htmlspecialchars($booking_data['location'] . ', ' . $booking_data['city'] . ', ' . $booking_data['country']); ?></div>
                            </div>
                        </div>
                        
                        <!-- Booking Details -->
                        <div class="detail-section">
                            <h3><i class="fas fa-calendar-check"></i> Booking Details</h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Booking Number:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking_number); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Room Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking_data['room_type']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Check-in Date:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($booking_data['check_in_date'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Check-out Date:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($booking_data['check_out_date'])); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Guests:</span>
                                    <span class="detail-value"><?php echo $booking_data['max_guests']; ?> person(s)</span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Total Amount:</span>
                                    <span class="detail-value">RM <?php echo number_format($booking_data['total_price'], 2); ?></span>
                                </div>
                            </div>
                    </div>
                    
                        <!-- Payment Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                        <span class="detail-label">Payment Method:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($payment_display); ?></span>
                    </div>
                                
                                <div class="detail-item">
                        <span class="detail-label">Payment Status:</span>
                                    <span class="detail-value" style="color: #f39c12; font-weight: 500;">
                            <?php echo htmlspecialchars($payment_status_display); ?>
                        </span>
                    </div>
                            </div>
                        </div>
                        
                        <!-- Guest Information -->
                        <div class="detail-section">
                            <h3><i class="fas fa-user"></i> Guest Information</h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking_data['first_name'] . ' ' . $booking_data['last_name']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking_data['email']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($booking_data['phone']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Special Requests -->
                        <?php if (!empty($booking_data['special_requests'])): ?>
                        <div class="detail-section">
                            <h3><i class="fas fa-star"></i> Special Requests</h3>
                            <p><?php echo htmlspecialchars($booking_data['special_requests']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Receipt Section -->
                        <div class="receipt-section">
                            <h3><i class="fas fa-receipt"></i> Receipt</h3>
                            <p style="margin-bottom: 15px;">Booking processed on <?php echo $payment_date; ?></p>
                            
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="manage_bookings.php" class="btn-download">
                                    <i class="fas fa-list"></i> View All Bookings
                                </a>
                                <?php if ($chat_tables_exist): ?>
                                    <a href="chat.php?hotel_id=<?php echo $booking_data['hotel_id']; ?>" class="btn-download" style="background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%); color: #000000;">
                                        <i class="fas fa-comments"></i> Chat with Hotel
                                    </a>
                                <?php endif; ?>
                            </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 