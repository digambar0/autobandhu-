<?php
// 1. Start the session to store the last booking ID
session_start();

// Include the database connection file
// This line assumes db_connect.php is in the same directory
include('db_connect.php'); 

// Placeholder variables for display and status tracking
$message = ''; 
$booking_status = ''; // Used by JS for simulation
$starting_point_display = ''; // Retained for status card
$destination_display = '';    // Retained for status card
$current_booking_id = null;

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize and validate input
    $starting_point = trim($_POST['starting_point'] ?? '');
    $destination = trim($_POST['destination'] ?? '');

    if (empty($starting_point) || empty($destination)) {
        $message = "<div class='alert error'>Error: Both Starting Point and Destination are required.</div>";
    } else {
        
        // Retain values for display, even if the form fields clear
        $starting_point_display = htmlspecialchars($starting_point);
        $destination_display = htmlspecialchars($destination);

        // 2. Prepare an SQL statement for insertion (Setting status to 'Pending')
        $sql = "INSERT INTO bookings (starting_point, destination, status) VALUES (?, ?, 'Pending')";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param("ss", $starting_point, $destination);

            // 3. Execute the statement
            if ($stmt->execute()) {
                // Get the newly inserted ID and store it in the session
                $current_booking_id = $conn->insert_id;
                $_SESSION['last_booking_id'] = $current_booking_id;
                
                $booking_status = 'pending'; // Signal JS that a booking was made

                // Create the success message with the new "Check Status" button
                $message = "
                    <div class='alert success'>
                        Booking initiated! Your ID is **#{$current_booking_id}**.
                        <br><button type='button' id='check-status-btn' class='submit-btn status-btn' onclick='startSimulation()' style='margin-top: 10px; width: auto; padding: 8px 15px;'>Check Status</button>
                    </div>";
                
                // Clear the form data variables for the empty input fields
                $starting_point = $destination = ''; 
            } else {
                $message = "<div class='alert error'>Error: Could not process booking (" . $stmt->error . "). Please try again.</div>";
            }
            
            // Close statement
            $stmt->close();
        } else {
             $message = "<div class='alert error'>Database error: Could not prepare statement (" . $conn->error . ").</div>";
        }
    }

    // Close connection
    // Note: If you need to fetch booking data later on this page, move the close later or use persistent connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autobandhu – Book Your Ride</title>
    <style>
        /* General Styles - Keeping the Yellow/Black/Green Theme */
        body { 
            margin: 0; 
            font-family: 'Arial', sans-serif; 
            background-color: #f4f4f4; 
            color: #333;
        }
        header {
            background: #FFC300; 
            color: #000; 
            padding: 15px 40px;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
        }
        header h1 { 
            margin: 0; 
            font-size: 28px; 
            color: #006400; 
        }
        .nav-group { display: flex; gap: 20px; margin-left: 30px; }
        .nav-group:first-child { margin-left: 0; }
        .nav-container { display: flex; flex-wrap: wrap; } 
        .nav-group a, nav a { 
            color: #000; 
            text-decoration: none; 
            font-weight: bold;
            transition: color 0.3s;
        }
        .nav-group a:hover, nav a:hover { 
            color: #006400; 
            text-decoration: none; 
        }
        /* Footer is no longer fixed to allow content to grow */
        footer { 
            background: #000; 
            color: #fff; 
            text-align: center; 
            padding: 25px; 
            width: 100%;
            margin-top: 40px; 
        }
        footer a { color: #FFC300; margin: 0 10px; text-decoration: none; }

        /* Dropdown styling (Unchanged) */
        .dropdown { position: relative; }
        .dropdown > a { cursor: pointer; }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0; 
            left: auto;
            background: #fff;
            border-radius: 4px;
            border: 1px solid #ccc;
            min-width: 120px;
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dropdown-menu a {
            display: block;
            padding: 8px 12px;
            color: #000;
            text-decoration: none;
        }
        .dropdown-menu a:hover { background-color: #f0f0f0; }
        .show { display: block; }

        /* RIDE BOOKING FORM & SECTION STYLES */
        .booking-section {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 50px 20px 50px; 
            gap: 40px; /* Space between form and status card */
            flex-wrap: wrap;
        }

        .booking-card, #live-status-card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .booking-card h2 {
            color: #006400;
            margin-bottom: 25px;
            font-size: 26px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .submit-btn {
            background: #006400;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }

        .submit-btn:hover {
            background: #004d00;
        }
        
        /* Message Alerts */
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* LIVE STATUS CARD STYLES */
        #live-status-card {
            text-align: left; /* Override booking-card center */
        }
        #live-status-card h2 {
            color: #FFC300;
            margin-bottom: 20px;
            text-align: center;
        }
        .status-detail {
            margin-bottom: 15px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .status-label {
            font-weight: bold;
            color: #006400;
            display: block;
        }
        #current-status {
            font-size: 1.5em;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            background-color: #f0f0f0;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <header>
        <h1>🚖 Autobandhu</h1>
        <div class="nav-container">
            <div class="nav-group">
                <a href="index.php">Home</a>
                <a href="#">Contact</a>
            </div>
            
            <div class="nav-group">
                <div class="dropdown">
                    <a onclick="toggleDropdown('loginDropdown')">Login</a>
                    <div class="dropdown-menu" id="loginDropdown">
                        <a href="user_login.php">User</a>
                        <a href="driver_login.php">Driver</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a onclick="toggleDropdown('signupDropdown')">Sign Up</a>
                    <div class="dropdown-menu" id="signupDropdown">
                        <a href="user_register.php">User</a>
                        <a href="driver_register.php">Driver</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="booking-section">
        <div class="booking-card" id="booking-form-card">
            <h2>Book Your Ride Now!</h2>

            <?php echo $message; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                
                <div class="form-group">
                    <label for="starting_point">Starting Point (Pickup)</label>
                    <input type="text" id="starting_point" name="starting_point" placeholder="Enter Pickup Location" value="<?php echo htmlspecialchars($starting_point ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="destination">Destination (Drop)</label>
                    <input type="text" id="destination" name="destination" placeholder="Enter Drop Location" value="<?php echo htmlspecialchars($destination ?? ''); ?>" required>
                </div>
                
                <button type="submit" class="submit-btn">Find My Auto/Bike</button>
            </form>
        </div>
        
        <div id="live-status-card" style="display: none;">
            <h2>Live Booking Status</h2>
            <div class="status-detail">
                <span class="status-label">Pickup:</span>
                <span id="pickup-location"><?php echo $starting_point_display; ?></span>
            </div>
            <div class="status-detail">
                <span class="status-label">Drop:</span>
                <span id="drop-location"><?php echo $destination_display; ?></span>
            </div>
            <div class="status-detail">
                <span class="status-label">Driver:</span>
                <span id="driver-name">Searching...</span>
            </div>
            <div class="status-detail">
                <span class="status-label">Distance/Time:</span>
                <span id="distance-time">Calculating...</span>
            </div>
            <div class="status-detail">
                <span class="status-label">Current Status:</span>
                <span id="current-status">PENDING</span>
            </div>
        </div>

    </section>

    <footer>
        <p>© 2025 Autobandhu. All rights reserved.</p>
        <p>
            <a href="#">Privacy Policy</a> | 
            <a href="#">Terms of Service</a> | 
            <a href="#">FAQ</a>
        </p>
    </footer>

    <script>
        // Dropdown functionality (Unchanged)
        function toggleDropdown(id) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if(menu.id !== id && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            });
            document.getElementById(id).classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        }

        // ------------------------------------------------------------------
        // NEW: Booking Status Display and Simulation Logic
        // ------------------------------------------------------------------

        const formCard = document.getElementById('booking-form-card');
        const statusCard = document.getElementById('live-status-card');

        // Function to start the real-time simulation
        function startSimulation() {
            // Check if the card is already visible to prevent starting the simulation multiple times
            if (statusCard.style.display !== 'block') {
                 // 1. Hide the form, show the status card
                formCard.style.display = 'none';
                statusCard.style.display = 'block';

                // 2. Initialize status elements
                const statusElement = document.getElementById('current-status');
                
                statusElement.textContent = 'SEARCHING DRIVER...';
                statusElement.style.backgroundColor = '#FFC300'; // Yellow
                statusElement.style.color = '#333';

                // --- SIMULATION STEP 1: Driver Acceptance (2.5 seconds) ---
                setTimeout(() => {
                    const driverName = "Ramesh S. (Auto No. 542)";
                    const travelDistance = "3.2 km";
                    const travelTime = "7 mins";

                    // Update details
                    document.getElementById('driver-name').textContent = driverName;
                    document.getElementById('distance-time').textContent = `${travelDistance} (ETA: ${travelTime})`;
                    
                    // Update status
                    statusElement.textContent = 'BOOKING ACCEPTED ✅';
                    statusElement.style.backgroundColor = '#d4edda'; // Light Green
                    statusElement.style.color = '#155724'; // Dark Green text

                    // --- SIMULATION STEP 2: Driver En-route (3 seconds after acceptance) ---
                    setTimeout(() => {
                        statusElement.textContent = 'DRIVER EN ROUTE 🛵';
                    }, 3000);

                }, 2500); // 2.5 second delay for simulated "driver found"
            }
        }
    </script>

</body>
</html>