<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/NotificationComponent.php';

// Check if manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: manager_login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];
$hotel_id = $_SESSION['hotel_id'];

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

// Get hotel main image
$image_sql = "SELECT hotel_image FROM hotel_img WHERE hotel_id = ? ORDER BY hi_id ASC LIMIT 1";
$stmt = $conn->prepare($image_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$image_result = $stmt->get_result();
$hotel_image = $image_result->fetch_assoc();
$main_image = $hotel_image ? $hotel_image['hotel_image'] : 'images/hotel/default_hotel.jpg';

// Get booking statistics
$booking_stats_sql = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
    SUM(total_price) as total_revenue
    FROM bookings 
    WHERE hotel_id = ?";
$stmt = $conn->prepare($booking_stats_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get recent bookings
$recent_bookings_sql = "SELECT b.*, u.first_name, u.last_name, u.email, r.room_type 
                       FROM bookings b 
                       LEFT JOIN users u ON b.user_id = u.user_id 
                       LEFT JOIN rooms r ON b.room_id = r.room_id 
                       WHERE b.hotel_id = ? 
                       ORDER BY b.created_at DESC 
                       LIMIT 5";
$stmt = $conn->prepare($recent_bookings_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$recent_bookings = $stmt->get_result();

// Get room availability
$rooms_sql = "SELECT r.room_id, r.room_type, r.price_per_night, r.max_guests, r.bed_type,
              COUNT(b.booking_id) as active_bookings 
              FROM rooms r 
              LEFT JOIN bookings b ON r.room_id = b.room_id 
              AND b.booking_status = 'confirmed' 
              AND b.check_out_date > CURDATE() 
              WHERE r.hotel_id = ? 
              GROUP BY r.room_id, r.room_type, r.price_per_night, r.max_guests, r.bed_type";
$stmt = $conn->prepare($rooms_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$rooms = $stmt->get_result();

// Get chat statistics (with error handling)
$chat_stats = ['total_conversations' => 0, 'unread_messages' => 0, 'active_conversations' => 0];

// Check if chat tables exist
$table_check_sql = "SHOW TABLES LIKE 'chat_conversations'";
$table_result = $conn->query($table_check_sql);

if ($table_result && $table_result->num_rows > 0) {
    // Tables exist, get chat statistics
    $chat_stats_sql = "SELECT 
        COUNT(DISTINCT c.conversation_id) as total_conversations,
        COUNT(CASE WHEN m.is_read = 0 AND m.sender_type = 'user' THEN 1 END) as unread_messages,
        COUNT(CASE WHEN c.status = 'active' THEN 1 END) as active_conversations
        FROM chat_conversations c
        LEFT JOIN chat_messages m ON c.conversation_id = m.conversation_id
        WHERE c.hotel_id = ?";
    $stmt = $conn->prepare($chat_stats_sql);
    if ($stmt) {
        $stmt->bind_param("i", $hotel_id);
        $stmt->execute();
        $chat_stats_result = $stmt->get_result();
        $chat_stats = $chat_stats_result->fetch_assoc() ?: $chat_stats;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Manager Dashboard - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 2px 10px rgba(255, 215, 0, 0.3);
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-item {
            color: #ffffff !important;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .nav-item:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .nav-item.active {
            background-color: rgba(255,255,255,0.2);
        }

        .main-content {
            padding: 2rem 0;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 2px solid rgba(255, 215, 0, 0.3);
        }
        
        .welcome-content {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .welcome-text {
            flex: 1;
        }
        
        .welcome-image {
            flex: 0 0 200px;
        }
        
        .hotel-main-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.4);
            border-color: rgba(255, 215, 0, 0.6);
        }

        .stat-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-icon.confirmed { background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); color: white; }
        .stat-icon.pending { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
        .stat-icon.cancelled { background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); color: white; }
        .stat-icon.chat { background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%); color: #000000; }
        .stat-icon.conversations { background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); color: white; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #ffd700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            color: #cbd5e1;
            font-size: 1rem;
            font-weight: 500;
        }

        .content-section {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }

        .booking-card {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ffd700;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .booking-id {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }

        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-confirmed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
            color: #333;
        }

        .room-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #22c55e;
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .room-type {
            font-weight: bold;
            color: #22c55e;
            font-size: 1.1rem;
        }

        .room-capacity {
            color: #666;
            font-size: 0.9rem;
        }

        .room-details {
            display: grid;
            gap: 0.75rem;
        }

        .room-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            font-weight: 500;
            color: #333;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
        }

        .btn-primary.active {
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
            color: #000000;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .welcome-content {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-image {
                flex: none;
            }
            
            .hotel-main-image {
                width: 150px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="manager_dashboard.php">
                <i class="fas fa-hotel"></i> Ered Hotel Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php renderNotificationBell(null, $_SESSION['manager_id']); ?>
                    <a href="manager_dashboard.php" class="nav-item active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="bookings.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="manage_payments.php" class="nav-item">
                        <i class="fas fa-list-alt"></i> All Bookings
                    </a>
                    <a href="rooms.php" class="nav-item">
                        <i class="fas fa-bed"></i> Rooms
                    </a>
                    <a href="gallery.php" class="nav-item">
                        <i class="fas fa-images"></i> Gallery
                    </a>
                    <a href="chat_management.php" class="nav-item">
                        <i class="fas fa-comments"></i> Chat Management
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="manager_logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($hotel['manager_name']); ?>!</h1>
                    <p class="welcome-subtitle">Here's an overview of your hotel's performance - <?php echo htmlspecialchars($hotel['name']); ?></p>
                </div>
                <div class="welcome-image">
                    <img src="../<?php echo htmlspecialchars($main_image); ?>" 
                         alt="<?php echo htmlspecialchars($hotel['name']); ?>" 
                         class="hotel-main-image"
                         onerror="this.src='../images/hotel/default_hotel.jpg'; this.onerror=null;">
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon confirmed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['confirmed_bookings']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending_bookings']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cancelled">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['cancelled_bookings']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon chat">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-value"><?php echo $chat_stats['unread_messages'] ?? 0; ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon conversations">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="stat-value"><?php echo $chat_stats['active_conversations'] ?? 0; ?></div>
                <div class="stat-label">Active Chats</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="d-flex justify-content-between mb-4">
            <div>
                <button id="recentBookingsBtn" class="btn btn-primary me-2">Recent Bookings</button>
                <button id="roomAvailabilityBtn" class="btn btn-primary">Room Availability</button>
            </div>
            <div>
                <a href="chat_management.php" class="btn btn-primary">
                    <i class="fas fa-comments"></i> Chat Management
                    <?php if (($chat_stats['unread_messages'] ?? 0) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $chat_stats['unread_messages']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Content Sections -->
        <!-- Recent Bookings Section -->
        <div id="recentBookingsSection" class="content-section" style="display: none;">
            <h2 class="section-title">Recent Bookings</h2>
            <?php if ($recent_bookings->num_rows > 0): ?>
                <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <span class="booking-id">#<?php echo htmlspecialchars($booking['book_number']); ?></span>
                            <span class="booking-status status-<?php echo $booking['booking_status']; ?>">
                                <?php echo ucfirst($booking['booking_status']); ?>
                            </span>
                        </div>
                        <div class="booking-details">
                            <div class="detail-item">
                                <span class="detail-label">Guest</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Room Type</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-in</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Check-out</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No recent bookings found.</div>
            <?php endif; ?>
        </div>

        <!-- Room Availability Section -->
        <div id="roomAvailabilitySection" class="content-section" style="display: none;">
            <h2 class="section-title">Room Availability</h2>
            <?php if ($rooms->num_rows > 0): ?>
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="room-card">
                        <div class="room-header">
                            <span class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></span>
                            <span class="room-capacity">Max Guests: <?php echo $room['max_guests']; ?></span>
                        </div>
                        <div class="room-details">
                            <div class="room-info">
                                <span class="info-label">Price per night:</span>
                                <span class="info-value">RM <?php echo number_format($room['price_per_night'], 2); ?></span>
                            </div>
                            <div class="room-info">
                                <span class="info-label">Bed Type:</span>
                                <span class="info-value"><?php echo htmlspecialchars($room['bed_type']); ?></span>
                            </div>
                            <div class="room-info">
                                <span class="info-label">Active Bookings:</span>
                                <span class="info-value"><?php echo $room['active_bookings']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No rooms found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const recentBookingsBtn = document.getElementById('recentBookingsBtn');
            const roomAvailabilityBtn = document.getElementById('roomAvailabilityBtn');
            const recentBookingsSection = document.getElementById('recentBookingsSection');
            const roomAvailabilitySection = document.getElementById('roomAvailabilitySection');

            // Show recent bookings by default
            recentBookingsSection.style.display = 'block';
            recentBookingsBtn.classList.add('active');

            recentBookingsBtn.addEventListener('click', function() {
                recentBookingsSection.style.display = 'block';
                roomAvailabilitySection.style.display = 'none';
                recentBookingsBtn.classList.add('active');
                roomAvailabilityBtn.classList.remove('active');
            });

            roomAvailabilityBtn.addEventListener('click', function() {
                recentBookingsSection.style.display = 'none';
                roomAvailabilitySection.style.display = 'block';
                recentBookingsBtn.classList.remove('active');
                roomAvailabilityBtn.classList.add('active');
            });
        });
    </script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?> 