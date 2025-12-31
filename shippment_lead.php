<?php
session_start(); // Always start the session at the very beginning

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Auto-logout after 90 minutes (5400 seconds) of inactivity
$inactivity_timeout = 5400; // 90 minutes * 60 seconds/minute

// Check current login status
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Get the logged-in user's ID and name
$logged_in_user_id = $_SESSION['user_id'] ?? null;
$logged_in_admin_id = $_SESSION['admin_id'] ?? null;
$logged_in_user_name = $_SESSION['user_name'] ?? 'User';
$logged_in_admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Get the logged-in user's phone number from the session
$logged_in_user_phone = $_SESSION['admin_no'] ?? 'Admin';
$logged_in_user_phone = $_SESSION['user_no'] ?? 'User';

// --- Handle Logout request first ---
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    $logged_out_type = $_SESSION['logged_in_type'] ?? 'user'; // Default to user logout page
    session_unset();   // Unset all session variables
    session_destroy(); // Destroy the session

    // Redirect to the appropriate login page after logout
    if ($logged_out_type === 'admin') {
        redirectWithStatus('admin.php', 'success', 'You have been logged out.');
    } else { // Assume it was a local user or general public trying to logout from this page
        redirectWithStatus('user_login.php', 'success', 'You have been logged out.');
    }
}

// --- Enforce Login for Shared Pages ---
// If NEITHER admin NOR local user is logged in, redirect to user_login.php
if (!$is_admin_logged_in && !$is_user_logged_in) {
    redirectWithStatus('user_login.php', 'error', 'Please log in to access this page.');
}

// --- Auto-logout check for ACTIVE session (either admin or user) ---
if (($is_admin_logged_in || $is_user_logged_in) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_timeout)) {
    $logged_out_type = $_SESSION['logged_in_type'] ?? 'user'; // Capture type before unsetting
    session_unset();
    session_destroy();
    if ($logged_out_type === 'admin') {
        redirectWithStatus('admin.php', 'error', 'You were logged out due0 to inactivity.');
    } else {
        redirectWithStatus('user_login.php', 'error', 'You were logged out due to inactivity.');
    }
}

// Update last activity time and store user type (Crucial for auto-logout redirect)
// Only update if someone is actively logged in
if ($is_admin_logged_in) {
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in_type'] = 'admin';
} elseif ($is_user_logged_in) {
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in_type'] = 'user';
}
// --- END CORRECTED AUTHENTICATION LOGIC ---

