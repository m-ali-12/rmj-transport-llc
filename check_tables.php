<?php
session_start();

// Check if admin is logged in
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$is_admin_logged_in) {
    die("Access denied. Admin privileges required.");
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

echo "<h2>Database Tables:</h2>";

// Show all tables
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check for user-related tables
    echo "<h3>User-related tables structure:</h3>";
    
    $user_tables = ['users', 'local_users', 'user_accounts', 'admin_users'];
    
    foreach ($user_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<h4>Table: $table</h4>";
            try {
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>" . $column['Field'] . "</td>";
                    echo "<td>" . $column['Type'] . "</td>";
                    echo "<td>" . $column['Null'] . "</td>";
                    echo "<td>" . $column['Key'] . "</td>";
                    echo "<td>" . $column['Default'] . "</td>";
                    echo "<td>" . $column['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Show sample data
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 5");
                $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($sample_data)) {
                    echo "<h5>Sample data from $table:</h5>";
                    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
                    
                    // Header
                    echo "<tr>";
                    foreach (array_keys($sample_data[0]) as $header) {
                        echo "<th>$header</th>";
                    }
                    echo "</tr>";
                    
                    // Data
                    foreach ($sample_data as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
            } catch (PDOException $e) {
                echo "<p>Error describing table $table: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
