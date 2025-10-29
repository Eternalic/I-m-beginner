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
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate room ID and check if it belongs to manager's hotel
$room_sql = "SELECT r.* FROM rooms r 
             JOIN hotels h ON r.hotel_id = h.hotel_id 
             JOIN hotel_managers hm ON h.hotel_id = hm.hotel_id 
             WHERE r.room_id = ? AND hm.manager_id = ? AND r.hotel_id = ?";
$stmt = $conn->prepare($room_sql);
$stmt->bind_param("iii", $room_id, $manager_id, $hotel_id);
$stmt->execute();
$room_result = $stmt->get_result();

if ($room_result->num_rows === 0) {
    header("Location: rooms.php");
    exit();
}

$room = $room_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_type = trim($_POST['room_type']);
    $price_per_night = (float)$_POST['price_per_night'];
    $max_guests = (int)$_POST['max_guests'];
    $bed_type = trim($_POST['bed_type']);
    $availability = isset($_POST['availability']) ? 1 : 0;

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
        // Update room details
        $update_sql = "UPDATE rooms SET 
                      room_type = ?, 
                      price_per_night = ?, 
                      max_guests = ?, 
                      bed_type = ?, 
                      availability = ? 
                      WHERE room_id = ? AND hotel_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sdisiii", $room_type, $price_per_night, $max_guests, $bed_type, $availability, $room_id, $hotel_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Room updated successfully";
            header("Location: rooms.php");
            exit();
        } else {
            $errors[] = "Error updating room: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room - Ered Hotel</title>
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
            background-color: #f8fafc;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem;
        }

        .card-title {
            color: #1e293b;
            font-weight: 600;
            margin: 0;
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

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .alert {
            border-radius: 0.5rem;
            border: none;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger-color);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .back-link {
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .back-link i {
            margin-right: 0.5rem;
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
                <h5 class="card-title">Edit Room</h5>
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

                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="room_type" class="form-label">Room Type</label>
                            <input type="text" class="form-control" id="room_type" name="room_type" 
                                   value="<?php echo htmlspecialchars($room['room_type']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="price_per_night" class="form-label">Price per Night</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price_per_night" name="price_per_night" 
                                       value="<?php echo htmlspecialchars($room['price_per_night']); ?>" 
                                       min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="max_guests" class="form-label">Maximum Guests</label>
                            <input type="number" class="form-control" id="max_guests" name="max_guests" 
                                   value="<?php echo htmlspecialchars($room['max_guests']); ?>" 
                                   min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="bed_type" class="form-label">Bed Type</label>
                            <select class="form-select" id="bed_type" name="bed_type" required>
                                <option value="">Select Bed Type</option>
                                <option value="Single" <?php echo $room['bed_type'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Double" <?php echo $room['bed_type'] === 'Double' ? 'selected' : ''; ?>>Double</option>
                                <option value="Queen" <?php echo $room['bed_type'] === 'Queen' ? 'selected' : ''; ?>>Queen</option>
                                <option value="King" <?php echo $room['bed_type'] === 'King' ? 'selected' : ''; ?>>King</option>
                                <option value="Twin" <?php echo $room['bed_type'] === 'Twin' ? 'selected' : ''; ?>>Twin</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="availability" name="availability" 
                                   <?php echo $room['availability'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="availability">
                                Room Available
                            </label>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 