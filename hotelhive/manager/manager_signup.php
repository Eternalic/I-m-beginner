<?php
require_once '../db.php';

// Check if manager is already logged in
if (isset($_SESSION['manager_id'])) {
    header("Location: manager_dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $manager_name = trim($_POST['manager_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $hotel_id = intval($_POST['hotel_id']);

    // Validation
    if (empty($manager_name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($hotel_id)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $error = "Please enter a valid phone number.";
    } else {
        // Check if manager name already exists
        $check_sql = "SELECT manager_id FROM hotel_managers WHERE manager_name = ? OR email = ?";
        if ($stmt = $conn->prepare($check_sql)) {
            $stmt->bind_param("ss", $manager_name, $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Manager name or email already exists.";
            } else {
                // Check if hotel exists
                $hotel_check = "SELECT hotel_id FROM hotels WHERE hotel_id = ?";
                if ($hotel_stmt = $conn->prepare($hotel_check)) {
                    $hotel_stmt->bind_param("i", $hotel_id);
                    $hotel_stmt->execute();
                    $hotel_result = $hotel_stmt->get_result();
                    
                    if ($hotel_result->num_rows == 0) {
                        $error = "Invalid hotel ID.";
                    } else {
                        // Check if hotel already has a manager
                        $manager_check = "SELECT manager_id FROM hotel_managers WHERE hotel_id = ?";
                        if ($manager_stmt = $conn->prepare($manager_check)) {
                            $manager_stmt->bind_param("i", $hotel_id);
                            $manager_stmt->execute();
                            $manager_result = $manager_stmt->get_result();
                            
                            if ($manager_result->num_rows > 0) {
                                $error = "This hotel already has a manager assigned.";
                            } else {
                                // Hash password and insert new manager
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $insert_sql = "INSERT INTO hotel_managers (manager_name, email, manager_password, hotel_id, phone) VALUES (?, ?, ?, ?, ?)";
                                
                                if ($insert_stmt = $conn->prepare($insert_sql)) {
                                    $insert_stmt->bind_param("sssis", $manager_name, $email, $hashed_password, $hotel_id, $phone);
                                    
                                    if ($insert_stmt->execute()) {
                                        $success = "Manager account created successfully! You can now sign in.";
                                    } else {
                                        $error = "Something went wrong. Please try again later.";
                                    }
                                    $insert_stmt->close();
                                }
                            }
                            $manager_stmt->close();
                        }
                    }
                    $hotel_stmt->close();
                }
            }
            $stmt->close();
        }
    }
}

// Get available hotels for dropdown (only hotels without managers)
$hotels_sql = "SELECT h.hotel_id, h.name 
               FROM hotels h 
               LEFT JOIN hotel_managers hm ON h.hotel_id = hm.hotel_id 
               WHERE hm.hotel_id IS NULL 
               ORDER BY h.name";
$hotels_result = $conn->query($hotels_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Manager Sign Up - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .signup-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .signup-header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .signup-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-signup {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-signup:hover {
            background-color: var(--secondary-color);
        }

        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 1rem;
            text-align: center;
        }

        .success-message {
            color: #059669;
            font-size: 0.875rem;
            margin-top: 1rem;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus {
            border-left: 1px solid #e2e8f0;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <h1>Hotel Manager Sign Up</h1>
            <p>Create your hotel management account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="manager_name" class="form-label">Manager Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="manager_name" id="manager_name" class="form-control" placeholder="Enter username" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter email" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-phone"></i>
                    </span>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="Enter phone number" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="hotel_id" class="form-label">Hotel</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-hotel"></i>
                    </span>
                    <select name="hotel_id" id="hotel_id" class="form-control" required>
                        <option value="">Select Hotel</option>
                        <?php 
                        $hotel_count = 0;
                        while ($hotel = $hotels_result->fetch_assoc()): 
                            $hotel_count++;
                        ?>
                            <option value="<?php echo $hotel['hotel_id']; ?>">
                                <?php echo htmlspecialchars($hotel['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($hotel_count == 0): ?>
                        <div class="form-text text-warning">
                            <i class="fas fa-exclamation-triangle"></i> All hotels already have managers assigned.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-signup" <?php echo ($hotel_count == 0) ? 'disabled' : ''; ?>>
                <i class="fas fa-user-plus me-2"></i> Create Account
            </button>
        </form>

        <div class="login-link">
            <a href="manager_login.php">
                <i class="fas fa-sign-in-alt"></i> Already have an account? Sign In
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close database connection at the end
$conn->close();
?>