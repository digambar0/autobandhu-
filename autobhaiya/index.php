<?php
// You can use this section in future to include database connections or session handling
// Example:
// session_start();
// include('config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autobandhu – Belgaum Rides</title>
    <style>
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
        .nav-group { 
            display: flex; 
            gap: 20px; 
            margin-left: 30px; 
        }
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
        .hero {
            text-align: center; 
            padding: 100px 20px; 
            background: url('rickshaw.jpg') no-repeat center center/cover;
            color: #fff; 
            position: relative;
        }
        .hero::before {
            content: ""; 
            position: absolute; 
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); 
        }
        .hero h2, .hero p, .hero a { position: relative; z-index: 1; }
        .hero h2 { font-size: 36px; margin-bottom: 10px; }
        .hero p { font-size: 18px; margin-bottom: 30px; }
        .btn {
            background: #006400; 
            color: #fff; 
            padding: 12px 25px; 
            border-radius: 5px;
            text-decoration: none; 
            font-weight: bold; 
            display: inline-block; 
            margin: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover { background: #004d00; } 
        
        .section { padding: 50px 20px; text-align: center; }
        .section h3 { font-size: 30px; margin-bottom: 25px; color: #006400; }
        
        #features { background-color: #fff; }
        .feature-grid {
            display: flex;
            justify-content: center;
            gap: 40px;
            max-width: 900px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        .feature-item {
            flex-basis: 250px;
            padding: 20px;
            border-radius: 8px;
            background: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: left;
        }
        .feature-item h4 {
            color: #FFC300;
            font-size: 20px;
            margin-top: 0;
        }
        .feature-item p {
            font-size: 14px;
            line-height: 1.6;
            color: #555;
        }

        footer { 
            background: #006400; 
            color: #fff; 
            text-align: center; 
            padding: 25px; 
        }
        footer a { 
            color: #FFC300; 
            margin: 0 10px; 
            text-decoration: none; 
        }

        .dropdown { position: relative; }
        .dropdown > a { cursor: pointer; }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0; 
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
    </style>
</head>
<body>

    <header>
        <h1>🚖 Autobandhu</h1>
        <div class="nav-container">
            <div class="nav-group">
                <a href="index.php">Home</a>
                <a href="contact.php">Contact</a>
            </div>
            
            <div class="nav-group">
                <!-- USER DROPDOWN -->
                <div class="dropdown">
                    <a onclick="toggleDropdown('userDropdown')">User</a>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="user_login.php">Login</a>
                        <a href="user_register.php">Register</a>
                    </div>
                </div>

                <!-- DRIVER DROPDOWN -->
                <div class="dropdown">
                    <a onclick="toggleDropdown('driverDropdown')">Driver</a>
                    <div class="dropdown-menu" id="driverDropdown">
                        <a href="driver_login.php">Login</a>
                        <a href="driver_register.php">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <h2>Autobandhu – Your Auto Partner in Belgaum 🛵</h2>
        <p>Affordable, quick, and reliable rides right where you need them.</p>
        <a href="#" class="btn">Book Now via App</a>
    </section>

    <section class="section" id="features">
        <h3>Why Choose Autobandhu?</h3>
        <div class="feature-grid">
            <div class="feature-item">
                <h4>Quick & Easy Booking</h4>
                <p>Find and book an auto or bike taxi in seconds with our user-friendly mobile app.</p>
            </div>
            <div class="feature-item">
                <h4>Lowest Fares Guaranteed</h4>
                <p>We offer the most competitive pricing in Belgaum without compromising on ride quality.</p>
            </div>
            <div class="feature-item">
                <h4>Safety First</h4>
                <p>All our drivers are verified, and every ride is tracked for your complete peace of mind.</p>
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
    </script>

</body>
</html>
