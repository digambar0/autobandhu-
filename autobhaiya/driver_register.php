<?php
session_start();
require 'db_connect.php'; // Make sure this connects to your DB

// Initialize variables
$name = $license = $vehicle = $phone = "";
$successMsg = $errorMsg = "";

// Function to generate unique 4-digit driver ID
function generateDriverID($conn) {
    do {
        $driverID = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); // 4-digit number with leading zeros
        $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_id = ?");
        $stmt->bind_param("s", $driverID);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0); // Repeat if ID already exists
    $stmt->close();
    return $driverID;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize inputs
    $name = htmlspecialchars(trim($_POST["name"] ?? ''));
    $license = htmlspecialchars(trim($_POST["license"] ?? ''));
    $vehicle = htmlspecialchars(trim($_POST["vehicle"] ?? ''));
    $phone = htmlspecialchars(trim($_POST["phone"] ?? ''));
    $password = $_POST["password"] ?? '';

    // Validation
    if (!$name || !$license || !$vehicle || !$phone || !$password) {
    $errorMsg = "All fields are required.";
} 
elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errorMsg = "Phone number must be exactly 10 digits.";
}
elseif (!preg_match('/^(KA)\s[0-9]{2}\s[0-9]{4}\s[0-9]{7}$/i', $license)) {
    $errorMsg = "Driving License format is invalid. Valid format: KA 22 2024 0007481";
}
 else {
        // Check if driver exists
        $stmt = $conn->prepare("SELECT id FROM drivers WHERE license_no = ? OR phone = ?");
        $stmt->bind_param("ss", $license, $phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errorMsg = "Driver with this license or phone already exists.";
        } else {
            // Generate unique 4-digit driver ID
            $driverID = generateDriverID($conn);

            // Insert new driver
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO drivers (driver_id, name, license_no, vehicle_no, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("ssssss", $driverID, $name, $license, $vehicle, $phone, $hashedPassword);

            if ($insert->execute()) {
                $successMsg = "✅ Registration successful! Your Driver ID is <strong>$driverID</strong>. You can now <a href='driver_login.php'>Login here</a>.";
                $name = $license = $vehicle = $phone = '';
            } else {
                $errorMsg = "Error during registration: " . $conn->error;
            }
            $insert->close();
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Registration – Autobandhu</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f4f4;
}

header {
    background: #006400;
    color: #fff;
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header h1 {
    margin: 0;
    font-size: 24px;
    color: #FFD700;
}

.nav-group a {
    color: #fff;
    text-decoration: none;
    margin-right: 15px;
    font-weight: bold;
}

.nav-group a:hover {
    text-decoration: underline;
}

.form-container {
    max-width: 450px;
    margin: 60px auto;
    background: #fff;
    padding: 30px 25px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid #e0e0e0;
}

.form-container h2 {
    text-align: center;
    color: #006400;
    margin-bottom: 25px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 1.8px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    transition: 0.3s;
    box-sizing: border-box;
}

.form-group input:focus {
    border-color: #006400;
    outline: none;
    box-shadow: 0 0 6px rgba(0, 100, 0, 0.2);
}

.form-group input::placeholder {
    color: #999;
    font-style: italic;
}

.submit-btn .btn {
    width: 100%;
    padding: 12px;
    background: #FFD700;
    color: #000;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    transition: 0.3s;
}

.submit-btn .btn:hover {
    background: #ffcc00;
}

.message {
    text-align: center;
    margin-bottom: 15px;
    padding: 12px;
    border-radius: 8px;
    font-weight: 500;
}

.success {
    background: #e6ffe6;
    color: #006400;
    border: 1px solid #006400;
}

.error {
    background: #ffe6e6;
    color: #cc0000;
    border: 1px solid #cc0000;
}

.alt-link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
}

.alt-link a {
    color: #006400;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>
<body>

<header>
<h1>🚖 Autobandhu</h1>
<div class="nav-group">
<a href="index.php">Home</a>
<a href="#">About</a>
<a href="#">Contact</a>
</div>
</header>

<div class="form-container">
<h2>Driver Registration</h2>

<?php if ($successMsg): ?>
    <div class="message success"><?php echo $successMsg; ?></div>
<?php elseif ($errorMsg): ?>
    <div class="message error"><?php echo $errorMsg; ?></div>
<?php endif; ?>

<form method="POST" action="">
<div class="form-group">
<label for="name">Full Name</label>
<input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
</div>
<div class="form-group">
<label for="license">Driving License No.</label>
<input type="text" id="license" name="license" value="<?php echo $license; ?>" required>
</div>
<div class="form-group">
<label for="vehicle">Vehicle Registration No.</label>
<input type="text" id="vehicle" name="vehicle" value="<?php echo $vehicle; ?>" required>
</div>
<div class="form-group">
<label for="phone">Phone Number</label>
<input type="text" id="phone" name="phone" value="<?php echo $phone; ?>" required>
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" name="password" required>
</div>
<div class="submit-btn">
<button type="submit" class="btn">Register as Driver</button>
</div>
</form>

<p class="alt-link">Already registered? <a href="driver_login.php">Driver Login</a></p>
</div>
<script>
document.getElementById("license").addEventListener("blur", function () {
    const licenseInput = this.value.trim();
    const licensePattern = /^(KA)\s[0-9]{2}\s[0-9]{4}\s[0-9]{7}$/i;

    if (!licensePattern.test(licenseInput)) {
        alert("⚠ Enter a valid Driving License Number.\nExample: KA 22 2024 0007481");
        this.focus();         // Return cursor to license field
        this.select();        // Highlight incorrect text
    }
});
</script>

</body>
</html>
