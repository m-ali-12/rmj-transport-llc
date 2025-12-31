<?php
session_start();

// Redirect function
function redirectWithStatus($page, $status, $message) {
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = ''; // <--- UPDATE THIS VALUE WITH YOUR CORRECT PASSWORD

// Check authentication and get user information
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$logged_in_admin_id = $_SESSION['admin_id'] ?? null;
$logged_in_user_id = $_SESSION['user_id'] ?? null;

// Enforce login requirement
if (!$is_admin_logged_in && !$is_user_logged_in) {
    redirectWithStatus('user_login.php', 'error', 'Please log in to access this page.');
}



// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    redirectWithStatus('view_leads.php', 'error', "Database connection error: " . $e->getMessage());
}

$current_lead_id = null;
$lead_data = null;
$status = '';
$message = '';

// --- Handle POST request (when form is submitted or initially loaded from view_leads.php) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_lead_id = $_POST['id'] ?? null;
    $is_update_request = isset($_POST['send_update']);

    if ($is_update_request) {
        // === Form Submission Logic ===

        // Check CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            redirectWithStatus('view_leads.php', 'error', 'Invalid security token. Please try again.');
        }

        if (!$current_lead_id || !is_numeric($current_lead_id)) {
            redirectWithStatus('view_leads.php', 'error', 'Invalid lead ID provided for updating.');
        }

        // Verify user has permission to edit this lead before proceeding
        try {
            $permission_sql = "SELECT id, user_id FROM shippment_lead WHERE id = :id";
            $permission_params = [':id' => $current_lead_id];

            if ($is_user_logged_in && $logged_in_user_id !== null) {
                $permission_sql .= " AND user_id = :user_id";
                $permission_params[':user_id'] = $logged_in_user_id;
            }

            $permission_stmt = $pdo->prepare($permission_sql);
            $permission_stmt->execute($permission_params);
            $permission_check = $permission_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permission_check) {
                if ($is_user_logged_in && !$is_admin_logged_in) {
                    redirectWithStatus('view_leads.php', 'error', 'You do not have permission to edit this lead.');
                } else {
                    redirectWithStatus('view_leads.php', 'error', 'Lead not found for updating.');
                }
            }
        } catch (PDOException $e) {
            redirectWithStatus('view_leads.php', 'error', "Permission check failed: " . $e->getMessage());
        }

        // Collect form data
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $quote_amount = $_POST['quote_amount'] ?? 0;
        $quote_id = $_POST['quote_id'] ?? '';
        $quote_date = $_POST['quote_date'] ?? null;
        $shippment_date = $_POST['shippment_date'] ?? null;
        $status_update = $_POST['status'] ?? '';
        $year = $_POST['year'] ?? '';
        $make = $_POST['make'] ?? '';
        $model = $_POST['model'] ?? '';
        $pickup_city = $_POST['pickup_city'] ?? '';
        $pickup_state = $_POST['pickup_state'] ?? '';
        $pickup_zip = $_POST['pickup_zip'] ?? '';
        $delivery_city = $_POST['delivery_city'] ?? '';
        $delivery_state = $_POST['delivery_state'] ?? '';
        $delivery_zip = $_POST['delivery_zip'] ?? '';
        $formatted_message = $_POST['formatted_message'] ?? '';

        // Validate required fields
        if (empty($name) || empty($quote_id) || empty($quote_date) || !is_numeric($quote_amount)) {
            $status = 'error';
            $message = 'Missing required fields or invalid data. Please check and try again.';
            // We do not redirect here, so the form keeps its token and data
        } else {
            // Update the lead - CORRECTED SQL QUERY
            $sql = "UPDATE shippment_lead SET
                    name = :name, email = :email, phone = :phone, quote_amount = :quote_amount,
                    quote_id = :quote_id, quote_date = :quote_date, shippment_date = :shippment_date,
                    status = :status, year = :year, make = :make, model = :model,
                    pickup_city = :pickup_city, pickup_state = :pickup_state, pickup_zip = :pickup_zip,
                    delivery_city = :delivery_city, delivery_state = :delivery_state,
                    delivery_zip = :delivery_zip, formatted_message = :formatted_message, updated_at = NOW()
                    WHERE id = :id";

            $update_params = [
                ':name' => $name, ':email' => $email, ':phone' => $phone,
                ':quote_amount' => (float)$quote_amount, ':quote_id' => $quote_id,
                ':quote_date' => $quote_date, ':shippment_date' => $shippment_date,
                ':status' => $status_update, ':year' => $year, ':make' => $make,
                ':model' => $model, ':pickup_city' => $pickup_city,
                ':pickup_state' => $pickup_state, ':pickup_zip' => $pickup_zip,
                ':delivery_city' => $delivery_city, ':delivery_state' => $delivery_state,
                ':delivery_zip' => $delivery_zip, ':formatted_message' => $formatted_message,
                ':id' => $current_lead_id
            ];

            // Add user permission check to UPDATE query for regular users
            if ($is_user_logged_in && $logged_in_user_id !== null) {
                $sql .= " AND user_id = :user_id";
                $update_params[':user_id'] = $logged_in_user_id;
            }

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($update_params);

                $rows_affected = $stmt->rowCount();

                // Clear CSRF token after successful update
                unset($_SESSION['csrf_token']);

                if ($rows_affected > 0) {
                    redirectWithStatus('view_leads.php', 'success', 'Lead updated successfully!');
                } else {
                    if ($is_user_logged_in && !$is_admin_logged_in) {
                        redirectWithStatus('view_leads.php', 'error', 'Update failed. You may not have permission to edit this lead or the lead was not found.');
                    } else {
                        redirectWithStatus('view_leads.php', 'info', 'No changes were made to the lead.');
                    }
                }
            } catch (PDOException $e) {
                $status = 'error';
                $message = 'Database error: ' . $e->getMessage();
            }
        }
    } else {
        // === Initial Page Load Logic (for POST request from view_leads.php) ===
        if (!$current_lead_id || !is_numeric($current_lead_id)) {
            redirectWithStatus('view_leads.php', 'error', 'Invalid lead ID provided for editing.');
        }
    }
} else {
    // If a non-POST request comes in, redirect as it's an invalid way to access the page
    redirectWithStatus('view_leads.php', 'error', 'Invalid access to the edit page.');
}

