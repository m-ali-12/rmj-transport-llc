<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    $to = "";
    $subject = "New Car Shipment Quote Request from " . $data['customerName'];

    $message = "
        <h2>Customer Quote Details</h2>
        <p><strong>Name:</strong> {$data['customerName']}</p>
        <p><strong>Email:</strong> {$data['customerEmail']}</p>
        <p><strong>Phone:</strong> {$data['customerPhone']}</p>

        <h3>Vehicle Details</h3>
        <p>{$data['vehicleYear']} {$data['vehicleMake']} {$data['vehicleModel']}</p>
        <p><strong>Type:</strong> {$data['vehicleType']}</p>
        <p><strong>Transport Type:</strong> {$data['transportType']}</p>

        <h3>Route</h3>
        <p><strong>Pickup:</strong> {$data['pickupLocation']}</p>
        <p><strong>Delivery:</strong> {$data['deliveryLocation']}</p>
        <p><strong>Shipment Date:</strong> {$data['shipmentDate']}</p>
        <p><strong>Distance:</strong> {$data['distance']} miles</p>

        <h3>Special Instructions</h3>
        <p>" . (!empty($data['specialInstructions']) ? nl2br($data['specialInstructions']) : 'None') . "</p>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: MJ Hauling <noreply@mjhaulingunitedllc.com>" . "\r\n";

    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>
