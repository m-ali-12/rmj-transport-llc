<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// sent_mail page data show

require_once 'handle_email.php';

session_start();


// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Check for and display status messages
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status = $_GET['status'];
    $message = $_GET['message'];
    // You would then display this message in your HTML
    // echo "<div class='alert alert-$status'>$message</div>";
}


// Auto-logout after 90 minutes (5400 seconds) of inactivity
$inactivity_timeout = 5400; // 90 minutes * 60 seconds/minute

// Check current login status and get user IDs
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$logged_in_admin_id = $_SESSION['admin_id'] ?? null;
$logged_in_user_id = $_SESSION['user_id'] ?? null;
$logged_in_email = $_SESSION['admin_email'] ?? $_SESSION['user_email'] ?? null;
$logged_in_name = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'User';

// --- Handle Logout request first ---
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    $logged_out_type = $_SESSION['logged_in_type'] ?? 'user';
    session_unset();
    session_destroy();

    if ($logged_out_type === 'admin') {
        redirectWithStatus('admin.php', 'success', 'You have been logged out.');
    } else {
        redirectWithStatus('user_login.php', 'success', 'You have been logged out.');
    }
}

// --- Enforce Login for Shared Pages ---
if (!$is_admin_logged_in && !$is_user_logged_in) {
    redirectWithStatus('user_login.php', 'error', 'Please log in to access this page.');
}

// --- Auto-logout check for ACTIVE session (either admin or user) ---
if (($is_admin_logged_in || $is_user_logged_in) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_timeout)) {
    $logged_out_type = $_SESSION['logged_in_type'] ?? 'user';
    session_unset();
    session_destroy();
    if ($logged_out_type === 'admin') {
        redirectWithStatus('admin.php', 'error', 'You were logged out due to inactivity.');
    } else {
        redirectWithStatus('user_login.php', 'error', 'You were logged out due to inactivity.');
    }
}

if ($is_admin_logged_in) {
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in_type'] = 'admin';
} elseif ($is_user_logged_in) {
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in_type'] = 'user';
}
// --- END AUTHENTICATION LOGIC ---

// Generate CSRF token for form submission (after authentication check)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = ''; // <--- UPDATE THIS VALUE WITH YOUR CORRECT PASSWORD


// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle single email sending request (native mail())
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
    $from_name = $logged_in_name; // Use logged-in user's name
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    //$headers .= "Bcc: " . $from_email . "\r\n"; // Add this line to BCC yourself
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Prepare subject with customer name
    $full_subject = "[MJ Hauling] " . $subject;

    // Use native mail() function
    if (mail($to_email, $full_subject, $message_body, $headers)) {
        redirectWithStatus('view_leads.php', 'success', 'Email sent successfully to ' . htmlspecialchars($customer_name ?: $to_email) . '!');
    } else {
        redirectWithStatus('view_leads.php', 'error', 'Email could not be sent. Please check your server configuration.');
    }
}

// Handle lead assignment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_leads_to_users'])) {
    // Check if admin is logged in
    if (!$is_admin_logged_in) {
        redirectWithStatus('view_leads.php', 'error', 'Access denied. Admin privileges required.');
    }

    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithStatus('view_leads.php', 'error', 'Invalid security token. Please try again.');
    }

    // Get and validate inputs
    $selected_users = $_POST['selected_users'] ?? [];
    $selected_leads = $_POST['selected_leads'] ?? [];

    // Debug logging
    error_log("Assignment request - Users: " . print_r($selected_users, true));
    error_log("Assignment request - Leads: " . print_r($selected_leads, true));

    if (empty($selected_users)) {
        redirectWithStatus('view_leads.php', 'error', 'Please select at least one user for assignment.');
    }

    if (empty($selected_leads)) {
        redirectWithStatus('view_leads.php', 'error', 'Please select at least one lead for assignment.');
    }

    // Sanitize inputs
    $selected_users = array_map('intval', array_filter($selected_users));
    $selected_leads = array_map('intval', array_filter($selected_leads));

    try {
        $pdo->beginTransaction();

        $success_count = 0;
        $error_leads = [];

        // Assign each lead to the first selected user (simple assignment)
        $primary_user_id = $selected_users[0];

        foreach ($selected_leads as $lead_id) {
            // Check if lead exists first
            $check_stmt = $pdo->prepare("SELECT id FROM shippment_lead WHERE id = ?");
            $check_stmt->execute([$lead_id]);

            if ($check_stmt->fetchColumn()) {
                // Update the lead assignment
                $stmt = $pdo->prepare("UPDATE shippment_lead SET user_id = ? WHERE id = ?");
                if ($stmt->execute([$primary_user_id, $lead_id]) && $stmt->rowCount() > 0) {
                    $success_count++;
                } else {
                    $error_leads[] = $lead_id;
                }
            } else {
                $error_leads[] = $lead_id;
            }
        }

        $pdo->commit();

        if ($success_count > 0) {
            $message = "Successfully assigned $success_count lead(s) to the selected user.";
            if (!empty($error_leads)) {
                $message .= " Failed to assign leads: " . implode(', ', $error_leads);
            }
            redirectWithStatus('view_leads.php', 'success', $message);
        } else {
            redirectWithStatus('view_leads.php', 'error', "Failed to assign any leads. Please check if the leads exist.");
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Lead assignment error: " . $e->getMessage());
        redirectWithStatus('view_leads.php', 'error', "Database error: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General assignment error: " . $e->getMessage());
        redirectWithStatus('view_leads.php', 'error', "An error occurred: " . $e->getMessage());
    }
}

// Handle multiple email sending request (native mail())
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

    $from_name = $logged_in_name;
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    foreach ($to_emails as $email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (mail($email, $subject, $message_body, $headers)) {
                $success_count++;
            } else {
                $error_emails[] = $email;
            }
        }
    }

    if ($success_count > 0) {
        $message = "Successfully sent email to $success_count recipient(s).";
        if (!empty($error_emails)) {
            $message .= " Failed to send to: " . implode(', ', $error_emails);
        }
        redirectWithStatus('view_leads.php', 'success', $message);
    } else {
        redirectWithStatus('view_leads.php', 'error', "Failed to send email to all selected recipients.");
    }
}

