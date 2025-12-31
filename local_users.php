<?php
// Enable error reporting for debugging. Remove this line in a production environment.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();



// --- YOU MUST UPDATE THESE DATABASE CREDENTIALS ---
$db_host = 'localhost';
$db_name = 'dbdqh70pj4ojj7';
$db_user = 'uogbxbyxq7okb';
$db_pass = '@:5`|lt+1f1@'; // <--- UPDATE THIS VALUE WITH YOUR CORRECT PASSWORD

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

// Redirect function to prevent form resubmission and clear URL
function redirectWithStatus($status, $message) {
    header('Location: local_users.php?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Handle Add New Local User Attempt
if (isset($_POST['add_local_user'])) {
    // Replaced FILTER_SANITIZE_STRING with FILTER_SANITIZE_SPECIAL_CHARS
    $new_name = filter_input(INPUT_POST, 'new_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $new_email = filter_input(INPUT_POST, 'new_email', FILTER_SANITIZE_EMAIL);
    $new_phone = filter_input(INPUT_POST, 'new_phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $new_username = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_SPECIAL_CHARS);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_UNSAFE_RAW);
    $new_status = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_SPECIAL_CHARS);

    // --- SECURITY VALIDATION ---
    $forbidden_usernames = ['admin', 'administrator', 'root', 'user', 'guest'];
    if (in_array(strtolower($new_username), $forbidden_usernames)) {
        redirectWithStatus('error', 'The username is not allowed. Please choose a different one.');
    } elseif (strlen($new_password) < 8) {
        redirectWithStatus('error', 'Password must be at least 8 characters long.');
    } elseif (empty($new_name) || empty($new_email) || empty($new_username) || empty($new_password)) {
        redirectWithStatus('error', 'Name, Email, Username, and Password are required.');
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        redirectWithStatus('error', 'Invalid email format.');
    } else {
        // NOTE: md5 is not a secure way to hash passwords.
        // It is highly recommended to use password_hash() for better security.
        $hashed_new_password = md5($new_password);

        try {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM local_users WHERE username = :username OR email = :email");
            $stmt_check->execute([':username' => $new_username, ':email' => $new_email]);
            if ($stmt_check->fetchColumn() > 0) {
                redirectWithStatus('error', 'Username or Email already exists.');
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO local_users (name, email, phone, username, password, status) VALUES (:name, :email, :phone, :username, :password, :status)");
                $stmt_insert->execute([
                    ':name' => $new_name,
                    ':email' => $new_email,
                    ':phone' => $new_phone,
                    ':username' => $new_username,
                    ':password' => $hashed_new_password,
                    ':status' => $new_status
                ]);
                redirectWithStatus('success', 'New local user added successfully!');
            }
        } catch (PDOException $e) {
            error_log("Add local user error: " . $e->getMessage());
            redirectWithStatus('error', 'Database error adding new user. Please try again.');
        }
    }
}

// Handle Update Local User Attempt
if (isset($_POST['update_local_user'])) {
    $user_id = filter_input(INPUT_POST, 'edit_id', FILTER_SANITIZE_NUMBER_INT);
    // Replaced FILTER_SANITIZE_STRING with FILTER_SANITIZE_SPECIAL_CHARS
    $name = filter_input(INPUT_POST, 'edit_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'edit_email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'edit_phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'edit_status', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (empty($name) || empty($email) || empty($user_id)) {
        redirectWithStatus('error', 'Name, Email, and User ID are required for updating.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE local_users SET name = :name, email = :email, phone = :phone, status = :status WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':status' => $status,
                ':id' => $user_id
            ]);
            redirectWithStatus('success', 'User details updated successfully!');
        } catch (PDOException $e) {
            error_log("Update local user error: " . $e->getMessage());
            redirectWithStatus('error', 'Database error updating user. Please try again.');
        }
    }
}

// Handle Delete Local User Attempt
if (isset($_GET['delete_local_user'])) {
    $user_id = filter_input(INPUT_GET, 'delete_local_user', FILTER_SANITIZE_NUMBER_INT);
    if (!empty($user_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM local_users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            if ($stmt->rowCount() > 0) {
                redirectWithStatus('success', 'Local user deleted successfully!');
            } else {
                redirectWithStatus('error', 'User not found or already deleted.');
            }
        } catch (PDOException $e) {
            error_log("Delete local user error: " . $e->getMessage());
            redirectWithStatus('error', 'Database error: Could not delete user.');
        }
    }
}

// Fetch all local users
$local_users = [];
try {
    $stmt = $pdo->query("SELECT id, name, email, phone, username, status FROM local_users ORDER BY username ASC");
    $local_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching local users: " . $e->getMessage());
}

// Handle messages from redirects
$status_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$status_type = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Local Users</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <img style="width: 60px" class="img-responsive" src="assets/img/logo/logo.png" alt="Logo">
        <div class="navbar-links">
            <a href="admin.php">Dashboard</a>
            <div class="dropdown">
                <a href="#">Users</a>
                <div class="dropdown-content">
                    <a href="admin_users.php">Admin Users</a>
                    <a href="local_users.php">Local Users</a>
                </div>
            </div>
            <a href="admin.php?logout=true">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Manage Local Users</h1>
        
        <?php if (!empty($status_message)): ?>
            <div class="status-message <?php echo $status_type; ?>">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <div class="table-header">
            <h3>Existing Local Users</h3>
            <button id="open-add-user-modal" class="btn btn-primary">Add New Local User</button>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($local_users as $user): ?>
                    <tr>
                        <td data-label="Name"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td data-label="Phone"><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td data-label="Status"><span class="status-badge status-<?php echo htmlspecialchars($user['status']); ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></td>
                        <td data-label="Actions" class="actions">
                            <button class="action-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                            <button class="action-btn delete-btn" onclick="openDeleteModal(<?php echo $user['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn add-close-btn">&times;</span>
            <h2 class="modal-title">Add New Local User</h2>
            <form action="local_users.php" method="POST" class="add-user-form">
                <input type="text" name="new_name" placeholder="Name" required>
                <input type="email" name="new_email" placeholder="Email" required>
                <input type="text" name="new_phone" placeholder="Phone">
                <input type="text" name="new_username" placeholder="Username" required>
                <input type="password" name="new_password" placeholder="Password" required>
                <select name="new_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="submit" name="add_local_user" class="btn btn-submit">Add Local User</button>
            </form>
        </div>
    </div>

    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn edit-close-btn">&times;</span>
            <h2 class="modal-title">Edit User</h2>
            <form action="local_users.php" method="POST" class="add-user-form">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="text" name="edit_name" id="edit_name" placeholder="Name" required>
                <input type="email" name="edit_email" id="edit_email" placeholder="Email" required>
                <input type="text" name="edit_phone" id="edit_phone" placeholder="Phone">
                <input type="text" name="edit_username" id="edit_username" placeholder="Username">
                <select name="edit_status" id="edit_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button type="submit" name="update_local_user" class="btn btn-submit">Update User</button>
            </form>
        </div>
    </div>

    <div id="delete-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn delete-close-btn">&times;</span>
            <h2 class="modal-title">Confirm Deletion</h2>
            <p>Are you sure you want to delete this user?</p>
            <div class="modal-actions">
                <a id="confirm-delete-link" href="#" class="btn btn-danger">Yes, Delete</a>
                <button class="btn btn-secondary delete-close-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        var addModal = document.getElementById("add-user-modal");
        var editModal = document.getElementById("edit-user-modal");
        var deleteModal = document.getElementById("delete-user-modal");

        document.getElementById("open-add-user-modal").onclick = function() { addModal.style.display = "block"; }
        document.querySelector(".add-close-btn").onclick = function() { addModal.style.display = "none"; }
        document.querySelector(".edit-close-btn").onclick = function() { editModal.style.display = "none"; }
        document.querySelector(".delete-close-btn").onclick = function() { deleteModal.style.display = "none"; }
        
        window.onclick = function(event) {
            if (event.target == addModal) { addModal.style.display = "none"; }
            if (event.target == editModal) { editModal.style.display = "none"; }
            if (event.target == deleteModal) { deleteModal.style.display = "none"; }
        }

        function openEditModal(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_status').value = user.status;
            editModal.style.display = "block";
        }

        function openDeleteModal(userId) {
            document.getElementById('confirm-delete-link').href = 'local_users.php?delete_local_user=' + userId;
            deleteModal.style.display = "block";
        }
    </script>
</body>
</html>
