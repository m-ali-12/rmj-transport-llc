<?php
session_start();

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Check if admin is logged in
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$is_admin_logged_in) {
    redirectWithStatus('view_leads.php', 'error', 'Access denied. Admin privileges required.');
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

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_leads'])) {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithStatus('assign_leads.php', 'error', 'Invalid security token. Please try again.');
    }

    $selected_users = $_POST['selected_users'] ?? [];
    $lead_ids = $_POST['lead_ids'] ?? [];

    if (empty($selected_users) || empty($lead_ids)) {
        redirectWithStatus('assign_leads.php', 'error', 'Please select at least one user and ensure leads are selected.');
    }

    try {
        $pdo->beginTransaction();
        
        $success_count = 0;
        $error_leads = [];

        foreach ($lead_ids as $lead_id) {
            foreach ($selected_users as $user_id) {
                // Update the lead to assign it to the user
                $stmt = $pdo->prepare("UPDATE shippment_lead SET user_id = ? WHERE id = ?");
                if ($stmt->execute([$user_id, $lead_id])) {
                    $success_count++;
                } else {
                    $error_leads[] = $lead_id;
                }
            }
        }

        $pdo->commit();

        if ($success_count > 0) {
            $message = "Successfully assigned " . count($lead_ids) . " lead(s) to " . count($selected_users) . " user(s).";
            if (!empty($error_leads)) {
                $message .= " Failed to assign leads: " . implode(', ', $error_leads);
            }
            redirectWithStatus('view_leads.php', 'success', $message);
        } else {
            redirectWithStatus('assign_leads.php', 'error', "Failed to assign leads to selected users.");
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        redirectWithStatus('assign_leads.php', 'error', "Database error: " . $e->getMessage());
    }
}

// Get selected leads from POST data
$selected_leads = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_leads'])) {
    foreach ($_POST['selected_leads'] as $lead_json) {
        $lead = json_decode($lead_json, true);
        if ($lead) {
            $selected_leads[] = $lead;
        }
    }
}

if (empty($selected_leads)) {
    redirectWithStatus('view_leads.php', 'error', 'No leads selected for assignment.');
}

