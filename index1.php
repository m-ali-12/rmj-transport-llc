<?php
// index1.php - Login Page
// This file should be placed in your_website_root/index1.php

require_once 'auth.php'; // Include authentication functions
global $conn; // Access the $conn object from db_config.php

// If user is already logged in, redirect to dashboard or admin panel
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["role"] === 'admin') {
        header("location: /admin/index.php");
    } else {
        header("location: /dashboard.php");
    }
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Pass $conn to the login_user function
        if (login_user($conn, $username, $password)) {
            // Redirect based on role
            if ($_SESSION["role"] === 'admin') {
                header("location: /admin/index.php");
            } else {
                header("location: /dashboard.php");
            }
            exit;
        } else {
            $login_err = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Your Website</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css"> </head>
<body class="font-['Inter'] bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Login</h2>
        <p class="text-center text-gray-600 mb-8">Please fill in your credentials to login.</p>

        <?php
        if (!empty($login_err)) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" autofocus>
                <?php if (!empty($username_err)) { echo '<p class="text-red-500 text-xs italic mt-1">' . $username_err . '</p>'; } ?>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
                <?php if (!empty($password_err)) { echo '<p class="text-red-500 text-xs italic mt-1">' . $password_err . '</p>'; } ?>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
                    Login
                </button>
            </div>
        </form>
    </div>

</body>
</html>