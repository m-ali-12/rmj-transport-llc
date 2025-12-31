<?php
// email_config.php

// SMTP server settings for Gmail
// Note: You must use an "App Password" for your Gmail account, not your regular password.
$smtp_host = 'smtp.gmail.com';
$smtp_username = 'REPLACE WITH THE GIVEN GMAIL BY THE SERVER';
$smtp_password = 'your_16_digit_app_password'; // **REQUIRED: Replace with your Gmail App Password**
$smtp_port = 465;
$smtp_security = 'ssl';

$from_email = $smtp_username;
$from_name = 'RMJ Transport LLC';

// Path to PHPMailer files
$phpmailer_path = 'phpmailer/src/';
?>