// Fetch all local users (excluding already assigned users for these leads)
try {
    // First, get the user_ids that are already assigned to the selected leads
    $lead_ids = array_column($selected_leads, 'id');
    $placeholders = str_repeat('?,', count($lead_ids) - 1) . '?';

    $assigned_users_stmt = $pdo->prepare("SELECT DISTINCT user_id FROM shippment_lead WHERE id IN ($placeholders) AND user_id IS NOT NULL");
    $assigned_users_stmt->execute($lead_ids);
    $assigned_user_ids = $assigned_users_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Try different possible user table names
    $available_users = [];
    $user_tables = ['users', 'local_users', 'user_accounts', 'admin_users'];

    foreach ($user_tables as $table) {
        try {
            // Check if table exists and get users
            $users_sql = "SELECT id, name, email FROM $table WHERE 1=1";
            $users_params = [];

            if (!empty($assigned_user_ids)) {
                $user_placeholders = str_repeat('?,', count($assigned_user_ids) - 1) . '?';
                $users_sql .= " AND id NOT IN ($user_placeholders)";
                $users_params = $assigned_user_ids;
            }

            $users_sql .= " ORDER BY name ASC";

            $users_stmt = $pdo->prepare($users_sql);
            $users_stmt->execute($users_params);
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
        // Try to create a simple users table if it doesn't exist
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
                    ['', ''],
                    ['', ''],
                    ['', ''],
                    ['', '']
                ];

                $insert_stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                foreach ($sample_users as $user) {
                    $insert_stmt->execute($user);
                }
            }

            // Now fetch users again
            $users_sql = "SELECT id, name, email FROM users WHERE 1=1";
            $users_params = [];

            if (!empty($assigned_user_ids)) {
                $user_placeholders = str_repeat('?,', count($assigned_user_ids) - 1) . '?';
                $users_sql .= " AND id NOT IN ($user_placeholders)";
                $users_params = $assigned_user_ids;
            }

            $users_sql .= " ORDER BY name ASC";

            $users_stmt = $pdo->prepare($users_sql);
            $users_stmt->execute($users_params);
            $available_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // If we can't create the table, just continue with empty users
        }
    }

} catch (PDOException $e) {
    redirectWithStatus('view_leads.php', 'error', "Error fetching users: " . $e->getMessage());
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle status messages
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
    <title>Assign Leads to Users - MJ Hauling United LLC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c73d2;
            --primary-dark: #1a4b8c;
            --secondary-color: #28a745;
            --danger-color: #d9534f;
            --info-color: #17a2b8;
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
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #dbe2ed 100%);
            color: #333;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .header p {
            color: var(--gray-color);
            font-size: 1.1rem;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
            font-size: 1.3rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
        }
        
        /* MODIFIED CSS: This is the key change for the compact, scrollable list */
        .leads-list {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            max-height: 250px; /* Limits the height of the list */
            overflow-y: auto; /* Adds a vertical scrollbar when content exceeds the height */
        }
        
        .lead-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: var(--white);
            margin-bottom: 10px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .lead-info {
            flex-grow: 1;
        }

        .lead-info strong {
            color: var(--primary-color);
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .user-card {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .user-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .user-card.selected {
            border-color: var(--primary-color);
            background: rgba(44, 115, 210, 0.1);
        }

        .user-card input[type="checkbox"] {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 18px;
            height: 18px;
        }

        .user-info h4 {
            margin: 0 0 5px 0;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .user-info p {
            margin: 0;
            color: var(--gray-color);
            font-size: 0.9rem;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--primary-dark), #0f3a6b);
            transform: translateY(-2px);
            box-shadow: 6px 8px rgba(0,0,0,0.15);
        }

        .btn-secondary {
            background: var(--gray-color);
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .no-users {
            text-align: center;
            padding: 30px;
            color: var(--gray-color);
            font-style: italic;
        }

        .status-message {
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            font-weight: bold;
            display: <?php echo !empty($status_message) ? 'block' : 'none'; ?>;
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

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .users-grid {
                grid-template-columns: 1fr;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            .lead-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="statusMessage" class="status-message <?php echo $status_type; ?>">
            <?php echo $status_message; ?>
        </div>

        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Assign Leads to Users</h1>
            <p>Select users to assign the selected leads to. Users who already have these leads assigned are excluded.</p>
        </div>

        <div class="section">
            <h3><i class="fas fa-list"></i> Selected Leads (<?php echo count($selected_leads); ?>)</h3>
            <div class="leads-list">
                <?php foreach ($selected_leads as $lead): ?>
                    <div class="lead-item">
                        <div class="lead-info">
                            <strong>ID: <?php echo htmlspecialchars($lead['id']); ?></strong> - 
                            <?php echo htmlspecialchars($lead['name']); ?> 
                            (<?php echo htmlspecialchars($lead['email']); ?>)
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" action="assign_leads.php" id="assignForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="assign_leads" value="1">
            
            <?php foreach ($selected_leads as $lead): ?>
                <input type="hidden" name="lead_ids[]" value="<?php echo htmlspecialchars($lead['id']); ?>">
            <?php endforeach; ?>

            <div class="section">
                <h3><i class="fas fa-users"></i> Available Users</h3>
                
                <?php if (!empty($available_users)): ?>
                    <div class="users-grid">
                        <?php foreach ($available_users as $user): ?>
                            <div class="user-card" onclick="toggleUserSelection(this, <?php echo $user['id']; ?>)">
                                <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>">
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-users">
                        <i class="fas fa-info-circle"></i> No available users found. All users may already have these leads assigned.
                    </div>
                <?php endif; ?>
            </div>

            <div class="buttons">
                <a href="view_leads.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Leads
                </a>
                <?php if (!empty($available_users)): ?>
                    <button type="submit" class="btn btn-primary" id="assignBtn" disabled>
                        <i class="fas fa-check"></i> Assign Selected Users
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
        function toggleUserSelection(card, userId) {
            const checkbox = card.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
            
            updateAssignButton();
        }

        function updateAssignButton() {
            const assignBtn = document.getElementById('assignBtn');
            const checkedBoxes = document.querySelectorAll('input[name="selected_users[]"]:checked');
            
            if (assignBtn) {
                assignBtn.disabled = checkedBoxes.length === 0;
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateAssignButton);
            });

            updateAssignButton();

            // Auto-hide status message after 5 seconds
            const statusDiv = document.getElementById('statusMessage');
            if (statusDiv && statusDiv.style.display === 'block') {
                setTimeout(() => statusDiv.style.display = 'none', 5000);
            }
        });
    </script>
</body>
</html>