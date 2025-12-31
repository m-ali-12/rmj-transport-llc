<?php
session_start();

// Redirect if the user is not logged in or the session has expired
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Sent Emails";

// Determine the logged-in user's name for display
$logged_in_name = 'Guest';
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $logged_in_name = $_SESSION['admin_name'] ?? 'Admin';
} elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $logged_in_name = $_SESSION['user_name'] ?? 'User';
}

// Database connection details
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- FOR DEBUGGING: Display the server's current date and time
echo "<p style='text-align: right; color: #888; font-size: 0.8em; margin-bottom: -15px;'>Current Server Time: " . date('Y-m-d H:i:s') . "</p>";
// --- END DEBUGGING

// --- Dynamic SQL query based on user role and date filter ---
$date_filters = isset($_GET['filters']) ? $_GET['filters'] : ['all'];
if (!is_array($date_filters)) {
    $date_filters = [$date_filters];
}
$is_all_selected = in_array('all', $date_filters);

$sql = "SELECT from_email, to_email, subject, message_body, sent_at FROM sent_emails";
$where_clause = [];

// Filter by user role (non-admins only see their emails)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $user_email = $_SESSION['user_email'] ?? '';
    $where_clause[] = "from_email = '{$conn->real_escape_string($user_email)}'";
}

// Filter by date based on the checkbox selections
$date_conditions = [];
if (!$is_all_selected) {
    foreach ($date_filters as $filter) {
        switch ($filter) {
            case 'today':
                $date_conditions[] = "DATE(sent_at) = CURDATE()";
                break;
            case 'yesterday':
                $date_conditions[] = "DATE(sent_at) = CURDATE() - INTERVAL 1 DAY";
                break;
            case 'weekly':
                $date_conditions[] = "YEARWEEK(sent_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'monthly':
                $date_conditions[] = "(YEAR(sent_at) = YEAR(CURDATE()) AND MONTH(sent_at) = MONTH(CURDATE()))";
                break;
            case 'yearly':
                $date_conditions[] = "YEAR(sent_at) = YEAR(CURDATE())";
                break;
        }
    }
}

// If specific date filters are selected, combine them with OR
if (!empty($date_conditions)) {
    $where_clause[] = "(" . implode(" OR ", $date_conditions) . ")";
}

// Construct the final SQL query
if (!empty($where_clause)) {
    $sql .= " WHERE " . implode(" AND ", $where_clause);
}

$sql .= " ORDER BY sent_at DESC";
$result = $conn->query($sql);

// New query to count messages sent today for the counter
$today_sql = "SELECT COUNT(*) AS daily_count FROM sent_emails WHERE DATE(sent_at) = CURDATE()";
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $user_email = $_SESSION['user_email'] ?? '';
    $today_sql .= " AND from_email = '{$conn->real_escape_string($user_email)}'";
}
$today_result = $conn->query($today_sql);
$daily_count = 0;
if ($today_result && $today_result->num_rows > 0) {
    $row = $today_result->fetch_assoc();
    $daily_count = $row['daily_count'];
}

// Query to count total messages sent (filtered by current filter)
$total_sql = "SELECT COUNT(*) AS total_count FROM sent_emails";
$total_where_clause = [];

// Filter by user role (non-admins only see their emails)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $user_email = $_SESSION['user_email'] ?? '';
    $total_where_clause[] = "from_email = '{$conn->real_escape_string($user_email)}'";
}

// Apply the same date filters as the main query
$total_date_conditions = [];
if (!$is_all_selected) {
    foreach ($date_filters as $filter) {
        switch ($filter) {
            case 'today':
                $total_date_conditions[] = "DATE(sent_at) = CURDATE()";
                break;
            case 'yesterday':
                $total_date_conditions[] = "DATE(sent_at) = CURDATE() - INTERVAL 1 DAY";
                break;
            case 'weekly':
                $total_date_conditions[] = "YEARWEEK(sent_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'monthly':
                $total_date_conditions[] = "(YEAR(sent_at) = YEAR(CURDATE()) AND MONTH(sent_at) = MONTH(CURDATE()))";
                break;
            case 'yearly':
                $total_date_conditions[] = "YEAR(sent_at) = YEAR(CURDATE())";
                break;
        }
    }
}

if (!empty($total_date_conditions)) {
    $total_where_clause[] = "(" . implode(" OR ", $total_date_conditions) . ")";
}

