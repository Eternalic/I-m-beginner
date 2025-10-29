<?php
// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = 'rick0059';
$database = 'travelx_db';

// Function to create database connection with proper error handling
function createDBConnection() {
    global $host, $username, $password, $database;
    
    // Suppress PHP errors and handle them manually
    $old_error_reporting = error_reporting(0);
    
    try {
        // Check if MySQL server is running by testing connection
        $test_conn = new mysqli($host, $username, $password);
        
        if ($test_conn->connect_error) {
            $error_message = $test_conn->connect_error;
            $test_conn->close();
            
            // Restore error reporting
            error_reporting($old_error_reporting);
            
            // Provide helpful error messages based on the error type
            if (strpos($error_message, 'actively refused') !== false || 
                strpos($error_message, 'Connection refused') !== false ||
                strpos($error_message, 'No connection could be made') !== false) {
                
                showMySQLNotRunningError($error_message);
            } else {
                showDatabaseConnectionError($error_message);
            }
        }
        
        $test_conn->close();
        
        // Now try to connect to the specific database
        $conn = new mysqli($host, $username, $password, $database);
        
        // Check connection
        if ($conn->connect_error) {
            // Restore error reporting
            error_reporting($old_error_reporting);
            showDatabaseConnectionError($conn->connect_error);
        }
        
        // Set charset
        $conn->set_charset("utf8mb4");
        
        // Set connection timeout to prevent "MySQL server has gone away" errors
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 60);
        $conn->options(MYSQLI_OPT_READ_TIMEOUT, 60);
        
        // Restore error reporting
        error_reporting($old_error_reporting);
        
        return $conn;
        
    } catch (Exception $e) {
        // Restore error reporting
        error_reporting($old_error_reporting);
        
        if (strpos($e->getMessage(), 'actively refused') !== false || 
            strpos($e->getMessage(), 'Connection refused') !== false ||
            strpos($e->getMessage(), 'No connection could be made') !== false) {
            showMySQLNotRunningError($e->getMessage());
        } else {
            showDatabaseConnectionError($e->getMessage());
        }
    }
}

