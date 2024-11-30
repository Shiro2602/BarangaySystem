<?php
require_once 'auth_check.php';
require_once 'config.php';

// Handle blotter record submission
if (isset($_POST['submit_blotter'])) {
    $stmt = $conn->prepare("INSERT INTO blotter (complainant_id, respondent_id, incident_type, incident_date, 
                          incident_location, incident_details) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss",
        $_POST['complainant_id'],
        $_POST['respondent_id'],
        $_POST['incident_type'],
        $_POST['incident_date'],
        $_POST['incident_location'],
        $_POST['incident_details']
    );
    $stmt->execute();
}

// Get all blotter records
$query = "SELECT b.*, 
                     CONCAT(c.last_name, ', ', c.first_name) as complainant_name,
                     CONCAT(r.last_name, ', ', r.first_name) as respondent_name
                     FROM blotter b 
                     JOIN residents c ON b.complainant_id = c.id 
                     JOIN residents r ON b.respondent_id = r.id 
                     ORDER BY b.created_at DESC";
$result = $conn->query($query);
$blotters = $result->fetch_all(MYSQLI_ASSOC);

// Get residents for dropdown
$query = "SELECT id, CONCAT(last_name, ', ', first_name, ' ', middle_name) as full_name 
                     FROM residents ORDER BY last_name, first_name";
$result = $conn->query($query);
$residents = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Records - Barangay System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white text-center mb-4">Barangay System</h3>
                <nav>
                    <a href="index.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a href="residents.php"><i class="fas fa-users me-2"></i> Residents</a>
                    <a href="clearance.php"><i class="fas fa-file-alt me-2"></i> Clearance</a>
                    <a href="indigency.php"><i class="fas fa-certificate me-2"></i> Indigency</a>
                    <a href="blotter.php" class="active"><i class="fas fa-book me-2"></i> Blotter</a>
                    <a href="officials.php"><i class="fas fa-user-tie me-2"></i> Officials</a>
                    <a href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a href="forecast.php"><i class="fas fa-chart-line me-2"></i> Population Forecast</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield me-2"></i> User Management</a>
                    <?php endif; ?>
                    <a href="account.php"><i class="fas fa-user-cog me-2"></i> Account Settings</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Blotter Records Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBlotterModal">
                            <i class="fas fa-plus"></i> Add Blotter Record
                        </button>
                    </div>
                </div>

                <!-- Blotter Table -->
                <div class="table-responsive">
                    <table id="blotterTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Case #</th>
                                <th>Complainant</th>
                                <th>Respondent</th>
                                <th>Incident Type</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blotters as $blotter): ?>
                            <tr>
                                <td><?= $blotter['id'] ?></td>
                                <td><?= $blotter['complainant_name'] ?></td>
                                <td><?= $blotter['respondent_name'] ?></td>
                                <td><?= $blotter['incident_type'] ?></td>
                                <td><?= $blotter['incident_date'] ?></td>
                                <td><?= $blotter['incident_location'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $blotter['status'] == 'Resolved' ? 'success' : 
                                        ($blotter['status'] == 'Dismissed' ? 'danger' : 
                                        ($blotter['status'] == 'Ongoing' ? 'primary' : 'warning')) ?>">
                                        <?= $blotter['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" title="View Details" data-bs-toggle="modal" 
                                            data-bs-target="#viewBlotterModal<?= $blotter['id'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Blotter Modal -->
    <div class="modal fade" id="addBlotterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Blotter Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Complainant</label>
                                <select name="complainant_id" class="form-control" required>
                                    <option value="">Select Complainant</option>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Respondent</label>
                                <select name="respondent_id" class="form-control" required>
                                    <option value="">Select Respondent</option>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Incident Type</label>
                                <select name="incident_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="Assault">Assault</option>
                                    <option value="Theft">Theft</option>
                                    <option value="Harassment">Harassment</option>
                                    <option value="Property Damage">Property Damage</option>
                                    <option value="Noise Complaint">Noise Complaint</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Incident Date</label>
                                <input type="date" name="incident_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Incident Location</label>
                            <input type="text" name="incident_location" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Incident Details</label>
                            <textarea name="incident_details" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_blotter" class="btn btn-primary">Submit Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#blotterTable').DataTable({
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
