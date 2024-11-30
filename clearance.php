<?php
require_once 'config.php';
require_once 'auth_check.php';

// Add Clearance
if (isset($_POST['add_clearance'])) {
    $stmt = $conn->prepare("INSERT INTO clearances (resident_id, purpose, issue_date, expiry_date, or_number, amount, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssds",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['issue_date'],
        $_POST['expiry_date'],
        $_POST['or_number'],
        $_POST['amount'],
        $_POST['status']
    );
    $stmt->execute();
}

// Edit Clearance
if (isset($_POST['edit_clearance'])) {
    $stmt = $conn->prepare("UPDATE clearances SET 
        resident_id = ?, 
        purpose = ?, 
        issue_date = ?, 
        expiry_date = ?, 
        or_number = ?, 
        amount = ?, 
        status = ? 
        WHERE id = ?");
    $stmt->bind_param("issssdsi",
        $_POST['resident_id'],
        $_POST['purpose'],
        $_POST['issue_date'],
        $_POST['expiry_date'],
        $_POST['or_number'],
        $_POST['amount'],
        $_POST['status'],
        $_POST['clearance_id']
    );
    $stmt->execute();
}

// Delete Clearance
if (isset($_POST['delete_clearance'])) {
    $stmt = $conn->prepare("DELETE FROM clearances WHERE id = ?");
    $stmt->bind_param("i", $_POST['clearance_id']);
    $stmt->execute();
}

// Get all clearances with resident information
$query = "SELECT c.*, CONCAT(r.first_name, ' ', r.last_name) as resident_name 
          FROM clearances c 
          LEFT JOIN residents r ON c.resident_id = r.id 
          ORDER BY c.issue_date DESC";
$result = $conn->query($query);
$clearances = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Barangay Clearance - Barangay System</title>
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
        .print-certificate {
            width: 8.5in;
            height: 11in;
            padding: 0.5in;
            margin: auto;
            border: 1px solid #ddd;
            display: none;
        }
        @media print {
            .print-certificate {
                display: block;
            }
            .no-print {
                display: none;
            }
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
                    <a href="clearance.php" class="active"><i class="fas fa-file-alt me-2"></i> Clearance</a>
                    <a href="indigency.php"><i class="fas fa-certificate me-2"></i> Indigency</a>
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
                        <h2>Barangay Clearance Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestClearanceModal">
                            <i class="fas fa-plus"></i> Request Clearance
                        </button>
                    </div>
                </div>

                <!-- Clearance Table -->
                <div class="table-responsive">
                    <table id="clearanceTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Resident</th>
                                <th>Purpose</th>
                                <th>Issue Date</th>
                                <th>Expiry Date</th>
                                <th>OR Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clearances as $clearance): ?>
                            <tr data-id="<?= $clearance['id'] ?>">
                                <td><?= $clearance['id'] ?></td>
                                <td class="resident-name"><?= htmlspecialchars($clearance['resident_name']) ?></td>
                                <td class="purpose"><?= htmlspecialchars($clearance['purpose']) ?></td>
                                <td class="issue-date"><?= $clearance['issue_date'] ?></td>
                                <td><?= $clearance['expiry_date'] ?></td>
                                <td class="or-number"><?= htmlspecialchars($clearance['or_number']) ?></td>
                                <td class="amount"><?= number_format($clearance['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $clearance['status'] == 'Approved' ? 'success' : 
                                        ($clearance['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars($clearance['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-clearance" data-id="<?= $clearance['id'] ?>" data-bs-toggle="modal" data-bs-target="#editClearanceModal" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success print-clearance" data-id="<?= $clearance['id'] ?>" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-clearance" data-id="<?= $clearance['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteClearanceModal" title="Delete">
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

    <!-- Request Clearance Modal -->
    <div class="modal fade" id="requestClearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Barangay Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Resident</label>
                            <select name="resident_id" class="form-control" required>
                                <option value="">Select Resident</option>
                                <?php foreach ($residents as $resident): ?>
                                    <option value="<?= $resident['id'] ?>"><?= $resident['full_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Purpose</label>
                            <textarea name="purpose" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>OR Number</label>
                            <input type="text" name="or_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_clearance" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Clearance Modal -->
    <div class="modal fade" id="viewClearanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Clearance Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Resident</label>
                            <input type="text" id="view_resident_name" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Purpose</label>
                            <input type="text" id="view_purpose" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Issue Date</label>
                            <input type="date" id="view_issue_date" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Expiry Date</label>
                            <input type="date" id="view_expiry_date" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>OR Number</label>
                            <input type="text" id="view_or_number" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label>Amount</label>
                            <input type="number" id="view_amount" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <input type="text" id="view_status" class="form-control" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Clearance Modal -->
    <div class="modal fade" id="editClearanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="clearance_id" id="edit_clearance_id">
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
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>OR Number</label>
                                <input type="text" name="or_number" id="edit_or_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Amount</label>
                                <input type="number" name="amount" id="edit_amount" class="form-control" required>
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
                        <button type="submit" name="edit_clearance" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Clearance Modal -->
    <div class="modal fade" id="deleteClearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Clearance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="clearance_id" id="delete_clearance_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this clearance? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_clearance" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Print Template (Hidden) -->
    <div id="certificateTemplate" class="print-certificate">
        <div class="certificate-header">
            <h4>Republic of the Philippines</h4>
            <h5>Province of _______________</h5>
            <h5>Municipality of _______________</h5>
            <h4 class="mt-4">OFFICE OF THE BARANGAY CHAIRMAN</h4>
            <h3 class="mt-4">BARANGAY CLEARANCE</h3>
        </div>
        
        <div class="certificate-body">
            <p class="mb-4">TO WHOM IT MAY CONCERN:</p>
            
            <p>This is to certify that <strong><span id="print-resident-name">_______________</span></strong>, of legal age, Filipino Citizen is a bonafide resident of this Barangay.</p>
            
            <p>This certification is being issued upon the request of the above-named person for <strong><span id="print-purpose">_______________</span></strong> purposes.</p>
            
            <p>Issued this <strong><span id="print-issue-date">_______________</span></strong> at the Barangay Hall, _______________.</p>
            
            <p class="mb-4">OR No.: <strong><span id="print-or-number">_______________</span></strong></p>
            <p>Amount: ₱<strong><span id="print-amount">_______________</span></strong></p>
        </div>
        
        <div class="certificate-footer">
            <div class="signature-line">
                Barangay Chairman
            </div>
        </div>
    </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#clearanceTable').DataTable();

            // View Clearance
            $('.view-clearance').click(function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                var clearance = <?php echo json_encode($clearances); ?>.find(c => c.id == id);
                
                if (clearance) {
                    $('#view_resident_name').val(clearance.resident_name);
                    $('#view_purpose').val(clearance.purpose);
                    $('#view_issue_date').val(clearance.issue_date);
                    $('#view_expiry_date').val(clearance.expiry_date);
                    $('#view_or_number').val(clearance.or_number);
                    $('#view_amount').val(clearance.amount);
                    $('#view_status').val(clearance.status);
                }
            });

            // Edit Clearance
            $('.edit-clearance').click(function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                var clearance = <?php echo json_encode($clearances); ?>.find(c => c.id == id);
                
                if (clearance) {
                    $('#edit_clearance_id').val(clearance.id);
                    $('#edit_resident_id').val(clearance.resident_id);
                    $('#edit_purpose').val(clearance.purpose);
                    $('#edit_issue_date').val(clearance.issue_date);
                    $('#edit_expiry_date').val(clearance.expiry_date);
                    $('#edit_or_number').val(clearance.or_number);
                    $('#edit_amount').val(clearance.amount);
                    $('#edit_status').val(clearance.status);
                }
            });

            // Delete Clearance
            $('.delete-clearance').click(function() {
                var id = $(this).data('id');
                $('#delete_clearance_id').val(id);
            });

            // Print Clearance
            $('.print-clearance').click(function() {
                var row = $(this).closest('tr');
                
                // Get the data from the row
                var residentName = row.find('td:eq(1)').text().trim();
                var purpose = row.find('td:eq(2)').text().trim();
                var issueDate = new Date(row.find('td:eq(3)').text().trim()).toLocaleDateString();
                var orNumber = row.find('td:eq(5)').text().trim();
                var amount = row.find('td:eq(6)').text().trim();

                // Update the certificate template
                $('#print-resident-name').text(residentName);
                $('#print-purpose').text(purpose);
                $('#print-issue-date').text(issueDate);
                $('#print-or-number').text(orNumber);
                $('#print-amount').text(amount);

                // Print
                window.print();
            });
        });
    </script>
</body>
</html>
