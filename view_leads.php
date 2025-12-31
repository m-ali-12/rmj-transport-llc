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

// Function to save email to database
function saveEmailToDatabase($pdo, $from_email, $to_email, $subject, $message_body, $logged_in_user_id = null, $logged_in_admin_id = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO sent_emails (from_email, to_email, subject, message_body, sent_at) VALUES (?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$from_email, $to_email, $subject, $message_body]);
        
        if ($result) {
            error_log("Email saved to database: From: $from_email, To: $to_email, Subject: $subject");
            return true;
        } else {
            error_log("Failed to save email to database");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error while saving email: " . $e->getMessage());
        return false;
    }
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
        // Save email to database after successful sending
        $db_saved = saveEmailToDatabase($pdo, $from_email, $to_email, $full_subject, $message_body, $logged_in_user_id, $logged_in_admin_id);
        
        $success_message = 'Email sent successfully to ' . htmlspecialchars($customer_name ?: $to_email) . '!';
        if (!$db_saved) {
            $success_message .= ' (Note: Email was sent but not logged in database)';
        }
        
        redirectWithStatus('view_leads.php', 'success', $success_message);
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
                $message .= "Already this leads is assign to the selected user: " . implode(', ', $error_leads);
            }
            redirectWithStatus('view_leads.php', 'success', $message);
        } else {
            redirectWithStatus('view_leads.php', 'error', "Already assign this leads to the selected user. ");
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
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithStatus('view_leads.php', 'error', 'Invalid security token. Please try again.');
    }

    $to_emails = $_POST['selected_emails'] ?? [];
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);

    // Debug logging
    error_log("Bulk email request - To emails: " . print_r($to_emails, true));
    error_log("Bulk email request - Subject: " . $subject);
    error_log("Bulk email request - From email: " . $from_email);

    if (empty($to_emails) || empty($subject) || empty($message_body) || empty($from_email)) {
        redirectWithStatus('view_leads.php', 'error', 'Error: Please select at least one recipient and provide a subject, message, and a "from" email address.');
    }

    $success_count = 0;
    $error_emails = [];
    $db_save_count = 0;

    $from_name = $logged_in_name;
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Prepare subject with company name
    $full_subject = "[MJ Hauling] " . $subject;

    foreach ($to_emails as $email) {
        $email = trim($email); // Remove any whitespace
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (mail($email, $full_subject, $message_body, $headers)) {
                $success_count++;
                
                // Save each successful email to database
                if (saveEmailToDatabase($pdo, $from_email, $email, $full_subject, $message_body, $logged_in_user_id, $logged_in_admin_id)) {
                    $db_save_count++;
                }
            } else {
                $error_emails[] = $email;
            }
        } else {
            $error_emails[] = $email; // Add invalid emails to error list
        }
    }

    if ($success_count > 0) {
        $message = "Successfully sent email to $success_count recipient(s).";
        if ($db_save_count < $success_count) {
            $message .= " ($db_save_count emails logged in database)";
        }
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
if (empty($filter_statuses)) {
    // If no statuses are selected, default to showing all leads
    $filter_statuses = ['All'];
}

// Handle sorting parameters - NEW ADDITION
$sort_order = $_GET['sort_order'] ?? 'desc'; // Default to newest first (desc)
$valid_sort_orders = ['asc', 'desc'];
if (!in_array($sort_order, $valid_sort_orders)) {
    $sort_order = 'desc';
}

// NEW: Handle leads per page setting with session persistence
$valid_per_page_options = [100, 200, 300, 400, 'all'];
$leads_per_page_param = $_GET['per_page'] ?? null;

// Check if user changed the per_page setting
if ($leads_per_page_param !== null && in_array($leads_per_page_param, $valid_per_page_options)) {
    $_SESSION['leads_per_page'] = $leads_per_page_param;
}

// Get the current setting from session or use default
$leads_per_page_setting = $_SESSION['leads_per_page'] ?? 100;

// Validate session value
if (!in_array($leads_per_page_setting, $valid_per_page_options)) {
    $leads_per_page_setting = 100;
    $_SESSION['leads_per_page'] = $leads_per_page_setting;
}

// Set actual leads per page for database query
if ($leads_per_page_setting === 'all') {
    $leads_per_page = PHP_INT_MAX; // Very large number to get all records
    $use_pagination = false;
} else {
    $leads_per_page = intval($leads_per_page_setting);
    $use_pagination = true;
}

// PAGINATION SETUP
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $leads_per_page;

// First, get the total count of leads (without LIMIT)
$total_leads = 0;
try {
    $count_sql = "SELECT COUNT(*) FROM shippment_lead WHERE 1=1";
    $count_params = [];

    if ($is_user_logged_in && $logged_in_user_id !== null) {
        $count_sql .= " AND shippment_lead.user_id = :current_user_id";
        $count_params[':current_user_id'] = $logged_in_user_id;
    }

    if (!empty($search_query)) {
        $search_conditions = [];
        $search_value = '%' . htmlspecialchars($search_query) . '%';
        $search_conditions[] = "CAST(shippment_lead.id AS CHAR) LIKE :search_query_id";
        $count_params[':search_query_id'] = $search_value;
        $search_conditions[] = "shippment_lead.name LIKE :search_query_name";
        $count_params[':search_query_name'] = $search_value;
        $search_conditions[] = "shippment_lead.email LIKE :search_query_email";
        $count_params[':search_query_email'] = $search_value;
        $search_conditions[] = "shippment_lead.phone LIKE :search_query_phone";
        $count_params[':search_query_phone'] = $search_value;
        $search_conditions[] = "CAST(shippment_lead.quote_amount AS CHAR) LIKE :search_query_quote_amount";
        $count_params[':search_query_quote_amount'] = $search_value;
        $search_conditions[] = "DATE_FORMAT(shippment_lead.shippment_date, '%Y-%m-%d') LIKE :search_query_shippment_date";
        $count_params[':search_query_shippment_date'] = $search_value;
        $search_conditions[] = "shippment_lead.status LIKE :search_query_status";
        $count_params[':search_query_status'] = $search_value;
        $search_conditions[] = "shippment_lead.quote_id LIKE :search_query_quote_id";
        $count_params[':search_query_quote_id'] = $search_value;
        $count_sql .= " AND (" . implode(" OR ", $search_conditions) . ")";
    }

    if (!empty($filter_quote_date)) {
        $count_sql .= " AND shippment_lead.quote_date = :filter_quote_date";
        $count_params[':filter_quote_date'] = htmlspecialchars($filter_quote_date);
    } else {
        if (!empty($filter_start_date) && !empty($filter_end_date)) {
            $count_sql .= " AND shippment_lead.quote_date BETWEEN :filter_start_date AND :filter_end_date";
            $count_params[':filter_start_date'] = htmlspecialchars($filter_start_date);
            $count_params[':filter_end_date'] = htmlspecialchars($filter_end_date);
        } elseif (!empty($filter_start_date)) {
            $count_sql .= " AND shippment_lead.quote_date >= :filter_start_date";
            $count_params[':filter_start_date'] = htmlspecialchars($filter_start_date);
        } elseif (!empty($filter_end_date)) {
            $count_sql .= " AND shippment_lead.quote_date <= :filter_end_date";
            $count_params[':filter_end_date'] = htmlspecialchars($filter_end_date);
        }
    }

    // Status filter for count query
    if (!empty($filter_statuses) && !in_array('All', $filter_statuses)) {
        $valid_statuses = array_filter($filter_statuses, function($status) {
            return $status !== 'All';
        });

        if (!empty($valid_statuses)) {
            $has_empty_status = in_array('Empty', $valid_statuses);
            $non_empty_statuses = array_filter($valid_statuses, function($status) {
                return $status !== 'Empty';
            });

            $status_conditions = [];

            if (!empty($non_empty_statuses)) {
                $status_placeholders = [];
                $i = 0;
                foreach ($non_empty_statuses as $status) {
                    $placeholder = ":count_status_$i";
                    $status_placeholders[] = $placeholder;
                    $count_params[$placeholder] = htmlspecialchars($status);
                    $i++;
                }
                $status_conditions[] = "shippment_lead.status IN (" . implode(',', $status_placeholders) . ")";
            }

            if ($has_empty_status) {
                $status_conditions[] = "(shippment_lead.status IS NULL OR shippment_lead.status = '')";
            }

            if (!empty($status_conditions)) {
                $count_sql .= " AND (" . implode(' OR ', $status_conditions) . ")";
            }
        }
    }

    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_leads = $count_stmt->fetchColumn();

} catch (PDOException $e) {
    die("Error counting leads: " . $e->getMessage());
}

// Calculate total pages
if ($use_pagination) {
    $total_pages = ceil($total_leads / $leads_per_page);
} else {
    $total_pages = 1;
    $current_page = 1;
}

// Fetch leads from the database with pagination
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

    // Status filter for main query
    if (!empty($filter_statuses) && !in_array('All', $filter_statuses)) {
        $valid_statuses = array_filter($filter_statuses, function($status) {
            return $status !== 'All';
        });

        if (!empty($valid_statuses)) {
            $has_empty_status = in_array('Empty', $valid_statuses);
            $non_empty_statuses = array_filter($valid_statuses, function($status) {
                return $status !== 'Empty';
            });

            $status_conditions = [];

            if (!empty($non_empty_statuses)) {
                $status_placeholders = [];
                $i = 0;
                foreach ($non_empty_statuses as $status) {
                    $placeholder = ":status_$i";
                    $status_placeholders[] = $placeholder;
                    $params[$placeholder] = htmlspecialchars($status);
                    $i++;
                }
                $status_conditions[] = "shippment_lead.status IN (" . implode(',', $status_placeholders) . ")";
            }

            if ($has_empty_status) {
                $status_conditions[] = "(shippment_lead.status IS NULL OR shippment_lead.status = '')";
            }

            if (!empty($status_conditions)) {
                $sql .= " AND (" . implode(' OR ', $status_conditions) . ")";
            }
        }
    }

    // Add ORDER BY clause with dynamic sort order - UPDATED
    if ($sort_order === 'asc') {
        $sql .= " ORDER BY shippment_lead.created_at ASC, shippment_lead.id ASC";
    } else {
        $sql .= " ORDER BY shippment_lead.created_at DESC, shippment_lead.id DESC";
    }
    
    // Add LIMIT only if using pagination
    if ($use_pagination) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Bind pagination parameters only if using pagination
    if ($use_pagination) {
        $stmt->bindValue(':limit', $leads_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    
    // Bind other parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching leads: " . $e->getMessage());
}

// Pagination helper function - UPDATED to include per_page parameter
function buildPaginationUrl($page, $search_query = '', $filter_quote_date = '', $filter_start_date = '', $filter_end_date = '', $filter_statuses = [], $sort_order = 'desc', $per_page = null) {
    global $leads_per_page_setting;
    
    $params = ['page' => $page];
    
    if (!empty($search_query)) {
        $params['search_query'] = $search_query;
    }
    if (!empty($filter_quote_date)) {
        $params['filter_quote_date'] = $filter_quote_date;
    }
    if (!empty($filter_start_date)) {
        $params['filter_start_date'] = $filter_start_date;
    }
    if (!empty($filter_end_date)) {
        $params['filter_end_date'] = $filter_end_date;
    }
    if (!empty($filter_statuses)) {
        foreach ($filter_statuses as $status) {
            $params['filter_status[]'] = $status;
        }
    }
    if (!empty($sort_order)) {
        $params['sort_order'] = $sort_order;
    }
    
    // Always include per_page to maintain the setting
    $params['per_page'] = $per_page ?? $leads_per_page_setting;
    
    return 'view_leads.php?' . http_build_query($params);
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
    <link rel="stylesheet" href="lead_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add your additional CSS file here -->
    <link rel="stylesheet" href="assets/adminpage_css/view_leads.css">

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

        // Sort Direction Dropdown Functions
        function toggleSortDropdown() {
            const dropdown = document.getElementById('sortDropdownContent');
            const button = document.getElementById('sortDropdownBtn');
            
            dropdown.classList.toggle('show');
            button.classList.toggle('active');
            
            // Close other dropdowns
            const perPageDropdown = document.getElementById('perPageDropdownContent');
            if (perPageDropdown) {
                perPageDropdown.classList.remove('show');
                document.getElementById('perPageDropdownBtn').classList.remove('active');
            }
        }

        function changeSortOrder(newOrder) {
            // Show loading state
            const button = document.getElementById('sortDropdownBtn');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sorting...';
            button.disabled = true;
            
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort_order', newOrder);
            currentUrl.searchParams.set('page', '1'); // Reset to first page
            
            // Navigate to new URL
            window.location.href = currentUrl.toString();
        }

        // Per Page Selector Functions
        function togglePerPageDropdown() {
            const dropdown = document.getElementById('perPageDropdownContent');
            const button = document.getElementById('perPageDropdownBtn');
            
            dropdown.classList.toggle('show');
            button.classList.toggle('active');
            
            // Close other dropdowns
            const sortDropdown = document.getElementById('sortDropdownContent');
            if (sortDropdown) {
                sortDropdown.classList.remove('show');
                document.getElementById('sortDropdownBtn').classList.remove('active');
            }
        }

        function changePerPage(newPerPage) {
            // Show loading state
            const button = document.getElementById('perPageDropdownBtn');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            button.disabled = true;
            
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('per_page', newPerPage);
            currentUrl.searchParams.set('page', '1'); // Reset to first page
            
            // Navigate to new URL
            window.location.href = currentUrl.toString();
        }

        // Scroll Functions
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }

        // Enhanced dropdown close functionality
        window.addEventListener('click', function(event) {
            const sortDropdown = document.getElementById('sortDropdownContent');
            const sortButton = document.getElementById('sortDropdownBtn');
            const perPageDropdown = document.getElementById('perPageDropdownContent');
            const perPageButton = document.getElementById('perPageDropdownBtn');
            
            // Close sort dropdown if clicked outside
            if (sortButton && !sortButton.contains(event.target) && sortDropdown) {
                sortDropdown.classList.remove('show');
                sortButton.classList.remove('active');
            }
            
            // Close per-page dropdown if clicked outside
            if (perPageButton && !perPageButton.contains(event.target) && perPageDropdown) {
                perPageDropdown.classList.remove('show');
                perPageButton.classList.remove('active');
            }
        });

        // Add keyboard support for dropdowns
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Close all dropdowns on Escape key
                const dropdowns = ['sortDropdownContent', 'perPageDropdownContent'];
                const buttons = ['sortDropdownBtn', 'perPageDropdownBtn'];
                
                dropdowns.forEach((dropdownId, index) => {
                    const dropdown = document.getElementById(dropdownId);
                    const button = document.getElementById(buttons[index]);
                    
                    if (dropdown && dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                        if (button) button.classList.remove('active');
                    }
                });
            }
        });

        // Email Modal Functions
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
                // Clear any existing hidden inputs first
                const existingInputs = bulkForm.querySelectorAll('input[name="selected_emails[]"]');
                existingInputs.forEach(input => input.remove());

                // Before submitting, append hidden inputs for each selected email
                const selectedEmails = bulkToEmailsDisplay.value.split(', ').map(email => email.trim()).filter(email => email);

                if (selectedEmails.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one email recipient.');
                    return false;
                }

                selectedEmails.forEach(email => {
                    if (email) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'selected_emails[]';
                        hiddenInput.value = email;
                        bulkForm.appendChild(hiddenInput);
                    }
                });

                // Debug logging
                console.log('Submitting bulk email with emails:', selectedEmails);
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
        
        // Custom Multi-select Dropdown JS
        function initializeCustomSelect() {
            const trigger = document.getElementById('custom-select-trigger');
            const optionsContainer = document.getElementById('custom-options');
            const selectedTextSpan = document.getElementById('selected-statuses-text');
            const checkboxes = optionsContainer.querySelectorAll('input[type="checkbox"]');
            const allCheckbox = document.getElementById('all-status-checkbox');

            function updateDisplayedText() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                const nonAllSelected = selected.filter(val => val !== 'All');

                if (selected.includes('All') || selected.length === 0) {
                    selectedTextSpan.textContent = "All Status";
                } else if (nonAllSelected.length > 2) {
                    selectedTextSpan.textContent = nonAllSelected.length + " statuses selected";
                } else if (nonAllSelected.length > 0) {
                    selectedTextSpan.textContent = nonAllSelected.join(", ");
                } else {
                    selectedTextSpan.textContent = "All Status";
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
                        // If "All" is checked, uncheck all other checkboxes
                        checkboxes.forEach(cb => {
                            if (cb.value !== 'All') {
                                cb.checked = false;
                            }
                        });
                    } else if (this.value !== 'All' && this.checked) {
                        // If any specific status is checked, uncheck "All"
                        if (allCheckbox && allCheckbox.checked) {
                            allCheckbox.checked = false;
                        }
                    } else if (this.value !== 'All' && !this.checked) {
                        // If a specific status is unchecked, check if any others are still checked
                        const anyOtherChecked = Array.from(checkboxes).some(cb => cb.checked && cb.value !== 'All');
                        if (!anyOtherChecked && allCheckbox) {
                            // If no specific statuses are checked, check "All"
                            allCheckbox.checked = true;
                        }
                    }
                    updateDisplayedText();
                });
            });

            // Initial setup of display text
            const initialStatuses = <?php echo json_encode($filter_statuses); ?>;
            const initialStatusesArray = initialStatuses;
            const nonAllInitial = initialStatusesArray.filter(val => val !== 'All');

            if (initialStatusesArray.includes('All') || initialStatusesArray.length === 0) {
                selectedTextSpan.textContent = "All Status";
            } else if (nonAllInitial.length > 2) {
                selectedTextSpan.textContent = nonAllInitial.length + " statuses selected";
            } else if (nonAllInitial.length > 0) {
                selectedTextSpan.textContent = nonAllInitial.join(", ");
            } else {
                selectedTextSpan.textContent = "All Status";
            }
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeEmailModal();
            initializeBulkEmailModal();
            initializeAssignLeadsModal();
            initializeDateRangeValidation();
            initializeCustomSelect();
            
            // Add smooth loading transitions
            document.body.classList.add('loaded');
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
                            $all_statuses = ['Booked', 'Not Pick', 'Voice Mail', 'In Future Shipment', 'Qutation', 'Invalid Lead', 'Stop Lead', 'Already Booked', 'Delivered','Empty','Potenial Lead'];
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
                
                <!-- Hidden inputs to preserve sort order and per_page -->
                <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
                <input type="hidden" name="per_page" value="<?php echo htmlspecialchars($leads_per_page_setting); ?>">
                
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
            <!-- Results Summary -->
            <div class="results-summary">
                <div class="summary-text">
                    <?php if ($use_pagination): ?>
                        Showing <?php echo count($leads); ?> leads on this page
                    <?php else: ?>
                        Showing all <?php echo count($leads); ?> leads
                    <?php endif; ?>
                </div>
                <div class="summary-details">
                    <?php if ($use_pagination): ?>
                        Total <?php echo $total_leads; ?> leads found | Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                        | Per page: <?php echo $leads_per_page_setting === 'all' ? 'All' : $leads_per_page_setting; ?>
                    <?php else: ?>
                        Total <?php echo $total_leads; ?> leads found | Showing all results
                    <?php endif; ?>
                    | Sorted by: <?php echo $sort_order === 'asc' ? 'Oldest First' : 'Newest First'; ?>
                </div>
            </div>

            <div class="table-controls">
                <div class="left-controls">
                    <?php if ($use_pagination): ?>
                        <div class="total-entries">
                            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> 
                            (<?php echo $total_leads; ?> total entries)
                        </div>
                    <?php else: ?>
                        <div class="total-entries">
                            All <?php echo $total_leads; ?> leads displayed
                        </div>
                    <?php endif; ?>
                    
                    <!-- Sort Direction Dropdown -->
                    <div class="sort-dropdown">
                        <button type="button" class="sort-dropdown-btn" id="sortDropdownBtn" onclick="toggleSortDropdown()">
                            <i class="fas fa-sort"></i>
                            Sort: <?php echo $sort_order === 'asc' ? 'Oldest First' : 'Newest First'; ?>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="sort-dropdown-content" id="sortDropdownContent">
                            <a href="#" onclick="changeSortOrder('desc')" class="<?php echo $sort_order === 'desc' ? 'active' : ''; ?>">
                                <i class="fas fa-arrow-down"></i>
                                Newest First
                            </a>
                            <a href="#" onclick="changeSortOrder('asc')" class="<?php echo $sort_order === 'asc' ? 'active' : ''; ?>">
                                <i class="fas fa-arrow-up"></i>
                                Oldest First
                            </a>
                        </div>
                    </div>

                    <!-- Per Page Selector -->
                    <div class="per-page-selector">
                        <button type="button" class="per-page-selector-btn" id="perPageDropdownBtn" onclick="togglePerPageDropdown()">
                            <i class="fas fa-list-ol"></i>
                            Show: <?php echo $leads_per_page_setting === 'all' ? 'All' : $leads_per_page_setting; ?> per page
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="per-page-dropdown" id="perPageDropdownContent">
                            <?php foreach ([100, 200, 300, 400, 'all'] as $option): ?>
                                <a href="#" onclick="changePerPage('<?php echo $option; ?>')" class="<?php echo $leads_per_page_setting == $option ? 'active' : ''; ?>">
                                    <i class="fas fa-<?php echo $option === 'all' ? 'infinity' : 'list'; ?>"></i>
                                    <?php echo $option === 'all' ? 'Show All' : $option . ' per page'; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="right-controls">
                    <?php if ($is_admin_logged_in): ?>
                    <button id="assign-leads-btn" class="bulk-email-btn" disabled style="background: linear-gradient(45deg, #ff6b35, #f7931e);">
                        <i class="fas fa-user-plus"></i> Assign Leads
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

            <!-- Pagination - Only show if using pagination -->
            <?php if ($use_pagination && $total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?php echo (($current_page - 1) * $leads_per_page) + 1; ?> to <?php echo min($current_page * $leads_per_page, $total_leads); ?> of <?php echo $total_leads; ?> leads
                </div>
                
                <div class="pagination">
                    <?php
                    // Helper function to build pagination URLs
                    function buildPagUrl($page) {
                        global $search_query, $filter_quote_date, $filter_start_date, $filter_end_date, $filter_statuses, $sort_order, $leads_per_page_setting;
                        $params = ['page' => $page];
                        
                        if (!empty($search_query)) $params['search_query'] = $search_query;
                        if (!empty($filter_quote_date)) $params['filter_quote_date'] = $filter_quote_date;
                        if (!empty($filter_start_date)) $params['filter_start_date'] = $filter_start_date;
                        if (!empty($filter_end_date)) $params['filter_end_date'] = $filter_end_date;
                        if (!empty($filter_statuses)) {
                            foreach ($filter_statuses as $status) {
                                $params['filter_status[]'] = $status;
                            }
                        }
                        if (!empty($sort_order)) $params['sort_order'] = $sort_order;
                        $params['per_page'] = $leads_per_page_setting;
                        
                        return 'view_leads.php?' . http_build_query($params);
                    }

                    // First page
                    if ($current_page > 1) {
                        echo '<a href="' . buildPagUrl(1) . '" class="nav-btn" title="First Page"><i class="fas fa-angle-double-left"></i> First</a>';
                        echo '<a href="' . buildPagUrl($current_page - 1) . '" class="nav-btn" title="Previous Page"><i class="fas fa-angle-left"></i> Prev</a>';
                    } else {
                        echo '<span class="nav-btn disabled"><i class="fas fa-angle-double-left"></i> First</span>';
                        echo '<span class="nav-btn disabled"><i class="fas fa-angle-left"></i> Prev</span>';
                    }

                    // Page numbers with smart ellipsis
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    // Show first page if we're not starting from 1
                    if ($start_page > 1) {
                        echo '<a href="' . buildPagUrl(1) . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="ellipsis">...</span>';
                        }
                    }

                    // Show page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $current_page) {
                            echo '<span class="current">' . $i . '</span>';
                        } else {
                            echo '<a href="' . buildPagUrl($i) . '">' . $i . '</a>';
                        }
                    }

                    // Show last page if we're not ending at the last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="ellipsis">...</span>';
                        }
                        echo '<a href="' . buildPagUrl($total_pages) . '">' . $total_pages . '</a>';
                    }

                    // Next and Last page
                    if ($current_page < $total_pages) {
                        echo '<a href="' . buildPagUrl($current_page + 1) . '" class="nav-btn" title="Next Page">Next <i class="fas fa-angle-right"></i></a>';
                        echo '<a href="' . buildPagUrl($total_pages) . '" class="nav-btn" title="Last Page">Last <i class="fas fa-angle-double-right"></i></a>';
                    } else {
                        echo '<span class="nav-btn disabled">Next <i class="fas fa-angle-right"></i></span>';
                        echo '<span class="nav-btn disabled">Last <i class="fas fa-angle-double-right"></i></span>';
                    }
                    ?>
                </div>

                <?php if ($total_pages > 10): ?>
                <div class="pagination">
                    <span style="margin-right: 15px; color: #666;">Quick Jump:</span>
                    <?php
                    $jump_pages = [];
                    $interval = max(1, floor($total_pages / 10));
                    
                    for ($i = 1; $i <= $total_pages; $i += $interval) {
                        if ($i != $current_page && !in_array($i, range($current_page - 2, $current_page + 2))) {
                            $jump_pages[] = $i;
                        }
                    }
                    
                    $jump_pages = array_slice(array_unique($jump_pages), 0, 5);
                    
                    foreach ($jump_pages as $page) {
                        echo '<a href="' . buildPagUrl($page) . '" class="jump-btn">' . $page . '</a>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="results-summary">
                <div class="summary-text" style="color: #dc3545;">
                    <i class="fas fa-search"></i> No leads found matching your criteria
                </div>
                <div class="summary-details">
                    Try adjusting your search filters or <a href="view_leads.php" style="color: #007bff;">reset all filters</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scroll Controls -->
    <div class="scroll-controls">
        <button class="scroll-btn" onclick="scrollToTop()" title="Scroll to Top">
            <i class="fas fa-chevron-up"></i>
        </button>
        <button class="scroll-btn scroll-to-bottom" onclick="scrollToBottom()" title="Scroll to Bottom">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <!-- Email Modal -->
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

    <!-- Bulk Email Modal -->
    <div id="bulkEmailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBulkModal()">&times;</span>
            <h3>Send Bulk Email</h3>
            <form id="bulkEmailForm" method="POST" action="view_leads.php">
                <input type="hidden" name="send_email_multiple" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
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