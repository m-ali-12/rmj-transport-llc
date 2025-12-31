<?php
// Include your database connection file if needed.
// For this email-focused snippet, it's not directly used unless for database logging.
// include("connection.php"); // Uncomment if you use it for database operations

// Include the FPDF library
require('./libs/fpdf186/fpdf.php'); // Adjust path if your fpdf folder is elsewhere

if(isset($_POST['submit'])) {
    // 1. Get and sanitize form data
    $name = htmlspecialchars($_POST['f_name'], ENT_QUOTES, 'UTF-8');
    $date = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'); // Assuming 'email' field is used for date based on your HTML form input type="date"
    $signature = $_POST['signature']; // Base64 signature data

    // --- Handle signature as PNG file ---
    $signatureImgSrc = ''; // Default to empty
    $filepath = ''; // Initialize filepath for PDF embedding

    if (!empty($signature)) {
        // Remove the "data:image/png;base64," prefix
        $base64_image = str_replace('data:image/png;base64,', '', $signature);
        $base64_image = str_replace(' ', '+', $base64_image); // Replace spaces with +
        $decoded_image = base64_decode($base64_image);

        // Define the directory to save signatures. Make sure this directory exists and is writable.
        $upload_dir = 'assets/signatures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Create directory if it doesn't exist
        }

        // Generate a unique filename for the signature
        $filename = uniqid('signature_') . '.png';
        $filepath = $upload_dir . $filename;

        // Save the decoded image to the file
        if (file_put_contents($filepath, $decoded_image)) {
            // Get the full URL to the saved image for embedding in email (still relevant if sending email)
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/";
            $signatureImgSrc = $base_url . $filepath;
        } else {
            error_log("Failed to save signature image to: " . $filepath);
        }
    }
    // --- END Signature Handling ---

    // --- FPDF PDF Generation ---
    class PDF extends FPDF {
        // Page header
        function Header() {
            // Logo (optional)
            // $this->Image('path/to/your/logo.png', 10, 8, 33);
            $this->SetFont('Arial', 'B', 15);
            // Move to the right
            $this->Cell(80);
            // Title
            $this->Cell(30, 10, 'Shipper Agreement', 0, 0, 'C');
            // Line break
            $this->Ln(20);
        }

        // Page footer
        function Footer() {
            // Position at 1.5 cm from bottom
            $this->SetY(-15);
            // Arial italic 8
            $this->SetFont('Arial', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }

        // Agreement content
        function ChapterBody($name, $date, $signaturePath) {
            $this->SetFont('Arial', '', 12);
            $this->MultiCell(0, 10, "This Shipper Agreement was submitted by {$name} on {$date} at " . date('Y-m-d H:i:s') . ".\n\n");

            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'Shipper Agreement Terms & Conditions', 0, 1, 'L');
            $this->Ln(5);

            $terms = [
                '1. Exclusive Broker Arrangement' => 'By signing this agreement, the Client agrees to work exclusively with the Broker for the entire shipping period. If another broker or carrier is engaged during this period, a non-refundable deposit will be retained.',
                '2. Door-to-Door Service' => 'Carriers will make every effort to provide door-to-door transport. If the pickup or delivery location has access restrictions, the Client and Carrier will agree upon a nearby meeting point.',
                '3. Transport Authorization' => 'The Carrier\'s driver is fully authorized to transport the vehicle between the pickup and delivery addresses listed. Deposits paid are final and only partially refundable under specific conditions.',
                '4. Personal Items' => 'Carriers are not responsible or insured for personal items inside the vehicle. Any items must:\n• Weigh no more than 100 lbs\n• Be secured in the trunk\n• Not interfere with transport operations',
                '5. Vehicle Condition' => 'The vehicle must be in good running condition and contain no more than half a tank of fuel. Carriers are not responsible for damage caused by mechanical issues, leaking fluids, or improper loading.',
                '6. Insurance Coverage' => 'Carriers maintain insurance coverage ranging from $100,000 to $250,000 per load. Claims for damage during transport are covered by the Carrier\'s insurance.',
                '7. Market Fluctuations' => 'In recognition of market dynamics and evolving transport conditions, rates may be subject to adjustments of up to thirty-five percent prior to dispatch. This ensures that the service remains aligned with current demands and operational factors.',
                '8. Scheduling & Rescheduling' => 'If the vehicle is not ready for pickup as scheduled, a rescheduling fee will be charged:\n• 2-5 days\' delay: additional fee applies',
                '9. Inspection Reports' => 'The Client is responsible for completing an inspection report at both pickup and delivery. Failure to do so may void the ability to file a damage claim.',
                '10. Non-Operational Vehicles' => 'A $150 fee applies to inoperable vehicles and will be included in the final invoice.',
                '11. Cancellation & Refunds' => 'Cancellations must be made prior to driver assignment. If no driver was dispatched, a refund minus up to $250 in administrative fees will be provided.',
                '12. Payment & Chargebacks' => 'The Client agrees to pay the full quoted price for transport services. No chargebacks will be pursued against the Broker or Carrier. This agreement is governed by the laws of the United States'
            ];

            $this->SetFont('Arial', '', 10);
            foreach ($terms as $heading => $content) {
                $this->SetFont('Arial', 'B', 12);
                $this->MultiCell(0, 7, $heading, 0, 'L');
                $this->SetFont('Arial', '', 10);
                $this->MultiCell(0, 6, $content, 0, 'L');
                $this->Ln(3);
            }

            $this->Ln(10); // Add some space before signature
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, 'Customer Signature:', 0, 1, 'L');

            if (!empty($signaturePath) && file_exists($signaturePath)) {
                // Get image dimensions to fit within a certain width
                list($imgWidth, $imgHeight) = getimagesize($signaturePath);
                $maxWidth = 100; // Max width for signature image in PDF
                $scale = $maxWidth / $imgWidth;
                $newWidth = $imgWidth * $scale;
                $newHeight = $imgHeight * $scale;

                $this->Image($signaturePath, $this->GetX(), $this->GetY(), $newWidth, $newHeight);
                $this->Ln($newHeight + 5); // Move cursor down after image
            } else {
                $this->SetFont('Arial', 'I', 10);
                $this->Cell(0, 10, 'No signature provided or failed to load signature image.', 0, 1, 'L');
            }

            $this->Ln(10);
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 5, 'This agreement was submitted through the MJ Hauling website.', 0, 1, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AliasNbPages(); // For {nb} in footer
    $pdf->AddPage();
    $pdf->ChapterBody($name, $date, $filepath); // Pass filepath to PDF method

    // Output PDF: 'D' for download, 'I' for inline display, 'F' for save to file
    $pdfFileName = "Shipper_Agreement_{$name}_" . date('Ymd_His') . ".pdf";
    $pdf->Output('D', $pdfFileName); // 'D' will force download the PDF

    // --- End FPDF PDF Generation ---

    // --- Email Sending (Optional: You can still send the email if needed) ---
    // Make sure you have PHPMailer or a similar robust mailer for better results
    // For simplicity, keeping the basic mail() function, but it's not recommended for production.
    $to = ""; // Recipient email address
    $from = ""; // Recommended: Use an email on your actual domain
    $fromName = "MJ Hauling United LLC";
    $subject = "New Shipper Agreement Submission";

    $message = <<<EOT
    <html>
    <head>
        <title>New Shipper Agreement</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background-color: #f4f4f4; padding: 15px; text-align: center; border-bottom: 1px solid #ddd; }
            .content { padding: 20px; }
            .signature-box { border: 2px solid #000; padding: 10px; margin: 20px 0; text-align: center; background-color: #fff; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            .label { font-weight: bold; width: 30%; }
            ul { list-style: disc; margin-left: 20px; padding-left: 0; }
            li { margin-bottom: 5px; }
            h2, h4 { color: #0056b3; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Shipper Agreement Submission</h2>
                <p>Submitted from MJ Hauling United LLC website</p>
            </div>
            <div class='content'>
                <table>
                    <tr>
                        <td class='label'>Customer Name:</td>
                        <td>{$name}</td>
                    </tr>
                    <tr>
                        <td class='label'>Agreement Date:</td>
                        <td>{$date}</td>
                    </tr>
                    <tr>
                        <td class='label'>Submission Time:</td>
                        <td>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                </table>

                <hr>
                <h3>Shipper Agreement Terms & Conditions</h3>
                <h4 dir="ltr">1. Exclusive Broker Arrangement</h4>
                <p dir="ltr">By signing this agreement, the Client agrees to work exclusively with the Broker for the entire shipping period. If another broker or carrier is engaged during this period, a non-refundable deposit will be retained.</p>
                <h4 dir="ltr">2. Door-to-Door Service</h4>
                <p dir="ltr">Carriers will make every effort to provide door-to-door transport. If the pickup or delivery location has access restrictions, the Client and Carrier will agree upon a nearby meeting point.</p>
                <h4 dir="ltr">3. Transport Authorization</h4>
                <p dir="ltr">The Carrier's driver is fully authorized to transport the vehicle between the pickup and delivery addresses listed. Deposits paid are final and only partially refundable under specific conditions.</p>
                <h4 dir="ltr">4. Personal Items</h4>
                <p dir="ltr">Carriers are not responsible or insured for personal items inside the vehicle. Any items must:
                <ul>
                    <li>• Weigh no more than 100 lbs</li>
                    <li>• Be secured in the trunk</li>
                    <li>• Not interfere with transport operations</li>
                </ul>
                </p>
                <h4 dir="ltr">5. Vehicle Condition</h4>
                <p dir="ltr">The vehicle must be in good running condition and contain no more than half a tank of fuel. Carriers are not responsible for damage caused by mechanical issues, leaking fluids, or improper loading.</p>
                <h4 dir="ltr">6. Insurance Coverage</h4>
                <p dir="ltr">Carriers maintain insurance coverage ranging from $100,000 to $250,000 per load. Claims for damage during transport are covered by the Carrier's insurance.</p>
                <h4 dir="ltr">7. Market Fluctuations</h4>
                <p dir="ltr">In recognition of market dynamics and evolving transport conditions, rates may be subject to adjustments of up to thirty-five percent prior to dispatch. This ensures that the service remains aligned with current demands and operational factors.</p>
                <h4 dir="ltr">8. Scheduling & Rescheduling</h4>
                <p dir="ltr">If the vehicle is not ready for pickup as scheduled, a rescheduling fee will be charged:
                <ul>
                    <li>• 2-5 days' delay: additional fee applies</li>
                </ul>
                </p>
                <h4 dir="ltr">9. Inspection Reports</h4>
                <p dir="ltr">The Client is responsible for completing an inspection report at both pickup and delivery. Failure to do so may void the ability to file a damage claim.</p>
                <h4 dir="ltr">10. Non-Operational Vehicles</h4>
                <p dir="ltr">A $150 fee applies to inoperable vehicles and will be included in the final invoice.</p>
                <h4 dir="ltr">11. Cancellation & Refunds</h4>
                <p dir="ltr">Cancellations must be made prior to driver assignment. If no driver was dispatched, a refund minus up to $250 in administrative fees will be provided.</p>
                <h4 dir="ltr">12. Payment & Chargebacks</h4>
                <p dir="ltr">The Client agrees to pay the full quoted price for transport services. No chargebacks will be pursued against the Broker or Carrier. This agreement is governed by the laws of the United States</p>
                <hr>

                <div class='signature-box'>
                    <p><strong>Customer Signature:</strong></p>
EOT;

    // Use the $signatureImgSrc if a file was successfully saved
    if (!empty($signatureImgSrc)) {
        $message .= "<img src='{$signatureImgSrc}' alt='Customer Signature' style='max-width: 300px; height: auto; border: 1px solid #ddd; background-color: #f9f9f9; padding: 5px;'/>";
    } else {
        $message .= "<p>No signature provided or failed to save signature image.</p>";
    }

    $message .= <<<EOT
                </div>

                <p style="font-size: 0.9em; color: #666; text-align: center;"><em>This agreement was submitted through the MJ Hauling website.</em></p>
            </div>
        </div>
    </body>
    </html>
EOT;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "Return-Path: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $mailSent = mail($to, $subject, $message, $headers);

    // 6. Handle the result (e.g., database logging, user feedback)
    if($mailSent) {
        // Optional: Save to database (uncomment and adjust as needed)
        // If storing the signature in DB, consider saving the *filepath* ($filepath) instead of the base64 string.
        /*
        if (isset($conn)) {
            $query = "INSERT INTO agreements (name, date, signature_filepath, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sss", $name, $date, $filepath); // Save filepath
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                error_log("Failed to prepare statement for agreement insertion: " . mysqli_error($conn));
            }
        } else {
            error_log("Database connection not available for saving agreement.");
        }
        */
        // Since we are forcing a download, we might not want an alert right after,
        // or you can adjust the message.
        // echo '<script>alert("Thank you! Your shipper agreement has been submitted successfully, and the PDF is downloading.");</script>';

    } else {
        echo '<script>alert("There was an error submitting your agreement. Please try again or contact us directly. The PDF might not have generated/downloaded.");</script>';
    }

    // No need to set client-side mailSent variable if PDF is directly downloaded.
    // If you want to show an alert *after* the download prompt, you'd need AJAX.
    // For now, the alert above covers it.
    exit(); // Important: Exit after PDF output to prevent further HTML output
}
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>MJ Hauling United LLC</title>
    <meta name="description" content="">
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad"></script>
    <style>
        /* Your existing CSS for signature pad */
        .unique-signature-pad {
            touch-action: none;
            border: 2px solid #ccc;
            border-radius: 5px;
            width: 100%;
            height: 200px;
        }
        .signature-container {
            text-align: center;
            margin: 20px 0;
        }
        .clear-btn {
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        /* Added some basic styling for labels in form */
        .unique-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include("header2.php");?>

    <main>

        <section class="page-title-area breadcrumb-spacing" data-background="assets/img/breadcrumb/breadcrumb-bg.png">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-9">
                        <div class="page-title-wrapper text-center">
                            <h5 class="page-title mb-25">Shipper Agreement</h5>
                            <div class="breadcrumb-menu">
                                <nav aria-label="Breadcrumbs" class="breadcrumb-trail breadcrumbs">
                                    <ul class="trail-items">
                                        <li class="trail-item trail-begin"><a href="index.html"><span>Home</span></a></li>
                                        <li class="trail-item trail-end"><span>Shipper Agreement</span></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="about__3 p-relative pt-120 pb-60 wow fadeInUp" data-wow-duration="1.5s"
            data-wow-delay=".3s">
            <div class="container">
                <div><center><h2>Shipper Agreement Terms & Conditions</h2></center><hr></div>

<h4 dir="ltr">1.Exclusive Broker Arrangement</h4>
<p dir="ltr">By signing this agreement, the Client agrees to work exclusively with the Broker for the entire shipping period. If another broker or carrier is engaged during this period, a non-refundable deposit will be retained.</p>
<h4 dir="ltr">2.Door-to-Door Service</h4>
<p dir="ltr">Carriers will make every effort to provide door-to-door transport. If the pickup or delivery location has access restrictions, the Client and Carrier will agree upon a nearby meeting point.</p>

<h4 dir="ltr">3.Transport Authorization</h4>
<p dir="ltr">The Carrier's driver is fully authorized to transport the vehicle between the pickup and delivery addresses listed. Deposits paid are final and only partially refundable under specific conditions.</p>

<h4 dir="ltr">4.Personal Items</h4>
<p dir="ltr">Carriers are not responsible or insured for personal items inside the vehicle. Any items must:
<ul>
<li>• Weigh no more than 100 lbs</li>
<li>• Be secured in the trunk</li>
<li>• Not interfere with transport operations</li>
</ul>
</p>

<h4 dir="ltr">5.Vehicle Condition</h4>
<p dir="ltr">The vehicle must be in good running condition and contain no more than half a tank of fuel. Carriers are not responsible for damage caused by mechanical issues, leaking fluids, or improper loading.</p>

<h4 dir="ltr">6.Insurance Coverage</h4>
<p dir="ltr">Carriers maintain insurance coverage ranging from $100,000 to $250,000 per load. Claims for damage during transport are covered by the Carrier's insurance.</p>

<h4 dir="ltr">7.Market Fluctuations</h4>
<p dir="ltr">In recognition of market dynamics and evolving transport conditions, rates may be subject to adjustments of up to thirty-five percent prior to dispatch. This ensures that the service remains aligned with current demands and operational factors.</p>

<h4 dir="ltr">8.Scheduling & Rescheduling</h4>
<p dir="ltr">If the vehicle is not ready for pickup as scheduled, a rescheduling fee will be charged:
<ul>
<li>• 2-5 days' delay: additional fee applies</li>
</ul>
</p>

<h4 dir="ltr">9.Inspection Reports</h4>
<p dir="ltr">The Client is responsible for completing an inspection report at both pickup and delivery. Failure to do so may void the ability to file a damage claim.</p>

<h4 dir="ltr">10.Non-Operational Vehicles</h4>
<p dir="ltr">A $150 fee applies to inoperable vehicles and will be included in the final invoice.</p>

<h4 dir="ltr">11.Cancellation & Refunds</h4>
<p dir="ltr">Cancellations must be made prior to driver assignment. If no driver was dispatched, a refund minus up to $250 in administrative fees will be provided.</p>

<h4 dir="ltr">12.Payment & Chargebacks</h4>
<p dir="ltr">The Client agrees to pay the full quoted price for transport services. No chargebacks will be pursued against the Broker or Carrier. This agreement is governed by the laws of the United States</p>
<hr>

                <div class="contact-form mb-60">
                    <form method="post" id="agreementForm">
                        <div class="row">
                            <div class="col-xxl-6 col-xl-6 col-lg-6">
                                <label for="f_name" class="unique-label">Full Name *</label>
                                <div class="single-input-field">
                                    <input name="f_name" type="text" required>
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="col-xxl-6 col-xl-6 col-lg-6">
                                <label for="email" class="unique-label">Date *</label>
                                <div class="single-input-field">
                                    <input name="email" type="date" required>
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>

                            <div class="col-xxl-12">
                                <div class="signature-container">
                                    <label for="unique-signature-canvas" class="unique-label">Digital Signature *</label>
                                    <canvas id="unique-signature-canvas" class="unique-signature-pad"></canvas>
                                    <button type="button" id="clear-signature" class="clear-btn">Clear Signature</button>
                                    <input type="hidden" name="signature" id="signature-data">
                                </div>
                            </div>

                            <div class="col-xxl-12 col-xl-12">
                                <button type="submit" class="fill-btn clip-btn" name="submit">Submit Agreement</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </main>
    <br><br>
    <?php include("footer.php");?>
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
    <script src="assets/js/main.js"></script>

    <script>
        // Initialize signature pad
        const canvas = document.getElementById("unique-signature-canvas");
        // Ensure the canvas context can be obtained before initializing SignaturePad
        if (canvas.getContext) {
            const signaturePad = new SignaturePad(canvas, {
                minWidth: 1,
                maxWidth: 3,
                penColor: "black",
                backgroundColor: "rgba(255,255,255,1)"
            });

            // Clear signature button
            document.getElementById("clear-signature").addEventListener("click", function() {
                signaturePad.clear();
            });

            // Make the canvas responsive
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                canvas.style.width = rect.width + 'px';
                canvas.style.height = rect.height + 'px';
                signaturePad.clear(); // Clear signature on resize to prevent distortion
            }

            // Initialize canvas
            window.addEventListener("resize", resizeCanvas);
            setTimeout(resizeCanvas, 100); // Small delay to ensure correct initial sizing
            resizeCanvas(); // Call once initially to set correct size

            // Form submission handler
            document.getElementById("agreementForm").addEventListener("submit", function(e) {
                // Check if signature is empty
                if (signaturePad.isEmpty()) {
                    alert("Please provide your signature before submitting.");
                    e.preventDefault(); // Stop form submission
                    return false; // Prevent further execution
                }

                // Set signature data to hidden input
                document.getElementById("signature-data").value = signaturePad.toDataURL();

                return true; // Allow form submission
            });
        } else {
            console.error("Canvas element not supported or context could not be obtained.");
            // Optionally, hide signature pad elements or show a fallback message
            document.querySelector('.signature-container').innerHTML = '<p>Your browser does not support the digital signature pad.</p>';
        }

        // The alert for mailSent is removed from here because the PHP script will exit
        // after generating the PDF, preventing this JavaScript from running unless
        // you implement AJAX for form submission. For direct form submission,
        // the PHP header redirect/exit handles the flow.
    </script>
</body>
</html>