// Handle search and filter parameters
$search_query = $_GET['search_query'] ?? '';
$filter_quote_date = $_GET['filter_quote_date'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

// Handle the array of selected statuses from the new multi-select filter
$filter_statuses = $_GET['filter_status'] ?? [];
if (empty($filter_statuses) || in_array('All', $filter_statuses)) {
    // If no statuses are selected, default to showing all leads
    $filter_statuses = ['All'];
}



// Fetch leads from the database
$leads = [];
try {
    $sql = "SELECT *,
                    DATE_FORMAT(shippment_lead.shippment_date, '%Y-%m-%d') AS formatted_shippment_date,
                    DATE_FORMAT(shippment_lead.quote_date, '%Y-%m-%d') AS formatted_quote_date,
                    DATE_FORMAT(shippment_lead.created_at, '%Y-%m-%d %H:%i:%s') AS formatted_created_at
            FROM shippment_lead WHERE 1=1";

    $params = [];

    if ($is_user_logged_in && $logged_in_user_id !== null) {
        $sql .= " AND shippment_lead.user_id = :current_user_id";
        $params[':current_user_id'] = $logged_in_user_id;
    }

    if (!empty($search_query)) {
        $search_conditions = [];
        $search_value = '%' . htmlspecialchars($search_query) . '%';
        $search_conditions[] = "CAST(shippment_lead.id AS CHAR) LIKE :search_query_id";
        $params[':search_query_id'] = $search_value;
        $search_conditions[] = "shippment_lead.name LIKE :search_query_name";
        $params[':search_query_name'] = $search_value;
        $search_conditions[] = "shippment_lead.email LIKE :search_query_email";
        $params[':search_query_email'] = $search_value;
        $search_conditions[] = "shippment_lead.phone LIKE :search_query_phone";
        $params[':search_query_phone'] = $search_value;
        $search_conditions[] = "CAST(shippment_lead.quote_amount AS CHAR) LIKE :search_query_quote_amount";
        $params[':search_query_quote_amount'] = $search_value;
        $search_conditions[] = "DATE_FORMAT(shippment_lead.shippment_date, '%Y-%m-%d') LIKE :search_query_shippment_date";
        $params[':search_query_shippment_date'] = $search_value;
        $search_conditions[] = "shippment_lead.status LIKE :search_query_status";
        $params[':search_query_status'] = $search_value;
        $search_conditions[] = "shippment_lead.quote_id LIKE :search_query_quote_id";
        $params[':search_query_quote_id'] = $search_value;
        $sql .= " AND (" . implode(" OR ", $search_conditions) . ")";
    }

    if (!empty($filter_quote_date)) {
        $sql .= " AND shippment_lead.quote_date = :filter_quote_date";
        $params[':filter_quote_date'] = htmlspecialchars($filter_quote_date);
    } else {
        if (!empty($filter_start_date) && !empty($filter_end_date)) {
            $sql .= " AND shippment_lead.quote_date BETWEEN :filter_start_date AND :filter_end_date";
            $params[':filter_start_date'] = htmlspecialchars($filter_start_date);
            $params[':filter_end_date'] = htmlspecialchars($filter_end_date);
        } elseif (!empty($filter_start_date)) {
            $sql .= " AND shippment_lead.quote_date >= :filter_start_date";
            $params[':filter_start_date'] = htmlspecialchars($filter_start_date);
        } elseif (!empty($filter_end_date)) {
            $sql .= " AND shippment_lead.quote_date <= :filter_end_date";
            $params[':filter_end_date'] = htmlspecialchars($filter_end_date);
        }
    }

    // New logic for multi-select status filter
    if (!empty($filter_statuses) && !in_array('All', $filter_statuses)) {
        // Use named placeholders for a PDO prepared statement to avoid issues with `implode`
        $status_placeholders = [];
        $i = 0;
        foreach ($filter_statuses as $status) {
            $placeholder = ":status_$i";
            $status_placeholders[] = $placeholder;
            $params[$placeholder] = htmlspecialchars($status);
            $i++;
        }
        $sql .= " AND shippment_lead.status IN (" . implode(',', $status_placeholders) . ")";
    }

    $sql .= " ORDER BY shippment_lead.created_at DESC, shippment_lead.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching leads: " . $e->getMessage());
}

$status_message = '';
$status_type = '';
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status_type = htmlspecialchars($_GET['status']);
    $status_message = htmlspecialchars(urldecode($_GET['message']));
}

$all_possible_statuses = ['Booked', 'Not Pick', 'Voice Mail', 'In Future Shipment', 'Qutation', 'Invalid Lead', 'Stop Lead', 'Already Booked', 'Delivered'];

