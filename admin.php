<?php
session_start(); // Start the session at the very beginning

// Redirect function (reused from other pages)
function redirectWithStatus($page, $status, $message) {
    // Before redirect, close and save session data to prevent race conditions
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}
// Database configuration
$db_host = 'localhost';
$db_name = ''; // Your database name
$db_user = ''; // Your database user
$db_pass = ''; // Your database password

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle Logout for Admin Users
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    // Only process logout if it's an admin session
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        session_unset();   // Unset all session variables
        session_destroy(); // Destroy the session
        redirectWithStatus('admin.php', 'success', 'You have been logged out.');
    } else {
        // If not an admin session or no session, just redirect
        header('Location: admin.php');
        exit();
    }
}

$login_error = ''; // Variable to store login error messages

// Handle Admin Login Attempt
if (isset($_POST['login'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW); // Get raw password for MD5 hashing

    if (empty($username) || empty($password)) {
        $login_error = "Username and password are required.";
    } else {
        $hashed_password_input = md5($password);

        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username AND password = :password");
            $stmt->execute([':username' => $username, ':password' => $hashed_password_input]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_status'] = $admin['status'];
                $_SESSION['admin_no'] = $admin['phone'];
                // --- START: LOGIN ACTIVITY LOGGING SNIPPET FOR ADMIN ---
                try {
                    $stmt_log = $pdo->prepare("INSERT INTO user_login_activity (user_id, user_type, ip_address, session_id, user_agent) VALUES (:user_id, :user_type, :ip_address, :session_id, :user_agent)");
                    $stmt_log->execute([
                        ':user_id' => $_SESSION['admin_id'],
                        ':user_type' => 'admin',
                        ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                        ':session_id' => session_id(),
                        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                } catch (PDOException $e) {
                    // Log the error but don't prevent login. This is for debugging.
                    error_log("Error recording admin login activity: " . $e->getMessage());
                }
                // --- END: LOGIN ACTIVITY LOGGING SNIPPET FOR ADMIN ---

                redirectWithStatus('admin.php', 'success', 'Welcome, ' . htmlspecialchars($admin['name']) . '!');
            } else {
                $login_error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            $login_error = "An error occurred during login. Please try again.";
        }
    }
}

// --- Display Login Page if NOT authenticated (Admin Only) ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - MJ Hauling United LLC</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #e0f2f7 0%, #c1e7ed 100%);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                color: #333;
                box-sizing: border-box;
                overflow: hidden;
            }
            .login-container {
                background: #fff;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
                text-align: center;
                box-sizing: border-box;
            }
            .login-container h1 {
                color: #2c73d2;
                margin-bottom: 25px;
                font-size: 2.2rem;
            }
            .form-group {
                margin-bottom: 20px;
                text-align: left;
                position: relative;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #555;
            }
            .form-group input[type="text"],
            .form-group input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #c2dcfc;
                border-radius: 8px;
                font-size: 1rem;
                box-sizing: border-box;
                transition: border-color 0.3s ease, box-shadow 0.3s ease;
            }
            .form-group input:focus {
                border-color: #2c73d2;
                box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
                outline: none;
            }
            .password-toggle {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                cursor: pointer;
                color: #888;
                font-size: 0.9em;
                user-select: none;
            }
            .login-button {
                background-color: #28a745;
                color: white;
                padding: 12px 25px;
                border: none;
                border-radius: 8px;
                font-size: 1.1rem;
                cursor: pointer;
                transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
                width: 100%;
                font-weight: 600;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .login-button:hover {
                background-color: #218838;
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            }
            .login-button:active {
                transform: translateY(0);
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .error-message {
                color: #dc3545;
                margin-top: -10px;
                margin-bottom: 20px;
                font-size: 0.9em;
            }
            .status-message {
                text-align: center;
                padding: 12px;
                margin-top: 20px;
                border-radius: 8px;
                font-weight: bold;
                width: 100%;
                max-width: 400px;
                box-sizing: border-box;
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
        </style>
        <script>
            function togglePasswordVisibility() {
                const passwordField = document.getElementById('password');
                const toggleBtn = document.getElementById('passwordToggle');
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleBtn.textContent = 'Hide';
                } else {
                    passwordField.type = 'password';
                    toggleBtn.textContent = 'Show';
                }
            }

            window.onload = function() {
                const urlParams = new URLSearchParams(window.location.search);
                const status = urlParams.get('status');
                const message = urlParams.get('message');
                if (status && message) {
                    const statusDiv = document.createElement('div');
                    statusDiv.className = `status-message ${status}`;
                    statusDiv.textContent = decodeURIComponent(message);
                    document.body.insertBefore(statusDiv, document.querySelector('.login-container'));
                    setTimeout(() => statusDiv.remove(), 5000);
                }
            };
        </script>
    </head>
    <body>
        <div class="login-container">
            <h1>Admin Login</h1>
            <?php if (!empty($login_error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <span class="password-toggle" id="passwordToggle" onclick="togglePasswordVisibility()">Show</span>
                </div>
                <button type="submit" name="login" class="login-button">Login</button>
            </form>
            <p style="margin-top: 20px; font-size: 0.9em;"><a href="user_login.php" style="color: #2c73d2; text-decoration: none;">User Login</a></p>
        </div>
    </body>
    </html>
    <?php
    exit(); // Stop execution here if not logged in as admin. Everything below is dashboard content.
}

// --- Admin Dashboard Content (only visible if logged in as Admin) ---

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Load email configuration
$email_config = require 'email_config.php';

// Adjust the path below to where you placed the PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * Function to send email using PHPMailer with proper configuration
 */
function sendEmail($to_email, $subject, $message_body, $from_email = null, $from_name = null) {
    global $email_config;

    $mail = new PHPMailer(true);

    try {
        // Use config file settings or defaults
        $smtp_from_email = $from_email ?: $email_config['from_email'];
        $smtp_from_name = $from_name ?: $email_config['from_name'];

        // Server settings
        if ($email_config['debug_mode']) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }

        $mail->isSMTP();
        $mail->Host       = $email_config['smtp_host'];
        $mail->SMTPAuth   = $email_config['smtp_auth'];
        $mail->Username   = $email_config['smtp_username'];
        $mail->Password   = $email_config['smtp_password'];
        $mail->SMTPSecure = $email_config['smtp_secure'];
        $mail->Port       = $email_config['smtp_port'];
        $mail->CharSet    = $email_config['charset'];

        // Recipients
        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($to_email);
        $mail->addReplyTo($smtp_from_email, $smtp_from_name);

        // Content
        $mail->isHTML($email_config['use_html']);
        $mail->Subject = $subject;

        if ($email_config['use_html']) {
            $mail->Body = nl2br(htmlspecialchars($message_body));
            $mail->AltBody = htmlspecialchars($message_body);
        } else {
            $mail->Body = htmlspecialchars($message_body);
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully!'];

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}

// Handle incoming email send request (from the modal form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $to_email = filter_input(INPUT_POST, 'to_email', FILTER_SANITIZE_EMAIL);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $app_source = filter_input(INPUT_POST, 'app_source', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validation
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        redirectWithStatus('admin.php', 'error', 'Error: Valid recipient email is required.');
    }

    if (empty($subject) || empty($message_body)) {
        redirectWithStatus('admin.php', 'error', 'Error: Subject and message are required.');
    }

    // Append app source to the subject for context if provided
    $full_subject = "[MJ Hauling Inquiry] " . $subject;
    if (!empty($app_source)) {
        $full_subject .= " (via " . $app_source . ")";
    }

    // Send email using the function
    $result = sendEmail($to_email, $full_subject, $message_body, $from_email);

    if ($result['success']) {
        redirectWithStatus('admin.php', 'success', 'Email sent successfully to ' . htmlspecialchars($to_email) . '!');
    } else {
        redirectWithStatus('admin.php', 'error', $result['message']);
    }
}
// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle Logout for Admin Users
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    // Only process logout if it's an admin session
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        session_unset();   // Unset all session variables
        session_destroy(); // Destroy the session
        redirectWithStatus('admin.php', 'success', 'You have been successfully logged out.');
    }
}

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    redirectWithStatus('login.php', 'error', 'You must be logged in as an administrator to access this page.');
}

