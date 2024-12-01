<?php
require_once 'config.php';
require_once 'auth_check.php';

// Get summary statistics
$stats = [
    'residents' => $conn->query("SELECT COUNT(*) FROM residents")->fetch_row()[0],
    'clearances' => $conn->query("SELECT COUNT(*) FROM clearances WHERE MONTH(issue_date) = MONTH(CURRENT_DATE())")->fetch_row()[0],
    'indigency' => $conn->query("SELECT COUNT(*) FROM indigency WHERE MONTH(issue_date) = MONTH(CURRENT_DATE())")->fetch_row()[0],
    'blotters' => $conn->query("SELECT COUNT(*) FROM blotter WHERE status = 'Pending'")->fetch_row()[0]
];

// Get monthly clearance revenue
$monthlyRevenue = [];
$result = $conn->query("SELECT 
    DATE_FORMAT(issue_date, '%Y-%m') as month,
    COUNT(*) as count,
    SUM(amount) as total
    FROM clearances 
    WHERE issue_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
    ORDER BY month DESC");
while ($row = $result->fetch_assoc()) {
    $monthlyRevenue[] = $row;
}

// Get recent blotter cases
$recentBlotters = [];
$result = $conn->query("SELECT b.*, 
    CONCAT(c.first_name, ' ', c.last_name) as complainant,
    CONCAT(r.first_name, ' ', r.last_name) as respondent
    FROM blotter b
    JOIN residents c ON b.complainant_id = c.id
    JOIN residents r ON b.respondent_id = r.id
    ORDER BY b.created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recentBlotters[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Barangay System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .report-card {
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/header.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Reports and Analytics</h2>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card report-card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title">Total Residents</h6>
                                <h2 class="card-text"><?= number_format($stats['residents']) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">Clearances This Month</h6>
                                <h2 class="card-text"><?= number_format($stats['clearances']) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title">Indigency Certs This Month</h6>
                                <h2 class="card-text"><?= number_format($stats['indigency']) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="card-title">Pending Blotter Cases</h6>
                                <h2 class="card-text"><?= number_format($stats['blotters']) ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Monthly Revenue from Clearances</h5>
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Recent Blotter Cases</h5>
                                <div class="list-group">
                                    <?php foreach ($recentBlotters as $blotter): ?>
                                    <a href="#" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($blotter['incident_type']) ?></h6>
                                            <small><?= date('M d', strtotime($blotter['created_at'])) ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <small>
                                                Complainant: <?= htmlspecialchars($blotter['complainant']) ?><br>
                                                Respondent: <?= htmlspecialchars($blotter['respondent']) ?>
                                            </small>
                                        </p>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Reports Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Generate Reports</h5>
                                <form class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Report Type</label>
                                        <select class="form-select">
                                            <option value="residents">Residents List</option>
                                            <option value="clearances">Clearance Transactions</option>
                                            <option value="indigency">Indigency Certificates</option>
                                            <option value="blotter">Blotter Records</option>
                                            <option value="revenue">Revenue Report</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date Range</label>
                                        <select class="form-select">
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month">This Month</option>
                                            <option value="quarter">This Quarter</option>
                                            <option value="year">This Year</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Format</label>
                                        <select class="form-select">
                                            <option value="pdf">PDF</option>
                                            <option value="excel">Excel</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-download me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($monthlyRevenue, 'month')) ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?= json_encode(array_column($monthlyRevenue, 'total')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
