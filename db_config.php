<?php
// db_config.php - Database connection configuration
// This file should be placed in your_website_root/db_config.php


$dbhost = 'localhost';
$dbuser = ''; // Your provided username
$dbpass = '';   // Your provided password
$dbname = ''; // Your provided database name

// Establish the database connection
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

// Set character set to UTF-8 for proper character handling
mysqli_set_charset($conn, "utf8");

// Check connection
if (!$conn) {
    die('ERROR: Could not connect to the database. ' . mysqli_connect_error());
}
?>