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

// Handle image edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_image'])) {
    $image_id = $_POST['image_id'];
    $target_dir = "../images/hotel/";
    $file_extension = strtolower(pathinfo($_FILES["new_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if file is an image
    $check = getimagesize($_FILES["new_image"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["new_image"]["tmp_name"], $target_file)) {
            $image_path = "images/hotel/" . $new_filename;
            
            // Get old image path to delete it
            $old_image_sql = "SELECT hotel_image FROM hotel_img WHERE hi_id = ? AND hotel_id = ?";
            $stmt = $conn->prepare($old_image_sql);
            $stmt->bind_param("ii", $image_id, $hotel_id);
            $stmt->execute();
            $old_image = $stmt->get_result()->fetch_assoc();
            
            // Delete old image file
            if ($old_image && file_exists("../" . $old_image['hotel_image'])) {
                unlink("../" . $old_image['hotel_image']);
            }
            
            // Update database
            $update_sql = "UPDATE hotel_img SET hotel_image = ? WHERE hi_id = ? AND hotel_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sii", $image_path, $image_id, $hotel_id);
            $stmt->execute();
            
            header("Location: gallery.php");
            exit();
        }
    }
}

// Get hotel images
$hotel_images_sql = "SELECT * FROM hotel_img WHERE hotel_id = ? ORDER BY hi_id ASC";
$stmt = $conn->prepare($hotel_images_sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel_images = $stmt->get_result();

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hotel_image'])) {
    $target_dir = "../images/hotel/";
    $file_extension = strtolower(pathinfo($_FILES["hotel_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if file is an image
    $check = getimagesize($_FILES["hotel_image"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["hotel_image"]["tmp_name"], $target_file)) {
            $image_path = "images/hotel/" . $new_filename;
            $insert_sql = "INSERT INTO hotel_img (hotel_id, hotel_image) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("is", $hotel_id, $image_path);
            $stmt->execute();
            header("Location: gallery.php");
            exit();
        }
    }
}

// Handle image delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $image_id = $_POST['image_id'];
    
    // Get image path before deleting
    $image_sql = "SELECT hotel_image FROM hotel_img WHERE hi_id = ? AND hotel_id = ?";
    $stmt = $conn->prepare($image_sql);
    $stmt->bind_param("ii", $image_id, $hotel_id);
    $stmt->execute();
    $image = $stmt->get_result()->fetch_assoc();
    
    if ($image) {
        // Delete from database
        $delete_sql = "DELETE FROM hotel_img WHERE hi_id = ? AND hotel_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $image_id, $hotel_id);
        
        if ($stmt->execute()) {
            // Delete file
            if (file_exists("../" . $image['hotel_image'])) {
                unlink("../" . $image['hotel_image']);
            }
            $_SESSION['delete_success'] = true;
        }
    }
    
    header("Location: gallery.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - Ered Hotel</title>
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
            overflow-x: hidden;
        }

        .container-fluid {
            max-width: 100%;
            padding: 0;
            overflow-x: hidden;
        }

        .sidebar {
            background-color: var(--sidebar-bg);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            background-color: var(--sidebar-hover);
            color: #fff;
            transform: translateX(5px);
        }

        .nav-item.active {
            background-color: var(--primary-color);
            color: #fff;
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .gallery-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }

        .gallery-card:hover {
            transform: translateY(-5px);
        }

        .gallery-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .gallery-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-image:hover {
            transform: scale(1.05);
        }

        .gallery-content {
            padding: 1rem;
        }

        .gallery-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #b91c1c;
            border-color: #b91c1c;
        }

        .upload-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .upload-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .upload-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #cbd5e1;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
        }

        .upload-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .upload-preview i {
            font-size: 3rem;
            color: #cbd5e1;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                display: none;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }

        .modal-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .delete-success {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #059669;
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: fadeOut 1.5s ease-in-out forwards;
            animation-delay: 0.5s;
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .delete-confirm-modal .modal-content {
            border: none;
            border-radius: 1rem;
        }

        .delete-confirm-modal .modal-header {
            background-color: #fee2e2;
            border-bottom: none;
            border-radius: 1rem 1rem 0 0;
        }

        .delete-confirm-modal .modal-body {
            padding: 2rem;
            text-align: center;
        }

        .delete-confirm-modal .modal-footer {
            border-top: none;
            padding: 1rem;
            justify-content: center;
        }

        .delete-confirm-modal .warning-icon {
            font-size: 3rem;
            color: #dc2626;
            margin-bottom: 1rem;
        }

        .delete-confirm-modal .btn-danger {
            background-color: #dc2626;
            border-color: #dc2626;
            padding: 0.5rem 2rem;
        }

        .delete-confirm-modal .btn-danger:hover {
            background-color: #b91c1c;
            border-color: #b91c1c;
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
                    <a href="rooms.php" class="nav-item">
                        <i class="fas fa-bed"></i> Rooms
                    </a>
                    <a href="gallery.php" class="nav-item active">
                        <i class="fas fa-images"></i> Gallery
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-header">Management</div>
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
                    <h1 class="welcome-title">Manage Gallery</h1>
                    <p class="welcome-subtitle">View and manage hotel images</p>
                </div>

                <!-- Upload Section -->
                <div class="upload-section">
                    <h3 class="mb-4">Add New Image</h3>
                    <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="upload-preview" id="imagePreview">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="hotel_image" id="imageInput" class="form-control mb-2" accept="image/*" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Hotel Images Grid -->
                <div class="gallery-grid">
                    <?php if ($hotel_images->num_rows > 0): ?>
                        <?php while ($image = $hotel_images->fetch_assoc()): ?>
                            <div class="gallery-card">
                                <div class="gallery-image-container">
                                    <img src="../<?php echo htmlspecialchars($image['hotel_image']); ?>" 
                                         alt="Hotel Image" 
                                         class="gallery-image"
                                         onerror="this.src='../images/default-hotel.jpg'">
                                </div>
                                <div class="gallery-content">
                                    <div class="gallery-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editImage(<?php echo $image['hi_id']; ?>, '<?php echo htmlspecialchars($image['hotel_image']); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="confirmDeleteImage(<?php echo $image['hi_id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No hotel images found</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Image Modal -->
    <div class="modal fade" id="editImageModal" tabindex="-1" aria-labelledby="editImageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editImageModalLabel">Edit Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="image_id" id="editImageId">
                        <div class="mb-3">
                            <img id="currentImagePreview" class="modal-preview" src="" alt="Current Image">
                        </div>
                        <div class="mb-3">
                            <label for="newImage" class="form-label">New Image</label>
                            <input type="file" class="form-control" id="newImage" name="new_image" accept="image/*" required>
                            <div class="mt-2">
                                <img id="newImagePreview" class="modal-preview" style="display: none;" src="" alt="New Image Preview">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_image" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade delete-confirm-modal" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="image_id" id="deleteImageId">
                        <input type="hidden" name="delete_image" value="1">
                        <div class="warning-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="mb-3">Are you sure?</h4>
                        <p class="text-muted">This action cannot be undone. The image will be permanently deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Success Message -->
    <?php if (isset($_SESSION['delete_success'])): ?>
        <div class="delete-success">
            <i class="fas fa-check-circle"></i> Image deleted successfully
        </div>
        <?php unset($_SESSION['delete_success']); ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store scroll position before form submission
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForm = document.querySelector('#deleteConfirmModal form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function() {
                    sessionStorage.setItem('scrollPosition', window.scrollY);
                });
            }

            // Restore scroll position if it exists
            const savedScrollPosition = sessionStorage.getItem('scrollPosition');
            if (savedScrollPosition) {
                window.scrollTo(0, parseInt(savedScrollPosition));
                sessionStorage.removeItem('scrollPosition');
            }

            // Auto-hide success message
            const successMessage = document.querySelector('.delete-success');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.remove();
                }, 2000);
            }
        });

        // Image preview functionality
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        function editImage(imageId, currentImagePath) {
            document.getElementById('editImageId').value = imageId;
            document.getElementById('currentImagePreview').src = '../' + currentImagePath;
            
            const editModal = new bootstrap.Modal(document.getElementById('editImageModal'));
            editModal.show();
        }

        // Preview new image in edit modal
        document.getElementById('newImage').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('newImagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        function confirmDeleteImage(imageId) {
            document.getElementById('deleteImageId').value = imageId;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>
<?php
$conn->close();
?> 