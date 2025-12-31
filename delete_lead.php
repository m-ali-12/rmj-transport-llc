<?php
session_start();

// Redirect function
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}


// Database configuration (same as in save_lead.php)
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    redirectWithStatus('view_leads.php', 'error', "Database connection error: " . $e->getMessage());
}

// Check if an ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithStatus('view_leads.php', 'error', 'Invalid lead ID provided for deletion.');
}

$lead_id = $_GET['id'];

// Database Deletion
try {
    $stmt = $pdo->prepare("DELETE FROM shippment_lead WHERE id = :id");
    $stmt->execute([':id' => $lead_id]);

    if ($stmt->rowCount() > 0) {
        redirectWithStatus('view_leads.php', 'success', 'Lead (ID: ' . $lead_id . ') deleted successfully!');
    } else {
        redirectWithStatus('view_leads.php', 'error', 'Lead (ID: ' . $lead_id . ') not found or already deleted.');
    }
} catch (PDOException $e) {
    error_log("Database DELETE error: " . $e->getMessage());
    redirectWithStatus('view_leads.php', 'error', "Failed to delete lead. A database error occurred. (Error Code: DB-DEL-001)");
}
?>