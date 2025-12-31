<?php
/**
 * Database Structure Check Script
 * 
 * This script checks if the required database columns exist and provides
 * information about the current database structure.
 * 
 * IMPORTANT: Delete this file after checking for security reasons!
 */

// Database configuration
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check - MJ Hauling United LLC</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ffeaa7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .code {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Structure Check</h1>
        
        <div class="warning">
            <strong>Security Notice:</strong> This is a diagnostic script. Delete this file after checking for security reasons!
        </div>

        <h2>Checking shippment_lead Table Structure</h2>
        
        <?php
        try {
            // Check if shippment_lead table exists and get its structure
            $stmt = $pdo->query("DESCRIBE shippment_lead");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($columns)) {
                echo '<div class="error">❌ shippment_lead table not found!</div>';
            } else {
                echo '<div class="success">✅ shippment_lead table found with ' . count($columns) . ' columns</div>';
                
                // Check for user_id column specifically
                $user_id_exists = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === 'user_id') {
                        $user_id_exists = true;
                        break;
                    }
                }
                
                if ($user_id_exists) {
                    echo '<div class="success">✅ user_id column exists in shippment_lead table</div>';
                } else {
                    echo '<div class="error">❌ user_id column is missing from shippment_lead table</div>';
                    echo '<div class="warning">
                        <strong>Fix Required:</strong> You need to add a user_id column to the shippment_lead table.
                        Run this SQL command in your database:
                        <div class="code">ALTER TABLE shippment_lead ADD COLUMN user_id INT NULL;</div>
                    </div>';
                }
                
                echo '<h3>Table Structure:</h3>';
                echo '<table>';
                echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Default']) . '</td>';
                    echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error checking table structure: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>Checking Sample Data</h2>
        
        <?php
        try {
            // Check if there are any leads in the table
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM shippment_lead");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_leads = $result['total'];
            
            echo '<div class="success">✅ Total leads in database: ' . $total_leads . '</div>';
            
            if ($total_leads > 0) {
                // Check if any leads have user_id set
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as with_user_id FROM shippment_lead WHERE user_id IS NOT NULL");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $leads_with_user_id = $result['with_user_id'];
                    
                    echo '<div class="success">✅ Leads with user_id set: ' . $leads_with_user_id . '</div>';
                    
                    if ($leads_with_user_id < $total_leads) {
                        $leads_without_user_id = $total_leads - $leads_with_user_id;
                        echo '<div class="warning">⚠️ ' . $leads_without_user_id . ' leads do not have user_id set (they will only be visible to admins)</div>';
                    }
                    
                } catch (PDOException $e) {
                    echo '<div class="error">❌ Could not check user_id data: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '<div class="warning">This likely means the user_id column does not exist.</div>';
                }
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error checking data: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>Checking User Tables</h2>
        
        <?php
        try {
            // Check admin_users table
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM admin_users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="success">✅ admin_users table: ' . $result['total'] . ' records</div>';
            
            // Check local_users table
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM local_users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="success">✅ local_users table: ' . $result['total'] . ' records</div>';
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error checking user tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>Recommendations</h2>
        
        <div class="warning">
            <h3>If user_id column is missing:</h3>
            <p>1. Add the user_id column to the shippment_lead table:</p>
            <div class="code">ALTER TABLE shippment_lead ADD COLUMN user_id INT NULL;</div>
            
            <p>2. Update existing leads to assign them to a default user (optional):</p>
            <div class="code">UPDATE shippment_lead SET user_id = 1 WHERE user_id IS NULL;</div>
            
            <p>3. Make sure new leads are created with proper user_id values</p>
        </div>

        <div class="success">
            <h3>If everything looks good:</h3>
            <p>✅ Your database structure is correct</p>
            <p>✅ The edit_lead.php permission system should work properly</p>
            <p>✅ Users will only see and edit their own leads</p>
            <p>✅ Admins will see and edit all leads</p>
        </div>

        <p><strong>Remember to delete this file after checking!</strong></p>
    </div>
</body>
</html>
