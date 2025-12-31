<?php
// dashboard.php - User Dashboard
// This file should be placed in your_website_root/dashboard.php

require_once 'auth.php'; // Include authentication functions
check_login(); // Ensure user is logged in
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Your Website</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="font-['Inter'] bg-gray-100 text-gray-800">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center mb-8">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-semibold mb-4">Your Dashboard</h2>
            <p class="text-gray-700 mb-4">This is your personal dashboard. You can access various features here.</p>

            <h3 class="text-xl font-semibold mb-3">Available Actions:</h3>
            <ul class="list-disc list-inside space-y-2">
                <li><a href="/restricted_pages/view_lead.php" class="text-blue-600 hover:underline">View Your Leads</a></li>
                <li><a href="/restricted_pages/shippment_lead.php" class="text-blue-600 hover:underline">Manage Shipments</a></li>
                <?php if ($_SESSION["role"] === 'admin') { ?>
                    <li><a href="/admin/index.php" class="text-purple-600 hover:underline font-bold">Go to Admin Panel</a></li>
                <?php } ?>
            </ul>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>