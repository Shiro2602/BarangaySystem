<?php
session_start();

// Temporarily disable authentication
$_SESSION['authenticated'] = true;
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists
$_SESSION['username'] = 'admin';

// Comment out the original authentication check
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
*/

// Get current user's information
function getCurrentUser($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check if user has permission for a specific action
function hasPermission($requiredRole) {
    return $_SESSION['role'] === 'admin' || $_SESSION['role'] === $requiredRole;
}
?>
