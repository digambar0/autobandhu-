<?php
// Start the session for the redirect
session_start();

// --- REQUIRED CHANGE: Manual Dompdf Autoloader ---
// This line replaces the composer autoloader (require 'vendor/autoload.php';)
// and assumes the dompdf library folder is named 'dompdf' in your project root.
require 'dompdf/autoload.inc.php'; 
// --- END CHANGE ---

// Use the namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Database Connection Setup
require 'db_connect.php'; 

// --- Configuration & Input Retrieval ---
$booking_id_to_view = $_GET['id'] ?? null; 

if (!is_numeric($booking_id_to_view) || $booking_id_to_view <= 0) {
    die("Error: Invalid or missing Booking ID for invoice generation.");
}

$booking_id_to_view = (int)$booking_id_to_view;

// --- Helper Functions (Copied from trip_summary.php) ---
function calculate_distance($start, $end) {
    if ($start == 'belgaum' && $end == 'hindlga') return '15.5 km';
    if ($start == 'bemco' && $end == 'kle') return '8.2 km';
    return '10.0 km (Estimate)';
}

function calculate_price($distance_str) {
    $base_fare = 50;
    if (preg_match('/(\d+\.?\d*)\s*km/', $distance_str, $matches)) {
        $distance = (float)$matches[1];
        $fare_per_km = 15;
        $total_price = $base_fare + ($distance * $fare_per_km);
        return '₹' . number_format($total_price, 2);
    }
    return '₹150.00 (Estimate)';
}

// 2. Fetch Detailed Booking Data
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id, b.starting_point, b.destination, b.booking_time, b.status,
        u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
        d.name AS driver_name, d.phone AS driver_phone, d.vehicle_no, d.license_no
    FROM 
        bookings b
    JOIN 
        users u ON b.user_id = u.id
    LEFT JOIN 
        drivers d ON b.driver_id = d.id
    WHERE 
        b.id = ?
");

$stmt->bind_param("i", $booking_id_to_view);
$stmt->execute();
$result = $stmt->get_result();
$trip_details = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$trip_details) {
    die("Error: Booking ID $booking_id_to_view not found.");
}

$distance = calculate_distance($trip_details['starting_point'], $trip_details['destination']);
$price = calculate_price($distance);
$invoice_number = "INV-" . $trip_details['booking_id'] . "-" . date('Ymd');
$date_issued = date('F j, Y');


// 3. Generate HTML Content for the PDF 
$html = '
<html>
<head>
    <style>
        /* Styles are crucial for good looking PDF */
        body { font-family: sans-serif; margin: 0; padding: 0; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 14px; line-height: 24px; color: #555; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #006400; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        .footer-note { text-align: center; margin-top: 30px; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                Autobandhu
                            </td>
                            <td>
                                Invoice #: ' . htmlspecialchars($invoice_number) . '<br>
                                Booking ID: ' . htmlspecialchars($trip_details['booking_id']) . '<br>
                                Created: ' . htmlspecialchars($date_issued) . '<br>
                                Trip Date: ' . date('F j, Y, g:i a', strtotime($trip_details['booking_time'])) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                **Rider Details:**<br>
                                ' . htmlspecialchars($trip_details['user_name']) . '<br>
                                ' . htmlspecialchars($trip_details['user_phone']) . '<br>
                                ' . htmlspecialchars($trip_details['user_email']) . '
                            </td>

                            <td>
                                **Driver Details:**<br>
                                ' . (empty($trip_details['driver_name']) ? 'Driver Pending' : htmlspecialchars($trip_details['driver_name'])) . '<br>
                                Vehicle No: ' . (empty($trip_details['vehicle_no']) ? 'N/A' : htmlspecialchars($trip_details['vehicle_no'])) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Item</td>
                <td>Price</td>
            </tr>

            <tr class="item">
                <td>Base Fare (Booking)</td>
                <td>₹50.00</td>
            </tr>

            <tr class="item">
                <td>Distance Fare (' . htmlspecialchars($distance) . ')</td>
                <td>' . htmlspecialchars($price) . ' (Estimate)</td>
            </tr>

            <tr class="item last">
                <td>Taxes & Fees</td>
                <td>₹0.00</td>
            </tr>

            <tr class="total">
                <td></td>
                <td>
                   Total: ' . htmlspecialchars($price) . '
                </td>
            </tr>
        </table>
        
        <p class="footer-note">Thank you for riding with Autobandhu! We look forward to your next trip in Belgaum.</p>
    </div>
</body>
</html>
';

// 4. Dompdf Initialization and PDF Output 

// Check if output buffering is active and clear it before sending headers
if (ob_get_level()) {
    ob_end_clean();
}

// Instantiate Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 
$dompdf = new Dompdf($options);

// Load HTML to Dompdf
$dompdf->loadHtml($html);

// Set paper size (e.g., A4) and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF (D = Download)
$dompdf->stream("Autobandhu_Invoice_B" . $trip_details['booking_id'] . ".pdf", array("Attachment" => true));


// Redirect to show the success message. 
header("Location: trip_summary.php?id=" . $booking_id_to_view . "&download=true");
exit;