// Duplicate PHPMailer code removed - using single implementation above


// Handle Add New Admin User Attempt
$add_admin_status_message = '';
$add_admin_status_type = '';

if (isset($_POST['add_admin_user'])) {
    $new_name = filter_input(INPUT_POST, 'new_name', FILTER_SANITIZE_STRING);
    $new_email = filter_input(INPUT_POST, 'new_email', FILTER_SANITIZE_EMAIL);
    $new_phone = filter_input(INPUT_POST, 'new_phone', FILTER_SANITIZE_STRING);
    $new_username = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_UNSAFE_RAW); // Raw for MD5
    $new_status = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);

    if (empty($new_name) || empty($new_email) || empty($new_username) || empty($new_password)) {
        $add_admin_status_type = 'error';
        $add_admin_status_message = 'Name, Email, Username, and Password are required for new admin.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $add_admin_status_type = 'error';
        $add_admin_status_message = 'Invalid email format for new admin.';
    } else {
        $hashed_new_password = md5($new_password);

        try {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = :username OR email = :email");
            $stmt_check->execute([':username' => $new_username, ':email' => $new_email]);
            if ($stmt_check->fetchColumn() > 0) {
                $add_admin_status_type = 'error';
                $add_admin_status_message = 'Username or Email already exists for an admin user.';
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO admin_users (name, email, phone, username, password, status) VALUES (:name, :email, :phone, :username, :password, :status)");
                $stmt_insert->execute([
                    ':name' => $new_name,
                    ':email' => $new_email,
                    ':phone' => $new_phone,
                    ':username' => $new_username,
                    ':password' => $hashed_new_password,
                    ':status' => $new_status
                ]);
                $add_admin_status_type = 'success';
                $add_admin_status_message = 'New admin user "' . htmlspecialchars($new_username) . '" added successfully!';
            }
        } catch (PDOException $e) {
            error_log("Add admin user error: " . $e->getMessage());
            $add_admin_status_type = 'error';
            $add_admin_status_message = "Database error adding new admin. Please try again.";
        }
    }
}

// Handle Add New Local User Attempt
$add_local_user_status_message = '';
$add_local_user_status_type = '';

