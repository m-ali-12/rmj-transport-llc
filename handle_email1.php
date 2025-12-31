<?php
// This file handles the logic for sending and logging emails.
// It should be included at the top of your view_leads.php file.

// Redirect function for clean redirects
// This function is assumed to be defined in view_leads.php
if (!function_exists('redirectWithStatus')) {
    function redirectWithStatus($page, $status, $message) {
        header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
        exit();
    }
}


// Database connection details for logging sent mail
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and create table if it doesn't exist
if ($conn->connect_error) {
    // If connection fails, log it and redirect with a critical error
    error_log("CRITICAL: Database connection failed: " . $conn->connect_error);
    redirectWithStatus('view_leads.php', 'error', 'Server error: Failed to connect to the database.');
}

// Check if sent_emails table exists, and create it if not
$table_check_sql = "SHOW TABLES LIKE 'sent_emails'";
$result = $conn->query($table_check_sql);

if ($result->num_rows == 0) {
    $create_table_sql = "CREATE TABLE sent_emails (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        from_email VARCHAR(255) NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message_body TEXT NOT NULL,
        sent_at TIMESTAMP
    )";
    if ($conn->query($create_table_sql) === TRUE) {
        error_log("Table 'sent_emails' created successfully.");
    } else {
        error_log("Error creating table 'sent_emails': " . $conn->error);
        // On table creation failure, we can still proceed but without logging.
    }
}

// Handle single email sending request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithStatus('view_leads.php', 'error', 'Invalid security token. Please try again.');
    }

    $to_email = filter_input(INPUT_POST, 'to_email', FILTER_SANITIZE_EMAIL);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validation
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message_body) || empty($from_email)) {
        redirectWithStatus('view_leads.php', 'error', 'Error: Valid recipient, from email, subject, and message are required.');
    }

    // Set "From" and "Reply-To" headers
    $from_name = $logged_in_name; // Use logged-in user's name from the main file
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Prepare subject with a prefix
    $full_subject = "[MJ Hauling] " . $subject;

    if (mail($to_email, $full_subject, $message_body, $headers)) {
        // Mail sent successfully, now insert into the database
        $stmt = $conn->prepare("INSERT INTO sent_emails (from_email, to_email, subject, message_body) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
             error_log("Failed to prepare single email statement: " . $conn->error);
             redirectWithStatus('view_leads.php', 'warning', 'Email sent, but failed to log to database due to a server error: ' . $conn->error);
        } else {
            $stmt->bind_param("ssss", $from_email, $to_email, $full_subject, $message_body);
            if ($stmt->execute()) {
                redirectWithStatus('view_leads.php', 'success', 'Email sent and logged successfully!');
            } else {
                $error = $stmt->error;
                error_log("Database insert failed for single email: " . $error);
                redirectWithStatus('view_leads.php', 'warning', 'Email sent, but failed to log to database. DB Error: ' . $error);
            }
            $stmt->close();
        }
    } else {
        redirectWithStatus('view_leads.php', 'error', 'Email could not be sent. Please check your server configuration.');
    }
    // Close connection after single email handling
    if ($conn) $conn->close();
}

// Handle multiple email sending request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email_multiple'])) {
    $to_emails = $_POST['selected_emails'] ?? [];
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);

    if (empty($to_emails) || empty($subject) || empty($message_body) || empty($from_email)) {
        redirectWithStatus('view_leads.php', 'error', 'Error: Please select at least one recipient and provide a subject, message, and a "from" email address.');
    }

    $success_count = 0;
    $error_emails = [];
    $log_errors = [];

    $from_name = $logged_in_name;
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $stmt = $conn->prepare("INSERT INTO sent_emails (from_email, to_email, subject, message_body) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        error_log("Failed to prepare bulk email statement: " . $conn->error);
        redirectWithStatus('view_leads.php', 'error', 'Server error. Failed to log emails. DB Error: ' . $conn->error);
    } else {
        foreach ($to_emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $full_subject = "[MJ Hauling] " . $subject;
                if (mail($email, $full_subject, $message_body, $headers)) {
                    $success_count++;
                    // Log to the database
                    $stmt->bind_param("ssss", $from_email, $email, $full_subject, $message_body);
                    if (!$stmt->execute()) {
                        $log_errors[] = $email . ' (' . $stmt->error . ')';
                    }
                } else {
                    $error_emails[] = $email;
                }
            }
        }
        $stmt->close();
    }

    // Close the connection after all operations
    if ($conn) $conn->close();

    if ($success_count > 0) {
        $message = "Successfully sent email to $success_count recipient(s).";
        if (!empty($log_errors)) {
            $message .= " However, failed to log emails for: " . implode(', ', $log_errors) . ".";
        }
        if (!empty($error_emails)) {
            $message .= " Failed to send email to: " . implode(', ', $error_emails) . ".";
        }
        redirectWithStatus('view_leads.php', 'success', $message);
    } else {
        redirectWithStatus('view_leads.php', 'error', "Failed to send email to all selected recipients.");
    }
}
