<?php
// Ensure no output before session_start()
ob_start();
session_start(); // Start the session at the very beginning

// Regenerate session ID for security on each page load
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Debug session configuration (remove in production)
error_log("Session ID: " . session_id());
error_log("Session save path: " . session_save_path());

// Redirect function
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
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
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Handle Logout for Local Users
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    // Check if the current session is a local user session
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        session_unset();
        session_destroy();
        redirectWithStatus('user_login.php', 'success', 'You have been logged out.');
    } else {
        // If it's not a local user session or no session, just redirect without message
        header('Location: user_login.php');
        exit();
    }
}

$login_error = ''; // Variable to store login error messages

// Handle Login Attempt for Local Users
if (isset($_POST['login'])) {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = $_POST['password'] ?? ''; // Don't filter password, use it raw

    if (empty($username) || empty($password)) {
        $login_error = "Username and password are required.";
    } else {
        $hashed_password_input = md5($password); // MD5 hash the input password
        
        // Debug: Log the login attempt (remove in production)
        error_log("Login attempt for username: " . $username);

        try {
            // First, test if we have any active users (debug query)
            $test_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM local_users WHERE status = 'Active'");
            $test_stmt->execute();
            $count = $test_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Active users in database: " . $count['count']);

            // Prepare and execute query to find local user
            // 🐛 FIX: Add 'phone' to the SELECT statement
            $stmt = $pdo->prepare("SELECT id, username, name, email, phone, status FROM local_users WHERE username = :username AND password = :password AND status = 'Active'");
            $stmt->execute([':username' => $username, ':password' => $hashed_password_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Debug: Check what user data was retrieved
                error_log("User found: " . print_r($user, true));
                
                // Authentication successful, set session variables for local user
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = (int)$user['id']; // Ensure integer type
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // 🐛 FIX: The phone number is now available from the corrected query
                $_SESSION['user_no'] = $user['phone'];
                
                $_SESSION['user_status'] = $user['status'];
                $_SESSION['logged_in_type'] = 'user'; // This is crucial for the logout redirect logic
                $_SESSION['last_activity'] = time(); // Record initial login time for inactivity check
                $_SESSION['login_time'] = date('Y-m-d H:i:s'); // Record login timestamp
                
                // Regenerate session ID for security after login
                session_regenerate_id(true);

                // Debug: Verify session was set
                error_log("Session set for user ID " . $user['id'] . ": " . print_r($_SESSION, true));
                
                // Verify session variables were set correctly
                if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !isset($_SESSION['user_id'])) {
                    error_log("Session variables not set properly");
                    $login_error = "Session creation failed. Please try again.";
                } else {
                    // Ensure session is written before redirect
                    session_write_close();
                    
                    // Redirect to a page accessible by local users
                    redirectWithStatus('view_leads.php', 'success', 'Welcome, ' . htmlspecialchars($user['name']) . '!');
                }
            } else {
                // Debug: Log why authentication failed
                error_log("Authentication failed for username: " . $username);
                $login_error = "Invalid username or password, or account is inactive.";
            }
        } catch (PDOException $e) {
            error_log("Local user login error: " . $e->getMessage()); // Log database error
            $login_error = "An error occurred during login. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - MJ Hauling United LLC</title>
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
            top: 60%;
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
            transition: background-color 0.3s ease, transform 0.2s ease;
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
            background-color: #f8d7da;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
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

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>
<body>
    <div class="login-container">
        <h1>User Login</h1>
        <?php if (!empty($login_error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <form method="POST" action="user_login.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <span class="password-toggle" id="passwordToggle" onclick="togglePasswordVisibility()">Show</span>
            </div>
            <button type="submit" name="login" class="login-button">Login</button>
        </form>
        <p style="margin-top: 20px; font-size: 0.9em;"><a href="admin.php" style="color: #2c73d2; text-decoration: none;">Admin Login</a></p>
    </div>
</body>
</html>