if (isset($_POST['add_local_user'])) {
    $new_local_name = filter_input(INPUT_POST, 'new_local_name', FILTER_SANITIZE_STRING);
    $new_local_email = filter_input(INPUT_POST, 'new_local_email', FILTER_SANITIZE_EMAIL);
    $new_local_phone = filter_input(INPUT_POST, 'new_local_phone', FILTER_SANITIZE_STRING);
    $new_local_username = filter_input(INPUT_POST, 'new_local_username', FILTER_SANITIZE_STRING);
    $new_local_password = filter_input(INPUT_POST, 'new_local_password', FILTER_UNSAFE_RAW); // Raw for MD5
    $new_local_status = filter_input(INPUT_POST, 'new_local_status', FILTER_SANITIZE_STRING);

    if (empty($new_local_name) || empty($new_local_email) || empty($new_local_username) || empty($new_local_password)) {
        $add_local_user_status_type = 'error';
        $add_local_user_status_message = 'Name, Email, Username, and Password are required for new local user.';
    } elseif (!filter_var($new_local_email, FILTER_VALIDATE_EMAIL)) {
        $add_local_user_status_type = 'error';
        $add_local_user_status_message = 'Invalid email format for new local user.';
    } else {
        $hashed_new_local_password = md5($new_local_password);

        try {
            // Check if username or email already exists in local_users
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM local_users WHERE username = :username OR email = :email");
            $stmt_check->execute([':username' => $new_local_username, ':email' => $new_local_email]);
            if ($stmt_check->fetchColumn() > 0) {
                $add_local_user_status_type = 'error';
                $add_local_user_status_message = 'Username or Email already exists for a local user.';
            } else {
                // Insert new local user
                $stmt_insert = $pdo->prepare("INSERT INTO local_users (name, email, phone, username, password, status) VALUES (:name, :email, :phone, :username, :password, :status)");
                $stmt_insert->execute([
                    ':name' => $new_local_name,
                    ':email' => $new_local_email,
                    ':phone' => $new_local_phone,
                    ':username' => $new_local_username,
                    ':password' => $hashed_new_local_password,
                    ':status' => $new_local_status
                ]);
                $add_local_user_status_type = 'success';
                $add_local_user_status_message = 'New local user "' . htmlspecialchars($new_local_username) . '" added successfully!';
            }
        } catch (PDOException $e) {
            error_log("Add local user error: " . $e->getMessage());
            $add_local_user_status_type = 'error';
            $add_local_user_status_message = "Database error adding new local user. Please try again.";
        }
    }
}


// Handle success/error messages for dashboard actions (e.g., from edit_lead.php, delete_lead.php)
$dashboard_status_message = '';
$dashboard_status_type = '';
if (isset($_GET['status']) && isset($_GET['message'])) {
    $dashboard_status_type = htmlspecialchars($_GET['status']);
    $dashboard_status_message = htmlspecialchars(urldecode($_GET['message']));
}

// Fetch all leads data for the dashboard table and cards
$leads = [];
$total_leads = 0;
$booked_leads = 0;
$in_transit_leads = 0;
$cancelled_leads = 0;
$on_hold_leads = 0;
$not_pick_leads = 0;
$voice_mail_leads = 0;
$in_future_leads = 0;
$qutation_leads = 0;
$invalid_leads = 0;
$stop_leads = 0;
$delivered_leads = 0;
$already_booked_leads = 0;