// Generate CSRF token for form submission (after authentication check)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rmj Transport LLC - Leads Formatter</title>
    <link rel="stylesheet" href="assets/adminpage_css/shippment_lead.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <img style="width: 60px" class="img-responsive" src="assets/img/logo/logo.png" alt="Logo">
        <div class="navbar-links">
            <div class="dropdown">
                <a href="#">Leads &#9662;</a>
                <div class="dropdown-content">
                    <a href="shippment_lead.php">New Lead Form</a>
                    <a href="view_leads.php">View All Leads</a>
                </div>
                
            </div>
                <div class="navbar-links">
                <a href="sent_mail.php">View Sent Mail </a>
                </div>
            
            
            <?php if ($is_admin_logged_in): ?>
            <div class="navbar-links">
            
            <a href="admin.php">Dashboard </a>
            </div>
            <div class="dropdown">
                
                <a href="#">More Tools &#9662;</a>
                <div class="dropdown-content">
                    <a href="agreement.php">Agreement Page</a>
                    <a href="contact_messages.php">Contact Messages</a>
                    <a href="quotation_requests.php">Quotation Requests</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#">Account &#9662;</a>
                <div class="dropdown-content">
                    <a href="user_login.php">User Login</a>
                    <a style="display: none;" href="admin_users.php">Admin Profile</a>
                    <a href="admin_users.php">Manage Admin Users</a>
                    <a href="local_users.php">Manage Local Users</a>
                </div>
            </div>
            <?php endif; ?>
            
            <a href="admin.php?logout=true">
                <?php echo $is_admin_logged_in ? 'Admin Logout' : ($is_user_logged_in ? htmlspecialchars($logged_in_user_name) . ' Logout' : 'Logout'); ?>
            </a>
        </div>
    </div>

    <div class="container">
        <div id="statusMessage" class="status-message"></div>

        <div class="form-row top-inputs">
            <div class="form-group">
                <label for="quoteAmountInput"><strong>Quote Amount ($):</strong></label>
                <input type="number" id="quoteAmountInput" placeholder="e.g., 655" step="0.01" required />
            </div>
            <div class="form-group">
                <label for="quoteDate"><strong>Quote Date (Compulsory):</strong></label>
                <input type="date" id="quoteDate" required />
            </div>
            <div class="form-group">
                <label for="shippmentDate"><strong>Shipment Date (Optional):</strong></label>
                <input type="date" id="shippmentDate" />
            </div>
            <div class="form-group">
                <label for="status"><strong>Status (Optional):</strong></label>
                <select id="status">
                    <option value="">-- Select Status --</option>
                    <option value="Booked">Booked</option>
                    <option value="Not Pick">Not Pick</option>
                    <option value="Voice Mail">Voice Mail</option>
                    <option value="In Future Shipment">In Future Shipment</option>
                    <option value="Qutation">Qutation</option>
                    <option value="Invalid Lead">Invalid Lead</option>
                    <option value="Stop Lead">Stop Lead</option>
                    <option value="Delivered">Delivered</option>

                    <option value="Already Booked">Already Booked</option>
                    <option value="Potenial Lead">Potenial Lead</option>
                </select>
            </div>
        </div>

        <div class="raw-data-section">
            <div class="form-group raw-data">
                <label for="inputData"><strong>Paste Raw Lead Data (Optional):</strong></label>
                <textarea id="inputData" placeholder="Paste your raw lead here. Information like Name, Email, Phone, Vehicle details, and locations will be extracted.
Example Format:

