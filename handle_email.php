<?php
// Email handling functions for MJ Hauling system

/**
 * Enhanced email sending function with better error handling
 */
function sendEmail($to, $subject, $message, $headers = '', $from_name = 'MJ Hauling') {
    // Validate email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        logEmailActivity('send_failed', "Invalid email address: $to");
        return false;
    }

    // Add default headers if not provided
    if (empty($headers)) {
        $headers = "From: $from_name <noreply@mjhaulingunited.com>\r\n";
        $headers .= "Reply-To: info@mjhaulingunited.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }

    // Attempt to send email
    $result = mail($to, $subject, $message, $headers);

    if ($result) {
        logEmailActivity('send_success', "Email sent to: $to, Subject: $subject");
    } else {
        logEmailActivity('send_failed', "Failed to send email to: $to, Subject: $subject");
    }

    return $result;
}

/**
 * Log email activities with timestamp
 */
function logEmailActivity($action, $details) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] Email Activity: $action - $details";
    error_log($log_message);
}

/**
 * Validate email configuration
 */
function checkEmailConfiguration() {
    $config = [
        'mail_function_exists' => function_exists('mail'),
        'sendmail_path' => ini_get('sendmail_path'),
        'smtp_server' => ini_get('SMTP'),
        'smtp_port' => ini_get('smtp_port')
    ];

    logEmailActivity('config_check', json_encode($config));
    return $config;
}

/**
 * Send bulk emails with better error handling
 */
function sendBulkEmails($emails, $subject, $message, $from_email, $from_name = 'MJ Hauling') {
    $results = [
        'success' => [],
        'failed' => [],
        'invalid' => []
    ];

    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    foreach ($emails as $email) {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $results['invalid'][] = $email;
            continue;
        }

        if (mail($email, $subject, $message, $headers)) {
            $results['success'][] = $email;
        } else {
            $results['failed'][] = $email;
        }
    }

    logEmailActivity('bulk_send', sprintf(
        "Sent: %d, Failed: %d, Invalid: %d",
        count($results['success']),
        count($results['failed']),
        count($results['invalid'])
    ));

    return $results;
}
?>