if (!empty($total_where_clause)) {
    $total_sql .= " WHERE " . implode(" AND ", $total_where_clause);
}

$total_result = $conn->query($total_sql);
$total_count = 0;
if ($total_result && $total_result->num_rows > 0) {
    $row = $total_result->fetch_assoc();
    $total_count = $row['total_count'];
}

// Function to clean up subject line
function cleanSubject($subject) {
    // Remove bracketed company names at the beginning
    $subject = preg_replace('/^\[.*?\]\s*/', '', $subject);
    
    // Remove duplicate company names (case insensitive)
    $companies = ['RMJ Transport', 'RMJ Transport LLC', 'Transport LLC', 'RMJ'];
    
    foreach ($companies as $company) {
        $pattern = '/\b' . preg_quote($company, '/') . '\b/i';
        $matches = [];
        preg_match_all($pattern, $subject, $matches);
        
        if (count($matches[0]) > 1) {
            // Keep only the first occurrence
            $subject = preg_replace($pattern, '', $subject, count($matches[0]) - 1);
        }
    }
    
    // Clean up extra spaces
    $subject = preg_replace('/\s+/', ' ', trim($subject));
    
    return $subject;
}

require_once 'header6.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3f51b5;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --light-text-color: #666;
            --border-color: #e0e0e0;
        }

        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: var(--secondary-color); 
            margin: 0; 
            padding: 20px; 
            color: var(--text-color);
        }
        
        .container { 
            width: 100%; 
            max-width: 1300px; 
            margin: 80px auto; 
            background-color: #fff; 
            padding: 20px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
            border-radius: 12px; 
        }

        h1 { 
            color: var(--primary-color); 
            margin-top: 0; 
            font-size: 2.2em; 
            border-bottom: 2px solid var(--border-color); 
            padding-bottom: 10px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .message-counters {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .message-counter {
            font-size: 1em;
            font-weight: bold;
            color: var(--primary-color);
            background-color: #f8f9ff;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid var(--primary-color);
        }

        .counter-today {
            background-color: #e8f5e8;
            border-color: #4caf50;
            color: #4caf50;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .search-bar {
            flex-grow: 1;
        }

        .search-bar input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(63, 81, 181, 0.2);
        }

        .date-filter-select {
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
        }

        .filter-dropdown {
            position: relative;
            display: inline-block;
        }

        .filter-dropdown-btn {
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
            transition: all 0.3s ease;
        }

        .filter-dropdown-btn:hover {
            border-color: var(--primary-color);
        }

        .filter-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 200px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            z-index: 1000;
            top: 100%;
            right: 0;
            margin-top: 5px;
            padding: 10px;
        }

        .filter-dropdown.active .filter-dropdown-content {
            display: block;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-bottom: 5px;
        }

        .filter-option:hover {
            background-color: #f5f5f5;
        }

        .filter-option input[type="checkbox"] {
            margin: 0;
            cursor: pointer;
        }

        .filter-option label {
            cursor: pointer;
            flex-grow: 1;
            margin: 0;
            font-size: 0.95em;
        }

        .apply-filters-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }

        .apply-filters-btn:hover {
            background-color: #303f9f;
        }

        .clear-filters-btn {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 5px;
            font-size: 0.85em;
            transition: all 0.3s ease;
        }

        .clear-filters-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .selected-filters {
            font-size: 0.85em;
            color: var(--light-text-color);
            margin-top: 5px;
        }

        .back-link { 
            text-decoration: none; 
            color: #fff; 
            font-weight: bold; 
            background-color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .back-link:hover { 
            background-color: #303f9f;
        }

        table { 
            width: 100%; 
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px; 
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid var(--border-color);
        }

        th { 
            background-color: var(--primary-color); 
            color: #fff;
            font-weight: 600;
        }

        tr:nth-child(even) { 
            background-color: #f8f9fa; 
        }

        tr:hover { 
            background-color: #e8eaf6; 
        }

        .message-body { 
            max-height: 50px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
            font-size: 0.9em; 
            color: var(--light-text-color); 
        }

        .empty-state { 
            text-align: center; 
            color: var(--light-text-color); 
            padding: 40px; 
            font-size: 1.2em;
        }

        .view-btn {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .view-btn:hover {
            background-color: #303f9f;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            position: relative;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .modal h2 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .modal-body p {
            margin: 10px 0;
            color: var(--light-text-color);
            line-height: 1.6;
        }
        .modal-body strong {
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>Sent Emails</h1>
            <div class="message-counters">
                <div class="message-counter counter-today">
                    Today: <span><?php echo $daily_count; ?></span>
                </div>
                <div class="message-counter">
                    <?php 
                    $filter_label = 'Filtered';
                    $active_filters = [];
                    
                    if (in_array('all', $date_filters) || empty($date_filters)) {
                        $filter_label = 'Total';
                    } else {
                        if (in_array('today', $date_filters)) $active_filters[] = 'Today';
                        if (in_array('yesterday', $date_filters)) $active_filters[] = 'Yesterday';
                        if (in_array('weekly', $date_filters)) $active_filters[] = 'Week';
                        if (in_array('monthly', $date_filters)) $active_filters[] = 'Month';
                        if (in_array('yearly', $date_filters)) $active_filters[] = 'Year';
                        
                        if (!empty($active_filters)) {
                            $filter_label = implode(' + ', $active_filters);
                        }
                    }
                    echo $filter_label;
                    ?>: <span><?php echo $total_count; ?></span>
                </div>
            </div>
            <a href="view_leads.php" class="back-link">&larr; Back to Leads</a>
        </div>
        
        <div class="filter-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search emails...">
            </div>
            <div class="filter-dropdown" id="filterDropdown">
                <div class="filter-dropdown-btn" onclick="toggleFilterDropdown()">
                    <i class="fas fa-filter"></i>
                    <span>Date Filters</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="filter-dropdown-content">
                    <div class="filter-option">
                        <input type="checkbox" id="filter-all" value="all" <?php echo in_array('all', $date_filters) ? 'checked' : ''; ?> onchange="handleAllFilter(this)">
                        <label for="filter-all">All Time</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-today" value="today" <?php echo in_array('today', $date_filters) ? 'checked' : ''; ?> onchange="handleFilterChange(this)">
                        <label for="filter-today">Today</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-yesterday" value="yesterday" <?php echo in_array('yesterday', $date_filters) ? 'checked' : ''; ?> onchange="handleFilterChange(this)">
                        <label for="filter-yesterday">Yesterday</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-weekly" value="weekly" <?php echo in_array('weekly', $date_filters) ? 'checked' : ''; ?> onchange="handleFilterChange(this)">
                        <label for="filter-weekly">This Week</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-monthly" value="monthly" <?php echo in_array('monthly', $date_filters) ? 'checked' : ''; ?> onchange="handleFilterChange(this)">
                        <label for="filter-monthly">This Month</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-yearly" value="yearly" <?php echo in_array('yearly', $date_filters) ? 'checked' : ''; ?> onchange="handleFilterChange(this)">
                        <label for="filter-yearly">This Year</label>
                    </div>
                    <button class="apply-filters-btn" onclick="applyDateFilters()">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                    <button class="clear-filters-btn" onclick="clearAllFilters()">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                    <div class="selected-filters" id="selectedFilters"></div>
                </div>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <table id="emailTable">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Subject</th>
                        <th>Message Body</th>
                        <th>Sent At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $row_id = 0; while($row = $result->fetch_assoc()): $row_id++; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['from_email']); ?></td>
                            <td><?php echo htmlspecialchars($row['to_email']); ?></td>
                            <td><?php echo htmlspecialchars(cleanSubject($row['subject'])); ?></td>
                            <td style="text-align:justify; width:20%;"><p><?php echo htmlspecialchars($row['message_body']); ?></p></td>
                            <td><?php echo htmlspecialchars($row['sent_at']); ?></td>
                            <td>
                                <button class="view-btn" onclick="openModal(<?php echo $row_id; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <div id="email-data-<?php echo $row_id; ?>" style="display: none;">
                                    <span class="from-email"><?php echo htmlspecialchars($row['from_email']); ?></span>
                                    <span class="to-email"><?php echo htmlspecialchars($row['to_email']); ?></span>
                                    <span class="subject"><?php echo htmlspecialchars(cleanSubject($row['subject'])); ?></span>
                                    <span class="message-body"><?php echo htmlspecialchars($row['message_body']); ?></span>
                                    <span class="sent-at"><?php echo htmlspecialchars($row['sent_at']); ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No emails have been sent yet for this filter.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 id="modal-subject"></h2>
            <div class="modal-body">
                <p><strong>From:</strong> <span id="modal-from"></span></p>
                <p><strong>To:</strong> <span id="modal-to"></span></p>
                <p><strong>Date:</strong> <span id="modal-date"></span></p>
                <hr>
                <p id="modal-message" style="white-space: pre-wrap;"></p>
            </div>
        </div>
    </div>
    
    <?php
    $conn->close();
    ?>

    <script>
        function filterTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("emailTable");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                // Skip header row
                if (tr[i].getElementsByTagName("th").length > 0) {
                    continue;
                }
                
                let found = false;
                // Check From, To, and Subject columns
                for (let j = 0; j < 3; j++) {
                    td = tr[i].getElementsByTagName("td")[j];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                if (found) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        function applyDateFilter() {
            var filterValue = document.getElementById('dateFilter').value;
            window.location.href = 'sent_mail.php?filter=' + filterValue;
        }

        function toggleFilterDropdown() {
            var dropdown = document.getElementById('filterDropdown');
            dropdown.classList.toggle('active');
        }

        function handleAllFilter(checkbox) {
            var otherCheckboxes = document.querySelectorAll('#filterDropdown input[type="checkbox"]:not(#filter-all)');
            if (checkbox.checked) {
                otherCheckboxes.forEach(cb => {
                    cb.checked = false;
                    cb.disabled = true;
                });
            } else {
                otherCheckboxes.forEach(cb => {
                    cb.disabled = false;
                });
            }
            updateSelectedFiltersDisplay();
        }

        function handleFilterChange(checkbox) {
            var allCheckbox = document.getElementById('filter-all');
            if (checkbox.checked && allCheckbox.checked) {
                allCheckbox.checked = false;
                var otherCheckboxes = document.querySelectorAll('#filterDropdown input[type="checkbox"]:not(#filter-all)');
                otherCheckboxes.forEach(cb => {
                    cb.disabled = false;
                });
            }
            updateSelectedFiltersDisplay();
        }

        function updateSelectedFiltersDisplay() {
            var selectedFilters = [];
            var checkboxes = document.querySelectorAll('#filterDropdown input[type="checkbox"]:checked');
            
            checkboxes.forEach(cb => {
                var label = cb.nextElementSibling.textContent;
                selectedFilters.push(label);
            });

            var display = document.getElementById('selectedFilters');
            if (selectedFilters.length > 0) {
                display.textContent = 'Selected: ' + selectedFilters.join(', ');
            } else {
                display.textContent = 'No filters selected';
            }
        }

        function applyDateFilters() {
            var selectedFilters = [];
            var checkboxes = document.querySelectorAll('#filterDropdown input[type="checkbox"]:checked');
            
            checkboxes.forEach(cb => {
                selectedFilters.push(cb.value);
            });

            if (selectedFilters.length === 0) {
                selectedFilters = ['all'];
            }

            var url = 'sent_mail.php?filters[]=' + selectedFilters.join('&filters[]=');
            window.location.href = url;
        }

        function clearAllFilters() {
            var checkboxes = document.querySelectorAll('#filterDropdown input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            document.getElementById('filter-all').checked = true;
            handleAllFilter(document.getElementById('filter-all'));
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            var dropdown = document.getElementById('filterDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Initialize the display
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedFiltersDisplay();
        });

        function openModal(rowId) {
            try {
                // Get the email data from the hidden div
                var dataDiv = document.getElementById('email-data-' + rowId);
                if (!dataDiv) {
                    console.error('Data div not found for row ID:', rowId);
                    return;
                }

                var fromEmail = dataDiv.querySelector('.from-email').textContent;
                var toEmail = dataDiv.querySelector('.to-email').textContent;
                var subject = dataDiv.querySelector('.subject').textContent;
                var messageBody = dataDiv.querySelector('.message-body').textContent;
                var sentAt = dataDiv.querySelector('.sent-at').textContent;

                // Populate the modal
                document.getElementById('modal-from').textContent = fromEmail;
                document.getElementById('modal-to').textContent = toEmail;
                document.getElementById('modal-subject').textContent = subject;
                document.getElementById('modal-message').textContent = messageBody;
                document.getElementById('modal-date').textContent = sentAt;
                
                // Show the modal
                document.getElementById('emailModal').style.display = "block";
            } catch (error) {
                console.error('Error opening modal:', error);
                alert('Error opening email details. Please try again.');
            }
        }

        function closeModal() {
            document.getElementById('emailModal').style.display = "none";
        }

        // Close the modal if the user clicks anywhere outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('emailModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>