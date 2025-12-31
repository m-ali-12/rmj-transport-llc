<?php
include("connection.php");

// PHP Processing Section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Collect and sanitize form data
    $formData = [
        'customerName' => filter_input(INPUT_POST, 'customerName', FILTER_SANITIZE_STRING) ?? '',
        'customerEmail' => filter_input(INPUT_POST, 'customerEmail', FILTER_SANITIZE_EMAIL) ?? '',
        'customerPhone' => filter_input(INPUT_POST, 'customerPhone', FILTER_SANITIZE_STRING) ?? '',
        'pickupCity' => filter_input(INPUT_POST, 'pickupCity', FILTER_SANITIZE_STRING) ?? '',
        'pickupState' => filter_input(INPUT_POST, 'pickupState', FILTER_SANITIZE_STRING) ?? '',
        'pickupZipcode' => filter_input(INPUT_POST, 'pickupZipcode', FILTER_SANITIZE_NUMBER_INT) ?? '',
        'deliveryCity' => filter_input(INPUT_POST, 'deliveryCity', FILTER_SANITIZE_STRING) ?? '',
        'deliveryState' => filter_input(INPUT_POST, 'deliveryState', FILTER_SANITIZE_STRING) ?? '',
        'deliveryZipcode' => filter_input(INPUT_POST, 'deliveryZipcode', FILTER_SANITIZE_NUMBER_INT) ?? '',
        'vehicleYear' => filter_input(INPUT_POST, 'vehicleYear', FILTER_SANITIZE_NUMBER_INT) ?? '',
        'vehicleMake' => filter_input(INPUT_POST, 'vehicleMake', FILTER_SANITIZE_STRING) ?? '',
        'vehicleModel' => filter_input(INPUT_POST, 'vehicleModel', FILTER_SANITIZE_STRING) ?? '',
        'vehicleType' => filter_input(INPUT_POST, 'vehicleType', FILTER_SANITIZE_STRING) ?? '',
        'transportType' => filter_input(INPUT_POST, 'transportType', FILTER_SANITIZE_STRING) ?? '',
        'shipmentDate' => filter_input(INPUT_POST, 'shipmentDate', FILTER_SANITIZE_STRING) ?? '',
        'specialInstructions' => filter_input(INPUT_POST, 'specialInstructions', FILTER_SANITIZE_STRING) ?? ''
    ];

    // Validate required fields
    $errors = [];
    $requiredFields = [
        'customerName', 'customerEmail', 'customerPhone',
        'pickupCity', 'pickupState', 'pickupZipcode',
        'deliveryCity', 'deliveryState', 'deliveryZipcode',
        'vehicleYear', 'vehicleMake', 'vehicleModel',
        'vehicleType', 'transportType', 'shipmentDate'
    ];

    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[] = ucfirst(preg_replace('/(?<!^)[A-Z]/', ' $0', $field)) . " is required";
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Database insertion with proper error handling
    try {
        // Prepare the SQL statement with new column names
        $sql = "INSERT INTO shipment_quote (
            customer_name,
            customer_email,
            customer_phone,
            pickup_city,
            pickup_state,
            pickup_zipcode,
            delivery_city,
            delivery_state,
            delivery_zipcode,
            vehicle_year,
            vehicle_make,
            vehicle_model,
            vehicle_type,
            transport_type,
            shipment_date,
            special_instructions,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Convert string numbers to integers for proper binding
        $pickupZipcode = (int)$formData['pickupZipcode'];
        $deliveryZipcode = (int)$formData['deliveryZipcode'];
        $vehicleYear = (int)$formData['vehicleYear'];

        // Bind parameters with correct types
        $stmt->bind_param(
            "sssssisssissssss",
            $formData['customerName'],
            $formData['customerEmail'],
            $formData['customerPhone'],
            $formData['pickupCity'],
            $formData['pickupState'],
            $pickupZipcode,
            $formData['deliveryCity'],
            $formData['deliveryState'],
            $deliveryZipcode,
            $vehicleYear,
            $formData['vehicleMake'],
            $formData['vehicleModel'],
            $formData['vehicleType'],
            $formData['transportType'],
            $formData['shipmentDate'],
            $formData['specialInstructions']
        );

        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $quote_id = $conn->insert_id;
        $stmt->close();

    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }

    // Prepare email
    $to = 'ENTER THE GMAIL ID WHRER U WNAT TO GAMIL';
    $fromName = "MJ Hauling United LLC";
    $subject = "New Car Shipment Quote Request from " . $formData['customerName'] . " (ID: #" . $quote_id . ")";

    $email_body = "
    <html>
    <head>
        <title>Car Shipment Quote Request</title>
    </head>
    <body>
        <h2>New Car Shipment Quote Request</h2>
        <p><strong>Quote ID:</strong> #$quote_id</p>

        <h3>Customer Information</h3>
        <p><strong>Name:</strong> {$formData['customerName']}</p>
        <p><strong>Email:</strong> {$formData['customerEmail']}</p>
        <p><strong>Phone:</strong> {$formData['customerPhone']}</p>

        <h3>Vehicle Details</h3>
        <p><strong>Vehicle:</strong> {$formData['vehicleYear']} {$formData['vehicleMake']} {$formData['vehicleModel']}</p>
        <p><strong>Type:</strong> " . ucfirst($formData['vehicleType']) . "</p>
        <p><strong>Transport:</strong> " . ucfirst($formData['transportType']) . "</p>

        <h3>Shipment Details</h3>
        <p><strong>Pickup:</strong> {$formData['pickupCity']}, {$formData['pickupState']} {$formData['pickupZipcode']}</p>
        <p><strong>Delivery:</strong> {$formData['deliveryCity']}, {$formData['deliveryState']} {$formData['deliveryZipcode']}</p>
        <p><strong>Date:</strong> {$formData['shipmentDate']}</p>

        <h3>Special Instructions</h3>
        <p>" . nl2br($formData['specialInstructions']) . "</p>

        <p><em>This quote was automatically saved to the database with ID: #$quote_id</em></p>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: MJ Hauling United LLC <info@mjhaulingunitedllc.com>',
        'Reply-To: ' . $formData['customerEmail']
    ];

    // Send email
    $mailSent = mail($to, $subject, $email_body, implode("\r\n", $headers));

    if ($mailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Quote request submitted successfully!',
            'quote_id' => $quote_id
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Quote saved to database but email failed to send. We will contact you soon.',
            'quote_id' => $quote_id
        ]);
    }
    exit;
}
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
   <meta charset="utf-8">
   <meta http-equiv="x-ua-compatible" content="ie=edge">
   <title>Car Shipment Quote - MJ Hauling United LLC</title>
   <meta name="description" content="Get a free car shipment quote from MJ Hauling United LLC">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
   <link rel="stylesheet" href="assets/css/preloader.css">
   <link rel="stylesheet" href="assets/css/bootstrap.min.css">
   <link rel="stylesheet" href="assets/css/meanmenu.css">
   <link rel="stylesheet" href="assets/css/animate.min.css">
   <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
   <link rel="stylesheet" href="assets/css/swiper-bundle.css">
   <link rel="stylesheet" href="assets/css/backToTop.css">
   <link rel="stylesheet" href="assets/css/magnific-popup.css">
   <link rel="stylesheet" href="assets/css/ui-range-slider.css">
   <link rel="stylesheet" href="assets/css/nice-select.css">
   <link rel="stylesheet" href="assets/css/fontAwesome5Pro.css">
   <link rel="stylesheet" href="assets/css/flaticon.css">
   <link rel="stylesheet" href="assets/css/default.css">
   <link rel="stylesheet" href="assets/css/style.css">
   <style>
        /* Custom styles for the quote form */
        .quote-section {
            padding: 70px 0;
        }

        .quote-form-container {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .form-title {
            color: #1e3c72;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Full width container for the form */
        .quote-form-container {
            max-width: 100%;
        }

        .form-grid-3-col {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .form-grid-3-col {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .form-grid-3-col {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.2);
        }

        .btn-quote {
            background: #1e3c72;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-quote:hover {
            background: #2a5298;
            transform: translateY(-2px);
        }

        #messageContainer {
            margin-bottom: 20px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        #quoteDisplay {
            display: none;
            margin-top: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #1e3c72;
        }

        .quote-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-item {
            background: white;
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .detail-item strong {
            color: #1e3c72;
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .quote-id-badge {
            background: #1e3c72;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-inputs-title {
            font-size: 21px !important;
            font-weight: 600;
            color: #1e3c72;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
            margin-bottom: 20px;
        }

        /* CSS to move the button to the right */
        .button-container {
            display: flex;
            justify-content: flex-end;
            width: 100%;
        }
   </style>
</head>

<body>
   <?php include("header2.php");?>

   <main>
     <section class="quote-section">
       <div class="container-fluid"> <div class="row">
           <div class="col-12"> <div class="quote-form-container">
               <h2 class="form-title">Get Your Free Car Shipment Quote</h2>
               <div id="messageContainer"></div>

               <form id="quoteForm">
                 <h3 class="form-inputs-title">CONTACT INFORMATION</h3>
                 <div class="form-grid-3-col"> <div class="form-group">
                     <label for="customerName">Full Name *</label>
                     <input type="text" id="customerName" name="customerName" required>
                   </div>
                   <div class="form-group">
                     <label for="customerEmail">Email Address *</label>
                     <input type="email" id="customerEmail" name="customerEmail" required>
                   </div>
                   <div class="form-group">
                     <label for="customerPhone">Phone Number *</label>
                     <input type="tel" id="customerPhone" name="customerPhone" required>
                   </div>
                 </div>

                 <h3 class="form-inputs-title">VEHICLE INFORMATION</h3>
                 <div class="form-grid-3-col"> <div class="form-group">
                     <label for="vehicleYear">Vehicle Year *</label>
                     <input type="number" id="vehicleYear" name="vehicleYear" min="1980" max="2025" required>
                   </div>
                   <div class="form-group">
                     <label for="vehicleMake">Vehicle Make *</label>
                     <input type="text" id="vehicleMake" name="vehicleMake" required>
                   </div>
                   <div class="form-group">
                     <label for="vehicleModel">Vehicle Model *</label>
                     <input type="text" id="vehicleModel" name="vehicleModel" required>
                   </div>
                   <div class="form-group">
                     <label for="vehicleType">Vehicle Type *</label>
                     <select id="vehicleType" name="vehicleType" required>
                       <option value="">Select Vehicle Type</option>
                       <option value="sedan">Sedan</option>
                       <option value="suv">SUV</option>
                       <option value="truck">Truck</option>
                       <option value="motorcycle">Motorcycle</option>
                       <option value="van">Van</option>
                       <option value="luxury">Luxury Car</option>
                     </select>
                   </div>
                   <div class="form-group">
                     <label for="transportType">Truck Type *</label>
                     <select id="transportType" name="transportType" required>
                       <option value="">Select Truck Type</option>
                       <option value="open">Open Truck</option>
                       <option value="enclosed">Enclosed Truck</option>
                     </select>
                   </div>
                   <div class="form-group">
                     <label for="shipmentDate">Preferred Shipment Date *</label>
                     <input type="date" id="shipmentDate" name="shipmentDate" required>
                   </div>
                 </div>

                 <h3 class="form-inputs-title">PICKUP AND DELIVERY INFORMATION</h3>
                 <div class="form-grid-3-col">
                   <div class="form-group">
                     <label for="pickupCity">Pickup City *</label>
                     <input type="text" id="pickupCity" name="pickupCity" required>
                   </div>
                   <div class="form-group">
                     <label for="pickupState">Pickup State *</label>
                     <input type="text" id="pickupState" name="pickupState" required>
                   </div>
                   <div class="form-group">
                     <label for="pickupZipcode">Pickup Zipcode *</label>
                     <input type="number" id="pickupZipcode" name="pickupZipcode" required>
                   </div>
                 </div>
                 <div class="form-grid-3-col">
                   <div class="form-group">
                     <label for="deliveryCity">Delivery City *</label>
                     <input type="text" id="deliveryCity" name="deliveryCity" required>
                   </div>
                   <div class="form-group">
                     <label for="deliveryState">Delivery State *</label>
                     <input type="text" id="deliveryState" name="deliveryState" required>
                   </div>
                   <div class="form-group">
                     <label for="deliveryZipcode">Delivery Zipcode *</label>
                     <input type="number" id="deliveryZipcode" name="deliveryZipcode" required>
                   </div>
                 </div>

                 <div class="form-group full-width">
                   <label for="specialInstructions">Special Instructions</label>
                   <textarea id="specialInstructions" name="specialInstructions" rows="4" placeholder="Any special requirements or instructions..."></textarea>
                 </div>

                 <div class="button-container">
                   <button type="submit" class="btn-quote">Get Free Quote</button>
                 </div>
               </form>

               <div id="quoteDisplay">
                 <h3 class="form-title">Your Shipment Quote Details</h3>
                 <div id="quoteIdBadge" class="quote-id-badge"></div>
                 <div class="quote-details" id="quoteDetails"></div>
                 <div class="action-buttons">
                   <button onclick="sendQuoteEmail()" class="btn-quote">Email This Quote</button>
                   <button onclick="generatePDF()" class="btn-quote" style="background: #6c757d;">Download PDF</button>
                   <button onclick="resetForm()" class="btn-quote" style="background: #dc3545;">Start New Quote</button>
                 </div>
               </div>
             </div>
           </div>
         </div>
       </div>
     </section>
   </main>
   <?php include("footer.php");?>

   <div class="progress-wrap">
     <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
       <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
     </svg>
   </div>
   <script src="assets/js/vendor/jquery-3.6.0.min.js"></script>
   <script src="assets/js/vendor/waypoints.min.js"></script>
   <script src="assets/js/bootstrap.bundle.min.js"></script>
   <script src="assets/js/meanmenu.js"></script>
   <script src="assets/js/swiper-bundle.min.js"></script>
   <script src="assets/js/owl.carousel.min.js"></script>
   <script src="assets/js/magnific-popup.min.js"></script>
   <script src="assets/js/parallax.min.js"></script>
   <script src="assets/js/backToTop.js"></script>
   <script src="assets/js/jquery-ui-slider-range.js"></script>
   <script src="assets/js/nice-select.min.js"></script>
   <script src="assets/js/counterup.min.js"></script>
   <script src="assets/js/ajax-form.js"></script>
   <script src="assets/js/wow.min.js"></script>
   <script src="assets/js/isotope.pkgd.min.js"></script>
   <script src="assets/js/imagesloaded.pkgd.min.js"></script>
   <script src="assets/js/rangeslider-js.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
   <script>
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;

        // Form submission handler
        document.getElementById('quoteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            fetch('quote.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                const messageContainer = document.getElementById('messageContainer');
                messageContainer.innerHTML = '';

                if (data.success) {
                    messageContainer.innerHTML = `<div class="success-message">${data.message}</div>`;
                    // Populate the quote display area with form data and quote ID
                    displayQuote(Object.fromEntries(formData.entries()), data.quote_id);
                    document.getElementById('quoteForm').style.display = 'none'; // Hide the form
                } else {
                    messageContainer.innerHTML = `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                const messageContainer = document.getElementById('messageContainer');
                messageContainer.innerHTML = `<div class="error-message">An unexpected error occurred. Please try again.</div>`;
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // Display quote
        function displayQuote(formData, quoteId) {
            // Display quote ID badge
            document.getElementById('quoteIdBadge').textContent = `Quote ID: #${quoteId}`;

            const container = document.getElementById('quoteDetails');
            container.innerHTML = `
                <div class="detail-item">
                    <strong>Customer Information</strong>
                    ${formData.customerName}<br>
                    ${formData.customerEmail}<br>
                    ${formData.customerPhone}
                </div>
                <div class="detail-item">
                    <strong>Vehicle Details</strong>
                    ${formData.vehicleYear} ${formData.vehicleMake} ${formData.vehicleModel}<br>
                    Type: ${formData.vehicleType.charAt(0).toUpperCase() + formData.vehicleType.slice(1)}
                </div>
                <div class="detail-item">
                    <strong>Transport Details</strong>
                    ${formData.transportType.charAt(0).toUpperCase() + formData.transportType.slice(1)} Transport<br>
                </div>
                <div class="detail-item">
                    <strong>Pickup Information</strong>
                    From: ${formData.pickupCity}, ${formData.pickupState} ${formData.pickupZipcode}
                </div>
                <div class="detail-item">
                    <strong>Delivery Information</strong>
                    To: ${formData.deliveryCity}, ${formData.deliveryState} ${formData.deliveryZipcode}
                </div>
                <div class="detail-item">
                    <strong>Shipment Date</strong>
                    ${formData.shipmentDate}
                </div>
                <div class="detail-item">
                    <strong>Special Instructions</strong>
                    ${formData.specialInstructions || 'None'}
                </div>
            `;

            document.getElementById('quoteDisplay').style.display = 'block';
            window.currentQuote = { formData, quoteId };
            document.getElementById('quoteDisplay').scrollIntoView({ behavior: 'smooth' });
        }

        // Email quote
        function sendQuoteEmail() {
            if (!window.currentQuote) return;

            const { formData, quoteId } = window.currentQuote;
            const messageContainer = document.getElementById('messageContainer');
            const emailBtn = document.querySelector('button[onclick="sendQuoteEmail()"]');
            const originalText = emailBtn.textContent;

            emailBtn.disabled = true;
            emailBtn.textContent = 'Sending...';

            // Create new FormData object for the email request
            const emailData = new FormData();
            for (const key in formData) {
                emailData.append(key, formData[key]);
            }
            emailData.append('quote_id', quoteId); // Add the quote ID to the data

            // The PHP script is named quote.php, so we use that here
            fetch('quote.php', {
                method: 'POST',
                body: emailData
            })
            .then(response => response.json())
            .then(data => {
                messageContainer.innerHTML = '';
                if (data.success) {
                    messageContainer.innerHTML = `<div class="success-message">Your quote has been sent to ${formData.customerEmail} successfully!</div>`;
                } else {
                    messageContainer.innerHTML = `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                messageContainer.innerHTML = `<div class="error-message">Failed to send email. Please try again.</div>`;
                console.error('Error:', error);
            })
            .finally(() => {
                emailBtn.disabled = false;
                emailBtn.textContent = originalText;
            });
        }

        // Generate PDF
        function generatePDF() {
            if (!window.currentQuote) return;

            const { formData, quoteId } = window.currentQuote;
            const doc = new jsPDF();

            // Add logo or header
            doc.setFontSize(18);
            doc.setTextColor(30, 60, 114);
            doc.text('MJ Hauling United LLC', 20, 20);
            doc.setFontSize(14);
            doc.text('Car Shipment Quote', 20, 30);
            doc.text(`Quote ID: #${quoteId}`, 20, 40);

            // Add customer information
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('Customer Information:', 20, 60);
            doc.setFont(undefined, 'normal');
            doc.text(`Name: ${formData.customerName}`, 20, 70);
            doc.text(`Email: ${formData.customerEmail}`, 20, 80);
            doc.text(`Phone: ${formData.customerPhone}`, 20, 90);

            // Add vehicle details
            doc.setFont(undefined, 'bold');
            doc.text('Vehicle Details:', 20, 110);
            doc.setFont(undefined, 'normal');
            doc.text(`Vehicle: ${formData.vehicleYear} ${formData.vehicleMake} ${formData.vehicleModel}`, 20, 120);
            doc.text(`Type: ${formData.vehicleType.charAt(0).toUpperCase() + formData.vehicleType.slice(1)}`, 20, 130);
            doc.text(`Transport: ${formData.transportType.charAt(0).toUpperCase() + formData.transportType.slice(1)}`, 20, 140);

            // Add shipment details
            doc.setFont(undefined, 'bold');
            doc.text('Shipment Details:', 20, 160);
            doc.setFont(undefined, 'normal');
            doc.text(`Pickup: ${formData.pickupCity}, ${formData.pickupState} ${formData.pickupZipcode}`, 20, 170);
            doc.text(`Delivery: ${formData.deliveryCity}, ${formData.deliveryState} ${formData.deliveryZipcode}`, 20, 180);
            doc.text(`Date: ${formData.shipmentDate}`, 20, 190);

            // Add special instructions
            doc.setFont(undefined, 'bold');
            doc.text('Special Instructions:', 20, 210);
            doc.setFont(undefined, 'normal');
            doc.text(`${formData.specialInstructions || 'None'}`, 20, 220);

            // Add footer
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text('Thank you for choosing MJ Hauling United LLC', 20, 280);
            doc.text('Email: info@mjhaulingunitedllc.com | Phone: (555) 123-4567', 20, 290);

            // Save PDF
            doc.save(`MJHauling_Quote_${quoteId}_${formData.customerName.replace(/\s+/g, '_')}.pdf`);
        }

        // Reset form
        function resetForm() {
            document.getElementById('quoteForm').reset();
            document.getElementById('quoteDisplay').style.display = 'none';
            document.getElementById('messageContainer').innerHTML = '';
            document.getElementById('quoteForm').style.display = 'block'; // Show the form again
            window.currentQuote = null;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Set minimum date to today
        document.getElementById('shipmentDate').min = new Date().toISOString().split('T')[0];
   </script>
</body>
</html>