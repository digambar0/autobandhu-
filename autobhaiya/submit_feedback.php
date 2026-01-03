<?php
session_start();
require_once 'db_connect.php';

// --- Get current logged-in user ID ---
$current_user_id = $_SESSION['user_id'] ?? 0;
if ($current_user_id <= 0) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// --- Ensure request method is POST ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feedback.php?status=error");
    exit;
}

// --- Sanitize and validate input ---
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
$rating     = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
$comments   = trim($_POST['comments'] ?? '');

if (empty($booking_id) || empty($rating) || !is_numeric($rating) || $rating < 1 || $rating > 5) {
    header("Location: feedback.php?id=" . urlencode($booking_id) . "&status=error");
    exit;
}

$booking_id = intval($booking_id);
$safe_comments = htmlspecialchars($comments, ENT_QUOTES, 'UTF-8');

// --- Start transaction ---
$conn->begin_transaction();

try {
    // --- Verify that this booking belongs to the current user ---
    $check_sql = "SELECT user_id FROM bookings WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) throw new Exception("Check prepare failed: " . $conn->error);

    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $booking_row = $result->fetch_assoc();
    $check_stmt->close();

    if (!$booking_row || $booking_row['user_id'] != $current_user_id) {
        throw new Exception("Unauthorized booking.");
    }

    // --- Insert feedback (no duplicate check anymore) ---
    $insert_sql = "
        INSERT INTO feedback (booking_id, rating, comments, feedback_date)
        VALUES (?, ?, ?, NOW())
    ";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) throw new Exception("Insert prepare failed: " . $conn->error);

    $insert_stmt->bind_param("iis", $booking_id, $rating, $safe_comments);

    if ($insert_stmt->execute()) {
        $conn->commit();
        header("Location: feedback.php?id=" . urlencode($booking_id) . "&status=success");
        exit;
    } else {
        throw new Exception("Insert execute failed: " . $insert_stmt->error);
    }

} catch (Exception $e) {
    $conn->rollback();
    header("Location: feedback.php?id=" . urlencode($booking_id) . "&status=error");
    exit;
} finally {
    $conn->close();
}
?>
