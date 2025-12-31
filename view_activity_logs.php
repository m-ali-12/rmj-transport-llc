<?php
session_start();
// Include your database connection file
// Make sure db_connect.php has the correct database credentials and PDO connection
require_once 'db_connect.php';

// Redirect function (reused from other pages)
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Check if an admin is logged in (only admins should typically view all activity logs)
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// If not an admin, redirect them to the admin login page
if (!$is_admin_logged_in) {
    redirectWithStatus('admin.php', 'error', 'You must be logged in as an administrator to view activity logs.');
}

// --- Activity Logging (for accessing this page) ---
$logged_in_admin_id = $_SESSION['admin_id'] ?? null;
if ($logged_in_admin_id) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $activity_description = 'Admin accessed User Activity Logs page.';

        $stmt_log = $conn->prepare("
            INSERT INTO user_login_activity (user_id, activity_type, activity_description, ip_address, user_agent, timestamp)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        // Using admin_id for user_id in logs for admin activities
        $stmt_log->execute([$logged_in_admin_id, 'Admin Activity', $activity_description, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        error_log("Error logging admin activity for view_activity_logs.php: " . $e->getMessage());
        // Continue execution even if logging fails
    }
}
// --- End Activity Logging ---


// --- Filtering and Pagination Logic ---
$records_per_page = 20; // Number of records to display per page
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
$offset = ($current_page - 1) * $records_per_page;

$user_id_filter = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$activity_type_filter = filter_input(INPUT_GET, 'activity_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$date_filter = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // For daily activity

$activity_logs = [];
$total_records = 0;
$error_message = '';

try {
    // Base SQL query for fetching logs with username
    $sql_base = "SELECT ula.*, u.username
                 FROM user_login_activity ula
                 LEFT JOIN users u ON ula.user_id = u.id"; // Adjust 'users' and 'id' if your user table is different

    $params = [];
    $conditions = [];

    if ($user_id_filter) {
        $conditions[] = "ula.user_id = ?";
        $params[] = $user_id_filter;
    }
    if (!empty($activity_type_filter)) {
        $conditions[] = "ula.activity_type = ?";
        $params[] = $activity_type_filter;
    }
    if (!empty($date_filter)) {
        // Filter by date (e.g., 'YYYY-MM-DD')
        $conditions[] = "DATE(ula.timestamp) = ?";
        $params[] = $date_filter;
    }

    $where_clause = '';
    if (!empty($conditions)) {
        $where_clause = " WHERE " . implode(" AND ", $conditions);
    }

    // Get total records for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM user_login_activity ula" . $where_clause);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch actual logs with limit and offset
    $sql_fetch = $sql_base . $where_clause . " ORDER BY ula.timestamp DESC LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;

    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->execute($params);
    $activity_logs = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching activity logs: " . $e->getMessage());
    $error_message = 'Error fetching activity logs from the database: ' . $e->getMessage();
}

// Handle success/error messages from other pages (e.g., from admin.php if redirected)
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Activity Logs - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1400px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #2c7be5; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; word-wrap: break-word; }
        th { background-color: #e6f0ff; color: #2c7be5; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f8ff; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; justify-content: center; align-items: flex-end; padding: 15px; border: 1px dashed #c2dcfc; border-radius: 8px; background-color: #f8fbfd; }
        .filter-form label { font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
        .filter-form select, .filter-form input[type="number"], .filter-form input[type="date"] {
            padding: 8px; border-radius: 5px; border: 1px solid #ddd; width: 100%; box-sizing: border-box; }
        .filter-form button { padding: 8px 15px; background-color: #2c7be5; color: white; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; }
        .filter-form button:hover { background-color: #246bbd; }
        .filter-form .form-group { flex: 1; min-width: 180px; }
        .filter-form .button-group { display: flex; gap: 10px; margin-top: auto; }

        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .pagination a, .pagination span {
            padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #2c7be5; border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .pagination a:hover { background-color: #e6f0ff; }
        .pagination span.current { background-color: #2c7be5; color: white; border-color: #2c7be5; font-weight: bold; }
        .pagination span.disabled { color: #ccc; cursor: not-allowed; }

        .back-link { display: block; text-align: center; margin-top: 30px; }
        .back-link a { color: #2c7be5; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
        .status-message {
            text-align: center; padding: 16px; margin: 25px 0; border-radius: 12px; font-weight: bold;
            display: block; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-message.error { background-color: #ffcdd2; color: #d32f2f; border: 1px solid #ef9a9a; }

        @media (max-width: 768px) {
            .filter-form { flex-direction: column; align-items: stretch; }
            .filter-form .form-group { min-width: unset; width: 100%; }
            .filter-form .button-group { flex-direction: column; }
            .filter-form button { width: 100%; }
            table { min-width: 600px; } /* Ensure table remains scrollable horizontally */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Activity Logs</h1>

        <?php if (!empty($status_message)): ?>
            <div class="status-message <?php echo $status_type; ?>"><?php echo htmlspecialchars($status_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="status-message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form class="filter-form" method="GET">
            <div class="form-group">
                <label for="user_id_filter">Filter by User ID:</label>
                <input type="number" id="user_id_filter" name="user_id" value="<?php echo htmlspecialchars($user_id_filter ?? ''); ?>" placeholder="e.g., 1">
            </div>
            <div class="form-group">
                <label for="activity_type_filter">Filter by Activity Type:</label>
                <select id="activity_type_filter" name="activity_type">
                    <option value="">-- All Types --</option>
                    <?php
                    // Fetch distinct activity types from the database for the dropdown
                    try {
                        $stmt_types = $conn->query("SELECT DISTINCT activity_type FROM user_login_activity ORDER BY activity_type ASC");
                        $distinct_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($distinct_types as $type) {
                            $selected = ($activity_type_filter == $type) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($type) . "\" {$selected}>" . htmlspecialchars($type) . "</option>";
                        }
                    } catch (PDOException $e) {
                        error_log("Error fetching activity types: " . $e->getMessage());
                        // Fallback to static options if DB fetch fails
                        $static_types = ['Login', 'Logout', 'Lead Saved', 'Lead Viewed', 'Lead Updated', 'Lead Deleted', 'Admin Activity'];
                        foreach ($static_types as $type) {
                            $selected = ($activity_type_filter == $type) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($type) . "\" {$selected}>" . htmlspecialchars($type) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date_filter">Filter by Date:</label>
                <input type="date" id="date_filter" name="date" value="<?php echo htmlspecialchars($date_filter ?? ''); ?>">
            </div>
            <div class="button-group">
                <button type="submit">Apply Filters</button>
                <button type="button" onclick="window.location.href='view_activity_logs.php'">Clear Filters</button>
            </div>
        </form>

        <?php if (empty($activity_logs)): ?>
            <p style="text-align: center; font-size: 1.2em; color: #666;">No activity logs found for the selected filters.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Activity Type</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activity_logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['activity_type']); ?></td>
                            <td><?php echo htmlspecialchars($log['activity_description']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_agent']); ?></td>
                            <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&user_id=<?php echo htmlspecialchars($user_id_filter ?? ''); ?>&activity_type=<?php echo htmlspecialchars($activity_type_filter ?? ''); ?>&date=<?php echo htmlspecialchars($date_filter ?? ''); ?>">Previous</a>
                <?php else: ?>
                    <span class="disabled">Previous</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&user_id=<?php echo htmlspecialchars($user_id_filter ?? ''); ?>&activity_type=<?php echo htmlspecialchars($activity_type_filter ?? ''); ?>&date=<?php echo htmlspecialchars($date_filter ?? ''); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&user_id=<?php echo htmlspecialchars($user_id_filter ?? ''); ?>&activity_type=<?php echo htmlspecialchars($activity_type_filter ?? ''); ?>&date=<?php echo htmlspecialchars($date_filter ?? ''); ?>">Next</a>
                <?php else: ?>
                    <span class="disabled">Next</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="admin.php">Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>