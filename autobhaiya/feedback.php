<?php
session_start();
require_once 'db_connect.php';

// --- Get current logged-in user ID ---
$current_user_id = $_SESSION['user_id'] ?? 0;
if ($current_user_id <= 0) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// --- Get booking ID ---
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status = $_GET['status'] ?? '';

// --- Fetch booking details ---
$booking_sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param("ii", $booking_id, $current_user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();
$booking_stmt->close();

// --- If booking not found ---
if (!$booking) {
    echo "<p>❌ Invalid booking or unauthorized access.</p>";
    exit;
}

// --- Fetch all previous feedback for this booking ---
$feedback_sql = "SELECT * FROM feedback WHERE booking_id = ? ORDER BY feedback_date DESC";
$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param("i", $booking_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trip Feedback – Autobandhu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #28a745;
            --text-color: #343a40;
            --light-bg: #f8f9fa;
            --card-bg: #fff;
            --border-color: #d1d1d1;
            --feedback-border: #e0e0e0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
            margin: 0;
            padding: 40px 15px;
            display: flex;
            justify-content: center;
        }

        .container {
            max-width: 700px;
            width: 100%;
            background: var(--card-bg);
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 25px;
        }

        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }

        form {
            margin-top: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--feedback-border);
        }

        label {
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
        }

        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .rating input[type="radio"] {
            display: none;
        }

        .rating label {
            font-size: 40px;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            margin: 0 5px;
        }

        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffc107;
            transform: scale(1.2);
        }

        .rating input[type="radio"]:checked ~ label {
            color: #ffc107;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 20px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        button {
            background-color: var(--secondary-color);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            margin-right: 10px;
        }

        button:hover {
            background-color: #218838;
        }

        .back-btn {
            background-color: #6c757d;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .feedback-list {
            margin-top: 30px;
        }

        .feedback-item {
            background: #f9f9f9;
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid var(--primary-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            word-wrap: break-word;
        }

        .feedback-item .rating {
            font-size: 22px;
            color: #ffc107;
            margin-bottom: 8px;
        }

        .feedback-item .comment {
            margin-bottom: 5px;
            color: var(--text-color);
            white-space: pre-wrap; /* ensures long comments wrap properly */
        }

        .feedback-item small {
            color: #555;
        }

        .form-actions {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Trip Feedback</h1>

    <?php if ($status === 'success'): ?>
        <div class="message success">✅ Thank you! Your feedback has been submitted.</div>
    <?php elseif ($status === 'error'): ?>
        <div class="message error">❌ Something went wrong. Please try again.</div>
    <?php endif; ?>

    <form action="submit_feedback.php" method="POST">
        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">

        <label>Overall Rating:</label>
        <div class="rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                <label for="star<?php echo $i; ?>">★</label>
            <?php endfor; ?>
        </div>

        <label for="comments">Comments:</label>
        <textarea id="comments" name="comments" rows="4" placeholder="Share your experience..."></textarea>

        <div class="form-actions">
            <button type="submit">Submit Feedback</button>
            <a href="trip_status.php"><button type="button" class="back-btn">← Back to Bookings</button></a>
        </div>
    </form>

    <?php if ($feedback_result->num_rows > 0): ?>
        <div class="feedback-list">
            <h2>Previous Feedback</h2>
            <?php while ($row = $feedback_result->fetch_assoc()): ?>
                <div class="feedback-item">
                    <div class="rating">
                        <?php
                        $rating = (int)$row['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?>
                    </div>
                    <div class="comment"><?php echo nl2br(htmlspecialchars($row['comments'])); ?></div>
                    <small>Submitted on <?php echo $row['feedback_date']; ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php
$feedback_stmt->close();
$conn->close();
?>
