<?php
session_start();
require 'db_connect.php';

$login_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = trim($_POST['id']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, password FROM drivers WHERE phone = ? OR license_no = ?");
    $stmt->bind_param("ss", $id, $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($driver_id, $driver_name, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // ✅ Successful login
            $_SESSION['driver_logged_in'] = true;
            $_SESSION['driver_id'] = $driver_id;
            $_SESSION['driver_name'] = $driver_name;

            header("Location: driver_dashboard.php");
            exit();
        } else {
            $login_error = "❌ Invalid password.";
        }
    } else {
        $login_error = "❌ Driver not found.";
    }
    $stmt->close();
}
?>
<?php

$login_error = "";  // to store error messages
$login_success = ""; // to store success message

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $driver_id = trim($_POST['id']);
    $password = trim($_POST['password']);

    // ✅ Example: Replace this with your actual DB connection & verification logic
    // For demonstration, using a hardcoded example:
    $demo_driver_id = "driver123";
    $demo_password = "pass123";

    if ($driver_id === $demo_driver_id && $password === $demo_password) {
        $_SESSION['driver_id'] = $driver_id;
        $login_success = "Login successful! Redirecting to dashboard...";
        // Example redirect after 2 seconds
        header("refresh:2;url=driver_dashboard.php");
    } else {
        $login_error = "Invalid Driver ID or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login – Autobandhu</title>
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
            border: none; cursor: pointer;
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

        .message {
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
        }
        .error { color: red; }
        .success { color: green; }
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
            <h2>Driver Login</h2>

            <!-- Login messages -->
            <?php if (!empty($login_error)): ?>
                <div class="message error"><?php echo $login_error; ?></div>
            <?php endif; ?>

            <?php if (!empty($login_success)): ?>
                <div class="message success"><?php echo $login_success; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="id">Driver ID or Phone</label>
                    <input type="text" id="id" name="id" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="submit-btn">
                    <button type="submit" class="btn">Driver Log In</button>
                </div>
            </form>

            <p class="alt-link">New driver? <a href="driver_register.php">Register here</a></p>
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
