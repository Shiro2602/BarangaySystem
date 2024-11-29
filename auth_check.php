<?php
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth.php';
require_once 'config.php';

// This file should be included at the top of every protected page
// It will automatically check if the user is logged in and has the right permissions
// If not, they will be redirected to the login page

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // If the current page is not login.php, redirect to login
    if (basename($_SERVER['PHP_SELF']) != 'login.php') {
        header("Location: login.php");
        exit();
    }
}
?>
