<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    // If the current page is not login.php, redirect to login
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'login.php') {
        header("Location: login.php");
        exit();
    }
}

// Get current user's information
function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check if user has permission for a specific action
function hasPermission($requiredRole) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
}
?>
