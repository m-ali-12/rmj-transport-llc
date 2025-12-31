<?php

// Check if the request method is POST and the action is set to send_email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    // Set the content type to application/json for the response
    header('Content-Type: application/json');

    // Collect and sanitize form data
    $to_emails_string = filter_input(INPUT_POST, 'to_email', FILTER_SANITIZE_STRING);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);
    $from_name = 'MJ Hauling United LLC'; // You can change this to a dynamic name if needed
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (empty($to_emails_string) || empty($subject) || empty($message_body) || empty($from_email)) {
        echo json_encode(['success' => false, 'message' => 'To, From, subject, and message are required.']);
        exit;
    }

    // Explode the string of emails into an array and sanitize each one
    $to_emails = array_map('trim', explode(',', $to_emails_string));
    $valid_emails = array_filter($to_emails, 'filter_var');
    $to_emails_clean = implode(',', $valid_emails);

    // Check if there are any valid emails
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
    exit; // Stop further execution
}
?>
