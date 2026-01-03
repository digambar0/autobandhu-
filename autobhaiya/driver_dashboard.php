<?php
session_start();
include('db_connect.php');

// Redirect if driver not logged in
if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];

/* ---------------------------------------------
   1️⃣ FETCH DRIVER INFO
--------------------------------------------- */
$stmt = $conn->prepare("SELECT name, license_no, vehicle_no, phone, created_at, driver_id 
                        FROM drivers WHERE id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$stmt->bind_result($driver_name, $license_no, $vehicle_no, $phone_no, $created_at, $driver_unique_id);
$stmt->fetch();
$stmt->close();


/* ---------------------------------------------
   💰 FETCH TOTAL EARNINGS
--------------------------------------------- */
$earnings_sql = "SELECT SUM(fare_amount) AS total_earnings 
                 FROM bookings 
                 WHERE driver_id = ? AND status = 'Accepted'";
$earn_stmt = $conn->prepare($earnings_sql);
$earn_stmt->bind_param("i", $driver_id);
$earn_stmt->execute();
$earn_stmt->bind_result($total_earnings);
$earn_stmt->fetch();
$earn_stmt->close();

if ($total_earnings === null) $total_earnings = 0.00; // fallback if no rides yet


/* ---------------------------------------------
   2️⃣ FETCH PENDING & ACCEPTED BOOKINGS
--------------------------------------------- */
$bookings = [];
$sql = "SELECT b.id AS booking_id, b.starting_point, b.destination, b.booking_time, 
               b.status, b.fare_amount,
               u.name AS user_name, u.phone AS user_phone, u.email AS user_email
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id 
        WHERE b.status IN ('Pending', 'Accepted')
          AND (b.driver_id IS NULL OR b.driver_id = ?)
        ORDER BY b.booking_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $bookings[] = $row;
$stmt->close();

/* ---------------------------------------------
   3️⃣ HANDLE AJAX ACCEPT / REJECT RIDE
--------------------------------------------- */
if (isset($_POST['ajax'])) {
    $booking_id = (int)$_POST['id'];
    $status = $_POST['status'];

    if ($status === 'Accepted') {
        $fare = 100.00; // Flat fare (you can calculate dynamically later)
        $update = $conn->prepare("UPDATE bookings SET status=?, driver_id=?, fare_amount=? WHERE id=?");
        $update->bind_param("sidi", $status, $driver_id, $fare, $booking_id);
    } else {
        $update = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        $update->bind_param("si", $status, $booking_id);
    }
    $update->execute();
    echo "success";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Dashboard – Autobandhu</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:#f4f4f4; }
header { background:#006400; color:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
header h1 { color:#FFD700; margin:0; }
.nav-container { display:flex; gap:15px; align-items:center; }
.nav-container a, .btn { text-decoration:none; font-weight:bold; padding:8px 14px; border-radius:6px; transition:0.3s; }
.nav-container a { color:#fff; }
.btn { background:#FFD700; color:#000; border:none; cursor:pointer; }
.btn:hover { background:#ffcc00; }

.dashboard-grid {
  display: flex;
  flex-direction: column; /* stack vertically */
  gap: 25px;
  padding: 25px;
}

.sidebar, .main-content { background:#fff; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); padding:20px; }
.sidebar h3 { color:#006400; margin-top:0; }
.booking-card { padding:15px; margin:10px 0; border:1px solid #ddd; border-radius:8px; background:#f9f9f9; transition:0.3s; }
.booking-card.accepted { border:2px solid #006400; background:#e6ffe6; }
.booking-card strong { color:#006400; }
.booking-card button { padding:6px 12px; border-radius:5px; border:none; cursor:pointer; font-weight:bold; margin-top:6px; }
.accept-btn { background:#006400; color:#fff; }
.reject-btn { background:#FFD700; color:#000; margin-left:6px; }
.accept-btn:hover { background:#004d00; } 
.reject-btn:hover { background:#ffcc00; }
#dashboardMap { height:450px; width:100%; border-radius:10px; margin-top:10px; }
footer { background:#006400; color:#fff; text-align:center; padding:20px; border-top:5px solid #FFD700; }
footer a { color:#FFD700; text-decoration:none; margin:0 8px; }
footer a:hover { text-decoration:underline; }
.profile-box { background:#fff; color:#000; border-radius:10px; padding:15px; box-shadow:0 4px 10px rgba(0,0,0,0.2); position:absolute; right:20px; top:60px; display:none; width:260px; }
.profile-box p { margin:5px 0; font-size:14px; }


/* 📱 Responsive Adjustments for Mobile */
@media (max-width: 768px) {
  header {
    flex-direction: column;
    align-items: flex-start;
  }

  .nav-container {
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 10px;
    margin-top: 10px;
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .sidebar, .main-content {
    padding: 15px;
  }

  .booking-card {
    font-size: 14px;
    padding: 12px;
  }

  .booking-card button {
    font-size: 13px;
    padding: 5px 10px;
  }

  #dashboardMap {
    height: 350px;
  }

  .profile-box {
    position: static;
    width: 100%;
    margin-top: 10px;
  }

  footer {
    font-size: 14px;
    padding: 15px;
  }
}

</style>
</head>
<body>

<header>
    <h1>🚖 Driver Dashboard</h1>
    <div class="nav-container">
        <!-- <a class="btn" href="driver_earnings.php">Earnings</a> -->
        <button class="btn" id="profileBtn">Profile ⮟</button>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</header>

<div class="profile-box" id="profileBox">
    <p><strong>Driver Name:</strong> <?= htmlspecialchars($driver_name) ?></p>
    <p><strong>Driver ID:</strong> <?= htmlspecialchars($driver_unique_id) ?></p>
    <p><strong>Vehicle No:</strong> <?= htmlspecialchars($vehicle_no) ?></p>
    <p><strong>License No:</strong> <?= htmlspecialchars($license_no) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($phone_no) ?></p>
    <p><strong>Joined:</strong> <?= htmlspecialchars($created_at) ?></p>
    <p><strong>Total Earnings:</strong> ₹<?= number_format($total_earnings, 2) ?></p>
</div>

<section class="dashboard-grid">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Ride Requests</h3>
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $b): ?>
                <div class="booking-card <?= ($b['status']=='Accepted')?'accepted':'' ?>">
                    <p><strong>User:</strong> <?= htmlspecialchars($b['user_name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($b['user_phone']) ?></p>
                    <p><strong>From:</strong> <?= htmlspecialchars($b['starting_point']) ?></p>
                    <p><strong>To:</strong> <?= htmlspecialchars($b['destination']) ?></p>
                    <p><strong>Time:</strong> <?= htmlspecialchars($b['booking_time']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($b['status']) ?></p>
                    <?php if ($b['status']=='Pending'): ?>
                        <button class="accept-btn" data-id="<?= $b['booking_id'] ?>">Accept</button>
                        <button class="reject-btn" data-id="<?= $b['booking_id'] ?>">Reject</button>
                    <?php else: ?>
                        <p><strong>Fare:</strong> ₹<?= number_format($b['fare_amount'],2) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No current ride requests.</p>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h3>Live Map</h3>
        <p>Track your location and nearby rides in Belgaum.</p>
        <div id="dashboardMap"></div>
    </div>
</section>

<footer>
    <p>© 2025 Autobandhu. All rights reserved.</p>
    <p>
        <a href="#">Privacy Policy</a> | 
        <a href="#">Terms of Service</a> | 
        <a href="#">Help</a>
    </p>
</footer>

<script>
// Toggle Profile
$('#profileBtn').click(function(){
    $('#profileBox').toggle();
});

// Initialize Map
var map = L.map('dashboardMap').setView([15.8497, 74.4977], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);
var driverMarker = L.marker([15.8497, 74.4977]).addTo(map).bindPopup("Your Location").openPopup();

// AJAX Accept/Reject
$(document).on('click', '.accept-btn, .reject-btn', function(){
    let id = $(this).data('id');
    let status = $(this).hasClass('accept-btn') ? 'Accepted' : 'Rejected';
    $.post('driver_dashboard.php', { ajax: true, id: id, status: status }, function(resp){
        if(resp.trim() == 'success'){
            location.reload();
        } else {
            alert('Error updating booking: ' + resp);
        }
    });
});
</script>
</body>
</html>
