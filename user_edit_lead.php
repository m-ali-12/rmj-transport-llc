<?php
ob_start(); // Start output buffering
session_start();

// Redirect function for consistency
function redirectWithStatus($page, $status, $message) {
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Check if user is logged in as a local user, otherwise redirect
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['logged_in_type'] !== 'user') {
    redirectWithStatus('user_login.php', 'error', 'You must be logged in as a local user to access this page.');
}

// Check for session timeout (e.g., 30 minutes inactivity)
$session_timeout = 1800; // 30 minutes (30 * 60 seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    redirectWithStatus('user_login.php', 'error', 'Your session has expired due to inactivity. Please log in again.');
}
$_SESSION['last_activity'] = time(); // Update last activity time

// Database configuration
$db_host = 'localhost';
$db_name = ''; // Your database name
$db_user = ''; // Your database user
$db_pass = ''; // Your database password

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed in user_edit_lead.php: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

$lead = null;
$lead_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$user_id = $_SESSION['user_id']; // Logged-in local user ID

$status_message = '';
$status_type = '';

// Handle form submission for updating lead
if (isset($_POST['update_lead'])) {
    $lead_id = filter_input(INPUT_POST, 'lead_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate lead_id and ensure it's assigned to the current user
    if (!$lead_id || $lead_id <= 0) {
        redirectWithStatus('view_leads.php', 'error', 'Invalid lead ID for update.');
    }

    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $pickup_location = trim(filter_input(INPUT_POST, 'pickup_location', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $destination = trim(filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $car_type = trim(filter_input(INPUT_POST, 'car_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $quote_amount = filter_input(INPUT_POST, 'quote_amount', FILTER_VALIDATE_FLOAT);
    $shippment_date = trim(filter_input(INPUT_POST, 'shippment_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $quote_date = trim(filter_input(INPUT_POST, 'quote_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $status = trim(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $broker_fee = filter_input(INPUT_POST, 'broker_fee', FILTER_VALIDATE_FLOAT);
    $note = trim(filter_input(INPUT_POST, 'note', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $source = trim(filter_input(INPUT_POST, 'source', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $customer_name = trim(filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $customer_phone = trim(filter_input(INPUT_POST, 'customer_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $customer_email = trim(filter_input(INPUT_POST, 'customer_email', FILTER_SANITIZE_EMAIL));
    $vehicle_year_make_model = trim(filter_input(INPUT_POST, 'vehicle_year_make_model', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $carrier_name = trim(filter_input(INPUT_POST, 'carrier_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $carrier_phone = trim(filter_input(INPUT_POST, 'carrier_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $pickup_city_state = trim(filter_input(INPUT_POST, 'pickup_city_state', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $delivery_city_state = trim(filter_input(INPUT_POST, 'delivery_city_state', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $initial_price = filter_input(INPUT_POST, 'initial_price', FILTER_VALIDATE_FLOAT);
    $total_payout_to_carrier = filter_input(INPUT_POST, 'total_payout_to_carrier', FILTER_VALIDATE_FLOAT);


    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($status)) {
        $status_type = 'error';
        $status_message = 'Name, email, phone, and status are required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE shippment_lead SET 
                                    name = :name, 
                                    email = :email, 
                                    phone = :phone, 
                                    pickup_location = :pickup_location, 
                                    destination = :destination, 
                                    car_type = :car_type, 
                                    quote_amount = :quote_amount, 
                                    shippment_date = :shippment_date, 
                                    quote_date = :quote_date, 
                                    status = :status, 
                                    broker_fee = :broker_fee, 
                                    note = :note,
                                    source = :source,
                                    customer_name = :customer_name,
                                    customer_phone = :customer_phone,
                                    customer_email = :customer_email,
                                    vehicle_year_make_model = :vehicle_year_make_model,
                                    carrier_name = :carrier_name,
                                    carrier_phone = :carrier_phone,
                                    pickup_city_state = :pickup_city_state,
                                    delivery_city_state = :delivery_city_state,
                                    initial_price = :initial_price,
                                    total_payout_to_carrier = :total_payout_to_carrier
                                WHERE id = :id AND local_user_id = :user_id");
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':pickup_location' => $pickup_location,
                ':destination' => $destination,
                ':car_type' => $car_type,
                ':quote_amount' => $quote_amount,
                ':shippment_date' => $shippment_date,
                ':quote_date' => $quote_date,
                ':status' => $status,
                ':broker_fee' => $broker_fee,
                ':note' => $note,
                ':source' => $source,
                ':customer_name' => $customer_name,
                ':customer_phone' => $customer_phone,
                ':customer_email' => $customer_email,
                ':vehicle_year_make_model' => $vehicle_year_make_model,
                ':carrier_name' => $carrier_name,
                ':carrier_phone' => $carrier_phone,
                ':pickup_city_state' => $pickup_city_state,
                ':delivery_city_state' => $delivery_city_state,
                ':initial_price' => $initial_price,
                ':total_payout_to_carrier' => $total_payout_to_carrier,
                ':id' => $lead_id,
                ':user_id' => $user_id // Crucial: Ensure lead belongs to the user
            ]);

            if ($stmt->rowCount() > 0) {
                redirectWithStatus('view_leads.php', 'success', 'Lead ID ' . $lead_id . ' updated successfully.');
            } else {
                // This means no rows were affected. Could be invalid ID or ID not assigned to user.
                $status_type = 'error';
                $status_message = 'Failed to update lead. Lead ID ' . $lead_id . ' not found or not assigned to your account.';
            }

        } catch (PDOException $e) {
            error_log("Lead update error for user " . $user_id . ", Lead ID " . $lead_id . ": " . $e->getMessage());
            $status_type = 'error';
            $status_message = "An error occurred while updating the lead. Please try again.";
        }
    }
}

// Handle email sending
if (isset($_POST['send_email'])) {
    $lead_id = filter_input(INPUT_POST, 'lead_id', FILTER_SANITIZE_NUMBER_INT);
    $recipient_email = filter_input(INPUT_POST, 'recipient_email', FILTER_SANITIZE_EMAIL);
    $email_subject = trim(filter_input(INPUT_POST, 'email_subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $email_body = trim($_POST['email_body']); // HTML content, don't sanitize with full_special_chars

    // Fetch lead details again to confirm ownership and get name/other info for email
    try {
        $stmt_fetch_lead = $pdo->prepare("SELECT name, email FROM shippment_lead WHERE id = :id AND local_user_id = :user_id");
        $stmt_fetch_lead->execute([':id' => $lead_id, ':user_id' => $user_id]);
        $lead_for_email = $stmt_fetch_lead->fetch(PDO::FETCH_ASSOC);

        if (!$lead_for_email) {
            redirectWithStatus('user_edit_lead.php?id=' . $lead_id, 'error', 'Lead not found or not assigned to your account for email sending.');
        }

        if (empty($recipient_email) || empty($email_subject) || empty($email_body)) {
            $status_type = 'error';
            $status_message = 'Recipient email, subject, and message body are required for sending.';
        } elseif (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            $status_type = 'error';
            $status_message = 'Invalid recipient email format.';
        } else {
            // Get logged-in user's email for "From" header (if you want to send from their email)
            $user_email = $_SESSION['user_email'] ?? 'noreply@yourdomain.com';
            $user_name = $_SESSION['user_name'] ?? 'MJ Hauling United LLC';

            $headers = "From: " . $user_name . " <" . $user_email . ">\r\n";
            $headers .= "Reply-To: " . $user_email . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // Attempt to send email
            if (mail($recipient_email, $email_subject, $email_body, $headers)) {
                // Update last_email_sent_date in database
                $stmt_update_email_date = $pdo->prepare("UPDATE shippment_lead SET last_email_sent_date = NOW() WHERE id = :id AND local_user_id = :user_id");
                $stmt_update_email_date->execute([':id' => $lead_id, ':user_id' => $user_id]);
                
                redirectWithStatus('user_edit_lead.php?id=' . $lead_id, 'success', 'Email sent successfully to ' . htmlspecialchars($recipient_email) . ' and lead updated!');
            } else {
                $status_type = 'error';
                $status_message = 'Failed to send email. Check your server\'s mail configuration.';
            }
        }
    } catch (PDOException $e) {
        error_log("Email send DB error for user " . $user_id . ", Lead ID " . $lead_id . ": " . $e->getMessage());
        $status_type = 'error';
        $status_message = "An error occurred during email sending. Please try again.";
    }
}


// Fetch lead data for display (after any updates/emails)
if ($lead_id) {
    try {
        $stmt = $pdo->prepare("SELECT *,
                                    DATE_FORMAT(shippment_lead.shippment_date, '%Y-%m-%d') AS formatted_shippment_date,
                                    DATE_FORMAT(shippment_lead.quote_date, '%Y-%m-%d') AS formatted_quote_date,
                                    DATE_FORMAT(shippment_lead.created_at, '%Y-%m-%d %H:%i:%s') AS formatted_created_at,
                                    DATE_FORMAT(shippment_lead.last_email_sent_date, '%Y-%m-%d %H:%i:%s') AS formatted_last_email_sent_date
                                FROM shippment_lead 
                                WHERE id = :id AND local_user_id = :user_id"); // Crucial check for user ownership
        $stmt->execute([':id' => $lead_id, ':user_id' => $user_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lead) {
            redirectWithStatus('view_leads.php', 'error', 'Lead not found or not assigned to your account.');
        }

    } catch (PDOException $e) {
        error_log("Lead fetch error for user " . $user_id . ", Lead ID " . $lead_id . ": " . $e->getMessage());
        redirectWithStatus('view_leads.php', 'error', 'Error fetching lead details. Please try again.');
    }
} else {
    redirectWithStatus('view_leads.php', 'error', 'No lead ID provided.');
}

// Handle status messages from redirects
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status_type = htmlspecialchars($_GET['status']);
    $status_message = htmlspecialchars(urldecode($_GET['message']));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lead #<?php echo htmlspecialchars($lead['id'] ?? 'N/A'); ?> - MJ Hauling United LLC</title>
    <style>
        /* General Body and Container Styles */
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 0;
            margin: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
            box-sizing: border-box;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* --- Navbar Styles (Reused from Admin Dashboard) --- */
        .navbar {
            background: #2c73d2; /* Primary blue */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box;
            background: linear-gradient(to right, #2c73d2, #4a90e2);
        }
        .navbar .site-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            letter-spacing: 0.8px;
            flex-shrink: 0;
            margin-right: 25px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        .navbar-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            white-space: nowrap;
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #2c73d2;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.25);
            z-index: 1;
            border-radius: 8px;
            top: calc(100% + 8px);
            left: 0;
            overflow: hidden;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }
        .dropdown-content a {
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            display: block;
            text-align: left;
            border-radius: 0;
            transition: background-color 0.3s ease;
            box-shadow: none;
        }
        .dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: none;
            box-shadow: none;
        }
        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* --- Navbar Responsive Adjustments --- */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 10px;
            }
            .navbar .site-title {
                font-size: 1.6rem;
                margin-bottom: 15px;
                width: 100%;
                text-align: center;
                margin-right: 0;
            }
            .navbar-links {
                width: 100%;
                justify-content: center;
                gap: 8px;
            }
            .navbar-links a {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            body {
                padding-top: 130px;
            }
            .dropdown {
                width: 100%;
                text-align: center;
            }
            .dropdown-content {
                width: 100%;
                left: 0;
                right: 0;
                top: unset;
                position: static;
                box-shadow: none;
                border-radius: 0;
                transform: translateY(0);
                opacity: 1;
            }
            .dropdown-content a {
                padding: 10px;
                border-radius: 0;
            }
        }
        @media (max-width: 480px) {
            .navbar-links a {
                padding: 6px 10px;
                font-size: 0.85rem;
                flex-grow: 1;
                text-align: center;
            }
            body {
                padding-top: 110px;
            }
        }

        /* --- Main Content --- */
        .main-content {
            width: 100%;
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 25px;
            box-sizing: border-box;
        }
        h1 {
            text-align: center;
            color: #2c73d2;
            margin-bottom: 2.5rem;
            font-size: 2.8rem;
            font-weight: 700;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.08);
            letter-spacing: -0.5px;
        }
        h2 {
            font-size: 2rem;
            color: #3f51b5;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        h3 {
            font-size: 1.5rem;
            color: #555;
            margin-top: 30px;
            margin-bottom: 20px;
            font-weight: 600;
            border-left: 5px solid #2c73d2;
            padding-left: 10px;
        }

        /* --- Status Message --- */
        .status-message {
            text-align: center;
            padding: 15px;
            margin: 20px auto;
            border-radius: 10px;
            font-weight: bold;
            max-width: 800px;
            display: block;
            animation: fadeIn 0.5s forwards;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 8px solid;
        }
        .status-message:empty {
            display: none;
        }
        .success {
            background-color: #e6ffed;
            color: #1a6d2f;
            border-color: #28a745;
        }
        .error {
            background-color: #ffe6e6;
            color: #a71a2b;
            border-color: #dc3545;
        }

        /* --- Form Sections --- */
        .form-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .form-group {
            margin-bottom: 0px; /* Adjust as grid handles spacing */
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.95rem;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #c2dcfc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2c73d2;
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
            outline: none;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .button-group {
            text-align: center;
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .button-group button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .button-group button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .button-group button:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .button-group .cancel-btn {
            background-color: #6c757d;
        }
        .button-group .cancel-btn:hover {
            background-color: #5a6268;
        }

        /* Email Section Specific Styles */
        #emailSection {
            margin-top: 40px;
        }
        #emailSection h3 {
            border-left-color: #ffc107;
        }
        #emailSection button {
            background-color: #ffc107;
            color: #333;
        }
        #emailSection button:hover {
            background-color: #e0a800;
        }
        #emailSection .form-group input[type="email"],
        #emailSection .form-group input[type="text"],
        #emailSection .form-group textarea {
            background-color: #fff;
        }

        /* --- Footer --- */
        footer {
            text-align: center;
            margin-top: auto;
            padding: 30px;
            font-weight: 600;
            color: #888;
            font-size: 0.9rem;
            background-color: #eef2f7;
            border-top: 1px solid #e0e0e0;
        }

        /* --- Scroll-to-top Button Styles --- */
        #scrollToTopBtn {
            display: none;
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            border: none;
            outline: none;
            background-color: #2c73d2;
            color: white;
            cursor: pointer;
            padding: 15px 20px;
            border-radius: 50%;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }
        #scrollToTopBtn:hover {
            background-color: #1a5ea6;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Adjustments (General) */
        @media (max-width: 768px) {
            .main-content { padding: 0 15px; }
            h1 { font-size: 2.2rem; margin-bottom: 2rem;}
            h2 { font-size: 1.8rem; }
            h3 { font-size: 1.3rem; }
            .form-grid { grid-template-columns: 1fr; } /* Stack columns on small screens */
            .button-group { flex-direction: column; }
            .button-group button { width: 100%; }
        }
        @media (max-width: 480px) {
            .main-content { padding: 0 10px; }
            h1 { font-size: 1.8rem; }
            h2 { font-size: 1.5rem; }
            .form-section { padding: 20px; }
        }
    </style>
    <script>
        window.onload = function() {
            // Display status messages
            const statusDiv = document.getElementById('editLeadStatusMessage');
            if (statusDiv && statusDiv.textContent.trim() !== '') {
                setTimeout(() => statusDiv.textContent = '', 5000); // Clear message after 5 seconds
            }

            // Scroll-to-top button logic
            let mybutton = document.getElementById("scrollToTopBtn");
            window.onscroll = function() { scrollFunction() };
            function scrollFunction() {
                if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                    mybutton.style.display = "block";
                } else {
                    mybutton.style.display = "none";
                }
            }
            mybutton.addEventListener('click', topFunction);
            function topFunction() {
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;
            }
        };
    </script>
</head>
<body>
    <div class="navbar">
        <span class="site-title">MJ Hauling United LLC</span>
        <div class="navbar-links">
            <a href="view_leads.php">My Leads</a>
            <div class="dropdown">
                <a href="#">Account &#9662;</a>
                <div class="dropdown-content">
                    <a href="user_profile.php">My Profile</a> <a href="user_login.php?logout=true">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <h1>Edit Lead #<?php echo htmlspecialchars($lead['id'] ?? 'N/A'); ?></h1>

        <div id="editLeadStatusMessage" class="status-message <?php echo $status_type; ?>">
            <?php echo $status_message; ?>
        </div>

        <?php if ($lead): ?>
            <div class="form-section">
                <h3>Lead Details</h3>
                <form action="user_edit_lead.php?id=<?php echo htmlspecialchars($lead['id']); ?>" method="POST">
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($lead['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($lead['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($lead['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="pickup_location">Pickup Location:</label>
                            <input type="text" id="pickup_location" name="pickup_location" value="<?php echo htmlspecialchars($lead['pickup_location']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="destination">Destination:</label>
                            <input type="text" id="destination" name="destination" value="<?php echo htmlspecialchars($lead['destination']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="car_type">Car Type:</label>
                            <input type="text" id="car_type" name="car_type" value="<?php echo htmlspecialchars($lead['car_type']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="quote_amount">Quote Amount:</label>
                            <input type="number" step="0.01" id="quote_amount" name="quote_amount" value="<?php echo htmlspecialchars($lead['quote_amount']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="shippment_date">Shipment Date:</label>
                            <input type="date" id="shippment_date" name="shippment_date" value="<?php echo htmlspecialchars($lead['formatted_shippment_date']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="quote_date">Quote Date:</label>
                            <input type="date" id="quote_date" name="quote_date" value="<?php echo htmlspecialchars($lead['formatted_quote_date']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <?php
                                $statuses = ['New Lead', 'Booked', 'In Transit', 'Delivered', 'Cancelled', 'Not Pick', 'Voice Mail', 'In Future Shipment', 'Qutation', 'Invalid Lead', 'Stop Lead', 'On Hold', 'Already Booked'];
                                foreach ($statuses as $s) {
                                    $selected = ($s == $lead['status']) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($s) . "\" " . $selected . ">" . htmlspecialchars($s) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="broker_fee">Broker Fee:</label>
                            <input type="number" step="0.01" id="broker_fee" name="broker_fee" value="<?php echo htmlspecialchars($lead['broker_fee']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="source">Source:</label>
                            <input type="text" id="source" name="source" value="<?php echo htmlspecialchars($lead['source']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="customer_name">Customer Name:</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($lead['customer_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="customer_phone">Customer Phone:</label>
                            <input type="tel" id="customer_phone" name="customer_phone" value="<?php echo htmlspecialchars($lead['customer_phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="customer_email">Customer Email:</label>
                            <input type="email" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($lead['customer_email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="vehicle_year_make_model">Vehicle (Year, Make, Model):</label>
                            <input type="text" id="vehicle_year_make_model" name="vehicle_year_make_model" value="<?php echo htmlspecialchars($lead['vehicle_year_make_model']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="carrier_name">Carrier Name:</label>
                            <input type="text" id="carrier_name" name="carrier_name" value="<?php echo htmlspecialchars($lead['carrier_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="carrier_phone">Carrier Phone:</label>
                            <input type="tel" id="carrier_phone" name="carrier_phone" value="<?php echo htmlspecialchars($lead['carrier_phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="pickup_city_state">Pickup City, State:</label>
                            <input type="text" id="pickup_city_state" name="pickup_city_state" value="<?php echo htmlspecialchars($lead['pickup_city_state']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="delivery_city_state">Delivery City, State:</label>
                            <input type="text" id="delivery_city_state" name="delivery_city_state" value="<?php echo htmlspecialchars($lead['delivery_city_state']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="initial_price">Initial Price:</label>
                            <input type="number" step="0.01" id="initial_price" name="initial_price" value="<?php echo htmlspecialchars($lead['initial_price']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="total_payout_to_carrier">Total Payout to Carrier:</label>
                            <input type="number" step="0.01" id="total_payout_to_carrier" name="total_payout_to_carrier" value="<?php echo htmlspecialchars($lead['total_payout_to_carrier']); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="note">Note:</label>
                            <textarea id="note" name="note"><?php echo htmlspecialchars($lead['note']); ?></textarea>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="update_lead">Update Lead</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='view_leads.php';">Back to My Leads</button>
                    </div>
                </form>
            </div>

            <div class="form-section" id="emailSection">
                <h3>Send Email to Lead</h3>
                <form action="user_edit_lead.php?id=<?php echo htmlspecialchars($lead['id']); ?>" method="POST">
                    <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead['id']); ?>">
                    
                    <div class="form-group">
                        <label for="recipient_email">Recipient Email:</label>
                        <input type="email" id="recipient_email" name="recipient_email" value="<?php echo htmlspecialchars($lead['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email_subject">Subject:</label>
                        <input type="text" id="email_subject" name="email_subject" value="Regarding your shipment inquiry from MJ Hauling United LLC" required>
                    </div>
                    <div class="form-group">
                        <label for="email_body">Message Body (HTML allowed):</label>
                        <textarea id="email_body" name="email_body" rows="10" required>
                            <p>Dear <?php echo htmlspecialchars($lead['name']); ?>,</p>
                            <p>Thank you for contacting MJ Hauling United LLC regarding your shipment from <?php echo htmlspecialchars($lead['pickup_location']); ?> to <?php echo htmlspecialchars($lead['destination']); ?> for your <?php echo htmlspecialchars($lead['car_type']); ?>.</p>
                            <p>Your quote amount is: $<?php echo htmlspecialchars(number_format($lead['quote_amount'], 2)); ?></p>
                            <p>We are ready to assist you with your auto transport needs. Please feel free to reply to this email or call us at <?php echo htmlspecialchars($_SESSION['user_phone'] ?? 'YOUR_COMPANY_PHONE_HERE'); ?> if you have any questions or are ready to proceed.</p>
                            <p>Best Regards,</p>
                            <p><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'MJ Hauling United LLC Team'); ?></p>
                        </textarea>
                    </div>
                    <div class="button-group">
                        <button type="submit" name="send_email">Send Email</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <footer>Powered by Desired Technologies</footer>
    <button id="scrollToTopBtn" title="Go to top">&#9650;</button>
</body>
</html>