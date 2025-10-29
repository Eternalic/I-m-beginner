<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: manage_bookings.php?error=invalid_booking_id");
    exit();
}

$booking_id = (int)$_GET['booking_id'];

try {
    // Get booking details
    $booking_sql = "SELECT b.*, h.name as hotel_name, h.location, h.city, 
                           r.room_type, r.price_per_night, r.max_guests,
                           u.first_name, u.last_name, u.email, u.phone
        FROM bookings b
        JOIN hotels h ON b.hotel_id = h.hotel_id
                    JOIN rooms r ON b.room_id = r.room_id
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.booking_id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($booking_sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage_bookings.php?error=booking_not_found");
        exit();
    }
    
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Validate booking status
    if ($booking['booking_status'] !== 'pending') {
        throw new Exception("Booking is not available for confirmation");
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payment_method = $_POST['payment_method'];
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
            // For cash payments, keep booking as pending until manager confirms
            $new_status = 'pending';
            
            $update_sql = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
                $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $new_status, $booking_id);
                
                if (!$update_stmt->execute()) {
                throw new Exception("Failed to update booking status");
                }
                
                $update_stmt->close();
                
            // Commit transaction
            $conn->commit();
            
            // Redirect to confirmation page
            header("Location: booking_confirmation.php?booking_id=" . $booking_id . "&status=" . $new_status);
            exit();
                
            } catch (Exception $e) {
            // Rollback transaction on error
                $conn->rollback();
            throw $e;
        }
    }
    
} catch (Exception $e) {
    error_log("Payment error: " . $e->getMessage());
    $error_message = "An error occurred while processing your request. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - Ered Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f8fafc;
        }

        .payment-container {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #0f172a;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .payment-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            }

            .booking-summary {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            color: #667eea;
        }
        
        .payment-methods {
            padding: 2rem;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background-color: #f0f2ff;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 1rem;
        }
        
        .payment-method label {
            cursor: pointer;
            margin: 0;
            font-weight: 500;
        }
        
        .payment-method .description {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .hotel-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .hotel-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .hotel-location {
            color: #666;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-container">
                    <!-- Header -->
                    <div class="payment-header">
                        <h1><i class="fas fa-credit-card"></i> Confirm Booking</h1>
                        <p>Complete your hotel reservation</p>
        </div>
                    
                    <!-- Error Message -->
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger m-3">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
                    <?php endif; ?>
                    
                    <!-- Booking Summary -->
                    <div class="booking-summary">
                        <h3 class="mb-3">Booking Summary</h3>
                        
                        <div class="hotel-info">
                            <div class="hotel-name"><?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                            <div class="hotel-location"><?php echo htmlspecialchars($booking['location'] . ', ' . $booking['city']); ?></div>
        </div>
                        
                        <div class="summary-item">
                            <span>Room Type:</span>
                            <span><?php echo htmlspecialchars($booking['room_type']); ?></span>
    </div>

                        <div class="summary-item">
                            <span>Check-in Date:</span>
                            <span><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
            </div>
                        
                        <div class="summary-item">
                            <span>Check-out Date:</span>
                            <span><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
    </div>

                        <div class="summary-item">
                            <span>Guests:</span>
                            <span><?php echo $booking['max_guests']; ?> person(s)</span>
    </div>

                        <div class="summary-item">
                            <span>Booking Number:</span>
                            <span><?php echo htmlspecialchars($booking['book_number']); ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Total Amount:</span>
                            <span>RM <?php echo number_format($booking['total_price'], 2); ?></span>
                                </div>
                                </div>
                    
                    <!-- Payment Methods -->
                    <div class="payment-methods">
                        <h3 class="mb-3">Payment Method</h3>
                        
                        <form method="POST" id="paymentForm">
                            <div class="payment-method selected">
                                <input type="radio" name="payment_method" value="cash" id="cash" checked>
                                <label for="cash">
                                    <i class="fas fa-money-bill-wave"></i> Cash Payment
                            </label>
                                <div class="description">
                                    Pay at the hotel upon arrival. Booking will be confirmed by hotel staff.
                        </div>
                    </div>

                            <button type="submit" class="btn btn-confirm mt-4" id="confirmBtn">
                                <i class="fas fa-check"></i> Confirm Booking
                            </button>
                        </form>
                                        </div>
                                    </div>
                                        </div>
                                    </div>
                    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
                    <script>
        // Form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            // Show loading state
            const btn = document.getElementById('confirmBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;
        });
                    </script>
</body>
</html>