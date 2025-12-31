<?php
session_start();

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

/**
 * Function to send email using native PHP mail() function
 */
function sendEmail($to_email, $subject, $message_body, $from_email = null, $from_name = null) {
    // Set default values if not provided
    $from_email = $from_email ?: 'info@rmjtransportllc.moversloader.com';
    $from_name = $from_name ?: 'RMJ Transport LLC';

    // Prepare headers
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: {$from_name} <{$from_email}>";
    $headers[] = "Reply-To: {$from_email}";
    $headers[] = "X-Mailer: PHP/" . phpversion();

    // Convert message to HTML format
    $html_message = nl2br(htmlspecialchars($message_body, ENT_QUOTES, 'UTF-8'));

    // Send email using native mail function
    $result = mail($to_email, $subject, $html_message, implode("\r\n", $headers));

    if ($result) {
        return ['success' => true, 'message' => 'Email sent successfully!'];
    } else {
        error_log("Native Mail Error: Failed to send email to {$to_email}");
        return ['success' => false, 'message' => "Email could not be sent. Please try again later."];
    }
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
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Optional: default fetch mode
} catch (PDOException $e) {
    redirectWithStatus('shippment_lead.php', 'error', 'Database connection failed: ' . $e->getMessage());
}

// Check for CSRF token for security
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Optionally regenerate token or just deny
    redirectWithStatus('shippment_lead.php', 'error', 'Invalid CSRF token. Please try again.');
}

// FIXED: Don't unset the token yet - wait until successful save
// This allows user to retry if there are validation errors

// Get user ID from the submitted form data
$user_id = $_POST['user_id'] ?? null;

// Basic validation for user_id (optional, but good for data integrity)
if ($user_id === null || !is_numeric($user_id)) {
    // This should ideally not happen if your session management is robust,
    // but it's a fallback. Log this for debugging.
    error_log("Attempt to save lead without valid user_id. Session user_id: " . ($_SESSION['user_id'] ?? 'N/A'));
    redirectWithStatus('shippment_lead.php', 'error', 'User not identified. Please log in again.');
}

// Collect and sanitize form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$quote_amount = $_POST['quote_amount'] ?? 0;
$quote_id = $_POST['quote_id'] ?? '';
$quote_date = $_POST['quote_date'] ?? null;
$shippment_date = $_POST['shippment_date'] ?? null;
$status = $_POST['status'] ?? '';
$year = $_POST['year'] ?? '';
$make = $_POST['make'] ?? '';
$model = $_POST['model'] ?? '';
$pickup_city = $_POST['pickup_city'] ?? '';
$pickup_state = $_POST['pickup_state'] ?? '';
$pickup_zip = $_POST['pickup_zip'] ?? '';
$delivery_city = $_POST['delivery_city'] ?? '';
$delivery_state = $_POST['delivery_state'] ?? '';
$delivery_zip = $_POST['delivery_zip'] ?? '';
$formatted_message = $_POST['formatted_message'] ?? '';

// Validate compulsory fields again on the server-side
if (empty($name) || empty($quote_amount) || empty($quote_id) || $quote_id === 'N/A' || empty($quote_date)) {
    redirectWithStatus('shippment_lead.php', 'error', 'Missing required lead details (Name, Quote Amount, Quote ID, Quote Date).');
}

// Ensure quote_amount is a valid number
if (!is_numeric($quote_amount)) {
    redirectWithStatus('shippment_lead.php', 'error', 'Invalid Quote Amount.');
}
$quote_amount = (float)$quote_amount;

$sqlck = "SELECT * FROM shippment_lead 
        WHERE email = :email 
        OR phone = :phone";
$stmt = $pdo->prepare($sqlck);

// Bind parameters
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':phone', $phone, PDO::PARAM_STR);

// Execute query
$stmt->execute();

// Fetch results
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) > 0) {
    redirectWithStatus('shippment_lead.php', 'success', 'Duplicate Lead!' . $email_status);
} else {

// Prepare SQL statement for insertion
// IMPORTANT: Make sure the 'user_id' column exists in your 'shippment_lead' table
$sql = "INSERT INTO shippment_lead (
            name, email, phone, quote_amount, quote_id, quote_date, shippment_date, status,
            year, make, model, pickup_city, pickup_state, pickup_zip,
            delivery_city, delivery_state, delivery_zip, formatted_message, user_id, created_at
        ) VALUES (
            :name, :email, :phone, :quote_amount, :quote_id, :quote_date, :shippment_date, :status,
            :year, :make, :model, :pickup_city, :pickup_state, :pickup_zip,
            :delivery_city, :delivery_state, :delivery_zip, :formatted_message, :user_id, NOW()
        )";

try {
    $stmt = $pdo->prepare($sql);
    // Bind parameters
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':quote_amount', $quote_amount);
    $stmt->bindParam(':quote_id', $quote_id);
    $stmt->bindParam(':quote_date', $quote_date);
    $stmt->bindParam(':shippment_date', $shippment_date);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':make', $make);
    $stmt->bindParam(':model', $model);
    $stmt->bindParam(':pickup_city', $pickup_city);
    $stmt->bindParam(':pickup_state', $pickup_state);
    $stmt->bindParam(':pickup_zip', $pickup_zip);
    $stmt->bindParam(':delivery_city', $delivery_city);
    $stmt->bindParam(':delivery_state', $delivery_state);
    $stmt->bindParam(':delivery_zip', $delivery_zip);
    $stmt->bindParam(':formatted_message', $formatted_message);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Bind user_id as integer

    $stmt->execute();

    // FIXED: Only unset CSRF token after successful database save
    unset($_SESSION['csrf_token']);

    // Send email to customer if email address is provided
    $email_status = '';
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Get sender email based on logged-in user
        $from_email = 'info@rmjtransportllc.moversloader.com';
        $from_name = 'RMJ Transport LLC';

        // Check if admin or user is logged in and get their email
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['admin_email'])) {
            $from_email = $_SESSION['admin_email'];
            $from_name = ($_SESSION['admin_name'] ?? 'Admin') . ' - MJ Hauling United LLC';
        } elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && isset($_SESSION['user_email'])) {
            $from_email = $_SESSION['user_email'];
            $from_name = ($_SESSION['user_name'] ?? 'User') . ' - MJ Hauling United LLC';
        }

        // Prepare email subject
        $email_subject = "[RMJ Transport] Quote for {$name} - {$quote_id}";

        // Send the formatted message as email body
        $email_result = sendEmail($email, $email_subject, $formatted_message, $from_email, $from_name);

        if ($email_result['success']) {
            $email_status = ' Email sent to customer successfully!';
        } else {
            $email_status = ' (Note: Email could not be sent to customer)';
            error_log("Failed to send email to customer {$email}: " . $email_result['message']);
        }
    } else {
        $email_status = ' (No email sent - customer email not provided or invalid)';
    }

    redirectWithStatus('shippment_lead.php', 'success', 'Lead saved successfully!' . $email_status);

} catch (PDOException $e) {
    // Log the error in a real application
    error_log("Error saving lead: " . $e->getMessage());
    // FIXED: Don't unset CSRF token on database errors, so user can retry
    redirectWithStatus('shippment_lead.php', 'error', 'Error saving lead: ' . $e->getMessage());
}
}
?>