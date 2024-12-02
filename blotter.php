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

// Handle status update
if (isset($_POST['update_status'])) {
    $stmt = $conn->prepare("UPDATE blotter SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $_POST['status'], $_POST['blotter_id']);
    $stmt->execute();
    header("Location: blotter.php");
    exit();
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
            <?php require_once __DIR__ . '/includes/header.php'; ?>

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
                                    <button class="btn btn-sm btn-warning" title="Update Status" data-bs-toggle="modal" 
                                            data-bs-target="#updateStatusModal<?= $blotter['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- View Details Modal -->
                            <div class="modal fade" id="viewBlotterModal<?= $blotter['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Blotter Details - Case #<?= $blotter['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Complainant</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['complainant_name']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Respondent</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['respondent_name']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Incident Type</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['incident_type']) ?>" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Incident Date</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['incident_date']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Location</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['incident_location']) ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['status']) ?>" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Incident Details</label>
                                                <textarea class="form-control" rows="4" readonly><?= htmlspecialchars($blotter['incident_details']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Date Filed</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($blotter['created_at']) ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Status Modal -->
                            <div class="modal fade" id="updateStatusModal<?= $blotter['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Status - Case #<?= $blotter['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="blotter_id" value="<?= $blotter['id'] ?>">
                                                <div class="mb-3">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="Pending" <?= $blotter['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Ongoing" <?= $blotter['status'] == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                                        <option value="Resolved" <?= $blotter['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                        <option value="Dismissed" <?= $blotter['status'] == 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
