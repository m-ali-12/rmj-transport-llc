<?php
// email_helper.php

/**
 * Sends an email using PHP's built-in mail() function.
 * * @param string $to The recipient's email address.
 * @param string $subject The email subject.
 * @param string $body The email body content (can be HTML).
 * @param string $from_email The sender's email address.
 * @param string $from_name The sender's name.
 * @return bool True if the email was successfully accepted for delivery, false otherwise.
 */
function sendUniversalEmail($to, $subject, $body, $from_email, $from_name) {
    // Standard headers for HTML email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
    ];

    // Join headers with CRLF
    $header_string = implode("\r\n", $headers);

    // Send the email and return the result
    return mail($to, $subject, $body, $header_string);
}

?>