// Fetch current lead data for display, only if we have a valid ID
if ($current_lead_id) {
    try {
        $sql = "SELECT * FROM shippment_lead WHERE id = :id";
        $params = [':id' => $current_lead_id];

        if ($is_user_logged_in && $logged_in_user_id !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $logged_in_user_id;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $lead_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lead_data) {
            if ($is_user_logged_in && !$is_admin_logged_in) {
                redirectWithStatus('view_leads.php', 'error', 'Lead not found or you do not have permission to edit this lead.');
            } else {
                redirectWithStatus('view_leads.php', 'error', 'Lead not found.');
            }
        }
    } catch (PDOException $e) {
        redirectWithStatus('view_leads.php', 'error', "Error fetching lead data: " . $e->getMessage());
    }
}

// Generate CSRF token for the form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lead - MJ Hauling United LLC</title>
    <link rel="stylesheet" href="assets/adminpage_css/edit_lead.css">
</head>
<body>
    <div class="navbar">
        <span class="site-title">MJ Hauling United LLC</span>
        <div class="navbar-links">
            <a href="shippment_lead.php">New Lead Form</a>
            <a href="view_leads.php">View All Leads</a>
        </div>
    </div>

    <div class="container">
        <h1>Edit Lead (ID: <?php echo htmlspecialchars($lead_data['id'] ?? ''); ?>)</h1>

        <div id="statusMessage" class="status-message"></div>

        <form id="editLeadForm" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($lead_data['id'] ?? ''); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="send_update" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($lead_data['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($lead_data['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($lead_data['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="quote_amount">Quote Amount ($):</label>
                    <input type="number" id="quote_amount" name="quote_amount" value="<?php echo htmlspecialchars($lead_data['quote_amount'] ?? ''); ?>" step="0.01" required>
                </div>
            </div>

            <div class="form-row">


                <div class="form-group">
                    <label for="quote_id">Quote ID:</label>
                    <input type="text" id="quote_id" name="quote_id" 
                    value="<?php echo htmlspecialchars($lead_data['quote_id'] ?? ''); ?>" 
                    readonly>
                </div>

                <div class="form-group">
                    <label for="quote_date">Quote Date:</label>
                    <input type="date" id="quote_date" name="quote_date" value="<?php echo htmlspecialchars($lead_data['quote_date'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="shippment_date">Shipment Date:</label>
                    <input type="date" id="shippment_date" name="shippment_date" value="<?php echo htmlspecialchars($lead_data['shippment_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label style="color: #2c73d2;font-size:21px;font-weight:700;" for="status">
                        Status:
                    </label>
                    <select style="background-color: #777777;color:white;" id="status" name="status">
                        <option value="">-- Select Status --</option>
                        <option value="Booked" <?php echo ($lead_data['status'] == 'Booked') ? 'selected' : ''; ?>>Booked</option>
                        <option value="Not Pick" <?php echo ($lead_data['status'] == 'Not Pick') ? 'selected' : ''; ?>>Not Pick</option>
                        <option value="Voice Mail" <?php echo ($lead_data['status'] == 'Voice Mail') ? 'selected' : ''; ?>>Voice Mail</option>
                        <option value="In Future Shipment" <?php echo ($lead_data['status'] == 'In Future Shipment') ? 'selected' : ''; ?>>In Future Shipment</option>
                        <option value="Qutation" <?php echo ($lead_data['status'] == 'Qutation') ? 'selected' : ''; ?>>Quotation</option>
                        <option value="Invalid Lead" <?php echo ($lead_data['status'] == 'Invalid Lead') ? 'selected' : ''; ?>>Invalid Lead</option>
                        <option value="Stop Lead" <?php echo ($lead_data['status'] == 'Stop Lead') ? 'selected' : ''; ?>>Stop Lead</option>
                        <option value="Already Booked"<?php echo ($lead_data['status'] == 'Already Booked') ? 'selected' : '';?>>Already Booked</option>

                        <option value="Delivered"<?php echo ($lead_data['status'] == 'Delivered') ? 'selected' : '';?>>Delivered</option>
                        <option value="Potenial Lead"<?php echo ($lead_data['status'] == 'Potenial Lead') ? 'selected' : '';?>>Potenial Lead</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="year">Vehicle Year:</label>
                    <input type="text" id="year" name="year" value="<?php echo htmlspecialchars($lead_data['year'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="make">Vehicle Make:</label>
                    <input type="text" id="make" name="make" value="<?php echo htmlspecialchars($lead_data['make'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="model">Vehicle Model:</label>
                    <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($lead_data['model'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="pickup_city">Pickup City:</label>
                    <input type="text" id="pickup_city" name="pickup_city" value="<?php echo htmlspecialchars($lead_data['pickup_city'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pickup_state">Pickup State:</label>
                    <input type="text" id="pickup_state" name="pickup_state" value="<?php echo htmlspecialchars($lead_data['pickup_state'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="pickup_zip">Pickup Zip:</label>
                    <input type="text" id="pickup_zip" name="pickup_zip" value="<?php echo htmlspecialchars($lead_data['pickup_zip'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="delivery_city">Delivery City:</label>
                    <input type="text" id="delivery_city" name="delivery_city" value="<?php echo htmlspecialchars($lead_data['delivery_city'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="delivery_state">Delivery State:</label>
                    <input type="text" id="delivery_state" name="delivery_state" value="<?php echo htmlspecialchars($lead_data['delivery_state'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group span-3-columns">
                    <label for="formatted_message">Formatted Message:</label>
                    <textarea id="formatted_message" name="formatted_message" rows="8"><?php echo htmlspecialchars($lead_data['formatted_message'] ?? ''); ?></textarea>
                </div>
                <div class="form-group buttons-stacked">
                    <label for="delivery_zip">Delivery Zip:</label>
                    <input type="text" id="delivery_zip" name="delivery_zip" value="<?php echo htmlspecialchars($lead_data['delivery_zip'] ?? ''); ?>">
                    <div class="form-buttons">
                        <button type="submit">Update Lead</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='view_leads.php';">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <footer>Powered by Desired Technologies</footer>
    <script>
        window.onload = function() {
            // Check for status messages in the URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlStatus = urlParams.get('status');
            const urlMessage = urlParams.get('message');
            if (urlStatus && urlMessage) {
                const statusDiv = document.getElementById('statusMessage');
                statusDiv.textContent = decodeURIComponent(urlMessage);
                statusDiv.className = `status-message ${urlStatus}`;
                statusDiv.style.display = 'block';
                setTimeout(() => statusDiv.style.display = 'none', 5000);
            }

            // Check for status messages passed from PHP on the same page
            const phpStatus = '<?php echo $status; ?>';
            const phpMessage = '<?php echo $message; ?>';
            if (phpStatus && phpMessage) {
                const statusDiv = document.getElementById('statusMessage');
                statusDiv.textContent = phpMessage;
                statusDiv.className = `status-message ${phpStatus}`;
                statusDiv.style.display = 'block';
                setTimeout(() => statusDiv.style.display = 'none', 5000);
            }
        };
    </script>
</body>
</html>