try {
    $stmt = $pdo->query("SELECT *,
                                DATE_FORMAT(shippment_lead.shippment_date, '%Y-%m-%d') AS formatted_shippment_date,
                                DATE_FORMAT(shippment_lead.quote_date, '%Y-%m-%d') AS formatted_quote_date,
                                DATE_FORMAT(shippment_lead.created_at, '%Y-%m-%d %H:%i:%s') AS formatted_created_at
                         FROM shippment_lead ORDER BY created_at DESC, id DESC");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_leads = count($leads);

    // Calculate status counts for dashboard overview cards
    foreach ($leads as $lead) {
        switch ($lead['status']) {
            case 'Booked':
                $booked_leads++;
                break;
            case 'In Transit':
                $in_transit_leads++;
                break;
            case 'Delivered':
                $delivered_leads++;
                break;
            case 'Cancelled':
                $cancelled_leads++;
                break;
            case 'Not Pick':
                $not_pick_leads++;
                break;
            case 'Voice Mail':
                $voice_mail_leads++;
                break;
            case 'In Future Shipment':
                $in_future_leads++;
                break;
            case 'Qutation':
                $qutation_leads++;
                break;
            case 'Invalid Lead':
                $invalid_leads++;
                break;
            case 'Stop Lead':
                $stop_leads++;
                break;
            case 'On Hold':
                $on_hold_leads++;
                break;
            case 'Already Booked':
                $already_booked_leads++;
                break;
            // Add more cases for other statuses if needed for display
        }
    }

} catch (PDOException $e) {
    die("Error fetching leads: " . $e->getMessage());
}

// Fetch all admin users for the admin management table
$admin_users = [];
try {
    $stmt_admin = $pdo->query("SELECT id, name, email, phone, username, status FROM admin_users ORDER BY username ASC");
    $admin_users = $stmt_admin->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching admin users: " . $e->getMessage());
}

// Fetch all local users for the local user management table
$local_users = [];
try {
    $stmt_local_users = $pdo->query("SELECT id, name, email, phone, username, status FROM local_users ORDER BY username ASC");
    $local_users = $stmt_local_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching local users: " . $e->getMessage());
}


// --- FETCH CONTACT DATA ---
$contacts = [];
try {
    $stmt_contacts = $pdo->query("SELECT id, f_name, l_name, email, mob_no, msg, status FROM contact ORDER BY id DESC");
    $contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching contact data: " . $e->getMessage());
    // Optionally set a status message for the dashboard
    $dashboard_status_type = 'error';
    $dashboard_status_message = 'Failed to load contact messages.';
}

// --- FETCH QUOTATION DATA ---
$quotations = [];
try {
    // This corrected query selects all the necessary individual fields.
    $stmt_quotations = $pdo->query("SELECT id, customer_name, customer_email, customer_phone, pickup_city, pickup_state, delivery_city, delivery_state, vehicle_make, vehicle_model, vehicle_type, transport_type, shipment_date, distance, special_instructions, created_at FROM shipment_quote ORDER BY created_at DESC");
    $quotations = $stmt_quotations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching quotation data: " . $e->getMessage());
    // This is the error message that appears on the dashboard
    $status_type = 'error';
    $status_message = 'Failed to load quotation requests.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MJ Hauling United LLC</title>
    <style>
        /* General Body and Container Styles */
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5; /* Light grey background */
            padding: 0;
            margin: 0;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
            box-sizing: border-box;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* --- Navbar Styles (Enhanced) --- */
        .navbar {
            background: #2c73d2; /* Primary blue */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box;
            /* Optional: Add a gradient for more appeal */
            background: linear-gradient(to right, #2c73d2, #4a90e2);
        }
        .navbar .site-title {
            font-size: 1.8rem; /* Slightly larger */
            font-weight: 700;
            color: white;
            letter-spacing: 0.8px; /* More prominent spacing */
            flex-shrink: 0;
            margin-right: 25px; /* Increased margin */
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2); /* Subtle text shadow */
        }
        .navbar-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
            gap: 15px; /* Consistent spacing */
            margin: 0; /* Reset default margin */
            padding: 0; /* Reset default padding */
            list-style: none; /* Remove bullet points if it were a ul */
        }
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px; /* Slightly more rounded */
            transition: all 0.3s ease; /* Smooth transition for all properties */
            font-weight: 500;
            white-space: nowrap;
            background-color: rgba(255, 255, 255, 0.1); /* Subtle background for links */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Light shadow for links */
        }
        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.25); /* More visible hover */
            transform: translateY(-2px) scale(1.02); /* Lift and slightly enlarge on hover */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover */
        }
        /* Dropdown common styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #2c73d2; /* Same as navbar for consistency */
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.25);
            z-index: 1;
            border-radius: 8px;
            top: calc(100% + 8px); /* Position slightly below the main link */
            left: 0;
            overflow: hidden; /* For rounded corners */
            opacity: 0; /* Start hidden for animation */
            transform: translateY(-10px); /* Start slightly above for animation */
            transition: opacity 0.2s ease-out, transform 0.2s ease-out; /* Smooth animation */
        }
        .dropdown-content a {
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            display: block;
            text-align: left;
            border-radius: 0; /* No individual rounded corners for dropdown items */
            transition: background-color 0.3s ease;
            box-shadow: none; /* Remove individual link shadow */
        }
        .dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.25); /* Lighter hover for dropdown */
            transform: none; /* No transform on dropdown items */
            box-shadow: none;
        }
        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1; /* Fade in */
            transform: translateY(0); /* Move to final position */
        }

        /* --- Navbar Responsive Adjustments (Refined) --- */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start; /* Align items to the start */
                padding: 15px 10px;
            }
            .navbar .site-title {
                font-size: 1.6rem; /* Adjusted for smaller screens */
                margin-bottom: 15px; /* More space below title */
                width: 100%;
                text-align: center;
                margin-right: 0;
            }
            .navbar-links {
                width: 100%;
                justify-content: center; /* Center links when wrapped */
                gap: 8px; /* Reduced gap for more compact layout */
            }
            .navbar-links a {
                padding: 8px 12px;
                font-size: 0.9rem; /* Smaller font size */
            }
            body {
                padding-top: 130px; /* Adjust body padding to accommodate expanded navbar */
            }
            .dropdown {
                width: 100%; /* Make dropdown take full width on small screens */
                text-align: center;
            }
            .dropdown-content {
                width: 100%;
                left: 0;
                right: 0;
                top: unset; /* Remove fixed top */
                position: static; /* Allow dropdown to flow in line */
                box-shadow: none;
                border-radius: 0;
                transform: translateY(0); /* No initial transform for mobile dropdown */
                opacity: 1; /* Always visible on mobile if parent dropdown is shown */
            }
            .dropdown-content a {
                padding: 10px;
                border-radius: 0;
            }
        }
        @media (max-width: 480px) {
            .navbar-links a {
                padding: 6px 10px;
                font-size: 0.85rem;
                flex-grow: 1; /* Allow links to grow to fill space */
                text-align: center;
            }
            body {
                padding-top: 110px;
            }
        }

        /* --- Main Content Container --- */
        .main-content {
            width: 100%;
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 25px;
            box-sizing: border-box;
        }
        h1 {
            text-align: center;
            color: #2c73d2;
            margin-bottom: 2.5rem;
            font-size: 2.8rem;
            font-weight: 700;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.08);
            letter-spacing: -0.5px;
        }
        h2 {
            font-size: 2rem;
            color: #3f51b5; /* Deeper blue for section titles */
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        h3 {
            font-size: 1.5rem;
            color: #555;
            margin-top: 30px;
            margin-bottom: 20px;
            font-weight: 600;
            border-left: 5px solid #2c73d2;
            padding-left: 10px;
        }

        /* --- Status Message --- */
        .status-message {
            text-align: center;
            padding: 15px;
            margin: 20px auto;
            border-radius: 10px;
            font-weight: bold;
            max-width: 800px;
            display: block;
            animation: fadeIn 0.5s forwards;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 8px solid; /* For colored border */
        }
        .status-message:empty {
            display: none;
        }
        .success {
            background-color: #e6ffed; /* Lighter green */
            color: #1a6d2f;
            border-color: #28a745;
        }
        .error {
            background-color: #ffe6e6; /* Lighter red */
            color: #a71a2b;
            border-color: #dc3545;
        }

        /* --- Dashboard Overview Cards (6-Column Layout, Smaller Size) --- */
        .dashboard-overview {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* Center cards for better alignment */
            gap: 10px; /* Adjusted gap to fit more cards */
            margin-bottom: 0.5rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 09px; /* Smaller padding for smaller boxes */
            text-align: center;
            flex: 1 1 calc(16% - 12px); /* Calc for 6 columns, with gap */
            min-width: 130px; /* Minimum width for cards */
            max-width: calc(16.66% - 12px); /* Max width to strictly maintain 6 columns if space allows */
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: center; /* Center content horizontally */
            border: 1px solid #e0e0e0;
            box-sizing: border-box;
            cursor: pointer; /* Indicate it's clickable */
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        .card.active-card { /* Style for active/clicked card */
            border-color: #2c73d2;
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2), 0 12px 30px rgba(0, 0, 0, 0.15);
            transform: translateY(-4px);
        }
        .card-icon { /* Style for potential icons in cards */
            font-size: 1.4em;
            margin-bottom: 6px;
            color: #555;
        }
        .card h2 {
            font-size: 1.05rem; /* Adjusted font size for smaller boxes */
            color: #555;
            margin-bottom: 8px; /* Adjusted margin */
            font-weight: 600;
            border-bottom: none;
            line-height: 1.2; /* Tighter line height */
        }
        .card .value {
            font-size: 2.1rem; /* Adjusted font size */
            font-weight: 700;
            color: #2c73d2;
            margin-top: 2px; /* Adjusted margin */
        }
        /* Specific card colors */
        .card.total .value { color: #28a745; }
        .card.booked .value { color: #ffc107; }
        .card.in-transit .value { color: #17a2b8; }
        .card.delivered .value { color: #8bc34a; }
        .card.cancelled .value { color: #dc3545; }
        .card.other .value { color: #6f42c1; }
        .card.already-booked .value { color: #ff9800; }
        /* Responsive adjustments for cards */
        @media (max-width: 1400px) { /* Adjust for 5 columns */
            .card { flex: 1 1 calc(20% - 12px); max-width: calc(20% - 12px); }
        }
        @media (max-width: 1200px) { /* Adjust for 4 columns */
            .card { flex: 1 1 calc(25% - 12px); max-width: calc(25% - 12px); }
        }
        @media (max-width: 992px) { /* Adjust for 3 columns */
            .card { flex: 1 1 calc(33.33% - 12px); max-width: calc(33.33% - 12px); }
        }
        @media (max-width: 768px) { /* Adjust for 2 columns */
            .card { flex: 1 1 calc(50% - 12px); max-width: calc(50% - 12px); }
        }
        @media (max-width: 480px) { /* Adjust for 1 column */
            .card { flex: 1 1 90%; max-width: 90%; padding: 10px; }
            .card h2 { font-size: 0.95rem; }
            .card .value { font-size: 2rem; }
            .card-icon { font-size: 1.5em; }
        }
        /* --- Action Buttons (Add New Lead) - Centered --- */
        .action-buttons {
            text-align: center; /* This centers the button */
            margin-bottom: 3.5rem;
        }
        .action-buttons .add-new-btn, .action-buttons .show-all-btn {
            background-color: #28a745;
            color: white;
            padding: 15px 35px;
            border: none;
            border-radius: 8px;
            font-size: 1.15rem;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: inline-block; /* Allows padding and margin */
            margin: 10px; /* Space between buttons if multiple */
        }
        .action-buttons .add-new-btn:hover, .action-buttons .show-all-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        .action-buttons .show-all-btn {
            background-color: #007bff; /* Blue for 'Show All' */
        }
        .action-buttons .show-all-btn:hover {
            background-color: #0056b3;
        }
        /* --- Table Styles (General) --- */
        .table-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .table-section h2 {
            margin-top: 0;
            margin-bottom: 25px;
            text-align: left;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .table-container {
            width: 100%;
            overflow-x: auto; /* Ensures horizontal scrolling for tables */
            margin-top: 20px;
            /* FIXED HEIGHT AND SCROLL FOR TABLES */
            max-height: 400px; /* Adjust as needed */
            overflow-y: auto;
            border: 1px solid #e6e6e6; /* Light border around scrollable area */
            border-radius: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            min-width: 800px; /* Ensure table doesn't shrink too much */
        }
        .data-table th, .data-table td {
            border: 1px solid #e6e6e6;
            padding: 12px 15px;
            text-align: left;
            vertical-align: top; /* Align content to top for better readability */
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
            position: sticky; /* Sticky headers for scrollable tables */
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.05); /* Subtle shadow for sticky effect */
        }
        .data-table tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        .data-table tr:hover {
            background-color: #eef7ff; /* Lighter blue on hover */
        }
        .data-table td.actions {
            white-space: nowrap; /* Prevent buttons from wrapping */
            text-align: center;
        }
        .data-table .action-btn {
            padding: 8px 12px;
            margin: 2px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            text-decoration: none; /* For anchor tags */
            display: inline-block;
        }
        .data-table .edit-btn {
            background-color: #007bff;
            color: white;
        }
        .data-table .edit-btn:hover {
            background-color: #0056b3;
        }
        .data-table .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .data-table .delete-btn:hover {
            background-color: #c82333;
        }
        .data-table .view-btn {
            background-color: #6c757d;
            color: white;
        }
        .data-table .view-btn:hover {
            background-color: #5a6268;
        }
        .data-table .email-btn {
            background-color: #ffc107;
            color: #333;
        } /* Yellow for email */
        .data-table .email-btn:hover {
            background-color: #e0a800;
        }
        .data-table .mark-read-btn {
            background-color: #28a745;
            color: white;
        }
        .data-table .mark-read-btn:hover {
            background-color: #218838;
        }
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            color: white;
            text-align: center;
            min-width: 80px;
        }
        .status-badge.New_Lead { background-color: #007bff; } /* Blue */
        .status-badge.Booked { background-color: #ffc107; color: #333;} /* Yellow */
        .status-badge.In_Transit { background-color: #17a2b8; } /* Cyan */
        .status-badge.Delivered { background-color: #28a745; } /* Green */
        .status-badge.Cancelled { background-color: #dc3545; } /* Red */
        .status-badge.Not_Pick { background-color: #6c757d; } /* Grey */
        .status-badge.Voice_Mail { background-color: #6f42c1; } /* Purple */
        .status-badge.In_Future_Shipment { background-color: #20c997; } /* Teal */
        .status-badge.Qutation { background-color: #fd7e14; } /* Orange */
        .status-badge.Invalid_Lead { background-color: #e83e8c; } /* Pink */
        .status-badge.Stop_Lead { background-color: #f8f9fa; color: #333; border: 1px solid #ccc; } /* Light grey with border */
        .status-badge.On_Hold { background-color: #ff851b; } /* Custom Orange */
        .status-badge.Already_Booked { background-color: #ff4136; } /* Stronger Red */
        /* Pagination styles (if you implement pagination later) */
        .pagination {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #2c73d2;
            transition: background-color 0.3s;
        }
        .pagination a:hover:not(.active) {
            background-color: #f2f2f2;
        }
        .pagination span.active {
            background-color: #2c73d2;
            color: white;
            border: 1px solid #2c73d2;
        }
        /* --- Add User Forms --- */
        .add-user-section {
            background: #21;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .add-user-section form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .add-user-section .form-group {
            margin-bottom: 0;
        }
        .add-user-section .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.95rem;
        }
        .add-user-section .form-group input[type="text"],
        .add-user-section .form-group input[type="email"],
        .add-user-section .form-group input[type="tel"],
        .add-user-section .form-group input[type="password"],
        .add-user-section .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #c2dcfc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .add-user-section .form-group input:focus,
        .add-user-section .form-group select:focus {
            border-color: #2c73d2;
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
            outline: none;
        }
        .add-user-section .form-group button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            width: auto; /* Auto width to fit content */
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
            justify-self: start; /* Align button to the start of its grid column */
        }
        .add-user-section .form-group button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .add-user-section .form-group button:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        /* Full width button for grid if needed */
        .add-user-section .grid-col-span-full {
            grid-column: 1 / -1; /* Span all columns */
            text-align: center; /* Center the button within the full span */
        }
        /* Responsive adjustments for add user forms */
        @media (max-width: 600px) {
            .add-user-section form {
                grid-template-columns: 1fr; /* Single column layout on small screens */
            }
            .add-user-section .form-group button {
                width: 100%;
                justify-self: stretch;
            }
        }

        /* --- Modal Styles --- */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1001; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.6); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s forwards;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 600px;
            position: relative;
            animation: slideInTop 0.3s forwards;
        }

        .modal-content h3 {
            margin-top: 0;
            color: #2c73d2;
            border-left: none;
            padding-left: 0;
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 25px;
        }

        .modal-content .form-group {
            margin-bottom: 20px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .modal-content input[type="text"],
        .modal-content input[type="email"],
        .modal-content textarea {
            width: calc(100% - 24px); /* Account for padding */
            padding: 12px;
            border: 1px solid #c2dcfc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .modal-content input:focus,
        .modal-content textarea:focus {
            border-color: #2c73d2;
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
            outline: none;
        }

        .modal-content textarea {
            min-height: 120px;
            resize: vertical;
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
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            font-weight: 600;
        }

        .modal-buttons .send-btn {
            background-color: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .modal-buttons .send-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .modal-buttons .close-btn {
            background-color: #6c757d;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .modal-buttons .close-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .close {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInTop {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Footer */
        .footer {
            background-color: #2c73d2;
            color: white;
            text-align: center;
            padding: 20px 0;
            font-size: 0.9em;
            margin-top: auto; /* Pushes footer to the bottom */
            box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
        }

        /* Utility classes for display */
        .hidden { display: none !important; }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
   <div class="navbar">
        <img style="width: 60px" class="img-responsive" src="assets/img/logo/logo.png" alt="Logo">
        <div class="navbar-links">
            <a href="admin.php">Dashboard</a>
                
            
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
        
        <?php if (!empty($dashboard_status_message)): ?>
            <div class="status-message <?php echo $dashboard_status_type; ?>">
                <?php echo $dashboard_status_message; ?>
            </div>
        <?php endif; ?>

        
        <div class="dashboard-overview">
            <div class="card total" data-status-filter="">
                <i class="fas fa-chart-line card-icon"></i>
                <h2>Total Leads</h2>
                <div class="value"><?php echo $total_leads; ?></div>
            </div>
            <div class="card booked" data-status-filter="Booked">
                <i class="fas fa-check-circle card-icon"></i>
                <h2>Booked Leads</h2>
                <div class="value"><?php echo $booked_leads; ?></div>
            </div>
            <div class="card in-transit" data-status-filter="In Transit">
                <i class="fas fa-truck card-icon"></i>
                <h2>In Transit</h2>
                <div class="value"><?php echo $in_transit_leads; ?></div>
            </div>
            <div class="card delivered" data-status-filter="Delivered">
                <i class="fas fa-dolly card-icon"></i>
                <h2>Delivered</h2>
                <div class="value"><?php echo $delivered_leads; ?></div>
            </div>
            <div class="card cancelled" data-status-filter="Cancelled">
                <i class="fas fa-times-circle card-icon"></i>
                <h2>Cancelled</h2>
                <div class="value"><?php echo $cancelled_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="On Hold">
                <i class="fas fa-hand-paper card-icon"></i>
                <h2>On Hold</h2>
                <div class="value"><?php echo $on_hold_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="Not Pick">
                <i class="fas fa-minus-circle card-icon"></i>
                <h2>Not Pick</h2>
                <div class="value"><?php echo $not_pick_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="Voice Mail">
                <i class="fas fa-voicemail card-icon"></i>
                <h2>Voice Mail</h2>
                <div class="value"><?php echo $voice_mail_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="In Future Shipment">
                <i class="fas fa-calendar-alt card-icon"></i>
                <h2>In Future Ship.</h2>
                <div class="value"><?php echo $in_future_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="Qutation">
                <i class="fas fa-file-invoice-dollar card-icon"></i>
                <h2>Qutation</h2>
                <div class="value"><?php echo $qutation_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="Invalid Lead">
                <i class="fas fa-ban card-icon"></i>
                <h2>Invalid Lead</h2>
                <div class="value"><?php echo $invalid_leads; ?></div>
            </div>
            <div class="card other" data-status-filter="Stop Lead">
                <i class="fas fa-stop-circle card-icon"></i>
                <h2>Stop Lead</h2>
                <div class="value"><?php echo $stop_leads; ?></div>
            </div>
            <div class="card already-booked" data-status-filter="Already Booked">
                <i class="fas fa-bookmark card-icon"></i>
                <h2>Already Booked</h2>
                <div class="value"><?php echo $already_booked_leads; ?></div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="shippment_lead.php" class="add-new-btn"><i class="fas fa-plus-circle"></i> Add New Lead</a>
            <button id="show-all-leads-btn" class="show-all-btn"><i class="fas fa-list-alt"></i> Show All Leads</button>
        </div>

        <section id="leads-section" class="table-section">
            <h2>All Leads Data</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Quote ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Quote Amount</th>
                            <th>Quote Date</th>
                            <th>Ship Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leads-table-body">
                        <?php if (!empty($leads)): ?>
                            <?php foreach ($leads as $lead): ?>
                                <tr class="lead-row" data-status="<?php echo htmlspecialchars($lead['status']); ?>">
                                    <td><?php echo htmlspecialchars($lead['id']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['quote_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($lead['quote_amount'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($lead['formatted_quote_date']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['formatted_shippment_date']); ?></td>
                                    <td><span class="status-badge <?php echo str_replace(' ', '_', htmlspecialchars($lead['status'])); ?>"><?php echo htmlspecialchars($lead['status']); ?></span></td>
                                    <td class="actions">
                                        <a href="edit_lead.php?id=<?php echo htmlspecialchars($lead['id']); ?>" class="action-btn edit-btn" title="Edit Lead"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_lead.php?id=<?php echo htmlspecialchars($lead['id']); ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this lead?');" title="Delete Lead"><i class="fas fa-trash-alt"></i> Delete</a>
                                        <button class="action-btn email-btn" data-email-to="<?php echo htmlspecialchars($lead['email']); ?>" data-customer-name="<?php echo htmlspecialchars($lead['name']); ?>" title="Send Email"><i class="fas fa-envelope"></i> Email</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="text-align: center;">No leads found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="admin-users-section" class="table-section">
            <h2>Admin Users</h2>
            <?php if (!empty($add_admin_status_message)): ?>
                <div class="status-message <?php echo $add_admin_status_type; ?>">
                    <?php echo $add_admin_status_message; ?>
                </div>
            <?php endif; ?>
            <h3>Add New Admin User</h3>
            <div class="add-user-section">
                <form method="POST" action="admin.php#admin-users-section">
                    <div class="form-group">
                        <label for="new_admin_name">Name:</label>
                        <input type="text" id="new_admin_name" name="new_name" required>
                    </div>
                    <div class="form-group">
                        <label for="new_admin_email">Email:</label>
                        <input type="email" id="new_admin_email" name="new_email" required>
                    </div>
                    <div class="form-group">
                        <label for="new_admin_phone">Phone (Optional):</label>
                        <input type="tel" id="new_admin_phone" name="new_phone">
                    </div>
                    <div class="form-group">
                        <label for="new_admin_username">Username:</label>
                        <input type="text" id="new_admin_username" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label for="new_admin_password">Password:</label>
                        <input type="password" id="new_admin_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_admin_status">Status:</label>
                        <select id="new_admin_status" name="new_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group grid-col-span-full">
                        <button type="submit" name="add_admin_user">Add Admin User</button>
                    </div>
                </form>
            </div>

            <h3>Existing Admin Users</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($admin_users)): ?>
                            <?php foreach ($admin_users as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><span class="status-badge <?php echo htmlspecialchars($admin['status']); ?>"><?php echo htmlspecialchars($admin['status']); ?></span></td>
                                    <td class="actions">
                                        <a href="edit_admin.php?id=<?php echo htmlspecialchars($admin['id']); ?>" class="action-btn edit-btn" title="Edit Admin"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_admin.php?id=<?php echo htmlspecialchars($admin['id']); ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this admin user?');" title="Delete Admin"><i class="fas fa-trash-alt"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No admin users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="local-users-section" class="table-section">
            <h2>Local Users</h2>
            <?php if (!empty($add_local_user_status_message)): ?>
                <div class="status-message <?php echo $add_local_user_status_type; ?>">
                    <?php echo $add_local_user_status_message; ?>
                </div>
            <?php endif; ?>
            <h3>Add New Local User</h3>
            <div class="add-user-section">
                <form method="POST" action="admin.php#local-users-section">
                    <div class="form-group">
                        <label for="new_local_name">Name:</label>
                        <input type="text" id="new_local_name" name="new_local_name" required>
                    </div>
                    <div class="form-group">
                        <label for="new_local_email">Email:</label>
                        <input type="email" id="new_local_email" name="new_local_email" required>
                    </div>
                    <div class="form-group">
                        <label for="new_local_phone">Phone (Optional):</label>
                        <input type="tel" id="new_local_phone" name="new_local_phone">
                    </div>
                    <div class="form-group">
                        <label for="new_local_username">Username:</label>
                        <input type="text" id="new_local_username" name="new_local_username" required>
                    </div>
                    <div class="form-group">
                        <label for="new_local_password">Password:</label>
                        <input type="password" id="new_local_password" name="new_local_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_local_status">Status:</label>
                        <select id="new_local_status" name="new_local_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group grid-col-span-full">
                        <button type="submit" name="add_local_user">Add Local User</button>
                    </div>
                </form>
            </div>

            <h3>Existing Local Users</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($local_users)): ?>
                            <?php foreach ($local_users as $local_user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($local_user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['username']); ?></td>
                                    <td><span class="status-badge <?php echo htmlspecialchars($local_user['status']); ?>"><?php echo htmlspecialchars($local_user['status']); ?></span></td>
                                    <td class="actions">
                                        <a href="edit_local_user.php?id=<?php echo htmlspecialchars($local_user['id']); ?>" class="action-btn edit-btn" title="Edit User"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="delete_local_user.php?id=<?php echo htmlspecialchars($local_user['id']); ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this local user?');" title="Delete User"><i class="fas fa-trash-alt"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No local users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        

        

    </div>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEmailModal()">&times;</span>
            <h3>Send Email</h3>
            <form id="emailForm" method="POST" action="admin.php">
                <div class="form-group">
                    <label for="to_email">To:</label>
                    <input type="email" id="to_email" name="to_email" readonly required>
                </div>
                <div class="form-group" style="display: none;">
                    <label for="from_email" >From (Your Email):</label>
                    <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($email_config['from_email'] ?? $_SESSION['admin_email'] ?? ''); ?>" required>
                </div>
                <div class="form-group" style="display: none;">
                    <label for="subject" >Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message_body">Message:</label>
                    <textarea id="message_body" name="message_body" required></textarea>
                </div>
                <input type="hidden" id="app_source" name="app_source" value="Admin Dashboard">
                <div class="modal-buttons">
                    <button type="button" class="close-btn" onclick="closeEmailModal()">Cancel</button>
                    <button type="submit" name="send_email" class="send-btn">Send Email</button>
                </div>
            </form>
        </div>
    </div>


    <footer class="footer">
        &copy; <?php echo date('Y'); ?> MJ Hauling United LLC. All rights reserved.
    </footer>

    <script>
        // JavaScript for Email Modal
        const emailModal = document.getElementById('emailModal');
        const emailButtons = document.querySelectorAll('.email-btn');
        const toEmailInput = document.getElementById('to_email');
        const customerNameInput = document.getElementById('customer_name_modal'); // This isn't in HTML, will be null.
        const subjectInput = document.getElementById('subject');
        const messageBody = document.getElementById('message_body');
        const appSourceInput = document.getElementById('app_source');

        emailButtons.forEach(button => {
            button.addEventListener('click', function() {
                const recipientEmail = this.dataset.emailTo;
                const customerName = this.dataset.customerName || ''; // Get customer name if available
                const appSource = this.dataset.appSource || 'Admin Dashboard'; // Get app source

                toEmailInput.value = recipientEmail;
                appSourceInput.value = appSource;

                // Pre-fill subject based on context
                if (appSource.includes('Quotation Request')) {
                    subjectInput.value = 'Regarding your recent quotation request' + (customerName ? ' (' + customerName + ')' : '');
                    messageBody.value = 'Dear ' + (customerName || 'Customer') + ',\n\n';
                } else if (appSource.includes('Contact Message')) {
                    subjectInput.value = 'Reply to your message' + (customerName ? ' (' + customerName + ')' : '');
                    messageBody.value = 'Dear ' + (customerName || 'Customer') + ',\n\n';
                } else {
                    subjectInput.value = 'Regarding your lead' + (customerName ? ' (' + customerName + ')' : '');
                    messageBody.value = 'Dear ' + (customerName || 'Customer') + ',\n\n';
                }


                emailModal.style.display = 'flex'; // Use flex to center the modal
            });
        });

        function closeEmailModal() {
            emailModal.style.display = 'none';
            // Clear fields on close
            toEmailInput.value = '';
            subjectInput.value = '';
            messageBody.value = '';
            appSourceInput.value = 'Admin Dashboard'; // Reset to default
        }

        window.onclick = function(event) {
            if (event.target == emailModal) {
                closeEmailModal();
            }
        }

        // JavaScript for Lead Status Filtering
        document.addEventListener('DOMContentLoaded', function() {
            const overviewCards = document.querySelectorAll('.dashboard-overview .card');
            const leadsTableBody = document.getElementById('leads-table-body');
            const leadsTableRows = leadsTableBody.querySelectorAll('.lead-row');
            const showAllLeadsBtn = document.getElementById('show-all-leads-btn');
            const leadsSection = document.getElementById('leads-section');

            function filterLeads(status) {
                leadsTableRows.forEach(row => {
                    if (status === "" || row.dataset.status === status) {
                        row.style.display = ''; // Show row
                    } else {
                        row.style.display = 'none'; // Hide row
                    }
                });
            }

            function setActiveCard(activeCardElement) {
                overviewCards.forEach(card => card.classList.remove('active-card'));
                if (activeCardElement) {
                    activeCardElement.classList.add('active-card');
                }
            }

            overviewCards.forEach(card => {
                card.addEventListener('click', function() {
                    const statusFilter = this.dataset.statusFilter;
                    filterLeads(statusFilter);
                    setActiveCard(this);
                    // Scroll to leads section for better UX
                    leadsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            showAllLeadsBtn.addEventListener('click', function() {
                filterLeads(""); // Show all leads
                setActiveCard(null); // Deactivate all cards
                leadsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            // Initial state: show all leads and no card active
            filterLeads("");
            setActiveCard(null);
        });
    </script>
</body>
</html>