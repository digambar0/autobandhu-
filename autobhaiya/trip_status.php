<?php
// 1. Database Connection Setup
// Start session for passing success message
session_start();
require 'db_connect.php'; 

// --- Configuration & Input Retrieval ---
$booking_id_to_view = $_GET['id'] ?? null; 
$errorMsg = '';
$trip_details = null;
$download_success = $_GET['download'] ?? null; // Check for download success flag

if (!is_numeric($booking_id_to_view) || $booking_id_to_view <= 0) {
    $errorMsg = "Error: Invalid or missing Booking ID.";
} else {
    $booking_id_to_view = (int)$booking_id_to_view;

    function calculate_distance($start, $end) {
        if ($start == 'belgaum' && $end == 'hindlga') return '15.5 km';
        if ($start == 'bemco' && $end == 'kle') return '8.2 km';
        return '10.0 km (Estimate)';
    }

    function calculate_price($distance_str) {
        $base_fare = 50;
        if (preg_match('/(\d+\.?\d*)\s*km/', $distance_str, $matches)) {
            $distance = (float)$matches[1];
            $fare_per_km = 15;
            $total_price = $base_fare + ($distance * $fare_per_km);
            return '₹' . number_format($total_price, 2);
        }
        return '₹150.00 (Estimate)';
    }

    // 2. Fetch Detailed Booking Data including driver_id
    $stmt = $conn->prepare("
        SELECT 
            b.id AS booking_id, b.starting_point, b.destination, b.booking_time, b.status,
            u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
            d.name AS driver_name, d.phone AS driver_phone, d.vehicle_no, d.license_no, d.driver_id
        FROM 
            bookings b
        JOIN 
            users u ON b.user_id = u.id
        LEFT JOIN 
            drivers d ON b.driver_id = d.id
        WHERE 
            b.id = ?
    ");

    if (!$stmt) {
        $errorMsg = "SQL Prepare Error: " . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param("i", $booking_id_to_view);
        $stmt->execute();
        $result = $stmt->get_result();
        $trip_details = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if (!$trip_details) {
            $errorMsg = "Error: Booking ID $booking_id_to_view not found.";
        } else {
            $distance = calculate_distance($trip_details['starting_point'], $trip_details['destination']);
            $price = calculate_price($distance);

            $driver_info = ($trip_details['driver_name']) 
                ? [
                    'Driver ID' => $trip_details['driver_id'],
                    'Name' => $trip_details['driver_name'],
                    'Phone' => $trip_details['driver_phone'],
                    'Vehicle No' => $trip_details['vehicle_no'],
                    'License No' => $trip_details['license_no']
                ]
                : ['Status' => 'Driver not yet assigned or accepted.'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trip Summary<?php echo $trip_details ? " - Booking #" . htmlspecialchars($trip_details['booking_id']) : ""; ?></title>
<style>
    * { box-sizing: border-box; }
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding-top: 100px;
        min-height: 100vh;
        color: #333;
        display: flex;
        flex-direction: column;
    }
    header {
        background: #FFC300;
        color: #000;
        padding: 15px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 100;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    header h1 { margin: 0; font-size: 28px; color: #006400; }
    .nav-group a {
        color: #006400;
        font-weight: bold;
        display: inline-block;
        padding: 8px 12px;
        border-radius: 5px;
        text-decoration: none;
        background: #FFD700;
        transition: all 0.3s;
        z-index: 101;
    }
    .nav-group a:hover { background: #FFC300; color: #006400; }
    
    /* Style for the new Download Button */
    .download-btn {
        background: #006400; /* Dark Green */
        color: #fff !important; /* White text, forced */
        margin-left: 15px;
        padding: 8px 15px !important;
    }
    .download-btn:hover {
        background: #004d00; /* Darker Green on hover */
    }

    .container {
        max-width: 800px;
        margin: 20px auto;
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        flex: 1;
    }
    h1 { color: #006400; text-align: center; border-bottom: 2px solid #FFD700; padding-bottom: 10px; margin-bottom: 30px; }
    h2 { color: #006400; margin-top: 25px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
    .detail-group { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
    .card { flex: 1 1 45%; background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 5px solid #006400; }
    .card p { margin: 8px 0; line-height: 1.4; }
    .card strong { display: inline-block; width: 100px; color: #333; }
    .highlight-card { flex: 1; background: #e6ffe6; border-left: 5px solid #FFD700; padding: 20px; text-align: center; }
    .highlight-card h3 { color: #006400; margin: 0; font-size: 24px; }
    .highlight-card p { font-size: 18px; font-weight: bold; color: #555; }
    .status-accepted { color: #008000; font-weight: bold; }
    .status-pending { color: #FFA500; font-weight: bold; }
    footer { background: #000; color: #fff; text-align: center; padding: 15px; line-height: 1.5; margin-top: auto; }
    footer a { color: #FFC300; margin: 0 10px; text-decoration: none; }
    @media (max-width: 600px) {
        header { padding: 10px 20px; }
        .nav-group { gap: 15px; }
        .container { margin: 20px auto; padding: 15px; }
    }
    .error-msg { color: red; font-weight: bold; text-align: center; margin: 50px 0; }
    .success-msg {
        background: #006400;
        color: #fff;
        padding: 20px;
        text-align: center;
        border-radius: 8px;
        font-size: 1.2em;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>
</head>
<body>

    <header>
        <h1>🚖 Autobandhu</h1>
        <div class="nav-container">
            <nav class="nav-group">
                <a href="bookingpage.php">← Back</a> 
                <?php if($trip_details): ?>
                <a href="feedback.php?id=<?php echo htmlspecialchars($trip_details['booking_id']); ?>" class="feedback-btn">
                    Give Feedback
                </a>
                
                <a href="generate_invoice.php?id=<?php echo htmlspecialchars($trip_details['booking_id']); ?>" class="download-btn">
                    Download Invoice (PDF)
                </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

<div class="container">
    <?php if(!empty($errorMsg)): ?>
        <p class="error-msg"><?php echo $errorMsg; ?></p>
    <?php else: ?>
        
        <?php if($download_success == 'true'): ?>
        <div class="success-msg">
            Invoice downloaded successfully! **Thank you, visit again.**
        </div>
        <?php endif; ?>

        <h1>Trip Summary & Details (ID: <?php echo htmlspecialchars($trip_details['booking_id']); ?>)</h1>

        <h2>Trip Overview</h2>
        <div class="detail-group">
            <div class="highlight-card">
                <h3>Distance</h3>
                <p><?php echo htmlspecialchars($distance); ?></p>
            </div>
            <div class="highlight-card">
                <h3>Estimated Price</h3>
                <p><?php echo htmlspecialchars($price); ?></p>
            </div>
            <div class="highlight-card">
                <h3>Status</h3>
                <p class="status-<?php echo strtolower($trip_details['status']); ?>">
                    <?php echo htmlspecialchars($trip_details['status']); ?>
                </p>
            </div>
        </div>
        
        <h2>Booking Details</h2>
        <div class="detail-group">
            <div class="card">
                <p><strong>Pickup:</strong> <?php echo htmlspecialchars($trip_details['starting_point']); ?></p>
                <p><strong>Drop:</strong> <?php echo htmlspecialchars($trip_details['destination']); ?></p>
                <p><strong>Time:</strong> <?php echo date('F j, Y, g:i a', strtotime($trip_details['booking_time'])); ?></p>
            </div>
        </div>

        <h2>User Details</h2>
        <div class="detail-group">
            <div class="card">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($trip_details['user_name']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($trip_details['user_phone']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($trip_details['user_email']); ?></p>
            </div>
        </div>

        <h2>Driver Details</h2>
        <div class="detail-group">
            <div class="card">
                <?php foreach ($driver_info as $label => $value): ?>
                    <p><strong><?php echo htmlspecialchars($label); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>© 2025 Autobandhu. All rights reserved.</p>
    <p>
        <a href="#">Privacy Policy</a> | 
        <a href="#">Terms of Service</a> | 
        <a href="#">FAQ</a>
    </p>
</footer>

</body>
</html>