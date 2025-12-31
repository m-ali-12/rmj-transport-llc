<?php
session_start(); // Start the session at the very beginning

// Redirect function (reused from other pages)
function redirectWithStatus($page, $status, $message) {
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    redirectWithStatus('admin.php', 'error', 'You must be logged in as an administrator to access this page.');
}

// Check for and handle logout request
if (isset($_GET['logout'])) {
    session_destroy();
    redirectWithStatus('admin.php', 'success', 'You have been logged out successfully.');
}

// Database configuration
$db_host = 'localhost';
$db_name = ''; // Your database name
$db_user = ''; // Your database user
$db_pass = ''; // Your database password



// --- EMAIL SENDING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    // This block handles the AJAX email submission
    header('Content-Type: application/json');

    // Get a comma-separated string of emails or a single email
    $to_emails_string = filter_input(INPUT_POST, 'to_email', FILTER_SANITIZE_STRING);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);
    $from_name = 'RMJ Transport LLC';
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_STRING);

    if (empty($to_emails_string) || empty($subject) || empty($message_body) || empty($from_email)) {
        echo json_encode(['success' => false, 'message' => 'To, From, subject, and message are required.']);
        exit;
    }

    // Explode the string of emails into an array and sanitize each one
    $to_emails = array_map('trim', explode(',', $to_emails_string));
    $valid_emails = array_filter($to_emails, 'filter_var');
    $to_emails_clean = implode(',', $valid_emails);

    if (empty($to_emails_clean)) {
        echo json_encode(['success' => false, 'message' => 'Invalid recipient email addresses.']);
        exit;
    }

    // Prepare headers for the native mail() function
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    // Use the native mail() function to send the email
    if (mail($to_emails_clean, $subject, $message_body, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Check your server\'s mail configuration.']);
    }
    exit; // Stop further execution after sending the JSON response
}


// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- FETCH QUOTATION DATA ---
$quotations = [];
$status_type = '';
$status_message = '';
try {
    // Corrected SQL query to only select columns for the main table view
    $stmt_quotations = $pdo->query("SELECT id, customer_name, customer_email, customer_phone, shipment_date, created_at FROM shipment_quote ORDER BY created_at DESC");
    $quotations = $stmt_quotations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // This will now display the exact database error for further debugging.
    error_log("Error fetching quotation data: " . $e->getMessage());
    $status_type = 'error';
    $status_message = 'Failed to load quotation requests. Error: ' . $e->getMessage();
}

