<?php
session_start(); // Always start the session at the very beginning

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Database configuration (ensure these match your actual credentials)
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Set default fetch mode
} catch (PDOException $e) {
    // Log the database connection error
    error_log("Database connection failed in update_lead.php: " . $e->getMessage());
    redirectWithStatus('view_leads.php', 'error', 'Database connection failed. Please try again later.');
}

// Check for CSRF token for security
// This ensures the request originates from your form
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF token mismatch in update_lead.php. Session: " . ($_SESSION['csrf_token'] ?? 'N/A') . ", Post: " . ($_POST['csrf_token'] ?? 'N/A'));
    redirectWithStatus('view_leads.php', 'error', 'Invalid security token. Please try again (refresh the page if needed).');
}

// FIXED: Only unset CSRF token on successful update, not on validation errors
// This will be moved to after successful database update

// Collect and sanitize form data from POST request
// Use null coalescing operator (??) for robustness
$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$quote_amount = $_POST['quote_amount'] ?? 0; // Default to 0 for numeric
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
$formatted_message = $_POST['formatted_message'] ?? ''; // Added formatted_message

// Validate essential fields
// Check for valid ID and ensure required text fields are not empty
if ($id === null || !is_numeric($id) || empty($name) || empty($quote_id) || empty($quote_date)) {
    redirectWithStatus('edit_lead.php?id=' . urlencode($id), 'error', 'Missing required lead details or invalid ID for update. Please fill all compulsory fields.');
}

// Validate quote_amount specifically as a number
if (!is_numeric($quote_amount)) {
    redirectWithStatus('edit_lead.php?id=' . urlencode($id), 'error', 'Invalid Quote Amount. Please enter a valid number.');
}
$quote_amount = (float)$quote_amount; // Cast to float for database

// Prepare SQL statement for updating the lead
// Ensure all your form fields correspond to actual database columns here
$sql = "UPDATE shippment_lead SET
            name = :name,
            email = :email,
            phone = :phone,
            quote_amount = :quote_amount,
            quote_id = :quote_id,
            quote_date = :quote_date,
            shippment_date = :shippment_date,
            status = :status,
            year = :year,
            make = :make,
            model = :model,
            pickup_city = :pickup_city,
            pickup_state = :pickup_state,
            pickup_zip = :pickup_zip,
            delivery_city = :delivery_city,
            delivery_state = :delivery_state,
            delivery_zip = :delivery_zip,
            formatted_message = :formatted_message,
            updated_at = NOW()
        WHERE id = :id";

try {
    $stmt = $pdo->prepare($sql);

    // Bind parameters to the prepared statement
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
    $stmt->bindParam(':formatted_message', $formatted_message); // Bind the new field
    $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Bind the ID as integer

    $stmt->execute(); // Execute the update query

    // FIXED: Only unset CSRF token after successful database operation
    unset($_SESSION['csrf_token']);

    // Check if any rows were affected by the UPDATE query
    if ($stmt->rowCount() > 0) {
        // If one or more rows were affected, the update was successful
        redirectWithStatus('view_leads.php', 'success', 'Lead updated successfully!');
    } else {
        // If rowCount is 0, it means the query ran without error, but no changes were detected.
        // This could be because the submitted data is identical to the existing data.
        redirectWithStatus('view_leads.php', 'info', 'Lead data submitted, but no changes detected for Lead ID: ' . htmlspecialchars($id));
    }

} catch (PDOException $e) {
    // Catch any database-related errors during execution
    error_log("Error updating lead ID " . ($id ?? 'N/A') . ": " . $e->getMessage());
    // FIXED: Don't unset CSRF token on database errors, so user can retry
    // Redirect back to the edit page with the specific error message
    redirectWithStatus('edit_lead.php?id=' . urlencode($id), 'error', 'Error updating lead: ' . $e->getMessage());
}
?>