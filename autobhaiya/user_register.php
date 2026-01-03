<?php
session_start();
require 'db_connect.php'; // Make sure this file connects to your database

$name = $email = $phone = $password = "";
$successMsg = $errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data and sanitize
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = htmlspecialchars(trim($_POST['password']));

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $errorMsg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errorMsg = "Phone number must be 10 digits.";
    } else {
        // Check if email or phone already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errorMsg = "Email or phone already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                $successMsg = "✅ Registration successful! You can now <a href='user_login.php'>Login</a>.";
                $name = $email = $phone = $password = "";
            } else {
                $errorMsg = "Something went wrong: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Registration – Autobandhu</title>
<style>
    body { margin: 0; font-family: Arial, sans-serif; }
    header {
        background: #006400; color: #fff; padding: 15px 40px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
    }
    header h1 { margin: 0; font-size: 24px; color: #FFD700; }
    .nav-group { display: flex; gap: 15px; margin-left: 20px; }
    .nav-group:first-child { margin-left: 0; }
    .nav-container { display: flex; flex-wrap: wrap; } 
    .nav-group a, nav a { color: #fff; text-decoration: none; font-weight: bold; }
    .nav-group a:hover, nav a:hover { text-decoration: underline; }
    .btn {
        background: #FFD700; color: #000; padding: 10px 20px; border-radius: 5px;
        text-decoration: none; font-weight: bold; display: inline-block; margin: 5px;
    }
    .btn:hover { background: #ffcc00; }
    .section { padding: 40px 20px; text-align: center; }
    footer { background: #006400; color: #fff; text-align: center; padding: 20px; }
    footer a { color: #FFD700; margin: 0 10px; text-decoration: none; }
    .form-container {
        max-width: 400px; margin: 50px auto; padding: 20px;
        border: 1px solid #ddd; border-radius: 8px; text-align: left;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .form-container h2 { color: #006400; text-align: center; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input {
        width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
    }
    .submit-btn .btn { width: 100%; margin: 15px 0 0; }
    .alt-link { text-align: center; margin-top: 20px; font-size: 14px; }
    .alt-link a { color: #006400; text-decoration: none; font-weight: bold; }
    .message { text-align:center; margin-bottom: 20px; }
    .success { color: green; }
    .error { color: red; }
</style>
</head>
<body>

<header>
    <h1>🚖 Autobandhu</h1>
    <div class="nav-container">
        <div class="nav-group">
            <a href="index.php">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
        </div>
    </div>
</header>

<section class="section">
    <div class="form-container">
        <h2>User Registration</h2>
        <?php 
            if (!empty($successMsg)) echo "<p class='message success'>$successMsg</p>";
            if (!empty($errorMsg)) echo "<p class='message error'>$errorMsg</p>";
        ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="" required>
            </div>
            <div class="submit-btn">
                <button type="submit" class="btn">Register</button>
            </div>
        </form>
        <p class="alt-link">Already have an account? <a href="user_login.php">Login here</a></p>
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

</body>
</html>
