<?php

session_start();

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
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


// Database configuration
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle search and filter parameters
$search_query = $_GET['search_query'] ?? '';
$filter_ship_date = $_GET['filter_ship_date'] ?? '';
$filter_status = $_GET['filter_status'] ?? 'All';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

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

    if (!empty($filter_ship_date)) {
        $sql .= " AND shippment_lead.shippment_date = :filter_ship_date";
        $params[':filter_ship_date'] = htmlspecialchars($filter_ship_date);
    } else {
        if (!empty($filter_start_date) && !empty($filter_end_date)) {
            $sql .= " AND shippment_lead.shippment_date BETWEEN :filter_start_date AND :filter_end_date";
            $params[':filter_start_date'] = htmlspecialchars($filter_start_date);
            $params[':filter_end_date'] = htmlspecialchars($filter_end_date);
        } elseif (!empty($filter_start_date)) {
            $sql .= " AND shippment_lead.shippment_date >= :filter_start_date";
            $params[':filter_start_date'] = htmlspecialchars($filter_start_date);
        } elseif (!empty($filter_end_date)) {
            $sql .= " AND shippment_lead.shippment_date <= :filter_end_date";
            $params[':filter_end_date'] = htmlspecialchars($filter_end_date);
        }
    }

    if (!empty($filter_status) && $filter_status !== 'All') {
        $sql .= " AND shippment_lead.status = :filter_status";
        $params[':filter_status'] = htmlspecialchars($filter_status);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Leads - MJ Hauling United LLC</title>
    <style>
        /* --- New Global & Variable Styles --- */
        :root {
            --primary-color: #2c73d2;
            --primary-dark: #1a4b8c;
            --secondary-color: #28a745;
            --danger-color: #d9534f;
            --danger-dark: #c9302c;
            --gray-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --white: #ffffff;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 0;
            margin: 0;
            color: #333;
            padding-top: 130px; /* Adjusted for new fixed headers */
            line-height: 1.6;
            overflow-x: hidden;
            min-width: 320px;
        }

        * {
            box-sizing: border-box;
        }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 1400px;
            margin: 20px auto;
        }

        /* --- Main Navbar Styles --- */
        .main-navbar {
            background-color: #3c548a; /* Dark blue background from the image */
            color: #fff;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1001;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            min-height: 70px;
            display: flex;
            align-items: center;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            height: 100%;
            flex-shrink: 0;
        }

        .navbar-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .nav-menu {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 25px;
            margin-right: auto; /* Push nav links to the left */
            margin-left: 25px;
        }

        .nav-link {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 10px 0;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .nav-link:hover {
            color: #f0f0f0;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: var(--danger-dark);
            transform: translateY(-1px);
        }

        .logout-btn i {
            margin-right: 8px;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .menu-toggle .bar {
            width: 25px;
            height: 3px;
            background-color: #fff;
            margin: 4px 0;
            transition: var(--transition);
        }

        .logout-btn-mobile {
            display: none;
            color: var(--danger-color);
        }

        /* --- Filter Bar Styles --- */
        .filter-bar {
            background: #f8f9fa; /* Light gray background */
            padding: 15px 0;
            position: fixed;
            top: 70px; /* Position below the main navbar */
            left: 0;
            width: 100%;
            z-index: 1000;
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
        .filter-form select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            font-size: 0.9rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            height: 40px;
            width: 100%;
            max-width: 200px;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2);
        }

        .date-range-group {
            background-color: #fff;
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 0 5px;
            max-width: 280px;
        }

        .date-range-group input[type="date"] {
            border: none;
            max-width: 120px;
            height: 38px;
            padding: 0 5px;
            background: transparent;
        }

        .date-separator {
            color: #666;
            font-weight: 500;
        }

        .filter-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            height: 40px;
            white-space: nowrap;
        }

        .filter-btn i {
            margin-right: 8px;
        }

        .primary-btn {
            background-color: var(--primary-color);
            color: #fff;
        }

        .primary-btn:hover {
            background-color: var(--primary-dark);
        }

        .secondary-btn {
            background-color: var(--gray-color);
            color: #fff;
        }

        .secondary-btn:hover {
            background-color: #5a6268;
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


        /* --- Responsive Styles --- */
        @media (max-width: 992px) {
            body {
                padding-top: 150px;
            }
            .main-navbar {
                min-height: 60px;
                padding: 0 15px;
            }
            .navbar-logo {
                height: 40px;
            }
            .nav-menu {
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 60px; /* Position below main navbar */
                left: 0;
                background-color: #3c548a;
                height: 0;
                overflow: hidden;
                transition: height 0.3s ease-in-out;
            }
            .nav-menu.active {
                height: auto;
                padding: 10px 0;
            }
            .nav-menu .nav-item {
                width: 100%;
                text-align: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            .nav-menu .nav-link {
                display: block;
                padding: 15px;
            }
            .menu-toggle {
                display: flex;
            }
            .logout-btn {
                display: none;
            }
            .logout-btn-mobile {
                display: block;
            }
            .filter-bar {
                top: 60px;
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
        }
        @media (max-width: 768px) {
            body {
                padding-top: 150px;
            }
            .filter-bar {
                top: 60px;
            }
        }
        @media (max-width: 576px) {
            body {
                padding-top: 180px;
            }
            .filter-bar {
                top: 60px;
            }
        }

        /* --- Main Container & Status Message --- */
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
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
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.02);
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 800px;
        }
        th, td {
            border: 1px solid #eee;
            padding: 10px 12px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            white-space: normal;
        }
        th {
            background-color: #eef2f7;
            color: #4a5568;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
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
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.85em;
            white-space: nowrap;
            display: inline-block;
            margin-bottom: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border: none;
            cursor: pointer;
        }
        .actions .edit-btn {
            background-color: #007bff;
            color: white;
        }
        .actions .edit-btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }
        .actions .email-btn {
            background-color: #28a745;
            color: white;
            margin-left: 5px;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.85em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            display: inline-block;
            margin-bottom: 5px;
            white-space: nowrap;
        }
        .actions .email-btn:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        .actions .email-btn i {
            margin-right: 5px;
        }
        .no-leads {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: #777;
            background-color: #fefefe;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .total-entries {
            text-align: left;
            margin-top: 15px;
            margin-bottom: 10px;
            font-weight: bold;
            color: #555;
            padding-left: 5px;
        }

        .select-checkbox {
            margin: 0;
            cursor: pointer;
        }

        .bulk-email-btn {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: 500;
        }

        .bulk-email-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .bulk-email-btn:hover:not(:disabled) {
            background-color: #138496;
            transform: translateY(-1px);
        }

        .bulk-email-btn i {
            margin-right: 5px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        footer {
            text-align: center;
            margin-top: 4rem;
            font-weight: 600;
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
            border: 1px solid #c2dcfc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
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
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: 600;
        }

        .modal-buttons .send-btn {
            background-color: var(--secondary-color);
            color: white;
        }

        .modal-buttons .send-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .modal-buttons .close-btn {
            background-color: var(--gray-color);
            color: white;
        }

        .modal-buttons .close-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
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

Thank you for your interest in MJ Hauling United LLC's vehicle shipping services.

I wanted to follow up regarding your shipping quote request (Lead #${leadId}).

If you have any questions or would like to proceed with the booking, please don't hesitate to contact us.

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

        // Function to validate date range and handle filter conflicts
        function initializeDateRangeValidation() {
            const exactDateInput = document.getElementById('filter_ship_date');
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

            const searchForm = document.querySelector('.search-form');
            searchForm.addEventListener('submit', function(e) {
                if (!validateDateRange()) {
                    e.preventDefault();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeEmailModal();
            initializeBulkEmailModal();
            initializeDateRangeValidation();

            // New JS for hamburger menu
            const mobileMenu = document.getElementById('mobile-menu');
            const navMenu = document.getElementById('navMenu');

            mobileMenu.addEventListener('click', () => {
                navMenu.classList.toggle('active');
            });
        });
    </script>
</head>
<body>

<nav class="main-navbar">
    <div class="navbar-container">
        <a href="#" class="navbar-brand">
            <img class="navbar-logo" src="assets/img/logo/logo.png" alt="MJ Hauling United LLC">
        </a>
        <div class="menu-toggle" id="mobile-menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
        <ul class="nav-menu" id="navMenu">
            <li class="nav-item">
                <a href="shippment_lead.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> New Lead
                </a>
            </li>
            <li class="nav-item">
                <a href="?logout=true" class="nav-link logout-btn-mobile">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
        <a href="?logout=true" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="filter-bar">
    <div class="container">
        <form method="GET" action="view_leads.php" class="filter-form">
            <div class="filter-group">
                <label for="search_query" class="sr-only">Search</label>
                <input type="text" id="search_query" name="search_query" placeholder="Search leads..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <div class="filter-group date-filter">
                <label for="filter_ship_date" class="sr-only">Ship Date</label>
                <input type="date" id="filter_ship_date" name="filter_ship_date" title="Exact Shipment Date" value="<?php echo htmlspecialchars($filter_ship_date); ?>">
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
                <select id="filter_status" name="filter_status" title="Filter by Status">
                    <option value="All" <?php echo ($filter_status == 'All') ? 'selected' : ''; ?>>All Status</option>
                    <option value="Booked" <?php echo ($filter_status == 'Booked') ? 'selected' : ''; ?>>Booked</option>
                    <option value="Not Pick" <?php echo ($filter_status == 'Not Pick') ? 'selected' : ''; ?>>Not Pick</option>
                    <option value="Voice Mail" <?php echo ($filter_status == 'Voice Mail') ? 'selected' : ''; ?>>Voice Mail</option>
                    <option value="In Future Shipment" <?php echo ($filter_status == 'In Future Shipment') ? 'selected' : ''; ?>>In Future Shipment</option>
                    <option value="Quotation" <?php echo ($filter_status == 'Quotation') ? 'selected' : ''; ?>>Quotation</option>
                    <option value="Invalid Lead" <?php echo ($filter_status == 'Invalid Lead') ? 'selected' : ''; ?>>Invalid Lead</option>
                    <option value="Stop Lead" <?php echo ($filter_status == 'Stop Lead') ? 'selected' : ''; ?>>Stop Lead</option>
                    <option value="Already Booked"<?php echo ($filter_status == 'Already Booked') ? 'selected' : ''; ?>>Already Booked</option>
                    <option value="Delivered"<?php echo ($filter_status == 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                </select>
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
            <button id="send-multiple-mail-btn" class="bulk-email-btn" disabled>
                <i class="fas fa-envelope"></i> Send Email to Selected
            </button>
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
            <div class="form-group">
                <label for="bulk_subject">Subject:</label>
                <input type="text" id="bulk_subject" name="subject" required>
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


<footer>Powered by Desired Technologies</footer>
</body>
</html>