// Fetch available users for assignment dropdown (admin only)
$available_users = [];
if ($is_admin_logged_in) {
    try {
        // Try different possible user table names
        $user_tables = ['users', 'local_users', 'user_accounts', 'admin_users'];

        foreach ($user_tables as $table) {
            try {
                $users_stmt = $pdo->prepare("SELECT id, name, email FROM $table ORDER BY name ASC");
                $users_stmt->execute();
                $available_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

                // If we found users, break out of the loop
                if (!empty($available_users)) {
                    break;
                }
            } catch (PDOException $e) {
                // Table doesn't exist or query failed, try next table
                continue;
            }
        }

        // If no users found in any table, create some sample users for demonstration
        if (empty($available_users)) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Insert some sample users if table is empty
                $count_stmt = $pdo->query("SELECT COUNT(*) FROM users");
                $user_count = $count_stmt->fetchColumn();

                if ($user_count == 0) {
                    $sample_users = [
                        ['John Doe', 'john.doe@mjhauling.com'],
                        ['Jane Smith', 'jane.smith@mjhauling.com'],
                        ['Mike Johnson', 'mike.johnson@mjhauling.com'],
                        ['Sarah Wilson', 'sarah.wilson@mjhauling.com']
                    ];

                    $insert_stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                    foreach ($sample_users as $user) {
                        $insert_stmt->execute($user);
                    }
                }

                // Now fetch users again
                $users_stmt = $pdo->prepare("SELECT id, name, email FROM users ORDER BY name ASC");
                $users_stmt->execute();
                $available_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                // If we can't create the table, just continue with empty users
            }
        }

    } catch (PDOException $e) {
        // Error fetching users, continue with empty array
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Leads - MJ Hauling United LLC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- New Global & Variable Styles --- */
        :root {
            --primary-color: #2c73d2;
            --primary-dark: #1a4b8c;
            --secondary-color: #28a745;
            --danger-color: #d9534f;
            --danger-dark: #c9302c;
            --info-color: #17a2b8;
            --info-dark: #138496;
            --gray-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --white: #ffffff;
            --bg-body: #f0f2f5;
            --bg-card: #ffffff;
            --text-color: #333;
            --border-color: #e0e0e0;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif; /* Modern, readable font */
            background: linear-gradient(135deg, #f0f4f8 0%, #dbe2ed 100%); /* Soft, modern gradient */
            color: #333;
            display: flex; /* Use flexbox for the body */
            flex-direction: column; /* Stack children vertically */
            align-items: center; /* Center content horizontally */
            overflow-y: auto; /* Allow the body to scroll vertically */
            padding-top: 150px; /* Adjust body padding to accommodate both navbars */
            padding-left: 25px; /* Enhanced horizontal padding */
            padding-right: 25px; /* Enhanced horizontal padding */
            position: relative;
        }
        
        * {
            box-sizing: border-box;
        }
        
        .container {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 1400px;
            margin: 20px auto;
        }

        /* --- Main Navbar Styles --- */
        .navbar {
            background: #2c7be5; /* Stronger primary blue */
            padding: 12px 30px; /* Slightly more generous padding */
            border-radius: 0;
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: center; /* Vertically align items in the navbar */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Softer shadow */
            width: 100%;
            max-width: none;
            box-sizing: border-box;
            position: fixed; /* Keep navbar fixed */
            top: 0;
            left: 0;
            z-index: 1010; /* Set a high z-index to be on top of everything */
        }
        .navbar .site-title {
            font-size: 4.5rem; /* Larger title */
            font-weight: 700;
            color: #ffffff;
            flex-grow: 1;
            margin-right: 25px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.15);
        }
        .menu-icon {
            display: none; /* Hidden by default on larger screens */
            color: #ffffff;
            font-size: 1.5rem; /* Fixed font size - was too small */
            cursor: pointer;
            padding: 10px;
            z-index: 1001;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .menu-icon:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .navbar-links {
            display: flex;
            align-items: center; /* Ensure all links and dropdowns are on the same line */
            gap: 15px; /* More spacing for links */
        }
        .navbar-links a,
        .dropdown a {
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px; /* Pill-shaped buttons */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            font-weight: 500;
            background-color: rgba(255, 255, 255, 0.15); /* Slightly transparent background */
            display: inline-flex; /* Use flexbox to align text and icon */
            align-items: center;
        }
        .navbar-links a:hover,
        .dropdown a:hover {
            background-color: rgba(255, 255, 255, 0.3); /* More opaque on hover */
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* --- Filter Bar Styles --- */
        .filter-bar {
            background: var(--light-color);
            padding: 15px 0;
            position: fixed;
            top: 80px; /* Position below the main navbar */
            left: 0;
            width: 100%;
            z-index: 1000; /* Lower z-index than the main navbar to allow dropdowns to show */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .filter-bar .container {
            background: transparent;
            padding: 0;
            margin: 0 auto;
            max-width: 1400px;
            border-radius: 0;
            box-shadow: none;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filter-form input,
        .filter-form select,
        .custom-select-trigger {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 0.9rem;
            transition: var(--transition);
            height: 42px;
            width: 100%;
            max-width: 200px;
            background-color: var(--white);
        }

        .filter-form input:focus,
        .filter-form select:focus,
        .custom-select-trigger:focus,
        .custom-select-trigger:hover {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
        }

        .date-range-group {
            background-color: var(--white);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0 5px;
            max-width: 280px;
        }

        .date-range-group input[type="date"] {
            border: none;
            max-width: 120px;
            height: 40px;
            padding: 0 5px;
            background: transparent;
        }

        .date-separator {
            color: var(--gray-color);
            font-weight: 500;
        }

        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            height: 42px;
            white-space: nowrap;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: var(--white);
            display: flex;
            align-items: center;
        }

        .filter-btn i {
            margin-right: 8px;
        }

        .primary-btn {
            background: linear-gradient(45deg, #4c8be6, #2c73d2);
        }

        .primary-btn:hover {
            background: linear-gradient(45deg, #2c73d2, #1a4b8c);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .secondary-btn {
            background: linear-gradient(45deg, #8b929a, #6c757d);
        }

        .secondary-btn:hover {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .to-from-inline{
            width:100%;
            display: flex;
            gap: 20px;
        }
        #emailModal .to-from-inline .form-group, #bulkEmailModal .to-from-inline .form-group {
            flex: 1;
            width: auto;
        }
        
        /* --- Multi-select Checkbox Dropdown Styles --- */
        .custom-select-container {
            position: relative;
            display: inline-block;
            width: 200px;
        }
        .custom-select-trigger {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .custom-select-trigger i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }
        .custom-select-trigger.active i {
            transform: rotate(180deg);
        }
        .custom-options {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-top: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 10;
            max-height: 200px;
            overflow-y: auto;
            padding: 8px;
        }
        .custom-options.open {
            display: block;
        }
        .custom-option {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }
        .custom-option:hover {
            background-color: #f1f5f9;
        }
        .selected-statuses-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1;
        }
        /* End of custom dropdown styles */

        /* --- Custom Checkbox Styles --- */
        .select-checkbox,
        .custom-option input[type="checkbox"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            position: relative;
            outline: none;
            transition: background-color 0.2s, border-color 0.2s;
            display: inline-block;
            vertical-align: middle;
            margin: 0;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .select-checkbox:checked,
        .custom-option input[type="checkbox"]:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .select-checkbox:checked::before,
        .custom-option input[type="checkbox"]:checked::before {
            content: '\f00c'; /* Font Awesome check icon */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: white;
            font-size: 10px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* For disabled checkboxes */
        .select-checkbox:disabled,
        .custom-option input[type="checkbox"]:disabled {
            cursor: not-allowed;
            background-color: #e2e8f0;
            border-color: #cbd5e1;
        }


        /* --- Additional Responsive Styles for Filter Bar --- */
        @media (max-width: 992px) {
            .filter-bar {
                top: 80px; /* Adjust for navbar height */
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            .filter-group {
                width: 100%;
                justify-content: space-between;
            }
            .filter-form input,
            .filter-form select,
            .custom-select-trigger,
            .filter-btn {
                max-width: none;
            }
            .date-range-group {
                max-width: none;
                justify-content: space-between;
                padding: 0 10px;
            }
            .date-range-group input[type="date"] {
                width: 45%;
            }
            .to-from-inline {
                flex-direction: column;
                gap: 0;
            }
        }
        @media (max-width: 768px) {
            body {
                padding-top: 140px; /* Adjusted for mobile navbar */
            }
            .filter-bar {
                top: 80px; /* Consistent with navbar height */
            }
        }
        @media (max-width: 576px) {
            body {
                padding-top: 130px; /* Adjusted for smaller mobile navbar */
            }
            .filter-bar {
                top: 70px; /* Adjusted for smaller navbar */
            }
        }

        /* --- Main Container & Status Message --- */
        .container {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1400px;
            margin: 20px auto;
        }
        .status-message {
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            font-weight: bold;
            display: <?php echo !empty($status_message) ? 'block' : 'none'; ?>;
            animation: fadeIn 0.5s forwards;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .success {
            background-color: #e6ffed;
            color: #1a7e3d;
            border: 1px solid #a8e6b9;
        }
        .error {
            background-color: #ffe6e6;
            color: #d63333;
            border: 1px solid #ffb3b3;
        }
        .warning {
            background-color: #fff8e6;
            color: #b3771a;
            border: 1px solid #ffe0b3;
        }

        /* --- Table Styles --- */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 10px;
        }

        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.02);
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 900px;
        }
        th, td {
            border: 1px solid #eee;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
            font-size: 13.5px;
        }
        th {
            background-color: #eef2f7;
            color: #4a5568;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            position: sticky;
            left: 0;
            z-index: 2;
        }
        tr:nth-child(even) {
            background-color: #f8fbfd;
        }
        tr:hover {
            background-color: #eef7ff;
            cursor: pointer;
        }
        td.actions {
            white-space: normal;
            min-width: 180px;
            text-align: center;
        }
        .actions a, .actions form {
            display:inline-block;
        }
        .actions button {
            margin-right: 8px;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.85em;
            white-space: nowrap;
            display: inline-block;
            margin-bottom: 5px;
            transition: var(--transition);
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--white);
        }
        .actions .edit-btn {
            background-color: #007bff;
        }
        .actions .edit-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .actions .email-btn {
            background-color: var(--secondary-color);
            color: white;
            margin-left: 5px;
        }
        .actions .email-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .actions .email-btn i {
            margin-right: 5px;
        }
        .no-leads {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: var(--gray-color);
            background-color: #fefefe;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .total-entries {
            text-align: left;
            margin-top: 5px;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
            padding-left: 5px;
        }
        .bulk-email-btn {
            background: linear-gradient(45deg, #17a2b8, #0f6674);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .bulk-email-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        .bulk-email-btn:hover:not(:disabled) {
            background: linear-gradient(45deg, #138496, #0e5c6a);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .bulk-email-btn i {
            margin-right: 5px;
        }

        .assign-btn {
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .assign-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        .assign-btn:hover:not(:disabled) {
            background: linear-gradient(45deg, #f7931e, #e8851c);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .assign-btn i {
            margin-right: 5px;
        }

        /* Assign Dropdown Styles */
        .assign-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .assign-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            width: 350px;
            max-height: 400px;
            overflow: hidden;
        }

        .assign-dropdown.show {
            display: block;
        }

        .dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--light-color);
            border-bottom: 1px solid var(--border-color);
        }

        .dropdown-header h4 {
            margin: 0;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .close-dropdown {
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--gray-color);
            font-weight: bold;
        }

        .close-dropdown:hover {
            color: var(--dark-color);
        }

        .users-list {
            max-height: 250px;
            overflow-y: auto;
            padding: 10px;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 5px;
        }

        .user-item:hover {
            background: var(--light-color);
        }

        .user-item.selected {
            background: rgba(44, 115, 210, 0.1);
            border: 1px solid var(--primary-color);
        }

        .user-item input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
        }

        .user-details {
            flex-grow: 1;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 2px;
        }

        .user-email {
            font-size: 0.85rem;
            color: var(--gray-color);
        }

        .dropdown-footer {
            padding: 15px;
            background: var(--light-color);
            border-top: 1px solid var(--border-color);
        }

        .btn-assign-confirm {
            width: 100%;
            padding: 10px;
            background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-assign-confirm:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .btn-assign-confirm:hover:not(:disabled) {
            background: linear-gradient(45deg, var(--primary-dark), #0f3a6b);
            transform: translateY(-1px);
        }

        .no-users-message {
            text-align: center;
            padding: 20px;
            color: var(--gray-color);
            font-style: italic;
        }

        /* Assign Modal Styles */
        .selected-items-display {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px;
            max-height: 100px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .selected-lead-item {
            background: var(--white);
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            border-left: 3px solid var(--primary-color);
            font-size: 0.9rem;
        }

        .users-selection-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px;
        }

        .user-selection-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .user-selection-item:hover {
            background: var(--light-color);
            border-color: var(--border-color);
        }

        .user-selection-item input[type="checkbox"]:checked + .user-label {
            background: rgba(44, 115, 210, 0.1);
        }

        .user-selection-item input[type="checkbox"] {
            margin-right: 12px;
            width: 18px;
            height: 18px;
        }

        .user-label {
            flex-grow: 1;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 2px;
        }

        .user-email {
            font-size: 0.85rem;
            color: var(--gray-color);
        }

        .no-users-message {
            text-align: center;
            padding: 20px;
            color: var(--gray-color);
            font-style: italic;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        footer {
            text-align: center;
            margin-top: 4rem;
            font-weight: 500;
            color: #888;
            font-size: 0.9rem;
            padding-bottom: 20px;
        }

        /* --- Email Modal Styles --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s forwards;
        }

        .modal-content {
            background-color: var(--bg-card);
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
            color: var(--primary-color);
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
        .modal-content select,
        .modal-content textarea {
            width: calc(100% - 24px);
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: var(--transition);
        }

        .modal-content input:focus,
        .modal-content select:focus,
        .modal-content textarea:focus {
            border-color: var(--primary-color);
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
            transition: var(--transition);
            font-weight: 600;
        }

        .modal-buttons .send-btn {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-buttons .send-btn:hover {
            background: linear-gradient(45deg, #27ae60, #1e8449);
            transform: translateY(-2px);
            box-shadow: 6px 8px rgba(0,0,0,0.15);
        }

        .modal-buttons .close-btn {
            background-color: var(--gray-color);
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-buttons .close-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 20px;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInTop {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        /*navabr*/
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
            z-index: 1020; /* Ensure dropdown content is on top of other elements, higher than navbar */
            border-radius: 8px;
            top: calc(100% + 1px); /* Position slightly below the main link */
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
            display: list-item;
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
                flex-direction: row; /* Keep row layout for logo and menu icon */
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                flex-wrap: wrap;
            }

            .navbar img {
                order: 1; /* Logo first */
            }

            .menu-icon {
                display: block; /* Show mobile menu icon */
                order: 2; /* Menu icon second */
            }

            .navbar-links {
                order: 3; /* Navigation links last */
                width: 100%;
                flex-direction: column;
                display: none; /* Hidden by default */
                margin-top: 15px;
                gap: 10px;
            }

            .navbar-links.active {
                display: flex; /* Show when active */
            }

            .navbar-links a {
                padding: 12px 15px;
                font-size: 1rem; /* Fixed font size - was too large */
                text-align: center;
                width: 100%;
                border-radius: 8px;
                margin-bottom: 5px;
            }

            body {
                padding-top: 120px; /* Adjust body padding */
            }

            .dropdown {
                width: 100%;
                text-align: center;
            }

            .dropdown-content {
                width: 100%;
                left: 0;
                right: 0;
                position: static;
                box-shadow: none;
                border-radius: 8px;
                margin-top: 5px;
                transform: none;
                opacity: 1;
                background-color: rgba(255, 255, 255, 0.1);
            }

            .dropdown-content a {
                padding: 10px 15px;
                border-radius: 5px;
                margin: 2px 0;
            }
        }
        @media (max-width: 480px) {
            .navbar {
                padding: 10px 15px;
            }

            .navbar img {
                width: 50px; /* Smaller logo on very small screens */
            }

            .navbar-links a {
                padding: 10px 12px;
                font-size: 0.9rem;
                text-align: center;
            }

            body {
                padding-top: 100px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>

    // Toggles the visibility of the navigation menu on smaller screens
        function toggleMenu() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }

        window.onload = function() {
            const statusDiv = document.getElementById('statusMessage');
            if (statusDiv.style.display === 'block') {
                setTimeout(() => statusDiv.style.display = 'none', 5000);
            }
        };

        function resetFilters() {
            window.location.href = 'view_leads.php';
        }

        // Email Modal Functions - Fixed Version
        function initializeEmailModal() {
            const emailModal = document.getElementById('emailModal');
            const emailButtons = document.querySelectorAll('.email-btn');
            const toEmailInput = document.getElementById('to_email');
            const customerNameHidden = document.getElementById('customer_name_hidden');
            const subjectInput = document.getElementById('subject');
            const messageBody = document.getElementById('message_body');
            const fromEmailSelect = document.getElementById('from_email');

            // Add event listeners to email buttons
            emailButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const recipientEmail = this.getAttribute('data-email-to');
                    const customerName = this.getAttribute('data-customer-name') || '';
                    const leadId = this.getAttribute('data-lead-id') || '';

                    // Populate modal fields
                    toEmailInput.value = recipientEmail;
                    customerNameHidden.value = customerName;

                    if (fromEmailSelect.options.length > 0) {
                         const firstValidOption = Array.from(fromEmailSelect.options).find(opt => opt.value !== '');
                         if (firstValidOption) {
                             fromEmailSelect.value = firstValidOption.value;
                         }
                    }

                    subjectInput.value = `Follow-up for Lead #${leadId} - ${customerName}`;

                    messageBody.value = `Dear ${customerName},

I hope this email finds you well.

Following up on the quote for your shipment.

Please let me know if you have any questions.

Best regards,
MJ Hauling United LLC Team
Call or Text: +1 (502) 390-7788
Email: info@mjhaulingunited.com`;

                    emailModal.style.display = 'flex';
                });
            });
        }

        // Function to close email modal
        function closeEmailModal() {
            const emailModal = document.getElementById('emailModal');
            emailModal.style.display = 'none';

            document.getElementById('to_email').value = '';
            document.getElementById('customer_name_hidden').value = '';
            document.getElementById('subject').value = '';
            document.getElementById('message_body').value = '';
            document.getElementById('from_email').selectedIndex = 0;
        }

        window.addEventListener('click', function(event) {
            const emailModal = document.getElementById('emailModal');
            if (event.target === emailModal) {
                closeEmailModal();
            }
        });

        // Bulk Email Modal Functions
        function initializeBulkEmailModal() {
            const bulkEmailModal = document.getElementById('bulkEmailModal');
            const bulkEmailBtn = document.getElementById('send-multiple-mail-btn');
            const closeBulkBtn = document.querySelector('#bulkEmailModal .close');
            const cancelBulkBtn = document.querySelector('#bulkEmailModal .close-btn');
            const bulkToEmailsDisplay = document.getElementById('bulk_to_emails');
            const bulkForm = document.getElementById('bulkEmailForm');
            const selectAllCheckbox = document.getElementById('select-all');
            const emailCheckboxes = document.querySelectorAll('.email-checkbox');

            function updateBulkSendButtonState() {
                const checkedCount = document.querySelectorAll('.email-checkbox:checked').length;
                bulkEmailBtn.disabled = checkedCount === 0;
            }

            selectAllCheckbox.addEventListener('change', function() {
                emailCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkSendButtonState();
            });

            emailCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkSendButtonState);
            });

            bulkEmailBtn.addEventListener('click', function() {
                const selectedEmails = Array.from(emailCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);

                if (selectedEmails.length > 0) {
                    bulkToEmailsDisplay.value = selectedEmails.join(', ');
                    bulkEmailModal.style.display = 'flex';
                }
            });

            function closeBulkModal() {
                bulkEmailModal.style.display = 'none';
                bulkToEmailsDisplay.value = '';
                document.getElementById('bulk_subject').value = '';
                document.getElementById('bulk_message_body').value = '';
                const hiddenInputs = bulkForm.querySelectorAll('input[name="selected_emails[]"]');
                hiddenInputs.forEach(input => input.remove());
            }

            closeBulkBtn.addEventListener('click', closeBulkModal);
            cancelBulkBtn.addEventListener('click', closeBulkModal);
            window.addEventListener('click', function(event) {
                if (event.target === bulkEmailModal) {
                    closeBulkModal();
                }
            });

            // Handle bulk form submission
            bulkForm.addEventListener('submit', function(e) {
                // Before submitting, append hidden inputs for each selected email
                const selectedEmails = bulkToEmailsDisplay.value.split(', ').map(email => email.trim());
                selectedEmails.forEach(email => {
                    if (email) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'selected_emails[]';
                        hiddenInput.value = email;
                        bulkForm.appendChild(hiddenInput);
                    }
                });
            });

            updateBulkSendButtonState();
        }

        // Assign Leads Modal Functions
        function initializeAssignLeadsModal() {
            const assignLeadsBtn = document.getElementById('assign-leads-btn');
            const assignModal = document.getElementById('assignLeadsModal');
            const assignForm = document.getElementById('assignLeadsForm');
            const selectedLeadsDisplay = document.getElementById('selected-leads-display');
            const assignConfirmBtn = document.getElementById('assign-confirm-btn');
            const selectAllCheckbox = document.getElementById('select-all');
            const emailCheckboxes = document.querySelectorAll('.email-checkbox');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');

            function updateAssignButtonState() {
                const checkedCount = document.querySelectorAll('.email-checkbox:checked').length;
                if (assignLeadsBtn) {
                    assignLeadsBtn.disabled = checkedCount === 0;
                }
            }

            function updateConfirmButtonState() {
                const checkedUsers = document.querySelectorAll('.user-checkbox:checked').length;
                if (assignConfirmBtn) {
                    assignConfirmBtn.disabled = checkedUsers === 0;
                }
            }

            function populateSelectedLeads() {
                const selectedLeads = Array.from(document.querySelectorAll('.email-checkbox:checked'))
                    .map(checkbox => {
                        const row = checkbox.closest('tr');
                        return {
                            id: row.cells[1].textContent.trim(),
                            name: row.cells[3].textContent.trim(),
                            email: row.cells[4].textContent.trim()
                        };
                    });

                selectedLeadsDisplay.innerHTML = '';
                selectedLeads.forEach(lead => {
                    const leadDiv = document.createElement('div');
                    leadDiv.className = 'selected-lead-item';
                    leadDiv.innerHTML = `<strong>ID: ${lead.id}</strong> - ${lead.name} (${lead.email})`;
                    selectedLeadsDisplay.appendChild(leadDiv);

                    // Add hidden input for form submission
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'selected_leads[]';
                    hiddenInput.value = lead.id;
                    assignForm.appendChild(hiddenInput);
                });
            }

            function showAssignModal() {
                // Clear previous hidden inputs
                const existingInputs = assignForm.querySelectorAll('input[name="selected_leads[]"]');
                existingInputs.forEach(input => input.remove());

                populateSelectedLeads();
                assignModal.style.display = 'flex';
                updateConfirmButtonState();
            }

            function closeAssignModal() {
                assignModal.style.display = 'none';
                // Clear user selections
                userCheckboxes.forEach(cb => cb.checked = false);
                updateConfirmButtonState();
            }

            // Make closeAssignModal globally available
            window.closeAssignModal = closeAssignModal;

            // Event listeners
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    emailCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateAssignButtonState();
                });
            }

            emailCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateAssignButtonState);
            });

            userCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateConfirmButtonState);
            });

            if (assignLeadsBtn) {
                assignLeadsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const checkedLeads = document.querySelectorAll('.email-checkbox:checked').length;
                    if (checkedLeads > 0) {
                        showAssignModal();
                    }
                });
            }

            // Handle form submission
            if (assignForm) {
                assignForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
                        .map(cb => cb.value);
                    const selectedLeads = Array.from(document.querySelectorAll('input[name="selected_leads[]"]'))
                        .map(input => input.value);

                    console.log('Selected users:', selectedUsers);
                    console.log('Selected leads:', selectedLeads);

                    if (selectedUsers.length === 0) {
                        alert('Please select at least one user.');
                        return;
                    }

                    if (selectedLeads.length === 0) {
                        alert('Please select at least one lead.');
                        return;
                    }

                    // Submit the form
                    this.submit();
                });
            }

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === assignModal) {
                    closeAssignModal();
                }
            });

            updateAssignButtonState();
        }

        // Function to validate date range and handle filter conflicts
        function initializeDateRangeValidation() {
            const exactDateInput = document.getElementById('filter_quote_date');
            const startDateInput = document.getElementById('filter_start_date');
            const endDateInput = document.getElementById('filter_end_date');
            const dateRangeGroup = document.querySelector('.date-range-group');

            function validateDateRange() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (startDate && endDate && startDate > endDate) {
                    alert('Start date cannot be later than end date. Please adjust your date range.');
                    return false;
                }
                return true;
            }

            function updateFilterVisualState() {
                const exactDate = exactDateInput.value;

                if (exactDate) {
                    dateRangeGroup.style.opacity = '0.5';
                    startDateInput.style.pointerEvents = 'none';
                    endDateInput.style.pointerEvents = 'none';
                } else {
                    dateRangeGroup.style.opacity = '1';
                    startDateInput.style.pointerEvents = 'auto';
                    endDateInput.style.pointerEvents = 'auto';
                }
            }

            exactDateInput.addEventListener('change', updateFilterVisualState);
            startDateInput.addEventListener('change', validateDateRange);
            endDateInput.addEventListener('change', validateDateRange);

            updateFilterVisualState();

            const searchForm = document.querySelector('.filter-form');
            searchForm.addEventListener('submit', function(e) {
                if (!validateDateRange()) {
                    e.preventDefault();
                }
            });
        }
        
        // Custom Multi-select Dropdown JS - Corrected Logic
        function initializeCustomSelect() {
            const trigger = document.getElementById('custom-select-trigger');
            const optionsContainer = document.getElementById('custom-options');
            const selectedTextSpan = document.getElementById('selected-statuses-text');
            const checkboxes = optionsContainer.querySelectorAll('input[type="checkbox"]');
            const allCheckbox = document.getElementById('all-status-checkbox');

            function updateDisplayedText() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                if (selected.includes('All') || selected.length === 0) {
                    selectedTextSpan.textContent = "All Status";
                } else if (selected.length > 2) {
                    selectedTextSpan.textContent = selected.length + " statuses selected";
                } else {
                    selectedTextSpan.textContent = selected.filter(val => val !== 'All').join(", ") || "All Status";
                }
            }

            trigger.addEventListener('click', function(event) {
                event.stopPropagation();
                optionsContainer.classList.toggle('open');
                trigger.classList.toggle('active');
            });

            window.addEventListener('click', function(event) {
                if (!optionsContainer.contains(event.target) && !trigger.contains(event.target)) {
                    optionsContainer.classList.remove('open');
                    trigger.classList.remove('active');
                }
            });
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.value === 'All' && this.checked) {
                        checkboxes.forEach(cb => {
                            if (cb.value !== 'All') {
                                cb.checked = false;
                            }
                        });
                    } else if (this.value !== 'All' && this.checked) {
                        if (allCheckbox.checked) {
                            allCheckbox.checked = false;
                        }
                    } else if (this.value !== 'All' && !this.checked) {
                        const anyOtherChecked = Array.from(checkboxes).some(cb => cb.checked && cb.value !== 'All');
                        if (!anyOtherChecked) {
                            allCheckbox.checked = true;
                        }
                    }
                    updateDisplayedText();
                });
            });

            // Initial setup of display text
            const initialStatuses = `<?php echo json_encode($filter_statuses); ?>`;
            const initialStatusesArray = JSON.parse(initialStatuses);
            if (initialStatusesArray.includes('All')) {
                selectedTextSpan.textContent = "All Status";
            } else if (initialStatusesArray.length > 2) {
                selectedTextSpan.textContent = initialStatusesArray.length + " statuses selected";
            } else if (initialStatusesArray.length > 0) {
                selectedTextSpan.textContent = initialStatusesArray.filter(val => val !== 'All').join(", ");
            } else {
                selectedTextSpan.textContent = "All Status";
            }
        }
        // End of custom dropdown JS

        document.addEventListener('DOMContentLoaded', function() {
            initializeEmailModal();
            initializeBulkEmailModal();
            initializeAssignLeadsModal();
            initializeDateRangeValidation();
            initializeCustomSelect();
        });
    </script>
