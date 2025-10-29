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

// Get hotel images with proper error handling
$images_sql = "SELECT hotel_image FROM hotel_img WHERE hotel_id = ? ORDER BY hi_id ASC";
$stmt = $conn->prepare($images_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$images_result = $stmt->get_result();
$hotel_images = array();
while ($image = $images_result->fetch_assoc()) {
    $image_path = '../' . $image['hotel_image'];
    if (file_exists($image_path)) {
        $hotel_images[] = $image['hotel_image'];
    }
}

// Get hotel facilities with proper sorting
$facilities_sql = "SELECT facility FROM hotel_facilities WHERE h_id = ? ORDER BY facility ASC";
$stmt = $conn->prepare($facilities_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$facilities_result = $stmt->get_result();
$hotel_facilities = array();
while ($facility = $facilities_result->fetch_assoc()) {
    $hotel_facilities[] = $facility['facility'];
}

// Get filter values
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$price_min = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$guests = isset($_GET['guests']) ? $_GET['guests'] : '';

// Build the query
$rooms_sql = "SELECT r.* FROM rooms r WHERE r.hotel_id = ?";

$params = array($hotel_id);
$types = "i";

if ($type_filter) {
    $rooms_sql .= " AND r.room_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($availability_filter !== '') {
    $rooms_sql .= " AND r.availability = ?";
    $params[] = $availability_filter;
    $types .= "i";
}

if ($search) {
    $search_term = "%$search%";
    $rooms_sql .= " AND r.room_type LIKE ?";
    $params[] = $search_term;
    $types .= "s";
}

if ($price_min !== '') {
    $rooms_sql .= " AND r.price_per_night >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if ($price_max !== '') {
    $rooms_sql .= " AND r.price_per_night <= ?";
    $params[] = $price_max;
    $types .= "d";
}

if ($guests !== '') {
    $rooms_sql .= " AND r.max_guests >= ?";
    $params[] = $guests;
    $types .= "i";
}

$rooms_sql .= " ORDER BY r.room_type";

$stmt = $conn->prepare($rooms_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rooms = $stmt->get_result();

// Get unique room types for filter
$types_sql = "SELECT DISTINCT room_type FROM rooms WHERE hotel_id = ?";
$stmt = $conn->prepare($types_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$room_types = $stmt->get_result();

// Deals table no longer exists

// Get room amenities data
$amenities_sql = "SELECT r.room_id, r.room_type, 
                  GROUP_CONCAT(ra.amenities) as all_amenities,
                  GROUP_CONCAT(ra.room_size) as room_sizes,
                  GROUP_CONCAT(ra.beds) as bed_configs
                  FROM rooms r 
                  LEFT JOIN room_amenities ra ON r.room_id = ra.room_id 
                  WHERE r.hotel_id = ?
                  GROUP BY r.room_id, r.room_type";
$stmt = $conn->prepare($amenities_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$amenities = $stmt->get_result();

// Get reviews data
$reviews_sql = "SELECT r.*, u.username, u.profile_img 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                WHERE r.hotel_id = ? 
                ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviews_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Ered Hotel</title>
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
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .container-fluid {
            max-width: 100%;
            padding: 0;
            overflow-x: hidden;
        }

        .sidebar {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border-right: 2px solid rgba(255, 215, 0, 0.3);
            min-height: 100vh;
            position: fixed;
            width: 280px;
            padding: 1.5rem;
            color: #fff;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-brand i {
            margin-right: 0.5rem;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-header {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background-color: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
        }

        .nav-item i {
            width: 1.5rem;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            width: calc(100% - 280px);
            overflow-x: hidden;
        }

        .welcome-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .welcome-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }

        .filters {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .room-card {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .room-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .room-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .room-image:hover {
            transform: scale(1.05);
        }

        .room-content {
            padding: 1.5rem;
        }

        .room-type {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .room-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }

        .room-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            color: #64748b;
            font-size: 0.875rem;
        }

        .detail-item i {
            width: 1.25rem;
            margin-right: 0.5rem;
            color: #94a3b8;
        }

        .room-facilities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .facility-badge {
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .facility-badge i {
            font-size: 0.875rem;
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
        
        .status-maintenance {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-cleaning {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
            color: #000000;
            border-color: #ffd700;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .form-label {
            color: #475569;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            padding: 0.625rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        .dropdown-item form {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item .form-select {
            width: 100%;
            font-size: 0.875rem;
        }
        
        .dropdown-item i {
            width: 1.25rem;
            margin-right: 0.5rem;
        }

        .no-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.8);
            color: #ffd700;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .no-image-placeholder i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .no-image-placeholder span {
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                display: none;
            }

            .rooms-grid {
                grid-template-columns: 1fr;
            }
        }

        .room-actions {
            margin-top: 1rem;
            text-align: right;
        }

        .dropdown-menu {
            min-width: 200px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-item i {
            width: 1.25rem;
            text-align: center;
        }

        .dropdown-item.text-danger {
            color: #dc2626 !important;
        }

        .dropdown-item.text-danger:hover {
            background-color: #fee2e2;
        }

        /* Deals Grid Styles */
        .deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .deal-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }

        .deal-card:hover {
            transform: translateY(-5px);
        }

        .deal-content {
            padding: 1.5rem;
        }

        .deal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .deal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .deal-discount {
            background-color: var(--success-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .deal-details {
            margin-bottom: 1rem;
        }

        .deal-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .feature-badge {
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .feature-badge i {
            font-size: 0.875rem;
            color: var(--success-color);
        }

        .deal-actions {
            margin-top: 1rem;
            text-align: right;
        }

        @media (max-width: 768px) {
            .deals-grid {
                grid-template-columns: 1fr;
            }
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .review-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }

        .review-card:hover {
            transform: translateY(-5px);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .review-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .review-user i {
            font-size: 2rem;
            color: #94a3b8;
        }

        .review-username {
            font-weight: 600;
            color: #1e293b;
        }

        .review-rating {
            color: #fbbf24;
        }

        .review-content {
            color: #475569;
        }

        .review-comment {
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .review-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .review-date i {
            margin-right: 0.25rem;
        }

        .profile-picture {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }

        /* Amenities Grid Styles */
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .amenity-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }

        .amenity-card:hover {
            transform: translateY(-5px);
        }

        .amenity-content {
            padding: 1.5rem;
        }

        .amenity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .amenity-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .amenity-size {
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .amenity-details {
            margin-bottom: 1rem;
        }

        .amenity-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .feature-badge {
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .feature-badge i {
            font-size: 0.875rem;
            color: var(--success-color);
        }

        .amenity-actions {
            margin-top: 1rem;
            text-align: right;
        }

        @media (max-width: 768px) {
            .amenities-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .alert-success {
            background-color: rgba(34, 197, 94, 0.2);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
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
                    <a href="bookings.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="manage_payments.php" class="nav-item">
                        <i class="fas fa-list-alt"></i> All Bookings
                    </a>
                    <a href="rooms.php" class="nav-item active">
                        <i class="fas fa-bed"></i> Hotels
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Management</div>
                    <a href="profile.php" class="nav-item">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        Hotel Info
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="welcome-title">Manage Hotels</h1>
                            <p class="welcome-subtitle">View and manage all rooms in your hotel</p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="addDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-plus"></i> Add
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="addDropdown">
                                <li>
                                    <a class="dropdown-item" href="add_room.php">
                                        <i class="fas fa-bed"></i> Add Room
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Add this after the welcome-header div -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- View Switch -->
                <div class="view-switch mb-4">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="view" id="hotelView" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="hotelView">
                            <i class="fas fa-hotel"></i> Hotel
                        </label>
                        <input type="radio" class="btn-check" name="view" id="roomView" autocomplete="off">
                        <label class="btn btn-outline-primary" for="roomView">
                            <i class="fas fa-bed"></i> Rooms
                        </label>
                        <input type="radio" class="btn-check" name="view" id="amenityView" autocomplete="off">
                        <label class="btn btn-outline-primary" for="amenityView">
                            <i class="fas fa-couch"></i> Room Amenity
                        </label>
                        <input type="radio" class="btn-check" name="view" id="reviewsView" autocomplete="off">
                        <label class="btn btn-outline-primary" for="reviewsView">
                            <i class="fas fa-star"></i> Reviews
                        </label>
                    </div>
                </div>

                <!-- Hotel View (initially visible) -->
                <div id="hotelViewContent">
                    <div class="hotel-details mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Hotel Information</h5>
                                        <div class="detail-item">
                                            <i class="fas fa-hotel"></i>
                                            <span class="detail-label">Name:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($hotel['name']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span class="detail-label">Location:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($hotel['location']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-city"></i>
                                            <span class="detail-label">City:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($hotel['city']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-flag"></i>
                                            <span class="detail-label">Country:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($hotel['country']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Hotel Description</h5>
                                        <p class="card-text"><?php echo htmlspecialchars($hotel['description']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hotel-images mb-4">
                        <h5 class="mb-3">Hotel Images</h5>
                        <div class="row">
                            <?php if (!empty($hotel_images)): ?>
                                <?php foreach ($hotel_images as $image): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="../<?php echo htmlspecialchars($image); ?>" 
                                                 class="card-img-top" 
                                                 alt="Hotel Image"
                                                 onerror="this.src='../images/default-hotel.jpg'">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No images available for this hotel
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="hotel-facilities">
                        <h5 class="mb-3">Hotel Facilities</h5>
                        <div class="amenities-grid">
                            <?php if (!empty($hotel_facilities)): ?>
                                <?php foreach ($hotel_facilities as $facility): ?>
                                    <div class="amenity-item">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span><?php echo htmlspecialchars($facility); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No facilities listed for this hotel
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Room View (initially hidden) -->
                <div id="roomViewContent" style="display: none;">
                    <!-- Filters -->
                    <div class="filters">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Room Type</label>
                                <select id="typeFilter" class="form-select">
                                    <option value="">All Types</option>
                                    <?php while ($type = $room_types->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($type['room_type']); ?>">
                                            <?php echo htmlspecialchars($type['room_type']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Availability</label>
                                <select id="availabilityFilter" class="form-select">
                                    <option value="">All</option>
                                    <option value="1">Available</option>
                                    <option value="0">Occupied</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Price Range</label>
                                <div class="input-group">
                                    <input type="number" id="priceMin" class="form-control" placeholder="Min">
                                    <input type="number" id="priceMax" class="form-control" placeholder="Max">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Max Guests</label>
                                <input type="number" id="guestsFilter" class="form-control" placeholder="Min guests">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="searchFilter" class="form-control" placeholder="Search room type...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rooms Grid -->
                    <div class="rooms-grid" id="roomsGrid">
                        <?php if ($rooms->num_rows > 0): ?>
                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                <div class="room-card" 
                                     data-type="<?php echo htmlspecialchars($room['room_type']); ?>"
                                     data-availability="<?php echo $room['availability']; ?>"
                                     data-price="<?php echo $room['price_per_night']; ?>"
                                     data-guests="<?php echo $room['max_guests']; ?>">
                                    <div class="room-image-container">
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
                                    </div>
                                    <div class="room-content">
                                        <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                        <div class="room-price">RM <?php echo number_format($room['price_per_night'], 2); ?> / night</div>
                                        <div class="room-details">
                                            <div class="detail-item">
                                                <i class="fas fa-user"></i>
                                                Max: <?php echo $room['max_guests']; ?> guests
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-bed"></i>
                                                <?php echo htmlspecialchars($room['bed_type']); ?>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-info-circle"></i>
                                                Status: 
                                                <span class="status-badge status-<?php 
                                                    switch($room['availability']) {
                                                        case '1': echo 'available'; break;
                                                        case '0': echo 'occupied'; break;
                                                        case '2': echo 'maintenance'; break;
                                                        case '3': echo 'cleaning'; break;
                                                        default: echo 'available';
                                                    }
                                                ?>">
                                                    <?php 
                                                    switch($room['availability']) {
                                                        case '1': echo 'Available'; break;
                                                        case '0': echo 'Occupied'; break;
                                                        case '2': echo 'Maintenance'; break;
                                                        case '3': echo 'Cleaning'; break;
                                                        default: echo 'Available';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="room-facilities">
                                            <?php foreach ($hotel_facilities as $facility): ?>
                                                <span class="facility-badge">
                                                    <i class="fas fa-check"></i>
                                                    <?php echo htmlspecialchars($facility); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="room-actions">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="roomActions<?php echo $room['room_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i> More
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="roomActions<?php echo $room['room_id']; ?>">
                                                    <li>
                                                        <a class="dropdown-item" href="room_details.php?id=<?php echo $room['room_id']; ?>">
                                                            <i class="fas fa-info-circle"></i> View Details
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="edit_room.php?id=<?php echo $room['room_id']; ?>">
                                                            <i class="fas fa-edit"></i> Edit Room
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="changeRoomStatus(<?php echo $room['room_id']; ?>, '<?php echo $room['availability']; ?>')">
                                                            <i class="fas fa-toggle-on"></i> Change Status
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $room['room_id']; ?>)">
                                                            <i class="fas fa-trash"></i> Delete Room
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No rooms found</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Room Amenity View (initially hidden) -->
                <div id="amenityViewContent" style="display: none;">
                    <!-- Filters -->
                    <div class="filters">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Room Type</label>
                                <select id="amenityRoomFilter" class="form-select">
                                    <option value="">All Rooms</option>
                                    <?php while ($room = $rooms->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($room['room_type']); ?>">
                                            <?php echo htmlspecialchars($room['room_type']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="amenitySearch" class="form-control" placeholder="Search amenities...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room Size</label>
                                <div class="input-group">
                                    <input type="number" id="amenitySizeMin" class="form-control" placeholder="Min m²">
                                    <input type="number" id="amenitySizeMax" class="form-control" placeholder="Max m²">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amenities Grid -->
                    <div class="amenities-grid" id="amenitiesGrid">
                        <?php if ($amenities->num_rows > 0): ?>
                            <?php while ($amenity = $amenities->fetch_assoc()): ?>
                                <div class="amenity-card" 
                                     data-room="<?php echo htmlspecialchars($amenity['room_type']); ?>"
                                     data-size="<?php echo htmlspecialchars($amenity['room_sizes']); ?>"
                                     data-amenities="<?php echo htmlspecialchars($amenity['all_amenities']); ?>">
                                    <div class="amenity-content">
                                        <div class="amenity-header">
                                            <h5 class="amenity-title"><?php echo htmlspecialchars($amenity['room_type']); ?></h5>
                                            <span class="amenity-size"><?php echo htmlspecialchars(explode(',', $amenity['room_sizes'])[0]); ?></span>
                                        </div>
                                        <div class="amenity-details">
                                            <div class="detail-item">
                                                <i class="fas fa-bed"></i>
                                                <?php echo htmlspecialchars(explode(',', $amenity['bed_configs'])[0]); ?>
                                            </div>
                                            <div class="amenity-features">
                                                <?php 
                                                $all_features = array_unique(explode(',', str_replace(', ', ',', $amenity['all_amenities'])));
                                                foreach ($all_features as $feature): 
                                                    if (!empty(trim($feature))):
                                                ?>
                                                    <span class="feature-badge">
                                                        <i class="fas fa-check"></i>
                                                        <?php echo htmlspecialchars(trim($feature)); ?>
                                                    </span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </div>
                                        <div class="amenity-actions">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="amenityActions<?php echo $amenity['room_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i> More
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="amenityActions<?php echo $amenity['room_id']; ?>">
                                                    <li>
                                                        <a class="dropdown-item" href="edit_amenity.php?id=<?php echo $amenity['room_id']; ?>">
                                                            <i class="fas fa-edit"></i> Edit Amenities
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="confirmDeleteAmenity(<?php echo $amenity['room_id']; ?>)">
                                                            <i class="fas fa-trash"></i> Delete Amenities
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No amenities found</div>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Reviews View (initially hidden) -->
                <div id="reviewsViewContent" style="display: none;">
                    <!-- Filters -->
                    <div class="filters">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Rating</label>
                                <select id="ratingFilter" class="form-select">
                                    <option value="">All Ratings</option>
                                    <option value="5">5 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="2">2 Stars</option>
                                    <option value="1">1 Star</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <div class="input-group">
                                    <input type="date" id="reviewDateFrom" class="form-control" placeholder="From">
                                    <input type="date" id="reviewDateTo" class="form-control" placeholder="To">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="reviewSearch" class="form-control" placeholder="Search by username or comment...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Grid -->
                    <div class="reviews-grid" id="reviewsGrid">
                        <?php if ($reviews->num_rows > 0): ?>
                            <?php while ($review = $reviews->fetch_assoc()): ?>
                                <div class="review-card" 
                                     data-rating="<?php echo $review['rating']; ?>"
                                     data-date="<?php echo date('Y-m-d', strtotime($review['created_at'])); ?>"
                                     data-username="<?php echo htmlspecialchars($review['username']); ?>"
                                     data-comment="<?php echo htmlspecialchars($review['comment']); ?>">
                                    <div class="review-header">
                                        <div class="review-user">
                                            <?php if (!empty($review['profile_img'])): ?>
                                                <img src="../<?php echo htmlspecialchars($review['profile_img']); ?>" 
                                                     alt="Profile Picture" 
                                                     class="profile-picture"
                                                     onerror="this.src='../images/default-profile.jpg'">
                                            <?php else: ?>
                                                <i class="fas fa-user-circle"></i>
                                            <?php endif; ?>
                                            <span class="review-username"><?php echo htmlspecialchars($review['username']); ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                                        <div class="review-meta">
                                            <span class="review-date">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No reviews found</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusChangeModal" tabindex="-1" aria-labelledby="statusChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusChangeModalLabel">Change Room Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Select the new status for this room:</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" id="setAvailable">
                            <i class="fas fa-check-circle"></i> Available
                        </button>
                        <button type="button" class="btn btn-warning" id="setOccupied">
                            <i class="fas fa-times-circle"></i> Occupied
                        </button>
                        <button type="button" class="btn btn-info" id="setMaintenance">
                            <i class="fas fa-tools"></i> Maintenance
                        </button>
                        <button type="button" class="btn btn-primary" id="setCleaning">
                            <i class="fas fa-broom"></i> Cleaning
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDelete(roomId) {
        if (confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
            window.location.href = 'delete_room.php?id=' + roomId;
        }
    }

    let currentRoomId = null;
    let statusChangeModal = null;

    function changeRoomStatus(roomId, currentStatus) {
        currentRoomId = roomId;
        
        // Initialize modal if not already done
        if (!statusChangeModal) {
            statusChangeModal = new bootstrap.Modal(document.getElementById('statusChangeModal'));
        }
        
        // Show the modal
        statusChangeModal.show();
    }

    function submitStatusChange(newStatus) {
        if (currentRoomId) {
            // Create a form to submit the status change
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'change_room_status.php';
            
            const roomIdInput = document.createElement('input');
            roomIdInput.type = 'hidden';
            roomIdInput.name = 'room_id';
            roomIdInput.value = currentRoomId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'new_status';
            statusInput.value = newStatus;
            
            form.appendChild(roomIdInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const roomView = document.getElementById('roomView');
        const hotelView = document.getElementById('hotelView');
        const reviewsView = document.getElementById('reviewsView');
        const roomViewContent = document.getElementById('roomViewContent');
        const hotelViewContent = document.getElementById('hotelViewContent');
        const reviewsViewContent = document.getElementById('reviewsViewContent');
        const amenityView = document.getElementById('amenityView');
        const amenityViewContent = document.getElementById('amenityViewContent');

        roomView.addEventListener('change', function() {
            if (this.checked) {
                roomViewContent.style.display = 'block';
                hotelViewContent.style.display = 'none';
                reviewsViewContent.style.display = 'none';
                amenityViewContent.style.display = 'none';
            }
        });

        hotelView.addEventListener('change', function() {
            if (this.checked) {
                roomViewContent.style.display = 'none';
                hotelViewContent.style.display = 'block';
                reviewsViewContent.style.display = 'none';
                amenityViewContent.style.display = 'none';
            }
        });


        reviewsView.addEventListener('change', function() {
            if (this.checked) {
                roomViewContent.style.display = 'none';
                hotelViewContent.style.display = 'none';
                reviewsViewContent.style.display = 'block';
                amenityViewContent.style.display = 'none';
            }
        });

        amenityView.addEventListener('change', function() {
            if (this.checked) {
                roomViewContent.style.display = 'none';
                hotelViewContent.style.display = 'none';
                reviewsViewContent.style.display = 'none';
                amenityViewContent.style.display = 'block';
            }
        });

        const typeFilter = document.getElementById('typeFilter');
        const availabilityFilter = document.getElementById('availabilityFilter');
        const priceMin = document.getElementById('priceMin');
        const priceMax = document.getElementById('priceMax');
        const guestsFilter = document.getElementById('guestsFilter');
        const searchFilter = document.getElementById('searchFilter');
        const roomsGrid = document.getElementById('roomsGrid');
        const roomCards = document.querySelectorAll('.room-card');

        function filterRooms() {
            const typeValue = typeFilter.value.toLowerCase();
            const availabilityValue = availabilityFilter.value;
            const priceMinValue = parseFloat(priceMin.value) || 0;
            const priceMaxValue = parseFloat(priceMax.value) || Infinity;
            const guestsValue = parseInt(guestsFilter.value) || 0;
            const searchValue = searchFilter.value.toLowerCase();

            roomCards.forEach(card => {
                const type = card.dataset.type.toLowerCase();
                const availability = card.dataset.availability;
                const price = parseFloat(card.dataset.price);
                const guests = parseInt(card.dataset.guests);

                const typeMatch = !typeValue || type.includes(typeValue);
                const availabilityMatch = !availabilityValue || availability === availabilityValue;
                const priceMatch = price >= priceMinValue && price <= priceMaxValue;
                const guestsMatch = !guestsValue || guests >= guestsValue;
                const searchMatch = !searchValue || type.includes(searchValue);

                if (typeMatch && availabilityMatch && priceMatch && guestsMatch && searchMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show "No rooms found" message if all cards are hidden
            const visibleCards = Array.from(roomCards).filter(card => card.style.display !== 'none');
            const noRoomsMessage = roomsGrid.querySelector('.alert-info');
            
            if (visibleCards.length === 0) {
                if (!noRoomsMessage) {
                    const message = document.createElement('div');
                    message.className = 'alert alert-info';
                    message.textContent = 'No rooms found matching your criteria';
                    roomsGrid.appendChild(message);
                }
            } else if (noRoomsMessage) {
                noRoomsMessage.remove();
            }
        }

        // Add event listeners for all filter inputs
        [typeFilter, availabilityFilter, priceMin, priceMax, guestsFilter, searchFilter].forEach(input => {
            input.addEventListener('input', filterRooms);
        });

        // Add debounce to search input
        let searchTimeout;
        searchFilter.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterRooms, 300);
        });


        // Reviews filtering functionality
        const ratingFilter = document.getElementById('ratingFilter');
        const reviewDateFrom = document.getElementById('reviewDateFrom');
        const reviewDateTo = document.getElementById('reviewDateTo');
        const reviewSearch = document.getElementById('reviewSearch');
        const reviewsGrid = document.getElementById('reviewsGrid');
        const reviewCards = document.querySelectorAll('.review-card');

        function filterReviews() {
            const ratingValue = ratingFilter.value;
            const dateFromValue = reviewDateFrom.value;
            const dateToValue = reviewDateTo.value;
            const searchValue = reviewSearch.value.toLowerCase();

            reviewCards.forEach(card => {
                const rating = card.dataset.rating;
                const date = card.dataset.date;
                const username = card.dataset.username.toLowerCase();
                const comment = card.dataset.comment.toLowerCase();

                const ratingMatch = !ratingValue || rating === ratingValue;
                const dateMatch = (!dateFromValue || date >= dateFromValue) && 
                                (!dateToValue || date <= dateToValue);
                const searchMatch = !searchValue || 
                                  username.includes(searchValue) || 
                                  comment.includes(searchValue);

                if (ratingMatch && dateMatch && searchMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show "No reviews found" message if all cards are hidden
            const visibleCards = Array.from(reviewCards).filter(card => card.style.display !== 'none');
            const noReviewsMessage = reviewsGrid.querySelector('.alert-info');
            
            if (visibleCards.length === 0) {
                if (!noReviewsMessage) {
                    const message = document.createElement('div');
                    message.className = 'alert alert-info';
                    message.textContent = 'No reviews found matching your criteria';
                    reviewsGrid.appendChild(message);
                }
            } else if (noReviewsMessage) {
                noReviewsMessage.remove();
            }
        }

        // Add event listeners for all review filter inputs
        [ratingFilter, reviewDateFrom, reviewDateTo, reviewSearch].forEach(input => {
            input.addEventListener('input', filterReviews);
        });

        // Add debounce to review search input
        let reviewSearchTimeout;
        reviewSearch.addEventListener('input', function() {
            clearTimeout(reviewSearchTimeout);
            reviewSearchTimeout = setTimeout(filterReviews, 300);
        });

        // Amenity filtering functionality
        const amenityRoomFilter = document.getElementById('amenityRoomFilter');
        const amenitySearch = document.getElementById('amenitySearch');
        const amenitySizeMin = document.getElementById('amenitySizeMin');
        const amenitySizeMax = document.getElementById('amenitySizeMax');
        const amenitiesGrid = document.getElementById('amenitiesGrid');
        const amenityCards = document.querySelectorAll('.amenity-card');

        function filterAmenities() {
            const roomValue = amenityRoomFilter.value.toLowerCase();
            const searchValue = amenitySearch.value.toLowerCase();
            const sizeMinValue = parseFloat(amenitySizeMin.value) || 0;
            const sizeMaxValue = parseFloat(amenitySizeMax.value) || Infinity;

            amenityCards.forEach(card => {
                const room = card.dataset.room.toLowerCase();
                const size = parseFloat(card.dataset.size);
                const amenities = card.dataset.amenities.toLowerCase();

                const roomMatch = !roomValue || room.includes(roomValue);
                const sizeMatch = size >= sizeMinValue && size <= sizeMaxValue;
                const searchMatch = !searchValue || amenities.includes(searchValue);

                if (roomMatch && sizeMatch && searchMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show "No amenities found" message if all cards are hidden
            const visibleCards = Array.from(amenityCards).filter(card => card.style.display !== 'none');
            const noAmenitiesMessage = amenitiesGrid.querySelector('.alert-info');
            
            if (visibleCards.length === 0) {
                if (!noAmenitiesMessage) {
                    const message = document.createElement('div');
                    message.className = 'alert alert-info';
                    message.textContent = 'No amenities found matching your criteria';
                    amenitiesGrid.appendChild(message);
                }
            } else if (noAmenitiesMessage) {
                noAmenitiesMessage.remove();
            }
        }

        // Add event listeners for all amenity filter inputs
        [amenityRoomFilter, amenitySearch, amenitySizeMin, amenitySizeMax].forEach(input => {
            input.addEventListener('input', filterAmenities);
        });

        // Add debounce to search input
        let amenitySearchTimeout;
        amenitySearch.addEventListener('input', function() {
            clearTimeout(amenitySearchTimeout);
            amenitySearchTimeout = setTimeout(filterAmenities, 300);
        });

        function confirmDeleteAmenity(amenityId) {
            Swal.fire({
                title: 'Delete Amenity',
                text: 'Are you sure you want to delete this amenity? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_amenity.php?id=' + amenityId;
                }
            });
        }

        // Add SweetAlert2 CSS and JS
        const swalCSS = document.createElement('link');
        swalCSS.rel = 'stylesheet';
        swalCSS.href = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';
        document.head.appendChild(swalCSS);

        const swalJS = document.createElement('script');
        swalJS.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.body.appendChild(swalJS);

        // Status change modal event listeners
        document.getElementById('setAvailable').addEventListener('click', function() {
            submitStatusChange('1');
        });

        document.getElementById('setOccupied').addEventListener('click', function() {
            submitStatusChange('0');
        });

        document.getElementById('setMaintenance').addEventListener('click', function() {
            submitStatusChange('2');
        });

        document.getElementById('setCleaning').addEventListener('click', function() {
            submitStatusChange('3');
        });
    });
    </script>
</body>
</html>
<?php
$conn->close();
?> 