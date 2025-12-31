<?php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailAsUser($to_email, $subject, $message_body, $user_id) {
    global $pdo, $email_config;
    
    try {
        // Get user details from database
        $stmt = $pdo->prepare("SELECT id, name, email FROM local_users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
    } catch (PDOException $e) {
        error_log("Database error fetching user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $email_config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_config['smtp_username'];
        $mail->Password   = $email_config['smtp_password'];
        $mail->SMTPSecure = $email_config['smtp_secure'];
        $mail->Port       = $email_config['smtp_port'];
        
        // Recipients
        $mail->setFrom($user['email'], $user['name']);
        $mail->addAddress($to_email);
        $mail->addReplyTo($user['email'], $user['name']);
        
        // Content
        $mail->isHTML($email_config['use_html']);
        $mail->Subject = $subject;
        
        if ($email_config['use_html']) {
            $mail->Body    = nl2br(htmlspecialchars($message_body));
            $mail->AltBody = strip_tags($message_body);
        } else {
            $mail->Body = $message_body;
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully!'];
        
    } catch (Exception $e) {
        error_log("Email error from user {$user['id']}: " . $mail->ErrorInfo);
        return [
            'success' => false, 
            'message' => "Email could not be sent. Please try again later."
        ];
    }
}
?>