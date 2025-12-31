<?php
include("connection.php");

// PHP Processing Section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Sanitize input
    $formData = [
        'customerName' => mysqli_real_escape_string($conn, $_POST['customerName'] ?? ''),
        'customerEmail' => mysqli_real_escape_string($conn, $_POST['customerEmail'] ?? ''),
        'customerPhone' => mysqli_real_escape_string($conn, $_POST['customerPhone'] ?? ''),
        'pickupLocation' => mysqli_real_escape_string($conn, $_POST['pickupLocation'] ?? ''),
        'deliveryLocation' => mysqli_real_escape_string($conn, $_POST['deliveryLocation'] ?? ''),
        'vehicleYear' => (int)($_POST['vehicleYear'] ?? 0),
        'vehicleMake' => mysqli_real_escape_string($conn, $_POST['vehicleMake'] ?? ''),
        'vehicleModel' => mysqli_real_escape_string($conn, $_POST['vehicleModel'] ?? ''),
        'vehicleType' => mysqli_real_escape_string($conn, $_POST['vehicleType'] ?? ''),
        'transportType' => mysqli_real_escape_string($conn, $_POST['transportType'] ?? ''),
        'shipmentDate' => mysqli_real_escape_string($conn, $_POST['shipmentDate'] ?? ''),
        'distance' => (int)($_POST['distance'] ?? 0),
        'specialInstructions' => mysqli_real_escape_string($conn, $_POST['specialInstructions'] ?? '')
    ];

    // Validate required fields
    $errors = [];
    foreach ($formData as $key => $value) {
        if (empty($value) && $key !== 'specialInstructions') {
            $errors[] = ucfirst($key) . " is required";
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // ✅ Step 1: Insert into Database
    $sql = "INSERT INTO shipment_quotes (
        customer_name, customer_email, customer_phone, pickup_location,
        delivery_location, vehicle_year, vehicle_make, vehicle_model,
        vehicle_type, transport_type, shipment_date, distance, special_instructions
    ) VALUES (
        '{$formData['customerName']}', '{$formData['customerEmail']}', '{$formData['customerPhone']}',
        '{$formData['pickupLocation']}', '{$formData['deliveryLocation']}', {$formData['vehicleYear']},
        '{$formData['vehicleMake']}', '{$formData['vehicleModel']}', '{$formData['vehicleType']}',
        '{$formData['transportType']}', '{$formData['shipmentDate']}', {$formData['distance']},
        '{$formData['specialInstructions']}'
    )";

    $dbSuccess = mysqli_query($conn, $sql);

    if (!$dbSuccess) {
        echo json_encode(['success' => false, 'message' => 'Failed to save data.']);
        exit;
    }

    // ✅ Step 2: Send Email
    $to = '';
    $subject = "New Car Shipment Quote Request from " . $formData['customerName'];
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: MJ Hauling United LLC <info@mjhaulingunitedllc.com>\r\n";
    $headers .= "Reply-To: " . $formData['customerEmail'] . "\r\n";

    $email_body = "
    <html>
    <head><title>New Quote Request</title></head>
    <body>
    <h2>New Car Shipment Quote Request</h2>
    <p><strong>Name:</strong> {$formData['customerName']}</p>
    <p><strong>Email:</strong> {$formData['customerEmail']}</p>
    <p><strong>Phone:</strong> {$formData['customerPhone']}</p>
    <p><strong>Vehicle:</strong> {$formData['vehicleYear']} {$formData['vehicleMake']} {$formData['vehicleModel']}</p>
    <p><strong>Type:</strong> {$formData['vehicleType']}</p>
    <p><strong>Transport:</strong> {$formData['transportType']}</p>
    <p><strong>Pickup:</strong> {$formData['pickupLocation']}</p>
    <p><strong>Delivery:</strong> {$formData['deliveryLocation']}</p>
    <p><strong>Date:</strong> {$formData['shipmentDate']}</p>
    <p><strong>Distance:</strong> {$formData['distance']} miles</p>
    <p><strong>Instructions:</strong> {$formData['specialInstructions']}</p>
    </body>
    </html>";

    $mailSent = mail($to, $subject, $email_body, $headers);

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => 'Quote saved and email sent!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Quote saved but failed to send email.']);
    }
    exit;
}
?>
