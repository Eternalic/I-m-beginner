<?php
session_start();
require_once '../db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager_id'])) {
    header("Location: manager_login.php");
    exit();
}

$manager_id = $_SESSION['manager_id'];
$hotel_id = $_SESSION['hotel_id'];

// Get room ID from URL
if (!isset($_GET['id'])) {
    header("Location: rooms.php");
    exit();
}

$room_id = $_GET['id'];

// Get room details
$room_sql = "SELECT r.*, h.name as hotel_name 
             FROM rooms r 
             JOIN hotels h ON r.hotel_id = h.hotel_id 
             WHERE r.room_id = ? AND r.hotel_id = ?";
$stmt = $conn->prepare($room_sql);
$stmt->bind_param("ii", $room_id, $hotel_id);
$stmt->execute();
$room_result = $stmt->get_result();
$room = $room_result->fetch_assoc();

if (!$room) {
    header("Location: rooms.php");
    exit();
}

// Get room amenities
$amenities_sql = "SELECT * FROM room_amenities WHERE room_id = ?";
$stmt = $conn->prepare($amenities_sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$amenities_result = $stmt->get_result();
$amenities = array();
while ($amenity = $amenities_result->fetch_assoc()) {
    $amenities[] = $amenity;
}

// Get hotel facilities
$facilities_sql = "SELECT facility FROM hotel_facilities WHERE h_id = ?";
$stmt = $conn->prepare($facilities_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$facilities_result = $stmt->get_result();
$hotel_facilities = array();
while ($facility = $facilities_result->fetch_assoc()) {
    $hotel_facilities[] = $facility['facility'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .room-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .room-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .room-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }

        .room-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        
        .no-image-placeholder {
            width: 100%;
            height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.8);
            color: #ffd700;
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        
        .no-image-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .no-image-placeholder span {
            font-size: 1.2rem;
        }

        .details-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .details-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1e293b;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }

        .detail-item i {
            width: 2rem;
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .detail-label {
            font-weight: 500;
            color: #475569;
            margin-right: 0.5rem;
        }

        .detail-value {
            color: #1e293b;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .amenity-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .amenity-item i {
            color: var(--success-color);
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-available {
            background-color: #dcfce7;
            color: #059669;
        }
        
        .status-occupied {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-decoration: none;
        }

        .btn-back:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="rooms.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Rooms
        </a>

        <div class="room-header">
            <h1 class="room-title"><?php echo htmlspecialchars($room['room_type']); ?></h1>
            <p class="room-subtitle"><?php echo htmlspecialchars($room['hotel_name']); ?></p>
        </div>

        <?php if (!empty($room['image_url'])): ?>
            <img src="../<?php echo htmlspecialchars($room['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($room['room_type']); ?>" 
                 class="room-image"
                 onerror="this.src='../images/room/default_room.jpg'; this.onerror=null;">
        <?php else: ?>
            <img src="../images/room/default_room.jpg" 
                 alt="<?php echo htmlspecialchars($room['room_type']); ?>" 
                 class="room-image"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="no-image-placeholder" style="display: none;">
                <i class="fas fa-image"></i>
                <span>No Image Available</span>
            </div>
        <?php endif; ?>

        <div class="details-card">
            <h2 class="details-title">Room Information</h2>
            <div class="detail-item">
                <i class="fas fa-dollar-sign"></i>
                <span class="detail-label">Price per Night:</span>
                <span class="detail-value">RM <?php echo number_format($room['price_per_night'], 2); ?></span>
            </div>
            <div class="detail-item">
                <i class="fas fa-user"></i>
                <span class="detail-label">Maximum Guests:</span>
                <span class="detail-value"><?php echo $room['max_guests']; ?></span>
            </div>
            <div class="detail-item">
                <i class="fas fa-bed"></i>
                <span class="detail-label">Bed Type:</span>
                <span class="detail-value"><?php echo htmlspecialchars($room['bed_type']); ?></span>
            </div>
            <div class="detail-item">
                <i class="fas fa-info-circle"></i>
                <span class="detail-label">Status:</span>
                <span class="status-badge status-<?php echo $room['availability'] ? 'available' : 'occupied'; ?>">
                    <?php echo $room['availability'] ? 'Available' : 'Occupied'; ?>
                </span>
            </div>
        </div>

        <?php if (!empty($amenities)): ?>
        <div class="details-card">
            <h2 class="details-title">Room Amenities</h2>
            <div class="amenities-grid">
                <?php foreach ($amenities as $amenity): ?>
                    <div class="amenity-item">
                        <i class="fas fa-check"></i>
                        <span><?php echo htmlspecialchars($amenity['amenities']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($hotel_facilities)): ?>
        <div class="details-card">
            <h2 class="details-title">Hotel Facilities</h2>
            <div class="amenities-grid">
                <?php foreach ($hotel_facilities as $facility): ?>
                    <div class="amenity-item">
                        <i class="fas fa-check"></i>
                        <span><?php echo htmlspecialchars($facility); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?> 