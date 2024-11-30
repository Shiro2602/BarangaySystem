<?php
require_once 'config.php';
require_once 'auth_check.php';

// Add Indigency
if (isset($_POST['add_indigency'])) {
    $stmt = $conn->prepare("INSERT INTO indigency (resident_id, purpose, issue_date, or_number, status) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['issue_date'],
        $_POST['or_number'],
        $_POST['status']
    );
    $stmt->execute();
}

// Edit Indigency
if (isset($_POST['edit_indigency'])) {
    $stmt = $conn->prepare("UPDATE indigency SET 
        resident_id = ?, 
        purpose = ?, 
        issue_date = ?, 
        or_number = ?, 
        status = ? 
        WHERE id = ?");
    $stmt->bind_param("issssi",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['issue_date'],
        $_POST['or_number'],
        $_POST['status'],
        $_POST['indigency_id']
    );
    $stmt->execute();
}

// Delete Indigency
if (isset($_POST['delete_indigency'])) {
    $stmt = $conn->prepare("DELETE FROM indigency WHERE id = ?");
    $stmt->bind_param("i", $_POST['indigency_id']);
    $stmt->execute();
}

// Get all indigency certificates with resident information
$query = "SELECT i.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name 
          FROM indigency i 
          LEFT JOIN residents r ON i.resident_id = r.id 
          ORDER BY i.issue_date DESC";
$result = $conn->query($query);
$certificates = $result->fetch_all(MYSQLI_ASSOC);

// Get all residents for the dropdown
$residents_query = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name 
                   FROM residents 
                   ORDER BY last_name, first_name";
$residents_result = $conn->query($residents_query);
$residents = $residents_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Indigency - Barangay System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="css/print.css" rel="stylesheet">
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
        @media print {
            body > *:not(#certificateTemplate) {
                display: none !important;
            }
            #certificateTemplate {
                display: block !important;
            }
        }
        .print-certificate {
            display: none;
            width: 8.5in;
            height: 11in;
            padding: 0.5in;
            margin: auto;
            border: 1px solid #ddd;
        }
        .certificate-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .certificate-body {
            margin: 2rem 0;
        }
        .certificate-footer {
            margin-top: 4rem;
            text-align: center;
        }
        .signature-line {
            display: inline-block;
            margin-top: 2rem;
            padding-top: 1rem;
            min-width: 200px;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar no-print">
                <h3 class="text-white text-center mb-4">Barangay System</h3>
                <nav>
                    <a href="index.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a href="residents.php"><i class="fas fa-users me-2"></i> Residents</a>
                    <a href="clearance.php"><i class="fas fa-file-alt me-2"></i> Clearance</a>
                    <a href="indigency.php" class="active"><i class="fas fa-certificate me-2"></i> Indigency</a>
                    <a href="blotter.php"><i class="fas fa-book me-2"></i> Blotter</a>
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
                        <h2>Certificate of Indigency Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIndigencyModal">
                            <i class="fas fa-plus"></i> Add Certificate
                        </button>
                    </div>
                </div>

                <!-- Indigency Table -->
                <div class="table-responsive">
                    <table id="indigencyTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Issue Date</th>
                                <th>OR Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $certificate): ?>
                            <tr data-id="<?= $certificate['id'] ?>">
                                <td><?= $certificate['id'] ?></td>
                                <td class="resident-name"><?= $certificate['resident_name'] ?></td>
                                <td class="purpose"><?= $certificate['purpose'] ?></td>
                                <td class="issue-date"><?= $certificate['issue_date'] ?></td>
                                <td class="or-number"><?= $certificate['or_number'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $certificate['status'] == 'Approved' ? 'success' : 
                                        ($certificate['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $certificate['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-indigency" data-id="<?= $certificate['id'] ?>" data-bs-toggle="modal" data-bs-target="#editIndigencyModal" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success print-indigency" data-id="<?= $certificate['id'] ?>" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-indigency" data-id="<?= $certificate['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteIndigencyModal" title="Delete">
                                        <i class="fas fa-trash"></i>
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

    <!-- Add Indigency Modal -->
    <div class="modal fade" id="addIndigencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Resident</label>
                                <select name="resident_id" class="form-control" required>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Purpose</label>
                                <input type="text" name="purpose" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Issue Date</label>
                                <input type="date" name="issue_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>OR Number</label>
                                <input type="text" name="or_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_indigency" class="btn btn-primary">Add Certificate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Indigency Modal -->
    <div class="modal fade" id="editIndigencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="indigency_id" id="edit_indigency_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Resident</label>
                                <select name="resident_id" id="edit_resident_id" class="form-control" required>
                                    <?php foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Purpose</label>
                                <input type="text" name="purpose" id="edit_purpose" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Issue Date</label>
                                <input type="date" name="issue_date" id="edit_issue_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>OR Number</label>
                                <input type="text" name="or_number" id="edit_or_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="edit_indigency" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Indigency Modal -->
    <div class="modal fade" id="deleteIndigencyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Indigency Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="indigency_id" id="delete_indigency_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this indigency certificate? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_indigency" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Template -->
    <div id="certificateTemplate" class="print-certificate">
        <div class="certificate-header">
            <h4>Republic of the Philippines</h4>
            <h5>Province of _______________</h5>
            <h5>Municipality of _______________</h5>
            <h4 class="mt-4">OFFICE OF THE BARANGAY CHAIRMAN</h4>
            <h3 class="mt-4">CERTIFICATE OF INDIGENCY</h3>
        </div>
        
        <div class="certificate-body">
            <p class="mb-4">TO WHOM IT MAY CONCERN:</p>
            
            <p>This is to certify that <strong><span id="print-resident-name">_______________</span></strong>, of legal age, Filipino Citizen is a bonafide resident of this Barangay and belongs to the indigent family in this Barangay.</p>
            
            <p>This certification is being issued upon the request of the above-named person for <strong><span id="print-purpose">_______________</span></strong> purposes.</p>
            
            <p>Issued this <strong><span id="print-issue-date">_______________</span></strong> at the Barangay Hall, _______________.</p>
            
            <p class="mb-4">OR No.: <strong><span id="print-or-number">_______________</span></strong></p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-line">
                Barangay Chairman
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#indigencyTable').DataTable();

            $('.edit-indigency').click(function() {
                var id = $(this).data('id');
                var certificate = <?php echo json_encode($certificates); ?>.find(c => c.id == id);
                
                if (certificate) {
                    $('#edit_indigency_id').val(certificate.id);
                    $('#edit_resident_id').val(certificate.resident_id);
                    $('#edit_purpose').val(certificate.purpose);
                    $('#edit_issue_date').val(certificate.issue_date);
                    $('#edit_or_number').val(certificate.or_number);
                    $('#edit_status').val(certificate.status);
                }
            });

            $('.delete-indigency').click(function() {
                var id = $(this).data('id');
                $('#delete_indigency_id').val(id);
            });

            $('.print-indigency').click(function() {
                var row = $(this).closest('tr');
                
                // Get the data from the row
                var residentName = row.find('td:eq(1)').text().trim();
                var purpose = row.find('td:eq(2)').text().trim();
                var issueDate = new Date(row.find('td:eq(3)').text().trim()).toLocaleDateString();
                var orNumber = row.find('td:eq(4)').text().trim();

                // Update the certificate template
                $('#print-resident-name').text(residentName);
                $('#print-purpose').text(purpose);
                $('#print-issue-date').text(issueDate);
                $('#print-or-number').text(orNumber);

                // Print
                window.print();
            });
        });
    </script>
</body>
</html>
