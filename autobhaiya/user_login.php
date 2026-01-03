<?php
session_start();
require 'db_connect.php';  // adjust path if needed

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['email']);  // email or phone
    $password = trim($_POST['password']);

    // Check if user exists by email or phone
    $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ? OR phone = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ss", $loginInput, $loginInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            header('Location:bookingpage.php');
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Account not found.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login – Autobandhu</title>
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
        .error-msg {
            background: #f8d7da; color: #721c24; padding: 10px;
            border: 1px solid #f5c6cb; border-radius: 4px;
            margin-bottom: 15px; text-align: center;
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
        </div>
    </header>

    <section class="section">
        <div class="form-container">
            <h2>User Login</h2>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="user_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email or Phone</label>
                    <input type="text" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="submit-btn">
                    <button type="submit" class="btn">Log In</button>
                </div>
            </form>
            <p class="alt-link">Don't have an account? <a href="user_register.php">Register here</a></p>
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