// Function to show MySQL not running error
function showMySQLNotRunningError($error_message) {
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>MySQL Server Not Running - HotelHive</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
            .error-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-header { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 20px; }
            .error-message { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .solution-box { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .step { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #007bff; }
            .code { background: #f8f9fa; padding: 5px 10px; border-radius: 3px; font-family: monospace; }
            .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .button:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <h1 class='error-header'>🚫 MySQL Server Not Running</h1>
            
            <div class='error-message'>
                <strong>Error Details:</strong> " . htmlspecialchars($error_message) . "
            </div>
            
            <div class='solution-box'>
                <h3>🔧 How to Fix This Issue:</h3>
                
                <div class='step'>
                    <strong>Method 1: Using XAMPP Control Panel</strong>
                    <ol>
                        <li>Open XAMPP Control Panel</li>
                        <li>Find MySQL in the services list</li>
                        <li>Click the <strong>'Start'</strong> button next to MySQL</li>
                        <li>Wait for the status to show 'Running' (green)</li>
                    </ol>
                </div>
                
                <div class='step'>
                    <strong>Method 2: Using Command Line (Run as Administrator)</strong>
                    <p>Open Command Prompt as Administrator and run:</p>
                    <div class='code'>net start mysql</div>
                </div>
                
                <div class='step'>
                    <strong>Method 3: Manual Service Start</strong>
                    <ol>
                        <li>Press <strong>Windows + R</strong></li>
                        <li>Type <span class='code'>services.msc</span> and press Enter</li>
                        <li>Find 'MySQL' service</li>
                        <li>Right-click and select 'Start'</li>
                    </ol>
                </div>
            </div>
            
            <div class='solution-box'>
                <h3>🔍 Additional Checks:</h3>
                <ul>
                    <li>Ensure XAMPP is properly installed</li>
                    <li>Check if MySQL is configured to run on port 3306</li>
                    <li>Verify no other application is using port 3306</li>
                    <li>Check Windows Firewall settings</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin-top: 30px;'>
                <a href='javascript:location.reload()' class='button'>🔄 Refresh Page After Starting MySQL</a>
                <a href='homepage.php' class='button'>🏠 Go to Homepage</a>
            </div>
        </div>
    </body>
    </html>
    ");
}

// Function to show general database connection error
function showDatabaseConnectionError($error_message) {
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Error - HotelHive</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
            .error-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-header { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 20px; }
            .error-message { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .solution-box { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .step { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #007bff; }
            .code { background: #f8f9fa; padding: 5px 10px; border-radius: 3px; font-family: monospace; }
            .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
            .button:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <h1 class='error-header'>⚠️ Database Connection Error</h1>
            
            <div class='error-message'>
                <strong>Error Details:</strong> " . htmlspecialchars($error_message) . "
            </div>
            
            <div class='solution-box'>
                <h3>🔧 Possible Solutions:</h3>
                <ul>
                    <li>Check if MySQL server is running</li>
                    <li>Verify database credentials in db.php</li>
                    <li>Ensure database 'travelx_db' exists</li>
                    <li>Check MySQL port configuration (default: 3306)</li>
                    <li>Verify user permissions for the database</li>
                    <li>Check if database name is correct</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin-top: 30px;'>
                <a href='javascript:location.reload()' class='button'>🔄 Refresh Page</a>
                <a href='homepage.php' class='button'>🏠 Go to Homepage</a>
            </div>
        </div>
    </body>
    </html>
    ");
}

// Function to check if connection is still alive and reconnect if needed
function ensureConnection($conn) {
    if (!$conn->ping()) {
        // Connection is dead, create a new one
        $conn->close();
        return createDBConnection();
    }
    return $conn;
}

// Create initial connection
$conn = createDBConnection();

/**
 * Get high-quality hotel images
 * @param string $hotelType Type of hotel (luxury, budget, etc.)
 * @param int $count Number of images to return
 * @return array Array of high-quality image URLs
 */
function getHotelImages($hotelType = 'luxury', $count = 5) {
    // Collection of high-quality hotel images
    $luxuryImages = [
        'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Exterior
        'https://images.unsplash.com/photo-1618773928121-c32242e63f39?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Luxury Room
        'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Bathroom
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Lobby
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Pool
        'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Restaurant
        'https://images.unsplash.com/photo-1498503182468-3b51cbb6cb24?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Gym
        'https://images.unsplash.com/photo-1508253578933-20b529302151?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Hotel Bar
        'https://images.unsplash.com/photo-1590381105924-c72589b9ef3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Spa
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Deluxe Suite
    ];
    
    $budgetImages = [
        'https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Budget Hotel Exterior
        'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Standard Room
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Basic Bathroom
        'https://images.unsplash.com/photo-1580977276076-ae4b8c219b8e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Budget Lobby
        'https://images.unsplash.com/photo-1541123356219-284ebe98ae3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Simple Breakfast
    ];
    
    $beachImages = [
        'https://images.unsplash.com/photo-1540541338287-41700207dee6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Beach Resort
        'https://images.unsplash.com/photo-1573843981267-be1999ff37cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Oceanview Room
        'https://images.unsplash.com/photo-1545579133-99bb5ab189bd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Beach View
        'https://images.unsplash.com/photo-1559599189-fe84dea4eb79?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Beach Pool
        'https://images.unsplash.com/photo-1596178065887-1198b6148b2b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // Beach Restaurant
    ];
    
    $cityImages = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // City Hotel
        'https://images.unsplash.com/photo-1522798514-97ceb8c4f1c8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // City View Room
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // City Skyline View
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // City Hotel Interior
        'https://images.unsplash.com/photo-1590073242678-70ee3fc28f8a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', // City Hotel Breakfast
    ];
    
    // Select image array based on hotel type
    switch (strtolower($hotelType)) {
        case 'budget':
            $images = $budgetImages;
            break;
        case 'beach':
            $images = $beachImages;
            break;
        case 'city':
            $images = $cityImages;
            break;
        case 'luxury':
        default:
            $images = $luxuryImages;
            break;
    }
    
    // If requesting more images than available, return all available
    if ($count > count($images)) {
        $count = count($images);
    }
    
    // Return random selection of images
    $keys = array_rand($images, $count);
    if (!is_array($keys)) {
        $keys = [$keys];
    }
    
    $result = [];
    foreach ($keys as $key) {
        $result[] = $images[$key];
    }
    
    return $result;
}
?>