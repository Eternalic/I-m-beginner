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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_type = trim($_POST['room_type']);
    $price_per_night = (float)$_POST['price_per_night'];
    $max_guests = (int)$_POST['max_guests'];
    $bed_type = trim($_POST['bed_type']);
    $availability = isset($_POST['availability']) ? 1 : 0;
    
    // Handle image upload
    $image_url = null;
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/room/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                $image_url = 'images/room/' . $new_filename;
            }
        }
    }

    // Validate input
    $errors = [];
    if (empty($room_type)) {
        $errors[] = "Room type is required";
    }
    if ($price_per_night <= 0) {
        $errors[] = "Price per night must be greater than 0";
    }
    if ($max_guests <= 0) {
        $errors[] = "Maximum guests must be greater than 0";
    }
    if (empty($bed_type)) {
        $errors[] = "Bed type is required";
    }

    if (empty($errors)) {
        // Insert room details
        $insert_sql = "INSERT INTO rooms (hotel_id, room_type, price_per_night, max_guests, bed_type, image_url, availability) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isdsssi", $hotel_id, $room_type, $price_per_night, $max_guests, $bed_type, $image_url, $availability);
        
        if ($stmt->execute()) {
            $room_id = $conn->insert_id;
            
            // Handle room amenities if provided
            if (!empty($_POST['amenities'])) {
                $amenities = trim($_POST['amenities']);
                $room_size = trim($_POST['room_size']);
                $beds = trim($_POST['beds']);
                
                $amenity_sql = "INSERT INTO room_amenities (room_id, amenities, room_size, beds) VALUES (?, ?, ?, ?)";
                $amenity_stmt = $conn->prepare($amenity_sql);
                $amenity_stmt->bind_param("isss", $room_id, $amenities, $room_size, $beds);
                $amenity_stmt->execute();
            }
            
            $_SESSION['success_message'] = "Room added successfully";
            header("Location: rooms.php");
            exit();
        } else {
            $errors[] = "Error adding room: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --danger-color: #dc2626;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .card {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .card-header {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            border-bottom: 2px solid rgba(255, 215, 0, 0.4);
            padding: 1.5rem;
        }

        .card-title {
            color: #000000;
            font-weight: 600;
            margin: 0;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 2px solid rgba(255, 215, 0, 0.3);
            background: rgba(0, 0, 0, 0.8);
            color: #ffffff;
            padding: 0.625rem 1rem;
        }
        
        .form-control::placeholder, .form-select::placeholder {
            color: #94a3b8;
        }

        .form-control:focus, .form-select:focus {
            border-color: #ffd700;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
            background: rgba(0, 0, 0, 0.9);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffffff 100%);
            color: #000000;
            border: 2px solid rgba(255, 215, 0, 0.3);
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ffffff 0%, #ffd700 100%);
            color: #000000;
            border-color: #ffd700;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .input-group-text {
            background-color: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-right: none;
            color: #ffd700;
        }
        
        .alert {
            border-radius: 0.5rem;
            border: none;
        }

        .alert-danger {
            background-color: rgba(220, 38, 38, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .form-check-input:checked {
            background-color: #ffd700;
            border-color: #ffd700;
        }
        
        .form-check-label {
            color: #cbd5e1;
        }

        .back-link {
            color: #ffd700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            color: #ffffff;
        }

        .back-link i {
            margin-right: 0.5rem;
        }
        
        .text-muted {
            color: #94a3b8 !important;
        }

        .image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }

        .optional-section {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }

        .optional-section h6 {
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="rooms.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Rooms
        </a>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add New Room</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="room_type" class="form-label">Room Type *</label>
                            <input type="text" class="form-control" id="room_type" name="room_type" 
                                   value="<?php echo isset($_POST['room_type']) ? htmlspecialchars($_POST['room_type']) : ''; ?>" 
                                   placeholder="e.g., Deluxe Suite, Standard Room" required>
                        </div>
                        <div class="col-md-6">
                            <label for="price_per_night" class="form-label">Price per Night *</label>
                            <div class="input-group">
                                <span class="input-group-text">RM</span>
                                <input type="number" class="form-control" id="price_per_night" name="price_per_night" 
                                       value="<?php echo isset($_POST['price_per_night']) ? htmlspecialchars($_POST['price_per_night']) : ''; ?>" 
                                       min="0" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="max_guests" class="form-label">Maximum Guests *</label>
                            <input type="number" class="form-control" id="max_guests" name="max_guests" 
                                   value="<?php echo isset($_POST['max_guests']) ? htmlspecialchars($_POST['max_guests']) : ''; ?>" 
                                   min="1" placeholder="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="bed_type" class="form-label">Bed Type *</label>
                            <select class="form-select" id="bed_type" name="bed_type" required>
                                <option value="">Select Bed Type</option>
                                <option value="Single" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Double" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'Double') ? 'selected' : ''; ?>>Double</option>
                                <option value="Queen" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'Queen') ? 'selected' : ''; ?>>Queen</option>
                                <option value="King" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'King') ? 'selected' : ''; ?>>King</option>
                                <option value="Twin" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'Twin') ? 'selected' : ''; ?>>Twin</option>
                                <option value="King + Queen" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'King + Queen') ? 'selected' : ''; ?>>King + Queen</option>
                                <option value="King + Twin" <?php echo (isset($_POST['bed_type']) && $_POST['bed_type'] === 'King + Twin') ? 'selected' : ''; ?>>King + Twin</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room_image" class="form-label">Room Image</label>
                        <input type="file" class="form-control" id="room_image" name="room_image" 
                               accept="image/*" onchange="previewImage(this)">
                        <small class="text-muted">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
                        <div id="imagePreview" class="mt-2"></div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="availability" name="availability" 
                                   <?php echo (isset($_POST['availability']) && $_POST['availability']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="availability">
                                Room Available
                            </label>
                        </div>
                    </div>

                    <!-- Optional Room Amenities Section -->
                    <div class="optional-section">
                        <h6>Room Amenities (Optional)</h6>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="amenities" class="form-label">Amenities</label>
                                <textarea class="form-control" id="amenities" name="amenities" rows="3" 
                                          placeholder="e.g., Free WiFi, Air Conditioning, TV, Mini Bar, Safe, Desk, Phone, Hairdryer, Iron, Coffee Maker"><?php echo isset($_POST['amenities']) ? htmlspecialchars($_POST['amenities']) : ''; ?></textarea>
                                <small class="text-muted">Separate amenities with commas</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="room_size" class="form-label">Room Size</label>
                                <input type="text" class="form-control" id="room_size" name="room_size" 
                                       value="<?php echo isset($_POST['room_size']) ? htmlspecialchars($_POST['room_size']) : ''; ?>" 
                                       placeholder="e.g., 28 m²">
                            </div>
                            <div class="col-md-6">
                                <label for="beds" class="form-label">Bed Configuration</label>
                                <input type="text" class="form-control" id="beds" name="beds" 
                                       value="<?php echo isset($_POST['beds']) ? htmlspecialchars($_POST['beds']) : ''; ?>" 
                                       placeholder="e.g., 1 king bed">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Room
                        </button>
                        <a href="rooms.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    img.alt = 'Room Image Preview';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Auto-fill bed configuration when bed type changes
        document.getElementById('bed_type').addEventListener('change', function() {
            const bedType = this.value;
            const bedsInput = document.getElementById('beds');
            
            if (bedType && !bedsInput.value) {
                switch(bedType) {
                    case 'Single':
                        bedsInput.value = '1 single bed';
                        break;
                    case 'Double':
                        bedsInput.value = '1 double bed';
                        break;
                    case 'Queen':
                        bedsInput.value = '1 queen bed';
                        break;
                    case 'King':
                        bedsInput.value = '1 king bed';
                        break;
                    case 'Twin':
                        bedsInput.value = '2 twin beds';
                        break;
                    case 'King + Queen':
                        bedsInput.value = '1 king bed and 1 queen bed';
                        break;
                    case 'King + Twin':
                        bedsInput.value = '1 king bed and 2 twin beds';
                        break;
                }
            }
        });
    </script>
</body>
</html>
