<?php
// auth.php - Authentication and Session Management Functions
// This file should be placed in your_website_root/auth.php

session_start();

require_once 'db_config.php'; // Include your database configuration (now using mysqli $conn)

/**
 * Checks if a user is logged in. If not, redirects to the login page.
 */
function check_login() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: /index1.php");
        exit;
    }
}

/**
 * Checks if a user is logged in AND has admin privileges.
 * If not, redirects to the login page or a forbidden page.
 */
function check_admin_access() {
    check_login(); // First, ensure the user is logged in
    if ($_SESSION["role"] !== 'admin') {
        header("location: /dashboard.php?error=forbidden"); // Adjust path if you have a specific forbidden page
        exit;
    }
}

/**
 * Attempts to log in a user.
 *
 * @param mysqli $conn The mysqli database connection object.
 * @param string $username The username provided by the user.
 * @param string $password The plain-text password provided by the user.
 * @return bool True on successful login, false otherwise.
 */
function login_user($conn, $username, $password) {
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameters
        mysqli_stmt_bind_param($stmt, "s", $param_username);
        $param_username = trim($username);

        // Attempt to execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Store result to check row count
            mysqli_stmt_store_result($stmt);

            // Check if username exists, if yes then verify password
            if (mysqli_stmt_num_rows($stmt) == 1) {
                // Bind result variables
                mysqli_stmt_bind_result($stmt, $id, $db_username, $hashed_password, $role);

                if (mysqli_stmt_fetch($stmt)) {
                    // Verify password using password_verify
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_regenerate_id(true); // Regenerate session ID for security

                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $db_username;
                        $_SESSION["role"] = $role;

                        // Update last_login timestamp
                        $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "i", $id);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }

                        mysqli_stmt_close($stmt);
                        return true;
                    } else {
                        // Password is not valid
                        mysqli_stmt_close($stmt);
                        return false;
                    }
                }
            } else {
                // Username doesn't exist
                mysqli_stmt_close($stmt);
                return false;
            }
        } else {
            error_log("Oops! Something went wrong with login query execution: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    } else {
        error_log("Oops! Something went wrong preparing login query: " . mysqli_error($conn));
        return false;
    }
}

/**
 * Logs out the current user by destroying the session.
 */
function logout_user() {
    $_SESSION = array(); // Unset all of the session variables
    session_destroy();   // Destroy the session
    header("location: /index1.php");
    exit;
}

// Handle logout action if requested via GET parameter
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    logout_user();
}
?>