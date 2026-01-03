<?php
session_start();
include('db_connect.php'); 

$message = ''; 
$starting_point = '';
$destination = '';

// Get logged-in user ID
$user_id = $_SESSION['user_id'] ?? null;

// Fetch last booking ID for the logged-in user
$last_booking_id = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($last_booking_id);
    $stmt->fetch();
    $stmt->close();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$user_id) {
        $message = "<div class='alert error'>Please <a href='user_login.php'>log in</a> to book a ride.</div>";
    } else {
        $starting_point = trim($_POST['starting_point'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        if (empty($starting_point) || empty($destination)) {
            $message = "<div class='alert error'>Both Starting Point and Destination are required.</div>";
        } else {
            $sql = "INSERT INTO bookings (user_id, starting_point, destination, status) VALUES (?, ?, ?, 'Pending')";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("iss", $user_id, $starting_point, $destination);
                if ($stmt->execute()) {
                    $message = "<div class='alert success'>Booking successful! We are finding your auto.</div>";
                    $starting_point = $destination = '';
                    $last_booking_id = $conn->insert_id; // Update last booking
                } else {
                    $message = "<div class='alert error'>Could not process booking (" . $stmt->error . ").</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert error'>Database error: Could not prepare statement (" . $conn->error . ").</div>";
            }
        }
    }
}

if (isset($conn)) $conn->close();

$starting_point = htmlspecialchars($starting_point ?? '');
$destination = htmlspecialchars($destination ?? '');
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
        footer { 
            background: #000; 
            color: #fff; 
            text-align: center; 
            padding: 5px; 
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        footer a { color: #FFC300; margin: 0 10px; text-decoration: none; }

        /* Dropdown styling */
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

        /* RIDE BOOKING FORM STYLES */
        .booking-section {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 50px 20px 100px;
            min-height: calc(100vh - 180px);
        }

        .booking-card {
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
            margin-bottom: 15px; /* Added margin to separate from the new status button */
        }

        .submit-btn:hover {
            background: #004d00;
        }

        .status-btn { display:inline-block; background:#FFD700; color:#000; padding:10px 20px; border-radius:6px; font-size:16px; font-weight:bold; text-decoration:none; width:100%; box-sizing:border-box; transition:background 0.3s; margin-top:15px; }
.status-btn:hover { background:#ffcc00; }
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
<div class="booking-card">
    <h2>Book Your Ride Now!</h2>

    <?php echo $message; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="starting_point">Starting Point (Pickup)</label>
            <input type="text" id="starting_point" name="starting_point" placeholder="Enter Pickup Location" value="<?php echo $starting_point; ?>" required>
        </div>
        <div class="form-group">
            <label for="destination">Destination (Drop)</label>
            <input type="text" id="destination" name="destination" placeholder="Enter Drop Location" value="<?php echo $destination; ?>" required>
        </div>
        <button type="submit" class="submit-btn">Find My Auto/Bike</button>
    </form>

    <?php if ($user_id): ?>
        <a href="trip_status.php?id=<?php echo $last_booking_id ?: 0; ?>" class="status-btn">
            View Last Booking Status
        </a>
    <?php endif; ?>

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
function toggleDropdown(id) {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if(menu.id !== id && menu.classList.contains('show')) menu.classList.remove('show');
    });
    document.getElementById(id).classList.toggle('show');
}
window.onclick = function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show'));
    }
}
</script>

</body>
</html>