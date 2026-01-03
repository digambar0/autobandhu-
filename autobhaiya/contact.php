<?php
// You can use this section in future to include database connections or session handling
// Example:
// session_start();
// include('config.php');

// Define the Key Contacts data
$key_contacts = [
    ['name' => 'Akash Patil', 'id' => '02fe23bca018'],
    ['name' => 'Abhay B', 'id' => '02fe23bca005'],
    ['name' => 'Rishikesh D', 'id' => '02fe23bca008'],
    // Note: Digambar K has the same ID as Akash Patil, which is included as requested.
    ['name' => 'Digambar K', 'id' => '02fe23bca018']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us – Autobandhu</title>
    <style>
        /* NEW STYLING: Yellow/Black/Green Theme */
        body { 
            margin: 0; 
            font-family: 'Arial', sans-serif; 
            background-color: #f4f4f4; /* Light gray background */
            color: #333;
        }
        header {
            background: #FFC300; /* New Header Background: Yellow */
            color: #000; /* Header Text: Black */
            padding: 15px 40px;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
        }
        header h1 { 
            margin: 0; 
            font-size: 28px; 
            color: #006400; /* Title Text: Dark Green */
        }
        .nav-group { 
            display: flex; 
            gap: 20px; /* Increased gap */
            margin-left: 30px; 
        }
        .nav-group:first-child { margin-left: 0; }
        .nav-container { display: flex; flex-wrap: wrap; } 
        .nav-group a, nav a { 
            color: #000; /* Nav Links: Black */
            text-decoration: none; 
            font-weight: bold;
            transition: color 0.3s;
        }
        .nav-group a:hover, nav a:hover { 
            color: #006400; /* Hover: Dark Green */
            text-decoration: none; 
        }
        /* Highlight the active Contact link */
        .nav-group a.active {
            color: #006400; /* Dark Green for active link */
            border-bottom: 2px solid #006400;
        }


        .btn {
            background: #006400; /* Button Background: Dark Green */
            color: #fff; /* Button Text: White */
            padding: 12px 25px; 
            border-radius: 5px;
            text-decoration: none; 
            font-weight: bold; 
            display: inline-block; 
            margin: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover { background: #004d00; } /* Darker Green on hover */
        
        .section { padding: 50px 20px; text-align: center; }
        .section h2 { font-size: 36px; margin-bottom: 30px; color: #006400; }
        
        /* Contact Us Page Specific Styling */
        .contact-grid {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        /* Contact Form Styling */
        .contact-form {
            flex-basis: 400px;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: left;
        }
        .contact-form h3 {
            color: #006400;
            margin-top: 0;
            border-bottom: 2px solid #FFC300;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .contact-form .btn {
            width: 100%;
            border: none;
            cursor: pointer;
        }


        /* Key Contacts Table Styling */
        .key-contacts {
            flex-grow: 1;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: left;
            max-width: 500px;
        }
        .key-contacts h3 {
            color: #006400;
            margin-top: 0;
            border-bottom: 2px solid #FFC300;
            padding-bottom: 10px;
        }
        .key-contacts table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .key-contacts th, .key-contacts td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .key-contacts th {
            background-color: #f9f9f9;
            color: #333;
            font-weight: bold;
        }
        .key-contacts td:last-child {
            font-family: monospace;
            color: #006400; /* Green highlight for the ID */
            font-weight: bold;
        }


        footer { 
            background: #006400; /* Footer Background: Dark Green */
            color: #fff; 
            text-align: center; 
            padding: 25px; 
            margin-top: 50px; /* Add space above the footer */
        }
        footer a { 
            color: #FFC300; /* Footer Links: Yellow */
            margin: 0 10px; 
            text-decoration: none; 
        }

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

    </style>
</head>
<body>

    <header>
        <h1>🚖 Autobandhu</h1>
        <div class="nav-container">
            <div class="nav-group">
                <a href="index.php">Home</a>
                <a href="contact.php" class="active">Contact</a> </div>
            
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


    <section class="section" id="contact-us">
        <h2>Get in Touch with Autobandhu</h2>
        <p style="margin-top: -20px; margin-bottom: 40px;">We're here to help! Choose the best way to reach us below.</p>
        
        <div class="contact-grid">
            
            <!-- <div class="contact-form">
                <h3>Send Us a Message</h3>
                <form action="submit_contact.php" method="POST">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Inquiry</button>
                </form>
            </div> -->

            <div class="key-contacts">
                <h3>Project/Team Contacts</h3>
                <p>For specific inquiries or development references, please contact one of the following key persons:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID/Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($key_contacts as $contact): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['id']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3 style="margin-top: 30px;">Other Ways to Contact</h3>
                <p>
                    <strong>Customer Support:</strong> support@autobandhu.in<br>
                    <strong>Phone:</strong> +91 98765 43210<br>
                    <strong>Office Address:</strong> <small>Autobandhu HQ, Belgaum, Karnataka, India.</small>
                </p>

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
            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                // Ensure the menu is only hidden if it's currently shown AND not the one being clicked
                if(menu.id !== id && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            });
            // Toggle clicked dropdown
            document.getElementById(id).classList.toggle('show');
        }

        // Close dropdown if clicked outside
        window.onclick = function(event) {
            // Check if the click target is NOT an element inside a dropdown trigger
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        }
    </script>

</body>
</html>