<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect function for clean navigation
function redirectWithStatus($page, $status_type, $status_message) {
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status_type) . '&message=' . urlencode($status_message));
    exit();
}



// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---
// Replace the values below with your actual database credentials.
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = ''; // <--- REPLACE THIS VALUE

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if the user is an logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    redirectWithStatus('login.php', 'error', 'You must be logged in to perform this action.');
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$user_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

// Validate user_id and user_type
if (!$user_id || ($user_type !== 'admin' && $user_type !== 'local')) {
    redirectWithStatus('admin.php', 'error', 'Invalid user ID or type provided.');
}

$table_name = ($user_type === 'admin') ? 'admin_users' : 'local_users';
$redirect_page = ($user_type === 'admin') ? 'admin_users.php' : 'local_users.php';

try {
    // Use a prepared statement to safely delete the user
    $stmt = $pdo->prepare("DELETE FROM $table_name WHERE id = :id");
    $stmt->execute([':id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        redirectWithStatus($redirect_page, 'success', 'User deleted successfully!');
    } else {
        redirectWithStatus($redirect_page, 'error', 'User not found or already deleted.');
    }
} catch (PDOException $e) {
    error_log("Delete user error: " . $e->getMessage());
    redirectWithStatus($redirect_page, 'error', 'Database error: Could not delete user.');
}
?>