<?php
session_start(); // Start the session at the very beginning

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}


// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = ''; // <--- UPDATE THIS VALUE WITH YOUR CORRECT PASSWORD


// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    redirectWithStatus('admin.php', 'error', 'You must be logged in to view this page.');
}

// Check for and display status messages from previous redirects
$status_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$status_type = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';

// --- Email Sending Logic ---
// This block must be handled first, before the main page content logic.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    // Crucial change: Explicitly check for the quote ID before proceeding.
    $quote_id_to_redirect = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
    if (!$quote_id_to_redirect) {
        // If the ID is missing from the form, we can't proceed. Redirect to admin.
        redirectWithStatus('admin.php', 'error', 'Cannot send email: Quotation ID is missing or invalid.');
    }

    $to = filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message_body'], FILTER_SANITIZE_STRING);
    $from = filter_var($_POST['from_email'], FILTER_SANITIZE_EMAIL);
    $appSource = filter_var($_POST['app_source'], FILTER_SANITIZE_STRING);
    $headers = "From: " . $from . "\r\n" .
                "Reply-To: " . $from . "\r\n" .
                "Content-Type: text/plain; charset=utf-8";
    
    if (mail($to, $subject, $message, $headers)) {
        redirectWithStatus('view_quote.php?id=' . $quote_id_to_redirect, 'success', 'Email sent successfully to ' . htmlspecialchars($to) . '!');
    } else {
        // Redirect back to the same page with an error status.
        redirectWithStatus('view_quote.php?id=' . $quote_id_to_redirect, 'error', 'Failed to send email. Please check your server configuration.');
    }
}

$quote = null;
// The key change: Check for the ID from both GET and POST requests.
// This allows the page to function correctly after the email form is submitted.
$quote_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$quote_id) {
    $quote_id = filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT);
}

// Additional check: if filter_input failed due to invalid format, try manual parsing
if (!$quote_id && isset($_GET['id'])) {
    // Extract just the numeric part from the ID parameter
    $raw_id = $_GET['id'];
    if (preg_match('/^(\d+)/', $raw_id, $matches)) {
        $quote_id = intval($matches[1]);
    }
}

// Debug: Log the quote ID to help with troubleshooting
error_log("Raw GET id: " . ($_GET['id'] ?? 'NULL'));
error_log("Quote ID from filter_input GET: " . (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 'NULL'));
error_log("Quote ID from POST: " . (filter_input(INPUT_POST, 'quote_id', FILTER_VALIDATE_INT) ?: 'NULL'));
error_log("Final Quote ID: " . ($quote_id ?: 'NULL'));