// Get and display status messages if redirected here
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
    <title>Quotation Requests - Admin Dashboard</title>
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
            padding-top: 70px;
            box-sizing: border-box;
            overflow-x: hidden;
            line-height: 1.6;
        }
        /* --- Navbar Styles (Enhanced) --- */
        .navbar {
            background: #2c73d2;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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

        /* --- Navbar Responsive Adjustments (Refined) --- */
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

        /* --- Main Content Container --- */
        .main-content {
            width: 100%;
            max-width: 1400px;
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
        .bulk-actions {
            margin-bottom: -50px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .bulk-actions .action-btn {
            padding: 10px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }
        .bulk-actions .action-btn:hover {
            background-color: #218838;
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
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* --- Table Styles (General) --- */
        .table-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .table-section h2 {
            margin-top: 0;
            margin-bottom: 25px;
            text-align: left;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
            max-height: 700px;
            overflow-y: auto;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            min-width: 800px;
        }
        .data-table th, .data-table td {
            border: 1px solid #e6e6e6;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.05);
        }
        .data-table tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        .data-table tr:hover {
            background-color: #eef7ff;
        }
        .data-table td.actions {
            white-space: nowrap;
            text-align: center;
        }
        .data-table .action-btn {
            padding: 8px 12px;
            margin: 2px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .data-table .email-btn { background-color: #ffc107; color: #333; }
        .data-table .email-btn:hover { background-color: #e0a800; }
        .data-table .view-btn { background-color: #007bff; color: white; }
        .data-table .view-btn:hover { background-color: #0069d9; }

        /* Special Instructions Column Truncation */
        .instructions-column {
            max-width: 250px; /* Adjust as needed */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer; /* Indicate it's clickable for view modal */
        }

        /* Footer Styles */
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: auto;
            width: 100%;
            box-sizing: border-box;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s forwards;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            animation: slideIn 0.3s forwards;
        }
        .modal-content h3 {
            margin-top: 0;
            color: #3f51b5;
            text-align: center;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
        }
        .close-button:hover, .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-form .form-group {
            margin-bottom: 20px;
        }
        .modal-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .modal-form input[type="text"],
        .modal-form input[type="email"],
        .modal-form textarea,
        .modal-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .modal-form textarea {
            min-height: 150px;
        }
        .modal-form .submit-btn {
            display: block;
            width: 100%;
            background-color: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .modal-form .submit-btn:hover {
            background-color: #218838;
        }
        #instructionsDisplayContent {
            white-space: pre-wrap;
            word-wrap: break-word;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }


        .to-from-inline{
            width: 100%;
            display: flex;
        }
        #form-group{
            width: 290px;
        }
        #form-group2{
            width: 290px;
            margin-left: 20px;
        }



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
            /* Optional: Add a gradient for more appeal */
            background: linear-gradient(to right, #2c73d2, #4a90e2);
        }
        .navbar .site-title {
            font-size: 1.8rem; /* Slightly larger */
            font-weight: 700;
            color: white;
            letter-spacing: 0.8px; /* More prominent spacing */
            flex-shrink: 0;
            margin-right: 25px; /* Increased margin */
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2); /* Subtle text shadow */
        }
        .navbar-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
            gap: 15px; /* Consistent spacing */
            margin: 0; /* Reset default margin */
            padding: 0; /* Reset default padding */
            list-style: none; /* Remove bullet points if it were a ul */
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px; /* Slightly more rounded */
            transition: all 0.3s ease; /* Smooth transition for all properties */
            font-weight: 500;
            white-space: nowrap;
            background-color: rgba(255, 255, 255, 0.1); /* Subtle background for links */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Light shadow for links */
        }
        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.25); /* More visible hover */
            transform: translateY(-2px) scale(1.02); /* Lift and slightly enlarge on hover */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover */
        }
        /* Dropdown common styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #2c73d2; /* Same as navbar for consistency */
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.25);
            z-index: 1;
            border-radius: 8px;
            top: calc(100% + 8px); /* Position slightly below the main link */
            left: 0;
            overflow: hidden; /* For rounded corners */
            opacity: 0; /* Start hidden for animation */
            transform: translateY(-10px); /* Start slightly above for animation */
            transition: opacity 0.2s ease-out, transform 0.2s ease-out; /* Smooth animation */
        }
        .dropdown-content a {
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            display: block;
            text-align: left;
            border-radius: 0; /* No individual rounded corners for dropdown items */
            transition: background-color 0.3s ease;
            box-shadow: none; /* Remove individual link shadow */
        }
        .dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.25); /* Lighter hover for dropdown */
            transform: none; /* No transform on dropdown items */
            box-shadow: none;
        }
        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1; /* Fade in */
            transform: translateY(0); /* Move to final position */
        }

        /* --- Navbar Responsive Adjustments (Refined) --- */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start; /* Align items to the start */
                padding: 15px 10px;
            }
            .navbar .site-title {
                font-size: 1.6rem; /* Adjusted for smaller screens */
                margin-bottom: 15px; /* More space below title */
                width: 100%;
                text-align: center;
                margin-right: 0;
            }
            .navbar-links {
                width: 100%;
                justify-content: center; /* Center links when wrapped */
                gap: 8px; /* Reduced gap for more compact layout */
            }
            .navbar-links a {
                padding: 8px 12px;
                font-size: 0.9rem; /* Smaller font size */
            }
            body {
                padding-top: 130px; /* Adjust body padding to accommodate expanded navbar */
            }
            .dropdown {
                width: 100%; /* Make dropdown take full width on small screens */
                text-align: center;
            }
            .dropdown-content {
                width: 100%;
                left: 0;
                right: 0;
                top: unset; /* Remove fixed top */
                position: static; /* Allow dropdown to flow in line */
                box-shadow: none;
                border-radius: 0;
                transform: translateY(0); /* No initial transform for mobile dropdown */
                opacity: 1; /* Always visible on mobile if parent dropdown is shown */
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
                flex-grow: 1; /* Allow links to grow to fill space */
                text-align: center;
            }
            body {
                padding-top: 110px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <img style="width: 60px" class="img-responsive" src="assets/img/logo/logo.png" alt="Logo">
        <div class="navbar-links">
            <div class="navbar-links">
                <a href="admin.php">Dashboard </a>
                </div>

            
            <div class="dropdown">
                <a href="#">Leads &#9662;</a>
                <div class="dropdown-content">
                    <a href="shippment_lead.php">New Lead Form</a>
                    <a href="view_leads.php">View All Leads</a>
                </div>
            </div>
    <div class="navbar-links">
                <a href="sent_mail.php">View Sent Mail </a>
                </div>
            
            <div class="dropdown">
                <a href="#">More Tools &#9662;</a>
                <div class="dropdown-content">
                    <a href="agreement.php">Agreement Page</a>
                    <a href="contact_messages.php">Contact Messages</a>
                    <a href="quotation_requests.php">Quotation Requests</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#">Account &#9662;</a>
                <div class="dropdown-content">
                    <a href="user_login.php">User Login</a>
                    <a style="display: none;" href="admin_users.php">Admin Profile</a>
                    <a href="admin_users.php">Manage Admin Users</a>
                    <a href="local_users.php">Manage Local Users</a>
                </div>
            </div>
            
            <a href="admin.php?logout=true">Admin Logout</a>
        </div>
    </div>

    <main class="main-content">

        <?php if (!empty($status_message)): ?>
            <div class="status-message <?php echo $status_type; ?>">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <div class="table-section">
            <div class="bulk-actions">
                <button class="action-btn" onclick="openBulkEmailModal()">Reply to Selected</button>
            </div>
            <h2>All Quotation Requests</h2>
            <?php if (!empty($quotations)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Shipment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotations as $quote): ?>
                                <tr>
                                    <td><input type="checkbox" name="selectedEmails[]" class="email-checkbox" value="<?php echo htmlspecialchars($quote['customer_email']); ?>"></td>
                                    <td><?php echo htmlspecialchars($quote['id']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['customer_email']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['customer_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['shipment_date']); ?></td>
                                    <td class="actions">
                                        <a href="view_quote.php?id=<?php echo htmlspecialchars($quote['id']); ?>" class="action-btn view-btn">View</a>
                                        <button class="action-btn email-btn" onclick="openSingleEmailModal('<?php echo htmlspecialchars($quote['customer_email']); ?>', 'Quotation Request')">Reply</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No quotation requests found.</p>
            <?php endif; ?>
        </div>
    </main>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEmailModal()">&times;</span>
            <h3>Send Reply Email</h3>
            <form id="emailForm" class="modal-form" method="POST" onsubmit="handleEmailSubmit(event)">
                
                <div class="to-from-inline">
                <div class="form-group" id="form-group">
                    <label for="to_email">To:</label>
                    <input type="text" id="to_email" name="to_email" readonly required>
                </div>
                <div class="form-group" id="form-group2">
                    <label for="from_email">From:</label>
                    <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'david.jason906@mjhaulingunitedllc.com'); ?>" readonly required>
                </div>
                </div>

                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message_body">Message:</label>
                    <textarea id="message_body" name="message_body" required></textarea>
                </div>
                <input type="hidden" name="source_page" value="quotation_requests.php">
                <input type="hidden" name="action" value="send_email">
                <button type="submit" name="send_email" class="submit-btn">Send Email</button>
            </form>
        </div>
    </div>

    <div id="viewInstructionsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeViewInstructionsModal()">&times;</span>
            <h3>Special Instructions</h3>
            <div id="instructionsDisplayContent"></div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> RMJ Transport LLC All Rights Reserved.</p>
    </footer>

    <script>
        const emailModal = document.getElementById('emailModal');
        const viewInstructionsModal = document.getElementById('viewInstructionsModal');
        const instructionsDisplayContent = document.getElementById('instructionsDisplayContent');
        const toEmailInput = document.getElementById('to_email');
        const subjectInput = document.getElementById('subject');
        const emailForm = document.getElementById('emailForm');
        const selectAllCheckbox = document.getElementById('selectAll');
        const emailCheckboxes = document.querySelectorAll('.email-checkbox');

        // Function to open the email modal for a single email
        function openSingleEmailModal(recipientEmail, messageType) {
            toEmailInput.value = recipientEmail;
            subjectInput.value = `Re: Your ${messageType} from  RMJ Transport LLC `;
            emailModal.style.display = 'flex';
        }

        // Function to open the email modal for multiple emails
        function openBulkEmailModal() {
            const selectedEmails = Array.from(emailCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);

            if (selectedEmails.length === 0) {
                alert('Please select at least one customer to reply to.');
                return;
            }

            toEmailInput.value = selectedEmails.join(', ');
            subjectInput.value = 'Reply from RMJ Transport LLC ';
            emailModal.style.display = 'flex';
        }

        function closeEmailModal() {
            emailModal.style.display = 'none';
        }

        function viewInstructions(instructionsContent) {
            instructionsDisplayContent.textContent = instructionsContent;
            viewInstructionsModal.style.display = 'flex';
        }

        function closeViewInstructionsModal() {
            viewInstructionsModal.style.display = 'none';
        }

        // Handle AJAX form submission
        function handleEmailSubmit(event) {
            event.preventDefault();

            const formData = new FormData(emailForm);
            const submitBtn = emailForm.querySelector('.submit-btn');
            const originalBtnText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            fetch('quotation_requests.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const statusMessageDiv = document.createElement('div');
                statusMessageDiv.className = 'status-message ' + (data.success ? 'success' : 'error');
                statusMessageDiv.textContent = data.message;
                
                const mainContent = document.querySelector('.main-content');
                const oldStatusMessage = mainContent.querySelector('.status-message');
                if (oldStatusMessage) {
                    oldStatusMessage.remove();
                }
                mainContent.insertBefore(statusMessageDiv, mainContent.firstChild.nextSibling);
                
                if (data.success) {
                    closeEmailModal();
                    emailForm.reset();
                }
            })
            .catch(error => {
                const statusMessageDiv = document.createElement('div');
                statusMessageDiv.className = 'status-message error';
                statusMessageDiv.textContent = 'An unexpected error occurred. Please try again.';
                
                const mainContent = document.querySelector('.main-content');
                const oldStatusMessage = mainContent.querySelector('.status-message');
                if (oldStatusMessage) {
                    oldStatusMessage.remove();
                }
                mainContent.insertBefore(statusMessageDiv, mainContent.firstChild.nextSibling);

                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        }

        // Add event listeners for bulk selection
        selectAllCheckbox.addEventListener('change', () => {
            emailCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });

        // Close modals if clicked outside
        window.onclick = function(event) {
            if (event.target == emailModal) {
                closeEmailModal();
            }
            if (event.target == viewInstructionsModal) {
                closeViewInstructionsModal();
            }
        }
    </script>
</body>
</html>