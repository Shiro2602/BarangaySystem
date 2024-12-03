<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>
<!-- Sidebar -->
<div class="col-auto px-0">
    <div class="sidebar">
        <h3 class="text-center">Barangay System</h3>
        <nav>
            <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="residents.php" <?php echo basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-users"></i> Residents
            </a>
            <a href="household.php" <?php echo basename($_SERVER['PHP_SELF']) == 'household.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-house-user"></i> Households
            </a>
            <a href="clearance.php" <?php echo basename($_SERVER['PHP_SELF']) == 'clearance.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-file-alt"></i> Clearance
            </a>
            <a href="indigency.php" <?php echo basename($_SERVER['PHP_SELF']) == 'indigency.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-certificate"></i> Indigency
            </a>
            <a href="blotter.php" <?php echo basename($_SERVER['PHP_SELF']) == 'blotter.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-book"></i> Blotter
            </a>
            <a href="crime_map.php" <?php echo basename($_SERVER['PHP_SELF']) == 'crime_map.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-map-marked-alt"></i> Crime Map
            </a>
            <a href="officials.php" <?php echo basename($_SERVER['PHP_SELF']) == 'officials.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-user-tie"></i> Officials
            </a>
            <a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="forecast.php" <?php echo basename($_SERVER['PHP_SELF']) == 'forecast.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-chart-line"></i> Population Forecast
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-user-shield"></i> User Management
            </a>
            <?php endif; ?>
            <a href="account.php" <?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-user-cog"></i> Account Settings
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
</div>