</head>
<body>
    <div class="navbar">
        <img style="width: 60px" class="img-responsive" src="assets/img/logo/logo.png" alt="Logo">

        <!-- Mobile menu toggle button -->
        <div class="menu-icon" id="mobile-menu" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>

        <div class="navbar-links" id="navbarLinks">
            <div class="dropdown">
                <a href="#">Leads &#9662;</a>
                <div class="dropdown-content">
                    <a href="shippment_lead.php">New Lead Form</a>
                    <a href="view_leads.php">View All Leads</a>
                </div>
            </div>

            <a href="sent_mail.php">View Sent Mail </a>

            <?php if ($is_admin_logged_in): ?>
            <a href="admin.php">Dashboard </a>
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

            <a href="view_leads.php?logout=true">
                <?php echo $is_admin_logged_in ? 'Admin Logout' : ($is_user_logged_in ? htmlspecialchars($logged_in_name) . ' Logout' : 'Logout'); ?>
            </a>
        </div>
    </div>

    <div class="filter-bar">
        <div class="container">
            <form method="GET" action="view_leads.php" class="filter-form">
                <div class="filter-group">
                    <label for="search_query" class="sr-only">Search</label>
                    <input type="text" id="search_query" name="search_query" placeholder="Search leads..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="filter-group date-filter">
                    <label for="filter_quote_date" class="sr-only">Quote Date</label>
                    <input type="date" id="filter_quote_date" name="filter_quote_date" title="Exact Quote Date" value="<?php echo htmlspecialchars($filter_quote_date); ?>">
                </div>
                <div class="filter-group date-range-group">
                    <label for="filter_start_date" class="sr-only">Start Date</label>
                    <input type="date" id="filter_start_date" name="filter_start_date" title="Date Range: From" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                    <span class="date-separator">to</span>
                    <label for="filter_end_date" class="sr-only">End Date</label>
                    <input type="date" id="filter_end_date" name="filter_end_date" title="Date Range: To" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="filter_status" class="sr-only">Status</label>
                    <div class="custom-select-container">
                        <div class="custom-select-trigger" id="custom-select-trigger">
                            <span id="selected-statuses-text">All Status</span>
                            <i class="fas fa-caret-down"></i>
                        </div>
                        <div class="custom-options" id="custom-options">
                            <?php
                            $filter_statuses_param = $_GET['filter_status'] ?? [];
                            if (empty($filter_statuses_param)) {
                                $filter_statuses_param = ['All'];
                            }

                            // Display 'All Status' checkbox first
                            $isCheckedAll = in_array('All', $filter_statuses_param) ? 'checked' : '';
                            echo '<label class="custom-option">';
                            echo '<input type="checkbox" name="filter_status[]" value="All" id="all-status-checkbox" ' . $isCheckedAll . '> All Status';
                            echo '</label>';
                            
                            // Display other status checkboxes
                            $all_statuses = ['Booked', 'Not Pick', 'Voice Mail', 'In Future Shipment', 'Qutation', 'Invalid Lead', 'Stop Lead', 'Already Booked', 'Delivered','Empty'];
                            foreach ($all_statuses as $status) {
                                $isChecked = in_array($status, $filter_statuses_param) ? 'checked' : '';
                                echo '<label class="custom-option">';
                                echo '<input type="checkbox" name="filter_status[]" value="' . htmlspecialchars($status) . '" ' . $isChecked . '> ' . htmlspecialchars($status);
                                echo '</label>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="filter-group button-group">
                    <button type="submit" class="filter-btn primary-btn" title="Apply Filters"><i class="fas fa-search"></i> Search</button>
                    <button type="button" class="filter-btn secondary-btn" onclick="resetFilters()" title="Clear All Filters"><i class="fas fa-undo"></i> Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <div id="statusMessage" class="status-message <?php echo $status_type; ?>">
            <?php echo $status_message; ?>
        </div>

        <?php if (count($leads) > 0): ?>
            <div class="table-controls">
                <div class="total-entries">Total Entries: <?php echo count($leads); ?></div>
                <div style="display: flex; gap: 10px;">
                    <?php if ($is_admin_logged_in): ?>
                    <button id="assign-leads-btn" class="bulk-email-btn" disabled style="background: linear-gradient(45deg, #ff6b35, #f7931e);">
                        <i class="fas fa-user-plus"></i> Assign Email
                    </button>
                    <?php endif; ?>
                    <button id="send-multiple-mail-btn" class="bulk-email-btn" disabled>
                        <i class="fas fa-envelope"></i> Send Email to Selected
                    </button>
                </div>
            </div>
            <div class="table-responsive-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" class="select-checkbox"></th>
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
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_emails[]" value="<?php echo htmlspecialchars($lead['email']); ?>" class="email-checkbox select-checkbox" <?php echo empty($lead['email']) ? 'disabled' : ''; ?>></td>
                                <td><?php echo htmlspecialchars($lead['id']); ?></td>
                                <td><?php echo htmlspecialchars($lead['quote_id']); ?></td>
                                <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                <td>$<?php echo htmlspecialchars(number_format($lead['quote_amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($lead['formatted_quote_date']); ?></td>
                                <td><?php echo htmlspecialchars($lead['formatted_shippment_date']); ?></td>
                                <td><?php echo htmlspecialchars($lead['status']); ?></td>
                                <td class="actions">
                                    <form action="edit_lead.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($lead['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="edit-btn">View Details & Edit</button>
                                    </form>
                                    <?php if (!empty($lead['email'])): ?>
                                        <button class="email-btn"
                                                data-email-to="<?php echo htmlspecialchars($lead['email']); ?>"
                                                data-customer-name="<?php echo htmlspecialchars($lead['name']); ?>"
                                                data-lead-id="<?php echo htmlspecialchars($lead['id']); ?>"
                                                title="Send Email to <?php echo htmlspecialchars($lead['name']); ?>">
                                            <i class="fas fa-envelope"></i> Email
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-leads">No leads found matching your criteria.</p>
        <?php endif; ?>
    </div>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEmailModal()">&times;</span>
            <h3>Send Email</h3>
            <form id="emailForm" method="POST" action="view_leads.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="customer_name" id="customer_name_hidden">

            <div class="to-from-inline">
                <div class="form-group">
                    <label for="to_email">To:</label>
                    <input type="email" id="to_email" name="to_email" readonly required>
                </div>

                <div class="form-group">
                    <label for="from_email">From (Your Email):</label>
                    <select id="from_email" name="from_email" required>
                         <?php
                        $primary_email = $logged_in_email;
                        $primary_name = $logged_in_name;

                        if (!empty($primary_email)) {
                             echo '<option value="' . htmlspecialchars($primary_email) . '" selected>' . htmlspecialchars($primary_email) . ' (' . htmlspecialchars($primary_name) . ')</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required placeholder="Enter email subject">
                </div>

                <div class="form-group">
                    <label for="message_body">Message:</label>
                    <textarea id="message_body" name="message_body" required placeholder="Type your message here..."></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="close-btn" onclick="closeEmailModal()">Cancel</button>
                    <button type="submit" name="send_email" class="send-btn">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkEmailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBulkModal()">&times;</span>
            <h3>Send Bulk Email</h3>
            <form id="bulkEmailForm" method="POST" action="view_leads.php">
                <input type="hidden" name="send_email_multiple" value="1">
                
            <div class="to-from-inline">
                <div class="form-group">
                    <label for="bulk_to_emails">To:</label>
                    <input type="text" id="bulk_to_emails" name="bulk_to_emails" readonly>
                </div>
                <div class="form-group">
                    <label for="bulk_from_email">From:</label>
                     <select id="bulk_from_email" name="from_email" required>
                         <?php
                        $primary_email = $logged_in_email;
                        $primary_name = $logged_in_name;

                        if (!empty($primary_email)) {
                             echo '<option value="' . htmlspecialchars($primary_email) . '" selected>' . htmlspecialchars($primary_email) . ' (' . htmlspecialchars($primary_name) . ')</option>';
                        }

            
                        ?>
                    </select>
                </div>
            </div>

                <div class="form-group">
                    <label for="bulk_subject">Subject:</label>
                    <input type="text" id="bulk_subject" name="subject" required></input>
                </div>
                <div class="form-group">
                    <label for="bulk_message_body">Message:</label>
                    <textarea id="bulk_message_body" name="message_body" required></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="close-btn" onclick="closeBulkModal()">Cancel</button>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Leads Modal -->
    <div id="assignLeadsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h3>Assign Leads to Users</h3>
            <form id="assignLeadsForm" method="POST" action="view_leads.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="assign_leads_to_users" value="1">

                <div class="form-group">
                    <label>Selected Leads:</label>
                    <div id="selected-leads-display" class="selected-items-display">
                        <!-- Selected leads will be displayed here -->
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Users to Assign:</label>
                    <div id="users-selection-list" class="users-selection-container">
                        <?php if (!empty($available_users)): ?>
                            <?php foreach ($available_users as $user): ?>
                                <div class="user-selection-item">
                                    <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>" class="user-checkbox">
                                    <label for="user_<?php echo $user['id']; ?>" class="user-label">
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-users-message">
                                <i class="fas fa-info-circle"></i> No users available for assignment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="close-btn" onclick="closeAssignModal()">Cancel</button>
                    <button type="submit" class="send-btn" id="assign-confirm-btn" disabled>
                        <i class="fas fa-user-plus"></i> Assign to Selected Users
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer>Powered by Desired Technologies</footer>
</body>
</html>