Ship Date: 2025-07-30
CONTACT INFORMATION
Name: James M Martinez
Email Address: jamesmartinez2@gmail.com
Phone: 9707785197
VEHICLE INFORMATION
Year: 1949
Make: CHEVROLET
Model: FLEETLINE
Type: Coupe
PICKUP AND DELIVERY INFORMATION
Pickup City: APACHE JUNCTION
Pickup State: AZ
Pickup Zipcode: 81501
Delivery City: GRAND JUNCTION
Delivery State: CO
Delivery Zipcode: 81501"></textarea>
            </div>
            <div class="raw-data-buttons">
                <button class="format-btn" onclick="formatLead()">Format Message</button>
                <button class="reset-btn" onclick="resetForm()">Reset Form</button>
            </div>
        </div>

        <div class="formatted-message-section">
            <div class="form-group full-width">
                <label for="formattedMessage"><strong>Formatted Message:</strong></label>
                <textarea id="formattedMessage" placeholder="Formatted message will appear here..." readonly></textarea>
            </div>
            <div class="formatted-message-buttons">
                <button class="copy-btn" onclick="copyMessage()">Copy & Email</button>
                <button class="save-btn" onclick="saveToDatabase()">Save to Database</button>
            </div>
            </div>

    </div>

    <form id="dataForm" action="save_lead.php" method="post" style="display:none;">
        <input type="hidden" name="name" id="dbName">
        <input type="hidden" name="email" id="dbEmail">
        <input type="hidden" name="phone" id="dbPhone">
        <input type="hidden" name="quote_amount" id="dbQuoteAmount">
        <input type="hidden" name="quote_id" id="dbQuoteID">
        <input type="hidden" name="quote_date" id="dbQuoteDate">
        <input type="hidden" name="shippment_date" id="dbShippmentDate">
        <input type="hidden" name="status" id="dbStatus">
        <input type="hidden" name="year" id="dbYear">
        <input type="hidden" name="make" id="dbMake">
        <input type="hidden" name="model" id="dbModel">
        <input type="hidden" name="pickup_city" id="dbPickupCity">
        <input type="hidden" name="pickup_state" id="dbPickupState">
        <input type="hidden" name="pickup_zip" id="dbPickupZip">
        <input type="hidden" name="delivery_city" id="dbDeliveryCity">
        <input type="hidden" name="delivery_state" id="dbDeliveryState">
        <input type="hidden" name="delivery_zip" id="dbDeliveryZip">
        <input type="hidden" name="formatted_message" id="dbFormattedMessage">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($logged_in_user_id ?? $logged_in_admin_id); ?>">
    </form>

    <footer>Powered by Rmj Transport LLC</footer>

    <script>
        // Toggles the visibility of the navigation menu on smaller screens
        function toggleMenu() {
            const navbarLinks = document.getElementById('navbarLinks');
            navbarLinks.classList.toggle('active');
        }

        // Displays status messages (success/error/warning) to the user
        function displayStatusMessage(message, type, elementToFocus = null) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.className = `status-message ${type}`; // Apply styling
            statusDiv.style.display = 'block'; // Make visible
            if (elementToFocus) {
                elementToFocus.focus(); // Focus on the problematic input field
            }
            setTimeout(() => statusDiv.style.display = 'none', 5000); // Hide after 5 seconds
        }

        // Converts string to Title Case (e.g., "john doe" to "John Doe")
        function toTitleCase(str) {
            return str?.toLowerCase().replace(/\b\w/g, l => l.toUpperCase()) || '';
        }

        // Sets the default date for the quoteDate input to today's date
        function setTodayDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            document.getElementById('quoteDate').value = `${year}-${month}-${day}`;
        }

        // This function runs automatically when the page finishes loading
        window.onload = function () {
            // Check for status messages passed via URL parameters from save_lead.php
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            if (status && message) {
                displayStatusMessage(decodeURIComponent(message), status);
            }
            // Auto-populate the Quote Date field with the current date
            setTodayDate();
        };

        // Resets all form fields to their initial state
        function resetForm() {
            document.getElementById('inputData').value = '';
            document.getElementById('quoteAmountInput').value = '';
            document.getElementById('shippmentDate').value = '';
            document.getElementById('status').value = '';
            document.getElementById('formattedMessage').value = '';

            setTodayDate(); // Reset auto-populated quote date to today

            // Clear all hidden form fields
            document.getElementById('dbName').value = '';
            document.getElementById('dbEmail').value = '';
            document.getElementById('dbPhone').value = '';
            document.getElementById('dbQuoteAmount').value = '';
            document.getElementById('dbQuoteID').value = '';
            document.getElementById('dbQuoteDate').value = '';
            document.getElementById('dbShippmentDate').value = '';
            document.getElementById('dbStatus').value = '';
            document.getElementById('dbYear').value = '';
            document.getElementById('dbMake').value = '';
            document.getElementById('dbModel').value = '';
            document.getElementById('dbPickupCity').value = '';
            document.getElementById('dbPickupState').value = '';
            document.getElementById('dbPickupZip').value = '';
            document.getElementById('dbDeliveryCity').value = '';
            document.getElementById('dbDeliveryState').value = '';
            document.getElementById('dbDeliveryZip').value = '';
            document.getElementById('dbFormattedMessage').value = '';

            document.getElementById('inputData').focus();
            displayStatusMessage('Form reset successfully!', 'success');
        }

        // Extracts data from raw input and visible fields, then formats the message.
        // Also populates the hidden fields of the submission form.
        function extractAndFormatData() {
            const input = document.getElementById('inputData').value;
            const quoteDate = document.getElementById('quoteDate').value || '';
            const shippmentDate = document.getElementById('shippmentDate').value || '';
            const status = document.getElementById('status').value || '';
            const quote = parseFloat(document.getElementById('quoteAmountInput').value) || 0;
            const sanitize = str => str?.trim() ?? '';

            // Updated to fetch dynamic user info from PHP session
            const loggedInUserName = "<?php echo htmlspecialchars($is_admin_logged_in ? ($logged_in_admin_name) : ($is_user_logged_in ? $logged_in_user_name : 'Adeel')); ?>";
            const loggedInUserPhone = "<?php echo htmlspecialchars($logged_in_user_phone); ?>";

            let finalName = sanitize(
                input.match(/(?:Name|Customer Name|Client Name|Customer):\s*(.*)/i)?.[1]
            );

            let extractedPhone = sanitize(input.match(/(?:Phone|Contact #|Tel):\s*(.*)/i)?.[1]);

            let quoteID = '';
            if (extractedPhone) {
                const digitsOnly = extractedPhone.replace(/\D/g, '');
                if (digitsOnly.length >= 4) {
                    quoteID = digitsOnly.slice(-4);
                } else {
                    quoteID = digitsOnly;
                    displayStatusMessage('Warning: Phone number has less than 4 digits, Quote ID might be incomplete.', 'warning', document.getElementById('inputData'));
                }
            } else {
                displayStatusMessage('Warning: Phone number not found in raw data. Quote ID will be "N/A".', 'warning', document.getElementById('inputData'));
            }

            if (!finalName && input.trim() !== '') { // Only show error if raw data is not empty
                displayStatusMessage('Customer Name could not be extracted from raw data. Please ensure "Name: [Customer Name]" is present.', 'error', document.getElementById('inputData'));
            }

            let extractedEmail = sanitize(input.match(/(?:Email|E-mail|Email Address):\s*(.*)/i)?.[1]);

            const year = sanitize(input.match(/(?:Year|Vehicle Year):\s*(.*)/i)?.[1]);
            const make = sanitize(input.match(/(?:Make|Vehicle Make):\s*(.*)/i)?.[1]);
            const model = sanitize(input.match(/(?:Model|Vehicle Model):\s*(.*)/i)?.[1]);
            const pickupCity = toTitleCase(sanitize(input.match(/(?:Pickup City|Origin City|From City):\s*(.*)/i)?.[1]));
            const pickupState = sanitize(input.match(/(?:Pickup State|Origin State|From State):\s*(.*)/i)?.[1]?.toUpperCase());
            const pickupZip = sanitize(input.match(/(?:Pickup Zipcode|Origin Zip|From Zip):\s*(.*)/i)?.[1]);
            const deliveryCity = toTitleCase(sanitize(input.match(/(?:Delivery City|Destination City|To City):\s*(.*)/i)?.[1]));
            const deliveryState = sanitize(sanitize(input.match(/(?:Delivery State|Destination State|To City):\s*(.*)/i)?.[1])?.toUpperCase());
            const deliveryZip = sanitize(input.match(/(?:Delivery Zipcode|Destination Zip|To Zip):\s*(.*)/i)?.[1]);

            const formattedQuoteID = quoteID ? (quoteID.startsWith('MJH-') ? quoteID : `MJH-${quoteID}`) : 'N/A';

            const message = `Good day! ${finalName || 'Customer'},
I'm pleased to give you the quotation of $${quote.toFixed(2)} for shipping your vehicle having Quote ID: ${formattedQuoteID} (Date: ${quoteDate}).
Year: ${year || 'N/A'}
Make: ${make || 'N/A'}
Model: ${model || 'N/A'}
Origin: ${pickupCity || 'N/A'}, ${pickupState || 'N/A'} ${pickupZip || 'N/A'}
Destination: ${deliveryCity || 'N/A'}, ${deliveryState || 'N/A'} ${deliveryZip || 'N/A'}
• Bumper to Bumper insurance up to $250,000
• 150 lbs of personal belongings included
• Door-to-Door Shipment
• Live Tracking Available
${loggedInUserName}
Call or Text: ${loggedInUserPhone}
Rmj Transport LLC
Text "Stop" to opt out`;

            document.getElementById('formattedMessage').value = message;

            document.getElementById('dbName').value = finalName;
            document.getElementById('dbEmail').value = extractedEmail;
            document.getElementById('dbPhone').value = extractedPhone;
            document.getElementById('dbQuoteAmount').value = quote;
            document.getElementById('dbQuoteID').value = formattedQuoteID; // Use formatted quote ID for consistency
            document.getElementById('dbQuoteDate').value = quoteDate;
            document.getElementById('dbShippmentDate').value = shippmentDate;
            document.getElementById('dbStatus').value = status;
            document.getElementById('dbYear').value = year;
            document.getElementById('dbMake').value = make;
            document.getElementById('dbModel').value = model;
            document.getElementById('dbPickupCity').value = pickupCity;
            document.getElementById('dbPickupState').value = pickupState;
            document.getElementById('dbPickupZip').value = pickupZip;
            document.getElementById('dbDeliveryCity').value = deliveryCity;
            document.getElementById('dbDeliveryState').value = deliveryState;
            document.getElementById('dbDeliveryZip').value = deliveryZip;
            document.getElementById('dbFormattedMessage').value = message;

            return quote !== 0 && quoteDate; // Name and phone are no longer strictly required for formatting but will affect "N/A"
        }

        // Formats the lead message and selects it for easy copying.
        function formatLead() {
            const isValid = extractAndFormatData();

            const msgBox = document.getElementById('formattedMessage');
            msgBox.focus();
            msgBox.select();
        }

        // Performs client-side validation for mandatory fields and then submits the form.
        function saveToDatabase() {
            // Ensure data is extracted and populated before validation
            extractAndFormatData();

            const dbName = document.getElementById('dbName').value.trim();
            const dbQuoteAmount = document.getElementById('dbQuoteAmount').value.trim();
            const dbQuoteID = document.getElementById('dbQuoteID').value.trim();
            const dbQuoteDate = document.getElementById('dbQuoteDate').value;

            if (!dbName) {
                displayStatusMessage('Customer Name is missing. Please ensure "Name: [Customer Name]" is in the Raw Lead Data.', 'error', document.getElementById('inputData'));
                return;
            }
            if (!dbQuoteAmount || isNaN(parseFloat(dbQuoteAmount))) {
                displayStatusMessage('Quote Amount is missing or invalid. Please enter a valid number in the "Quote Amount ($)" field.', 'error', document.getElementById('quoteAmountInput'));
                return;
            }
            if (!dbQuoteID || dbQuoteID === 'N/A') {
                displayStatusMessage('Quote ID could not be derived from Phone number. Please ensure "Phone: [Number]" is present in Raw Lead Data and has at least 4 digits.', 'error', document.getElementById('inputData'));
                return;
            }
            if (!dbQuoteDate) {
                displayStatusMessage('Quote Date is required. Please select a date.', 'error', document.getElementById('quoteDate'));
                return;
            }

            // If all client-side validations pass, submit the form
            document.getElementById('dataForm').submit();
        }

        // Copies the content of the formatted message textarea to the clipboard
        function copyMessage() {
            const msg = document.getElementById('formattedMessage');
            navigator.clipboard.writeText(msg.value).then(() => {
                displayStatusMessage('Copied to Clipboard! ✅', 'success');
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                displayStatusMessage('Failed to copy to clipboard. Please copy manually.', 'error');
            });
        }

        // Shows a temporary "✅ Copied!" message next to the copy button
        function showTick() {
            // This function is no longer directly used for copy feedback as displayStatusMessage handles it.
            // Keeping it here for reference in case it's used elsewhere or you change your mind.
            const tick = document.getElementById("tick");
            if (tick) {
                tick.style.display = "inline";
                setTimeout(() => tick.style.display = "none", 2000);
            }
        }

        // Event listener for Ctrl/Cmd + Enter on raw data textarea
        document.getElementById('inputData').addEventListener('keydown', function(e) {
             if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                 e.preventDefault();
                 formatLead();
             }
        });

        // Event listener for Ctrl/Cmd + C on formatted message textarea
        document.getElementById('formattedMessage').addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
                navigator.clipboard.writeText(this.value).then(() => {
                    displayStatusMessage('Copied to Clipboard! ✅', 'success');
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                    displayStatusMessage('Failed to copy to clipboard. Please copy manually.', 'error');
                });
            }
        });
    </script>
</body>
</html>