if ($quote_id && $quote_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM shipment_quote WHERE id = :id");
        $stmt->execute([':id' => $quote_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            redirectWithStatus('admin.php', 'error', 'Quotation request not found.');
        }
    } catch (PDOException $e) {
        error_log("Error fetching quotation details: " . $e->getMessage());
        redirectWithStatus('admin.php', 'error', 'Error fetching quotation details.');
    }
} else {
    // More detailed error message for debugging
    $get_id = $_GET['id'] ?? 'not set';
    $post_id = $_POST['quote_id'] ?? 'not set';
    error_log("No valid quotation ID provided. GET id: $get_id, POST quote_id: $post_id");
    redirectWithStatus('admin.php', 'error', 'No quotation ID provided. GET: ' . $get_id . ', POST: ' . $post_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quotation Request - MJ Hauling United LLC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Body and Layout */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7f9;
            padding: 0;
            margin: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 70px; /* Adjust for fixed navbar height */
            box-sizing: border-box;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* --- Navbar Styling (As Provided in quotation_requests.php) --- */
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

        @media (max-width: 768px) {
            .navbar { flex-direction: column; align-items: flex-start; padding: 15px 10px; }
            .navbar .site-title { font-size: 1.6rem; margin-bottom: 15px; width: 100%; text-align: center; margin-right: 0; }
            .navbar-links { width: 100%; justify-content: center; gap: 8px; }
            .navbar-links a { padding: 8px 12px; font-size: 0.9rem; }
            body { padding-top: 130px; }
            .dropdown { width: 100%; text-align: center; }
            .dropdown-content { width: 100%; left: 0; right: 0; top: unset; position: static; box-shadow: none; border-radius: 0; transform: translateY(0); opacity: 1; }
            .dropdown-content a { padding: 10px; border-radius: 0; }
        }
        @media (max-width: 480px) {
            .navbar-links a { padding: 6px 10px; font-size: 0.85rem; flex-grow: 1; text-align: center; }
            body { padding-top: 110px; }
        }


        /* Main Content Area */
        .main-content {
            width: 100%;
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 25px;
            box-sizing: border-box;
        }
        
        h1.page-title {
            text-align: center;
            color: #2c73d2;
            margin-bottom: 2.5rem;
            font-size: 2.8rem;
            font-weight: 700;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.08);
            letter-spacing: -0.5px;
        }
        
        /* Status Message Styling */
        .status-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            animation: slideInDown 0.5s ease-out;
        }
        .status-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* NEW: Details Layout */
        .details-container {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e0e0;
        }
        
        .quote-id-header {
            text-align: center;
            background-color: #f0f4f8;
            color: #2c73d2;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 1.6rem;
            font-weight: 600;
            border: 1px solid #d0d7e0;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .detail-group-card {
            background-color: #fcfdfe;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .detail-group-card h3 {
            color: #3f51b5;
            font-size: 1.4rem;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-group-card h3 .fas {
            font-size: 1.2rem;
            color: #6a82fb;
        }

        .detail-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
            flex-basis: 50%;
        }
        .detail-value {
            flex-basis: 50%;
            text-align: right;
            word-wrap: break-word;
            color: #333;
        }
        
        .special-instructions-card {
            background-color: #e6f3ff;
            border-left: 4px solid #2c73d2;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .special-instructions-card h3 {
            color: #2c73d2;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #b3d7ff;
            padding-bottom: 10px;
        }
        .special-instructions-card p {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }
        .action-buttons .btn {
            background: linear-gradient(to right, #007bff, #0056b3);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .action-buttons .btn:hover {
            background: linear-gradient(to right, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        .action-buttons .email-btn {
            background: linear-gradient(to right, #ffc107, #e0a800);
            color: #333;
            text-shadow: none;
        }
        .action-buttons .email-btn:hover {
            background: linear-gradient(to right, #e0a800, #c69500);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        /* Footer */
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
        
        /* Modal Styles (As Provided by you) */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            position: relative;
            width: 90%;
            max-width: 600px;
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-content h3 {
            color: #2c73d2;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8rem;
            text-align: center;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
        }
        .close-button {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close-button:hover,
        .close-button:focus {
            color: #333;
            text-decoration: none;
        }
        .modal-form-group {
            margin-bottom: 20px;
        }
        .modal-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 1.05rem;
        }
        .modal-form-group input,
        .modal-form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .modal-form-group input:focus,
        .modal-form-group textarea:focus {
            border-color: #2c73d2;
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
            outline: none;
        }
        .modal-form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        .modal-buttons button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .modal-buttons .send-btn {
            background-color: #28a745;
            color: white;
        }
        .modal-buttons .send-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        .modal-buttons .cancel-btn {
            background-color: #6c757d;
            color: white;
        }
        .modal-buttons .cancel-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <img style="width: 60px" class="img-responsive" src="assets/img/logo/logo.png" alt="Logo">
        <div class="navbar-links">
            <div class="dropdown">
                <a href="#">Leads &#9662;</a>
                <div class="dropdown-content">
                    <a href="shippment_lead.php">New Lead Form</a>
                    <a href="view_leads.php">View All Leads</a>
                </div>
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

    <div class="main-content">
        <h1 class="page-title">Quotation Request Details</h1>
        
        <?php if (!empty($status_message)): ?>
            <div class="status-message <?php echo $status_type; ?>">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($quote): ?>
            <div class="details-container">
                <div class="quote-id-header">Quotation ID: #<?php echo htmlspecialchars($quote['id']); ?></div>
                
                <div class="details-grid">
                    <div class="detail-group-card">
                        <h3><i class="fas fa-user-circle"></i> Customer Information</h3>
                        <div class="detail-item">
                            <div class="detail-label">Name:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['customer_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['customer_email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['customer_phone']); ?></div>
                        </div>
                    </div>

                    <div class="detail-group-card">
                        <h3><i class="fas fa-car"></i> Vehicle Details</h3>
                        <div class="detail-item">
                            <div class="detail-label">Make:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['vehicle_make']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Model:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['vehicle_model']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Type:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['vehicle_type']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Year:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['vehicle_year']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Transport Type:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['transport_type']); ?></div>
                        </div>
                    </div>
                
                    <div class="detail-group-card">
                        <h3><i class="fas fa-shipping-fast"></i> Pickup & Delivery Information</h3>
                        <div class="detail-item">
                            <div class="detail-label">Pickup Location:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['pickup_city']) . ', ' . htmlspecialchars($quote['pickup_state']) . ' ' . htmlspecialchars($quote['pickup_zipcode']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Delivery Location:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['delivery_city']) . ', ' . htmlspecialchars($quote['delivery_state']) . ' ' . htmlspecialchars($quote['delivery_zipcode']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Shipment Date:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['shipment_date']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Distance:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['distance']); ?> miles</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Created At:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($quote['created_at']); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($quote['special_instructions'])): ?>
                    <div class="special-instructions-card">
                        <h3>Special Instructions</h3>
                        <p><?php echo nl2br(htmlspecialchars($quote['special_instructions'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="quotation_requests.php" class="btn"><i class="fas fa-arrow-left"></i> Go Back</a>
                <button class="btn email-btn" onclick="openEmailModal()">
                    <i class="fas fa-reply"></i> Reply to Quotation
                </button>
            </div>
        <?php else: ?>
            <div class="details-container" style="text-align: center;">
                <p>No quotation request found with the provided ID.</p>
                <a href="quotation_requests.php" class="btn"><i class="fas fa-arrow-left"></i> Go Back to Quotation Requests</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>&copy; <?php echo date('Y'); ?> MJ Hauling United LLC. All rights reserved.</footer>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEmailModal()">&times;</span>
            <h3>Send Email</h3>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="send_email" value="1">
                <input type="hidden" name="quote_id" value="<?php echo htmlspecialchars($quote_id ?? ''); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($quote_id ?? ''); ?>">
                <div class="modal-form-group">
                    <label for="emailTo">To:</label>
                    <input type="email" id="emailTo" name="to_email" readonly required>
                </div>
                <div class="modal-form-group" style="display: none;">
                    <label for="emailFrom">From:</label>
                    <input type="email" id="emailFrom" name="from_email" required value="<?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'david.jason906@mjhaulingunitedllc.com'); ?>">
                </div>
                <div class="modal-form-group">
                    <label for="emailSubject">Subject:</label>
                    <input type="text" id="emailSubject" name="subject" required>
                </div>
                <div class="modal-form-group" style="display: none;">
                    <label for="emailAppSource">App/Source:</label>
                    <input type="text" id="emailAppSource" name="app_source" readonly>
                </div>
                <div class="modal-form-group">
                    <label for="emailMessage">Message:</label>
                    <textarea id="emailMessage" name="message_body" required></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i> Send</button>
                    <button type="button" class="cancel-btn" onclick="closeEmailModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEmailModal() {
            const emailModal = document.getElementById('emailModal');
            
            // Set the form values using PHP variables
            document.getElementById('emailTo').value = "<?php echo htmlspecialchars($quote['customer_email'] ?? ''); ?>";
            document.getElementById('emailSubject').value = "Regarding your shipment quote request (Ref ID: <?php echo htmlspecialchars($quote['id'] ?? ''); ?>)";
            document.getElementById('emailAppSource').value = "Quotation Request (ID: <?php echo htmlspecialchars($quote['id'] ?? ''); ?>)";
            
            // Set the message body
            const messageBody = `Dear <?php echo htmlspecialchars($quote['customer_name'] ?? ''); ?>,

Thank you for your shipment quotation request (ID: <?php echo htmlspecialchars($quote['id'] ?? ''); ?>).

Details provided:
Pickup: <?php echo htmlspecialchars($quote['pickup_city'] ?? ''); ?>, <?php echo htmlspecialchars($quote['pickup_state'] ?? ''); ?>
Delivery: <?php echo htmlspecialchars($quote['delivery_city'] ?? ''); ?>, <?php echo htmlspecialchars($quote['delivery_state'] ?? ''); ?>
Vehicle: <?php echo htmlspecialchars(($quote['vehicle_year'] ?? '') . ' ' . ($quote['vehicle_make'] ?? '') . ' ' . ($quote['vehicle_model'] ?? '')); ?>

We will review your request and get back to you shortly with a competitive quote.

Best regards,
MJ Hauling United LLC Team

---`;
            
            document.getElementById('emailMessage').value = messageBody;
            document.getElementById('emailFrom').value = "<?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'david.jason906@mjhaulingunitedllc.com'); ?>";
            
            emailModal.style.display = 'flex';
        }

        function closeEmailModal() {
            const emailModal = document.getElementById('emailModal');
            emailModal.style.display = 'none';
            // Clear form fields when closing
            document.getElementById('emailTo').value = '';
            document.getElementById('emailSubject').value = '';
            document.getElementById('emailMessage').value = '';
            document.getElementById('emailAppSource').value = '';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('emailModal');
            if (event.target == modal) {
                closeEmailModal();
            }
        }

        // Debug function to check quote ID
        function debugQuoteId() {
            const quoteId = '<?php echo htmlspecialchars($quote_id ?? 'NOT SET'); ?>';
            console.log('Quote ID: ' + quoteId);
            console.log('Form action: <?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>');
            console.log('GET parameters: ' + window.location.search);
            
            // Check if quote ID is in URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlQuoteId = urlParams.get('id');
            console.log('URL Quote ID: ' + (urlQuoteId || 'NOT SET'));
        }
        
        // Call debug function on page load
        window.onload = function() {
            debugQuoteId();
        }
    </script>
</body>
</html>