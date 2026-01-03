<?php
// update_booking_status.php
session_start();
require 'db_connect.php'; // Ensure this points to your database connection file

// Check for required POST data and driver ID
if (!isset($_POST['id'], $_POST['status']) || !isset($_SESSION['driver_id'])) {
    echo "error: Invalid request.";
    exit();
}

$booking_id = (int)$_POST['id'];
$new_status = trim($_POST['status']);
$driver_id = $_SESSION['driver_id'];

// Determine which action to take
if ($new_status === 'Accepted') {
    // 1. Update the booking status and assign the driver
    // NOTE: You need to add a column named 'driver_id' to your 'bookings' table for this to work properly.
    // If you haven't done that, replace the line below with the simple update:
    // $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $sql = "UPDATE bookings SET status = ?, driver_id = ? WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sii", $new_status, $driver_id, $booking_id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error: " . $stmt->error;
        }
        $stmt->close();
    }
} elseif ($new_status === 'Rejected') {
    // 2. Simply mark the booking as rejected (or pending, but 'Rejected' is clearer)
    // For a real app, this should remove the booking request specifically from *this* driver's view.
    // For simplicity here, we'll mark it 'Rejected' (or 'Pending' if you want another driver to see it).
    // Let's use 'Rejected' to remove the buttons, or 'Pending' to keep the buttons for demonstration.
    // For this example, setting it to 'Rejected' is better for UI feedback.
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_status, $booking_id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    echo "error: Invalid status value.";
}

$conn->close();
?>