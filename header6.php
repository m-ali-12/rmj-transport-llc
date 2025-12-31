<?php
// Start a session if one hasn't already been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check current login status and get user IDs
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Get the logged in user's name for display in the logout link
$logged_in_user_name = '';
if ($is_admin_logged_in) {
    $logged_in_user_name = $_SESSION['admin_name'] ?? 'Admin';
} elseif ($is_user_logged_in) {
    $logged_in_user_name = $_SESSION['user_name'] ?? 'User';
}

// This file contains the header and navigation for the application.
// It should be included at the top of every page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your App Title</title>
    <!-- Use the external CSS file as requested -->
    <link rel="stylesheet" href="lead_styling.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
            
            <div class="navbar-links">
                <a href="sent_mail.php">View Sent Mail </a>
            </div>
            
            <?php if ($is_admin_logged_in): ?>
            <div class="navbar-links">
            
            <a href="admin.php">Dashboard </a>
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
            <?php endif; ?>
            
            <a href="admin.php?logout=true">
                <?php echo $is_admin_logged_in ? 'Admin Logout' : ($is_user_logged_in ? htmlspecialchars($logged_in_user_name) . ' Logout' : 'Logout'); ?>
            </a>
        </div>
    </div>
