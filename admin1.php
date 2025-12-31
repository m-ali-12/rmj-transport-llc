<?php
session_start(); // Start the session at the very beginning

// Redirect function (reused from other pages)
function redirectWithStatus($page, $status, $message) {
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
            $stmt = $pdo->prepare("SELECT id, username, name, email, status FROM admin_users WHERE username = :username AND password = :password");
            $stmt->execute([':username' => $username, ':password' => $hashed_password_input]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_status'] = $admin['status'];

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
    $stmt_quotations = $pdo->query("SELECT id, customer_name, customer_email, customer_phone, pickup_location, delivery_location, vehicle_make, vehicle_model, vehicle_type, transport_type, shipment_date, distance, special_instructions, created_at FROM shipment_quote ORDER BY created_at DESC");
    $quotations = $stmt_quotations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching quotation data: " . $e->getMessage());
    // Optionally set a status message for the dashboard
    $dashboard_status_type = 'error';
    $dashboard_status_message = 'Failed to load quotation requests.';
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
            gap: 12px; /* Adjusted gap to fit more cards */
            margin-bottom: 3.5rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px; /* Smaller padding for smaller boxes */
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
            font-size: 1.8em;
            margin-bottom: 8px;
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
            font-size: 2.5rem; /* Adjusted font size */
            font-weight: 700;
            color: #2c73d2;
            margin-top: 5px; /* Adjusted margin */
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
            .card {
                flex: 1 1 calc(20% - 12px);
                max-width: calc(20% - 12px);
            }
        }
        @media (max-width: 1200px) { /* Adjust for 4 columns */
            .card {
                flex: 1 1 calc(25% - 12px);
                max-width: calc(25% - 12px);
            }
        }
        @media (max-width: 992px) { /* Adjust for 3 columns */
            .card {
                flex: 1 1 calc(33.33% - 12px);
                max-width: calc(33.33% - 12px);
            }
        }
        @media (max-width: 768px) { /* Adjust for 2 columns */
            .card {
                flex: 1 1 calc(50% - 12px);
                max-width: calc(50% - 12px);
            }
        }
        @media (max-width: 480px) { /* Adjust for 1 column */
            .card {
                flex: 1 1 90%;
                max-width: 90%;
                padding: 10px;
            }
            .card h2 {
                font-size: 0.95rem;
            }
            .card .value {
                font-size: 2rem;
            }
            .card-icon {
                font-size: 1.5em;
            }
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
            display: inline-block; /* For proper padding and alignment */
            margin: 0 10px; /* Space between buttons if multiple */
        }
        .action-buttons .add-new-btn:hover, .action-buttons .show-all-btn:hover {
            background-color: #218838;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .action-buttons .add-new-btn:active, .action-buttons .show-all-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .action-buttons .show-all-btn {
             background-color: #007bff; /* Different color for show all */
        }
        .action-buttons .show-all-btn:hover {
            background-color: #0056b3;
        }

        /* --- Table Sections (Leads, Users, Contacts, Quotations) --- */
        .leads-table-section, .admin-users-section, .local-users-section,
        .contact-messages-section, .quotation-requests-section {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 3.5rem;
            border: 1px solid #e0e0e0;
        }
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #e9ecef; /* Lighter border for table container */
            border-radius: 10px;
            box-shadow: inset 0 0 8px rgba(0,0,0,0.03);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 1000px; /* Ensures horizontal scroll for many columns */
        }
        th, td {
            border: 1px solid #f0f0f0; /* Very light border for cells */
            padding: 14px 18px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
        }
        th {
            background-color: #f8fafd; /* Very light blue header */
            color: #495057; /* Darker grey for header text */
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
            position: sticky;
            top: 0;
            z-index: 2;
            white-space: nowrap; /* Prevent wrapping in headers */
        }
        tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        tr:hover {
            background-color: #eaf6ff; /* Light blue on hover */
            cursor: default;
        }
        td.actions {
            white-space: nowrap;
            min-width: 180px; /* Ensure enough space for buttons */
            text-align: center;
        }
        .actions a {
            margin: 0 6px;
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 0.9em;
            display: inline-block;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            font-weight: 500;
        }
        .actions .edit-btn {
            background-color: #007bff; /* Blue */
            color: white;
        }
        .actions .edit-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .actions .delete-btn {
            background-color: #dc3545; /* Red */
            color: white;
        }
        .actions .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .email-btn {
            background-color: #17a2b8; /* Teal for email */
            color: white;
        }
        .email-btn:hover {
            background-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .no-leads, .no-users {
            text-align: center;
            padding: 40px;
            font-style: italic;
            color: #777;
            background-color: #fefefe;
            border-radius: 10px;
            margin-top: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px dashed #e0e0e0;
        }

        /* --- User Add Forms --- */
        .add-user-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
            padding: 30px;
            border: 2px dashed #c2dcfc; /* More prominent dashed border */
            border-radius: 12px;
            background-color: #fdfefe; /* Slightly off-white background */
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .add-user-form .form-group {
            position: relative;
        }
        .add-user-form label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #444;
            font-size: 0.95em;
        }
        .add-user-form input[type="text"],
        .add-user-form input[type="email"],
        .add-user-form input[type="password"],
        .add-user-form input[type="tel"],
        .add-user-form select {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcdfe6; /* Light grey border */
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .add-user-form input:focus,
        .add-user-form select:focus {
            border-color: #2c73d2;
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
            outline: none;
        }
        .add-user-form button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            margin-top: 10px; /* Space from inputs */
            width: auto;
            align-self: flex-end; /* Align button to bottom in grid */
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .add-user-form button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .add-user-form .password-toggle {
            position: absolute;
            right: 15px;
            top: 60%; /* Adjust based on input padding */
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 0.85em;
            user-select: none;
            padding: 5px; /* Make it easier to click */
        }
        .add-user-form .password-toggle:hover {
            color: #444;
        }
        .users-table-wrapper, .contact-table-wrapper, .quotation-table-wrapper {
            margin-top: 25px; /* Space between form and table */
        }


        /* --- Footer --- */
        footer {
            text-align: center;
            margin-top: auto; /* Pushes footer to the bottom */
            padding: 30px;
            font-weight: 600;
            color: #888;
            font-size: 0.9rem;
            background-color: #eef2f7; /* Light background for footer */
            border-top: 1px solid #e0e0e0;
        }

        /* --- Scroll-to-top Button Styles --- */
        #scrollToTopBtn {
            display: none; /* Hidden by default */
            position: fixed; /* Fixed/sticky position */
            bottom: 30px; /* Place the button at the bottom of the page */
            right: 30px; /* Place the button 30px from the right */
            z-index: 99; /* Make sure it does not overlap */
            border: none; /* Remove borders */
            outline: none; /* Remove outline */
            background-color: #2c73d2; /* Set a background color */
            color: white; /* Text color */
            cursor: pointer; /* Add a mouse pointer on hover */
            padding: 15px 20px; /* Some padding */
            border-radius: 50%; /* Rounded square button */
            font-size: 18px; /* Increase font size */
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); /* Add a shadow */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        #scrollToTopBtn:hover {
            background-color: #1a5ea6; /* Darker background on hover */
            transform: translateY(-3px); /* Lift button slightly */
            box-shadow: 0 6px 15px rgba(0,0,0,0.3); /* Stronger shadow on hover */
        }

        /* --- SVG Background Circle --- */
        .svg-background-circle {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px; /* Adjust size as needed */
            height: 150px;
            opacity: 0.05; /* Very subtle */
            z-index: -1; /* Send it behind other content */
            pointer-events: none; /* Ensure it doesn't block clicks */
            animation: rotate360 20s linear infinite; /* Slow rotation animation */
        }
        .svg-background-circle path {
            stroke: #ffffff; /* White stroke */
            stroke-width: 2; /* Adjust width as needed */
            fill: none; /* No fill */
        }
        @keyframes rotate360 {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Adjustments (General) */
        @media (max-width: 768px) {
            .main-content {
                padding: 0 15px;
            }
            h1 {
                font-size: 2.2rem;
            }
            h2 {
                font-size: 1.8rem;
            }
            h3 {
                font-size: 1.3rem;
            }
            .action-buttons .add-new-btn, .action-buttons .show-all-btn {
                padding: 12px 25px;
                font-size: 1.05rem;
            }
            .add-user-form {
                grid-template-columns: 1fr; /* Stack inputs vertically */
                gap: 15px;
                padding: 20px;
            }
            table {
                min-width: 600px; /* Still provide some min-width for smaller screens */
            }
            th, td {
                padding: 10px 12px;
            }
            .actions a {
                margin: 0 3px;
                padding: 7px 12px;
                font-size: 0.85em;
            }
        }
        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }
            h1 {
                font-size: 1.8rem;
            }
            h2 {
                font-size: 1.5rem;
            }
            .action-buttons .add-new-btn, .action-buttons .show-all-btn {
                padding: 10px 20px;
                font-size: 0.95rem;
            }
            .add-user-form input, .add-user-form select {
                padding: 10px;
                font-size: 0.9rem;
            }
            .add-user-form button {
                padding: 10px 15px;
                font-size: 0.95rem;
            }
            table {
                min-width: 500px;
            }
            th, td {
                font-size: 0.8em;
                padding: 8px 10px;
            }
            td.actions {
                min-width: 150px;
            }
        }
    </style>
    <script>
        // Function to confirm deletion for leads
        function confirmDelete(id) {
            return confirm('Are you sure you want to delete lead ID: ' + id + '? This action cannot be undone.');
        }
        // Function to confirm deletion for admin users
        function confirmAdminDelete(id, username) {
            return confirm('Are you sure you want to delete admin user: ' + username + ' (ID: ' + id + ')? This action cannot be undone.');
        }
        // Function to confirm deletion for local users
        function confirmLocalUserDelete(id, username) {
            return confirm('Are you sure you want to delete local user: ' + username + ' (ID: ' + id + ')? This action cannot be undone.');
        }

        // Function to display status messages (for dashboard actions, admin add, local user add)
        window.onload = function() {
            const statusDivAdmin = document.getElementById('adminStatusMessage');
            if (statusDivAdmin && statusDivAdmin.textContent.trim() !== '') {
                setTimeout(() => statusDivAdmin.textContent = '', 5000);
            }
            const statusDivLocal = document.getElementById('localUserStatusMessage');
            if (statusDivLocal && statusDivLocal.textContent.trim() !== '') {
                setTimeout(() => statusDivLocal.textContent = '', 5000);
            }

            // This one is for general dashboard messages from GET params
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            if (status && message) {
                const statusDiv = document.getElementById('dashboardStatusMessage');
                if (statusDiv) {
                    statusDiv.className = `status-message ${status}`;
                    statusDiv.textContent = decodeURIComponent(message);
                    setTimeout(() => statusDiv.textContent = '', 5000);
                }
            }
            
            // Add click listeners to cards for filtering
            document.querySelectorAll('.dashboard-overview .card').forEach(card => {
                card.addEventListener('click', function() {
                    const statusToFilter = this.dataset.status;
                    filterLeadsByStatus(statusToFilter);
                    // Add/remove active class for visual feedback
                    document.querySelectorAll('.dashboard-overview .card').forEach(c => c.classList.remove('active-card'));
                    this.classList.add('active-card');
                });
            });

            // Add click listener to Show All Leads button
            document.getElementById('showAllLeadsBtn').addEventListener('click', function() {
                filterLeadsByStatus('All'); // Changed to 'All' to match total leads card data-status
                document.querySelectorAll('.dashboard-overview .card').forEach(c => c.classList.remove('active-card')); // Remove active class from all cards
            });
        };


        // Toggle password visibility for new admin user form
        function toggleNewAdminPasswordVisibility() {
            const passwordField = document.getElementById('new_password');
            const toggleBtn = document.getElementById('newAdminPasswordToggle');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = 'Hide';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = 'Show';
            }
        }

        // Toggle password visibility for new local user form
        function toggleNewLocalUserPasswordVisibility() {
            const passwordField = document.getElementById('new_local_password');
            const toggleBtn = document.getElementById('newLocalUserPasswordToggle');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = 'Hide';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = 'Show';
            }
        }

        // Get the scroll-to-top button
        let mybutton = document.getElementById("scrollToTopBtn");

        // When the user scrolls down 20px from the top of the document, show the button
        window.onscroll = function() {scrollFunction()};

        function scrollFunction() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                mybutton.style.display = "block";
            } else {
                mybutton.style.display = "none";
            }
        }

        // When the user clicks on the button, scroll to the top of the document
        mybutton.addEventListener('click', topFunction);

        function topFunction() {
            document.body.scrollTop = 0; // For Safari
            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
        }

        // --- Lead Filtering Function ---
        function filterLeadsByStatus(status) {
            const leadsTableBody = document.querySelector('.leads-table-section tbody');
            if (!leadsTableBody) return;

            const rows = leadsTableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const rowStatus = row.dataset.status; // Get status from data-status attribute
                if (status === 'All' || rowStatus === status) { // 'All' status from the "Total Leads" card
                    row.style.display = ''; // Show row
                } else {
                    row.style.display = 'none'; // Hide row
                }
            });

            // Scroll to the leads table section after filtering
            document.getElementById('leads-data-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>
</head>
<body>
    <div class="navbar">
        <span class="site-title">MJ Hauling United LLC</span>
        <div class="navbar-links">
            <a href="admin.php">Dashboard</a>
            <div class="dropdown">
                <a href="#">Leads &#9662;</a>
                <div class="dropdown-content">
                    <a href="shippment_lead.php">New Lead Form</a>
                    <a href="view_leads.php">View All Leads</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#">More Tools &#9662;</a>
                <div class="dropdown-content">
                    <a href="agreement.php">Agreement Page</a>
                    <a href="#contact-messages">Contact Messages</a>
                    <a href="#quotation-requests">Quotation Requests</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#">Account &#9662;</a>
                <div class="dropdown-content">
                    <a href="user_login.php">User Login</a>
                    <a href="admin.php">Admin Profile</a> <a href="#admin-users-section">Manage Admin Users</a>
                    <a href="#local-users-section">Manage Local Users</a>
                </div>
            </div>
            
            <a href="admin.php?logout=true">Admin Logout</a>
        </div>
        <svg class="svg-background-circle" viewBox="0 0 100 100">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" style="transition: stroke-dashoffset 10ms linear; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 250;"></path>
        </svg>
    </div>

    <div class="main-content">
        <h1>Admin Dashboard</h1>

        <div id="dashboardStatusMessage" class="status-message">
            <?php echo $dashboard_status_message; ?>
        </div>

        <div class="dashboard-overview">
            <div class="card total" data-status="All">
                <h2>Total Leads</h2>
                <div class="value"><?php echo $total_leads; ?></div>
            </div>
            <div class="card booked" data-status="Booked">
                <h2>Booked Leads</h2>
                <div class="value"><?php echo $booked_leads; ?></div>
            </div>
            <div class="card in-transit" data-status="In Transit">
                <h2>In Transit Leads</h2>
                <div class="value"><?php echo $in_transit_leads; ?></div>
            </div>
            <div class="card delivered" data-status="Delivered">
                <h2>Delivered Leads</h2>
                <div class="value"><?php echo $delivered_leads; ?></div>
            </div>
            <div class="card cancelled" data-status="Cancelled">
                <h2>Cancelled Leads</h2>
                <div class="value"><?php echo $cancelled_leads; ?></div>
            </div>
            <div class="card other" data-status="Not Pick">
                <h2>Not Pick</h2>
                <div class="value"><?php echo $not_pick_leads; ?></div>
            </div>
            <div class="card other" data-status="Voice Mail">
                <h2>Voice Mail</h2>
                <div class="value"><?php echo $voice_mail_leads; ?></div>
             </div>
             <div class="card other" data-status="In Future Shipment">
                <h2>In Future</h2>
                <div class="value"><?php echo $in_future_leads; ?></div>
            </div>
             <div class="card other" data-status="Qutation">
                <h2>Qutation</h2>
                <div class="value"><?php echo $qutation_leads; ?></div>
            </div>
             <div class="card other" data-status="Invalid Lead">
                <h2>Invalid Lead</h2>
                <div class="value"><?php echo $invalid_leads; ?></div>
            </div>
             <div class="card other" data-status="Stop Lead">
                <h2>Stop Lead</h2>
                <div class="value"><?php echo $stop_leads; ?></div>
            </div>
            <div class="card other" data-status="On Hold">
                <h2>On Hold</h2>
                <div class="value"><?php echo $on_hold_leads; ?></div>
            </div>
            <div class="card already-booked" data-status="Already Booked">
                <h2>Already Booked</h2>
                <div class="value"><?php echo $already_booked_leads; ?></div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="shippment_lead.php" class="add-new-btn">Add New Lead</a>
            <button id="showAllLeadsBtn" class="show-all-btn">Show All Leads</button>
        </div>

        <div class="leads-table-section" id="leads-data-section">
            <h2>All Leads Data</h2>
            <?php if (count($leads) > 0): ?>
                <div class="table-responsive-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Quote ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th> <th>Quote Amount</th>
                                <th>Quote Date</th>
                                <th>Ship Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead): ?>
                                <tr data-status="<?php echo htmlspecialchars($lead['status']); ?>">
                                    <td><?php echo htmlspecialchars($lead['id']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['quote_id']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['phone']); ?></td> <td>$<?php echo htmlspecialchars(number_format($lead['quote_amount'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($lead['formatted_quote_date']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['formatted_shippment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($lead['status']); ?></td>
                                    <td class="actions">
                                        <a href="edit_lead.php?id=<?php echo htmlspecialchars($lead['id']); ?>" class="edit-btn">Edit</a>
                                        <a href="delete_lead.php?id=<?php echo htmlspecialchars($lead['id']); ?>" class="delete-btn" onclick="return confirmDelete(<?php echo htmlspecialchars($lead['id']); ?>);">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-leads">No leads found in the database.</p>
            <?php endif; ?>
        </div>

        <div class="admin-users-section" id="admin-users-section">
            <h2>Manage Admin Users</h2>
            <div id="adminStatusMessage" class="status-message <?php echo $add_admin_status_type; ?>">
                <?php echo htmlspecialchars($add_admin_status_message); ?>
            </div>

            <h3>Add New Admin User</h3>
            <form method="POST" action="admin.php" class="add-user-form">
                <div class="form-group">
                    <label for="new_name">Name:</label>
                    <input type="text" id="new_name" name="new_name" required>
                </div>
                <div class="form-group">
                    <label for="new_email">Email:</label>
                    <input type="email" id="new_email" name="new_email" required>
                </div>
                <div class="form-group">
                    <label for="new_phone">Phone:</label>
                    <input type="tel" id="new_phone" name="new_phone">
                </div>
                <div class="form-group">
                    <label for="new_username">Username:</label>
                    <input type="text" id="new_username" name="new_username" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <span class="password-toggle" id="newAdminPasswordToggle" onclick="toggleNewAdminPasswordVisibility()">Show</span>
                </div>
                <div class="form-group">
                    <label for="new_status">Status:</label>
                    <select id="new_status" name="new_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" name="add_admin_user">Add Admin</button>
            </form>

            <h3>Existing Admin Users</h3>
            <?php if (count($admin_users) > 0): ?>
                <div class="users-table-wrapper table-responsive-wrapper">
                    <table>
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
                            <?php foreach ($admin_users as $admin_user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin_user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin_user['status']); ?></td>
                                    <td class="actions">
                                        <?php if ($admin_user['id'] !== $_SESSION['admin_id']): ?>
                                            <a href="delete_admin.php?id=<?php echo htmlspecialchars($admin_user['id']); ?>" class="delete-btn" onclick="return confirmAdminDelete(<?php echo htmlspecialchars($admin_user['id']); ?>, '<?php echo htmlspecialchars($admin_user['username']); ?>');">Delete</a>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8em;">(Cannot delete self)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-users">No admin users found.</p>
            <?php endif; ?>
        </div>

        <div class="local-users-section" id="local-users-section">
            <h2>Manage Local Users</h2>
            <div id="localUserStatusMessage" class="status-message <?php echo $add_local_user_status_type; ?>">
                <?php echo htmlspecialchars($add_local_user_status_message); ?>
            </div>

            <h3>Add New Local User</h3>
            <form method="POST" action="admin.php" class="add-user-form">
                <div class="form-group">
                    <label for="new_local_name">Name:</label>
                    <input type="text" id="new_local_name" name="new_local_name" required>
                </div>
                <div class="form-group">
                    <label for="new_local_email">Email:</label>
                    <input type="email" id="new_local_email" name="new_local_email" required>
                </div>
                <div class="form-group">
                    <label for="new_local_phone">Phone:</label>
                    <input type="tel" id="new_local_phone" name="new_local_phone">
                </div>
                <div class="form-group">
                    <label for="new_local_username">Username:</label>
                    <input type="text" id="new_local_username" name="new_local_username" required>
                </div>
                <div class="form-group">
                    <label for="new_local_password">Password:</label>
                    <input type="password" id="new_local_password" name="new_local_password" required>
                    <span class="password-toggle" id="newLocalUserPasswordToggle" onclick="toggleNewLocalUserPasswordVisibility()">Show</span>
                </div>
                <div class="form-group">
                    <label for="new_local_status">Status:</label>
                    <select id="new_local_status" name="new_local_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" name="add_local_user">Add Local User</button>
            </form>

            <h3>Existing Local Users</h3>
            <?php if (count($local_users) > 0): ?>
                <div class="users-table-wrapper table-responsive-wrapper">
                    <table>
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
                            <?php foreach ($local_users as $local_user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($local_user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($local_user['status']); ?></td>
                                    <td class="actions">
                                        <a href="delete_local_user.php?id=<?php echo htmlspecialchars($local_user['id']); ?>" class="delete-btn" onclick="return confirmLocalUserDelete(<?php echo htmlspecialchars($local_user['id']); ?>, '<?php echo htmlspecialchars($local_user['username']); ?>');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-users">No local users found.</p>
            <?php endif; ?>
        </div>

        <div id="contact-messages" class="contact-messages-section">
            <h2>Contact Messages</h2>
            <?php if (count($contacts) > 0): ?>
                <div class="contact-table-wrapper table-responsive-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Mobile No.</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contact['id']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['f_name']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['l_name']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['mob_no']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($contact['msg'])); ?></td>
                                    <td><?php echo htmlspecialchars($contact['status']); ?></td>
                                    <td class="actions">
                                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="email-btn" title="Reply to <?php echo htmlspecialchars($contact['email']); ?>">Email</a>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-leads">No contact messages found.</p>
            <?php endif; ?>
        </div>
        <div id="quotation-requests" class="quotation-requests-section">
            <h2>Quotation Requests</h2>
            <?php if (count($quotations) > 0): ?>
                <div class="quotation-table-wrapper table-responsive-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Pickup</th>
                                <th>Delivery</th>
                                <th>Vehicle</th>
                                <th>Transport Type</th>
                                <th>Shipment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quotations as $quote): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quote['id']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['customer_email']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['customer_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['pickup_location']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['delivery_location']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['vehicle_make'] . ' ' . $quote['vehicle_model']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['vehicle_type']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['transport_type']); ?></td>
                                    <td><?php echo htmlspecialchars($quote['shipment_date']); ?></td>
                                    <td class="actions">
                                        <a href="mailto:<?php echo htmlspecialchars($quote['customer_email']); ?>?subject=Regarding your Shipment Quote Request - MJ Hauling United LLC&body=Dear <?php echo htmlspecialchars($quote['customer_name']); ?>,%0A%0AThank you for your quotation request regarding the shipment of your <?php echo htmlspecialchars($quote['vehicle_make'] . ' ' . $quote['vehicle_model']); ?> from <?php echo htmlspecialchars($quote['pickup_location']); ?> to <?php echo htmlspecialchars($quote['delivery_location']); ?>.%0A%0AWe are reviewing your request and will get back to you shortly with a detailed quote.%0A%0ARequest Details:%0A - Vehicle: <?php echo htmlspecialchars($quote['vehicle_make'] . ' ' . $quote['vehicle_model']); ?>%0A - Pickup Location: <?php echo htmlspecialchars($quote['pickup_location']); ?>%0A - Delivery Location: <?php echo htmlspecialchars($quote['delivery_location']); ?>%0A - Preferred Shipment Date: <?php echo htmlspecialchars($quote['shipment_date']); ?>%0A - Special Instructions: <?php echo nl2br(htmlspecialchars($quote['special_instructions'])); ?>%0A%0AIf you have any further questions, please do not hesitate to contact us.%0A%0ASincerely,%0AThe MJ Hauling United LLC Team" class="email-btn" title="Reply to <?php echo htmlspecialchars($quote['customer_email']); ?>">Email Quote</a>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-leads">No quotation requests found.</p>
            <?php endif; ?>
        </div>
        </div>
    <footer>Powered by Desired Technologies</footer>

    <button id="scrollToTopBtn" title="Go to top">&#9650;</button>

</body>
</html>
