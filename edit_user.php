<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = ''; // <--- UPDATE THIS VALUE WITH YOUR CORRECT PASSWORD
// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$status_message = '';
$status_type = '';
$user = null;
$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$user_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

// Validate user_id and user_type
if (!$user_id || ($user_type !== 'admin' && $user_type !== 'local')) {
    $status_type = 'error';
    $status_message = 'Invalid user ID or type provided.';
} else {
    $table_name = ($user_type === 'admin') ? 'admin_users' : 'local_users';

    // Handle form submission for updating user
    if (isset($_POST['update_user'])) {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

        if (empty($name) || empty($email)) {
            $status_type = 'error';
            $status_message = 'Name and Email are required.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE $table_name SET name = :name, email = :email, phone = :phone, status = :status WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':status' => $status,
                    ':id' => $user_id
                ]);
                $status_type = 'success';
                $status_message = 'User details updated successfully!';
            } catch (PDOException $e) {
                error_log("Update user error: " . $e->getMessage());
                $status_type = 'error';
                $status_message = "Database error updating user. Please try again.";
            }
        }
    }

    // Fetch user data for the form
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $status_type = 'error';
            $status_message = "User not found.";
        }
    } catch (PDOException $e) {
        die("Error fetching user data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Edit User: <?php echo htmlspecialchars($user_type); ?></h1>
        
        <?php if (!empty($status_message)): ?>
            <div class="status-message <?php echo $status_type; ?>">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <form action="edit_user.php?id=<?php echo $user['id']; ?>&type=<?php echo htmlspecialchars($user_type); ?>" method="POST" class="add-user-form">
                <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                <input type="text" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                
                <select name="status">
                    <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
                
                <button type="submit" name="update_user">Update User</button>
            </form>
        <?php endif; ?>
        
        <a href="<?php echo htmlspecialchars($user_type); ?>_users.php">Back to Users</a>
    </div>
</body>
</html>