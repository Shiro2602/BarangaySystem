<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar">
    <h3 class="text-white text-center mb-4">Barangay System</h3>
    <nav>
        <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="residents.php" <?php echo basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'class="active"' : ''; ?>><i class="fas fa-users me-2"></i> Residents</a>
        <a href="household.php" <?php echo basename($_SERVER['PHP_SELF']) == 'household.php' ? 'class="active"' : ''; ?>><i class="fas fa-house-user me-2"></i> Households</a>
        <a href="clearance.php" <?php echo basename($_SERVER['PHP_SELF']) == 'clearance.php' ? 'class="active"' : ''; ?>><i class="fas fa-file-alt me-2"></i> Clearance</a>
        <a href="indigency.php" <?php echo basename($_SERVER['PHP_SELF']) == 'indigency.php' ? 'class="active"' : ''; ?>><i class="fas fa-certificate me-2"></i> Indigency</a>
        <a href="blotter.php" <?php echo basename($_SERVER['PHP_SELF']) == 'blotter.php' ? 'class="active"' : ''; ?>><i class="fas fa-book me-2"></i> Blotter</a>
        <a href="officials.php" <?php echo basename($_SERVER['PHP_SELF']) == 'officials.php' ? 'class="active"' : ''; ?>><i class="fas fa-user-tie me-2"></i> Officials</a>
        <a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : ''; ?>><i class="fas fa-chart-bar me-2"></i> Reports</a>
        <a href="forecast.php" <?php echo basename($_SERVER['PHP_SELF']) == 'forecast.php' ? 'class="active"' : ''; ?>><i class="fas fa-chart-line me-2"></i> Population Forecast</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>><i class="fas fa-user-shield me-2"></i> User Management</a>
        <?php endif; ?>
        <a href="account.php" <?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'class="active"' : ''; ?>><i class="fas fa-user-cog me-2"></i> Account Settings</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </nav>
</div>
