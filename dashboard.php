<?php
require_once 'config.php';
require_once 'auth.php';

// Get all active officials
$query = "SELECT * FROM officials WHERE status = 'Active' ORDER BY position";
$result = $conn->query($query);
$officials = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics for dashboard cards
$stats = [
    'residents' => $conn->query("SELECT COUNT(*) FROM residents")->fetch_row()[0],
    'blotters' => $conn->query("SELECT COUNT(*) FROM blotter WHERE status = 'Pending'")->fetch_row()[0]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .officials-table {
            font-size: 0.9rem;
        }
        .officials-table th, .officials-table td {
            padding: 8px;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/includes/header.php'; ?>

            <div class="col-md-9 col-lg-10 main-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Header with user info -->
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm">
                    <h2 class="mb-0">Dashboard</h2>
                    <div class="dropdown">
                        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="account.php"><i class="fas fa-user-cog me-2"></i>Account Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>

                <div class="row">
                    <!-- Officials Table Section (Left) -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Current Barangay Officials</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover officials-table">
                                        <thead>
                                            <tr>
                                                <th>Position</th>
                                                <th>Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($officials as $official): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($official['position']) ?></td>
                                                <td><?= htmlspecialchars($official['first_name'] . ' ' . $official['last_name']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Cards Section (Right) -->
                    <div class="col-md-4">
                        <!-- Total Residents Card -->
                        <div class="card mb-3 stat-card">
                            <div class="card-body bg-primary text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Residents</h6>
                                        <h2 class="mb-0 mt-2"><?= number_format($stats['residents']) ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Blotters Card -->
                        <div class="card mb-3 stat-card">
                            <div class="card-body bg-danger text-white rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Active Blotters</h6>
                                        <h2 class="mb-0 mt-2"><?= number_format($stats['blotters']) ?></h2>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
