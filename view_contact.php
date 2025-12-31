<?php
session_start();

// Redirect function
function redirectWithStatus($page, $status, $message) {
    session_write_close();
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    redirectWithStatus('admin.php', 'error', 'You must be logged in to view this page.');
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
    die("Database connection failed: " . $e->getMessage());
}

// --- EMAIL SENDING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    // This block handles the AJAX email submission
    header('Content-Type: application/json');

    $to_email = filter_input(INPUT_POST, 'to_email', FILTER_SANITIZE_EMAIL);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);
    $from_name = 'MJ Hauling United LLC'; // Your company name
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message_body = filter_input(INPUT_POST, 'message_body', FILTER_SANITIZE_STRING);

    if (empty($to_email) || empty($from_email) || empty($subject) || empty($message_body)) {
        echo json_encode(['success' => false, 'message' => 'To, From, Subject, and Message are required.']);
        exit;
    }

    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    if (mail($to_email, $subject, $message_body, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Check your server\'s mail configuration.']);
    }
    exit;
}

$contact = null;
$contact_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($contact_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM contact WHERE id = :id");
        $stmt->execute([':id' => $contact_id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contact) {
            redirectWithStatus('contact_messages.php', 'error', 'Contact message not found.');
        }

        // Mark as read if it's currently unread
        if ($contact['status'] === 'Unread') {
            $update_stmt = $pdo->prepare("UPDATE contact SET status = 'Read' WHERE id = :id");
            $update_stmt->execute([':id' => $contact_id]);
            $contact['status'] = 'Read'; // Update the local variable to reflect the change
        }

    } catch (PDOException $e) {
        error_log("Error fetching contact details: " . $e->getMessage());
        redirectWithStatus('contact_messages.php', 'error', 'Error fetching contact details.');
    }
} else {
    redirectWithStatus('contact_messages.php', 'error', 'No contact ID provided.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Contact Message - MJ Hauling United LLC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 0; margin: 0; color: #333; display: flex; flex-direction: column; min-height: 100vh; padding-top: 70px; box-sizing: border-box; overflow-x: hidden; line-height: 1.6; }
        .navbar { background: linear-gradient(to right, #2c73d2, #4a90e2); padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; box-sizing: border-box; }
        .navbar .site-title { font-size: 1.8rem; font-weight: 700; color: white; letter-spacing: 0.8px; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); }
        .navbar-links { display: flex; gap: 15px; }
        .navbar-links a { color: white; text-decoration: none; padding: 10px 18px; border-radius: 8px; transition: all 0.3s ease; font-weight: 500; background-color: rgba(255, 255, 255, 0.1); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar-links a:hover { background-color: rgba(255, 255, 255, 0.25); transform: translateY(-2px) scale(1.02); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }
        .main-content { width: 100%; max-width: 900px; margin: 30px auto; padding: 0 25px; box-sizing: border-box; }
        h1 { text-align: center; color: #2c73d2; margin-bottom: 2.5rem; font-size: 2.8rem; font-weight: 700; text-shadow: 1px 1px 4px rgba(0,0,0,0.08); letter-spacing: -0.5px; }
        .status-message { text-align: center; padding: 15px; margin: 20px auto; border-radius: 10px; font-weight: bold; max-width: 800px; display: block; animation: fadeIn 0.5s forwards; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 8px solid; }
        .status-message:empty { display: none; }
        .success { background-color: #e6ffed; color: #1a6d2f; border-color: #28a745; }
        .error { background-color: #ffe6e6; color: #a71a2b; border-color: #dc3545; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .detail-card { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); margin-bottom: 30px; border: 1px solid #e0e0e0; }
        .detail-item { display: flex; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #eee; align-items: flex-start; }
        .detail-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .detail-label { font-weight: 600; color: #555; flex: 0 0 150px; margin-right: 20px; }
        .detail-value { flex-grow: 1; color: #333; word-wrap: break-word; }
        .detail-value.message { white-space: pre-wrap; background-color: #f9f9f9; border: 1px solid #e9e9e9; padding: 15px; border-radius: 8px; margin-top: 5px; }
        .action-buttons { text-align: center; margin-top: 30px; }
        .action-buttons .btn { background-color: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; text-decoration: none; transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease; font-weight: 600; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); display: inline-block; margin: 0 10px; }
        .action-buttons .btn:hover { background-color: #0056b3; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
        .action-buttons .email-btn { background-color: #ffc107; color: #333; }
        .action-buttons .email-btn:hover { background-color: #e0a800; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 15px; font-size: 0.9em; font-weight: 600; color: white; text-align: center; min-width: 80px; }
        .status-badge.New_Lead { background-color: #007bff; }
        .status-badge.Read { background-color: #28a745; }
        .status-badge.Unread { background-color: #dc3545; }
        footer { text-align: center; margin-top: auto; padding: 30px; font-weight: 600; color: #888; font-size: 0.9rem; background-color: #eef2f7; border-top: 1px solid #e0e0e0; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.6); justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; }
        .modal-content { background-color: #fefefe; margin: auto; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25); position: relative; width: 90%; max-width: 600px; animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .modal-content h3 { color: #2c73d2; margin-top: 0; margin-bottom: 25px; font-size: 1.8rem; text-align: center; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .close-button { color: #aaa; position: absolute; top: 15px; right: 25px; font-size: 32px; font-weight: bold; cursor: pointer; transition: color 0.3s ease; }
        .close-button:hover, .close-button:focus { color: #333; text-decoration: none; }
        .modal-form-group { margin-bottom: 20px; }
        .modal-form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 1.05rem; }
        .modal-form-group input[type="email"], .modal-form-group input[type="text"], .modal-form-group textarea { width: calc(100% - 22px); padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.3s ease, box-shadow 0.3s ease; }
        .modal-form-group input[type="email"]:focus, .modal-form-group input[type="text"]:focus, .modal-form-group textarea:focus { border-color: #2c73d2; box-shadow: 0 0 0 3px rgba(44, 115, 210, 0.2); outline: none; }
        .modal-form-group textarea { resize: vertical; min-height: 120px; }
        .modal-buttons { text-align: right; margin-top: 30px; }
        .modal-buttons button { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-size: 1.05rem; font-weight: 600; transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); margin-left: 15px; }
        .modal-buttons .send-btn { background-color: #28a745; color: white; }
        .modal-buttons .send-btn:hover { background-color: #218838; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
        .modal-buttons .cancel-btn { background-color: #6c757d; color: white; }
        .modal-buttons .cancel-btn:hover { background-color: #5a6268; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
        @media (max-width: 768px) { .navbar { flex-direction: column; align-items: flex-start; padding: 15px 10px; } .navbar .site-title { margin-bottom: 10px; } .navbar-links { width: 100%; justify-content: center; flex-wrap: wrap; } .main-content { padding: 0 15px; } h1 { font-size: 2.2rem; } .detail-item { flex-direction: column; align-items: flex-start; } .detail-label { margin-bottom: 5px; flex: none; } .action-buttons .btn { width: 100%; margin: 10px 0; } .modal-content { padding: 20px; } .modal-buttons { text-align: center; } .modal-buttons button { display: block; width: 100%; margin: 10px 0; } }
    </style>
</head>
<body>
    <div class="navbar">
        <span class="site-title">MJ Hauling United LLC</span>
        <div class="navbar-links">
            <a href="admin.php">Dashboard</a>
            <a href="contact_messages.php">Back to Messages</a>
        </div>
    </div>
    
    <main class="main-content">
        <h1>Contact Message Details</h1>

        <?php
        $status_type = '';
        $status_message = '';
        if (isset($_GET['status']) && isset($_GET['message'])) {
            $status_type = htmlspecialchars($_GET['status']);
            $status_message = htmlspecialchars(urldecode($_GET['message']));
        }
        if (!empty($status_message)): ?>
            <div class="status-message <?php echo $status_type; ?>">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($contact): ?>
            <div class="detail-card">
                <div class="detail-item">
                    <div class="detail-label">ID:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($contact['id']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">First Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($contact['f_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Last Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($contact['l_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($contact['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Mobile No:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($contact['mob_no']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge <?php echo str_replace(' ', '_', htmlspecialchars($contact['status'])); ?>">
                            <?php echo htmlspecialchars($contact['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Message:</div>
                    <div class="detail-value message"><?php echo nl2br(htmlspecialchars($contact['msg'])); ?></div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="contact_messages.php" class="btn"><i class="fas fa-arrow-left"></i> Go Back</a>
                <button class="btn email-btn"
                        data-email-to="<?php echo htmlspecialchars($contact['email']); ?>"
                        data-subject="<?php echo htmlspecialchars('Re: Your inquiry from MJ Hauling United LLC'); ?>"
                        data-message-body="<?php echo htmlspecialchars("Dear " . $contact['f_name'] . ",\n\nThank you for reaching out to MJ Hauling United LLC. We have received your message and will get back to you shortly.\n\nBest regards,\nMJ Hauling United LLC\n"); ?>"
                        onclick="openEmailModal(this)">
                    <i class="fas fa-reply"></i> Reply to Contact
                </button>
            </div>
        <?php else: ?>
            <div class="detail-card" style="text-align: center;">
                <p>No contact message found with the provided ID.</p>
                <a href="contact_messages.php" class="btn"><i class="fas fa-arrow-left"></i> Go Back to Contact Messages</a>
            </div>
        <?php endif; ?>
    </main>

    <footer>&copy; <?php echo date('Y'); ?> MJ Hauling United LLC. All rights reserved.</footer>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEmailModal()">&times;</span>
            <h3>Send Email Reply</h3>
            <form id="emailForm" class="modal-form" method="POST" onsubmit="handleEmailSubmit(event)">
                <div class="modal-form-group">
                    <label for="to_email">To:</label>
                    <input type="email" id="to_email" name="to_email" readonly required>
                </div>
                <div class="modal-form-group">
                    <label for="from_email">From:</label>
                    <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@example.com'); ?>" readonly required>
                </div>
                <div class="modal-form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="modal-form-group">
                    <label for="message_body">Message:</label>
                    <textarea id="message_body" name="message_body" required></textarea>
                </div>
                <input type="hidden" name="action" value="send_email">
                <div class="modal-buttons">
                    <button type="submit" class="send-btn">Send</button>
                    <button type="button" class="cancel-btn" onclick="closeEmailModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const emailModal = document.getElementById('emailModal');
        const emailForm = document.getElementById('emailForm');
        
        function openEmailModal(buttonElement) {
            const recipientEmail = buttonElement.dataset.emailTo;
            const subject = buttonElement.dataset.subject;
            const messageBody = buttonElement.dataset.messageBody;
            
            document.getElementById('to_email').value = recipientEmail;
            document.getElementById('subject').value = subject;
            document.getElementById('message_body').value = messageBody;
            
            emailModal.style.display = 'flex';
        }

        function closeEmailModal() {
            emailModal.style.display = 'none';
            emailForm.reset();
        }

        function handleEmailSubmit(event) {
            event.preventDefault();

            const formData = new FormData(emailForm);
            const submitBtn = emailForm.querySelector('.send-btn');
            const originalBtnText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            fetch('view_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // On successful send, redirect to the messages page with a success status
                    window.location.href = 'contact_messages.php?status=success&message=' + encodeURIComponent(data.message);
                } else {
                    // On failure, display the error message on the current page
                    const mainContent = document.querySelector('.main-content');
                    const oldStatusMessage = mainContent.querySelector('.status-message');
                    if (oldStatusMessage) {
                        oldStatusMessage.remove();
                    }
                    const statusMessageDiv = document.createElement('div');
                    statusMessageDiv.className = 'status-message error';
                    statusMessageDiv.textContent = data.message;
                    mainContent.insertBefore(statusMessageDiv, mainContent.firstChild.nextSibling);
                }
            })
            .catch(error => {
                const mainContent = document.querySelector('.main-content');
                const oldStatusMessage = mainContent.querySelector('.status-message');
                if (oldStatusMessage) {
                    oldStatusMessage.remove();
                }
                const statusMessageDiv = document.createElement('div');
                statusMessageDiv.className = 'status-message error';
                statusMessageDiv.textContent = 'An unexpected error occurred. Please try again.';
                mainContent.insertBefore(statusMessageDiv, mainContent.firstChild.nextSibling);
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        }
        
        window.onclick = function(event) {
            if (event.target === emailModal) {
                closeEmailModal();
            }
        }
    </script>
</body>
</html>