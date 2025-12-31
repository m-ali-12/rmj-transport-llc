<?php

// This file is designed to be included at the beginning of your HTML body.
// It handles user authentication, session management, and provides the main navigation bar.
// You must have session_start() at the very top of your main page (e.g., view_leads.php)
// before including this file.

// Redirect function for clean redirects
function redirectWithStatus($page, $status, $message) {
    header('Location: ' . $page . '?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit();
}

// Auto-logout after 90 minutes (5400 seconds) of inactivity
$inactivity_timeout = 5400; // 90 minutes * 60 seconds/minute

// Check current login status and get user IDs
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$logged_in_admin_id = $_SESSION['admin_id'] ?? null;
$logged_in_user_id = $_SESSION['user_id'] ?? null;
$logged_in_email = $_SESSION['admin_email'] ?? $_SESSION['user_email'] ?? null;
$logged_in_name = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'User';

// --- Handle Logout request first ---
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    $logged_out_type = $_SESSION['logged_in_type'] ?? 'user';
    session_unset();
    session_destroy();

    if ($logged_out_type === 'admin') {
        redirectWithStatus('admin.php', 'success', 'You have been logged out.');
    } else {
        redirectWithStatus('user_login.php', 'success', 'You have been logged out.');
    }
}

// --- Enforce Login for Shared Pages ---
if (!$is_admin_logged_in && !$is_user_logged_in) {
    redirectWithStatus('user_login.php', 'error', 'Please log in to access this page.');
}

// --- Auto-logout check for ACTIVE session (either admin or user) ---
if (($is_admin_logged_in || $is_user_logged_in) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_timeout)) {
    $logged_out_type = $_SESSION['logged_in_type'] ?? 'user';
    session_unset();
    session_destroy();
    if ($logged_out_type === 'admin') {
        redirectWithStatus('admin.php', 'error', 'You were logged out due to inactivity.');
    } else {
        redirectWithStatus('user_login.php', 'error', 'You were logged out due to inactivity.');
    }
}

if ($is_admin_logged_in) {
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in_type'] = 'admin';
} elseif ($is_user_logged_in) {
    $_SESSION['last_activity'] = time();
    $_SESSION['logged_in_type'] = 'user';
}
// --- END AUTHENTICATION LOGIC ---

// Get the current page to highlight active link (if needed)
$current_page = basename($_SERVER['PHP_SELF']);
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation</title>
    <style>
        /* --- Navbar & Responsive Styles --- */
        :root {
            --primary-color: #2c73d2;
            --primary-dark: #1a4b8c;
            --danger-color: #d9534f;
            --danger-dark: #c9302c;
            --white: #ffffff;
            --transition: all 0.3s ease;
        }

        .main-navbar {
            background-color: #3c548a; /* Dark blue background from the image */
            color: #fff;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1001;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            min-height: 70px;
            display: flex;
            align-items: center;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            height: 100%;
            flex-shrink: 0;
        }

        .navbar-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .nav-menu {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 25px;
            margin-right: auto; /* Push nav links to the left */
            margin-left: 25px;
        }

        .nav-link {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 10px 0;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .nav-link:hover {
            color: #f0f0f0;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: var(--danger-dark);
            transform: translateY(-1px);
        }

        .logout-btn i {
            margin-right: 8px;
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .menu-toggle .bar {
            width: 25px;
            height: 3px;
            background-color: #fff;
            margin: 4px 0;
            transition: var(--transition);
        }

        .logout-btn-mobile {
            display: none;
            color: var(--danger-color);
        }

        /* --- Responsive Styles --- */
        @media (max-width: 992px) {
            .main-navbar {
                min-height: 60px;
                padding: 0 15px;
            }
            .navbar-logo {
                height: 40px;
            }
            .nav-menu {
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 60px; /* Position below main navbar */
                left: 0;
                background-color: #3c548a;
                height: 0;
                overflow: hidden;
                transition: height 0.3s ease-in-out;
            }
            .nav-menu.active {
                height: auto;
                padding: 10px 0;
            }
            .nav-menu .nav-item {
                width: 100%;
                text-align: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            .nav-menu .nav-link {
                display: block;
                padding: 15px;
            }
            .menu-toggle {
                display: flex;
            }
            .logout-btn {
                display: none;
            }
            .logout-btn-mobile {
                display: block;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // New JS for hamburger menu
            const mobileMenu = document.getElementById('mobile-menu');
            const navMenu = document.getElementById('navMenu');

            mobileMenu.addEventListener('click', () => {
                navMenu.classList.toggle('active');
            });
        });
    </script>
</head>

<nav class="main-navbar">
    <div class="navbar-container">
        <a href="#" class="navbar-brand">
            <img class="navbar-logo" src="assets/img/logo/logo.png" alt="RMJ Transport LLC">
        </a>
        <div class="menu-toggle" id="mobile-menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
        <ul class="nav-menu" id="navMenu">
            <li class="nav-item">
                <a href="shippment_lead.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> New Lead
                </a>
            </li>
            <li class="nav-item">
                <a href="?logout=true" class="nav-link logout-btn-mobile">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
        <a href="?logout=true" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>